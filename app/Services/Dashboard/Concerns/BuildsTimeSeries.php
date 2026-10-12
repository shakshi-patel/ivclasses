<?php

namespace App\Services\Dashboard\Concerns;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

trait BuildsTimeSeries
{
    /**
     * Month buckets ending with the current month.
     *
     * @return array{0: Collection<string, string>, 1: CarbonImmutable}  [Y-m => label], first month start
     */
    protected function lastMonths(int $count): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($count - 1);

        $months = collect(range(0, $count - 1))
            ->mapWithKeys(function (int $offset) use ($start) {
                $month = $start->addMonths($offset);

                return [$month->format('Y-m') => $month->format('M y')];
            });

        return [$months, $start];
    }

    /**
     * Day buckets ending today.
     *
     * @return array{0: Collection<string, string>, 1: CarbonImmutable}  [Y-m-d => label], first day
     */
    protected function lastDays(int $count): array
    {
        $end = CarbonImmutable::today();
        $start = $end->subDays($count - 1);

        $days = collect(CarbonPeriod::create($start, $end))
            ->mapWithKeys(fn ($day) => [$day->format('Y-m-d') => $day->format('d M')]);

        return [$days, $start];
    }

    /**
     * Null when there is no baseline (previous = 0, current > 0).
     */
    protected function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? null : 0.0;
        }

        return round(($current - $previous) * 100 / $previous, 1);
    }

    /**
     * @param  Collection<string, string>  $buckets
     * @param  iterable<string, int|float>  $values
     * @return list<int|float>
     */
    protected function fillBuckets(Collection $buckets, iterable $values): array
    {
        $values = collect($values);

        return $buckets->keys()->map(fn (string $key) => $values->get($key, 0))->values()->all();
    }
}
