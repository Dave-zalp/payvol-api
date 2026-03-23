<?php

namespace App\Http\Controllers\VirtualBank;

use App\Http\Controllers\Controller;
use App\Jobs\Virtual\CreateVirtualAccountJob;
use App\Models\VirtualAccount;
use App\Services\VirtualBankAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CrudController extends Controller
{
    protected VirtualBankAccountService $service;

    public function __construct(VirtualBankAccountService $service)
    {
        $this->service = $service;
    }

    public function create(Request $request)
    {
        try {

            $user = $request->user();

            if (VirtualAccount::where('user_id', $user->id)->exists()) {
                return $this->error('Virtual account already exists.', 409);
            }

            CreateVirtualAccountJob::dispatch($user);

            return $this->success('Virtual account creation is being processed.', null, 202);

        } catch (\Throwable $e) {

            Log::error('Virtual Account Creation Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return $this->error('An unexpected error occurred. Please try again later.', 500);
        }
    }

    public function show(Request $request)
    {
        try {

            $virtualAccount = VirtualAccount::where('user_id', $request->user()->id)->first();

            if (!$virtualAccount) {
                return $this->error('Virtual account not found.', 404);
            }

            return $this->success('Virtual account retrieved successfully.', [
                'account_name'   => $virtualAccount->account_name,
                'account_number' => $virtualAccount->account_number,
                'bank_name'      => $virtualAccount->bank_name,
                'currency'       => $virtualAccount->currency,
            ]);

        } catch (\Throwable $e) {

            Log::error('Fetch Virtual Account Error', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return $this->error('Unable to fetch virtual account.', 500);
        }
    }
}
