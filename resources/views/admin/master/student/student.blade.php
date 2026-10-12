@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <h4 class="admin-page-title mb-0"><i class="bx bx-user"></i> Student</h4>
                    <div class="d-flex flex-wrap gap-2">
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('student.export'))
                            <a href="{{ route('admin.export.students', request()->query()) }}" class="btn btn-outline-secondary">
                                <i class="bx bx-download me-1"></i> Export
                            </a>
                        @endif
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('student.add'))
                            <a href="{{ route('admin.student.add') }}" class="btn admin-btn-primary">
                                <i class="bx bx-plus me-1"></i> Create Student
                            </a>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Student</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.student')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-standard="true"
                    :show-status="true"
                    :statuses="['active' => 'Active', 'inactive' => 'Inactive', 'left' => 'Left', 'alumni' => 'Alumni']"
                    :institutes="$institutes"
                    :branches="$branches"
                    :standards="$standards"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Student List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Admission No.</th>
                                    <th>Name</th>
                                    <th>Standard</th>
                                    <th>Branch</th>
                                    <th>Parent</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($students as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $row->admission_no }}</td>
                                        <td class="fw-semibold">{{ $row->name }}</td>
                                        <td>{{ $row->standard?->name ?? '—' }}</td>
                                        <td>{{ $row->branch?->name ?? '—' }}</td>
                                        <td>{{ $row->parent?->father_name ?? '—' }}</td>
                                        <td>
                                            <span class="admin-status-pill {{ $row->isActive() ? 'is-active' : 'is-muted' }}" data-role="status-pill">
                                                {{ $row->isActive() ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('student.show'))
                                                <a href="{{ route('admin.student.show', $row) }}" class="admin-action-icon" title="View"><i class="bx bx-show"></i></a>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('student.edit'))
                                                <a href="{{ route('admin.student.edit', $row) }}" class="admin-action-icon" title="Edit"><i class="bx bx-pencil"></i></a>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('student.status'))
                                                <button type="button" class="admin-action-icon btn-student-toggle"
                                                    title="{{ $row->isActive() ? 'Deactivate' : 'Activate' }}"
                                                    data-url="{{ route('admin.student.toggle', $row) }}"
                                                    data-active="{{ $row->isActive() ? '1' : '0' }}">
                                                    <i class="bx {{ $row->isActive() ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('student.receipt'))
                                                <button type="button" class="admin-action-icon btn-student-receipt" title="Receipt"
                                                    data-url="{{ route('admin.student.receipt', $row) }}">
                                                    <i class="bx bx-receipt"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">No students yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="studentReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentReceiptTitle">Student Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="studentReceiptBody">
                    <p class="text-muted mb-0">Loading…</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-secondary" id="studentReceiptPrintBtn">
                        <i class="bx bx-printer me-1"></i> Print
                    </button>
                    <a href="#" class="btn btn-export-students" id="studentReceiptExportBtn">
                        <i class="bx bx-download me-1"></i> Export
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    @include('admin.partials.iv_receipt_styles')
@endpush

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.btn-student-toggle').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            try {
                const data = await AdminAjax.post(btn.dataset.url);
                AdminAjax.applyStatusToggle(btn, AdminAjax.statusFrom(data) === 'active');
                await AdminAjax.success(data.message || 'Status updated.');
            } catch (e) {
                await AdminAjax.error(e.message);
            }
        });
    });

    const modalEl = document.getElementById('studentReceiptModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const body = document.getElementById('studentReceiptBody');
    const title = document.getElementById('studentReceiptTitle');
    const exportBtn = document.getElementById('studentReceiptExportBtn');
    const printBtn = document.getElementById('studentReceiptPrintBtn');

    document.querySelectorAll('.btn-student-receipt').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            body.innerHTML = '<p class="text-muted mb-0">Loading…</p>';
            title.textContent = 'Student Receipt';
            exportBtn.href = '#';
            modal.show();
            try {
                const res = await fetch(btn.dataset.url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    throw new Error(json.message || 'Could not load receipt.');
                }
                const payload = json.data || {};
                title.textContent = payload.title || 'Student Receipt';
                body.innerHTML = payload.html || '<p class="text-muted mb-0">No receipt data.</p>';
                exportBtn.href = payload.export_url || '#';
            } catch (e) {
                body.innerHTML = '<p class="text-danger mb-0">' + (e.message || 'Failed to load receipt.') + '</p>';
            }
        });
    });

    printBtn?.addEventListener('click', function () {
        const area = document.getElementById('ivReceiptPrintArea');
        if (!area) return;
        window.print();
    });
})();
</script>
@endpush
