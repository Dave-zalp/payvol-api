<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Credit a wallet (money coming in).
     */
    public function credit(
        Wallet $wallet,
        float $amount,
        string $description,
        ?Transaction $tx = null,
        array $metadata = []
    ): WalletLedgerEntry {
        return $this->record($wallet, 'credit', $amount, $description, $tx, $metadata);
    }

    /**
     * Debit a wallet (money going out).
     *
     * @throws InsufficientBalanceException
     */
    public function debit(
        Wallet $wallet,
        float $amount,
        string $description,
        ?Transaction $tx = null,
        array $metadata = []
    ): WalletLedgerEntry {
        return $this->record($wallet, 'debit', $amount, $description, $tx, $metadata);
    }

    /**
     * Compute the authoritative balance from ledger entries.
     * Use this to verify the cached wallet.balance is correct.
     */
    public function computeBalance(Wallet $wallet): float
    {
        $credits = (float) WalletLedgerEntry::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->sum('amount');

        $debits = (float) WalletLedgerEntry::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->sum('amount');

        return round($credits - $debits, 8);
    }

    /**
     * Check whether the cached wallet.balance matches the ledger sum.
     */
    public function verifyBalance(Wallet $wallet): bool
    {
        return abs($this->computeBalance($wallet) - (float) $wallet->balance) < 0.000_000_01;
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    private function record(
        Wallet $wallet,
        string $type,
        float $amount,
        string $description,
        ?Transaction $tx,
        array $metadata
    ): WalletLedgerEntry {
        return DB::transaction(function () use ($wallet, $type, $amount, $description, $tx, $metadata) {
            // Lock the wallet row to prevent concurrent balance corruption
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            $currentBalance = (float) $wallet->balance;
            $newBalance     = $type === 'credit'
                ? $currentBalance + $amount
                : $currentBalance - $amount;

            if ($type === 'debit' && $newBalance < 0) {
                throw new InsufficientBalanceException($wallet->currency);
            }

            $entry = WalletLedgerEntry::create([
                'wallet_id'       => $wallet->id,
                'user_id'         => $wallet->user_id,
                'transaction_id'  => $tx?->id,
                'type'            => $type,
                'amount'          => $amount,
                'currency'        => $wallet->currency,
                'description'     => $description,
                'running_balance' => $newBalance,
                'reference'       => WalletLedgerEntry::generateReference(),
                'metadata'        => $metadata ?: null,
            ]);

            // Update the cached balance
            $wallet->update(['balance' => $newBalance]);

            return $entry;
        });
    }
}
