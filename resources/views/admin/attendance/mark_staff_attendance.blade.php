@extends('admin.layouts.layout')

@section('content')
    @php
        $isMarked = $meta['is_marked'] ?? false;
        $formattedDate = \Carbon\Carbon::parse($date)->format('d M Y');
        $authUser = auth()->user();
        $isPlatformUser = (bool) $authUser?->isPlatformUser();
        $canAddAtt = $isPlatformUser || (bool) $authUser?->can('teacher_attendance.add');
        $canEditAtt = $isPlatformUser || (bool) $authUser?->can('teacher_attendance.edit');
        $canSaveAtt = $isMarked ? $canEditAtt : $canAddAtt;
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y admin-list-page staff-att-mark-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title mb-0" id="staffAttTitle">
                            <i class="bx bx-user-check"></i>
                            <span id="staffAttTitleText">{{ $isMarked ? 'Update Attendance' : 'Mark Attendance' }}</span>
                        </h4>
                        <p class="staff-att-subtitle mb-0" id="staffAttSubtitle">
                            Teacher &amp; Staff · {{ $formattedDate }}
                        </p>
                    </div>
                    <a href="{{ route('admin.attendance.teacher', ['date' => $date, 'branch_id' => $branchId, 'institute_id' => $instituteId]) }}"
                        class="btn btn-outline-secondary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back to List
                    </a>
                </div>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.attendance.teacher') }}">Teacher Attendance</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $isMarked ? 'Update' : 'Mark' }}</li>
                    </ol>
                </nav>

                @if (session('success'))
                    <div class="alert alert-success mt-3 mb-0">{{ session('success') }}</div>
                @endif

                <div class="staff-att-filters mt-3">
                    <div class="staff-att-filter-item">
                        <label class="staff-att-filter-label" for="staffAttDate">
                            <i class="bx bx-calendar"></i> Date
                        </label>
                        <input type="date" class="form-control" id="staffAttDate" value="{{ $date }}">
                    </div>
                    <div class="staff-att-filter-item staff-att-filter-item--grow">
                        <label class="staff-att-filter-label" for="staffAttBranch">
                            <i class="bx bx-buildings"></i> Branch
                        </label>
                        <select class="form-select" id="staffAttBranch" data-no-search>
                            <option value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="staff-att-legend">
                        <span class="staff-att-legend-item is-present"><i class="bx bx-check"></i> Present</span>
                        <span class="staff-att-legend-item is-absent"><i class="bx bx-x"></i> Absent</span>
                        <span class="staff-att-legend-item is-late"><i class="bx bx-time-five"></i> Late</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="alert alert-warning mb-3" id="staffAttNoPermission" @if ($canSaveAtt) hidden @endif>
                    <i class="bx bx-lock-alt me-1"></i>
                    <span id="staffAttNoPermissionText">
                        {{ $isMarked
                            ? 'Attendance is already marked for this date. You do not have permission to edit it.'
                            : 'You do not have permission to add attendance for this date.' }}
                    </span>
                </div>
                <div class="alert staff-att-hint mb-3" id="staffAttHintNew" @if ($isMarked || ! $canSaveAtt) hidden @endif>
                    <i class="bx bx-info-circle"></i>
                    <span>Tap a card to cycle <strong>Present → Absent → Late</strong>, then submit.</span>
                </div>
                <div class="alert alert-success mb-3" id="staffAttMarkedHint" @if (! $isMarked || ! $canSaveAtt) hidden @endif>
                    <i class="bx bx-check-circle me-1"></i>
                    Attendance already marked. Change status and click <strong>Update Attendance</strong> to save.
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-group staff-att-section-icon"></i>
                        <h5 class="mb-0 staff-att-section-title">Roster</h5>
                    </div>
                    <span class="staff-att-count-pill">
                        Total: <strong id="staffAttTotal">{{ $roster->count() }}</strong>
                    </span>
                </div>

                @if ($roster->isEmpty())
                    <div class="staff-att-empty">
                        <i class="bx bx-user-x"></i>
                        <p class="mb-0">No active teachers or staff found for this selection.</p>
                    </div>
                @else
                    <div class="master-roll-grid staff-att-roll-grid" id="staffRollGrid">
                        @foreach ($roster as $member)
                            <div class="master-roll-chip staff-att-chip"
                                data-user-id="{{ $member['user_id'] }}"
                                data-state="{{ $member['status'] }}"
                                role="button"
                                tabindex="0"
                                title="Click to change status">
                                <div class="master-roll-no">{{ $member['code'] ?: '—' }}</div>
                                <div class="staff-att-chip-body">
                                    <div class="master-roll-name">{{ $member['name'] }}</div>
                                    <div class="staff-att-chip-role">{{ $member['role'] }}</div>
                                </div>
                                <div class="staff-att-chip-status" aria-hidden="true"></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-2 g-md-3 my-3">
                        <div class="col-4">
                            <div class="master-att-stat is-present">
                                <i class="bx bx-check-circle"></i>
                                <span>Present <strong id="staffCntP">0</strong></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="master-att-stat is-absent">
                                <i class="bx bx-x-circle"></i>
                                <span>Absent <strong id="staffCntA">0</strong></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="master-att-stat is-late">
                                <i class="bx bx-time-five"></i>
                                <span>Late <strong id="staffCntL">0</strong></span>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn w-100 admin-btn-primary staff-att-save-btn" id="btnStaffAttSave" @if (! $canSaveAtt) hidden @endif>
                        <i class="bx bx-save me-1"></i>
                        <span id="btnStaffAttSaveText">{{ $isMarked ? 'Update Attendance' : 'Submit Attendance' }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .staff-att-mark-page .staff-att-subtitle {
        margin-top: 0.35rem;
        color: var(--iv-text-secondary);
        font-size: 0.92rem;
    }

    .staff-att-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-radius: 14px;
        background: rgba(var(--iv-primary-rgb), 0.05);
        border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
    }

    .staff-att-filter-item {
        min-width: 160px;
    }

    .staff-att-filter-item--grow {
        flex: 1 1 220px;
        min-width: 200px;
    }

    .staff-att-filter-label {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-bottom: 0.4rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--iv-primary-dark);
    }

    .staff-att-filter-label i {
        font-size: 1rem;
        color: var(--iv-primary);
    }

    .staff-att-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-left: auto;
        padding-bottom: 0.15rem;
    }

    .staff-att-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        color: #fff;
    }

    .staff-att-legend-item.is-present { background: var(--iv-success); }
    .staff-att-legend-item.is-absent { background: var(--iv-error); }
    .staff-att-legend-item.is-late { background: var(--iv-warning); color: #3d2e08; }

    .staff-att-hint {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        background: rgba(var(--iv-secondary-rgb), 0.16);
        border: 1px solid rgba(var(--iv-secondary-rgb), 0.35);
        color: #6b4e12;
        border-radius: 12px;
    }

    .staff-att-hint i {
        font-size: 1.15rem;
        margin-top: 0.1rem;
    }

    .staff-att-section-icon {
        font-size: 1.25rem;
        color: var(--iv-primary);
    }

    .staff-att-section-title {
        color: var(--iv-primary-dark);
        font-weight: 750;
    }

    .staff-att-count-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: rgba(var(--iv-primary-rgb), 0.08);
        border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
        color: var(--iv-primary-dark);
        font-size: 0.82rem;
    }

    .staff-att-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--iv-text-secondary);
        border: 1px dashed rgba(var(--iv-primary-rgb), 0.25);
        border-radius: 14px;
        background: rgba(var(--iv-primary-rgb), 0.03);
    }

    .staff-att-empty i {
        font-size: 2rem;
        color: var(--iv-primary);
        opacity: 0.55;
        display: block;
        margin-bottom: 0.6rem;
    }

    .staff-att-roll-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }

    .staff-att-chip {
        min-height: 64px;
        box-shadow: 0 1px 2px rgba(44, 42, 74, 0.04);
    }

    .staff-att-chip:hover {
        box-shadow: 0 4px 14px rgba(var(--iv-primary-rgb), 0.12);
        transform: translateY(-1px);
    }

    .staff-att-chip-body {
        flex: 1;
        min-width: 0;
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 2px;
    }

    .staff-att-chip .master-roll-name {
        padding: 0;
        line-height: 1.25;
        word-break: break-word;
    }

    .staff-att-chip-role {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--iv-text-secondary);
        text-transform: capitalize;
        line-height: 1.2;
    }

    .staff-att-chip-status {
        width: 6px;
        flex-shrink: 0;
        background: var(--iv-success);
    }

    .staff-att-chip.is-absent .staff-att-chip-status { background: var(--iv-error); }
    .staff-att-chip.is-late .staff-att-chip-status { background: var(--iv-warning); }

    .staff-att-save-btn {
        min-height: 50px;
        font-weight: 700;
        font-size: 1rem;
        border-radius: 12px;
    }

    @media (max-width: 767.98px) {
        .staff-att-legend {
            margin-left: 0;
            width: 100%;
        }

        .staff-att-filters {
            padding: 0.9rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const dataUrl = @json(route('admin.attendance.teacher.data'));
    const storeUrl = @json(route('admin.attendance.teacher.store'));
    const instituteId = @json($instituteId);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const dateEl = document.getElementById('staffAttDate');
    const branchEl = document.getElementById('staffAttBranch');
    const grid = document.getElementById('staffRollGrid');
    const btn = document.getElementById('btnStaffAttSave');
    const btnText = document.getElementById('btnStaffAttSaveText');
    const titleText = document.getElementById('staffAttTitleText');
    const noPermission = document.getElementById('staffAttNoPermission');
    const noPermissionText = document.getElementById('staffAttNoPermissionText');
    const canAdd = @json($canAddAtt);
    const canEdit = @json($canEditAtt);
    let canSave = @json($canSaveAtt);
    const subtitle = document.getElementById('staffAttSubtitle');
    const totalEl = document.getElementById('staffAttTotal');

    function chips() {
        return grid ? Array.from(grid.querySelectorAll('.master-roll-chip')) : [];
    }

    function instituteQuery() {
        return instituteId ? '&institute_id=' + encodeURIComponent(instituteId) : '';
    }

    function formatDateLabel(iso) {
        try {
            const d = new Date(iso + 'T00:00:00');
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        } catch (e) {
            return iso;
        }
    }

    function refresh() {
        let p = 0, a = 0, l = 0;
        chips().forEach(c => {
            const s = c.dataset.state || 'present';
            c.classList.remove('is-absent', 'is-late');
            if (s === 'absent') {
                c.classList.add('is-absent');
                a++;
            } else if (s === 'late') {
                c.classList.add('is-late');
                l++;
            } else {
                p++;
            }
        });
        const elP = document.getElementById('staffCntP');
        const elA = document.getElementById('staffCntA');
        const elL = document.getElementById('staffCntL');
        if (elP) elP.textContent = p;
        if (elA) elA.textContent = a;
        if (elL) elL.textContent = l;
        if (totalEl) totalEl.textContent = String(p + a + l);
    }

    function bind() {
        chips().forEach(c => {
            c.onclick = () => {
                if (!canSave) return;
                const order = ['present', 'absent', 'late'];
                c.dataset.state = order[(order.indexOf(c.dataset.state || 'present') + 1) % 3];
                refresh();
            };
            c.onkeydown = (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    c.click();
                }
            };
        });
        refresh();
    }

    function applyMeta(meta) {
        const marked = !!(meta && meta.is_marked);
        canSave = marked ? canEdit : canAdd;
        const hintNew = document.getElementById('staffAttHintNew');
        const hintMarked = document.getElementById('staffAttMarkedHint');
        if (hintNew) hintNew.hidden = marked || !canSave;
        if (hintMarked) hintMarked.hidden = !marked || !canSave;
        if (noPermission) noPermission.hidden = canSave;
        if (noPermissionText) {
            noPermissionText.textContent = marked
                ? 'Attendance is already marked for this date. You do not have permission to edit it.'
                : 'You do not have permission to add attendance for this date.';
        }
        if (btn) btn.hidden = !canSave;
        if (btnText) btnText.textContent = marked ? 'Update Attendance' : 'Submit Attendance';
        if (titleText) titleText.textContent = marked ? 'Update Attendance' : 'Mark Attendance';
        if (subtitle && dateEl) {
            subtitle.textContent = 'Teacher & Staff · ' + formatDateLabel(dateEl.value);
        }
    }

    function chipHtml(m) {
        const code = (m.code || '—').toString().replace(/</g, '&lt;');
        const name = (m.name || '—').toString().replace(/</g, '&lt;');
        const role = (m.role || '').toString().replace(/</g, '&lt;');
        return '<div class="master-roll-no">' + code + '</div>' +
            '<div class="staff-att-chip-body">' +
            '<div class="master-roll-name">' + name + '</div>' +
            '<div class="staff-att-chip-role">' + role + '</div>' +
            '</div><div class="staff-att-chip-status" aria-hidden="true"></div>';
    }

    async function load() {
        if (!grid) return;
        const url = dataUrl + '?date=' + encodeURIComponent(dateEl.value) +
            (branchEl.value ? '&branch_id=' + branchEl.value : '') + instituteQuery();
        const res = await fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (!json.success) return;
        grid.innerHTML = '';
        (json.data.staff || []).forEach(m => {
            const c = document.createElement('div');
            c.className = 'master-roll-chip staff-att-chip';
            c.dataset.userId = m.user_id;
            c.dataset.state = m.status || 'present';
            c.setAttribute('role', 'button');
            c.tabIndex = 0;
            c.title = 'Click to change status';
            c.innerHTML = chipHtml(m);
            grid.appendChild(c);
        });
        bind();
        applyMeta(json.data.meta);
    }

    dateEl?.addEventListener('change', load);
    branchEl?.addEventListener('change', load);
    btn?.addEventListener('click', async () => {
        btn.disabled = true;
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
                    attendance_date: dateEl.value,
                    institute_id: instituteId,
                    branch_id: branchEl.value ? parseInt(branchEl.value, 10) : null,
                    records: chips().map(c => ({
                        user_id: parseInt(c.dataset.userId, 10),
                        status: c.dataset.state || 'present'
                    }))
                })
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            applyMeta(json.data?.meta);
            if (window.Swal) {
                Swal.fire({ icon: 'success', title: 'Saved', text: json.message, timer: 1800, showConfirmButton: false });
            }
        } catch (e) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: e.message });
        } finally {
            btn.disabled = false;
        }
    });
    bind();
})();
</script>
@endpush
