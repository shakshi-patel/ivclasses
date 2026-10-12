<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Paid => 'is-active',
            self::Pending => 'is-muted',
            self::Failed, self::Refunded => 'is-navy',
        };
    }
}
