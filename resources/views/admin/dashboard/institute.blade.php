@extends('admin.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v={{ filemtime(public_path('css/admin-dashboard.css')) }}" />
@endpush

@php
    use App\Support\Money;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $money = Money::format(...);

    $persona = $dashboard->persona();
    $branchId = $dashboard->selectedBranchId();
    $branchParams = array_filter(['branch_id' => $branchId]);
    $chartParams = ['branch_id' => $branchId];
    $pulse = $summary['pulse'] ?? [];
    $kpis = $dashboard->kpis($summary);
    $actions = $dashboard->quickActions();

    $inquiryTone = fn (?string $status) => match ($status) {
        'converted' => 'success',
        'follow_up' => 'warning',
        default => 'info',
    };
    $rateTone = fn (?float $rate) => $rate === null ? 'muted' : ($rate >= 85 ? 'success' : ($rate >= 70 ? 'warning' : 'error'));
    $studentPhoto = fn ($student) => $student?->photo_path ? asset('storage/' . $student->photo_path) : null;
    $onboarding = $onboarding ?? [
        'blocking' => false,
        'waiting' => false,
        'reminders' => [],
        'current' => null,
        'title' => '',
        'intro' => '',
        'done_required' => 0,
        'total_required' => 0,
    ];
@endphp

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y ivd">

        @if (! empty($onboarding['blocking']) && ! empty($onboarding['current']))
            @php $step = $onboarding['current']; @endphp
            <div class="ivd-setup">
                <div class="ivd-setup__kicker">
                    <i class="bx bx-flag"></i>
                    First-time setup
                    @if (($onboarding['total_required'] ?? 0) > 0)
                        <span>Step {{ $step['step_number'] ?? 1 }} of {{ $onboarding['total_required'] }}</span>
                    @endif
                </div>
                <h3 class="ivd-setup__title">{{ $onboarding['title'] }}</h3>
                @if ($onboarding['intro'])
                    <p class="ivd-setup__intro">{{ $onboarding['intro'] }}</p>
                @endif
                @if (($onboarding['total_required'] ?? 0) > 0)
                    <div class="ivd-setup__bar" role="progressbar"
                        aria-valuenow="{{ max(0, ($step['step_number'] ?? 1) - 1) }}"
                        aria-valuemin="0"
                        aria-valuemax="{{ $onboarding['total_required'] }}">
                        <span style="width: {{ (int) round((max(0, ($step['step_number'] ?? 1) - 1) / max(1, $onboarding['total_required'])) * 100) }}%"></span>
                    </div>
                @endif
                <div class="ivd-setup__step">
                    <div class="ivd-setup__step-label">{{ ! empty($step['already']) ? 'Already added' : 'Compulsory' }}</div>
                    <h4>{{ $step['title'] }}</h4>
                    @if (! empty($step['description']))
                        <p>{{ $step['description'] }}</p>
                    @endif
                    @if (! empty($step['already_note']))
                        <p>{{ $step['already_note'] }}</p>
                    @endif
                    <div class="ivd-setup__actions">
                        <a href="{{ $step['go_url'] }}" class="ivd-btn ivd-btn--gold">
                            <i class="bx bx-right-arrow-alt"></i> {{ ! empty($step['already']) ? 'Add another' : 'Continue' }}
                        </a>
                        @if (! empty($step['can_skip']))
                            <form method="POST" action="{{ route('onboarding.skip', $step['id']) }}">
                                @csrf
                                <button type="submit" class="ivd-btn ivd-btn--ghost">Skip</button>
                            </form>
                        @endif
                    </div>
                </div>
                <p class="ivd-setup__note">
                    @if (! empty($step['already']))
                        Skip moves you to the next step. Closing the browser brings you back here until you skip.
                    @else
                        This step finishes when the record is saved. Closing the browser brings you back here until it is done.
                    @endif
                </p>
            </div>
        @else
            @if (! empty($onboarding['waiting']))
                <div class="ivd-setup-note">
                    <i class="bx bx-info-circle"></i>
                    <span>Institute setup is still in progress. Ask the institute admin to finish the required steps.</span>
                </div>
            @endif

            @if (! empty($onboarding['reminders']))
                <div class="ivd-row">
                    <div class="ivd-reminders">
                        <div class="ivd-reminders__head">
                            <strong>You can add these later</strong>
                            <span>The dashboard is open. These optional steps stay here until the record exists, or you dismiss them.</span>
                        </div>
                        <div class="row g-3">
                            @foreach ($onboarding['reminders'] as $reminder)
                                <div class="col-md-6 col-xl-4">
                                    <div class="ivd-reminder">
                                        <h5>{{ $reminder['title'] }}</h5>
                                        @if (! empty($reminder['description']))
                                            <p>{{ $reminder['description'] }}</p>
                                        @endif
                                        <div class="ivd-reminder__actions">
                                            <a href="{{ $reminder['go_url'] }}" class="ivd-btn ivd-btn--gold">Set up</a>
                                            <form method="POST" action="{{ route('onboarding.skip', $reminder['id']) }}">
                                                @csrf
                                                <button type="submit" class="ivd-btn ivd-btn--ghost">Dismiss</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        {{-- Hero --}}
        <div class="ivd-row">
            <div class="ivd-hero">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="ivd-hero__chip">
                                <i class="bx bx-calendar"></i> {{ now()->format('l, d M Y') }}
                            </span>
                            @if ($persona->label())
                                <span class="ivd-hero__chip">
                                    <i class="bx {{ $persona->icon() }}"></i> {{ $persona->label() }}
                                </span>
                            @endif
                            @if ($dashboard->selectedBranchName())
                                <span class="ivd-hero__chip">
                                    <i class="bx bx-git-branch"></i> {{ $dashboard->selectedBranchName() }}
                                </span>
                            @endif
                        </div>
                        <h3 class="ivd-hero__title">{{ $greeting }}, <span>{{ $user?->name }}</span></h3>
                        <p class="ivd-hero__subtitle">{{ $persona->subtitle($dashboard->placeName()) }}</p>

                        @if ($actions)
                            <div class="ivd-hero__actions">
                                @foreach ($actions as $i => $action)
                                    <a href="{{ route($action['route']) }}" class="ivd-btn {{ $i === 0 ? 'ivd-btn--gold' : 'ivd-btn--glass' }}">
                                        <i class="bx {{ $action['icon'] }}"></i> {{ $action['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="ivd-hero__side">
                        <div class="ivd-hero__toolbar">
                            <span class="d-none" data-module-help-slot data-module-help-btn-class="ivd-btn ivd-btn--glass"></span>
                            @if ($plan)
                                <a href="{{ route('admin.billing.index') }}" class="ivd-plan-chip">
                                    <i class="bx bx-crown"></i>
                                    {{ $plan['name'] }}
                                    @if ($plan['days_left'] !== null)
                                        · {{ $plan['days_left'] }} {{ Str::plural('day', $plan['days_left']) }} left
                                    @endif
                                </a>
                            @endif
                            @if ($dashboard->branchOptions()->count() > 1)
                                <form method="GET" action="{{ route('dashboard') }}">
                                    <select name="branch_id" class="ivd-hero__select" onchange="this.form.submit()"
                                        aria-label="Filter by branch">
                                        <option value="">{{ $user?->isBranchManager() ? 'All my branches' : 'All branches' }}</option>
                                        @foreach ($dashboard->branchOptions() as $branch)
                                            <option value="{{ $branch->id }}" @selected($branchId === (int) $branch->id)>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                        </div>

                        @if ($pulse)
                            <div class="ivd-hero__pulse" style="--ivd-pulse-cols: {{ count($pulse) }}">
                                @foreach ([
                                    'branches' => ['bx-git-branch', 'Branches'],
                                    'batches' => ['bx-group', 'Active batches'],
                                    'teachers' => ['bx-chalkboard', 'Teachers'],
                                ] as $key => [$icon, $label])
                                    @isset($pulse[$key])
                                        <div class="ivd-pulse">
                                            <i class="bx {{ $icon }}"></i>
                                            <span class="ivd-pulse__value">{{ number_format($pulse[$key]) }}</span>
                                            <span class="ivd-pulse__label">{{ $label }}</span>
                                        </div>
                                    @endisset
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI cards --}}
        @if ($kpis)
            <div class="row g-4 ivd-row row-cols-1 row-cols-sm-2 row-cols-xl-{{ count($kpis) }}">
                @foreach ($kpis as $kpi)
                    <div class="col">
                        @include('admin.dashboard.kpis.' . $kpi, ['data' => $summary[$kpi]])
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Persona rows --}}
        @foreach ($dashboard->layoutRows() as $row)
            <div class="row g-4 ivd-row">
                @foreach ($row as $block => $col)
                    <div class="{{ $col }}">
                        @include('admin.dashboard.blocks.' . $block, ['wide' => $col === 'col-12'])
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- Lists --}}
        @if ($lists = $dashboard->listBlocks())
            <div class="row g-4">
                @foreach ($lists as $block)
                    <div class="col-md-6 col-xl-4">
                        @include('admin.dashboard.blocks.' . $block, ['wide' => false])
                    </div>
                @endforeach
            </div>
        @endif

        @endif

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/admin-dashboard.js') }}?v={{ filemtime(public_path('admin-assets/js/admin-dashboard.js')) }}"></script>
@endpush
