<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Services\LedgerService;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(LedgerService $ledger): void
    {
        $credits = [
            'USD'  => 500.00,
            'NGN'  => 500000.00,
            'USDT' => 500.00000000,
        ];

        User::all()->each(function (User $user) use ($ledger, $credits) {
            foreach ($credits as $currency => $amount) {
                $wallet = Wallet::where('user_id', $user->id)
                    ->where('currency', $currency)
                    ->first();

                if (!$wallet) {
                    $this->command->warn("No {$currency} wallet found for user {$user->id} — skipping.");
                    continue;
                }

                $ledger->credit(
                    $wallet,
                    $amount,
                    "Test seed credit — {$amount} {$currency}"
                );

                $this->command->info("Credited {$amount} {$currency} to user {$user->id}");
            }
        });
    }
}
