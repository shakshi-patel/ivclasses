<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount, ?string $currency = 'INR', int $decimals = 0): string
    {
        $currency = strtoupper((string) $currency);
        $prefix = $currency === '' || $currency === 'INR' ? '₹' : $currency.' ';

        return $prefix.number_format((float) $amount, $decimals);
    }
}
