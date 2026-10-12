<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'P';
    case Absent = 'A';
    case LatePresent = 'LP';
    case Holiday = 'H';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::LatePresent => 'Late / Partial',
            self::Holiday => 'Holiday',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::Present => 'is-active',
            self::Absent => 'is-navy',
            self::LatePresent => 'is-muted',
            self::Holiday => 'is-muted',
        };
    }

    public static function tryFromMixed(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        $raw = strtoupper(trim((string) $value));
        if ($raw !== '') {
            $fromCode = self::tryFrom($raw);
            if ($fromCode) {
                return $fromCode;
            }
        }

        return match (strtolower(trim((string) $value))) {
            'present' => self::Present,
            'absent' => self::Absent,
            'late', 'late_present', 'lp' => self::LatePresent,
            'holiday' => self::Holiday,
            default => null,
        };
    }
}
