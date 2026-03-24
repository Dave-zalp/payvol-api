<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(string $currency = '')
    {
        $message = $currency
            ? "Insufficient balance in {$currency} wallet."
            : 'Insufficient wallet balance.';

        parent::__construct($message);
    }
}
