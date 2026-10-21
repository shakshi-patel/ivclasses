<?php

namespace App\Services\Dashboard;

use App\Models\Batch;
use App\Models\Exam;
use App\Models\Holiday;
use App\Models\Homework;
use App\Models\LeaveRequest;
use App\Models\Notice;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentBatch;
use App\Models\Todo;
use App\Models\User;
use App\Services\Dashboard\Concerns\BuildsTimeSeries;
use App\Services\Dashboard\Concerns\GatesSections;
use App\Services\Dashboard\Concerns\SummarisesAttendance;
use App\Services\ExamService;
use App\Services\HomeworkService;
use App\Services\NoticeService;
use App\Services\TodoService;
use App\Support\OwnBatchScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * "My classes" dashboard for teachers: everything is limited to the batches the teacher is assigned to
 * (as batch teacher or subject teacher) and gated by the teacher's permissions.
 */
class TeacherDashboardService
{
    use BuildsTimeSeries;
    use GatesSections;
    use SummarisesAttendance;

    public const CHART_WIDGETS = ['attendance_trend', 'batch_attendance'];

    public const PERMISSION_MODULE = 'teacher_dashboard';

    /**
     * Section => ability keys it needs (any one); keys mirror teacher_dashboard actions in the permission matrix.
     *
     * @var array<string, list<string>>
     */
    public const SECTION_ABILITIES = [
        'quick_actions' => [],
        'pulse' => [],
        'my_status' => [],
        'kpi_students' => [],
        'kpi_attendance' => ['attendance'],
        'kpi_homework' => ['homework'],
        'kpi_exams' => ['exams'],
        'kpi_my_attendance' => [],
        'classes' => [],
        'attention' => [],
        'chart_attendance_trend' => ['attendance'],
        'chart_batch_attendance' => ['attendance'],
        'list_exams' => ['exams'],
        'list_homework' => ['homework'],
        'list_tasks' => ['todo'],
        'list_notices' => ['notices'],
        'list_holidays' => [],
    ];

    private const CACHE_TTL = 120;

    private readonly int $instituteId;

    /** @var array<string, bool> */
    private array $abilities;

    /** @var list<int>|null */
    private ?array $batchIds = null;

    private ?Collection $batches = null;

    private function __construct(private readonly User $user)
    {
        $this->instituteId = (int) $user->institute_id;
        $this->abilities = $this->resolveAbilities();
    }

    public static function for(User $user): self
    {
        return new self($user);
    }

    /**
     * @return array<string, bool>
     */
    public function abilities(): array
    {
        return $this->abilities;
    }

    public function can(string $ability): bool
    {
        return $this->abilities[$ability] ?? false;
    }

    /**
     * @return list<string>
     */
    public function allowedWidgets(): array
    {
        return array_values(array_filter(self::CHART_WIDGETS, fn (string $widget) => $this->shows('chart_'.$widget)));
    }

    public function allowsWidget(string $widget): bool
    {
        return in_array($widget, $this->allowedWidgets(), true);
    }

    /**
     * @return list<array{route: string, icon: string, label: string}>
     */
    public function quickActions(): array
    {
        if (! $this->shows('quick_actions')) {
            return [];
        }

        return array_values(array_filter([
            $this->can('attendance_add') ? ['route' => 'admin.attendance.student', 'icon' => 'bx-calendar-check', 'label' => 'Mark Attendance'] : null,
            $this->can('homework_add') ? ['route' => 'admin.homework.add', 'icon' => 'bx-book-add', 'label' => 'Give Homework'] : null,
            $this->can('exam_add') ? ['route' => 'admin.exam.add', 'icon' => 'bx-edit', 'label' => 'Schedule Exam'] : null,
            $this->can('study_material_add') ? ['route' => 'admin.study_material.add', 'icon' => 'bx-upload', 'label' => 'Upload Material'] : null,
            $this->can('staff_leave_add') ? ['route' => 'admin.staff_leave_request', 'icon' => 'bx-calendar-minus', 'label' => 'Apply Leave'] : null,
        ]));
    }

    /**
     * Active batches the teacher takes, ordered by start time, with today's attendance state.
     *
     * @return Collection<int, Batch>
     */
    public function batches(): Collection
    {
        if ($this->batches !== null) {
            return $this->batches;
        }

        $ids = $this->batchIds();
        if ($ids === []) {
            return $this->batches = collect();
        }

        $batches = Batch::query()
            ->whereIn('id', $ids)
            ->with([
                'standard:id,name',
                'branch:id,name',
                'subjects' => fn ($q) => $q->select('subjects.id', 'subjects.name')
                    ->where('batch_subject.teacher_user_id', $this->user->id),
            ])
            ->withCount(['enrollments as students_count' => fn (Builder $q) => $q->where('status', 'active')
                ->select(DB::raw('COUNT(DISTINCT student_id)'))])
            ->orderBy('start_time')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id', 'standard_id', 'status', 'start_time', 'end_time']);

        $primary = DB::table('batch_teacher')
            ->where('teacher_user_id', $this->user->id)
            ->whereIn('batch_id', $ids)
            ->pluck('is_primary', 'batch_id');

        $marks = StudentAttendance::query()
            ->whereIn('batch_id', $ids)
            ->whereDate('attendance_date', CarbonImmutable::today()->toDateString())
            ->toBase()
            ->selectRaw('batch_id, status, COUNT(*) as total')
            ->groupBy('batch_id', 'status')
            ->get()
            ->groupBy('batch_id');

        $now = now()->format('H:i:s');

        return $this->batches = $batches->each(function (Batch $batch) use ($primary, $marks, $now) {
            $counts = collect($marks->get($batch->id, []))->pluck('total', 'status');

            $batch->setAttribute('is_primary', (bool) ($primary[$batch->id] ?? false));
            $batch->setAttribute('is_marked', $counts->isNotEmpty());
            $batch->setAttribute('present_count', (int) ($counts[StudentAttendance::STATUS_PRESENT] ?? 0) + (int) ($counts[StudentAttendance::STATUS_LATE] ?? 0));
            $batch->setAttribute('absent_count', (int) ($counts[StudentAttendance::STATUS_ABSENT] ?? 0));
            $batch->setAttribute('is_live', $batch->start_time && $batch->end_time
                && $batch->start_time <= $now && $batch->end_time >= $now);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return $this->remember('summary', function () {
            $today = CarbonImmutable::today();
            $monthStart = $today->startOfMonth();
            $batches = $this->batches();

            $out = [
                'classes' => [
                    'batches' => $batches->count(),
                    'students' => $this->studentIdsQuery()->distinct()->count('student_id'),
                    'subjects' => $batches->flatMap(fn (Batch $b) => $b->subjects->pluck('name'))->unique()->count(),
                ],
                'me' => $this->myAttendance($monthStart, $today),
            ];

            if ($this->shows('kpi_attendance')) {
                $marked = $batches->where('is_marked', true);
                $present = (int) $marked->sum('present_count');
                $absent = (int) $marked->sum('absent_count');

                $out['attendance'] = [
                    'batches_total' => $batches->count(),
                    'batches_marked' => $marked->count(),
                    'present' => $present,
                    'absent' => $absent,
                    'rate' => ($present + $absent) > 0 ? round($present * 100 / ($present + $absent), 1) : null,
                    'is_holiday' => $this->isHoliday($today),
                ];
            }

            if ($this->shows('kpi_homework')) {
                $homework = fn () => app(HomeworkService::class)->queryForUser($this->user);

                $out['homework'] = [
                    'this_month' => $homework()->whereBetween('homework_date', [$monthStart->toDateString(), $today->toDateString()])->count(),
                    'due_today' => $homework()->whereDate('due_date', $today->toDateString())->count(),
                    'open' => $homework()->whereDate('due_date', '>=', $today->toDateString())->count(),
                ];
            }

            if ($this->shows('kpi_exams')) {
                $next = $this->examQuery()
                    ->whereDate('exam_date', '>=', $today->toDateString())
                    ->orderBy('exam_date')
                    ->first(['id', 'title', 'exam_date']);

                $out['exams'] = [
                    'upcoming' => $this->examQuery()
                        ->whereBetween('exam_date', [$today->toDateString(), $today->addDays(14)->toDateString()])
                        ->count(),
                    'next_title' => $next?->title,
                    'next_date' => $next?->exam_date?->toDateString(),
                    'results_pending' => $this->resultsPendingQuery($today)->count(),
                ];
            }

            return $out;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function attention(): array
    {
        if (! $this->shows('attention')) {
            return [];
        }

        return $this->remember('attention', function () {
            $today = CarbonImmutable::today();
            $items = [];

            if ($this->can('attendance_add') && ! $this->isHoliday($today)) {
                $pending = $this->batches()->where('is_marked', false)->count();
                $items[] = $this->item('primary', 'bx-calendar-x', 'Attendance not marked', 'Your batches pending today',
                    $pending, 'admin.attendance.student');
            }

            if ($this->can('exams')) {
                $items[] = $this->item('warning', 'bx-spreadsheet', 'Results to enter', 'Past exams without marks',
                    $this->resultsPendingQuery($today)->count(), 'admin.exam');
                $items[] = $this->item('info', 'bx-edit', 'Exams today', 'Scheduled in your batches',
                    $this->examQuery()->whereDate('exam_date', $today->toDateString())->count(), 'admin.exam');
            }

            if ($this->can('homework')) {
                $items[] = $this->item('secondary', 'bx-book-open', 'Homework due today', 'Check submissions in class',
                    app(HomeworkService::class)->queryForUser($this->user)->whereDate('due_date', $today->toDateString())->count(),
                    'admin.homework');
            }

            if ($this->can('student_leave')) {
                $items[] = $this->item('secondary', 'bx-envelope', 'Student leave requests', 'From students in your batches',
                    LeaveRequest::query()
                        ->where('institute_id', $this->instituteId)
                        ->where('requester_type', LeaveRequest::TYPE_STUDENT)
                        ->where('status', LeaveRequest::STATUS_PENDING)
                        ->whereIn('student_id', $this->studentIdsQuery()->select('student_id'))
                        ->count(),
                    'admin.student_leave_request');
            }

            if ($this->can('todo')) {
                $items[] = $this->item('warning', 'bx-task', 'Tasks due', 'Pending tasks due today or overdue',
                    app(TodoService::class)->queryForUser($this->user)
                        ->where('status', Todo::STATUS_PENDING)
                        ->whereDate('due_date', '<=', $today->toDateString())
                        ->count(),
                    'admin.to_do_task');
            }

            return array_values(array_filter($items, fn (array $item) => $item['count'] > 0));
        });
    }

    /**
     * @return Collection<int, Exam>
     */
    public function upcomingExams(int $days = 14, int $limit = 5): Collection
    {
        if (! $this->shows('list_exams')) {
            return collect();
        }

        $today = CarbonImmutable::today();

        return $this->examQuery()
            ->whereBetween('exam_date', [$today->toDateString(), $today->addDays($days)->toDateString()])
            ->with(['batch:id,name', 'subject:id,name'])
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get(['id', 'batch_id', 'subject_id', 'title', 'exam_date', 'start_time', 'total_marks']);
    }

    /**
     * @return Collection<int, Homework>
     */
    public function recentHomework(int $limit = 5): Collection
    {
        if (! $this->shows('list_homework')) {
            return collect();
        }

        return app(HomeworkService::class)->queryForUser($this->user)
            ->with(['batch:id,name', 'subject:id,name'])
            ->orderByDesc('homework_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'batch_id', 'subject_id', 'title', 'homework_date', 'due_date']);
    }

    /**
     * Notices meant for teachers (or everyone) in the teacher's branches / batches.
     *
     * @return Collection<int, Notice>
     */
    public function notices(int $limit = 4): Collection
    {
        if (! $this->shows('list_notices')) {
            return collect();
        }

        $batchIds = $this->batchIds();

        return app(NoticeService::class)->queryForUser($this->user)
            ->whereIn('audience', [Notice::AUDIENCE_ALL, Notice::AUDIENCE_TEACHERS])
            ->where(fn (Builder $q) => $q->whereDoesntHave('batches')
                ->orWhereHas('batches', fn (Builder $b) => $b->whereIn('batches.id', $batchIds ?: [0])))
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'title', 'notice_date', 'audience']);
    }

    /**
     * @return Collection<int, Holiday>
     */
    public function holidays(int $limit = 4): Collection
    {
        if (! $this->shows('list_holidays') || ! $this->can('holidays')) {
            return collect();
        }

        return $this->holidayQuery()
            ->whereDate('end_date', '>=', CarbonImmutable::today()->toDateString())
            ->orderBy('start_date')
            ->limit($limit)
            ->get(['id', 'title', 'start_date', 'end_date']);
    }

    /**
     * @return Collection<int, Todo>
     */
    public function tasks(int $limit = 5): Collection
    {
        if (! $this->shows('list_tasks')) {
            return collect();
        }

        return app(TodoService::class)->queryForUser($this->user)
            ->where('status', Todo::STATUS_PENDING)
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit($limit)
            ->get(['id', 'title', 'task_items', 'due_date', 'status']);
    }

    /**
     * @return Collection<int, Student>
     */
    public function birthdaysToday(int $limit = 6): Collection
    {
        if (! $this->shows('list_holidays')) {
            return collect();
        }

        $today = CarbonImmutable::today();

        return Student::query()
            ->where('institute_id', $this->instituteId)
            ->where('status', 'active')
            ->whereIn('id', $this->studentIdsQuery()->select('student_id'))
            ->whereMonth('dob', $today->month)
            ->whereDay('dob', $today->day)
            ->with('standard:id,name')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'photo_path', 'standard_id', 'dob']);
    }

    /**
     * @return array<string, mixed>
     */
    public function chart(string $widget): array
    {
        return $this->remember('chart:'.$widget, fn () => match ($widget) {
            'attendance_trend' => $this->attendanceTrendFrom($this->attendanceQuery()),
            'batch_attendance' => $this->batchRatesFrom($this->attendanceQuery()),
        });
    }

    /**
     * @return array{present: int, late: int, absent: int, working_days: int, today: ?string, check_in_at: ?string, check_out_at: ?string}
     */
    private function myAttendance(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $records = StaffAttendance::query()
            ->where('user_id', $this->user->id)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->get(['attendance_date', 'status', 'check_in_at', 'check_out_at']);

        $today = $records->first(fn (StaffAttendance $a) => $a->attendance_date->isSameDay($to));

        return [
            'present' => $records->where('status', StaffAttendance::STATUS_PRESENT)->count(),
            'late' => $records->where('status', StaffAttendance::STATUS_LATE)->count(),
            'absent' => $records->where('status', StaffAttendance::STATUS_ABSENT)->count(),
            'working_days' => $records->count(),
            'today' => $today?->status,
            'check_in_at' => $today?->check_in_at ? substr((string) $today->check_in_at, 0, 5) : null,
            'check_out_at' => $today?->check_out_at ? substr((string) $today->check_out_at, 0, 5) : null,
        ];
    }

    /**
     * Batch ids the teacher takes (class or subject), not BranchScope.
     *
     * @return list<int>
     */
    private function batchIds(): array
    {
        if ($this->batchIds !== null) {
            return $this->batchIds;
        }

        return $this->batchIds = OwnBatchScope::batchIds($this->user, activeOnly: true);
    }

    private function studentIdsQuery(): Builder
    {
        return StudentBatch::query()
            ->whereIn('batch_id', $this->batchIds() ?: [0])
            ->where('status', 'active');
    }

    private function attendanceQuery(): Builder
    {
        return StudentAttendance::query()
            ->where('institute_id', $this->instituteId)
            ->whereIn('batch_id', $this->batchIds() ?: [0]);
    }

    private function examQuery(): Builder
    {
        return app(ExamService::class)->queryForUser($this->user);
    }

    private function resultsPendingQuery(CarbonImmutable $today): Builder
    {
        return $this->examQuery()
            ->whereDate('exam_date', '<', $today->toDateString())
            ->whereDate('exam_date', '>=', $today->subDays(60)->toDateString())
            ->whereDoesntHave('results');
    }

    private function holidayQuery(): Builder
    {
        $query = Holiday::query()->where('institute_id', $this->instituteId);
        $batchIds = $this->batchIds();
        $branchIds = OwnBatchScope::branchIds($this->user);

        return $query->where(function (Builder $q) use ($batchIds, $branchIds) {
            $q->where(fn (Builder $inner) => $inner->whereDoesntHave('branches')->whereDoesntHave('batches'));
            if ($branchIds !== []) {
                $q->orWhereHas('branches', fn (Builder $b) => $b->whereIn('branches.id', $branchIds));
            }
            if ($batchIds !== []) {
                $q->orWhereHas('batches', fn (Builder $b) => $b->whereIn('batches.id', $batchIds));
            }
        });
    }

    private function isHoliday(CarbonImmutable $day): bool
    {
        return $this->holidayQuery()
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function item(string $tone, string $icon, string $title, string $text, int $count, string $route): array
    {
        return [
            'tone' => $tone,
            'icon' => $icon,
            'title' => $title,
            'text' => $text,
            'count' => $count,
            'route' => $route,
            'params' => [],
            'showCount' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function resolveAbilities(): array
    {
        $can = fn (string ...$abilities) => $this->user->canAny($abilities);

        return [
            'batches' => $can('batch.view'),
            'batch_show' => $can('batch.show'),
            'attendance' => $can('attendance.view', 'attendance.add'),
            'attendance_add' => $can('attendance.add'),
            'homework' => $can('homework.view'),
            'homework_add' => $can('homework.add'),
            'exams' => $can('exam.view'),
            'exam_add' => $can('exam.add'),
            'study_material_add' => $can('study_material.add'),
            'notices' => $can('notice.view'),
            'holidays' => $can('holiday.view'),
            'student_leave' => $can('student_leave.view'),
            'staff_leave_add' => $can('staff_leave.add'),
            'todo' => $can('todo.view', 'todo.add'),
        ];
    }

    private function remember(string $key, \Closure $callback): mixed
    {
        return Cache::remember("dashboard:teacher:{$this->user->id}:{$this->accessFingerprint()}:{$key}", self::CACHE_TTL, $callback);
    }
}
