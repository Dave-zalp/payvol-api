<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

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
                    'balance'        => $wallet?->balance ?? 0,
                    'ledger_balance' => $wallet?->ledger_balance ?? 0,
                    'is_active'      => $wallet?->is_active ?? false,
                    'exists'         => (bool) $wallet,
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => $request->has('currency') ? $data->first() : $data
        ], 200);
    }
}
