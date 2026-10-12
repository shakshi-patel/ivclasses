@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-git-branch"></i>
                            Branch
                        </h4>
                    </div>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('branch.add'))
                        <a href="{{ route('admin.master.branch.add') }}" class="btn admin-btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Branch
                        </a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">Master</li>
                        <li class="breadcrumb-item active" aria-current="page">Branch</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.master.branch')"
                    :show-institute="$isPlatform"
                    :show-status="true"
                    :statuses="['active' => 'Active', 'inactive' => 'Inactive']"
                    :institutes="$institutes"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Branch List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1100px;">
                            <thead>
                                <tr>
                                    <th>Sr. No</th>
                                    <th>Branch Name</th>
                                    <th>Institute</th>
                                    <th>Contact / Manager</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($branches as $i => $row)
                                    <tr data-branch-id="{{ $row->id }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $row->name }}</td>
                                        <td>{{ $row->institute?->name ?? '—' }}</td>
                                        <td>{{ $row->manager?->name ?: ($row->contact_person ?: '—') }}</td>
                                        <td>{{ $row->phone ?: '—' }}</td>
                                        <td>{{ $row->email ?: '—' }}</td>
                                        <td>
                                            @php
                                                $addr = collect([$row->address, $row->city, $row->state, $row->pincode])
                                                    ->filter()
                                                    ->implode(', ');
                                            @endphp
                                            {{ $addr !== '' ? $addr : '—' }}
                                        </td>
                                        <td>
                                            @if ($row->status === 'active')
                                                <span class="admin-status-pill is-active" data-role="status-pill">Active</span>
                                            @else
                                                <span class="admin-status-pill is-muted" data-role="status-pill">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('branch.edit'))
                                                <a href="{{ route('admin.master.branch.edit', $row) }}"
                                                    class="admin-action-icon" title="Edit">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('branch.status'))
                                                <button type="button" class="admin-action-icon btn-toggle-branch"
                                                    title="{{ $row->status === 'active' ? 'Deactivate' : 'Activate' }}"
                                                    data-url="{{ route('admin.master.branch.toggle', $row) }}">
                                                    <i class="bx {{ $row->status === 'active' ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('branch.delete'))
                                                <button type="button" class="admin-action-icon btn-delete-branch"
                                                    title="Delete"
                                                    data-url="{{ route('admin.master.branch.destroy', $row) }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            No branches yet.
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('branch.add'))
                                                <a href="{{ route('admin.master.branch.add') }}">Add your first branch</a>.
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
            document.querySelectorAll('.btn-toggle-branch').forEach(function(btn) {
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

            document.querySelectorAll('.btn-delete-branch').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Delete branch?',
                        text: 'This will soft-delete the branch.',
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
