<?php

namespace App\Services\Dashboard;

use App\Enums\InstituteStatus;
use App\Enums\UserType;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Holiday;
use App\Models\IncomeExpense;
use App\Models\Inquiry;
use App\Models\Institute;
use App\Models\LeaveRequest;
use App\Models\ExamResult;
use App\Models\Notice;
use App\Models\ResultAnnouncement;
use App\Models\StaffAttendance;
use App\Models\Standard;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\Todo;
use App\Models\User;
use App\Services\Dashboard\Concerns\BuildsTimeSeries;
use App\Services\Dashboard\Concerns\GatesSections;
use App\Services\Dashboard\Concerns\SummarisesAttendance;
use App\Services\StaffAttendanceService;
use App\Services\TodoService;
use App\Support\BranchScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Aggregates for institute-side dashboards (admin, principal, branch manager, accountant, receptionist, staff).
 * Every section is gated by the viewer's permissions and limited to the branches they can access.
 */
class InstituteDashboardService
{
    use BuildsTimeSeries;
    use GatesSections;
    use SummarisesAttendance;

    public const CHART_WIDGETS = [
        'fees_trend', 'attendance_trend', 'admissions', 'lead_sources', 'standards', 'branches',
        'payment_methods', 'batch_attendance',
    ];

    public const PERMISSION_MODULE = 'institute_dashboard';

    /**
     * Section => ability keys it needs (any one). Sections are "kpi_{summary key}", "chart_{widget}"
     * and "list_{block}"; every key must exist as an action of institute_dashboard in the permission matrix.
     *
     * @var array<string, list<string>>
     */
    public const SECTION_ABILITIES = [
        'quick_actions' => [],
        'pulse' => [],
        'kpi_students' => ['students'],
        'kpi_fees' => ['fees'],
        'kpi_dues' => ['dues'],
        'kpi_attendance' => ['attendance'],
        'kpi_staff_attendance' => ['staff_attendance'],
        'kpi_inquiries' => ['inquiries'],
        'kpi_front_desk' => ['inquiries'],
        'kpi_finance' => ['expenses'],
        'attention' => [],
        'chart_fees_trend' => ['fees'],
        'chart_attendance_trend' => ['attendance'],
        'chart_batch_attendance' => ['attendance'],
        'chart_admissions' => ['students'],
        'chart_lead_sources' => ['inquiries'],
        'chart_standards' => ['students'],
        'chart_branches' => ['students'],
        'chart_payment_methods' => ['fees'],
        'list_payments' => ['fees'],
        'list_dues' => ['dues'],
        'list_fee_follow_ups' => ['fee_follow_up'],
        'list_batches_today' => ['attendance'],
        'list_staff_today' => ['staff_attendance'],
        'list_follow_ups' => ['inquiries'],
        'list_inquiries' => ['inquiries'],
        'list_exams' => ['exams'],
        'list_results' => ['results'],
        'list_notices' => ['notices'],
        'list_holidays' => ['holidays', 'students'],
    ];

    /** @var array<string, array{0: string, 1: string, 2: string, 3: string}> action => [ability, route, icon, label] */
    private const QUICK_ACTIONS = [
        'student_add' => ['student_add', 'admin.student.add', 'bx-user-plus', 'Add Student'],
        'fee_add' => ['fee_add', 'admin.fees.add', 'bx-rupee', 'Collect Fee'],
        'fee_follow_up' => ['fee_follow_up', 'admin.fees.follow_up', 'bx-phone-outgoing', 'Fee Follow-ups'],
        'attendance_add' => ['attendance_add', 'admin.attendance.student', 'bx-calendar-check', 'Mark Attendance'],
        'staff_attendance_add' => ['staff_attendance_add', 'admin.attendance.teacher', 'bx-id-card', 'Staff Attendance'],
        'inquiry_add' => ['inquiry_add', 'admin.inquiry.add', 'bx-user-voice', 'New Inquiry'],
        'follow_up' => ['follow_up', 'admin.follow_up', 'bx-phone-call', 'Follow-ups'],
        'notice_add' => ['notice_add', 'admin.notice.add', 'bx-bell', 'Post Notice'],
        'exam_add' => ['exam_add', 'admin.exam.add', 'bx-edit', 'Schedule Exam'],
        'income_expense' => ['expenses', 'admin.income_expense', 'bx-transfer-alt', 'Income & Expense'],
        'gst_invoice' => ['gst', 'admin.gst_invoice', 'bx-file', 'GST Invoices'],
    ];

    private const MAX_KPIS = 5;

    private const MAX_ACTIONS = 5;

    private const CACHE_TTL = 120;

    private readonly int $instituteId;

    private readonly DashboardPersona $persona;

    /** @var array<string, bool> */
    private array $abilities;

    /** @var Collection<int, \App\Models\Branch> */
    private Collection $branchOptions;

    private ?int $selectedBranchId = null;

    /** null = every branch of the institute, [] = none */
    private ?array $branchIds;

    private function __construct(private readonly User $user, ?int $branchId)
    {
        $this->instituteId = (int) $user->institute_id;
        $scope = BranchScope::for($user);

        $this->branchOptions = $scope->branchesForInstitute($this->instituteId);
        $allowedIds = $this->branchOptions->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($branchId && in_array($branchId, $allowedIds, true)) {
            $this->selectedBranchId = $branchId;
            $this->branchIds = [$branchId];
        } else {
            $this->branchIds = $scope->isRestricted() ? $allowedIds : null;
        }

        $this->abilities = $this->resolveAbilities();
        $this->persona = DashboardPersona::for($user);
    }

    public static function for(User $user, ?int $branchId = null): self
    {
        return new self($user, $branchId);
    }

    public function persona(): DashboardPersona
    {
        return $this->persona;
    }

    /**
     * Name used in the hero: the selected / only branch, otherwise the institute.
     */
    public function placeName(): string
    {
        if ($this->selectedBranchName()) {
            return $this->selectedBranchName();
        }

        if ($this->branchIds !== null && $this->branchOptions->count() === 1) {
            return (string) $this->branchOptions->first()->name;
        }

        if ($this->persona === DashboardPersona::BranchManager) {
            return 'your branches';
        }

        return (string) ($this->user->institute?->name ?? 'your institute');
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return list<string>
     */
    public function kpis(array $summary): array
    {
        $available = array_values(array_filter(
            $this->persona->kpis(),
            fn (string $key) => isset($summary[$key]) && $this->shows('kpi_'.$key)
        ));

        return array_slice($available, 0, self::MAX_KPIS);
    }

    /**
     * @return list<array{route: string, icon: string, label: string}>
     */
    public function quickActions(): array
    {
        if (! $this->shows('quick_actions')) {
            return [];
        }

        $actions = [];

        foreach ($this->persona->actions() as $key) {
            [$ability, $route, $icon, $label] = self::QUICK_ACTIONS[$key];

            if ($this->can($ability)) {
                $actions[] = compact('route', 'icon', 'label');
            }
        }

        return array_slice($actions, 0, self::MAX_ACTIONS);
    }

    /**
     * Persona rows with unavailable blocks removed and widths rebalanced.
     *
     * @return list<array<string, string>> block key => column class
     */
    public function layoutRows(): array
    {
        $rows = [];

        foreach ($this->persona->rows() as $row) {
            $visible = array_filter($row, fn (string $block) => $this->hasBlock($block), ARRAY_FILTER_USE_KEY);

            if ($visible === []) {
                continue;
            }

            $rebalance = count($visible) !== count($row);
            $even = intdiv(12, count($visible));

            $rows[] = array_map(
                fn (int $width) => count($visible) === 1 ? 'col-12' : 'col-xl-'.($rebalance ? $even : $width),
                $visible
            );
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function listBlocks(): array
    {
        return array_values(array_filter($this->persona->lists(), fn (string $block) => $this->hasBlock($block)));
    }

    public function hasBlock(string $block): bool
    {
        if (in_array($block, self::CHART_WIDGETS, true)) {
            return $this->allowsWidget($block);
        }

        return $this->shows($block === 'attention' ? 'attention' : 'list_'.$block);
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
     * @return Collection<int, \App\Models\Branch>
     */
    public function branchOptions(): Collection
    {
        return $this->branchOptions;
    }

    public function selectedBranchId(): ?int
    {
        return $this->selectedBranchId;
    }

    public function selectedBranchName(): ?string
    {
        return $this->selectedBranchId
            ? $this->branchOptions->firstWhere('id', $this->selectedBranchId)?->name
            : null;
    }

    public function isMultiBranchView(): bool
    {
        return $this->selectedBranchId === null && $this->branchOptions->count() > 1;
    }

    /**
     * @return list<string>
     */
    public function allowedWidgets(): array
    {
        return array_values(array_filter(self::CHART_WIDGETS, function (string $widget) {
            if ($widget === 'branches' && ! $this->isMultiBranchView()) {
                return false;
            }

            return $this->shows('chart_'.$widget);
        }));
    }

    public function allowsWidget(string $widget): bool
    {
        return in_array($widget, $this->allowedWidgets(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return $this->remember('summary', function () {
            $today = CarbonImmutable::today();
            $monthStart = $today->startOfMonth();
            $prevStart = $monthStart->subMonth();
            $prevCut = $prevStart->addDays($today->day - 1)->min($prevStart->endOfMonth());

            $out = ['pulse' => $this->shows('pulse') ? $this->pulse() : []];

            if ($this->shows('kpi_students')) {
                $new = $this->inRange($this->students(), 'admission_date', $monthStart, $today)->count();
                $newPrev = $this->inRange($this->students(), 'admission_date', $prevStart, $prevCut)->count();

                $out['students'] = [
                    'active' => $this->students()->where('status', 'active')->count(),
                    'total' => $this->students()->count(),
                    'new_this_month' => $new,
                    'change' => $this->percentChange($new, $newPrev),
                    'birthdays_today' => $this->birthdayQuery($today)->count(),
                ];
            }

            if ($this->shows('kpi_fees')) {
                $collected = $this->feeCollectedBetween($monthStart, $today);

                $out['fees'] = [
                    'this_month' => $collected,
                    'today' => $this->feeCollectedBetween($today, $today),
                    'change' => $this->percentChange($collected, $this->feeCollectedBetween($prevStart, $prevCut)),
                ];
            }

            if ($this->shows('kpi_dues')) {
                $dues = $this->scoped(FeeInvoice::query())
                    ->where('due_amount', '>', 0)
                    ->toBase()
                    ->selectRaw('COALESCE(SUM(due_amount), 0) as amount, COUNT(DISTINCT student_id) as students')
                    ->first();

                $out['dues'] = [
                    'amount' => round((float) ($dues->amount ?? 0), 2),
                    'students' => (int) ($dues->students ?? 0),
                    'overdue' => $this->overdueInvoiceQuery($today)->count(),
                ];
            }

            if ($this->shows('kpi_attendance')) {
                $out['attendance'] = $this->attendanceOn($today);
            }

            $followUpsDue = $this->shows('kpi_inquiries') || $this->shows('kpi_front_desk')
                ? $this->inquiryFollowUpQuery($today)->count()
                : 0;

            if ($this->shows('kpi_inquiries')) {
                $new = $this->inRange($this->scoped(Inquiry::query()), 'inquiry_date', $monthStart, $today)->count();
                $newPrev = $this->inRange($this->scoped(Inquiry::query()), 'inquiry_date', $prevStart, $prevCut)->count();
                $total = $this->scoped(Inquiry::query())->count();
                $converted = $this->scoped(Inquiry::query())->where('status', 'converted')->count();

                $out['inquiries'] = [
                    'this_month' => $new,
                    'change' => $this->percentChange($new, $newPrev),
                    'follow_ups_due' => $followUpsDue,
                    'conversion_rate' => $total > 0 ? round($converted * 100 / $total, 1) : 0.0,
                ];
            }

            if ($this->shows('kpi_front_desk')) {
                $out['front_desk'] = [
                    'new_today' => $this->inRange($this->scoped(Inquiry::query()), 'inquiry_date', $today, $today)->count(),
                    'follow_ups_due' => $followUpsDue,
                    'converted_this_month' => $this->scoped(Inquiry::query())
                        ->where('status', 'converted')
                        ->whereBetween('updated_at', [$monthStart->startOfDay(), $today->endOfDay()])
                        ->count(),
                    'admissions_today' => $this->can('students')
                        ? $this->inRange($this->students(), 'admission_date', $today, $today)->count()
                        : null,
                ];
            }

            if ($this->shows('kpi_staff_attendance')) {
                $out['staff_attendance'] = $this->staffAttendanceOn($today);
            }

            if ($this->shows('kpi_finance')) {
                $income = $this->ledgerTotal('income', $monthStart, $today);
                $expense = $this->ledgerTotal('expense', $monthStart, $today);

                $out['finance'] = [
                    'income' => $income,
                    'expense' => $expense,
                    'net' => round($income - $expense, 2),
                    'expense_change' => $this->percentChange($expense, $this->ledgerTotal('expense', $prevStart, $prevCut)),
                ];
            }

            return $out;
        });
    }

    /**
     * Actionable items for the viewer; only non-zero entries are returned.
     *
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

            if ($this->can('fees')) {
                $items[] = $this->attentionItem('overdue_fees', 'error', 'bx-error-circle', 'Overdue fees',
                    'Invoices past their due date', $this->overdueInvoiceQuery($today)->count(), 'admin.fees.follow_up');
            }

            if ($this->can('fee_follow_up')) {
                $items[] = $this->attentionItem('fee_follow_ups', 'warning', 'bx-phone-call', 'Fee follow-ups due',
                    'Scheduled for today or earlier',
                    $this->scoped(FeeInvoice::query())
                        ->where('due_amount', '>', 0)
                        ->whereHas('latestFollowUp', fn (Builder $q) => $q->where('status', 'pending')
                            ->where('next_follow_up_at', '<=', $today->endOfDay()))
                        ->count(),
                    'admin.fees.follow_up', ['status' => 'pending']);
            }

            if ($this->can('inquiries')) {
                $items[] = $this->attentionItem('inquiry_follow_ups', 'info', 'bx-user-voice', 'Inquiry follow-ups',
                    'Leads to call back today', $this->inquiryFollowUpQuery($today)->count(),
                    $this->can('follow_up') ? 'admin.follow_up' : 'admin.inquiry');
            }

            if ($this->can('attendance_add') && ! $this->isHoliday($today)) {
                $attendance = $this->attendanceOn($today);
                $items[] = $this->attentionItem('attendance_pending', 'primary', 'bx-calendar-x', 'Attendance not marked',
                    'Active batches pending today',
                    max(0, $attendance['batches_total'] - $attendance['batches_marked']), 'admin.attendance.student');
            }

            if ($this->can('student_leave')) {
                $items[] = $this->attentionItem('student_leaves', 'secondary', 'bx-envelope', 'Student leave requests',
                    'Waiting for approval', $this->pendingLeaves(LeaveRequest::TYPE_STUDENT), 'admin.student_leave_request');
            }

            if ($this->can('staff_leave')) {
                $items[] = $this->attentionItem('staff_leaves', 'secondary', 'bx-briefcase', 'Staff leave requests',
                    'Waiting for approval', $this->pendingLeaves(LeaveRequest::TYPE_STAFF), 'admin.staff_leave_request');
            }

            if ($this->can('exams')) {
                $items[] = $this->attentionItem('exams_today', 'info', 'bx-edit', 'Exams today', 'Scheduled for today',
                    $this->scoped(Exam::query())->whereDate('exam_date', $today->toDateString())->count(), 'admin.exam');
            }

            if ($this->can('todo')) {
                $items[] = $this->attentionItem('tasks_due', 'warning', 'bx-task', 'Tasks due',
                    'Pending tasks due today or overdue',
                    app(TodoService::class)->queryForUser($this->user)
                        ->where('status', Todo::STATUS_PENDING)
                        ->whereDate('due_date', '<=', $today->toDateString())
                        ->count(),
                    'admin.to_do_task');
            }

            $plan = $this->planStatus();
            if ($plan && $plan['days_left'] !== null && $plan['days_left'] <= 7) {
                $items[] = $this->attentionItem('plan_expiry', 'error', 'bx-crown',
                    $plan['is_trial'] ? 'Trial ending soon' : 'Plan renewal due',
                    $plan['days_left'] === 0 ? 'Ends today' : 'Ends in '.$plan['days_left'].' '.Str::plural('day', $plan['days_left']),
                    max(1, $plan['days_left']), 'admin.billing.index', [], false);
            }

            return array_values(array_filter($items, fn (array $item) => $item['count'] > 0));
        });
    }

    /**
     * Plan / trial info for users who manage billing.
     *
     * @return array{name: string, is_trial: bool, ends_at: ?CarbonImmutable, days_left: ?int}|null
     */
    public function planStatus(): ?array
    {
        if (! $this->can('billing')) {
            return null;
        }

        /** @var Institute|null $institute */
        $institute = $this->user->institute()->with('currentSubscription.plan:id,name')->first();
        if (! $institute) {
            return null;
        }

        $isTrial = $institute->status === InstituteStatus::Trial;
        $endsAt = $isTrial ? $institute->trial_ends_at : $institute->currentSubscription?->ends_at;

        return [
            'name' => $institute->currentSubscription?->plan?->name ?? ($isTrial ? 'Free Trial' : 'No plan'),
            'is_trial' => $isTrial,
            'ends_at' => $endsAt ? CarbonImmutable::parse($endsAt) : null,
            'days_left' => $endsAt ? max(0, (int) ceil(now()->diffInDays($endsAt, false))) : null,
        ];
    }

    /**
     * @return Collection<int, FeePayment>
     */
    public function recentPayments(int $limit = 6): Collection
    {
        if (! $this->shows('list_payments')) {
            return collect();
        }

        return $this->feePayments()
            ->with(['student:id,name,admission_no,photo_path'])
            ->latest('payment_date')
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'student_id', 'amount', 'payment_date', 'payment_method', 'receipt_no']);
    }

    /**
     * Students with the highest outstanding balance.
     *
     * @return Collection<int, FeeInvoice>
     */
    public function topDues(int $limit = 6): Collection
    {
        if (! $this->shows('list_dues')) {
            return collect();
        }

        return $this->scoped(FeeInvoice::query())
            ->where('due_amount', '>', 0)
            ->selectRaw('student_id, SUM(due_amount) as due_total, COUNT(*) as invoice_count, MIN(due_date) as oldest_due')
            ->groupBy('student_id')
            ->orderByDesc('due_total')
            ->limit($limit)
            ->get()
            ->load(['student:id,name,admission_no,photo_path,standard_id', 'student.standard:id,name']);
    }

    /**
     * @return Collection<int, Exam>
     */
    public function upcomingExams(int $days = 14, int $limit = 6): Collection
    {
        if (! $this->shows('list_exams')) {
            return collect();
        }

        $today = CarbonImmutable::today();

        return $this->scoped(Exam::query())
            ->whereBetween('exam_date', [$today->toDateString(), $today->addDays($days)->toDateString()])
            ->with(['batch:id,name', 'subject:id,name'])
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get(['id', 'batch_id', 'subject_id', 'title', 'exam_date', 'start_time', 'total_marks']);
    }

    /**
     * @return Collection<int, Inquiry>
     */
    public function recentInquiries(int $limit = 6): Collection
    {
        if (! $this->shows('list_inquiries')) {
            return collect();
        }

        return $this->scoped(Inquiry::query())
            ->with('standard:id,name')
            ->latest()
            ->limit($limit)
            ->get(['id', 'student_name', 'phone', 'standard_id', 'source', 'status', 'inquiry_date', 'next_follow_up_at', 'created_at']);
    }

    /**
     * @return Collection<int, Notice>
     */
    public function latestNotices(int $limit = 4): Collection
    {
        if (! $this->shows('list_notices')) {
            return collect();
        }

        return $this->scopeToBranchPivot(Notice::query()->where('institute_id', $this->instituteId))
            ->where('status', 'active')
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'title', 'notice_date', 'audience']);
    }

    /**
     * @return Collection<int, Holiday>
     */
    public function upcomingHolidays(int $limit = 4): Collection
    {
        if (! $this->shows('list_holidays') || ! $this->can('holidays')) {
            return collect();
        }

        return $this->scopeToBranchPivot(Holiday::query()->where('institute_id', $this->instituteId))
            ->whereDate('end_date', '>=', CarbonImmutable::today()->toDateString())
            ->orderBy('start_date')
            ->limit($limit)
            ->get(['id', 'title', 'start_date', 'end_date']);
    }

    /**
     * @return Collection<int, Student>
     */
    public function birthdaysToday(int $limit = 8): Collection
    {
        if (! $this->shows('list_holidays') || ! $this->can('students')) {
            return collect();
        }

        return $this->birthdayQuery(CarbonImmutable::today())
            ->with('standard:id,name')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'photo_path', 'standard_id', 'dob']);
    }

    /**
     * Leads to call back today or overdue, oldest first.
     *
     * @return Collection<int, Inquiry>
     */
    public function followUpsToday(int $limit = 7): Collection
    {
        if (! $this->shows('list_follow_ups')) {
            return collect();
        }

        return $this->inquiryFollowUpQuery(CarbonImmutable::today())
            ->with('standard:id,name')
            ->orderBy('next_follow_up_at')
            ->limit($limit)
            ->get(['id', 'student_name', 'phone', 'standard_id', 'source', 'status', 'next_follow_up_at']);
    }

    /**
     * Today's staff & teacher roster; unmarked and absent people first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function staffToday(int $limit = 7): Collection
    {
        if (! $this->shows('list_staff_today')) {
            return collect();
        }

        $order = [StaffAttendance::STATUS_ABSENT => 0, 'unmarked' => 1, StaffAttendance::STATUS_LATE => 2, StaffAttendance::STATUS_PRESENT => 3];

        return $this->staffRoster(CarbonImmutable::today())
            ->sortBy(fn (array $row) => sprintf('%d-%s', $order[$row['state']] ?? 9, $row['name']))
            ->take($limit)
            ->values();
    }

    /**
     * Active batches with today's attendance status; unmarked batches first.
     *
     * @return Collection<int, Batch>
     */
    public function batchesToday(int $limit = 7): Collection
    {
        if (! $this->shows('list_batches_today')) {
            return collect();
        }

        $today = CarbonImmutable::today()->toDateString();

        $batches = $this->activeBatchQuery()
            ->with('standard:id,name')
            ->withCount(['enrollments as students_count' => fn (Builder $q) => $q->where('status', 'active')
                ->select(DB::raw('COUNT(DISTINCT student_id)'))])
            ->orderBy('start_time')
            ->orderBy('name')
            ->get(['id', 'name', 'standard_id', 'start_time', 'end_time']);

        $marks = $this->scoped(StudentAttendance::query())
            ->whereDate('attendance_date', $today)
            ->whereIn('batch_id', $batches->pluck('id'))
            ->toBase()
            ->selectRaw('batch_id, status, COUNT(*) as total')
            ->groupBy('batch_id', 'status')
            ->get()
            ->groupBy('batch_id');

        return $batches
            ->each(function (Batch $batch) use ($marks) {
                $counts = collect($marks->get($batch->id, []))->pluck('total', 'status');
                $batch->setAttribute('present_count', (int) ($counts[StudentAttendance::STATUS_PRESENT] ?? 0) + (int) ($counts[StudentAttendance::STATUS_LATE] ?? 0));
                $batch->setAttribute('absent_count', (int) ($counts[StudentAttendance::STATUS_ABSENT] ?? 0));
                $batch->setAttribute('is_marked', $counts->isNotEmpty());
            })
            ->sortBy(fn (Batch $b) => $b->is_marked ? 1 : 0)
            ->take($limit)
            ->values();
    }

    /**
     * Latest published results with the class average.
     *
     * @return Collection<int, ResultAnnouncement>
     */
    public function recentResults(int $limit = 5): Collection
    {
        if (! $this->shows('list_results')) {
            return collect();
        }

        $announcements = ResultAnnouncement::query()
            ->where('institute_id', $this->instituteId)
            ->whereNotNull('published_at')
            ->whereHas('exam', fn (Builder $q) => $this->scoped($q))
            ->with(['exam:id,title,batch_id,subject_id,total_marks,exam_date', 'exam.batch:id,name', 'exam.subject:id,name'])
            ->latest('published_at')
            ->limit($limit)
            ->get(['id', 'exam_id', 'title', 'published_at']);

        $stats = ExamResult::query()
            ->whereIn('exam_id', $announcements->pluck('exam_id'))
            ->where('is_attempted', true)
            ->toBase()
            ->selectRaw('exam_id, AVG(marks_obtained) as average, COUNT(*) as students')
            ->groupBy('exam_id')
            ->get()
            ->keyBy('exam_id');

        return $announcements->each(function (ResultAnnouncement $announcement) use ($stats) {
            $row = $stats->get($announcement->exam_id);
            $total = (float) ($announcement->exam?->total_marks ?? 0);

            $announcement->setAttribute('students_count', (int) ($row->students ?? 0));
            $announcement->setAttribute('average_percent', $row && $total > 0 ? round($row->average * 100 / $total, 1) : null);
        });
    }

    /**
     * Pending fee follow-ups scheduled up to today, oldest first.
     *
     * @return Collection<int, FeeInvoice>
     */
    public function feeFollowUpsDue(int $limit = 6): Collection
    {
        if (! $this->shows('list_fee_follow_ups')) {
            return collect();
        }

        $endOfToday = CarbonImmutable::today()->endOfDay();

        return $this->scoped(FeeInvoice::query())
            ->where('due_amount', '>', 0)
            ->whereHas('latestFollowUp', fn (Builder $q) => $q->where('status', 'pending')
                ->where('next_follow_up_at', '<=', $endOfToday))
            ->with(['student:id,name,photo_path,parent_id', 'student.parent:id,phone', 'latestFollowUp'])
            ->get(['id', 'student_id', 'title', 'due_amount', 'due_date'])
            ->sortBy(fn (FeeInvoice $invoice) => $invoice->latestFollowUp?->next_follow_up_at)
            ->take($limit)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function chart(string $widget): array
    {
        return $this->remember('chart:'.$widget, fn () => match ($widget) {
            'fees_trend' => $this->feesTrendChart(),
            'attendance_trend' => $this->attendanceTrendChart(),
            'admissions' => $this->admissionsChart(),
            'lead_sources' => $this->leadSourcesChart(),
            'standards' => $this->standardsChart(),
            'branches' => $this->branchesChart(),
            'payment_methods' => $this->paymentMethodsChart(),
            'batch_attendance' => $this->batchAttendanceChart(),
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentMethodsChart(): array
    {
        $today = CarbonImmutable::today();

        $methods = $this->feePayments()
            ->whereBetween('payment_date', [$today->startOfMonth()->toDateString(), $today->toDateString()])
            ->toBase()
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($row) => [Str::headline($row->payment_method ?: 'cash') => round((float) $row->total, 2)]);

        return [
            'labels' => $methods->keys()->all(),
            'series' => $methods->values()->all(),
            'total' => round((float) $methods->sum(), 2),
        ];
    }


    /**
     * @return array<string, mixed>
     */
    private function feesTrendChart(): array
    {
        [$months, $start] = $this->lastMonths(12);

        $collected = $this->feePayments()
            ->where('payment_date', '>=', $start->toDateString())
            ->toBase()
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, COALESCE(SUM(amount), 0) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->map(fn ($total) => round((float) $total, 2));

        $series = [['name' => 'Fee collection', 'type' => 'column', 'data' => $this->fillBuckets($months, $collected)]];

        if ($this->can('expenses')) {
            $expenses = $this->scoped(IncomeExpense::query())
                ->whereRaw('LOWER(type) = ?', ['expense'])
                ->where('entry_date', '>=', $start->toDateString())
                ->toBase()
                ->selectRaw("DATE_FORMAT(entry_date, '%Y-%m') as ym, COALESCE(SUM(amount), 0) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym')
                ->map(fn ($total) => round((float) $total, 2));

            $series[] = ['name' => 'Expenses', 'type' => 'line', 'data' => $this->fillBuckets($months, $expenses)];
        }

        return [
            'labels' => $months->values()->all(),
            'series' => $series,
            'total' => round((float) $collected->sum(), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceTrendChart(): array
    {
        return $this->attendanceTrendFrom($this->scoped(StudentAttendance::query()));
    }

    /**
     * @return array<string, mixed>
     */
    private function batchAttendanceChart(): array
    {
        return $this->batchRatesFrom($this->scoped(StudentAttendance::query()));
    }

    /**
     * @return array<string, mixed>
     */
    private function admissionsChart(): array
    {
        [$months, $start] = $this->lastMonths(12);
        $end = CarbonImmutable::today()->toDateString();
        $startDate = $start->toDateString();
        $startDateTime = $start->startOfDay()->toDateTimeString();
        $endDateTime = CarbonImmutable::today()->endOfDay()->toDateTimeString();

        $perMonth = $this->students()
            ->toBase()
            ->where(function ($q) use ($startDate, $end, $startDateTime, $endDateTime) {
                $q->whereBetween('admission_date', [$startDate, $end])
                    ->orWhere(function ($n) use ($startDateTime, $endDateTime) {
                        $n->whereNull('admission_date')
                            ->whereBetween('created_at', [$startDateTime, $endDateTime]);
                    });
            })
            ->selectRaw("DATE_FORMAT(COALESCE(admission_date, DATE(created_at)), '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->map(fn ($total) => (int) $total);

        $series = $this->fillBuckets($months, $perMonth);

        return [
            'labels' => $months->values()->all(),
            'series' => [['name' => 'New admissions', 'data' => $series]],
            'total' => array_sum($series),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leadSourcesChart(): array
    {
        $sources = $this->scoped(Inquiry::query())
            ->toBase()
            ->selectRaw('source, COUNT(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($row) => [Str::headline($row->source ?: 'unknown') => (int) $row->total]);

        $statuses = $this->scoped(Inquiry::query())
            ->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => $sources->keys()->all(),
            'series' => $sources->values()->all(),
            'total' => (int) $sources->sum(),
            'statuses' => [
                'new' => (int) ($statuses['new'] ?? 0),
                'follow_up' => (int) ($statuses['follow_up'] ?? 0),
                'converted' => (int) ($statuses['converted'] ?? 0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function standardsChart(): array
    {
        $counts = $this->students()
            ->where('status', 'active')
            ->toBase()
            ->selectRaw('standard_id, COUNT(*) as total')
            ->groupBy('standard_id')
            ->pluck('total', 'standard_id');

        $standards = Standard::query()
            ->where('institute_id', $this->instituteId)
            ->whereIn('id', $counts->keys()->filter()->all())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');

        $labels = [];
        $data = [];
        foreach ($standards as $id => $name) {
            $labels[] = $name;
            $data[] = (int) $counts[$id];
        }

        $unassigned = (int) ($counts[''] ?? 0);
        if ($unassigned > 0) {
            $labels[] = 'Not assigned';
            $data[] = $unassigned;
        }

        return [
            'labels' => $labels,
            'series' => [['name' => 'Students', 'data' => $data]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function branchesChart(): array
    {
        $students = $this->students()
            ->where('status', 'active')
            ->toBase()
            ->selectRaw('branch_id, COUNT(*) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');

        $series = [[
            'name' => 'Active students',
            'type' => 'column',
            'data' => $this->branchOptions->map(fn ($b) => (int) ($students[$b->id] ?? 0))->values()->all(),
        ]];

        if ($this->can('fees')) {
            $collected = $this->feePayments()
                ->where('payment_date', '>=', CarbonImmutable::today()->startOfMonth()->toDateString())
                ->join('students as s', 's.id', '=', 'fee_payments.student_id')
                ->toBase()
                ->selectRaw('s.branch_id, SUM(fee_payments.amount) as total')
                ->groupBy('s.branch_id')
                ->pluck('total', 'branch_id');

            $series[] = [
                'name' => 'Fees this month',
                'type' => 'column',
                'data' => $this->branchOptions->map(fn ($b) => round((float) ($collected[$b->id] ?? 0), 2))->values()->all(),
            ];
        }

        return [
            'labels' => $this->branchOptions->pluck('name')->values()->all(),
            'series' => $series,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function pulse(): array
    {
        $pulse = [];

        if ($this->can('branches')) {
            $pulse['branches'] = $this->selectedBranchId ? 1 : $this->branchOptions->count();
        }

        if ($this->can('batches')) {
            $pulse['batches'] = $this->activeBatchQuery()->count();
        }

        if ($this->can('teachers')) {
            $pulse['teachers'] = Teacher::query()
                ->where('institute_id', $this->instituteId)
                ->where('status', 'active')
                ->when($this->branchIds !== null, fn (Builder $q) => $q->whereHas(
                    'branches',
                    fn (Builder $b) => $b->whereIn('branches.id', $this->branchIds ?: [0])
                ))
                ->count();
        }

        return $pulse;
    }

    /**
     * @return array{present: int, late: int, absent: int, marked: int, rate: ?float, batches_marked: int, batches_total: int}
     */
    private function attendanceOn(CarbonImmutable $day): array
    {
        $query = fn () => $this->scoped(StudentAttendance::query())->whereDate('attendance_date', $day->toDateString());

        $counts = $query()->toBase()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $present = (int) ($counts[StudentAttendance::STATUS_PRESENT] ?? 0);
        $late = (int) ($counts[StudentAttendance::STATUS_LATE] ?? 0);
        $absent = (int) ($counts[StudentAttendance::STATUS_ABSENT] ?? 0);
        $marked = $present + $late + $absent;

        return [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'marked' => $marked,
            'rate' => $marked > 0 ? round(($present + $late) * 100 / $marked, 1) : null,
            'batches_marked' => (int) $query()->distinct()->count('batch_id'),
            'batches_total' => $this->activeBatchQuery()->count(),
        ];
    }

    /**
     * @return array{present: int, late: int, absent: int, marked: int, total: int, rate: ?float}
     */
    private function staffAttendanceOn(CarbonImmutable $day): array
    {
        $states = $this->staffRoster($day)->countBy('state');

        $present = (int) ($states[StaffAttendance::STATUS_PRESENT] ?? 0);
        $late = (int) ($states[StaffAttendance::STATUS_LATE] ?? 0);
        $absent = (int) ($states[StaffAttendance::STATUS_ABSENT] ?? 0);
        $marked = $present + $late + $absent;

        return [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'marked' => $marked,
            'total' => $marked + (int) ($states['unmarked'] ?? 0),
            'rate' => $marked > 0 ? round(($present + $late) * 100 / $marked, 1) : null,
        ];
    }

    /**
     * Active teachers & staff in scope with their attendance state for the day.
     *
     * @return Collection<int, array{user_id: int, name: string, role: string, branch_name: string, state: string, check_in_at: ?string, late_minutes: ?int}>
     */
    private function staffRoster(CarbonImmutable $day): Collection
    {
        return collect($this->remember('staff_roster:'.$day->toDateString(), function () use ($day) {
            $service = app(StaffAttendanceService::class);
            $date = $day->toDateString();

            $rows = collect($this->branchIds ?? [null])
                ->flatMap(fn (?int $branchId) => $service->roster($this->instituteId, $date, $branchId))
                ->unique('user_id');

            $records = StaffAttendance::query()
                ->where('institute_id', $this->instituteId)
                ->whereDate('attendance_date', $date)
                ->whereIn('user_id', $rows->pluck('user_id'))
                ->get(['user_id', 'status', 'check_in_at', 'late_minutes'])
                ->keyBy('user_id');

            return $rows->map(function (array $row) use ($records) {
                $record = $records->get($row['user_id']);

                return [
                    'user_id' => (int) $row['user_id'],
                    'name' => (string) $row['name'],
                    'role' => (string) $row['role'],
                    'branch_name' => (string) $row['branch_name'],
                    'state' => $record?->status ?? 'unmarked',
                    'check_in_at' => $record?->check_in_at ? substr((string) $record->check_in_at, 0, 5) : null,
                    'late_minutes' => $record?->late_minutes,
                ];
            })->values()->all();
        }));
    }

    private function ledgerTotal(string $type, CarbonImmutable $from, CarbonImmutable $to): float
    {
        return round((float) $this->scoped(IncomeExpense::query())
            ->whereRaw('LOWER(type) = ?', [$type])
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount'), 2);
    }

    private function resolveAbilities(): array
    {
        $can = fn (string ...$abilities) => $this->user->canAny($abilities);

        return [
            'students' => $can('student.view'),
            'student_show' => $can('student.show'),
            'student_add' => $can('student.add'),
            'fees' => $can('fees.view'),
            'fee_add' => $can('fees.add'),
            'fee_follow_up' => $can('fees_follow_up.view'),
            'dues' => $can('fees.view', 'fees_follow_up.view'),
            'attendance' => $can('attendance.view'),
            'attendance_add' => $can('attendance.add'),
            'inquiries' => $can('inquiry.view'),
            'inquiry_add' => $can('inquiry.add'),
            'follow_up' => $can('follow_up.view'),
            'exams' => $can('exam.view'),
            'exam_add' => $can('exam.add'),
            'results' => $can('result_announcement.view'),
            'staff_attendance' => $can('teacher_attendance.view'),
            'staff_attendance_add' => $can('teacher_attendance.add'),
            'gst' => $can('gst_invoice.view'),
            'notices' => $can('notice.view'),
            'notice_add' => $can('notice.add'),
            'holidays' => $can('holiday.view'),
            'student_leave' => $can('student_leave.view'),
            'staff_leave' => $can('staff_leave.view'),
            'todo' => $can('todo.view', 'todo.add'),
            'expenses' => $can('income_expense.view'),
            'billing' => $this->user->user_type === UserType::InstituteAdmin && $can('billing.view'),
            'branches' => $can('branch.view'),
            'batches' => $can('batch.view'),
            'teachers' => $can('teacher.view'),
        ];
    }

    private function students(): Builder
    {
        return $this->scoped(Student::query());
    }

    private function feePayments(): Builder
    {
        return FeePayment::query()->whereHas('student', fn (Builder $q) => $this->scoped($q));
    }

    private function activeBatchQuery(): Builder
    {
        return $this->scoped(Batch::query())->where('status', 'active');
    }

    private function overdueInvoiceQuery(CarbonImmutable $today): Builder
    {
        return $this->scoped(FeeInvoice::query())
            ->where('due_amount', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString());
    }

    private function inquiryFollowUpQuery(CarbonImmutable $today): Builder
    {
        return $this->scoped(Inquiry::query())
            ->where('status', '!=', 'converted')
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', $today->endOfDay());
    }

    private function birthdayQuery(CarbonImmutable $day): Builder
    {
        return $this->students()
            ->where('status', 'active')
            ->whereMonth('dob', $day->month)
            ->whereDay('dob', $day->day);
    }

    private function pendingLeaves(string $type): int
    {
        return $this->scoped(LeaveRequest::query())
            ->where('requester_type', $type)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->count();
    }

    private function isHoliday(CarbonImmutable $day): bool
    {
        return $this->scopeToBranchPivot(Holiday::query()->where('institute_id', $this->instituteId))
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->exists();
    }

    private function feeCollectedBetween(CarbonImmutable $from, CarbonImmutable $to): float
    {
        return round((float) $this->feePayments()
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount'), 2);
    }

    /**
     * Limit to the institute and the viewer's branches.
     */
    private function scoped(Builder $query, string $branchColumn = 'branch_id'): Builder
    {
        $table = $query->getModel()->getTable();
        $query->where($table.'.institute_id', $this->instituteId);

        if ($this->branchIds === null) {
            return $query;
        }

        return $this->branchIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn($table.'.'.$branchColumn, $this->branchIds);
    }

    /**
     * Records linked to branches through a pivot; no linked branch means "all branches".
     */
    private function scopeToBranchPivot(Builder $query): Builder
    {
        if ($this->branchIds === null) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->whereDoesntHave('branches')
            ->orWhereHas('branches', fn (Builder $b) => $b->whereIn('branches.id', $this->branchIds ?: [0])));
    }

    /**
     * Date column with created_at fallback for rows where the business date is empty.
     */
    private function inRange(Builder $query, string $dateColumn, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereBetween($dateColumn, [$from->toDateString(), $to->toDateString()])
            ->orWhere(fn (Builder $n) => $n->whereNull($dateColumn)
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function attentionItem(
        string $key,
        string $tone,
        string $icon,
        string $title,
        string $text,
        int $count,
        ?string $route = null,
        array $params = [],
        bool $showCount = true,
    ): array {
        if ($route && $this->selectedBranchId && $route !== 'admin.billing.index') {
            $params['branch_id'] ??= $this->selectedBranchId;
        }

        return compact('key', 'tone', 'icon', 'title', 'text', 'count', 'route', 'params', 'showCount');
    }

    /**
     * Drop cached institute dashboard aggregates (summary, charts, attention).
     * File/database cache has no tags, so a version bump misses every old key.
     */
    public static function forgetForInstitute(int $instituteId): void
    {
        if ($instituteId < 1) {
            return;
        }

        $versionKey = "dashboard:institute:{$instituteId}:version";
        Cache::put($versionKey, ((int) Cache::get($versionKey, 1)) + 1, now()->addYear());
    }

    private function remember(string $key, \Closure $callback): mixed
    {
        $version = (int) Cache::get("dashboard:institute:{$this->instituteId}:version", 1);
        $scopeKey = md5(json_encode([$this->user->id, $this->branchIds, $this->accessFingerprint()]));

        return Cache::remember(
            "dashboard:institute:{$this->instituteId}:v{$version}:{$scopeKey}:{$key}",
            self::CACHE_TTL,
            $callback
        );
    }
}
