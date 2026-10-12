<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffAttendanceService
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function roster(int $instituteId, string $date, ?int $branchId = null): Collection
    {
        $users = $this->activeStaffAndTeachers($instituteId, $branchId);

        $existing = StaffAttendance::query()
            ->where('institute_id', $instituteId)
            ->whereDate('attendance_date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get()
            ->keyBy('user_id');

        return $users->map(function (array $row) use ($existing) {
            $attendance = $existing->get($row['user_id']);

            return array_merge($row, [
                'status' => $attendance?->status ?? StaffAttendance::STATUS_PRESENT,
                'is_saved' => $attendance !== null,
            ]);
        })->values();
    }

    /**
     * @return array{is_marked: bool, marked_count: int, total_staff: int}
     */
    public function attendanceMeta(int $instituteId, string $date, ?int $branchId = null): array
    {
        $query = StaffAttendance::query()
            ->where('institute_id', $instituteId)
            ->whereDate('attendance_date', $date);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $markedCount = (clone $query)->count();
        $totalStaff = $this->activeStaffAndTeachers($instituteId, $branchId)->count();

        return [
            'is_marked' => $markedCount > 0,
            'marked_count' => $markedCount,
            'total_staff' => $totalStaff,
        ];
    }

    /**
     * @param  array<int, array{user_id: int, status: string, remark?: string|null}>  $records
     */
    public function mark(int $instituteId, string $date, array $records, ?int $branchId = null): int
    {
        return DB::transaction(function () use ($instituteId, $date, $records, $branchId) {
            $saved = 0;
            $userMap = $this->activeStaffAndTeachers($instituteId, $branchId)->keyBy('user_id');

            foreach ($records as $record) {
                $userId = (int) $record['user_id'];
                $status = (string) $record['status'];

                if (! $userMap->has($userId) || ! array_key_exists($status, StaffAttendance::STATUSES)) {
                    continue;
                }

                $row = $userMap->get($userId);
                $attendance = StaffAttendance::query()->firstOrNew([
                    'user_id' => $userId,
                    'attendance_date' => $date,
                ]);

                $attendance->fill([
                    'institute_id' => $instituteId,
                    'branch_id' => $branchId ?? $row['branch_id'],
                    'status' => $status,
                    'remark' => $record['remark'] ?? null,
                    'source' => 'manual',
                    'marked_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                if (! $attendance->exists) {
                    $attendance->created_by = auth()->id();
                }

                $attendance->save();
                $saved++;
            }

            return $saved;
        });
    }

    /**
     * Monthly attendance calendar for a staff/teacher user.
     *
     * @return array{year: int, month: int, days_in_month: int, days: array<int, string>, holidays: array<int, array{title: string, reason: string|null}>, summary: array<string, int>}
     */
    public function userMonthCalendar(int $userId, int $instituteId, ?int $branchId, int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $daysInMonth = (int) $start->daysInMonth;

        $holidayDays = $this->holidayService->monthHolidayDays(
            $instituteId,
            $branchId,
            null,
            $year,
            $month
        );

        $records = StaffAttendance::query()
            ->where('user_id', $userId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $byDay = $records->keyBy(fn (StaffAttendance $a) => (int) $a->attendance_date->format('j'));

        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if (isset($holidayDays[$d])) {
                $days[$d] = 'is-holiday';

                continue;
            }

            $rec = $byDay->get($d);
            $days[$d] = $rec ? $this->statusToCalendarClass($rec->status) : '';
        }

        return [
            'year' => $year,
            'month' => $month,
            'days_in_month' => $daysInMonth,
            'days' => $days,
            'holidays' => $holidayDays,
            'summary' => [
                'present' => $records->where('status', StaffAttendance::STATUS_PRESENT)->count(),
                'absent' => $records->where('status', StaffAttendance::STATUS_ABSENT)->count(),
                'late' => $records->where('status', StaffAttendance::STATUS_LATE)->count(),
                'total_marked' => $records->count(),
            ],
        ];
    }

    protected function statusToCalendarClass(string $status): string
    {
        return match ($status) {
            StaffAttendance::STATUS_PRESENT => 'is-present',
            StaffAttendance::STATUS_ABSENT => 'is-absent',
            StaffAttendance::STATUS_LATE => 'is-late',
            default => '',
        };
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function activeStaffAndTeachers(int $instituteId, ?int $branchId = null): Collection
    {
        $rows = collect();

        $teachers = Teacher::query()
            ->with(['user:id,name,phone,email', 'branches:id,name'])
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->get();

        foreach ($teachers as $teacher) {
            if (! $teacher->user) {
                continue;
            }

            $primaryBranch = $teacher->primaryBranch();
            if ($branchId && (! $primaryBranch || (int) $primaryBranch->id !== $branchId)) {
                if (! $teacher->branches->contains('id', $branchId)) {
                    continue;
                }
            }

            $batch = $teacher->assignedBatches()->orderByPivot('is_primary', 'desc')->first();

            $rows->push([
                'user_id' => $teacher->user_id,
                'profile_type' => 'teacher',
                'profile_id' => $teacher->id,
                'name' => $teacher->user->name,
                'phone' => $teacher->user->phone,
                'role' => 'Teacher',
                'branch_id' => $primaryBranch?->id,
                'branch_name' => $primaryBranch?->name ?? '—',
                'batch_name' => $batch?->name ?? '—',
                'code' => $teacher->employee_code ?: ('T'.$teacher->id),
            ]);
        }

        $staffMembers = Staff::query()
            ->with(['user:id,name,phone,email', 'branches:id,name'])
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->get();

        foreach ($staffMembers as $staff) {
            if (! $staff->user) {
                continue;
            }

            $primaryBranch = $staff->primaryBranch();
            if ($branchId && (! $primaryBranch || (int) $primaryBranch->id !== $branchId)) {
                if (! $staff->branches->contains('id', $branchId)) {
                    continue;
                }
            }

            $rows->push([
                'user_id' => $staff->user_id,
                'profile_type' => 'staff',
                'profile_id' => $staff->id,
                'name' => $staff->user->name,
                'phone' => $staff->user->phone,
                'role' => $staff->designation ?: 'Staff',
                'branch_id' => $primaryBranch?->id,
                'branch_name' => $primaryBranch?->name ?? '—',
                'batch_name' => '—',
                'code' => $staff->employee_code ?: ('S'.$staff->id),
            ]);
        }

        return $rows->sortBy('name')->values();
    }
}
