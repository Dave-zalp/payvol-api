<?php

namespace App\Services;

use App\Integrations\Strowallet\StrowalletService;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VirtualBankAccountService
{
    protected StrowalletService $integration;

    public function __construct(StrowalletService $integration)
    {
        $this->integration = $integration;
    }

    public function createVirtualAccount($user)
    {

        $accountName = trim(implode(' ', array_filter([
            $user->first_name,
            $user->middle_name,
            $user->surname,
        ])));

        $response = $this->integration->createVirtualAccount([
            'email'        => $user->email,
            'account_name' => $accountName,
            'phone'        => $user->phone,
        ]);

        if (empty($response['accountNumber'])) {
            Log::warning('Strowallet Virtual Account Creation Failed', [
                'user_id'  => $user->id,
                'response' => $response
            ]);

            // Send a dispatch mail that the account could not be created

            throw new \Exception($response['message'] ?? 'Failed to create virtual account.');
        }

        DB::transaction(function () use ($user, $response, &$virtualAccount) {

            $virtualAccount = VirtualAccount::create([
                'user_id'            => $user->id,
                'account_name'       => $user->name,
                'account_number'     => $response['accountNumber'],
                'bank_name'          => $response['sourceBankName'] ?? null,
                'provider_reference' => $response['sessionId'] ?? null,
                'currency'           => $response['currency'] ?? 'NGN',
                'provider_name'      => 'strowallet',
                'balance'            => 0,
                'response'           => $response,
            ]);

            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'currency' => 'NGN'],
                [
                    'balance'        => 0,
                    'ledger_balance' => 0,
                    'is_active'      => true,
                ]
            );

            // Send a dispatch mail that the account was created

        });

        return $virtualAccount;
    }
}
