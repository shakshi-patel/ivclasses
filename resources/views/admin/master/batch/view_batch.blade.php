@extends('admin.layouts.layout')

@section('content')
    @php
        $branchAddress = collect([
            $batch->branch?->address,
            $batch->branch?->city,
            $batch->branch?->state,
            $batch->branch?->pincode,
        ])
            ->filter()
            ->implode(', ');
        $teacherRows = $teacherRows ?? collect();
        $u = auth()->user();
        $isPlatform = (bool) $u?->isPlatformUser();
        $can = fn(string $perm) => $isPlatform || ($u && $u->can($perm));

        $batchTabs = [
            'details' => $can('batch.tab_details'),
            'attendance' => $can('batch.tab_attendance'),
            'students' => $can('batch.tab_students'),
            'exam' => $can('batch.tab_exam'),
            'homework' => $can('batch.tab_homework'),
            'attendance_report' => $can('batch.tab_attendance_report'),
            'notice' => $can('batch.tab_notice'),
        ];
        $hasAnyBatchTab = collect($batchTabs)->contains(true) || $can('batch.assign');
        $firstTab = collect($batchTabs)->search(true) ?: 'details';
        $reqTab = request('tab');
        if ($reqTab === 'attendance' && ($batchTabs['attendance'] ?? false)) {
            $firstTab = 'attendance';
        }
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h4 class="admin-page-title mb-0">
                        <i class="bx bx-show"></i>
                        View Batch
                    </h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.master.batch') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back
                        </a>
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('batch.edit'))
                            <a href="{{ route('admin.master.batch.edit', $batch) }}" class="btn btn-sm admin-btn-primary">
                                <i class="bx bx-pencil me-1"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.master.batch') }}">Batches</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $batch->name }}</li>
                    </ol>
                </nav>

                <div class="master-info-bar mt-3">
                    <div>
                        <strong>{{ $batch->institute?->name ?? '—' }} | Batch: {{ $batch->name }}</strong>
                        <div style="font-size: 0.82rem; opacity: 0.9;">
                            {{ $batch->standard?->name ?? '—' }} · {{ $batch->branch?->name ?? '—' }}
                        </div>
                    </div>
                    <span>Created: {{ $batch->created_at?->format('d M Y | h:i A') }}</span>
                </div>

                <ul class="nav stu-view-tabs mt-3" role="tablist">
                    @if ($batchTabs['details'])
                        <li class="nav-item">
                            <button class="nav-link {{ $firstTab === 'details' ? 'active' : '' }}" data-bs-toggle="pill"
                                data-bs-target="#bvDetails" type="button">
                                <i class="bx bx-detail"></i> Batch Details
                            </button>
                        </li>
                    @endif
                    @if ($batchTabs['attendance'])
                        <li class="nav-item">
                            <button class="nav-link {{ $firstTab === 'attendance' ? 'active' : '' }}" data-bs-toggle="pill"
                                data-bs-target="#bvAttendance" type="button">
                                <i class="bx bx-calendar-check"></i> Attendance
                            </button>
                        </li>
                    @endif
                    @if ($batchTabs['students'])
                        <li class="nav-item">
                            <button class="nav-link {{ $firstTab === 'students' ? 'active' : '' }}" data-bs-toggle="pill"
                                data-bs-target="#bvStudents" type="button">
                                <i class="bx bx-user"></i> Students
                            </button>
                        </li>
                    @endif
                    @if ($batchTabs['exam'])
                        <li class="nav-item">
                            <button class="nav-link {{ $firstTab === 'exam' ? 'active' : '' }}" data-bs-toggle="pill"
                                data-bs-target="#bvExam" type="button">
                                <i class="bx bx-edit"></i> Exam
                            </button>
                        </li>
                    @endif
                    @if ($batchTabs['homework'])
                        <li class="nav-item">
                            <button class="nav-link {{ $firstTab === 'homework' ? 'active' : '' }}" data-bs-toggle="pill"
                                data-bs-target="#bvHomework" type="button">
                                <i class="bx bx-book-open"></i> Home Work
                            </button>
                        </li>
                    @endif
                    @if ($batchTabs['attendance_report'])
                        <li class="nav-item">
                            <button class="nav-link {{ $firstTab === 'attendance_report' ? 'active' : '' }}"
                                data-bs-toggle="pill" data-bs-target="#bvAttReport" type="button">
                                <i class="bx bx-bar-chart-alt-2"></i> Attendance Report
                            </button>
                        </li>
                    @endif
                    @if ($batchTabs['notice'])
                        <li class="nav-item">
                            <button class="nav-link {{ $firstTab === 'notice' ? 'active' : '' }}" data-bs-toggle="pill"
                                data-bs-target="#bvNotice" type="button">
                                <i class="bx bx-bell"></i> Notice
                            </button>
                        </li>
                    @endif
                    @if ($can('batch.assign'))
                        <li class="nav-item">
                            <button class="nav-link" type="button" data-bs-toggle="modal"
                                data-bs-target="#assignBatchModal">
                                <i class="bx bx-link"></i> Assign Batch
                            </button>
                        </li>
                    @endif
                </ul>

                @if (!$hasAnyBatchTab)
                    <div class="admin-detail-block mt-3 text-center py-5">
                        <i class="bx bx-lock-alt d-block mb-2"
                            style="font-size: 2rem; color: var(--iv-primary); opacity: 0.7;"></i>
                        <p class="mb-1 fw-semibold" style="color: var(--iv-primary-dark);">No permission assigned</p>
                        <p class="text-muted mb-0 small">You do not have permission to view any section of this batch.
                            Please contact your administrator.</p>
                    </div>
                @endif

                <div class="tab-content admin-detail-block">
                    {{-- BATCH DETAILS --}}
                    @if ($batchTabs['details'])
                        <div class="tab-pane fade {{ $firstTab === 'details' ? 'show active' : '' }}" id="bvDetails">
                            <div class="master-batch-card mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <strong>{{ $batch->name }} | {{ $batch->standard?->name ?? '—' }}</strong>
                                        <div class="text-muted small mt-1">
                                            Branch Address : {{ $branchAddress ?: '—' }}
                                        </div>
                                    </div>
                                    @if ($batch->isActive())
                                        <span class="admin-status-pill is-active">Active</span>
                                    @else
                                        <span class="admin-status-pill is-muted">Inactive</span>
                                    @endif
                                </div>
                                <div class="master-batch-time">
                                    Batch Timing: {{ $batch->timingLabel() }}
                                </div>
                            </div>

                            <h6 class="mb-3" style="color: var(--iv-primary-dark); font-weight: 750;">Teacher List</h6>
                            <div class="row g-3">
                                @forelse ($teacherRows as $t)
                                    <div class="col-md-6 col-xl-4">
                                        <div class="master-teacher-chip master-teacher-chip--stack h-100">
                                            <div class="d-flex align-items-center gap-2 w-100">
                                                <img src="{{ $t['avatar'] }}" alt="{{ $t['name'] }}"
                                                    class="master-teacher-chip-img"
                                                    onerror="this.onerror=null;this.src='{{ asset('admin-assets/img/roles/teacher.svg') }}';">
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="t-name text-truncate">{{ $t['name'] }}</div>
                                                    <div class="t-meta text-truncate">
                                                        @if ($t['code'] !== '—')
                                                            {{ $t['code'] }} ·
                                                        @endif
                                                        {{ $t['subject'] }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between w-100 mt-2 px-2 py-2 rounded-2"
                                                style="background: rgba(var(--iv-primary-rgb), 0.06); font-size: 0.8rem;">
                                                <span class="text-truncate">Contact: {{ $t['phone'] }}</span>
                                                <i class="bx bx-phone flex-shrink-0"
                                                    style="color: var(--iv-primary);"></i>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted mb-0">No teachers assigned to this batch yet.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    {{-- ATTENDANCE --}}
                    @if ($batchTabs['attendance'])
                        @php
                            $attAlreadyMarked = $attendanceMeta['is_marked'] ?? false;
                            $canAttAdd = $can('attendance.add');
                            $canAttEdit = $can('attendance.edit');
                            $canAttSave = $attAlreadyMarked ? $canAttEdit : $canAttAdd;
                        @endphp
                        <div class="tab-pane fade {{ $firstTab === 'attendance' ? 'show active' : '' }}"
                            id="bvAttendance">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="mb-0 fw-semibold" style="color: var(--iv-primary);">Date</label>
                                    <input type="date" class="form-control form-control-sm" id="attDateInput"
                                        style="width: auto;" value="{{ $attendanceDate }}">
                                </div>
                                <button type="button" class="btn btn-sm"
                                    id="btnToggleAddAtt"{{ $attAlreadyMarked || $canAttAdd ? '' : ' hidden' }}
                                    style="background: var(--iv-primary); color: #fff;">
                                    <i class="bx {{ $attAlreadyMarked ? 'bx-edit' : 'bx-plus' }} me-1"></i>
                                    <span
                                        id="btnToggleAddAttLabel">{{ $attAlreadyMarked ? 'Update Attendance' : 'Add Attendance' }}</span>
                                </button>
                            </div>

                            <div class="alert alert-warning mb-3" role="alert"
                                id="attNoAddPermission"{{ $attAlreadyMarked || $canAttAdd ? ' hidden' : '' }}>
                                <i class="bx bx-lock-alt me-1"></i>
                                Attendance is not marked for this date. You do not have permission to add attendance.
                            </div>
                            <div class="alert mb-3" role="alert" id="attHintCard"
                                style="background: rgba(var(--iv-secondary-rgb), 0.18); border: 1px solid rgba(var(--iv-secondary-rgb), 0.35); color: #8a6418;{{ $attAlreadyMarked || !$canAttAdd ? ' display:none;' : '' }}">
                                Select a date and click <strong>Add Attendance</strong> to mark present / absent / late.
                            </div>
                            <div class="alert alert-success mb-3" role="alert"
                                id="attMarkedBanner"{{ $attAlreadyMarked ? '' : ' hidden' }}>
                                <i class="bx bx-check-circle me-1"></i>
                                <span id="attMarkedBannerText">
                                    Attendance already marked for this date. Open below to review or update.
                                </span>
                            </div>

                            <div id="addAttPanel" @if (!$attAlreadyMarked) hidden @endif>
                                <div class="master-info-bar mb-3">
                                    <strong id="attDateLabel">Date:
                                        {{ \Carbon\Carbon::parse($attendanceDate)->format('d F Y') }}</strong>
                                    <span>Total Students: {{ $attendanceRoster->count() }}</span>
                                </div>

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="bx bx-id-card" style="color: var(--iv-primary);"></i>
                                    <h6 class="mb-0" style="color: var(--iv-primary-dark);">Batch Student ( Roll No. )
                                    </h6>
                                </div>

                                @if ($attendanceRoster->isEmpty())
                                    <p class="text-muted">No active students enrolled in this batch.</p>
                                @else
                                    <div class="master-roll-grid" id="rollGrid">
                                        @foreach ($attendanceRoster as $student)
                                            <div class="master-roll-chip" data-student-id="{{ $student['student_id'] }}"
                                                data-state="{{ $student['status'] }}" role="button" tabindex="0">
                                                <div class="master-roll-no">{{ $student['roll_no'] }}</div>
                                                <div class="master-roll-name">{{ $student['name'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-3 mb-3 px-3 py-2 rounded-3"
                                        style="background: rgba(var(--iv-primary-rgb), 0.08); color: var(--iv-primary-dark); font-size: 0.86rem;">
                                        Note: Click a student to cycle Present → Absent → Late Present.
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-4">
                                            <div class="master-att-stat is-present">
                                                <i class="bx bx-check-circle"></i>
                                                <span>Present: <strong id="cntPresent">0</strong></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="master-att-stat is-absent">
                                                <i class="bx bx-x-circle"></i>
                                                <span>Absent: <strong id="cntAbsent">0</strong></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="master-att-stat is-late">
                                                <i class="bx bx-time-five"></i>
                                                <span>Late Present: <strong id="cntLate">0</strong></span>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn w-100"
                                        id="btnSubmitBatchAtt"{{ $canAttSave ? '' : ' hidden' }}
                                        style="background: var(--iv-primary); color: #fff; min-height: 48px;">
                                        {{ $attAlreadyMarked ? 'Update Attendance' : 'Submit Attendance' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- STUDENTS --}}
                    @if ($batchTabs['students'])
                        <div class="tab-pane fade {{ $firstTab === 'students' ? 'show active' : '' }}" id="bvStudents">
                            <div class="d-flex justify-content-end mb-3">
                                <a href="{{ route('admin.export.batch_students', $batch) }}"
                                    class="btn btn-sm btn-export-students">
                                    <i class="bx bx-download me-1"></i> Export PDF
                                </a>
                            </div>
                            <div class="row g-3">
                                @forelse ($batchStudents as $enrollment)
                                    @php $stu = $enrollment->student; @endphp
                                    <div class="col-md-6 col-xl-4">
                                        <div class="master-student-card">
                                            <div class="s-top">
                                                <div class="s-avatar"><i class="bx bx-user"></i></div>
                                                <div class="s-info">
                                                    <div class="s-name">{{ $stu?->name ?? '—' }}</div>
                                                    <div class="s-id">#{{ $stu?->admission_no ?? '—' }}</div>
                                                </div>
                                                <div class="s-roll">Roll {{ $enrollment->roll_no ?: '—' }}</div>
                                            </div>
                                            <div class="s-father">
                                                <span>Father</span>
                                                <span>{{ $stu?->parent?->father_name ?? '—' }}</span>
                                            </div>
                                            @php $parentPhone = trim((string) ($stu?->parent?->phone ?? '')); @endphp
                                            <div class="s-contact">
                                                <span>{{ $parentPhone !== '' ? $parentPhone : '—' }}</span>
                                                @if ($parentPhone !== '')
                                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $parentPhone) }}"
                                                        class="s-call" title="Call {{ $parentPhone }}"
                                                        aria-label="Call {{ $parentPhone }}">
                                                        <i class="bx bx-phone"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted mb-0">No students enrolled in this batch.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    {{-- EXAM --}}
                    @if ($batchTabs['exam'])
                        <div class="tab-pane fade {{ $firstTab === 'exam' ? 'show active' : '' }}" id="bvExam">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <h6 class="mb-0" style="color: var(--iv-primary-dark); font-weight: 750;">Batch Exams
                                </h6>
                                @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('exam.add'))
                                    <a href="{{ route('admin.exam.add') }}?batch_id={{ $batch->id }}"
                                        class="btn btn-sm admin-btn-primary">
                                        <i class="bx bx-plus me-1"></i> Add Exam
                                    </a>
                                @endif
                            </div>
                            <div class="row g-2 g-lg-3">
                                @forelse ($batchExams ?? [] as $exam)
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="master-exam-card">
                                            <strong>{{ $exam->title }}</strong>
                                            <div class="c-date">{{ $exam->exam_date?->format('d M Y') ?? '—' }}</div>
                                            <div class="c-meta">
                                                @if ($exam->subject?->name)
                                                    {{ $exam->subject->name }} ·
                                                @endif
                                                @if ($exam->duration_label)
                                                    Timing: {{ $exam->duration_label }} ·
                                                @endif
                                                Maximum Marks:
                                                {{ rtrim(rtrim(number_format((float) $exam->total_marks, 2), '0'), '.') }}
                                            </div>
                                            @if ($exam->description)
                                                <div class="c-body">
                                                    {{ \Illuminate\Support\Str::limit($exam->description, 120) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted mb-0">No exams for this batch yet.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    {{-- HOMEWORK --}}
                    @if ($batchTabs['homework'])
                        <div class="tab-pane fade {{ $firstTab === 'homework' ? 'show active' : '' }}" id="bvHomework">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <h6 class="mb-0" style="color: var(--iv-primary-dark); font-weight: 750;">Batch Homework
                                </h6>
                                @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('homework.add'))
                                    <a href="{{ route('admin.homework.add') }}?batch_id={{ $batch->id }}"
                                        class="btn btn-sm admin-btn-primary">
                                        <i class="bx bx-plus me-1"></i> Add Homework
                                    </a>
                                @endif
                            </div>
                            <div class="row g-2 g-lg-3">
                                @forelse ($batchHomeworks as $hw)
                                    <div class="col-sm-6 col-xl-3">
                                        @php
                                            $dueDate = $hw->due_date;
                                            $dueState = !$dueDate
                                                ? 'no-date'
                                                : ($dueDate->isBefore(today())
                                                    ? 'overdue'
                                                    : ($dueDate->isToday()
                                                        ? 'today'
                                                        : (today()->diffInDays($dueDate) <= 3
                                                            ? 'soon'
                                                            : 'upcoming')));
                                            $canDownloadHomework =
                                                $hw->attachment_path &&
                                                (auth()->user()?->isPlatformUser() ||
                                                    auth()->user()?->can('homework.download'));
                                        @endphp
                                        <div class="master-hw-card">
                                            @if ($canDownloadHomework)
                                                <a href="{{ route('admin.homework.download', $hw) }}"
                                                    class="master-hw-attachment-badge" title="Download homework document"
                                                    aria-label="Download homework document">
                                                    <i class="bx bx-download"></i>
                                                </a>
                                            @endif
                                            <strong>{{ $hw->title }}</strong>
                                            @if ($hw->subject?->name)
                                                <div class="text-muted small">{{ $hw->subject->name }}</div>
                                            @endif
                                            <div class="master-hw-due is-{{ $dueState }}">
                                                <i class="bx bx-calendar-exclamation"></i>
                                                <span>{{ $dueState === 'overdue' ? 'Overdue' : ($dueState === 'today' ? 'Due today' : 'Due') }}</span>
                                                <b>{{ $dueDate?->format('d M Y') ?? 'Not set' }}</b>
                                            </div>
                                            <div class="c-body">
                                                {{ \Illuminate\Support\Str::limit($hw->description ?? '—', 120) }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted mb-0">No homework assigned for this batch yet.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    {{-- ATTENDANCE REPORT --}}
                    @if ($batchTabs['attendance_report'])
                        <div class="tab-pane fade {{ $firstTab === 'attendance_report' ? 'show active' : '' }}"
                            id="bvAttReport">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div class="master-att-nav">
                                    <button type="button" class="btn-att-nav" id="batchAttPrev"
                                        aria-label="Previous month">
                                        <i class="bx bx-chevron-left"></i>
                                    </button>
                                    <h6 id="batchAttMonthLabel">—</h6>
                                    <button type="button" class="btn-att-nav" id="batchAttNext"
                                        aria-label="Next month">
                                        <i class="bx bx-chevron-right"></i>
                                    </button>
                                </div>
                                <a href="#" id="batchAttExport" class="btn btn-sm btn-export-students"
                                    data-base="{{ route('admin.export.batch_attendance', $batch) }}">
                                    <i class="bx bx-download me-1"></i> Export PDF
                                </a>
                            </div>
                            <div class="master-att-report-wrap">
                                <table class="master-att-report" id="batchAttReportTable">
                                    <thead id="batchAttReportHead"></thead>
                                    <tbody id="batchAttReportBody">
                                        <tr>
                                            <td class="text-muted p-3">Loading report…</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="master-att-legend mt-3">
                                <span><span class="master-att-badge is-p">P</span> Present</span>
                                <span><span class="master-att-badge is-a">A</span> Absent</span>
                                <span><span class="master-att-badge is-lp">LP</span> Late</span>
                                <span><span class="master-att-badge is-s">S</span> Sunday</span>
                                <span><span class="master-att-badge is-h">H</span> Holiday</span>
                            </div>
                        </div>
                    @endif

                    {{-- NOTICE --}}
                    @if ($batchTabs['notice'])
                        <div class="tab-pane fade {{ $firstTab === 'notice' ? 'show active' : '' }}" id="bvNotice">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <h6 class="mb-0" style="color: var(--iv-primary-dark); font-weight: 750;">Batch Notices
                                </h6>
                                @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('notice.add'))
                                    <a href="{{ route('admin.notice.add') }}" class="btn btn-sm admin-btn-primary">
                                        <i class="bx bx-plus me-1"></i> Add Notice
                                    </a>
                                @endif
                            </div>
                            <div class="row g-2 g-lg-3">
                                @forelse ($batchNotices ?? [] as $notice)
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="master-notice-card">
                                            <strong>{{ $notice->title }}</strong>
                                            <div class="c-date">
                                                {{ \App\Models\Notice::AUDIENCES[$notice->audience] ?? ucfirst($notice->audience ?? 'Notice') }}
                                                · {{ $notice->scopeLabel() }}
                                                · {{ $notice->notice_date?->format('d M Y') ?? '—' }}
                                            </div>
                                            <div class="c-body">
                                                {{ \Illuminate\Support\Str::limit($notice->body ?? '—', 140) }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted mb-0">No notices for this batch yet.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($can('batch.assign'))
        <div class="modal fade admin-modal" id="assignBatchModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bx bx-link me-1"></i> Assign Batch Teachers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Assign teachers to <strong>{{ $batch->name }}</strong> (design
                            preview).</p>
                        <label class="form-label fw-semibold">Select Teachers</label>
                        <select class="form-select" multiple data-no-search>
                            @foreach ($batch->teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn admin-btn-primary">Save Assignment</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function() {
            const batchId = @json($batch->id);
            const dataUrl = @json(route('admin.attendance.student.data', $batch));
            const storeUrl = @json(route('admin.attendance.student.store', $batch));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const btn = document.getElementById('btnToggleAddAtt');
            const panel = document.getElementById('addAttPanel');
            const hint = document.getElementById('attHintCard');
            const markedBanner = document.getElementById('attMarkedBanner');
            const markedBannerText = document.getElementById('attMarkedBannerText');
            const toggleLabel = document.getElementById('btnToggleAddAttLabel');
            const dateInput = document.getElementById('attDateInput');
            const dateLabel = document.getElementById('attDateLabel');
            const btnSubmit = document.getElementById('btnSubmitBatchAtt');
            const noAddPermission = document.getElementById('attNoAddPermission');
            const canAttAdd = @json($can('attendance.add'));
            const canAttEdit = @json($can('attendance.edit'));
            let attMeta = @json($attendanceMeta);

            function isAttMarked() {
                return !!(attMeta && attMeta.is_marked);
            }

            function canSaveAtt() {
                return isAttMarked() ? canAttEdit : canAttAdd;
            }

            function setToggleClosed() {
                if (!btn) return;
                const isMarked = isAttMarked();
                const icon = isMarked ? (canAttEdit ? 'bx-edit' : 'bx-show') : 'bx-plus';
                const label = isMarked ? (canAttEdit ? 'Update Attendance' : 'View Attendance') : 'Add Attendance';
                btn.innerHTML = '<i class="bx ' + icon + ' me-1"></i> <span id="btnToggleAddAttLabel">' + label +
                    '</span>';
            }

            function setToggleOpen() {
                if (btn) btn.innerHTML = '<i class="bx bx-x me-1"></i> Close';
            }

            function applyMeta(meta) {
                attMeta = meta || attMeta;
                const isMarked = isAttMarked();
                const canOpen = isMarked || canAttAdd;
                if (btn) btn.hidden = !canOpen;
                if (!canOpen && panel) panel.setAttribute('hidden', '');
                if (noAddPermission) noAddPermission.hidden = canOpen;
                if (hint) hint.style.display = (isMarked || !canAttAdd) ? 'none' : '';
                if (markedBanner) markedBanner.hidden = !isMarked;
                if (markedBannerText && meta) {
                    markedBannerText.textContent = 'Attendance already marked for this date' +
                        (meta.marked_count ? ' (' + meta.marked_count + '/' + meta.total_students + ' students).' :
                        '.') +
                        (canAttEdit ? ' Review below and update if needed.' : '');
                }
                if (btnSubmit) {
                    btnSubmit.textContent = isMarked ? 'Update Attendance' : 'Submit Attendance';
                    btnSubmit.hidden = !canSaveAtt();
                }
                if (panel && !panel.hasAttribute('hidden') && btn) setToggleOpen();
                else setToggleClosed();
            }

            if (btn && panel) {
                btn.addEventListener('click', function() {
                    const open = panel.hasAttribute('hidden');
                    if (open) {
                        panel.removeAttribute('hidden');
                        if (hint) hint.style.display = 'none';
                        setToggleOpen();
                    } else {
                        panel.setAttribute('hidden', '');
                        applyMeta(attMeta);
                    }
                });
            }

            const elP = document.getElementById('cntPresent');
            const elA = document.getElementById('cntAbsent');
            const elL = document.getElementById('cntLate');

            function chips() {
                const grid = document.getElementById('rollGrid');
                return grid ? Array.from(grid.querySelectorAll('.master-roll-chip')) : [];
            }

            function refreshAtt() {
                if (!elP) return;
                let p = 0,
                    a = 0,
                    l = 0;
                chips().forEach(function(chip) {
                    const s = chip.dataset.state;
                    chip.classList.remove('is-absent', 'is-late');
                    if (s === 'absent') {
                        chip.classList.add('is-absent');
                        a++;
                    } else if (s === 'late') {
                        chip.classList.add('is-late');
                        l++;
                    } else {
                        p++;
                    }
                });
                elP.textContent = p;
                elA.textContent = a;
                elL.textContent = l;
            }

            function cycle(chip) {
                if (!canSaveAtt()) return;
                const order = ['present', 'absent', 'late'];
                const i = order.indexOf(chip.dataset.state || 'present');
                chip.dataset.state = order[(i + 1) % order.length];
                refreshAtt();
            }

            function bindChips() {
                chips().forEach(function(chip) {
                    chip.onclick = function() {
                        cycle(chip);
                    };
                    chip.onkeydown = function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            cycle(chip);
                        }
                    };
                });
                refreshAtt();
            }

            function renderRoster(students, meta) {
                const grid = document.getElementById('rollGrid');
                if (!grid) return;
                grid.innerHTML = '';
                students.forEach(function(student) {
                    const chip = document.createElement('div');
                    chip.className = 'master-roll-chip';
                    chip.dataset.studentId = student.student_id;
                    chip.dataset.state = student.status || 'present';
                    chip.setAttribute('role', 'button');
                    chip.tabIndex = 0;
                    chip.innerHTML = '<div class="master-roll-no">' + (student.roll_no || '—') + '</div>' +
                        '<div class="master-roll-name">' + (student.name || '—') + '</div>';
                    grid.appendChild(chip);
                });
                bindChips();
                applyMeta(meta);
            }

            async function loadForDate(date) {
                const res = await fetch(dataUrl + '?date=' + encodeURIComponent(date), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (data.success && data.data?.students) {
                    renderRoster(data.data.students, data.data.meta);
                    if (dateLabel) {
                        dateLabel.textContent = 'Date: ' + new Date(date + 'T00:00:00').toLocaleDateString(
                        'en-IN', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric'
                        });
                    }
                    if (data.data.meta?.is_marked && panel) {
                        panel.removeAttribute('hidden');
                        if (hint) hint.style.display = 'none';
                        setToggleOpen();
                    }
                }
            }

            dateInput?.addEventListener('change', function() {
                loadForDate(dateInput.value);
            });

            btnSubmit?.addEventListener('click', async function() {
                const records = chips().map(function(chip) {
                    return {
                        student_id: parseInt(chip.dataset.studentId, 10),
                        status: chip.dataset.state || 'present'
                    };
                });
                btnSubmit.disabled = true;
                try {
                    const res = await fetch(storeUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            attendance_date: dateInput.value,
                            records: records
                        })
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Save failed');
                    if (data.data?.meta) applyMeta(data.data.meta);
                    if (window.Swal) Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: data.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                } catch (err) {
                    if (window.Swal) Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.message
                    });
                } finally {
                    btnSubmit.disabled = false;
                }
            });

            bindChips();
            applyMeta(attMeta);

            @if (request('tab') === 'attendance')
                document.querySelector('[data-bs-target="#bvAttendance"]')?.click();
            @endif

            const label = document.getElementById('batchAttMonthLabel');
            const prev = document.getElementById('batchAttPrev');
            const next = document.getElementById('batchAttNext');
            const reportHead = document.getElementById('batchAttReportHead');
            const reportBody = document.getElementById('batchAttReportBody');
            const batchReportUrl = @json(route('admin.attendance.report.batch', $batch));

            function renderBatchReport(data) {
                if (!reportHead || !reportBody || !data) return;
                const daysInMonth = data.days_in_month || 31;
                const sundays = data.sundays || [];
                const holidays = data.holidays || {};
                let headHtml = '<tr><th class="name-col">Student</th>';
                for (let d = 1; d <= daysInMonth; d++) {
                    const holiday = holidays[d] || holidays[String(d)];
                    let headClass = 'day-head';
                    if (sundays.includes(d)) headClass += ' is-sun';
                    if (holiday) headClass += ' is-holiday';
                    headHtml += '<th class="' + headClass + '"' + (holiday?.title ? ' title="' + holiday.title.replace(
                        /"/g, '&quot;') + '"' : '') + '>' + d + '</th>';
                }
                headHtml += '</tr>';
                reportHead.innerHTML = headHtml;

                const students = data.students || [];
                if (!students.length) {
                    reportBody.innerHTML = '<tr><td colspan="' + (daysInMonth + 1) +
                        '" class="text-muted p-3">No students or attendance for this month.</td></tr>';
                    return;
                }

                let bodyHtml = '';
                students.forEach(function(stu) {
                    bodyHtml += '<tr><td class="name-col">' + (stu.name || '—') + (stu.roll_no ? ' | ' + stu
                        .roll_no : '') + '</td>';
                    for (let d = 1; d <= daysInMonth; d++) {
                        const badge = stu.days && (stu.days[d] || stu.days[String(d)]);
                        const holiday = holidays[d] || holidays[String(d)];
                        const isSun = sundays.includes(d);
                        bodyHtml += '<td>';
                        if (badge) {
                            const titleAttr = (badge.class === 'is-h' && holiday?.title) ?
                                ' title="' + holiday.title.replace(/"/g, '&quot;') + '"' :
                                '';
                            bodyHtml += '<span class="master-att-badge ' + badge.class + '"' + titleAttr + '>' +
                                badge.label + '</span>';
                        } else if (holiday) {
                            bodyHtml += '<span class="master-att-badge is-h"' + (holiday?.title ? ' title="' +
                                holiday.title.replace(/"/g, '&quot;') + '"' : '') + '>H</span>';
                        } else if (isSun) {
                            bodyHtml += '<span class="master-att-badge is-s">S</span>';
                        } else {
                            bodyHtml += '<span class="text-muted">-</span>';
                        }
                        bodyHtml += '</td>';
                    }
                    bodyHtml += '</tr>';
                });
                reportBody.innerHTML = bodyHtml;
            }

            if (label && prev && next && reportHead && reportBody) {
                let view = new Date(@json((int) date('Y', strtotime($attendanceDate))), @json((int) date('n', strtotime($attendanceDate)) - 1), 1);
                const exportBtn = document.getElementById('batchAttExport');

                function syncExport() {
                    if (!exportBtn) return;
                    const base = exportBtn.dataset.base || '';
                    exportBtn.href = base + '?year=' + view.getFullYear() + '&month=' + (view.getMonth() + 1);
                }

                async function loadBatchReport() {
                    syncExport();
                    label.textContent = view.toLocaleDateString('en-IN', {
                        month: 'long',
                        year: 'numeric'
                    });
                    const url = batchReportUrl + '?year=' + view.getFullYear() + '&month=' + (view.getMonth() + 1);
                    const res = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const json = await res.json();
                    if (json.success) renderBatchReport(json.data);
                }

                prev.addEventListener('click', function() {
                    view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
                    loadBatchReport();
                });
                next.addEventListener('click', function() {
                    view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                    loadBatchReport();
                });

                renderBatchReport(@json($attendanceReport));
                label.textContent = view.toLocaleDateString('en-IN', {
                    month: 'long',
                    year: 'numeric'
                });
                syncExport();
            }
        })();
    </script>
@endpush
