<?php

namespace App\Services;

use App\Integrations\Strowallet\StrowalletService;
use App\Jobs\USD\FetchCardDetailsJob;
use App\Models\CardTransaction;
use App\Models\Transaction;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StrowalletCardService
{
    public function __construct(
        protected StrowalletService $integration,
        protected LedgerService $ledger
    ) {}

    public function createCard(User $user, string $walletId, float $prefundAmount, float $deduction): array
    {
        $customer    = $user->strowalletCustomer;
        $wallet      = Wallet::findOrFail($walletId);
        $creationFee = 2.00;
        $serviceFee  = round($prefundAmount * 0.023, 2);

        // Debit wallet and record pending transaction atomically
        $platformTx = DB::transaction(function () use ($user, $wallet, $prefundAmount, $creationFee, $serviceFee, $deduction) {
            $platformTx = Transaction::create([
                'user_id'        => $user->id,
                'reference'      => Transaction::generateReference(),
                'type'           => 'card_creation',
                'channel'        => 'virtual_card',
                'amount'         => $prefundAmount,
                'fee'            => $creationFee + $serviceFee,
                'currency'       => 'USD',
                'status'         => 'pending',
                'description'    => 'Virtual card creation',
                'balance_before' => (float) $wallet->balance,
                'balance_after'  => (float) $wallet->balance - $deduction,
                'metadata'       => [
                    'wallet_currency' => $wallet->currency,
                    'wallet_deduction' => $deduction,
                    'creation_fee'    => $creationFee,
                    'service_fee'     => $serviceFee,
                ],
            ]);

            $this->ledger->debit(
                $wallet,
                $deduction,
                "Card creation — \${$prefundAmount} prefund + \${$creationFee} creation fee + \${$serviceFee} service fee",
                $platformTx
            );

            return $platformTx;
        });

        try {
            $payload = [
                'name_on_card'  => $customer->first_name . ' ' . $customer->last_name,
                'card_type'     => 'visa',
                'public_key'    => config('services.strowallet.public_key'),
                'amount'        => (string) $prefundAmount,
                'customerEmail' => $customer->customer_email,
                'mode'          => config('services.strowallet.mode', 'sandbox'),
            ];

            $response = $this->integration->createCard($payload);
            $success  = $response['success'] ?? false;
            $data     = $response['response'] ?? null;

            if (!$success || !$data || empty($data['card_id'])) {
                $platformTx->markFailed();
                $this->ledger->credit(
                    $wallet,
                    $deduction,
                    'Refund: card creation failed',
                    $platformTx
                );

                Log::warning('Strowallet Card Creation Failed', [
                    'user_id'  => $user->id,
                    'response' => $response,
                ]);

                throw new \Exception(
                    is_string($response['message'] ?? null)
                        ? $response['message']
                        : json_encode($response['message'] ?? 'Failed to create card')
                );
            }

            $virtualCard = DB::transaction(function () use ($user, $data, $response, $platformTx) {
                $platformTx->markSuccess();

                return VirtualCard::create([
                    'user_id'         => $user->id,
                    'card_id'         => $data['card_id'],
                    'card_user_id'    => $data['card_user_id'] ?? null,
                    'reference'       => $data['reference'] ?? null,
                    'name_on_card'    => $data['name_on_card'],
                    'card_brand'      => $data['card_brand'] ?? null,
                    'card_type'       => $data['card_type'] ?? null,
                    'card_status'     => $data['card_status'] ?? 'pending',
                    'customer_id'     => $data['customer_id'] ?? null,
                    'card_created_at' => $data['card_created_date'] ?? null,
                    'response'        => $response,
                ]);
            });

            // Fetch and store full card details (card number, CVV, expiry, billing, etc.)
            // Delayed 15s to allow Strowallet to finish provisioning the card
            FetchCardDetailsJob::dispatch($virtualCard->id)->delay(now()->addSeconds(15));

            return $data;

        } catch (\Throwable $e) {
            if ($platformTx->isPending()) {
                $platformTx->markFailed();
                $this->ledger->credit(
                    $wallet,
                    $deduction,
                    'Refund: card creation failed',
                    $platformTx
                );
            }
            throw $e;
        }
    }

    public function toggleCardStatus(User $user, string $id, string $action): array
    {
        $card = VirtualCard::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if (!in_array($action, ['freeze', 'unfreeze'])) {
            throw new \InvalidArgumentException('Action must be freeze or unfreeze.');
        }

        if ($action === 'freeze' && !$card->isActive()) {
            throw new \Exception('Card is not active and cannot be frozen.');
        }

        if ($action === 'unfreeze' && $card->card_status !== 'frozen') {
            throw new \Exception('Card is not frozen.');
        }

        $response = $this->integration->updateCardStatus($card->card_id, $action);

        $success = $response['success'] ?? false;

        if (!$success) {
            Log::warning('Strowallet Update Card Status Failed', [
                'user_id'  => $user->id,
                'action'   => $action,
                'response' => $response,
            ]);

            throw new \Exception(
                is_string($response['message'] ?? null)
                    ? $response['message']
                    : json_encode($response['message'] ?? 'Failed to update card status')
            );
        }

        $card->update([
            'card_status' => $action === 'freeze' ? 'frozen' : 'active',
        ]);

        return $response;
    }

    public function getUserCards(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return VirtualCard::where('user_id', $user->id)->latest()->get();
    }

    public function fundCard(User $user, string $id, float $amount, string $walletId, float $deduction): array
    {
        $card   = VirtualCard::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $wallet = Wallet::findOrFail($walletId);
        $fee    = round($amount * 0.023, 2);

        // Debit wallet and create pending platform transaction atomically
        $platformTx = DB::transaction(function () use ($user, $card, $wallet, $amount, $fee, $deduction) {
            $platformTx = Transaction::create([
                'user_id'           => $user->id,
                'reference'         => Transaction::generateReference(),
                'type'              => 'card_fund',
                'channel'           => 'virtual_card',
                'amount'            => $amount,
                'fee'               => $fee,
                'currency'          => 'USD',
                'status'            => 'pending',
                'description'       => 'Virtual card funding',
                'transactable_type' => VirtualCard::class,
                'transactable_id'   => $card->id,
                'balance_before'    => (float) $wallet->balance,
                'balance_after'     => (float) $wallet->balance - $deduction,
                'metadata'          => ['wallet_currency' => $wallet->currency, 'wallet_deduction' => $deduction],
            ]);

            $this->ledger->debit(
                $wallet,
                $deduction,
                "Card funding — {$amount} USD + {$fee} USD fee",
                $platformTx
            );

            return $platformTx;
        });

        try {
            $payload = [
                'card_id'    => $card->card_id,
                'amount'     => (string) $amount,
                'public_key' => config('services.strowallet.public_key'),
                'mode'       => config('services.strowallet.mode', 'sandbox'),
            ];

            $response = $this->integration->fundCard($payload);
            $success  = $response['success'] ?? false;

            if (!$success) {
                $platformTx->markFailed();
                $this->ledger->credit(
                    $wallet,
                    $deduction,
                    'Refund: card funding failed',
                    $platformTx
                );

                Log::warning('Strowallet Fund Card Failed', [
                    'user_id'  => $user->id,
                    'response' => $response,
                ]);

                throw new \Exception(
                    is_string($response['message'] ?? null)
                        ? $response['message']
                        : json_encode($response['message'] ?? 'Failed to fund card')
                );
            }

            $providerData = $response['apiresponse']['data'] ?? [];

            DB::transaction(function () use ($user, $card, $platformTx, $providerData, $response) {
                $platformTx->markSuccess();

                CardTransaction::create([
                    'user_id'         => $user->id,
                    'virtual_card_id' => $card->id,
                    'transaction_id'  => $platformTx->id,
                    'provider_id'     => $providerData['id'] ?? null,
                    'card_id'         => $card->card_id,
                    'type'            => $providerData['type'] ?? 'credit',
                    'method'          => $providerData['method'] ?? 'topup',
                    'narrative'       => $providerData['narrative'] ?? 'Top-up card',
                    'amount'          => ($providerData['centAmount'] ?? 0) / 100,
                    'cent_amount'     => $providerData['centAmount'] ?? 0,
                    'currency'        => $providerData['currency'] ?? 'usd',
                    'status'          => $providerData['status'] ?? 'pending',
                    'reference'       => $providerData['reference'] ?? null,
                    'transacted_at'   => $providerData['createdAt'] ?? now(),
                    'metadata'        => $response,
                ]);
            });

            return $providerData;

        } catch (\Throwable $e) {
            if ($platformTx->isPending()) {
                $platformTx->markFailed();
                $this->ledger->credit(
                    $wallet,
                    $deduction,
                    'Refund: card funding failed',
                    $platformTx
                );
            }
            throw $e;
        }
    }

    public function getCardDetails(User $user, string $id): array
    {
        $card = VirtualCard::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $response = $this->integration->fetchCardDetail($card->card_id);

        $success = $response['success'] ?? false;

        if (!$success) {
            Log::warning('Strowallet Fetch Card Detail Failed', [
                'user_id'  => $user->id,
                'response' => $response,
            ]);

            throw new \Exception(
                is_string($response['message'] ?? null)
                    ? $response['message']
                    : json_encode($response['message'] ?? 'Failed to fetch card details')
            );
        }

        $detail = $response['response']['card_detail'];

        // Sync the local record with whatever Strowallet returns right now
        $card->update(array_filter([
            'card_status'      => $detail['card_status'] ?? null,
            'card_number'      => $detail['card_number'] ?? null,
            'last4'            => $detail['last4'] ?? null,
            'cvv'              => $detail['cvv'] ?? null,
            'expiry'           => $detail['expiry'] ?? null,
            'balance'          => $detail['balance'] ?? null,
            'customer_email'   => $detail['customer_email'] ?? null,
            'billing_country'  => $detail['billing_country'] ?? null,
            'billing_city'     => $detail['billing_city'] ?? null,
            'billing_street'   => $detail['billing_street'] ?? null,
            'billing_zip_code' => $detail['billing_zip_code'] ?? null,
            'card_details'     => $response,
        ], fn($value) => !is_null($value)));

        return $detail;
    }

    public function getCardTransactions(User $user, string $id): array
    {
        $card = VirtualCard::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $response = $this->integration->fetchCardTransactions($card->card_id);

        $success = $response['success'] ?? false;

        if (!$success) {
            Log::warning('Strowallet Fetch Card Transactions Failed', [
                'user_id'  => $user->id,
                'response' => $response,
            ]);

            throw new \Exception(
                is_string($response['message'] ?? null)
                    ? $response['message']
                    : json_encode($response['message'] ?? 'Failed to fetch card transactions')
            );
        }

        $providerTransactions = $response['response']['card_transactions'] ?? [];

        // Upsert provider transactions into local card_transactions table
        foreach ($providerTransactions as $txn) {
            if (empty($txn['id'])) {
                continue;
            }

            CardTransaction::updateOrCreate(
                ['provider_id' => $txn['id']],
                [
                    'user_id'         => $user->id,
                    'virtual_card_id' => $card->id,
                    'card_id'         => $card->card_id,
                    'type'            => $txn['type'] ?? 'unknown',
                    'method'          => $txn['method'] ?? 'unknown',
                    'narrative'       => $txn['narrative'] ?? null,
                    'amount'          => $txn['amount'] ?? 0,
                    'cent_amount'     => $txn['centAmount'] ?? 0,
                    'currency'        => $txn['currency'] ?? 'usd',
                    'status'          => $txn['status'] ?? 'pending',
                    'reference'       => $txn['reference'] ?? null,
                    'transacted_at'   => $txn['createdAt'] ?? null,
                    'metadata'        => $txn,
                ]
            );
        }

        return $providerTransactions;
    }
}
