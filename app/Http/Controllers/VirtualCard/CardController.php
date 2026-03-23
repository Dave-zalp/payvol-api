<?php

namespace App\Http\Controllers\VirtualCard;

use App\Http\Controllers\Controller;
use App\Jobs\USD\CreateCardJob;
use App\Jobs\USD\FundCardJob;
use App\Models\VirtualCard;
use App\Services\StrowalletCardService;
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

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

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

    public function fund(Request $request, string $id, StrowalletCardService $service)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        try {

            FundCardJob::dispatch($request->user(), $id, (float) $request->amount);

            return $this->success('Card funding in progress.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

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

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

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

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

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

    public function create(Request $request, StrowalletCardService $service)
    {
        try {

            $user = $request->user();

            if (!$user->strowalletCustomer) {
                return $this->error('You do not have a Strowallet customer profile.', 422);
            }

            // $usdWallet = $user->wallets()->where('currency', 'USD')->where('is_active', true)->first();

            // if (!$usdWallet) {
            //     return $this->error('You do not have an active USD wallet.', 422);
            // }

            if (VirtualCard::where('user_id', $user->id)->where('card_status', 'pending')->exists()) {
                return $this->error('Card creation already in progress.', 409);
            }

            // Add a charge check here

            CreateCardJob::dispatch($user);

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
