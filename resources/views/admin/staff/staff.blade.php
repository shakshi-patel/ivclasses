@extends('admin.layouts.layout')

@section('content')
    @php
        $authUser = auth()->user();
        $canViewStaff = $authUser && ($authUser->isPlatformUser() || $authUser->can('staff.view'));
        $canShowStaff = $authUser && ($authUser->isPlatformUser() || $authUser->can('staff.show'));
        $canEditStaff = $authUser && ($authUser->isPlatformUser() || $authUser->can('staff.edit'));
        $canStatusStaff = $authUser && ($authUser->isPlatformUser() || $authUser->can('staff.status'));
        $canDeleteStaff = $authUser && ($authUser->isPlatformUser() || $authUser->can('staff.delete'));
        $canAddStaff = $authUser && ($authUser->isPlatformUser() || $authUser->can('staff.add'));
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-id-card"></i>
                            Staff
                        </h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($canViewStaff)
                            <a href="{{ route('admin.export.staff') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-download me-1"></i> Export
                            </a>
                        @endif
                        @if ($canAddStaff)
                            <a href="{{ route('admin.staff.add') }}" class="btn admin-btn-primary">
                                <i class="bx bx-plus me-1"></i> Add Staff
                            </a>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Staff</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.staff')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-status="true"
                    :statuses="['active' => 'Active', 'inactive' => 'Inactive']"
                    :institutes="$institutes"
                    :branches="$branches"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Staff List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1050px;">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Sr. No.</th>
                                <th>Role</th>
                                <th>User Id</th>
                                <th>Name</th>
                                <th>Email Address</th>
                                <th>Contact No.</th>
                                <th>Branch</th>
                                <th style="width: 100px;">Status</th>
                                <th class="text-center" style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staff as $i => $row)
                                @php
                                    $roleKey = $row->user_id.':'.$row->institute_id;
                                    $roleName = $roleNames[$roleKey] ?? null;
                                @endphp
                                <tr data-id="{{ $row->id }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <span class="stu-method-pill">{{ $roleName ? \Illuminate\Support\Str::headline($roleName) : ($row->designation ?: '—') }}</span>
                                    </td>
                                    <td class="fw-semibold">{{ $row->employee_code ?: '—' }}</td>
                                    <td class="fw-semibold">{{ $row->user?->name }}</td>
                                    <td>{{ $row->user?->email }}</td>
                                    <td>{{ $row->user?->phone ?: '—' }}</td>
                                    <td>{{ $row->primaryBranch()?->name ?? '—' }}</td>
                                    <td>
                                        @if ($row->isActive())
                                            <span class="admin-status-pill is-active" data-role="status-pill">Active</span>
                                        @else
                                            <span class="admin-status-pill is-muted" data-role="status-pill">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($canShowStaff)
                                            <a href="{{ route('admin.staff.show', $row) }}"
                                                class="admin-action-icon" title="View Detail">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        @endif

                                        @if ($canEditStaff)
                                            <a href="{{ route('admin.staff.edit', $row) }}"
                                                class="admin-action-icon" title="Edit">
                                                <i class="bx bx-pencil"></i>
                                            </a>
                                        @endif

                                        @if ($canStatusStaff)
                                            <button type="button"
                                                class="admin-action-icon btn-toggle-staff"
                                                title="{{ $row->isActive() ? 'Deactivate' : 'Activate' }}"
                                                data-url="{{ route('admin.staff.toggle', $row) }}"
                                                data-active="{{ $row->isActive() ? '1' : '0' }}">
                                                <i class="bx {{ $row->isActive() ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                            </button>
                                        @endif

                                        @if ($canDeleteStaff)
                                            <button type="button"
                                                class="admin-action-icon btn-delete-staff"
                                                title="Delete"
                                                data-url="{{ route('admin.staff.destroy', $row) }}">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        No staff yet.
                                        @if ($canAddStaff)
                                            <a href="{{ route('admin.staff.add') }}">Add branch manager / staff</a>.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            document.querySelectorAll('.btn-toggle-staff').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    try {
                        const data = await AdminAjax.post(btn.dataset.url);
                        AdminAjax.applyStatusToggle(btn, AdminAjax.statusFrom(data) === 'active');
                        await AdminAjax.success(data.message || 'Status updated.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });

            document.querySelectorAll('.btn-delete-staff').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Delete staff?',
                        text: 'Their login will be removed.',
                        confirmText: 'Yes, delete'
                    });
                    if (!ok) return;
                    try {
                        const data = await AdminAjax.destroy(btn.dataset.url);
                        AdminAjax.removeRow(btn.closest('tr'));
                        await AdminAjax.success(data.message || 'Deleted.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });
        })();
    </script>
@endpush
