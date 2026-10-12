<?php

namespace App\Enums;

enum DemoStatus: string
{
    case Requested = 'requested';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Completed => 'is-active',
            self::Cancelled => 'is-navy',
            default => 'is-muted',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
