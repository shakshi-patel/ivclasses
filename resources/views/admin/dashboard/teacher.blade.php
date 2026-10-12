@extends('admin.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v={{ filemtime(public_path('css/admin-dashboard.css')) }}" />
@endpush

@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $classes = $summary['classes'];
    $me = $summary['me'];
    $actions = $dashboard->quickActions();
    $studentPhoto = fn ($student) => $student?->photo_path ? asset('storage/' . $student->photo_path) : null;
    $rateTone = fn (?float $rate) => $rate === null ? 'muted' : ($rate >= 85 ? 'success' : ($rate >= 70 ? 'warning' : 'error'));

    $meStates = [
        'present' => ['success', 'bx-check-circle', 'Present today'],
        'late' => ['warning', 'bx-time-five', 'Marked late today'],
        'absent' => ['error', 'bx-x-circle', 'Marked absent today'],
    ];
    [$meTone, $meIcon, $meLabel] = $meStates[$me['today']] ?? ['muted', 'bx-time', 'Attendance not marked yet'];

    $kpiCount = count(array_filter([
        $show['kpi_students'],
        isset($summary['attendance']),
        isset($summary['homework']),
        isset($summary['exams']),
        $show['kpi_my_attendance'],
    ]));
    $widgets = $dashboard->allowedWidgets();
    $canShowBatch = $can['batches'] && Route::has('admin.master.batch.show');
    $hasLists = $show['list_exams'] || $show['list_homework'] || $show['list_tasks'] || $show['list_notices'] || $show['list_holidays'];
@endphp

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y ivd">

        {{-- Hero --}}
        <div class="ivd-row">
            <div class="ivd-hero">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="ivd-hero__chip"><i class="bx bx-calendar"></i> {{ now()->format('l, d M Y') }}</span>
                            <span class="ivd-hero__chip"><i class="bx bx-chalkboard"></i> Teacher</span>
                        </div>
                        <h3 class="ivd-hero__title">{{ $greeting }}, <span>{{ $user?->name }}</span></h3>
                        <p class="ivd-hero__subtitle">
                            @if ($classes['batches'] > 0)
                                You teach {{ $classes['batches'] }} {{ Str::plural('batch', $classes['batches']) }}
                                with {{ number_format($classes['students']) }} {{ Str::plural('student', $classes['students']) }}.
                                Here's your day at a glance.
                            @else
                                No batches are assigned to you yet. Your classes will appear here once the institute assigns them.
                            @endif
                        </p>

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
                        </div>
                        @if ($show['my_status'])
                            <div class="ivd-hero__me ivd-tone-{{ $meTone }}">
                                <span class="ivd-hero__me-icon"><i class="bx {{ $meIcon }}"></i></span>
                                <div>
                                    <strong>{{ $meLabel }}</strong>
                                    <span>
                                        @if ($me['check_in_at'])
                                            In {{ $me['check_in_at'] }}
                                            @if ($me['check_out_at'])
                                                · Out {{ $me['check_out_at'] }}
                                            @endif
                                        @else
                                            Your own attendance for today
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endif
                        @if ($show['pulse'])
                            <div class="ivd-hero__pulse" style="--ivd-pulse-cols: 3">
                                @foreach ([
                                    ['bx-group', $classes['batches'], 'My batches'],
                                    ['bxs-graduation', $classes['students'], 'Students'],
                                    ['bx-book', $classes['subjects'], 'Subjects'],
                                ] as [$icon, $value, $label])
                                    <div class="ivd-pulse">
                                        <i class="bx {{ $icon }}"></i>
                                        <span class="ivd-pulse__value">{{ number_format($value) }}</span>
                                        <span class="ivd-pulse__label">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI cards --}}
        @if ($kpiCount > 0)
        <div class="row g-4 ivd-row row-cols-1 row-cols-sm-2 row-cols-xl-{{ $kpiCount }}">
            @if ($show['kpi_students'])
                <div class="col">
                    <x-dashboard.stat-card label="My students" :value="number_format($classes['students'])" icon="bxs-graduation"
                        tone="primary" :href="$can['batches'] ? route('admin.master.batch') : null">
                        <span class="ivd-pill ivd-tone-muted">{{ $classes['batches'] }} {{ Str::plural('batch', $classes['batches']) }}</span>
                        <span class="ivd-pill ivd-tone-info">{{ $classes['subjects'] }} {{ Str::plural('subject', $classes['subjects']) }}</span>
                    </x-dashboard.stat-card>
                </div>
            @endif

            @isset($summary['attendance'])
                @php($a = $summary['attendance'])
                <div class="col">
                    <x-dashboard.stat-card label="Class attendance"
                        :value="$a['is_holiday'] ? 'Holiday' : ($a['rate'] === null ? 'Not marked' : $a['rate'] . '%')"
                        icon="bx-calendar-check" tone="info" :href="route('admin.attendance.student')">
                        <div class="w-100 ivd-tone-{{ $rateTone($a['rate']) }}">
                            <div class="ivd-progress mb-2">
                                <span style="width: {{ $a['batches_total'] ? round($a['batches_marked'] * 100 / $a['batches_total']) : 0 }}%"></span>
                            </div>
                            <div class="ivd-split">
                                <span class="ivd-stat__meta">{{ $a['batches_marked'] }} of {{ $a['batches_total'] }} batches marked</span>
                                @if ($a['absent'] > 0)
                                    <span class="ivd-pill ivd-tone-error">{{ $a['absent'] }} absent</span>
                                @endif
                            </div>
                        </div>
                    </x-dashboard.stat-card>
                </div>
            @endisset

            @isset($summary['homework'])
                @php($h = $summary['homework'])
                <div class="col">
                    <x-dashboard.stat-card label="Open homework" :value="number_format($h['open'])" icon="bx-book-open"
                        tone="secondary" :href="route('admin.homework')">
                        @if ($h['due_today'] > 0)
                            <span class="ivd-pill ivd-tone-warning">{{ $h['due_today'] }} due today</span>
                        @endif
                        <span class="ivd-pill ivd-tone-muted">{{ $h['this_month'] }} given this month</span>
                    </x-dashboard.stat-card>
                </div>
            @endisset

            @isset($summary['exams'])
                @php($e = $summary['exams'])
                <div class="col">
                    <x-dashboard.stat-card label="Upcoming exams" :value="number_format($e['upcoming'])" icon="bx-edit"
                        tone="warning" :href="route('admin.exam')">
                        @if ($e['next_date'])
                            <span class="ivd-stat__meta w-100 text-truncate">
                                Next: {{ $e['next_title'] }} · {{ \Carbon\Carbon::parse($e['next_date'])->format('d M') }}
                            </span>
                        @endif
                        @if ($e['results_pending'] > 0)
                            <span class="ivd-pill ivd-tone-error">{{ $e['results_pending'] }} results pending</span>
                        @endif
                    </x-dashboard.stat-card>
                </div>
            @endisset

            @if ($show['kpi_my_attendance'])
                <div class="col">
                    <x-dashboard.stat-card label="My attendance" :value="($me['present'] + $me['late']) . ' days'"
                        icon="bx-user-check" tone="success">
                        <span class="ivd-stat__meta w-100">Present this month</span>
                        @if ($me['late'] > 0)
                            <span class="ivd-pill ivd-tone-warning">{{ $me['late'] }} late</span>
                        @endif
                        @if ($me['absent'] > 0)
                            <span class="ivd-pill ivd-tone-error">{{ $me['absent'] }} absent</span>
                        @endif
                        @if ($me['late'] === 0 && $me['absent'] === 0)
                            <span class="ivd-pill ivd-tone-success">Perfect record</span>
                        @endif
                    </x-dashboard.stat-card>
                </div>
            @endif
        </div>
        @endif

        {{-- Today's classes + attention --}}
        @if ($show['classes'] || $show['attention'])
        <div class="row g-4 ivd-row">
            @if ($show['classes'])
            <div class="{{ $show['attention'] ? 'col-xl-8' : 'col-12' }}">
                <x-dashboard.panel title="My classes" subtitle="Your batches in order of timing" icon="bx-time-five"
                    :action-url="$can['batches'] ? route('admin.master.batch') : null" action-label="All batches">
                    @forelse ($batches as $batch)
                        @if ($loop->first)<ul class="ivd-timeline">@endif
                        <li class="ivd-class {{ $batch->is_live ? 'is-live' : '' }}">
                            <div class="ivd-class__time">
                                <strong>{{ $batch->start_time ? substr((string) $batch->start_time, 0, 5) : '—' }}</strong>
                                <small>{{ $batch->end_time ? substr((string) $batch->end_time, 0, 5) : '' }}</small>
                            </div>
                            <div class="ivd-class__body">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @if ($canShowBatch)
                                        <a href="{{ route('admin.master.batch.show', $batch) }}" class="ivd-list__title">{{ $batch->name }}</a>
                                    @else
                                        <span class="ivd-list__title">{{ $batch->name }}</span>
                                    @endif
                                    @if ($batch->is_live)
                                        <span class="ivd-live">Live now</span>
                                    @endif
                                    @if ($batch->is_primary)
                                        <span class="ivd-pill ivd-tone-secondary">Class teacher</span>
                                    @endif
                                </div>
                                <span class="ivd-list__meta">
                                    {{ $batch->standard?->name ?? '—' }}
                                    @if ($batch->subjects->isNotEmpty()) · {{ $batch->subjects->pluck('name')->join(', ') }} @endif
                                    · {{ $batch->students_count }} {{ Str::plural('student', (int) $batch->students_count) }}
                                    @if ($batch->branch) · {{ $batch->branch->name }} @endif
                                </span>
                            </div>
                            <div class="ivd-list__end">
                                @if ($can['attendance'])
                                    @if ($batch->is_marked)
                                        <span class="ivd-pill ivd-tone-success">{{ $batch->present_count }} present</span>
                                        @if ($batch->absent_count > 0)
                                            <span class="ivd-list__meta">{{ $batch->absent_count }} absent</span>
                                        @endif
                                    @elseif ($can['attendance_add'])
                                        <a href="{{ route('admin.attendance.student.mark', $batch) }}" class="ivd-chip-btn">
                                            <i class="bx bx-check-square"></i> Mark attendance
                                        </a>
                                    @else
                                        <span class="ivd-pill ivd-tone-warning">Not marked</span>
                                    @endif
                                @endif
                            </div>
                        </li>
                        @if ($loop->last)</ul>@endif
                    @empty
                        <div class="ivd-empty"><i class="bx bx-chalkboard"></i>No batches assigned to you yet.</div>
                    @endforelse
                </x-dashboard.panel>
            </div>
            @endif

            @if ($show['attention'])
            <div class="{{ $show['classes'] ? 'col-xl-4' : 'col-12' }}">
                <x-dashboard.panel title="Needs attention" subtitle="What to act on today" icon="bx-bell">
                    @include('admin.dashboard.shared.attention', [
                        'allClear' => 'Attendance is marked and nothing is pending.',
                    ])
                </x-dashboard.panel>
            </div>
            @endif
        </div>
        @endif

        {{-- Charts --}}
        @if ($widgets)
            <div class="row g-4 ivd-row">
                @if (in_array('attendance_trend', $widgets, true))
                    <div class="{{ count($widgets) > 1 ? 'col-xl-7' : 'col-12' }}">
                        <x-dashboard.panel title="Attendance trend" subtitle="Your batches · last 14 days" icon="bx-calendar-check">
                            <x-slot:tools>
                                <span class="ivd-kpi-inline">Avg. present <strong data-ivd-total="attendance_rate">—</strong></span>
                            </x-slot:tools>
                            <x-dashboard.chart widget="attendance_trend" :height="290" />
                        </x-dashboard.panel>
                    </div>
                @endif
                @if (in_array('batch_attendance', $widgets, true))
                    <div class="{{ count($widgets) > 1 ? 'col-xl-5' : 'col-12' }}">
                        <x-dashboard.panel title="Batch-wise attendance" subtitle="Average present · last 30 days" icon="bx-bar-chart-alt-2">
                            <x-slot:tools>
                                <span class="ivd-kpi-inline">Overall <strong data-ivd-total="batch_attendance_rate">—</strong></span>
                            </x-slot:tools>
                            <x-dashboard.chart widget="batch_attendance" :height="290" />
                        </x-dashboard.panel>
                    </div>
                @endif
            </div>
        @endif

        {{-- Lists --}}
        @if ($hasLists)
        <div class="row g-4">
            @if ($show['list_exams'])
                <div class="col-md-6 col-xl-4">
                    <x-dashboard.panel title="Upcoming exams" subtitle="Next 14 days" icon="bx-edit" :action-url="route('admin.exam')">
                        @include('admin.dashboard.shared.exam-list', ['exams' => $dashboard->upcomingExams()])
                    </x-dashboard.panel>
                </div>
            @endif

            @if ($show['list_homework'])
                <div class="col-md-6 col-xl-4">
                    <x-dashboard.panel title="Recent homework" subtitle="What you've assigned lately" icon="bx-book-open"
                        :action-url="route('admin.homework')">
                        @forelse ($dashboard->recentHomework() as $homework)
                            @if ($loop->first)<ul class="ivd-list">@endif
                            @php($dueTone = ! $homework->due_date ? 'muted' : ($homework->due_date->isToday() ? 'warning' : ($homework->due_date->isPast() ? 'muted' : 'info')))
                            <li class="ivd-list__item">
                                <span class="ivd-time ivd-tone-secondary"><i class="bx bx-book-bookmark"></i></span>
                                <div class="ivd-list__main">
                                    <span class="ivd-list__title">{{ $homework->title }}</span>
                                    <span class="ivd-list__meta">
                                        {{ $homework->batch?->name ?? '—' }}
                                        @if ($homework->subject) · {{ $homework->subject->name }} @endif
                                        · Given {{ $homework->homework_date?->format('d M') }}
                                    </span>
                                </div>
                                <div class="ivd-list__end">
                                    <span class="ivd-pill ivd-tone-{{ $dueTone }}">
                                        @if (! $homework->due_date)
                                            No due date
                                        @elseif ($homework->due_date->isToday())
                                            Due today
                                        @elseif ($homework->due_date->isPast())
                                            Closed
                                        @else
                                            Due {{ $homework->due_date->format('d M') }}
                                        @endif
                                    </span>
                                </div>
                            </li>
                            @if ($loop->last)</ul>@endif
                        @empty
                            <div class="ivd-empty"><i class="bx bx-book-open"></i>No homework assigned yet.</div>
                        @endforelse
                    </x-dashboard.panel>
                </div>
            @endif

            @if ($show['list_tasks'])
                <div class="col-md-6 col-xl-4">
                    <x-dashboard.panel title="My tasks" subtitle="Pending to-dos" icon="bx-task" :action-url="route('admin.to_do_task')">
                        @forelse ($dashboard->tasks() as $task)
                            @if ($loop->first)<ul class="ivd-list">@endif
                            @php($lines = $task->taskLines())
                            @php($late = $task->due_date?->isPast() && ! $task->due_date?->isToday())
                            <li class="ivd-list__item">
                                <span class="ivd-time {{ $late ? 'ivd-tone-error' : 'ivd-tone-warning' }}"><i class="bx bx-checkbox"></i></span>
                                <div class="ivd-list__main">
                                    <span class="ivd-list__title">{{ $lines[0] ?? $task->title }}</span>
                                    <span class="ivd-list__meta">
                                        {{ count($lines) }} {{ Str::plural('item', count($lines)) }}
                                    </span>
                                </div>
                                <div class="ivd-list__end">
                                    @if ($task->due_date)
                                        <span class="ivd-pill {{ $late ? 'ivd-tone-error' : ($task->due_date->isToday() ? 'ivd-tone-warning' : 'ivd-tone-info') }}">
                                            {{ $task->due_date->isToday() ? 'Today' : $task->due_date->format('d M') }}
                                        </span>
                                    @endif
                                </div>
                            </li>
                            @if ($loop->last)</ul>@endif
                        @empty
                            <div class="ivd-empty"><i class="bx bx-check-double"></i>No pending tasks.</div>
                        @endforelse
                    </x-dashboard.panel>
                </div>
            @endif

            @if ($show['list_notices'])
                <div class="col-md-6 col-xl-4">
                    <x-dashboard.panel title="Notice board" subtitle="Notices for teachers" icon="bx-news" :action-url="route('admin.notice')">
                        @include('admin.dashboard.shared.notice-list', ['notices' => $dashboard->notices()])
                    </x-dashboard.panel>
                </div>
            @endif

            @if ($show['list_holidays'])
            <div class="col-md-6 col-xl-4">
                <x-dashboard.panel :title="$can['holidays'] ? 'Holidays & birthdays' : 'Birthdays today'"
                    :subtitle="$can['holidays'] ? 'Upcoming breaks and your students\' birthdays' : 'Students in your batches'"
                    icon="bx-party" :action-url="$can['holidays'] ? route('admin.holiday') : null" action-label="All holidays">
                    @if ($can['holidays'])
                        @include('admin.dashboard.shared.holiday-list', ['holidays' => $dashboard->holidays()])
                        <p class="ivd-section-label">Birthdays today</p>
                    @endif
                    @forelse ($dashboard->birthdaysToday() as $student)
                        @if ($loop->first)<ul class="ivd-list">@endif
                        <li class="ivd-list__item">
                            <x-dashboard.avatar :name="$student->name" :src="$studentPhoto($student)" />
                            <div class="ivd-list__main">
                                <span class="ivd-list__title">{{ $student->name }}</span>
                                <span class="ivd-list__meta">{{ $student->standard?->name ?? '—' }} · turns {{ $student->dob?->age }}</span>
                            </div>
                            <div class="ivd-list__end"><i class="bx bx-cake ivd-days ivd-tone-secondary fs-4"></i></div>
                        </li>
                        @if ($loop->last)</ul>@endif
                    @empty
                        <div class="ivd-empty py-3"><i class="bx bx-cake"></i>No birthdays today.</div>
                    @endforelse
                </x-dashboard.panel>
            </div>
            @endif
        </div>
        @endif

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/admin-dashboard.js') }}?v={{ filemtime(public_path('admin-assets/js/admin-dashboard.js')) }}"></script>
@endpush
