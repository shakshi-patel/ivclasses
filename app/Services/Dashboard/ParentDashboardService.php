<?php

namespace App\Services\Dashboard;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Holiday;
use App\Models\Homework;
use App\Models\LeaveRequest;
use App\Models\Notice;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentBatch;
use App\Models\User;
use App\Services\Dashboard\Concerns\BuildsTimeSeries;
use App\Services\Dashboard\Concerns\GatesSections;
use App\Services\ExamService;
use App\Services\HomeworkService;
use App\Services\NoticeService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard for parents: one child at a time (switchable), limited to the parent's own children.
 * Every section follows its parent_dashboard permission; homework, exams, results etc. also need their module permission.
 */
class ParentDashboardService
{
    use BuildsTimeSeries;
    use GatesSections;

    public const CHART_WIDGETS = ['child_attendance', 'child_results'];

    public const PERMISSION_MODULE = 'parent_dashboard';

    /**
     * Section => ability keys it needs (any one); keys mirror parent_dashboard actions in the permission matrix.
     *
     * @var array<string, list<string>>
     */
    public const SECTION_ABILITIES = [
        'quick_actions' => [],
        'kpi_attendance' => [],
        'kpi_fees' => [],
        'kpi_exams' => ['exams'],
        'kpi_homework' => ['homework'],
        'kpi_results' => ['results'],
        'chart_attendance' => [],
        'attention' => [],
        'classes' => [],
        'chart_results' => ['results'],
        'list_homework' => ['homework'],
        'list_exams' => ['exams'],
        'list_results' => ['results'],
        'list_fees' => [],
        'list_notices' => ['notices'],
        'list_holidays' => ['holidays', 'leave'],
    ];

    /** @var array<string, string> chart widget => section */
    private const WIDGET_SECTIONS = [
        'child_attendance' => 'chart_attendance',
        'child_results' => 'chart_results',
    ];

    private const CACHE_TTL = 120;

    /** @var Collection<int, Student> */
    private Collection $children;

    private ?Student $child;

    /** @var array<string, bool> */
    private array $abilities;

    /** @var list<int>|null */
    private ?array $batchIds = null;

    private function __construct(private readonly User $user, ?int $studentId)
    {
        $this->children = Student::query()
            ->where('status', 'active')
            ->whereHas('parent', fn (Builder $q) => $q->where('user_id', $user->id))
            ->with(['standard:id,name', 'branch:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'photo_path', 'standard_id', 'branch_id', 'admission_no', 'dob']);

        $this->child = ($studentId ? $this->children->firstWhere('id', $studentId) : null) ?? $this->children->first();
        $this->abilities = $this->resolveAbilities();
    }

    public static function for(User $user, ?int $studentId = null): self
    {
        return new self($user, $studentId);
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
     * @return Collection<int, Student>
     */
    public function children(): Collection
    {
        return $this->children;
    }

    public function child(): ?Student
    {
        return $this->child;
    }

    /**
     * @return list<string>
     */
    public function allowedWidgets(): array
    {
        if (! $this->child) {
            return [];
        }

        return array_values(array_filter(self::CHART_WIDGETS, fn (string $widget) => $this->shows(self::WIDGET_SECTIONS[$widget])));
    }

    public function allowsWidget(string $widget): bool
    {
        return in_array($widget, $this->allowedWidgets(), true);
    }

    /**
     * @return list<array{route: string, params: array<string, mixed>, icon: string, label: string}>
     */
    public function quickActions(): array
    {
        if (! $this->shows('quick_actions')) {
            return [];
        }

        $params = $this->child ? ['student_id' => $this->child->id] : [];

        return array_values(array_filter([
            $this->can('leave_add') ? ['route' => 'admin.student_leave_request', 'params' => [], 'icon' => 'bx-calendar-minus', 'label' => 'Apply Leave'] : null,
            $this->can('homework') ? ['route' => 'admin.homework', 'params' => $params, 'icon' => 'bx-book-open', 'label' => 'Homework'] : null,
            $this->can('exams') ? ['route' => 'admin.exam', 'params' => $params, 'icon' => 'bx-edit', 'label' => 'Exams'] : null,
            $this->can('results') ? ['route' => 'admin.result_announcement', 'params' => $params, 'icon' => 'bx-trophy', 'label' => 'Results'] : null,
            $this->can('study_material') ? ['route' => 'admin.study_material', 'params' => $params, 'icon' => 'bx-library', 'label' => 'Study Material'] : null,
        ]));
    }

    /**
     * Switcher cards: headline numbers for every child.
     *
     * @return array<int, array{attendance: ?float, due: float}>
     */
    public function childStats(): array
    {
        return $this->remember('children', function () {
            $ids = $this->children->pluck('id');
            $monthStart = CarbonImmutable::today()->startOfMonth()->toDateString();

            $attendance = StudentAttendance::query()
                ->whereIn('student_id', $ids)
                ->where('attendance_date', '>=', $monthStart)
                ->toBase()
                ->selectRaw('student_id, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as attended, COUNT(*) as marked', [
                    StudentAttendance::STATUS_PRESENT,
                    StudentAttendance::STATUS_LATE,
                ])
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            $dues = FeeInvoice::query()
                ->whereIn('student_id', $ids)
                ->where('due_amount', '>', 0)
                ->toBase()
                ->selectRaw('student_id, SUM(due_amount) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id');

            return $this->children->mapWithKeys(function (Student $child) use ($attendance, $dues) {
                $row = $attendance->get($child->id);

                return [$child->id => [
                    'attendance' => $row && $row->marked > 0 ? round($row->attended * 100 / $row->marked, 1) : null,
                    'due' => round((float) ($dues[$child->id] ?? 0), 2),
                ]];
            })->all();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        if (! $this->child) {
            return [];
        }

        return $this->remember('summary:'.$this->child->id, function () {
            $today = CarbonImmutable::today();
            $childId = $this->child->id;

            $month = StudentAttendance::query()
                ->where('student_id', $childId)
                ->whereBetween('attendance_date', [$today->startOfMonth()->toDateString(), $today->toDateString()])
                ->get(['attendance_date', 'status']);

            $present = $month->where('status', StudentAttendance::STATUS_PRESENT)->count();
            $late = $month->where('status', StudentAttendance::STATUS_LATE)->count();
            $absent = $month->where('status', StudentAttendance::STATUS_ABSENT)->count();

            $dues = FeeInvoice::query()->where('student_id', $childId)->where('due_amount', '>', 0);
            $nextDue = (clone $dues)->whereNotNull('due_date')->orderBy('due_date')->value('due_date');

            $out = [
                'attendance' => [
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'marked' => $month->count(),
                    'rate' => $month->count() > 0 ? round(($present + $late) * 100 / $month->count(), 1) : null,
                    'today' => $month->first(fn (StudentAttendance $a) => $a->attendance_date->isSameDay($today))?->status,
                ],
                'fees' => [
                    'due' => round((float) (clone $dues)->sum('due_amount'), 2),
                    'paid' => round((float) FeePayment::query()->where('student_id', $childId)->sum('amount'), 2),
                    'next_due_date' => $nextDue ? CarbonImmutable::parse($nextDue)->toDateString() : null,
                    'overdue' => (clone $dues)->whereNotNull('due_date')->whereDate('due_date', '<', $today->toDateString())->count(),
                ],
            ];

            if ($this->shows('kpi_exams')) {
                $next = $this->examQuery()
                    ->whereDate('exam_date', '>=', $today->toDateString())
                    ->with('subject:id,name')
                    ->orderBy('exam_date')
                    ->orderBy('start_time')
                    ->first(['id', 'title', 'subject_id', 'exam_date']);

                $out['exams'] = [
                    'upcoming' => $this->examQuery()
                        ->whereBetween('exam_date', [$today->toDateString(), $today->addDays(30)->toDateString()])
                        ->count(),
                    'next_title' => $next?->title,
                    'next_date' => $next?->exam_date?->toDateString(),
                ];
            }

            if ($this->shows('kpi_homework')) {
                $out['homework'] = [
                    'due_this_week' => $this->homeworkQuery()
                        ->whereBetween('due_date', [$today->toDateString(), $today->addDays(6)->toDateString()])
                        ->count(),
                    'due_today' => $this->homeworkQuery()->whereDate('due_date', $today->toDateString())->count(),
                ];
            }

            if ($this->shows('kpi_results')) {
                $latest = $this->publishedResults(1)->first();

                $out['results'] = [
                    'latest_percent' => $latest?->percent,
                    'latest_title' => $latest?->exam?->title,
                    'average_percent' => $this->averagePercent(),
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
        if (! $this->child || ! $this->shows('attention')) {
            return [];
        }

        $summary = $this->summary();
        $today = CarbonImmutable::today();
        $params = ['student_id' => $this->child->id];
        $items = [];

        if ($summary['attendance']['today'] === StudentAttendance::STATUS_ABSENT) {
            $items[] = $this->item('error', 'bx-user-x', 'Absent today', 'Marked absent in class', 1, null, [], false);
        }

        if ($summary['fees']['overdue'] > 0) {
            $items[] = $this->item('error', 'bx-wallet', 'Fees overdue',
                $summary['fees']['overdue'].' '.\Illuminate\Support\Str::plural('instalment', $summary['fees']['overdue']).' past due date',
                $summary['fees']['overdue'], null);
        }

        if ($this->can('homework')) {
            $items[] = $this->item('warning', 'bx-book-open', 'Homework due today', 'Make sure it is complete',
                $summary['homework']['due_today'] ?? $this->homeworkQuery()->whereDate('due_date', $today->toDateString())->count(),
                'admin.homework', $params);
        }

        if ($this->can('exams')) {
            $soon = $this->examQuery()
                ->whereBetween('exam_date', [$today->toDateString(), $today->addDays(3)->toDateString()])
                ->count();
            $items[] = $this->item('info', 'bx-edit', 'Exams in the next 3 days', 'Time to revise', $soon, 'admin.exam', $params);
        }

        if ($this->can('leave')) {
            $pending = LeaveRequest::query()
                ->where('student_id', $this->child->id)
                ->where('requester_type', LeaveRequest::TYPE_STUDENT)
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->count();
            $items[] = $this->item('secondary', 'bx-envelope', 'Leave awaiting approval', 'Submitted by you',
                $pending, 'admin.student_leave_request');
        }

        return array_values(array_filter($items, fn (array $item) => $item['count'] > 0));
    }

    /**
     * Active enrolments with batch timing, subject and class teacher.
     *
     * @return Collection<int, StudentBatch>
     */
    public function enrollments(): Collection
    {
        if (! $this->child || ! $this->shows('classes')) {
            return collect();
        }

        return StudentBatch::query()
            ->where('student_id', $this->child->id)
            ->where('status', 'active')
            ->with([
                'batch:id,name,start_time,end_time,branch_id',
                'batch.teachers' => fn ($q) => $q->select('users.id', 'users.name')->orderByPivot('is_primary', 'desc'),
                'subject:id,name',
            ])
            ->get(['id', 'batch_id', 'subject_id', 'roll_no'])
            ->filter(fn (StudentBatch $e) => $e->batch !== null)
            ->values();
    }

    /**
     * @return Collection<int, Exam>
     */
    public function upcomingExams(int $limit = 5): Collection
    {
        if (! $this->child || ! $this->shows('list_exams')) {
            return collect();
        }

        return $this->examQuery()
            ->whereDate('exam_date', '>=', CarbonImmutable::today()->toDateString())
            ->with(['batch:id,name', 'subject:id,name'])
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get(['id', 'batch_id', 'subject_id', 'title', 'exam_date', 'start_time', 'total_marks']);
    }

    /**
     * Homework still open (due today or later), soonest first.
     *
     * @return Collection<int, Homework>
     */
    public function homework(int $limit = 5): Collection
    {
        if (! $this->child || ! $this->shows('list_homework')) {
            return collect();
        }

        return $this->homeworkQuery()
            ->where(fn (Builder $q) => $q->whereNull('due_date')
                ->orWhereDate('due_date', '>=', CarbonImmutable::today()->toDateString()))
            ->with(['subject:id,name', 'teacher:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit($limit)
            ->get(['id', 'batch_id', 'subject_id', 'teacher_user_id', 'title', 'homework_date', 'due_date']);
    }

    /**
     * The child's marks in exams whose results have been published.
     *
     * @return Collection<int, ExamResult>
     */
    public function publishedResults(int $limit = 5): Collection
    {
        if (! $this->child || ! $this->can('results')) {
            return collect();
        }

        return $this->resultsQuery()
            ->with(['exam:id,title,subject_id,total_marks,exam_date', 'exam.subject:id,name'])
            ->join('exams', 'exams.id', '=', 'exam_results.exam_id')
            ->orderByDesc('exams.exam_date')
            ->limit($limit)
            ->get(['exam_results.id', 'exam_results.exam_id', 'exam_results.marks_obtained', 'exam_results.grade',
                'exam_results.is_attempted', 'exam_results.attendance_status'])
            ->each(function (ExamResult $result) {
                $total = (float) ($result->exam?->total_marks ?? 0);
                $result->setAttribute('percent', $result->is_attempted && $total > 0
                    ? round((float) $result->marks_obtained * 100 / $total, 1)
                    : null);
            });
    }

    /**
     * @return Collection<int, Notice>
     */
    public function notices(int $limit = 4): Collection
    {
        if (! $this->child || ! $this->shows('list_notices')) {
            return collect();
        }

        return app(NoticeService::class)->queryForUser($this->user, $this->child->id)
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'title', 'body', 'notice_date', 'audience']);
    }

    /**
     * Upcoming holidays that apply to the child's branch and batches.
     *
     * @return Collection<int, Holiday>
     */
    public function holidays(int $limit = 4): Collection
    {
        if (! $this->child || ! $this->shows('list_holidays') || ! $this->can('holidays')) {
            return collect();
        }

        $batchIds = $this->batchIds();

        return Holiday::query()
            ->where('institute_id', $this->user->institute_id)
            ->whereDate('end_date', '>=', CarbonImmutable::today()->toDateString())
            ->where(fn (Builder $q) => $q->whereDoesntHave('branches')
                ->orWhereHas('branches', fn (Builder $b) => $b->where('branches.id', $this->child->branch_id)))
            ->where(fn (Builder $q) => $q->whereDoesntHave('batches')
                ->orWhereHas('batches', fn (Builder $b) => $b->whereIn('batches.id', $batchIds ?: [0])))
            ->orderBy('start_date')
            ->limit($limit)
            ->get(['id', 'title', 'start_date', 'end_date']);
    }

    /**
     * @return Collection<int, FeePayment>
     */
    public function payments(int $limit = 4): Collection
    {
        if (! $this->child || ! $this->shows('list_fees')) {
            return collect();
        }

        return FeePayment::query()
            ->where('student_id', $this->child->id)
            ->latest('payment_date')
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'amount', 'payment_date', 'payment_method', 'receipt_no']);
    }

    /**
     * @return Collection<int, FeeInvoice>
     */
    public function dueInvoices(int $limit = 4): Collection
    {
        if (! $this->child || ! $this->shows('list_fees')) {
            return collect();
        }

        return FeeInvoice::query()
            ->where('student_id', $this->child->id)
            ->where('due_amount', '>', 0)
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit($limit)
            ->get(['id', 'title', 'net_amount', 'paid_amount', 'due_amount', 'due_date']);
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function leaves(int $limit = 4): Collection
    {
        if (! $this->child || ! $this->shows('list_holidays') || ! $this->can('leave')) {
            return collect();
        }

        return LeaveRequest::query()
            ->where('student_id', $this->child->id)
            ->where('requester_type', LeaveRequest::TYPE_STUDENT)
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'from_date', 'to_date', 'reason', 'status', 'review_note']);
    }

    /**
     * @return array<string, mixed>
     */
    public function chart(string $widget): array
    {
        return $this->remember('chart:'.$widget.':'.$this->child?->id, fn () => match ($widget) {
            'child_attendance' => $this->attendanceChart(),
            'child_results' => $this->resultsChart(),
        });
    }

    /**
     * Monthly attendance rate for the last six months.
     *
     * @return array<string, mixed>
     */
    private function attendanceChart(): array
    {
        [$months, $start] = $this->lastMonths(6);

        $rows = StudentAttendance::query()
            ->where('student_id', $this->child->id)
            ->where('attendance_date', '>=', $start->toDateString())
            ->get(['attendance_date', 'status'])
            ->groupBy(fn (StudentAttendance $a) => $a->attendance_date->format('Y-m'));

        $rates = $rows->map(function (Collection $days) {
            $attended = $days->whereIn('status', [StudentAttendance::STATUS_PRESENT, StudentAttendance::STATUS_LATE])->count();

            return round($attended * 100 / max(1, $days->count()), 1);
        });

        $absent = $rows->map(fn (Collection $days) => $days->where('status', StudentAttendance::STATUS_ABSENT)->count());

        return [
            'labels' => $months->values()->all(),
            'series' => [
                ['name' => 'Attendance %', 'type' => 'column', 'data' => $this->fillBuckets($months, $rates)],
                ['name' => 'Days absent', 'type' => 'line', 'data' => $this->fillBuckets($months, $absent)],
            ],
        ];
    }

    /**
     * Score percentage in the latest published exams, oldest first.
     *
     * @return array<string, mixed>
     */
    private function resultsChart(): array
    {
        $results = $this->publishedResults(10)->reverse()->values()->filter(fn (ExamResult $r) => $r->percent !== null)->values();

        return [
            'labels' => $results->map(fn (ExamResult $r) => $r->exam?->subject?->name
                ? $r->exam->subject->name.' · '.$r->exam->exam_date?->format('d M')
                : ($r->exam?->title ?? 'Exam'))->all(),
            'series' => [['name' => 'Score', 'data' => $results->pluck('percent')->all()]],
            'average' => $this->averagePercent(),
        ];
    }

    private function averagePercent(): ?float
    {
        $row = $this->resultsQuery()
            ->join('exams', 'exams.id', '=', 'exam_results.exam_id')
            ->where('exam_results.is_attempted', true)
            ->where('exams.total_marks', '>', 0)
            ->toBase()
            ->selectRaw('AVG(exam_results.marks_obtained * 100 / exams.total_marks) as average')
            ->first();

        return $row && $row->average !== null ? round((float) $row->average, 1) : null;
    }

    private function resultsQuery(): Builder
    {
        return ExamResult::query()
            ->where('exam_results.student_id', $this->child->id)
            ->whereHas('exam.announcement', fn (Builder $q) => $q->whereNotNull('published_at'));
    }

    private function examQuery(): Builder
    {
        return app(ExamService::class)->queryForUser($this->user, $this->child->id);
    }

    private function homeworkQuery(): Builder
    {
        return app(HomeworkService::class)->queryForUser($this->user, $this->child->id);
    }

    /**
     * @return list<int>
     */
    private function batchIds(): array
    {
        return $this->batchIds ??= StudentBatch::query()
            ->where('student_id', $this->child->id)
            ->where('status', 'active')
            ->pluck('batch_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function item(string $tone, string $icon, string $title, string $text, int $count, ?string $route, array $params = [], bool $showCount = true): array
    {
        return compact('tone', 'icon', 'title', 'text', 'count', 'route', 'params', 'showCount');
    }

    /**
     * @return array<string, bool>
     */
    private function resolveAbilities(): array
    {
        $can = fn (string ...$abilities) => $this->user->canAny($abilities);

        return [
            'homework' => $can('homework.view'),
            'exams' => $can('exam.view'),
            'results' => $can('result_announcement.view'),
            'notices' => $can('notice.view'),
            'holidays' => $can('holiday.view'),
            'study_material' => $can('study_material.view'),
            'leave' => $can('student_leave.view', 'student_leave.add', 'student_leave.show'),
            'leave_add' => $can('student_leave.add'),
        ];
    }

    private function remember(string $key, \Closure $callback): mixed
    {
        return Cache::remember("dashboard:parent:{$this->user->id}:{$this->accessFingerprint()}:{$key}", self::CACHE_TTL, $callback);
    }
}
