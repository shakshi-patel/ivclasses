@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-envelope"></i>
                            Email Templates
                        </h4>
                        <p class="text-muted small mb-0">
                            Edit subject and body for system emails. Triggers stay in code; use <code>@{{variable}}</code> placeholders.
                        </p>
                    </div>
                    <a href="{{ route('admin.email_templates.create') }}" class="btn admin-btn-primary">
                        <i class="bx bx-plus me-1"></i> Add Template
                    </a>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Email Templates</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Templates</h5>
                    </div>
                    <table class="table admin-list-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Sr</th>
                                <th>Name</th>
                                <th>Key</th>
                                <th>Subject</th>
                                <th style="width: 100px;">Status</th>
                                <th class="text-center" style="width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $row['name'] }}</div>
                                        <div class="text-muted small">{{ $row['description'] }}</div>
                                    </td>
                                    <td><code>{{ $row['key'] }}</code></td>
                                    <td class="small">{{ \Illuminate\Support\Str::limit($row['subject'], 60) }}</td>
                                    <td>
                                        <span class="admin-status-pill {{ $row['status'] === 'active' ? 'is-active' : 'is-muted' }}">
                                            {{ ucfirst($row['status']) }}
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('admin.email_templates.show', $row['key']) }}"
                                            class="admin-action-icon" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <a href="{{ route('admin.email_templates.edit', $row['key']) }}"
                                            class="admin-action-icon" title="Edit">
                                            <i class="bx bx-pencil"></i>
                                        </a>
                                        <button type="button"
                                            class="admin-action-icon border-0 bg-transparent p-0 btn-email-tpl-del"
                                            title="Delete"
                                            data-url="{{ route('admin.email_templates.destroy', $row['key']) }}">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No templates yet.</td>
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
            document.querySelectorAll('.btn-email-tpl-del').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Delete email template?',
                        text: 'This will soft-delete the template. You can re-seed system templates later if needed.',
                        confirmText: 'Yes, delete',
                        icon: 'warning'
                    });
                    if (!ok) return;
                    try {
                        const data = await AdminAjax.destroy(btn.getAttribute('data-url'));
                        AdminAjax.removeRow(btn.closest('tr'));
                        await AdminAjax.success(data.message || 'Email template deleted.');
                    } catch (e) {
                        await AdminAjax.error(e.message || 'Delete failed.');
                    }
                });
            });
        })();
    </script>
@endpush
