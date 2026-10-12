@extends('admin.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v={{ filemtime(public_path('css/admin-dashboard.css')) }}" />
@endpush

@php
    use App\Models\LeaveRequest;
    use App\Support\Money;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $money = Money::format(...);

    $children = $dashboard->children();
    $child = $dashboard->child();
    $childParams = $child ? ['student_id' => $child->id] : [];
    $photo = fn ($student) => $student?->photo_path ? asset('storage/' . $student->photo_path) : null;
    $rateTone = fn (?float $rate) => $rate === null ? 'muted' : ($rate >= 85 ? 'success' : ($rate >= 70 ? 'warning' : 'error'));
    $firstName = $child ? Str::of($child->name)->before(' ')->toString() : null;

    $todayStates = [
        'present' => ['success', 'Present today'],
        'late' => ['warning', 'Late today'],
        'absent' => ['error', 'Absent today'],
    ];
    $leaveTones = [
        LeaveRequest::STATUS_APPROVED => 'success',
        LeaveRequest::STATUS_REJECTED => 'error',
        LeaveRequest::STATUS_PENDING => 'warning',
    ];

    $kpiCount = count(array_filter([
        $show['kpi_attendance'],
        $show['kpi_fees'],
        isset($summary['exams']),
        isset($summary['homework']),
        isset($summary['results']),
    ]));
    $widgets = $dashboard->allowedWidgets();
    $hasAttendanceChart = in_array('child_attendance', $widgets, true);
    $hasResultsChart = in_array('child_results', $widgets, true);
    $hasLists = $show['list_homework'] || $show['list_exams'] || $show['list_results'] || $show['list_fees']
        || $show['list_notices'] || $show['list_holidays'];
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
                            <span class="ivd-hero__chip"><i class="bx bx-home-heart"></i> Parent</span>
                        </div>
                        <h3 class="ivd-hero__title">{{ $greeting }}, <span>{{ $user?->name }}</span></h3>
                        <p class="ivd-hero__subtitle">
                            @if ($child)
                                Here's how {{ $firstName }} is doing: attendance, homework, exams and fees in one place.
                            @else
                                Your child's progress will appear here once the institute links their admission to your account.
                            @endif
                        </p>

                        @if ($child && ($actions = $dashboard->quickActions()))
                            <div class="ivd-hero__actions">
                                @foreach ($actions as $i => $action)
                                    <a href="{{ route($action['route'], $action['params']) }}"
                                        class="ivd-btn {{ $i === 0 ? 'ivd-btn--gold' : 'ivd-btn--glass' }}">
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
                        @if ($child)
                            <div class="ivd-hero__child">
                                <span class="ivd-hero__child-photo">
                                    @if ($photo($child))
                                        <img src="{{ $photo($child) }}" alt="{{ $child->name }}">
                                    @else
                                        {{ Str::upper(Str::substr($child->name, 0, 1)) }}
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <strong>{{ $child->name }}</strong>
                                    <span>
                                        {{ $child->standard?->name ?? 'Standard not set' }}
                                        @if ($child->branch) · {{ $child->branch->name }} @endif
                                    </span>
                                    @if ($child->admission_no)
                                        <small>Admission no. {{ $child->admission_no }}</small>
                                    @endif
                                </div>
                                @if ($show['kpi_attendance'])
                                    @php
                                        [$tTone, $tLabel] = $todayStates[$summary['attendance']['today']] ?? ['muted', 'Not marked today'];
                                    @endphp
                                    <span class="ivd-pill ivd-tone-{{ $tTone }} ms-auto">{{ $tLabel }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if (! $child)
            <div class="ivd-panel">
                <div class="ivd-empty py-5">
                    <i class="bx bx-user-pin"></i>
                    No active student is linked to your account yet. Please contact the institute office.
                </div>
            </div>
        @else
            {{-- Child switcher --}}
            @if ($children->count() > 1)
                @php($stats = $dashboard->childStats())
                <div class="ivd-row ivd-kids">
                    @foreach ($children as $kid)
                        @php($s = $stats[$kid->id] ?? ['attendance' => null, 'due' => 0])
                        <a href="{{ route('dashboard', ['student_id' => $kid->id]) }}"
                            class="ivd-kid {{ $kid->id === $child->id ? 'is-active' : '' }}">
                            <x-dashboard.avatar :name="$kid->name" :src="$photo($kid)" />
                            <div class="ivd-kid__main">
                                <strong>{{ $kid->name }}</strong>
                                <span>{{ $kid->standard?->name ?? '—' }}</span>
                            </div>
                            <div class="ivd-kid__stats">
                                @if ($show['kpi_attendance'])
                                    <span class="ivd-pill ivd-tone-{{ $rateTone($s['attendance']) }}">
                                        {{ $s['attendance'] === null ? '—' : $s['attendance'] . '%' }}
                                    </span>
                                @endif
                                @if ($show['kpi_fees'] && $s['due'] > 0)
                                    <span class="ivd-pill ivd-tone-warning">{{ $money($s['due']) }} due</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- KPI cards --}}
            @if ($kpiCount > 0)
            <div class="row g-4 ivd-row row-cols-1 row-cols-sm-2 row-cols-xl-{{ $kpiCount }}">
                @if ($show['kpi_attendance'])
                @php($a = $summary['attendance'])
                <div class="col">
                    <x-dashboard.stat-card label="Attendance this month" :value="$a['rate'] === null ? 'No data' : $a['rate'] . '%'"
                        icon="bx-calendar-check" tone="info">
                        <div class="w-100 ivd-tone-{{ $rateTone($a['rate']) }}">
                            <div class="ivd-progress mb-2"><span style="width: {{ $a['rate'] ?? 0 }}%"></span></div>
                            <div class="ivd-split">
                                <span class="ivd-stat__meta">{{ $a['present'] + $a['late'] }} of {{ $a['marked'] }} days</span>
                                @if ($a['absent'] > 0)
                                    <span class="ivd-pill ivd-tone-error">{{ $a['absent'] }} absent</span>
                                @endif
                            </div>
                        </div>
                    </x-dashboard.stat-card>
                </div>
                @endif

                @if ($show['kpi_fees'])
                @php($f = $summary['fees'])
                <div class="col">
                    <x-dashboard.stat-card label="Fees due" :value="$money($f['due'])" icon="bx-wallet"
                        :tone="$f['overdue'] > 0 ? 'error' : ($f['due'] > 0 ? 'warning' : 'success')">
                        @if ($f['due'] <= 0)
                            <span class="ivd-pill ivd-tone-success">All fees paid</span>
                        @elseif ($f['overdue'] > 0)
                            <span class="ivd-pill ivd-tone-error">{{ $f['overdue'] }} overdue</span>
                        @elseif ($f['next_due_date'])
                            <span class="ivd-pill ivd-tone-warning">Next due {{ \Carbon\Carbon::parse($f['next_due_date'])->format('d M') }}</span>
                        @endif
                        <span class="ivd-stat__meta">{{ $money($f['paid']) }} paid so far</span>
                    </x-dashboard.stat-card>
                </div>
                @endif

                @isset($summary['exams'])
                    @php($e = $summary['exams'])
                    <div class="col">
                        <x-dashboard.stat-card label="Upcoming exams" :value="number_format($e['upcoming'])" icon="bx-edit"
                            tone="warning" :href="route('admin.exam', $childParams)">
                            @if ($e['next_date'])
                                <span class="ivd-stat__meta w-100 text-truncate">
                                    Next: {{ $e['next_title'] }} · {{ \Carbon\Carbon::parse($e['next_date'])->format('d M') }}
                                </span>
                            @else
                                <span class="ivd-stat__meta">Nothing scheduled in the next 30 days</span>
                            @endif
                        </x-dashboard.stat-card>
                    </div>
                @endisset

                @isset($summary['homework'])
                    @php($h = $summary['homework'])
                    <div class="col">
                        <x-dashboard.stat-card label="Homework this week" :value="number_format($h['due_this_week'])"
                            icon="bx-book-open" tone="secondary" :href="route('admin.homework', $childParams)">
                            @if ($h['due_today'] > 0)
                                <span class="ivd-pill ivd-tone-warning">{{ $h['due_today'] }} due today</span>
                            @else
                                <span class="ivd-stat__meta">Nothing due today</span>
                            @endif
                        </x-dashboard.stat-card>
                    </div>
                @endisset

                @isset($summary['results'])
                    @php($r = $summary['results'])
                    <div class="col">
                        <x-dashboard.stat-card label="Average score"
                            :value="$r['average_percent'] === null ? 'No results' : $r['average_percent'] . '%'"
                            icon="bx-trophy" tone="success" :href="route('admin.result_announcement', $childParams)">
                            @if ($r['latest_percent'] !== null)
                                <span class="ivd-stat__meta w-100 text-truncate">
                                    Latest: {{ $r['latest_title'] }} · <strong>{{ $r['latest_percent'] }}%</strong>
                                </span>
                            @else
                                <span class="ivd-stat__meta">Across published exams</span>
                            @endif
                        </x-dashboard.stat-card>
                    </div>
                @endisset
            </div>
            @endif

            {{-- Attendance chart + attention --}}
            @if ($hasAttendanceChart || $show['attention'])
            <div class="row g-4 ivd-row">
                @if ($hasAttendanceChart)
                <div class="{{ $show['attention'] ? 'col-xl-8' : 'col-12' }}">
                    <x-dashboard.panel title="Attendance" :subtitle="$firstName . '\'s monthly attendance · last 6 months'"
                        icon="bx-calendar-check">
                        <x-dashboard.chart widget="child_attendance" :height="290" :params="$childParams" />
                    </x-dashboard.panel>
                </div>
                @endif
                @if ($show['attention'])
                <div class="{{ $hasAttendanceChart ? 'col-xl-4' : 'col-12' }}">
                    <x-dashboard.panel title="Needs attention" subtitle="Things to look at today" icon="bx-bell">
                        @include('admin.dashboard.shared.attention', [
                            'allClear' => 'No absences, overdue fees or pending homework today.',
                        ])
                    </x-dashboard.panel>
                </div>
                @endif
            </div>
            @endif

            {{-- Classes + results chart --}}
            @if ($show['classes'] || $hasResultsChart)
            <div class="row g-4 ivd-row">
                @if ($show['classes'])
                <div class="{{ $hasResultsChart ? 'col-xl-5' : 'col-12' }}">
                    <x-dashboard.panel title="Classes" subtitle="Batches, timings and teachers" icon="bx-chalkboard">
                        @forelse ($dashboard->enrollments() as $enrollment)
                            @if ($loop->first)<ul class="ivd-list">@endif
                            @php($teacher = $enrollment->batch->teachers->first())
                            <li class="ivd-list__item">
                                <span class="ivd-time ivd-tone-primary"><i class="bx bx-group"></i></span>
                                <div class="ivd-list__main">
                                    <span class="ivd-list__title">
                                        {{ $enrollment->batch->name }}
                                        @if ($enrollment->subject) · {{ $enrollment->subject->name }} @endif
                                    </span>
                                    <span class="ivd-list__meta">
                                        {{ $enrollment->batch->timingLabel() }}
                                        @if ($teacher) · {{ $teacher->name }} @endif
                                    </span>
                                </div>
                                @if ($enrollment->roll_no)
                                    <div class="ivd-list__end"><span class="ivd-pill ivd-tone-muted">Roll {{ $enrollment->roll_no }}</span></div>
                                @endif
                            </li>
                            @if ($loop->last)</ul>@endif
                        @empty
                            <div class="ivd-empty"><i class="bx bx-chalkboard"></i>Not enrolled in any batch yet.</div>
                        @endforelse
                    </x-dashboard.panel>
                </div>
                @endif

                @if ($hasResultsChart)
                    <div class="{{ $show['classes'] ? 'col-xl-7' : 'col-12' }}">
                        <x-dashboard.panel title="Performance" subtitle="Score in recent published exams" icon="bx-line-chart"
                            :action-url="route('admin.result_announcement', $childParams)">
                            <x-slot:tools>
                                <span class="ivd-kpi-inline">Average <strong data-ivd-total="child_average">—</strong></span>
                            </x-slot:tools>
                            <x-dashboard.chart widget="child_results" :height="290" :params="$childParams" />
                        </x-dashboard.panel>
                    </div>
                @endif
            </div>
            @endif

            {{-- Lists --}}
            @if ($hasLists)
            <div class="row g-4">
                @if ($show['list_homework'])
                    <div class="col-md-6 col-xl-4">
                        <x-dashboard.panel title="Homework" subtitle="Open assignments, soonest first" icon="bx-book-open"
                            :action-url="route('admin.homework', $childParams)">
                            @forelse ($dashboard->homework() as $homework)
                                @if ($loop->first)<ul class="ivd-list">@endif
                                @php($dueToday = $homework->due_date?->isToday())
                                <li class="ivd-list__item">
                                    <span class="ivd-time {{ $dueToday ? 'ivd-tone-warning' : 'ivd-tone-secondary' }}"><i class="bx bx-book-bookmark"></i></span>
                                    <div class="ivd-list__main">
                                        <span class="ivd-list__title">{{ $homework->title }}</span>
                                        <span class="ivd-list__meta">
                                            {{ $homework->subject?->name ?? 'General' }}
                                            @if ($homework->teacher) · {{ $homework->teacher->name }} @endif
                                        </span>
                                    </div>
                                    <div class="ivd-list__end">
                                        <span class="ivd-pill {{ $dueToday ? 'ivd-tone-warning' : 'ivd-tone-info' }}">
                                            {{ ! $homework->due_date ? 'No due date' : ($dueToday ? 'Due today' : 'Due ' . $homework->due_date->format('d M')) }}
                                        </span>
                                    </div>
                                </li>
                                @if ($loop->last)</ul>@endif
                            @empty
                                <div class="ivd-empty"><i class="bx bx-happy"></i>No open homework.</div>
                            @endforelse
                        </x-dashboard.panel>
                    </div>
                @endif

                @if ($show['list_exams'])
                    <div class="col-md-6 col-xl-4">
                        <x-dashboard.panel title="Upcoming exams" subtitle="Prepare in advance" icon="bx-edit"
                            :action-url="route('admin.exam', $childParams)">
                            @include('admin.dashboard.shared.exam-list', [
                                'exams' => $dashboard->upcomingExams(),
                                'empty' => 'No upcoming exams.',
                            ])
                        </x-dashboard.panel>
                    </div>
                @endif

                @if ($show['list_results'])
                    <div class="col-md-6 col-xl-4">
                        <x-dashboard.panel title="Recent results" subtitle="Marks in published exams" icon="bx-trophy"
                            :action-url="route('admin.result_announcement', $childParams)">
                            @forelse ($dashboard->publishedResults() as $result)
                                @if ($loop->first)<ul class="ivd-list">@endif
                                <li class="ivd-list__item">
                                    <span class="ivd-time ivd-tone-{{ $rateTone($result->percent) }}"><i class="bx bx-medal"></i></span>
                                    <div class="ivd-list__main">
                                        <span class="ivd-list__title">{{ $result->exam?->title ?? 'Exam' }}</span>
                                        <span class="ivd-list__meta">
                                            {{ $result->exam?->subject?->name ?? '—' }} · {{ $result->exam?->exam_date?->format('d M Y') }}
                                        </span>
                                    </div>
                                    <div class="ivd-list__end">
                                        @if ($result->is_attempted)
                                            <span class="ivd-list__amount">
                                                {{ rtrim(rtrim((string) $result->marks_obtained, '0'), '.') }}/{{ rtrim(rtrim((string) $result->exam?->total_marks, '0'), '.') }}
                                            </span>
                                            <span class="ivd-pill ivd-tone-{{ $rateTone($result->percent) }}">
                                                {{ $result->grade ?: ($result->percent !== null ? $result->percent . '%' : '—') }}
                                            </span>
                                        @else
                                            <span class="ivd-pill ivd-tone-error">{{ Str::headline($result->attendance_status ?: 'absent') }}</span>
                                        @endif
                                    </div>
                                </li>
                                @if ($loop->last)</ul>@endif
                            @empty
                                <div class="ivd-empty"><i class="bx bx-trophy"></i>No results published yet.</div>
                            @endforelse
                        </x-dashboard.panel>
                    </div>
                @endif

                @if ($show['list_fees'])
                <div class="col-md-6 col-xl-4">
                    <x-dashboard.panel title="Fees" subtitle="Dues and recent payments" icon="bx-receipt">
                        @php($dueInvoices = $dashboard->dueInvoices())
                        @if ($dueInvoices->isNotEmpty())
                            <ul class="ivd-list">
                                @foreach ($dueInvoices as $invoice)
                                    @php($late = $invoice->due_date?->isPast() && ! $invoice->due_date?->isToday())
                                    <li class="ivd-list__item">
                                        <span class="ivd-time {{ $late ? 'ivd-tone-error' : 'ivd-tone-warning' }}"><i class="bx bx-wallet"></i></span>
                                        <div class="ivd-list__main">
                                            <span class="ivd-list__title">{{ $invoice->title ?: 'Fee instalment' }}</span>
                                            <span class="ivd-list__meta">
                                                {{ $money($invoice->paid_amount) }} paid of {{ $money($invoice->net_amount) }}
                                            </span>
                                        </div>
                                        <div class="ivd-list__end">
                                            <span class="ivd-list__amount">{{ $money($invoice->due_amount) }}</span>
                                            @if ($invoice->due_date)
                                                <span class="ivd-pill {{ $late ? 'ivd-tone-error' : 'ivd-tone-warning' }}">
                                                    {{ $late ? 'Overdue' : 'Due ' . $invoice->due_date->format('d M') }}
                                                </span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="ivd-empty py-3"><i class="bx bx-check-shield"></i>No pending fees.</div>
                        @endif

                        <p class="ivd-section-label">Recent payments</p>
                        @forelse ($dashboard->payments() as $payment)
                            @if ($loop->first)<ul class="ivd-list">@endif
                            <li class="ivd-list__item">
                                <span class="ivd-time ivd-tone-success"><i class="bx bx-check"></i></span>
                                <div class="ivd-list__main">
                                    <span class="ivd-list__title">{{ $payment->receipt_no ?: 'Receipt #' . $payment->id }}</span>
                                    <span class="ivd-list__meta">
                                        {{ Str::headline($payment->payment_method ?: 'cash') }} · {{ $payment->payment_date?->format('d M Y') }}
                                    </span>
                                </div>
                                <div class="ivd-list__end"><span class="ivd-list__amount">{{ $money($payment->amount) }}</span></div>
                            </li>
                            @if ($loop->last)</ul>@endif
                        @empty
                            <div class="ivd-empty py-3"><i class="bx bx-receipt"></i>No payments yet.</div>
                        @endforelse
                    </x-dashboard.panel>
                </div>
                @endif

                @if ($show['list_notices'])
                    <div class="col-md-6 col-xl-4">
                        <x-dashboard.panel title="Notice board" subtitle="Updates from the institute" icon="bx-news"
                            :action-url="route('admin.notice')">
                            @include('admin.dashboard.shared.notice-list', ['notices' => $dashboard->notices()])
                        </x-dashboard.panel>
                    </div>
                @endif

                @if ($show['list_holidays'])
                    <div class="col-md-6 col-xl-4">
                        <x-dashboard.panel :title="$can['holidays'] ? 'Holidays & leave' : 'Leave requests'"
                            :subtitle="$can['holidays'] ? 'Upcoming breaks and your leave requests' : 'Status of your requests'"
                            icon="bx-calendar-event"
                            :action-url="$can['leave'] ? route('admin.student_leave_request') : route('admin.holiday')"
                            :action-label="$can['leave'] ? 'Leave requests' : 'All holidays'">
                            @if ($can['holidays'])
                                @include('admin.dashboard.shared.holiday-list', ['holidays' => $dashboard->holidays()])
                            @endif

                            @if ($can['leave'])
                                @if ($can['holidays'])
                                    <p class="ivd-section-label">Leave requests</p>
                                @endif
                                @forelse ($dashboard->leaves() as $leave)
                                    @if ($loop->first)<ul class="ivd-list">@endif
                                    <li class="ivd-list__item">
                                        <span class="ivd-date ivd-tone-{{ $leaveTones[$leave->status] ?? 'muted' }}">
                                            <strong>{{ $leave->from_date?->format('d') }}</strong>
                                            <small>{{ $leave->from_date?->format('M') }}</small>
                                        </span>
                                        <div class="ivd-list__main">
                                            <span class="ivd-list__title">{{ Str::limit($leave->reason ?: 'Leave', 40) }}</span>
                                            <span class="ivd-list__meta">
                                                {{ $leave->totalDays() }} {{ Str::plural('day', $leave->totalDays()) }}
                                                @if ($leave->to_date && ! $leave->to_date->isSameDay($leave->from_date))
                                                    · till {{ $leave->to_date->format('d M') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="ivd-list__end">
                                            <span class="ivd-pill ivd-tone-{{ $leaveTones[$leave->status] ?? 'muted' }}">{{ Str::headline($leave->status) }}</span>
                                        </div>
                                    </li>
                                    @if ($loop->last)</ul>@endif
                                @empty
                                    <div class="ivd-empty py-3"><i class="bx bx-envelope"></i>No leave requests.</div>
                                @endforelse
                            @endif
                        </x-dashboard.panel>
                    </div>
                @endif
            </div>
            @endif
        @endif

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/admin-dashboard.js') }}?v={{ filemtime(public_path('admin-assets/js/admin-dashboard.js')) }}"></script>
@endpush
