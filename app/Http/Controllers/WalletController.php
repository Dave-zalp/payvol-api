<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * GET /wallets
     * Returns balances for all 3 wallets (or a single one if ?currency= is passed).
     */
    public function index(Request $request)
    {
        $user       = $request->user();
        $currencies = ['NGN', 'USD', 'USDT'];

        $query = Wallet::where('user_id', $user->id)
            ->whereIn('currency', $currencies);

        if ($request->has('currency')) {
            $request->validate([
                'currency' => ['required', 'string', 'in:NGN,USD,USDT'],
            ]);

            $query->where('currency', strtoupper($request->currency));
        }

        $wallets = $query->get();

        $data = collect($request->has('currency') ? [$request->currency] : $currencies)
            ->map(function ($currency) use ($wallets) {
                $wallet = $wallets->firstWhere('currency', $currency);

                return [
                    'currency'       => $currency,
                    'balance'        => $wallet?->balance ?? '0.00000000',
                    'ledger_balance' => $wallet?->ledger_balance ?? '0.00000000',
                    'is_active'      => $wallet?->is_active ?? false,
                    'exists'         => (bool) $wallet,
                ];
            });

        return $this->success(
            'Wallets retrieved successfully.',
            $request->has('currency') ? $data->first() : $data
        );
    }

    /**
     * GET /wallets/transactions
     *
     * Query params:
     *   currency  — filter by NGN | USD | USDT
     *   type      — filter by credit | debit
     *   per_page  — items per page (default 20, max 100)
     */
    public function transactions(Request $request)
    {
        $request->validate([
            'currency' => ['nullable', 'string', 'in:NGN,USD,USDT'],
            'type'     => ['nullable', 'string', 'in:credit,debit'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();

        $query = WalletLedgerEntry::where('user_id', $user->id)
            ->select([
                'id',
                'wallet_id',
                'transaction_id',
                'type',
                'amount',
                'currency',
                'description',
                'running_balance',
                'reference',
                'created_at',
            ])
            ->latest();

        if ($request->filled('currency')) {
            $query->where('currency', strtoupper($request->currency));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $entries = $query->paginate($request->per_page ?? 20);

        return $this->success('Transactions retrieved successfully.', $entries);
    }

    /**
     * GET /wallets/{currency}/balance
     *
     * Returns the live balance for a single currency wallet,
     * including a breakdown of total credits vs debits from the ledger.
     */
    public function balance(Request $request, string $currency)
    {
        $currency = strtoupper($currency);

        if (!in_array($currency, ['NGN', 'USD', 'USDT'])) {
            return $this->error('Invalid currency. Use NGN, USD, or USDT.', 422);
        }

        $user   = $request->user();
        $wallet = Wallet::where('user_id', $user->id)
            ->where('currency', $currency)
            ->first();

        if (!$wallet) {
            return $this->success('Wallet not found.', [
                'currency'      => $currency,
                'balance'       => '0.00000000',
                'total_credits' => '0.00000000',
                'total_debits'  => '0.00000000',
                'exists'        => false,
            ]);
        }

        $credits = (float) WalletLedgerEntry::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->sum('amount');

        $debits = (float) WalletLedgerEntry::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->sum('amount');

        return $this->success('Balance retrieved successfully.', [
            'currency'       => $currency,
            'balance'        => $wallet->balance,
            'ledger_balance' => $wallet->ledger_balance,
            'total_credits'  => number_format($credits, 8, '.', ''),
            'total_debits'   => number_format($debits, 8, '.', ''),
            'is_active'      => $wallet->is_active,
        ]);
    }
}
