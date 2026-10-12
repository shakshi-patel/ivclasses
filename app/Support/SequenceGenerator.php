<?php

namespace App\Support;

use App\Models\Institute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Serializes code generation so concurrent creates cannot mint the same sequence.
 */
class SequenceGenerator
{
    /**
     * @param  callable(): string  $mint  Called inside a transaction after the lock is held.
     */
    public static function forInstitute(int $instituteId, callable $mint): string
    {
        return Cache::lock('seq:institute:'.$instituteId, 10)->block(10, function () use ($instituteId, $mint) {
            return DB::transaction(function () use ($instituteId, $mint) {
                Institute::query()->whereKey($instituteId)->lockForUpdate()->firstOrFail();

                return $mint();
            });
        });
    }

    /**
     * @param  callable(): string  $mint
     */
    public static function global(string $key, callable $mint): string
    {
        return Cache::lock('seq:global:'.$key, 10)->block(10, function () use ($mint) {
            return DB::transaction(fn () => $mint());
        });
    }
}
