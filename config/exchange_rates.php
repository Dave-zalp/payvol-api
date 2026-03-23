<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exchange Rates
    |--------------------------------------------------------------------------
    | Rates are defined as: how many units of the target currency equal 1 USD.
    | All conversions are routed through USD as the base currency.
    |
    | To update rates, change the values here or override via environment
    | variables. When you integrate a live rates API, swap out the
    | FixedRateProvider binding in AppServiceProvider.
    |
    */

    'usd_to_ngn' => (float) env('RATE_USD_TO_NGN', 1600.00),
    'usd_to_usdt' => (float) env('RATE_USD_TO_USDT', 1.00),

];
