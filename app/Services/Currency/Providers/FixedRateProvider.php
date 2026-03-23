<?php

namespace App\Services\Currency\Providers;

use App\Contracts\ExchangeRateProvider;

class FixedRateProvider implements ExchangeRateProvider
{
    /**
     * All conversions are resolved through USD as the base currency.
     * Rates represent: 1 USD = X of the given currency.
     */
    private array $usdRates;

    public function __construct()
    {
        $this->usdRates = [
            'USD'  => 1.0,
            'NGN'  => config('exchange_rates.usd_to_ngn'),
            'USDT' => config('exchange_rates.usd_to_usdt'),
        ];
    }

    public function getRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        if (!isset($this->usdRates[$from])) {
            throw new \InvalidArgumentException("Unsupported source currency: {$from}");
        }

        if (!isset($this->usdRates[$to])) {
            throw new \InvalidArgumentException("Unsupported target currency: {$to}");
        }

        // Convert from → USD → to
        $fromToUsd = 1 / $this->usdRates[$from];
        return $fromToUsd * $this->usdRates[$to];
    }
}
