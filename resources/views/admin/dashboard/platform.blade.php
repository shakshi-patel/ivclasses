@extends('admin.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v={{ filemtime(public_path('css/admin-dashboard.css')) }}" />
@endpush

@php
    use App\Enums\InstituteStatus;
    use App\Enums\PaymentStatus;
    use App\Support\Money;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $money = Money::format(...);

    $instituteTone = fn (?InstituteStatus $status) => match ($status) {
        InstituteStatus::Active => 'success',
        InstituteStatus::Trial => 'info',
        InstituteStatus::Suspended => 'warning',
        default => 'error',
    };

    $paymentTone = fn (?PaymentStatus $status) => match ($status) {
        PaymentStatus::Paid => 'success',
        PaymentStatus::Pending => 'warning',
        PaymentStatus::Failed => 'error',
        default => 'muted',
    };

    $inst = $summary['institutes'];
    $rev = $summary['revenue'];
    $subs = $summary['subscriptions'];
    $inq = $summary['inquiries'];
    $pf = $summary['platform'];
@endphp

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y ivd">

        {{-- Hero --}}
        <div class="ivd-row">
            <div class="ivd-hero">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <div>
                        <span class="ivd-hero__chip">
                            <i class="bx bx-calendar"></i> {{ now()->format('l, d M Y') }}
                        </span>
                        <h3 class="ivd-hero__title">{{ $greeting }}, <span>{{ $user?->name ?: 'Admin' }}</span></h3>
                        <p class="ivd-hero__subtitle">
                            Here's how Class Flow is performing across all institutes today.
                        </p>
                        <div class="ivd-hero__actions">
                            <a href="{{ route('admin.institutes.index') }}" class="ivd-btn ivd-btn--gold">
                                <i class="bx bx-buildings"></i> Manage Institutes
                            </a>
                            <a href="{{ route('admin.project-inquiries.index') }}" class="ivd-btn ivd-btn--glass">
                                <i class="bx bx-user-voice"></i> Project Inquiries
                            </a>
                            <a href="{{ route('admin.demo-calendar.index') }}" class="ivd-btn ivd-btn--glass">
                                <i class="bx bx-calendar-event"></i> Demo Calendar
                            </a>
                            <a href="{{ route('admin.plans.index') }}" class="ivd-btn ivd-btn--glass">
                                <i class="bx bx-package"></i> Plans
                            </a>
                        </div>
                    </div>

                    <div class="ivd-hero__pulse">
                        <div class="ivd-pulse">
                            <i class="bx bxs-graduation"></i>
                            <span class="ivd-pulse__value">{{ number_format($pf['students']) }}</span>
                            <span class="ivd-pulse__label">Students</span>
                        </div>
                        <div class="ivd-pulse">
                            <i class="bx bx-chalkboard"></i>
                            <span class="ivd-pulse__value">{{ number_format($pf['teachers']) }}</span>
                            <span class="ivd-pulse__label">Teachers</span>
                        </div>
                        <div class="ivd-pulse">
                            <i class="bx bx-git-branch"></i>
                            <span class="ivd-pulse__value">{{ number_format($pf['branches']) }}</span>
                            <span class="ivd-pulse__label">Branches</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI cards --}}
        <div class="row g-4 ivd-row">
            <div class="col-sm-6 col-xl-3">
                <x-dashboard.stat-card label="Institutes" :value="number_format($inst['total'])" icon="bx-buildings"
                    tone="primary" :href="route('admin.institutes.index')" with-change :change="$inst['change']"
                    :change-label="$inst['new_this_month'] . ' new this month'">
                    <div class="ivd-split w-100 mt-1">
                        <span class="ivd-pill ivd-tone-success">{{ $inst['active'] }} active</span>
                        <span class="ivd-pill ivd-tone-info">{{ $inst['trial'] }} trial</span>
                        @if ($inst['inactive'] > 0)
                            <span class="ivd-pill ivd-tone-error">{{ $inst['inactive'] }} inactive</span>
                        @endif
                    </div>
                </x-dashboard.stat-card>
            </div>

            <div class="col-sm-6 col-xl-3">
                <x-dashboard.stat-card label="Revenue this month" :value="$money($rev['this_month'])" icon="bx-rupee"
                    tone="secondary" :href="route('admin.billing.index')" with-change :change="$rev['change']">
                    <div class="ivd-split w-100 mt-1">
                        <span class="ivd-kpi-inline">This year <strong>{{ $money($rev['year']) }}</strong></span>
                    </div>
                </x-dashboard.stat-card>
            </div>

            <div class="col-sm-6 col-xl-3">
                <x-dashboard.stat-card label="Active subscriptions" :value="number_format($subs['active'])"
                    icon="bx-badge-check" tone="success" :href="route('admin.institutes.index', ['status' => InstituteStatus::Active->value])">
                    @if ($subs['expiring_7d'] > 0)
                        <span class="ivd-pill ivd-tone-warning">{{ $subs['expiring_7d'] }} expiring in 7 days</span>
                    @else
                        <span class="ivd-pill ivd-tone-success">No renewals due this week</span>
                    @endif
                </x-dashboard.stat-card>
            </div>

            <div class="col-sm-6 col-xl-3">
                <x-dashboard.stat-card label="Inquiries this month" :value="number_format($inq['this_month'])"
                    icon="bx-user-voice" tone="info" :href="route('admin.project-inquiries.index')" with-change
                    :change="$inq['change']">
                    <div class="ivd-split w-100 mt-1">
                        <span class="ivd-pill ivd-tone-primary">{{ $inq['upcoming_demos'] }} upcoming demos</span>
                        <span class="ivd-pill ivd-tone-secondary">{{ $inq['conversion_rate'] }}% converted</span>
                    </div>
                </x-dashboard.stat-card>
            </div>
        </div>

        {{-- Revenue + attention --}}
        <div class="row g-4 ivd-row">
            <div class="col-xl-8">
                <x-dashboard.panel title="Revenue" subtitle="Paid subscription payments · last 12 months"
                    icon="bx-line-chart">
                    <x-slot:tools>
                        <span class="ivd-kpi-inline">12-month total <strong data-ivd-total="revenue">—</strong></span>
                    </x-slot:tools>
                    <x-dashboard.chart widget="revenue" :height="310" />
                </x-dashboard.panel>
            </div>

            <div class="col-xl-4">
                <x-dashboard.panel title="Needs attention" subtitle="Items that need action" icon="bx-bell">
                    @if (count($attention))
                        <div class="ivd-attn-list">
                            @foreach ($attention as $item)
                                <x-dashboard.attention-item :title="$item['title']" :text="$item['text']"
                                    :count="$item['count']" :icon="$item['icon']" :tone="$item['tone']"
                                    :href="$item['route'] && Route::has($item['route']) ? route($item['route'], $item['params']) : null" />
                            @endforeach
                        </div>
                    @else
                        <div class="ivd-all-clear">
                            <span class="ivd-all-clear__icon"><i class="bx bx-check-shield"></i></span>
                            <strong>All clear</strong>
                            <span>No renewals, failed payments or pending leads need action right now.</span>
                        </div>
                    @endif
                </x-dashboard.panel>
            </div>
        </div>

        {{-- Growth + plan mix --}}
        <div class="row g-4 ivd-row">
            <div class="col-xl-8">
                <x-dashboard.panel title="Institute growth" subtitle="New sign-ups and total institutes · last 12 months"
                    icon="bx-trending-up" :action-url="route('admin.institutes.index')">
                    <x-dashboard.chart widget="institutes" :height="300" />
                </x-dashboard.panel>
            </div>

            <div class="col-xl-4">
                <x-dashboard.panel title="Plan mix" subtitle="Active and trial institutes by plan" icon="bx-pie-chart-alt-2"
                    :action-url="route('admin.plans.index')" action-label="Plans">
                    <x-dashboard.chart widget="plans" :height="300" />
                </x-dashboard.panel>
            </div>
        </div>

        {{-- Funnel + sign-ins --}}
        <div class="row g-4 ivd-row">
            <div class="col-xl-5">
                <x-dashboard.panel title="Sales funnel" subtitle="Project inquiries · all time" icon="bx-filter-alt">
                    <x-slot:tools>
                        <span class="ivd-kpi-inline">Conversion <strong data-ivd-total="conversion">—</strong></span>
                    </x-slot:tools>
                    <x-dashboard.chart widget="inquiries" :height="280" />
                </x-dashboard.panel>
            </div>

            <div class="col-xl-7">
                <x-dashboard.panel title="Sign-in activity" subtitle="Successful vs failed logins · last 14 days"
                    icon="bx-log-in-circle">
                    <x-dashboard.chart widget="logins" :height="280" />
                </x-dashboard.panel>
            </div>
        </div>

        {{-- Lists --}}
        <div class="row g-4">
            <div class="col-lg-6 col-xl-4">
                <x-dashboard.panel title="Recent institutes" subtitle="Latest sign-ups" icon="bx-buildings"
                    :action-url="route('admin.institutes.index')">
                    @if ($recentInstitutes->isEmpty())
                        <div class="ivd-empty"><i class="bx bx-buildings"></i>No institutes yet.</div>
                    @else
                        <ul class="ivd-list">
                            @foreach ($recentInstitutes as $institute)
                                <li class="ivd-list__item">
                                    <x-dashboard.avatar :name="$institute->name" :src="$institute->logoUrl()" />
                                    <div class="ivd-list__main">
                                        <a href="{{ route('admin.institutes.show', $institute) }}"
                                            class="ivd-list__title">{{ $institute->name }}</a>
                                        <span class="ivd-list__meta">
                                            {{ $institute->code }}
                                            @if ($institute->city) · {{ $institute->city }} @endif
                                            · {{ $institute->created_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    <div class="ivd-list__end">
                                        <span class="ivd-pill ivd-tone-{{ $instituteTone($institute->status) }}">
                                            {{ $institute->status?->label() ?? '—' }}
                                        </span>
                                        <span class="ivd-list__meta">
                                            {{ $institute->currentSubscription?->plan?->name ?? 'No plan' }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dashboard.panel>
            </div>

            <div class="col-lg-6 col-xl-4">
                <x-dashboard.panel title="Latest payments" subtitle="Subscription billing" icon="bx-credit-card"
                    :action-url="route('admin.billing.index')">
                    @if ($recentPayments->isEmpty())
                        <div class="ivd-empty"><i class="bx bx-receipt"></i>No payments recorded yet.</div>
                    @else
                        <ul class="ivd-list">
                            @foreach ($recentPayments as $payment)
                                <li class="ivd-list__item">
                                    <x-dashboard.avatar icon="bx-receipt" />
                                    <div class="ivd-list__main">
                                        <span class="ivd-list__title">{{ $payment->institute?->name ?? 'Unknown institute' }}</span>
                                        <span class="ivd-list__meta">
                                            {{ $payment->plan?->name ?? '—' }}
                                            @if ($payment->billing_cycle) · {{ Str::headline($payment->billing_cycle) }} @endif
                                            · {{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y') }}
                                        </span>
                                    </div>
                                    <div class="ivd-list__end">
                                        <span class="ivd-list__amount">{{ $money($payment->amount, $payment->currency, 2) }}</span>
                                        <span class="ivd-pill ivd-tone-{{ $paymentTone($payment->status) }}">
                                            {{ $payment->status?->label() ?? '—' }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dashboard.panel>
            </div>

            <div class="col-lg-12 col-xl-4">
                <x-dashboard.panel title="Upcoming renewals" subtitle="Subscriptions ending in 30 days" icon="bx-revision"
                    :action-url="route('admin.institutes.index', ['status' => InstituteStatus::Active->value])">
                    @if ($upcomingRenewals->isEmpty())
                        <div class="ivd-empty"><i class="bx bx-calendar-check"></i>No renewals due in the next 30 days.</div>
                    @else
                        <ul class="ivd-list">
                            @foreach ($upcomingRenewals as $subscription)
                                @php
                                    $daysLeft = max(0, (int) ceil(now()->diffInDays($subscription->ends_at, false)));
                                    $renewTone = $daysLeft <= 7 ? 'error' : ($daysLeft <= 15 ? 'warning' : 'primary');
                                @endphp
                                <li class="ivd-list__item">
                                    <x-dashboard.avatar :name="$subscription->institute?->name" />
                                    <div class="ivd-list__main">
                                        @if ($subscription->institute)
                                            <a href="{{ route('admin.institutes.show', $subscription->institute) }}"
                                                class="ivd-list__title">{{ $subscription->institute->name }}</a>
                                        @else
                                            <span class="ivd-list__title">Unknown institute</span>
                                        @endif
                                        <span class="ivd-list__meta">
                                            {{ $subscription->plan?->name ?? '—' }} · ends {{ $subscription->ends_at?->format('d M Y') }}
                                        </span>
                                    </div>
                                    <div class="ivd-list__end ivd-tone-{{ $renewTone }}">
                                        <span class="ivd-days">{{ $daysLeft === 0 ? 'Today' : $daysLeft . ' ' . Str::plural('day', $daysLeft) }}</span>
                                        <span class="ivd-list__meta">left</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-dashboard.panel>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/admin-dashboard.js') }}?v={{ filemtime(public_path('admin-assets/js/admin-dashboard.js')) }}"></script>
@endpush
