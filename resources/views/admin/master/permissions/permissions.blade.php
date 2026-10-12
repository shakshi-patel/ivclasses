@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-key"></i>
                            Permissions
                        </h4>
                        <p class="text-muted mb-0 small">Source of truth: <code>config/permission_matrix.php</code> → Sync</p>
                    </div>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('permissions.sync'))
                        <button type="button" class="btn admin-btn-primary" id="btnSyncPermissions">
                            <i class="bx bx-refresh me-1"></i> Sync Matrix
                        </button>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">Master</li>
                        <li class="breadcrumb-item">Role &amp; Permissions</li>
                        <li class="breadcrumb-item active" aria-current="page">Permissions</li>
                    </ol>
                </nav>

                <div class="admin-table-wrap mt-4">
                    <div class="admin-list-card-bar">
                        <h5>Permissions List ({{ $permissions->count() }})</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Sr No.</th>
                                    <th>Permission Name</th>
                                    <th>Module</th>
                                    <th>Action</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 140px;">Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($permissions as $i => $perm)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-semibold"><code>{{ $perm->name }}</code></td>
                                        <td>{{ $perm->module ?: '—' }}</td>
                                        <td>{{ $perm->description ?: $perm->action }}</td>
                                        <td>
                                            @if ($perm->is_active)
                                                <span class="admin-status-pill is-active">Active</span>
                                            @else
                                                <span class="admin-status-pill is-muted">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($perm->created_at)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No permissions yet. Click <strong>Sync Matrix</strong>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            document.getElementById('btnSyncPermissions')?.addEventListener('click', async function() {
                const ok = await AdminAjax.confirm({
                    title: 'Sync permissions?',
                    text: 'Sync from permission_matrix.php. Developer role will get all active permissions.',
                    confirmText: 'Yes, sync'
                });
                if (!ok) return;
                const btn = this;
                btn.disabled = true;
                try {
                    const data = await AdminAjax.post(@json(route('admin.master.permissions.sync')));
                    const extra = data.data
                        ? (' Created: ' + data.data.created + ', Updated: ' + data.data.updated)
                        : '';
                    await AdminAjax.success((data.message || 'Synced.') + extra);
                    window.location.reload();
                } catch (e) {
                    await AdminAjax.error(e.message);
                    btn.disabled = false;
                }
            });
        })();
    </script>
@endpush
