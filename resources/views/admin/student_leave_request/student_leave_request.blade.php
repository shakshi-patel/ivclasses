@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <h4 class="admin-page-title"><i class="bx bx-calendar-x"></i> Student Leave Request</h4>
                    @if ($canApply && $students->isNotEmpty())
                        <button type="button" class="btn admin-btn-primary" id="btnApplyStudentLeave">
                            <i class="bx bx-plus me-1"></i> Apply Leave
                        </button>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Student Leave Request</li>
                    </ol>
                </nav>

                @if ($canApply && $students->isEmpty())
                    <div class="alert alert-warning mt-3 mb-0">
                        @if ($isParentApply)
                            No active students linked to your parent account.
                        @else
                            No active students available to apply leave.
                        @endif
                    </div>
                @endif

                <x-admin.list-filters
                    :action="route('admin.student_leave_request')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-status="true"
                    :statuses="['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']"
                    :institutes="$institutes ?? collect()"
                    :branches="$branches ?? collect()"
                >
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">From</label>
                        <input type="date" class="form-control form-control-sm" name="start_date" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">To</label>
                        <input type="date" class="form-control form-control-sm" name="end_date" value="{{ request('end_date') }}">
                    </div>
                </x-admin.list-filters>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Student Leave Request List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1100px;">
                            <thead>
                                <tr>
                                    <th>Sr.</th><th>Student</th><th>Branch</th><th>Batch</th><th>Start</th><th>End</th><th>Days</th><th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leaves as $leave)
                                    @php $batch = $leave->student?->enrollments?->first()?->batch; @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $leave->student?->name ?? '—' }}</td>
                                        <td>{{ $leave->student?->branch?->name ?? $leave->branch?->name ?? '—' }}</td>
                                        <td>{{ $batch?->name ?? '—' }}</td>
                                        <td>{{ $leave->from_date?->format('d M Y') }}</td>
                                        <td>{{ $leave->to_date?->format('d M Y') }}</td>
                                        <td>{{ $leave->totalDays() }}</td>
                                        <td><span class="admin-status-pill {{ $leave->statusPillClass() }}">{{ ucfirst($leave->status) }}</span></td>
                                        <td class="text-center">
                                            @if ($leave->status === 'pending')
                                                @if ($canApprove)
                                                    <button type="button" class="admin-action-icon btn-leave-approve" data-url="{{ route('admin.student_leave_request.approve', $leave) }}" title="Approve"><i class="bx bx-check"></i></button>
                                                @endif
                                                @if ($canReject)
                                                    <button type="button" class="admin-action-icon btn-leave-reject" data-url="{{ route('admin.student_leave_request.reject', $leave) }}" title="Reject"><i class="bx bx-x"></i></button>
                                                @endif
                                            @endif
                                            @if ($canShow)
                                                <button type="button" class="admin-action-icon btn-leave-view" title="View"
                                                    data-name="{{ $leave->student?->name }}"
                                                    data-start="{{ $leave->from_date?->format('d M Y') }}"
                                                    data-end="{{ $leave->to_date?->format('d M Y') }}"
                                                    data-reason="{{ $leave->reason }}"><i class="bx bx-show"></i></button>
                                            @endif
                                            @if (! $canShow && ! ($leave->status === 'pending' && ($canApprove || $canReject)))
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">No leave requests yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    @if ($canApply && $students->isNotEmpty())
        <div class="modal fade admin-modal" id="studentLeaveFormModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Apply Student Leave</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Student <span class="req">*</span></label>
                                <select class="form-select" id="studentLeaveStudent" required @if($isParentApply && $students->count() === 1) disabled @endif>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}" @selected($loop->first)>{{ $student->name }}</option>
                                    @endforeach
                                </select>
                                @if ($isParentApply && $students->count() === 1)
                                    <input type="hidden" id="studentLeaveStudentHidden" value="{{ $students->first()->id }}">
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">From Date <span class="req">*</span></label>
                                <input type="date" class="form-control" id="studentLeaveFrom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Date <span class="req">*</span></label>
                                <input type="date" class="form-control" id="studentLeaveTo" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reason</label>
                                <textarea class="form-control" id="studentLeaveReason" rows="3" placeholder="Reason for leave"></textarea>
                            </div>
                        </div>
                        <div class="text-danger small mt-2" id="studentLeaveError" hidden></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn admin-btn-primary" id="studentLeaveSaveBtn">Submit Request</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade admin-modal" id="leaveViewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Leave Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Student:</strong> <span id="lvName">—</span></p>
                    <p class="mb-1"><strong>Period:</strong> <span id="lvPeriod">—</span></p>
                    <p class="mb-0"><strong>Reason:</strong> <span id="lvReason">—</span></p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const storeUrl = @json(route('admin.student_leave_request.store'));
    const modal = new bootstrap.Modal(document.getElementById('leaveViewModal'));
    const formModalEl = document.getElementById('studentLeaveFormModal');
    const formModal = formModalEl ? new bootstrap.Modal(formModalEl) : null;

    document.getElementById('btnApplyStudentLeave')?.addEventListener('click', () => {
        document.getElementById('studentLeaveFrom').value = '';
        document.getElementById('studentLeaveTo').value = '';
        document.getElementById('studentLeaveReason').value = '';
        document.getElementById('studentLeaveError').hidden = true;
        formModal?.show();
    });

    document.getElementById('studentLeaveSaveBtn')?.addEventListener('click', async () => {
        const err = document.getElementById('studentLeaveError');
        const hiddenStudent = document.getElementById('studentLeaveStudentHidden');
        const studentId = hiddenStudent ? hiddenStudent.value : document.getElementById('studentLeaveStudent').value;
        const payload = {
            student_id: parseInt(studentId, 10),
            from_date: document.getElementById('studentLeaveFrom').value,
            to_date: document.getElementById('studentLeaveTo').value,
            reason: document.getElementById('studentLeaveReason').value.trim() || null,
        };
        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                const msg = data.message
                    || (data.errors ? Object.values(data.errors).flat().join(' ') : null)
                    || 'Submit failed';
                throw new Error(msg);
            }
            formModal?.hide();
            if (window.Swal) {
                await Swal.fire({ icon: 'success', title: 'Submitted', text: data.message || 'Leave request submitted.', timer: 1800, showConfirmButton: false });
            }
            location.reload();
        } catch (e) {
            err.textContent = e.message;
            err.hidden = false;
        }
    });

    document.body.addEventListener('click', async e => {
        const v = e.target.closest('.btn-leave-view');
        if (v) {
            document.getElementById('lvName').textContent = v.dataset.name || '—';
            document.getElementById('lvPeriod').textContent = (v.dataset.start||'—') + ' to ' + (v.dataset.end||'—');
            document.getElementById('lvReason').textContent = v.dataset.reason || '—';
            modal.show(); return;
        }
        const ap = e.target.closest('.btn-leave-approve');
        if (ap) {
            const ok = window.AdminAjax
                ? await AdminAjax.confirm({ title: 'Approve leave?', text: 'This leave request will be marked as approved.', confirmText: 'Yes, approve', icon: 'question' })
                : confirm('Approve this leave request?');
            if (!ok) return;
            try {
                const data = window.AdminAjax
                    ? await AdminAjax.post(ap.dataset.url)
                    : await (await fetch(ap.dataset.url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } })).json();
                if (window.AdminAjax) await AdminAjax.success(data.message || 'Leave approved.');
                location.reload();
            } catch (err) {
                if (window.AdminAjax) await AdminAjax.error(err.message || 'Approve failed.');
            }
            return;
        }
        const rj = e.target.closest('.btn-leave-reject');
        if (rj) {
            const ok = window.AdminAjax
                ? await AdminAjax.confirm({ title: 'Reject leave?', text: 'This leave request will be marked as rejected.', confirmText: 'Yes, reject', icon: 'warning' })
                : confirm('Reject this leave request?');
            if (!ok) return;
            try {
                const data = window.AdminAjax
                    ? await AdminAjax.post(rj.dataset.url, {})
                    : await (await fetch(rj.dataset.url, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({}) })).json();
                if (window.AdminAjax) await AdminAjax.success(data.message || 'Leave rejected.');
                location.reload();
            } catch (err) {
                if (window.AdminAjax) await AdminAjax.error(err.message || 'Reject failed.');
            }
        }
    });
})();
</script>
@endpush
