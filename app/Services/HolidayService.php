<?php

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HolidayService
{    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Holiday
    {
        return DB::transaction(function () use ($data) {
            $holiday = Holiday::query()->create([
                'institute_id' => (int) $data['institute_id'],
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->syncRelations($holiday, $data);

            return $holiday->load(['branches:id,name', 'batches:id,name', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Holiday $holiday, array $data): Holiday
    {
        return DB::transaction(function () use ($holiday, $data) {
            $holiday->update([
                'title' => $data['title'] ?? $holiday->title,
                'start_date' => $data['start_date'] ?? $holiday->start_date,
                'end_date' => $data['end_date'] ?? $holiday->end_date,
                'reason' => $data['reason'] ?? $holiday->reason,
                'updated_by' => auth()->id(),
            ]);

            $this->syncRelations($holiday, $data);

            return $holiday->fresh(['branches:id,name', 'batches:id,name', 'creator']);
        });
    }

    public function delete(Holiday $holiday): void
    {
        $holiday->delete();
    }

    /**
     * @return array<int, array{title: string, reason: string|null}>
     */
    public function monthHolidayDays(
        int $instituteId,
        ?int $branchId,
        ?int $batchId,
        int $year,
        int $month
    ): array {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $holidays = Holiday::query()
            ->where('institute_id', $instituteId)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->where(function ($query) use ($branchId) {
                $query->whereDoesntHave('branches');
                if ($branchId) {
                    $query->orWhereHas('branches', fn ($q) => $q->where('branches.id', $branchId));
                }
            })
            ->where(function ($query) use ($batchId) {
                $query->whereDoesntHave('batches');
                if ($batchId) {
                    $query->orWhereHas('batches', fn ($q) => $q->where('batches.id', $batchId));
                }
            })
            ->orderBy('start_date')
            ->get(['title', 'reason', 'start_date', 'end_date']);

        $map = [];

        foreach ($holidays as $holiday) {
            $cursor = $holiday->start_date->copy()->max($start);
            $holidayEnd = $holiday->end_date->copy()->min($end);

            while ($cursor->lte($holidayEnd)) {
                if ((int) $cursor->month === $month && (int) $cursor->year === $year) {
                    $day = (int) $cursor->day;

                    if (isset($map[$day])) {
                        $map[$day]['title'] .= ', '.$holiday->title;
                    } else {
                        $map[$day] = [
                            'title' => $holiday->title,
                            'reason' => $holiday->reason,
                        ];
                    }
                }

                $cursor->addDay();
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncRelations(Holiday $holiday, array $data): void
    {
        $branchIds = collect($data['branch_ids'] ?? [])->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $batchIds = collect($data['batch_ids'] ?? [])->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        $holiday->branches()->sync($branchIds);
        $holiday->batches()->sync($batchIds);
    }
}
