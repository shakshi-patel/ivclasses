<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\LeaveRequest;
use App\Models\StudentAttendance;
use App\Models\StudentBatch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentAttendanceService
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function batchRoster(Batch $batch, string $date): Collection
    {
        $enrollments = StudentBatch::query()
            ->with('student:id,name,admission_no,status')
            ->where('batch_id', $batch->id)
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->orderBy('id')
            ->get();

        $existing = StudentAttendance::query()
            ->where('batch_id', $batch->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        return $enrollments->map(function (StudentBatch $enrollment) use ($existing) {
            $student = $enrollment->student;
            $attendance = $existing->get($enrollment->student_id);

            return [
                'student_id' => $enrollment->student_id,
                'roll_no' => $enrollment->roll_no ?: '—',
                'name' => $student?->name ?? '—',
                'admission_no' => $student?->admission_no,
                'status' => $attendance?->status ?? StudentAttendance::STATUS_PRESENT,
                'remark' => $attendance?->remark,
                'is_saved' => $attendance !== null,
            ];
        })->values();
    }

    /**
     * @return array{is_marked: bool, marked_count: int, total_students: int, last_updated_at: string|null}
     */
    public function batchAttendanceMeta(Batch $batch, string $date): array
    {
        $query = StudentAttendance::query()
            ->where('batch_id', $batch->id)
            ->whereDate('attendance_date', $date);

        $markedCount = (clone $query)->count();
        $lastUpdated = (clone $query)->max('updated_at');

        return [
            'is_marked' => $markedCount > 0,
            'marked_count' => $markedCount,
            'total_students' => $this->activeStudentCount($batch->id),
            'last_updated_at' => $lastUpdated ? (string) $lastUpdated : null,
        ];
    }

    /**
     * @param  array<int, array{student_id: int, status: string, remark?: string|null}>  $records
     */
    public function markBatch(Batch $batch, string $date, array $records): int
    {
        return DB::transaction(function () use ($batch, $date, $records) {
            $saved = 0;

            foreach ($records as $record) {
                $studentId = (int) $record['student_id'];
                $status = (string) $record['status'];

                if (! array_key_exists($status, StudentAttendance::STATUSES)) {
                    continue;
                }

                $attendance = StudentAttendance::query()->firstOrNew([
                    'student_id' => $studentId,
                    'batch_id' => $batch->id,
                    'attendance_date' => $date,
                ]);

                $attendance->fill([
                    'institute_id' => $batch->institute_id,
                    'branch_id' => $batch->branch_id,
                    'status' => $status,
                    'remark' => $record['remark'] ?? null,
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

    public function activeStudentCount(int $batchId): int
    {
        return StudentBatch::query()
            ->where('batch_id', $batchId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * @return array{year: int, month: int, days_in_month: int, sundays: array<int, int>, days: array<int, string>, holidays: array<int, array{title: string, reason: string|null}>, summary: array<string, int>}
     */
    public function studentMonthCalendar(int $studentId, int $batchId, int $year, int $month): array
    {
        $batch = Batch::query()->findOrFail($batchId);
        [$start, $end, $daysInMonth, $sundays] = $this->monthBounds($year, $month);

        $holidayDays = $this->holidayService->monthHolidayDays(
            (int) $batch->institute_id,
            $batch->branch_id ? (int) $batch->branch_id : null,
            $batchId,
            $year,
            $month
        );

        $records = StudentAttendance::query()
            ->where('student_id', $studentId)
            ->where('batch_id', $batchId)
            ->whereBetween('attendance_date', [$start, $end])
            ->get();

        $byDay = $records->keyBy(fn (StudentAttendance $a) => (int) $a->attendance_date->format('j'));
        $leaveDays = $this->monthLeaveDays($studentId, $start, $end);

        $days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if (isset($holidayDays[$d])) {
                $days[$d] = 'is-holiday';

                continue;
            }

            if (isset($leaveDays[$d])) {
                $days[$d] = 'is-leave';

                continue;
            }

            $rec = $byDay->get($d);
            $days[$d] = $rec ? $this->statusToCalendarClass($rec->status) : '';
        }

        return [
            'year' => $year,
            'month' => $month,
            'days_in_month' => $daysInMonth,
            'sundays' => $sundays,
            'days' => $days,
            'holidays' => $holidayDays,
            'summary' => [
                'present' => $records->where('status', StudentAttendance::STATUS_PRESENT)->count(),
                'absent' => $records->where('status', StudentAttendance::STATUS_ABSENT)->count(),
                'late' => $records->where('status', StudentAttendance::STATUS_LATE)->count(),
                'leave' => count($leaveDays),
                'total_marked' => $records->count(),
            ],
        ];
    }

    /**
     * @return array{year: int, month: int, days_in_month: int, sundays: array<int, int>, holidays: array<int, array{title: string, reason: string|null}>, students: \Illuminate\Support\Collection<int, array<string, mixed>>}
     */
    public function batchMonthReport(Batch $batch, int $year, int $month): array
    {
        [$start, $end, $daysInMonth, $sundays] = $this->monthBounds($year, $month);

        $holidayDays = $this->holidayService->monthHolidayDays(
            (int) $batch->institute_id,
            $batch->branch_id ? (int) $batch->branch_id : null,
            (int) $batch->id,
            $year,
            $month
        );

        $enrollments = StudentBatch::query()
            ->with('student:id,name')
            ->where('batch_id', $batch->id)
            ->where('status', 'active')
            ->orderBy('roll_no')
            ->orderBy('id')
            ->get();

        $attendances = StudentAttendance::query()
            ->where('batch_id', $batch->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->get()
            ->groupBy('student_id');

        $leaveDaysByStudent = $this->monthLeaveDaysForStudents(
            $enrollments->pluck('student_id')->all(),
            $start,
            $end
        );

        $students = $enrollments->map(function (StudentBatch $enrollment) use ($attendances, $daysInMonth, $holidayDays, $leaveDaysByStudent) {
            $studentAtt = $attendances->get($enrollment->student_id, collect());
            $byDay = $studentAtt->keyBy(fn (StudentAttendance $a) => (int) $a->attendance_date->format('j'));
            $leaveDays = $leaveDaysByStudent->get($enrollment->student_id, []);

            $days = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                if (isset($holidayDays[$d])) {
                    $days[$d] = ['class' => 'is-h', 'label' => 'H'];

                    continue;
                }

                if (isset($leaveDays[$d])) {
                    $days[$d] = ['class' => 'is-lv', 'label' => 'LV'];

                    continue;
                }

                $rec = $byDay->get($d);
                $days[$d] = $rec ? $this->statusToReportBadge($rec->status) : null;
            }

            return [
                'student_id' => $enrollment->student_id,
                'name' => $enrollment->student?->name ?? '—',
                'roll_no' => $enrollment->roll_no ?: '—',
                'days' => $days,
            ];
        })->values();

        return [
            'year' => $year,
            'month' => $month,
            'days_in_month' => $daysInMonth,
            'sundays' => $sundays,
            'holidays' => $holidayDays,
            'students' => $students,
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: int, 3: array<int, int>}
     */
    protected function monthBounds(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));
        $daysInMonth = (int) date('t', strtotime($start));
        $sundays = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            if ((int) date('w', strtotime($date)) === 0) {
                $sundays[] = $d;
            }
        }

        return [$start, $end, $daysInMonth, $sundays];
    }

    /**
     * @return array<int, true> day-of-month => true, for approved leaves covering this student in [start, end]
     */
    protected function monthLeaveDays(int $studentId, string $start, string $end): array
    {
        $leaves = LeaveRequest::query()
            ->where('requester_type', LeaveRequest::TYPE_STUDENT)
            ->where('student_id', $studentId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('from_date', '<=', $end)
            ->where('to_date', '>=', $start)
            ->get(['from_date', 'to_date']);

        return $this->expandLeaveDays($leaves, $start, $end);
    }

    /**
     * @param  array<int, int>  $studentIds
     * @return Collection<int, array<int, true>> student_id => [day => true]
     */
    protected function monthLeaveDaysForStudents(array $studentIds, string $start, string $end): Collection
    {
        if ($studentIds === []) {
            return collect();
        }

        return LeaveRequest::query()
            ->where('requester_type', LeaveRequest::TYPE_STUDENT)
            ->whereIn('student_id', $studentIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('from_date', '<=', $end)
            ->where('to_date', '>=', $start)
            ->get(['student_id', 'from_date', 'to_date'])
            ->groupBy('student_id')
            ->map(fn (Collection $leaves) => $this->expandLeaveDays($leaves, $start, $end));
    }

    /**
     * @param  Collection<int, LeaveRequest>  $leaves
     * @return array<int, true>
     */
    protected function expandLeaveDays(Collection $leaves, string $start, string $end): array
    {
        $monthStart = Carbon::parse($start);
        $monthEnd = Carbon::parse($end);
        $days = [];

        foreach ($leaves as $leave) {
            $from = $leave->from_date->greaterThan($monthStart) ? $leave->from_date : $monthStart;
            $to = $leave->to_date->lessThan($monthEnd) ? $leave->to_date : $monthEnd;

            for ($cursor = $from->copy(); $cursor->lessThanOrEqualTo($to); $cursor->addDay()) {
                $days[(int) $cursor->format('j')] = true;
            }
        }

        return $days;
    }

    protected function statusToCalendarClass(string $status): string
    {
        return match ($status) {
            StudentAttendance::STATUS_PRESENT => 'is-present',
            StudentAttendance::STATUS_ABSENT => 'is-absent',
            StudentAttendance::STATUS_LATE => 'is-late',
            default => '',
        };
    }

    /**
     * @return array{class: string, label: string}|null
     */
    protected function statusToReportBadge(string $status): ?array
    {
        return match ($status) {
            StudentAttendance::STATUS_PRESENT => ['class' => 'is-p', 'label' => 'P'],
            StudentAttendance::STATUS_ABSENT => ['class' => 'is-a', 'label' => 'A'],
            StudentAttendance::STATUS_LATE => ['class' => 'is-lp', 'label' => 'LP'],
            default => null,
        };
    }
}
