<?php

namespace App\Enums;

enum ProjectInquiryStatus: string
{
    case New = 'new';
    case DemoCompleted = 'demo_completed';
    case Converted = 'converted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::DemoCompleted => 'Demo completed',
            self::Converted => 'Converted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Converted => 'is-active',
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
