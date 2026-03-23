<?php

namespace App\Contracts;

interface ExchangeRateProvider
{
    /**
     * Get the exchange rate from one currency to another.
     * e.g. getRate('USD', 'NGN') → 1600.00
     */
    public function getRate(string $from, string $to): float;
}
