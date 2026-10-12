@extends('admin.layouts.layout')

@section('content')
    @php
        $authUser = auth()->user();
        $canViewTeacher = $authUser && ($authUser->isPlatformUser() || $authUser->can('teacher.view'));
        $canShowTeacher = $authUser && ($authUser->isPlatformUser() || $authUser->can('teacher.show'));
        $canEditTeacher = $authUser && ($authUser->isPlatformUser() || $authUser->can('teacher.edit'));
        $canStatusTeacher = $authUser && ($authUser->isPlatformUser() || $authUser->can('teacher.status'));
        $canDeleteTeacher = $authUser && ($authUser->isPlatformUser() || $authUser->can('teacher.delete'));
        $canAddTeacher = $authUser && ($authUser->isPlatformUser() || $authUser->can('teacher.add'));
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-user-voice"></i>
                            Teacher
                        </h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($canViewTeacher)
                            <a href="{{ route('admin.export.teachers') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-download me-1"></i> Export
                            </a>
                        @endif
                        @if ($canAddTeacher)
                            <a href="{{ route('admin.master.teacher.add') }}" class="btn admin-btn-primary">
                                <i class="bx bx-plus me-1"></i> Add Teacher
                            </a>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">Master</li>
                        <li class="breadcrumb-item active" aria-current="page">Teacher</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.master.teacher')"
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
                        <h5>Teacher List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1050px;">
                        <thead>
                            <tr>
                                <th>Sr. No.</th>
                                <th>Profile</th>
                                <th>Teacher Name</th>
                                <th>Branch Name</th>
                                <th>Contact Number</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teachers as $i => $row)
                                <tr data-id="{{ $row->id }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <img src="{{ $row->user?->avatar_path ? asset('storage/'.$row->user->avatar_path) : asset('admin-assets/img/avatars/1.png') }}"
                                            alt="" width="40" height="40" class="rounded-circle"
                                            style="object-fit: cover; border: 2px solid rgba(var(--iv-secondary-rgb), 0.4);">
                                    </td>
                                    <td class="fw-semibold">
                                        {{ $row->user?->name }}
                                        @if ($row->employee_code)
                                            <small class="text-muted d-block">{{ $row->employee_code }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $row->primaryBranch()?->name ?? '—' }}</td>
                                    <td>{{ $row->user?->phone ?: '—' }}</td>
                                    <td>{{ $row->user?->email }}</td>
                                    <td>
                                        @if ($row->isActive())
                                            <span class="admin-status-pill is-active" data-role="status-pill">Active</span>
                                        @else
                                            <span class="admin-status-pill is-muted" data-role="status-pill">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($canShowTeacher)
                                            <a href="{{ route('admin.master.teacher.show', $row) }}"
                                                class="admin-action-icon" title="View Detail">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        @endif

                                        @if ($canEditTeacher)
                                            <a href="{{ route('admin.master.teacher.edit', $row) }}"
                                                class="admin-action-icon" title="Edit">
                                                <i class="bx bx-pencil"></i>
                                            </a>
                                        @endif

                                        @if ($canStatusTeacher)
                                            <button type="button"
                                                class="admin-action-icon btn-toggle-teacher"
                                                title="{{ $row->isActive() ? 'Deactivate' : 'Activate' }}"
                                                data-url="{{ route('admin.master.teacher.toggle', $row) }}"
                                                data-active="{{ $row->isActive() ? '1' : '0' }}">
                                                <i class="bx {{ $row->isActive() ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                            </button>
                                        @endif

                                        @if ($canDeleteTeacher)
                                            <button type="button"
                                                class="admin-action-icon btn-delete-teacher"
                                                title="Delete"
                                                data-url="{{ route('admin.master.teacher.destroy', $row) }}">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No teachers yet.
                                        @if ($canAddTeacher)
                                            <a href="{{ route('admin.master.teacher.add') }}">Add your first teacher</a>.
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
            document.querySelectorAll('.btn-toggle-teacher').forEach(function(btn) {
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

            document.querySelectorAll('.btn-delete-teacher').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Delete teacher?',
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
