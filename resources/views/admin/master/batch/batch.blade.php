@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-group"></i>
                            Batches
                        </h4>
                    </div>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('batch.add'))
                        <a href="{{ route('admin.master.batch.add') }}" class="btn admin-btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Batch
                        </a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Master</li>
                        <li class="breadcrumb-item active" aria-current="page">Batches</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.master.batch')"
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
                        <h5>Batch List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1050px;">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    @if (auth()->user()?->isPlatformUser())
                                        <th>Institute</th>
                                    @endif
                                    <th>Batch &amp; Standard</th>
                                    <th>Branch</th>
                                    <th>Batch Timing</th>
                                    <th>Subjects</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($batches as $i => $row)
                                    <tr data-id="{{ $row->id }}">
                                        <td data-role="sr">{{ $i + 1 }}</td>
                                        @if (auth()->user()?->isPlatformUser())
                                            <td>{{ $row->institute?->name ?? '—' }}</td>
                                        @endif
                                        <td>
                                            <div class="fw-semibold">{{ $row->name }}</div>
                                            <div class="text-muted small">{{ $row->standard?->name ?? '—' }}</div>
                                        </td>
                                        <td>{{ $row->branch?->name ?? '—' }}</td>
                                        <td>{{ $row->timingLabel() }}</td>
                                        <td>{{ $row->subjects->pluck('name')->join(', ') ?: '—' }}</td>
                                        <td>
                                            @if ($row->isActive())
                                                <span class="admin-status-pill is-active" data-role="status-pill">Active</span>
                                            @else
                                                <span class="admin-status-pill is-muted" data-role="status-pill">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('batch.show'))
                                                <a href="{{ route('admin.master.batch.show', $row) }}"
                                                    class="admin-action-icon" title="View Detail">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('batch.edit'))
                                                <a href="{{ route('admin.master.batch.edit', $row) }}"
                                                    class="admin-action-icon" title="Edit">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('batch.status'))
                                                <button type="button" class="admin-action-icon btn-toggle-batch"
                                                    title="{{ $row->status === 'active' ? 'Deactivate' : 'Activate' }}"
                                                    data-url="{{ route('admin.master.batch.toggle', $row) }}">
                                                    <i class="bx {{ $row->status === 'active' ? 'bx-user-x' : 'bx-user-check' }}"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('batch.delete'))
                                                <button type="button" class="admin-action-icon btn-delete-batch"
                                                    title="Delete"
                                                    data-url="{{ route('admin.master.batch.destroy', $row) }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->user()?->isPlatformUser() ? 8 : 7 }}" class="text-center text-muted py-4">
                                            No batches yet.
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('batch.add'))
                                                <a href="{{ route('admin.master.batch.add') }}">Add your first batch</a>.
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
            document.querySelectorAll('.btn-toggle-batch').forEach(function(btn) {
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

            document.querySelectorAll('.btn-delete-batch').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Delete batch?',
                        text: 'This will soft-delete the batch.',
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
