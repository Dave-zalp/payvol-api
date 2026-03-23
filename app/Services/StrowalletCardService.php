<?php

namespace App\Services;

use App\Integrations\Strowallet\StrowalletService;
use App\Models\CardTransaction;
use App\Models\Transaction;
use App\Models\VirtualCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StrowalletCardService
{
    public function __construct(
        protected StrowalletService $integration
    ) {}

    public function createCard(User $user)
    {
        $customer = $user->strowalletCustomer;

        $payload = [
            'name_on_card'  => $customer->first_name . ' ' . $customer->last_name,
            'card_type'     => 'visa',
            'public_key'    => config('services.strowallet.public_key'),
            'amount'        => (string) 3,
            'customerEmail' => $customer->customer_email,
            'mode'          => 'sandbox', // remove in production
        ];

        $response = $this->integration->createCard($payload);

        $success = $response['success'] ?? false;
        $data    = $response['response'] ?? null;

        if (!$success || !$data || empty($data['card_id'])) {
            Log::warning('Strowallet Card Creation Failed', [
                'user_id'  => $user->id,
                'response' => $response
            ]);

            throw new \Exception(
                is_string($response['message'])
                    ? $response['message']
                    : json_encode($response['message'])
            );
        }

        DB::transaction(function () use ($user, $data, $response) {

            VirtualCard::create([
                'user_id'          => $user->id,
                'card_id'          => $data['card_id'],
                'card_user_id'     => $data['card_user_id'] ?? null,
                'reference'        => $data['reference'] ?? null,
                'name_on_card'     => $data['name_on_card'],
                'card_brand'       => $data['card_brand'] ?? null,
                'card_type'        => $data['card_type'] ?? null,
                'card_status'      => $data['card_status'] ?? 'pending',
                'customer_id'      => $data['customer_id'] ?? null,
                'card_created_at'  => $data['card_created_date'] ?? null,
                'response'         => $response,
            ]);

        });

        return $data;
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

    public function fundCard(User $user, string $id, float $amount): array
    {
        $card = VirtualCard::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if (!$card->isActive()) {
            throw new \Exception('Card is not active and cannot be funded.');
        }

        // Create a pending platform transaction before hitting the provider
        $platformTx = DB::transaction(function () use ($user, $card, $amount) {
            return Transaction::create([
                'user_id'           => $user->id,
                'reference'         => Transaction::generateReference(),
                'type'              => 'card_fund',
                'channel'           => 'virtual_card',
                'amount'            => $amount,
                'currency'          => 'USD',
                'status'            => 'pending',
                'description'       => 'Virtual card funding',
                'transactable_type' => VirtualCard::class,
                'transactable_id'   => $card->id,
            ]);
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
                    'user_id'        => $user->id,
                    'virtual_card_id'=> $card->id,
                    'transaction_id' => $platformTx->id,
                    'provider_id'    => $providerData['id'] ?? null,
                    'card_id'        => $card->card_id,
                    'type'           => $providerData['type'] ?? 'credit',
                    'method'         => $providerData['method'] ?? 'topup',
                    'narrative'      => $providerData['narrative'] ?? 'Top-up card',
                    'amount'         => ($providerData['centAmount'] ?? 0) / 100,
                    'cent_amount'    => $providerData['centAmount'] ?? 0,
                    'currency'       => $providerData['currency'] ?? 'usd',
                    'status'         => $providerData['status'] ?? 'pending',
                    'reference'      => $providerData['reference'] ?? null,
                    'transacted_at'  => $providerData['createdAt'] ?? now(),
                    'metadata'       => $response,
                ]);

            });

            return $providerData;

        } catch (\Throwable $e) {
            // Ensure platform tx is marked failed if not already
            if ($platformTx->isPending()) {
                $platformTx->markFailed();
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

        return $response['response']['card_detail'];
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
