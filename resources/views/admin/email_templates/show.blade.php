@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-show"></i>
                            View Email Template
                        </h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.email_templates.edit', $template->key) }}" class="btn admin-btn-primary">
                            <i class="bx bx-pencil me-1"></i> Edit
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-email-tpl-del"
                            data-url="{{ route('admin.email_templates.destroy', $template->key) }}"
                            data-redirect="{{ route('admin.email_templates.index') }}">
                            <i class="bx bx-trash me-1"></i> Delete
                        </button>
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.email_templates.index') }}">Email Templates</a></li>
                        <li class="breadcrumb-item active">{{ $template->name }}</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.email_templates.index') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $template->name }}</div>
                        <div class="stu-form-body">
                            <p class="text-muted small mb-3">{{ $meta['description'] ?? '' }}</p>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="text-muted small">Key</div>
                                    <code>{{ $template->key }}</code>
                                    @if ($isSystem)
                                        <span class="badge bg-label-primary ms-1">System</span>
                                    @else
                                        <span class="badge bg-label-secondary ms-1">Custom</span>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small">Status</div>
                                    <span class="admin-status-pill {{ $template->status === 'active' ? 'is-active' : 'is-muted' }}">
                                        {{ ucfirst($template->status) }}
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small">Created by</div>
                                    <div>{{ $template->creator?->name ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">Created</div>
                                    <div>{{ optional($template->created_at)->format('d M Y, h:i A') ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">Updated</div>
                                    <div>
                                        {{ optional($template->updated_at)->format('d M Y, h:i A') ?? '—' }}
                                        @if ($template->updater)
                                            <span class="text-muted small">({{ $template->updater->name }})</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="text-muted small">Subject</div>
                                    <div class="fw-semibold">{{ $template->subject }}</div>
                                </div>
                            </div>

                            @if (! empty($meta['variables']))
                                <div class="mb-3">
                                    <div class="fw-semibold small mb-1" style="color: var(--iv-primary);">Variables</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($meta['variables'] as $var => $label)
                                            <span class="badge bg-label-secondary" title="{{ $label }}">
                                                {{ '{'.'{'.$var.'}'.'}' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="fw-semibold small mb-2" style="color: var(--iv-primary);">Preview</div>
                            <div class="border rounded p-3 bg-white" style="max-height: 520px; overflow: auto;">
                                {!! $template->body_html !!}
                            </div>
                            <div class="form-text mt-2">Placeholders like @{{name}} show as-is until the email is sent with real data.</div>
                        </div>
                    </div>
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
                        text: 'This will soft-delete the template.',
                        confirmText: 'Yes, delete',
                        icon: 'warning'
                    });
                    if (!ok) return;
                    try {
                        const data = await AdminAjax.destroy(btn.getAttribute('data-url'));
                        await AdminAjax.success(data.message || 'Email template deleted.');
                        window.location.href = btn.getAttribute('data-redirect') || @json(route('admin.email_templates.index'));
                    } catch (e) {
                        await AdminAjax.error(e.message || 'Delete failed.');
                    }
                });
            });
        })();
    </script>
@endpush
