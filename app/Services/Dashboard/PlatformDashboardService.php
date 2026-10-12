<?php

namespace App\Services\Dashboard;

use App\Enums\DemoStatus;
use App\Enums\InstituteStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectInquiryStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Branch;
use App\Models\Institute;
use App\Models\LoginLog;
use App\Models\ProjectInquiry;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Teacher;
use App\Services\Dashboard\Concerns\BuildsTimeSeries;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Aggregates for the platform (super admin) dashboard.
 * KPIs render server-side; chart widgets are fetched lazily via chart().
 */
class PlatformDashboardService
{
    use BuildsTimeSeries;

    public const CHART_WIDGETS = ['revenue', 'institutes', 'plans', 'inquiries', 'logins'];

    private const CACHE_TTL = 300;

    private const CACHE_PREFIX = 'dashboard:platform:';

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return $this->remember('summary', function () {
            $now = CarbonImmutable::now();
            $monthStart = $now->startOfMonth();
            $lastMonthStart = $monthStart->subMonth();

            $institutesByStatus = Institute::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $newInstitutes = Institute::query()->where('created_at', '>=', $monthStart)->count();
            $newInstitutesLast = Institute::query()
                ->whereBetween('created_at', [$lastMonthStart, $monthStart])
                ->count();

            $revenueThis = $this->paidRevenueBetween($monthStart, $now);
            $revenueLast = $this->paidRevenueBetween($lastMonthStart, $monthStart);

            $activeSubscriptions = $this->activeSubscriptionQuery($now)->count();
            $expiringSoon = $this->activeSubscriptionQuery($now)
                ->where('ends_at', '<=', $now->addDays(7))
                ->count();

            $inquiriesThis = ProjectInquiry::query()->where('created_at', '>=', $monthStart)->count();
            $inquiriesLast = ProjectInquiry::query()
                ->whereBetween('created_at', [$lastMonthStart, $monthStart])
                ->count();
            $inquiriesTotal = ProjectInquiry::query()->count();
            $converted = ProjectInquiry::query()
                ->where('status', ProjectInquiryStatus::Converted->value)
                ->count();

            return [
                'institutes' => [
                    'total' => (int) $institutesByStatus->sum(),
                    'active' => (int) ($institutesByStatus[InstituteStatus::Active->value] ?? 0),
                    'trial' => (int) ($institutesByStatus[InstituteStatus::Trial->value] ?? 0),
                    'inactive' => (int) (($institutesByStatus[InstituteStatus::Suspended->value] ?? 0)
                        + ($institutesByStatus[InstituteStatus::Cancelled->value] ?? 0)),
                    'new_this_month' => $newInstitutes,
                    'change' => $this->percentChange($newInstitutes, $newInstitutesLast),
                ],
                'revenue' => [
                    'this_month' => $revenueThis,
                    'last_month' => $revenueLast,
                    'year' => $this->paidRevenueBetween($now->startOfYear(), $now),
                    'change' => $this->percentChange($revenueThis, $revenueLast),
                ],
                'subscriptions' => [
                    'active' => $activeSubscriptions,
                    'expiring_7d' => $expiringSoon,
                ],
                'inquiries' => [
                    'this_month' => $inquiriesThis,
                    'change' => $this->percentChange($inquiriesThis, $inquiriesLast),
                    'upcoming_demos' => ProjectInquiry::query()
                        ->where('demo_status', DemoStatus::Scheduled->value)
                        ->where('demo_scheduled_at', '>=', $now)
                        ->count(),
                    'conversion_rate' => $inquiriesTotal > 0 ? round($converted * 100 / $inquiriesTotal, 1) : 0.0,
                ],
                'platform' => [
                    'students' => Student::query()->count(),
                    'teachers' => Teacher::query()->count(),
                    'branches' => Branch::query()->count(),
                ],
            ];
        });
    }

    /**
     * Actionable items; only non-zero entries are returned.
     *
     * @return list<array<string, mixed>>
     */
    public function attention(): array
    {
        return $this->remember('attention', function () {
            $now = CarbonImmutable::now();
            $week = $now->addDays(7);

            $items = [
                [
                    'key' => 'expiring_subscriptions',
                    'tone' => 'warning',
                    'icon' => 'bx-time-five',
                    'title' => 'Subscriptions expiring',
                    'text' => 'Renewal due within 7 days',
                    'count' => $this->activeSubscriptionQuery($now)->where('ends_at', '<=', $week)->count(),
                    'route' => 'admin.institutes.index',
                    'params' => ['status' => InstituteStatus::Active->value],
                ],
                [
                    'key' => 'trials_ending',
                    'tone' => 'info',
                    'icon' => 'bx-hourglass',
                    'title' => 'Trials ending soon',
                    'text' => 'Trial ends within 7 days',
                    'count' => Institute::query()
                        ->where('status', InstituteStatus::Trial->value)
                        ->whereBetween('trial_ends_at', [$now, $week])
                        ->count(),
                    'route' => 'admin.institutes.index',
                    'params' => ['status' => InstituteStatus::Trial->value],
                ],
                [
                    'key' => 'trials_expired',
                    'tone' => 'error',
                    'icon' => 'bx-error-circle',
                    'title' => 'Expired trials',
                    'text' => 'Trial over, no plan assigned',
                    'count' => Institute::query()
                        ->where('status', InstituteStatus::Trial->value)
                        ->where('trial_ends_at', '<', $now)
                        ->count(),
                    'route' => 'admin.institutes.index',
                    'params' => ['status' => InstituteStatus::Trial->value],
                ],
                [
                    'key' => 'failed_payments',
                    'tone' => 'error',
                    'icon' => 'bx-credit-card',
                    'title' => 'Failed payments',
                    'text' => 'In the last 30 days',
                    'count' => SubscriptionPayment::query()
                        ->where('status', PaymentStatus::Failed->value)
                        ->where('created_at', '>=', $now->subDays(30))
                        ->count(),
                    'route' => 'admin.billing.index',
                    'params' => [],
                ],
                [
                    'key' => 'demos_today',
                    'tone' => 'primary',
                    'icon' => 'bx-video',
                    'title' => 'Demos today',
                    'text' => 'Scheduled product demos',
                    'count' => ProjectInquiry::query()
                        ->where('demo_status', DemoStatus::Scheduled->value)
                        ->whereDate('demo_scheduled_at', $now->toDateString())
                        ->count(),
                    'route' => 'admin.demo-calendar.index',
                    'params' => [],
                ],
                [
                    'key' => 'unassigned_inquiries',
                    'tone' => 'secondary',
                    'icon' => 'bx-user-voice',
                    'title' => 'Unassigned inquiries',
                    'text' => 'New leads without an owner',
                    'count' => ProjectInquiry::query()
                        ->where('status', ProjectInquiryStatus::New->value)
                        ->whereNull('assigned_to')
                        ->count(),
                    'route' => 'admin.project-inquiries.index',
                    'params' => [],
                ],
                [
                    'key' => 'failed_logins',
                    'tone' => 'error',
                    'icon' => 'bx-shield-x',
                    'title' => 'Failed sign-ins',
                    'text' => 'In the last 24 hours',
                    'count' => LoginLog::query()
                        ->where('event', 'failed')
                        ->where('logged_at', '>=', $now->subDay())
                        ->count(),
                    'route' => null,
                    'params' => [],
                ],
            ];

            return array_values(array_filter($items, fn (array $item) => $item['count'] > 0));
        });
    }

    /**
     * @return Collection<int, Institute>
     */
    public function recentInstitutes(int $limit = 6): Collection
    {
        return Institute::query()
            ->with('currentSubscription.plan:id,name')
            ->latest()
            ->limit($limit)
            ->get(['id', 'name', 'code', 'city', 'status', 'logo_path', 'trial_ends_at', 'created_at']);
    }

    /**
     * @return Collection<int, SubscriptionPayment>
     */
    public function recentPayments(int $limit = 6): Collection
    {
        return SubscriptionPayment::query()
            ->with(['institute:id,name', 'plan:id,name'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function upcomingRenewals(int $days = 30, int $limit = 6): Collection
    {
        $now = CarbonImmutable::now();

        return $this->activeSubscriptionQuery($now)
            ->where('ends_at', '<=', $now->addDays($days))
            ->with(['institute:id,name,code', 'plan:id,name'])
            ->orderBy('ends_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function chart(string $widget): array
    {
        return $this->remember('chart:'.$widget, fn () => match ($widget) {
            'revenue' => $this->revenueChart(),
            'institutes' => $this->instituteGrowthChart(),
            'plans' => $this->planMixChart(),
            'inquiries' => $this->inquiryFunnelChart(),
            'logins' => $this->loginActivityChart(),
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function revenueChart(): array
    {
        [$months, $start] = $this->lastMonths(12);

        $totals = SubscriptionPayment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (SubscriptionPayment $p) => $p->paid_at->format('Y-m'))
            ->map(fn (Collection $rows) => round((float) $rows->sum('amount'), 2));

        $series = $months->keys()->map(fn (string $key) => $totals[$key] ?? 0)->values()->all();

        return [
            'labels' => $months->values()->all(),
            'series' => [['name' => 'Revenue', 'data' => $series]],
            'total' => round((float) array_sum($series), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function instituteGrowthChart(): array
    {
        [$months, $start] = $this->lastMonths(12);

        $newPerMonth = Institute::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->countBy(fn (Institute $i) => $i->created_at->format('Y-m'));

        $running = Institute::query()->where('created_at', '<', $start)->count();
        $newSeries = [];
        $totalSeries = [];

        foreach ($months->keys() as $key) {
            $count = (int) ($newPerMonth[$key] ?? 0);
            $running += $count;
            $newSeries[] = $count;
            $totalSeries[] = $running;
        }

        return [
            'labels' => $months->values()->all(),
            'series' => [
                ['name' => 'New institutes', 'type' => 'column', 'data' => $newSeries],
                ['name' => 'Total institutes', 'type' => 'line', 'data' => $totalSeries],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function planMixChart(): array
    {
        $mix = Institute::query()
            ->whereIn('status', [InstituteStatus::Active->value, InstituteStatus::Trial->value])
            ->with('currentSubscription.plan:id,name')
            ->get(['id', 'status'])
            ->countBy(fn (Institute $i) => $i->currentSubscription?->plan?->name
                ?? ($i->status === InstituteStatus::Trial ? 'Trial (no plan)' : 'No plan'))
            ->sortDesc();

        return [
            'labels' => $mix->keys()->values()->all(),
            'series' => $mix->values()->all(),
            'total' => (int) $mix->sum(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inquiryFunnelChart(): array
    {
        $total = ProjectInquiry::query()->count();

        $demoScheduled = ProjectInquiry::query()
            ->where(fn ($q) => $q->whereNotNull('demo_scheduled_at')
                ->orWhereIn('demo_status', [DemoStatus::Scheduled->value, DemoStatus::Completed->value]))
            ->count();

        $demoCompleted = ProjectInquiry::query()
            ->where(fn ($q) => $q->where('demo_status', DemoStatus::Completed->value)
                ->orWhereIn('status', [ProjectInquiryStatus::DemoCompleted->value, ProjectInquiryStatus::Converted->value]))
            ->count();

        $converted = ProjectInquiry::query()
            ->where('status', ProjectInquiryStatus::Converted->value)
            ->count();

        return [
            'labels' => ['Inquiries', 'Demo scheduled', 'Demo completed', 'Converted'],
            'series' => [['name' => 'Leads', 'data' => [$total, $demoScheduled, $demoCompleted, $converted]]],
            'conversion_rate' => $total > 0 ? round($converted * 100 / $total, 1) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loginActivityChart(): array
    {
        [$days, $start] = $this->lastDays(14);

        $counts = LoginLog::query()
            ->whereIn('event', ['login', 'failed'])
            ->where('logged_at', '>=', $start)
            ->get(['event', 'logged_at'])
            ->groupBy('event')
            ->map(fn (Collection $rows) => $rows->countBy(fn (LoginLog $log) => $log->logged_at->format('Y-m-d')));

        $seriesFor = fn (string $event) => $days->keys()
            ->map(fn (string $day) => (int) ($counts[$event][$day] ?? 0))
            ->values()
            ->all();

        return [
            'labels' => $days->values()->all(),
            'series' => [
                ['name' => 'Successful', 'data' => $seriesFor('login')],
                ['name' => 'Failed', 'data' => $seriesFor('failed')],
            ],
        ];
    }

    private function activeSubscriptionQuery(CarbonImmutable $now): Builder
    {
        return Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->where('ends_at', '>=', $now);
    }

    private function paidRevenueBetween(CarbonImmutable $from, CarbonImmutable $to): float
    {
        return round((float) SubscriptionPayment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount'), 2);
    }

    private function remember(string $key, \Closure $callback): mixed
    {
        return Cache::remember(self::CACHE_PREFIX.$key, self::CACHE_TTL, $callback);
    }
}
