<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Named-route based admin URLs (no hardcoded /admin/... paths).
 */
final class AdminUrl
{
    /**
     * Base for cascade lookups: …/admin/lookups
     */
    public static function lookups(): string
    {
        return Str::beforeLast(route('admin.lookups.branches'), '/branches');
    }
}
