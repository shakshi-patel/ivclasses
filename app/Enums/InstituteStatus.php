<?php

namespace App\Enums;

enum InstituteStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Cancelled => 'Cancelled',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Active => 'is-active',
            self::Trial => 'is-muted',
            default => 'is-navy',
        };
    }
}
