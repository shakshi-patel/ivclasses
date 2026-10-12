<?php

namespace App\Services\Dashboard\Concerns;

use App\Models\Batch;
use App\Models\StudentAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Chart payloads built from an already-scoped StudentAttendance query.
 * Requires BuildsTimeSeries.
 */
trait SummarisesAttendance
{
    /**
     * Daily present / late / absent counts.
     *
     * @param  Builder<StudentAttendance>  $query
     * @return array<string, mixed>
     */
    protected function attendanceTrendFrom(Builder $query, int $days = 14): array
    {
        [$buckets, $start] = $this->lastDays($days);

        $rows = $query
            ->where('attendance_date', '>=', $start->toDateString())
            ->toBase()
            ->selectRaw("DATE_FORMAT(attendance_date, '%Y-%m-%d') as day, status, COUNT(*) as total")
            ->groupBy('day', 'status')
            ->get();

        $byStatus = [];
        foreach ($rows as $row) {
            $byStatus[$row->status][$row->day] = (int) $row->total;
        }

        $present = array_sum($byStatus[StudentAttendance::STATUS_PRESENT] ?? []);
        $late = array_sum($byStatus[StudentAttendance::STATUS_LATE] ?? []);
        $marked = $present + $late + array_sum($byStatus[StudentAttendance::STATUS_ABSENT] ?? []);

        return [
            'labels' => $buckets->values()->all(),
            'series' => [
                ['name' => 'Present', 'data' => $this->fillBuckets($buckets, $byStatus[StudentAttendance::STATUS_PRESENT] ?? [])],
                ['name' => 'Late', 'data' => $this->fillBuckets($buckets, $byStatus[StudentAttendance::STATUS_LATE] ?? [])],
                ['name' => 'Absent', 'data' => $this->fillBuckets($buckets, $byStatus[StudentAttendance::STATUS_ABSENT] ?? [])],
            ],
            'rate' => $marked > 0 ? round(($present + $late) * 100 / $marked, 1) : 0.0,
        ];
    }

    /**
     * Attendance rate per batch, best first.
     *
     * @param  Builder<StudentAttendance>  $query
     * @return array<string, mixed>
     */
    protected function batchRatesFrom(Builder $query, int $days = 30, int $limit = 10): array
    {
        $rows = $query
            ->where('attendance_date', '>=', CarbonImmutable::today()->subDays($days - 1)->toDateString())
            ->toBase()
            ->selectRaw('batch_id, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as attended, COUNT(*) as marked', [
                StudentAttendance::STATUS_PRESENT,
                StudentAttendance::STATUS_LATE,
            ])
            ->groupBy('batch_id')
            ->get();

        $names = Batch::query()->whereIn('id', $rows->pluck('batch_id'))->pluck('name', 'id');

        $rates = $rows
            ->filter(fn ($row) => (int) $row->marked > 0 && $names->has($row->batch_id))
            ->map(fn ($row) => [
                'name' => $names[$row->batch_id],
                'rate' => round($row->attended * 100 / $row->marked, 1),
            ])
            ->sortByDesc('rate')
            ->take($limit)
            ->values();

        $attended = (int) $rows->sum('attended');
        $marked = (int) $rows->sum('marked');

        return [
            'labels' => $rates->pluck('name')->all(),
            'series' => [['name' => 'Attendance', 'data' => $rates->pluck('rate')->all()]],
            'rate' => $marked > 0 ? round($attended * 100 / $marked, 1) : 0.0,
        ];
    }
}
