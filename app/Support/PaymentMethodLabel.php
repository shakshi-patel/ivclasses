<?php

namespace App\Support;

use Illuminate\Support\Str;

class PaymentMethodLabel
{
    /**
     * Str::headline() splits on every uppercase letter, so known acronyms
     * ("UPI" -> "U P I") get mangled. Keep those intact and headline the rest.
     */
    protected const ACRONYMS = ['UPI', 'NEFT', 'RTGS', 'IMPS', 'ATM'];

    public static function format(?string $value): string
    {
        $value = $value ?: 'Cash';

        foreach (self::ACRONYMS as $acronym) {
            if (strcasecmp($value, $acronym) === 0) {
                return $acronym;
            }
        }

        return Str::headline($value);
    }
}
