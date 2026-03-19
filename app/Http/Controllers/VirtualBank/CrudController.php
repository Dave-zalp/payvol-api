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
                return;
            }

            // Dispatch queue job
            CreateVirtualAccountJob::dispatch($user);

            return response()->json([
                'status'  => true,
                'message' => 'Virtual account creation is being processed.',
            ], 202);

        } catch (\Throwable $e) {

            // Log the real error for debugging
            Log::error('Virtual Account Creation Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $user = $request->user();

            $virtualAccount = VirtualAccount::where('user_id', $user->id)->first();

            if (!$virtualAccount) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Virtual account not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'account_name'   => $virtualAccount->account_name,
                    'account_number' => $virtualAccount->account_number,
                    'bank_name'      => $virtualAccount->bank_name,
                    'currency'       => $virtualAccount->currency,
                ]
            ], 200);

        } catch (\Throwable $e) {
            \Log::error('Fetch Virtual Account Error', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch virtual account.'
            ], 500);
        }
    }
}
