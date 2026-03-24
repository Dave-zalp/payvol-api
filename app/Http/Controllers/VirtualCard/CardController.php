<?php

namespace App\Http\Controllers\VirtualCard;

use App\Http\Controllers\Controller;
use App\Jobs\USD\CreateCardJob;
use App\Jobs\USD\FundCardJob;
use App\Models\CardTransaction;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Services\Currency\CurrencyConversionService;
use App\Services\StrowalletCardService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardController extends Controller
{

    public function index(Request $request, StrowalletCardService $service)
    {
        try {

            $cards = $service->getUserCards($request->user());

            return $this->success('Cards retrieved successfully.', $cards);

        } catch (\Throwable $e) {

            Log::error('Get Cards Error', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage() ?: 'Unable to retrieve cards.', 500);
        }
    }

    public function show(Request $request, string $id, StrowalletCardService $service)
    {
        try {

            $cardDetail = $service->getCardDetails($request->user(), $id);

            return $this->success('Card details retrieved successfully.', $cardDetail);

        } catch (ModelNotFoundException $e) {

            return $this->error('Card not found.', 404);

        } catch (\Throwable $e) {

            Log::error('Fetch Card Detail Error', [
                'user_id' => $request->user()?->id,
                'card_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage() ?: 'Unable to fetch card details.', 500);
        }
    }

    public function fund(Request $request, string $id, StrowalletCardService $service, CurrencyConversionService $fx)
    {
        $request->validate([
            'amount'    => ['nullable', 'numeric', 'min:1'],
            'wallet_id' => ['required', 'uuid'],
        ]);

        try {

            $user = $request->user();

            $card = VirtualCard::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            if (!$card->isActive()) {
                return $this->error('Card is not active and cannot be funded.', 422);
            }

            $wallet = Wallet::where('id', $request->wallet_id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$wallet) {
                return $this->error('Wallet not found or inactive.', 422);
            }

            $amount   = (float) ($request->amount ?? 5);
            $fee      = round($amount * 0.023, 2);
            $totalUsd = $amount + $fee;

            $deduction = match ($wallet->currency) {
                'NGN'  => $fx->usdToNgn($totalUsd),
                'USDT' => $fx->usdToUsdt($totalUsd),
                default => $totalUsd,
            };

            if ((float) $wallet->balance < $deduction) {
                return $this->error('Insufficient wallet balance.', 422);
            }

            FundCardJob::dispatch($user, $id, $amount, $wallet->id, $deduction);

            return $this->success('Card funding in progress.');

        } catch (ModelNotFoundException $e) {

            return $this->error('Card not found.', 404);

        } catch (\Throwable $e) {

            Log::error('Fund Card Error', [
                'user_id' => $request->user()?->id,
                'card_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage() ?: 'Unable to fund card.', 500);
        }
    }

    public function transactions(Request $request, string $id, StrowalletCardService $service)
    {
        try {

            $transactions = $service->getCardTransactions($request->user(), $id);

            return $this->success('Card transactions retrieved successfully.', $transactions);

        } catch (ModelNotFoundException $e) {

            return $this->error('Card not found.', 404);

        } catch (\Throwable $e) {

            Log::error('Card Transactions Error', [
                'user_id' => $request->user()?->id,
                'card_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage() ?: 'Unable to fetch card transactions.', 500);
        }
    }

    public function toggleStatus(Request $request, string $id, string $action, StrowalletCardService $service)
    {
        if (!in_array($action, ['freeze', 'unfreeze'])) {
            return $this->error('Invalid action. Use freeze or unfreeze.', 422);
        }

        try {

            $service->toggleCardStatus($request->user(), $id, $action);

            $message = $action === 'freeze' ? 'Card frozen successfully.' : 'Card unfrozen successfully.';

            return $this->success($message);

        } catch (ModelNotFoundException $e) {

            return $this->error('Card not found.', 404);

        } catch (\Throwable $e) {

            Log::error('Toggle Card Status Error', [
                'user_id' => $request->user()?->id,
                'card_id' => $id,
                'action'  => $action,
                'message' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage() ?: 'Unable to update card status.', 500);
        }
    }

    public function cardBalance(Request $request, string $id)
    {
        try {

            $card = VirtualCard::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();

            $credits = (float) CardTransaction::where('virtual_card_id', $card->id)
                ->where('type', 'credit')
                ->where('status', 'success')
                ->sum('amount');

            $debits = (float) CardTransaction::where('virtual_card_id', $card->id)
                ->whereIn('type', ['debit', 'authorization'])
                ->where('status', 'success')
                ->sum('amount');

            return $this->success('Card balance retrieved.', [
                'card_id'       => $card->card_id,
                'card_status'   => $card->card_status,
                'currency'      => 'USD',
                'balance'       => round($credits - $debits, 2),
                'total_credits' => round($credits, 2),
                'total_debits'  => round($debits, 2),
            ]);

        } catch (ModelNotFoundException $e) {

            return $this->error('Card not found.', 404);

        } catch (\Throwable $e) {

            Log::error('Card Balance Error', [
                'user_id' => $request->user()?->id,
                'card_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return $this->error('Unable to retrieve card balance.', 500);
        }
    }

    public function create(Request $request, StrowalletCardService $service, CurrencyConversionService $fx)
    {
        $request->validate([
            'wallet_id' => ['required', 'uuid'],
            'amount'    => ['nullable', 'numeric', 'min:1'],
        ]);

        try {

            $user = $request->user();

            if (!$user->strowalletCustomer) {
                return $this->error('You do not have a Strowallet customer profile.', 422);
            }

            if (VirtualCard::where('user_id', $user->id)->whereIn('card_status', ['pending', 'active'])->exists()) {
                return $this->error('You already have a card or one is being created.', 409);
            }

            $wallet = Wallet::where('id', $request->wallet_id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$wallet) {
                return $this->error('You would need to create a bank account', 422);
            }

            $prefundAmount = (float) ($request->amount ?? 5);
            $creationFee   = 2.00;
            $serviceFee    = round($prefundAmount * 0.023, 2);
            $totalUsd      = $prefundAmount + $creationFee + $serviceFee;

            $deduction = match ($wallet->currency) {
                'NGN'  => $fx->usdToNgn($totalUsd),
                'USDT' => $fx->usdToUsdt($totalUsd),
                default => $totalUsd,
            };

            if ((float) $wallet->balance < $deduction) {
                return $this->error(
                    "Insufficient balance. Total required: \${$totalUsd} USD (prefund \${$prefundAmount} + \$2.00 creation fee + \${$serviceFee} service fee).",
                    422
                );
            }

            CreateCardJob::dispatch($user, $wallet->id, $prefundAmount, $deduction);

            return $this->success('Card creation in progress.');

        } catch (\Throwable $e) {

            Log::error('Create Card Error', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return $this->error($e->getMessage() ?: 'Unable to create card.', 500);
        }
    }

}
