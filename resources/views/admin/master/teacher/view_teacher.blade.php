@extends('admin.layouts.layout')

@section('content')
    @php
        $u = $teacher->user;
        $branch = $teacher->primaryBranch();
        $loginTime = $teacher->expected_login_time
            ? \Illuminate\Support\Carbon::parse($teacher->expected_login_time)->format('g:i A')
            : '—';
        $assignedBatches = $teacher->assignedBatches ?? collect();

        $authUser = auth()->user();
        $can = fn (string $perm) => $authUser && ($authUser->isPlatformUser() || $authUser->can($perm));
        $teacherTabs = [
            'attendance' => $can('teacher.tab_attendance'),
            'batches' => $can('teacher.tab_batches'),
            'work' => $can('teacher.tab_assigned_work'),
            'login' => $can('teacher.tab_login_history'),
        ];
        $requestedTab = request('tab');
        $firstTab = ($requestedTab && ! empty($teacherTabs[$requestedTab]))
            ? $requestedTab
            : collect($teacherTabs)->search(true);
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h4 class="admin-page-title mb-0">
                        <i class="bx bx-user"></i>
                        Teacher Details
                    </h4>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('teacher.edit'))
                        <a href="{{ route('admin.master.teacher.edit', $teacher) }}" class="btn btn-sm admin-btn-primary">
                            <i class="bx bx-pencil me-1"></i> Edit
                        </a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.users', ['role' => 'teacher']) }}">Users</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $u?->name }}</li>
                    </ol>
                </nav>

                <div class="master-teacher-view-card mt-4">
                    <div class="row g-0">
                        <div class="col-lg-4 master-teacher-profile-col">
                            <div class="text-center mb-3">
                                <img src="{{ $u?->avatar_path ? asset('storage/'.$u->avatar_path) : asset('admin-assets/img/avatars/1.png') }}"
                                    alt="{{ $u?->name }}" class="master-profile-avatar mx-auto">
                                <h5 class="mt-3 mb-1" style="color: var(--iv-primary-dark);">{{ $u?->name }}</h5>
                                @if ($teacher->isActive())
                                    <span class="admin-status-pill is-active">Active</span>
                                @else
                                    <span class="admin-status-pill is-muted">Inactive</span>
                                @endif
                            </div>
                            <div class="master-profile-rows">
                                <div class="master-profile-row">
                                    <span>Teacher ID</span>
                                    <strong>{{ $teacher->employee_code ?? '—' }}</strong>
                                </div>
                                <div class="master-profile-row">
                                    <span>Institute</span>
                                    <strong>{{ $teacher->institute?->name ?? '—' }}</strong>
                                </div>
                                <div class="master-profile-row">
                                    <span>Branch</span>
                                    <strong>{{ $branch?->name ?? '—' }}</strong>
                                </div>
                                <div class="master-profile-row">
                                    <span>Mobile</span>
                                    <strong>{{ $u?->phone ?: '—' }}</strong>
                                </div>
                                <div class="master-profile-row">
                                    <span>Email</span>
                                    <strong>{{ $u?->email }}</strong>
                                </div>
                                <div class="master-profile-row">
                                    <span>Specialization</span>
                                    <strong>{{ $teacher->specialization ?: '—' }}</strong>
                                </div>
                                <div class="master-profile-row">
                                    <span>Login Time</span>
                                    <strong>{{ $loginTime }}</strong>
                                </div>
                                <div class="master-profile-row">
                                    <span>Grace Time</span>
                                    <strong>{{ $teacher->grace_minutes }} minutes</strong>
                                </div>
                                <div class="master-profile-row">
                                    <span>Address</span>
                                    <strong>{{ $teacher->address ?: '—' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 master-teacher-tabs-col">
                            @if ($firstTab === false)
                                <div class="text-center py-5">
                                    <i class="bx bx-lock-alt d-block mb-2" style="font-size: 2rem; color: var(--iv-primary); opacity: 0.7;"></i>
                                    <p class="mb-1 fw-semibold" style="color: var(--iv-primary-dark);">No permission assigned</p>
                                    <p class="text-muted mb-0 small">You do not have permission to view any section of this teacher. Please contact your administrator.</p>
                                </div>
                            @endif

                            <ul class="nav nav-pills master-tabs gap-2 mb-3 flex-wrap" role="tablist">
                                @if ($teacherTabs['attendance'])
                                    <li class="nav-item">
                                        <button class="nav-link {{ $firstTab === 'attendance' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tvAtt" type="button">Attendance</button>
                                    </li>
                                @endif
                                @if ($teacherTabs['batches'])
                                    <li class="nav-item">
                                        <button class="nav-link {{ $firstTab === 'batches' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tvBatches" type="button">Batches</button>
                                    </li>
                                @endif
                                @if ($teacherTabs['work'])
                                    <li class="nav-item">
                                        <button class="nav-link {{ $firstTab === 'work' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tvWork" type="button">Assigned Work</button>
                                    </li>
                                @endif
                                @if ($teacherTabs['login'])
                                    <li class="nav-item">
                                        <button class="nav-link {{ $firstTab === 'login' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tvLogin" type="button">Login History</button>
                                    </li>
                                @endif
                            </ul>

                            <div class="tab-content">
                                @if ($teacherTabs['attendance'])
                                <div class="tab-pane fade {{ $firstTab === 'attendance' ? 'show active' : '' }}" id="tvAtt">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                        <div class="master-att-nav">
                                            <button type="button" class="btn-att-nav" id="tchAttPrev" aria-label="Previous month">
                                                <i class="bx bx-chevron-left"></i>
                                            </button>
                                            <h6 id="tchAttMonthLabel" class="mb-0">—</h6>
                                            <button type="button" class="btn-att-nav" id="tchAttNext" aria-label="Next month">
                                                <i class="bx bx-chevron-right"></i>
                                            </button>
                                        </div>
                                        <a href="#" id="tchAttExport" class="btn btn-sm btn-export-students"
                                            data-base="{{ route('admin.export.teacher_attendance', $teacher) }}">
                                            <i class="bx bx-download me-1"></i> Export PDF
                                        </a>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-4"><div class="master-att-stat is-present py-2"><span>Present: <strong id="tchSumPresent">0</strong></span></div></div>
                                        <div class="col-4"><div class="master-att-stat is-absent py-2"><span>Absent: <strong id="tchSumAbsent">0</strong></span></div></div>
                                        <div class="col-4"><div class="master-att-stat is-late py-2"><span>Late: <strong id="tchSumLate">0</strong></span></div></div>
                                    </div>
                                    <div class="master-att-cal mb-3" id="tchAttCal"></div>
                                    <div class="master-att-legend">
                                        <span><i class="master-att-dot is-present"></i> Present</span>
                                        <span><i class="master-att-dot is-absent"></i> Absent</span>
                                        <span><i class="master-att-dot is-late"></i> Late</span>
                                        <span><span class="master-att-badge is-h">H</span> Holiday</span>
                                    </div>
                                </div>
                                @endif

                                @if ($teacherTabs['batches'])
                                <div class="tab-pane fade {{ $firstTab === 'batches' ? 'show active' : '' }}" id="tvBatches">
                                    @if ($assignedBatches->isEmpty())
                                        <p class="text-muted mb-0">No batches assigned yet.</p>
                                    @else
                                        <div class="row g-2 g-lg-3">
                                            @foreach ($assignedBatches as $batch)
                                                <div class="col-sm-6 col-xl-4">
                                                    <div class="stu-assign-batch-card">
                                                        <strong>{{ $batch->name }}</strong>
                                                        <div class="meta">
                                                            Standard: {{ $batch->standard?->name ?? '—' }}<br>
                                                            Branch: {{ $batch->branch?->name ?? '—' }}<br>
                                                            Subjects: {{ $batch->subjects->pluck('name')->filter()->implode(', ') ?: '—' }}<br>
                                                            Timing: {{ $batch->start_time && $batch->end_time ? substr((string) $batch->start_time, 0, 5).' – '.substr((string) $batch->end_time, 0, 5) : '—' }}
                                                        </div>
                                                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('batch.view'))
                                                            <a href="{{ route('admin.master.batch.show', $batch) }}" class="btn btn-sm btn-outline-primary mt-2">
                                                                <i class="bx bx-show me-1"></i> View batch
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                @endif

                                @if ($teacherTabs['work'])
                                <div class="tab-pane fade {{ $firstTab === 'work' ? 'show active' : '' }}" id="tvWork">
                                    <div class="person-data-panel">
                                        <div class="person-data-panel__head">
                                            <div>
                                                <h6 class="person-data-panel__title mb-0">Assigned Work</h6>
                                                <p class="person-data-panel__meta mb-0">
                                                    {{ $teacherHomeworks->total() }} total
                                                    · showing {{ $teacherHomeworks->firstItem() ?? 0 }}–{{ $teacherHomeworks->lastItem() ?? 0 }}
                                                </p>
                                            </div>
                                            <a href="{{ route('admin.export.teacher_homework', $teacher) }}" class="btn btn-sm btn-export-students">
                                                <i class="bx bx-download me-1"></i> Export PDF
                                            </a>
                                        </div>
                                        <div class="admin-table-wrap mb-0 person-data-panel__table">
                                            <div class="table-responsive">
                                                <table class="table admin-list-table align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 56px;">Sr</th>
                                                            <th>Title</th>
                                                            <th>Subject</th>
                                                            <th>Batch</th>
                                                            <th style="width: 110px;">Due Date</th>
                                                            <th style="width: 100px;" class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($teacherHomeworks as $hw)
                                                            <tr>
                                                                <td>{{ $teacherHomeworks->firstItem() + $loop->index }}</td>
                                                                <td>
                                                                    <div class="fw-semibold text-break">{{ $hw->title }}</div>
                                                                    @if ($hw->description)
                                                                        <div class="small text-muted text-break">{{ \Illuminate\Support\Str::limit(strip_tags((string) $hw->description), 80) }}</div>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $hw->subject?->name ?? '—' }}</td>
                                                                <td>{{ $hw->batch?->name ?? '—' }}</td>
                                                                <td>{{ $hw->due_date?->format('d M Y') ?? '—' }}</td>
                                                                <td class="text-center">
                                                                    @if ($hw->attachment_path && (auth()->user()?->isPlatformUser() || auth()->user()?->can('homework.download')))
                                                                        <a href="{{ route('admin.homework.download', $hw) }}" class="admin-action-icon" title="Download">
                                                                            <i class="bx bx-download"></i>
                                                                        </a>
                                                                    @else
                                                                        <span class="text-muted">—</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="6" class="text-muted text-center py-4">No assigned homework yet.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if ($teacherHomeworks->hasPages())
                                                <div class="person-data-panel__pager">
                                                    {{ $teacherHomeworks->links() }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if ($teacherTabs['login'])
                                <div class="tab-pane fade {{ $firstTab === 'login' ? 'show active' : '' }}" id="tvLogin">
                                    <div class="person-data-panel">
                                        <div class="person-data-panel__head">
                                            <div>
                                                <h6 class="person-data-panel__title mb-0">Login History</h6>
                                                <p class="person-data-panel__meta mb-0">
                                                    {{ $loginLogs->total() }} total
                                                    · showing {{ $loginLogs->firstItem() ?? 0 }}–{{ $loginLogs->lastItem() ?? 0 }}
                                                </p>
                                            </div>
                                            <a href="{{ route('admin.export.teacher_login', $teacher) }}" class="btn btn-sm btn-export-students">
                                                <i class="bx bx-download me-1"></i> Export PDF
                                            </a>
                                        </div>
                                        <div class="admin-table-wrap mb-0 person-data-panel__table">
                                            <div class="table-responsive">
                                                <table class="table admin-list-table align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 56px;">Sr</th>
                                                            <th style="width: 110px;">Event</th>
                                                            <th>Portal</th>
                                                            <th style="width: 170px;">Date &amp; Time</th>
                                                            <th style="width: 140px;">IP</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($loginLogs as $log)
                                                            <tr>
                                                                <td>{{ $loginLogs->firstItem() + $loop->index }}</td>
                                                                <td>
                                                                    <span class="admin-status-pill {{ $log->event === 'login' ? 'is-active' : ($log->event === 'logout' ? 'is-navy' : 'is-muted') }}">
                                                                        {{ ucfirst($log->event) }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $log->portal ? ucfirst(str_replace('_', ' ', $log->portal)) : '—' }}</td>
                                                                <td>{{ $log->logged_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                                                <td><code class="person-ip">{{ $log->ip ?: '—' }}</code></td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-muted text-center py-4">No login history yet.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if ($loginLogs->hasPages())
                                                <div class="person-data-panel__pager">
                                                    {{ $loginLogs->links() }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const label = document.getElementById('tchAttMonthLabel');
            const grid = document.getElementById('tchAttCal');
            const prev = document.getElementById('tchAttPrev');
            const next = document.getElementById('tchAttNext');
            const reportUrl = @json(route('admin.attendance.report.teacher', $teacher));
            const sumP = document.getElementById('tchSumPresent');
            const sumA = document.getElementById('tchSumAbsent');
            const sumL = document.getElementById('tchSumLate');
            const exportBtn = document.getElementById('tchAttExport');

            if (!label || !grid || !prev || !next) return;

            let view = new Date();

            function syncExport() {
                if (!exportBtn) return;
                const base = exportBtn.dataset.base || '';
                exportBtn.href = base + '?year=' + view.getFullYear() + '&month=' + (view.getMonth() + 1);
            }

            function renderCalendar(data) {
                const dayMap = data?.days || {};
                const holidays = data?.holidays || {};
                if (sumP) sumP.textContent = data?.summary?.present ?? 0;
                if (sumA) sumA.textContent = data?.summary?.absent ?? 0;
                if (sumL) sumL.textContent = data?.summary?.late ?? 0;

                const y = view.getFullYear();
                const m = view.getMonth();
                label.textContent = view.toLocaleDateString('en-IN', { month: 'long', year: 'numeric' });
                grid.innerHTML = '';
                ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(t) {
                    const el = document.createElement('div');
                    el.className = 'dow';
                    el.textContent = t;
                    grid.appendChild(el);
                });
                const first = new Date(y, m, 1);
                const pad = (first.getDay() + 6) % 7;
                const daysInMonth = new Date(y, m + 1, 0).getDate();
                for (let i = 0; i < pad; i++) {
                    const empty = document.createElement('div');
                    empty.className = 'master-att-day is-empty';
                    grid.appendChild(empty);
                }
                for (let day = 1; day <= daysInMonth; day++) {
                    const cell = document.createElement('div');
                    const cls = dayMap[day] || dayMap[String(day)] || '';
                    const isHoliday = cls === 'is-holiday';
                    cell.className = 'master-att-day' + (cls ? ' ' + cls : '');
                    cell.textContent = isHoliday ? 'H' : String(day);
                    const holiday = holidays[day] || holidays[String(day)];
                    if (holiday?.title) {
                        cell.title = holiday.title;
                    }
                    grid.appendChild(cell);
                }
            }

            async function loadReport() {
                syncExport();
                try {
                    const res = await AdminAjax.get(
                        reportUrl + '?year=' + view.getFullYear() + '&month=' + (view.getMonth() + 1)
                    );
                    renderCalendar(res.data || {});
                } catch (e) {
                    label.textContent = 'Could not load';
                    grid.innerHTML = '';
                }
            }

            prev.addEventListener('click', function() {
                view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
                loadReport();
            });
            next.addEventListener('click', function() {
                view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                loadReport();
            });
            loadReport();
        })();
    </script>
@endpush
