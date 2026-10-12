@extends('admin.layouts.layout')

@section('content')
    @php
        $isMarked = $attendanceMeta['is_marked'] ?? false;
        $authUser = auth()->user();
        $isPlatformUser = (bool) $authUser?->isPlatformUser();
        $canAddAtt = $isPlatformUser || (bool) $authUser?->can('attendance.add');
        $canEditAtt = $isPlatformUser || (bool) $authUser?->can('attendance.edit');
        $canSaveAtt = $isMarked ? $canEditAtt : $canAddAtt;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h4 class="admin-page-title mb-0" id="attPageTitle">
                        <i class="bx bx-calendar-check"></i>
                        {{ $isMarked ? 'Update Attendance' : 'Mark Attendance' }}
                    </h4>
                    <a href="{{ route('admin.attendance.student') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.attendance.student') }}">Student Attendance</a></li>
                        <li class="breadcrumb-item active">{{ $batch->name }}</li>
                    </ol>
                </nav>

                @if (session('success'))
                    <div class="alert alert-success mt-3 mb-0">{{ session('success') }}</div>
                @endif

                <div class="master-info-bar mt-3 mb-3">
                    <div>
                        <strong>{{ $batch->name }}</strong>
                        <div class="small opacity-75">{{ $batch->branch?->name ?? '—' }} · {{ $batch->standard?->name ?? '—' }}</div>
                    </div>
                    <label class="stu-att-date-badge" for="attendanceDate">
                        <span class="stu-att-date-badge-icon"><i class="bx bx-calendar"></i></span>
                        <span class="stu-att-date-badge-body">
                            <span class="stu-att-date-badge-label">Date</span>
                            <input type="date" class="stu-att-date-badge-input" id="attendanceDate" value="{{ $date }}">
                        </span>
                    </label>
                </div>

                <div class="alert alert-warning mb-3" role="alert" id="attNoPermission"{{ $canSaveAtt ? ' hidden' : '' }}>
                    <i class="bx bx-lock-alt me-1"></i>
                    <span id="attNoPermissionText">
                        {{ $isMarked
                            ? 'Attendance is already marked for this date. You do not have permission to edit it.'
                            : 'You do not have permission to add attendance for this date.' }}
                    </span>
                </div>
                <div class="alert mb-3" role="alert" id="attHintNew"
                    style="background: rgba(var(--iv-secondary-rgb), 0.18); border: 1px solid rgba(var(--iv-secondary-rgb), 0.35); color: #8a6418;{{ ($isMarked || ! $canSaveAtt) ? ' display:none;' : '' }}">
                    Click a student to cycle <strong>Present → Absent → Late Present</strong>, then submit.
                </div>
                <div class="alert alert-success mb-3" role="alert" id="attHintMarked"{{ ($isMarked && $canSaveAtt) ? '' : ' hidden' }}>
                    <i class="bx bx-check-circle me-1"></i>
                    <span id="attMarkedText">
                        Attendance already marked for this date
                        @if (!empty($attendanceMeta['marked_count']))
                            ({{ $attendanceMeta['marked_count'] }}/{{ $attendanceMeta['total_students'] }} students).
                        @endif
                        Change any student and click <strong>Update Attendance</strong> to save corrections.
                    </span>
                </div>

                <div class="admin-detail-block">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bx bx-id-card" style="color: var(--iv-primary);"></i>
                        <h5 class="mb-0" style="color: var(--iv-primary-dark);">Batch Students</h5>
                        <span class="text-muted small">Total: <strong id="totalStudents">{{ $roster->count() }}</strong></span>
                    </div>

                    @if ($roster->isEmpty())
                        <p class="text-muted mb-0">No active students enrolled in this batch.</p>
                    @else
                        <div class="master-roll-grid" id="rollGrid">
                            @foreach ($roster as $student)
                                <div class="master-roll-chip" data-student-id="{{ $student['student_id'] }}"
                                    data-state="{{ $student['status'] }}" role="button" tabindex="0">
                                    <div class="master-roll-no">{{ $student['roll_no'] }}</div>
                                    <div class="master-roll-name">{{ $student['name'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-2 my-3">
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

                        <button type="button" class="btn w-100" id="btnSubmitAttendance"{{ $canSaveAtt ? '' : ' hidden' }}
                            style="background: var(--iv-primary); color: #fff; min-height: 48px;">
                            {{ $isMarked ? 'Update Attendance' : 'Submit Attendance' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .stu-att-date-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        padding: 8px 12px 8px 8px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(255, 255, 255, 0.55);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        cursor: pointer;
        max-width: 100%;
    }

    .stu-att-date-badge-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--iv-primary), var(--iv-primary-dark));
        color: #fff;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .stu-att-date-badge-body {
        display: flex;
        flex-direction: column;
        gap: 1px;
        min-width: 0;
        padding-right: 4px;
    }

    .stu-att-date-badge-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--iv-primary-dark);
        line-height: 1;
    }

    .stu-att-date-badge-input {
        border: 0;
        background: transparent;
        padding: 0;
        margin: 0;
        color: #1f2926;
        font-size: 0.88rem;
        font-weight: 700;
        line-height: 1.2;
        min-width: 132px;
        outline: none;
        box-shadow: none;
    }

    .stu-att-date-badge-input:focus {
        outline: none;
        box-shadow: none;
    }

    @media (max-width: 575.98px) {
        .stu-att-date-badge {
            width: 100%;
            justify-content: flex-start;
        }

        .stu-att-date-badge-input {
            width: 100%;
            min-width: 0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const dataUrl = @json(route('admin.attendance.student.data', $batch));
    const storeUrl = @json(route('admin.attendance.student.store', $batch));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const dateInput = document.getElementById('attendanceDate');
    const grid = document.getElementById('rollGrid');
    const btnSubmit = document.getElementById('btnSubmitAttendance');
    const elP = document.getElementById('cntPresent');
    const elA = document.getElementById('cntAbsent');
    const elL = document.getElementById('cntLate');
    const elTotal = document.getElementById('totalStudents');
    const hintNew = document.getElementById('attHintNew');
    const hintMarked = document.getElementById('attHintMarked');
    const markedText = document.getElementById('attMarkedText');
    const pageTitle = document.getElementById('attPageTitle');
    const noPermission = document.getElementById('attNoPermission');
    const noPermissionText = document.getElementById('attNoPermissionText');
    const canAdd = @json($canAddAtt);
    const canEdit = @json($canEditAtt);
    let canSave = @json($canSaveAtt);

    function chips() {
        return grid ? Array.from(grid.querySelectorAll('.master-roll-chip')) : [];
    }

    function refreshCounts() {
        let p = 0, a = 0, l = 0;
        chips().forEach(function(chip) {
            const s = chip.dataset.state || 'present';
            chip.classList.remove('is-absent', 'is-late');
            if (s === 'absent') { chip.classList.add('is-absent'); a++; }
            else if (s === 'late') { chip.classList.add('is-late'); l++; }
            else { p++; }
        });
        if (elP) elP.textContent = p;
        if (elA) elA.textContent = a;
        if (elL) elL.textContent = l;
    }

    function cycle(chip) {
        if (!canSave) return;
        const order = ['present', 'absent', 'late'];
        const i = order.indexOf(chip.dataset.state || 'present');
        chip.dataset.state = order[(i + 1) % order.length];
        refreshCounts();
    }

    function bindChips() {
        chips().forEach(function(chip) {
            chip.onclick = function() { cycle(chip); };
            chip.onkeydown = function(e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); cycle(chip); }
            };
        });
        refreshCounts();
    }

    function applyMeta(meta) {
        const isMarked = !!(meta && meta.is_marked);
        canSave = isMarked ? canEdit : canAdd;
        if (hintNew) hintNew.style.display = (isMarked || !canSave) ? 'none' : '';
        if (hintMarked) hintMarked.hidden = !(isMarked && canSave);
        if (noPermission) noPermission.hidden = canSave;
        if (noPermissionText) {
            noPermissionText.textContent = isMarked
                ? 'Attendance is already marked for this date. You do not have permission to edit it.'
                : 'You do not have permission to add attendance for this date.';
        }
        if (btnSubmit) btnSubmit.hidden = !canSave;
        if (markedText && meta) {
            markedText.innerHTML = 'Attendance already marked for this date'
                + (meta.marked_count ? ' (' + meta.marked_count + '/' + meta.total_students + ' students).' : '.')
                + ' Change any student and click <strong>Update Attendance</strong> to save corrections.';
        }
        if (btnSubmit) btnSubmit.textContent = isMarked ? 'Update Attendance' : 'Submit Attendance';
        if (pageTitle) {
            pageTitle.innerHTML = '<i class="bx bx-calendar-check"></i> ' + (isMarked ? 'Update Attendance' : 'Mark Attendance');
        }
    }

    function renderRoster(students, meta) {
        if (!grid) return;
        grid.innerHTML = '';
        students.forEach(function(student) {
            const chip = document.createElement('div');
            chip.className = 'master-roll-chip';
            chip.dataset.studentId = student.student_id;
            chip.dataset.state = student.status || 'present';
            chip.setAttribute('role', 'button');
            chip.tabIndex = 0;
            chip.innerHTML = '<div class="master-roll-no">' + (student.roll_no || '—') + '</div>'
                + '<div class="master-roll-name">' + (student.name || '—') + '</div>';
            grid.appendChild(chip);
        });
        if (elTotal) elTotal.textContent = students.length;
        bindChips();
        applyMeta(meta);
    }

    async function loadForDate(date) {
        const res = await fetch(dataUrl + '?date=' + encodeURIComponent(date), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success && data.data?.students) {
            renderRoster(data.data.students, data.data.meta);
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
            if (window.Swal) Swal.fire({ icon: 'success', title: 'Saved', text: data.message, timer: 1800, showConfirmButton: false });
            else alert(data.message);
        } catch (err) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: err.message });
            else alert(err.message);
        } finally {
            btnSubmit.disabled = false;
        }
    });

    bindChips();
    applyMeta(@json($attendanceMeta));
})();
</script>
@endpush
