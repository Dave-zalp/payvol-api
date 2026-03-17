<?php

namespace App\Http\Controllers\VirtualBank;

use App\Http\Controllers\Controller;
use App\Jobs\Virtual\CreateVirtualAccountJob;
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

            // Build full name
            $user->name = trim(implode(' ', array_filter([
                $user->first_name,
                $user->middle_name,
                $user->surname,
            ])));

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
}
