<?php

namespace App\Enums;

enum FeeInvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Partial => 'Partial',
            self::Paid => 'Paid',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Paid => 'is-active',
            self::Partial => 'is-navy',
            self::Unpaid => 'is-muted',
        };
    }

    public static function fromMixed(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        $raw = strtolower(trim((string) $value));

        return self::tryFrom($raw) ?? self::Paid;
    }
}
