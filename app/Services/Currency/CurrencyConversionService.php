<?php

namespace App\Services\Currency;

use App\Contracts\ExchangeRateProvider;

class CurrencyConversionService
{
    public function __construct(
        protected ExchangeRateProvider $provider
    ) {}

    /**
     * Convert an amount from one currency to another.
     *
     * Usage:
     *   $service->convert(100, 'USD', 'NGN')  → 160000.00
     *   $service->convert(100, 'NGN', 'USD')  → 0.0625
     *   $service->convert(50,  'USD', 'USDT') → 50.00
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $rate = $this->provider->getRate($from, $to);
        return round($amount * $rate, 2);
    }

    /**
     * Get the raw exchange rate between two currencies.
     */
    public function getRate(string $from, string $to): float
    {
        return $this->provider->getRate($from, $to);
    }

    // Convenience helpers

    public function usdToNgn(float $amount): float
    {
        return $this->convert($amount, 'USD', 'NGN');
    }

    public function ngnToUsd(float $amount): float
    {
        return $this->convert($amount, 'NGN', 'USD');
    }

    public function usdToUsdt(float $amount): float
    {
        return $this->convert($amount, 'USD', 'USDT');
    }

    public function usdtToUsd(float $amount): float
    {
        return $this->convert($amount, 'USDT', 'USD');
    }
}
