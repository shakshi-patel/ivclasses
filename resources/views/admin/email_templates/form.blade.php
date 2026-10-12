@extends('admin.layouts.layout')

@section('content')
    @php
        $isCreate = ($mode ?? 'edit') === 'create';
        $bodyHtml = old('body_html', $template->body_html);
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx {{ $isCreate ? 'bx-plus' : 'bx-pencil' }}"></i>
                    {{ $isCreate ? 'Add Email Template' : 'Edit Email Template' }}
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.email_templates.index') }}">Email Templates</a></li>
                        <li class="breadcrumb-item active">{{ $isCreate ? 'Add' : $template->name }}</li>
                    </ol>
                </nav>

                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.email_templates.index') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isCreate ? 'New template' : $template->name }}</div>
                        <div class="stu-form-body">
                            <p class="text-muted small mb-3">{{ $meta['description'] ?? '' }}</p>

                            @unless ($isCreate)
                                <p class="mb-3">
                                    <span class="text-muted small">Key:</span>
                                    <code>{{ $template->key }}</code>
                                    @if ($isSystem ?? false)
                                        <span class="badge bg-label-primary ms-1">System</span>
                                    @endif
                                </p>
                            @endunless

                            @if (! empty($meta['variables']))
                                <div class="mb-3">
                                    <div class="fw-semibold small mb-1" style="color: var(--iv-primary);">Available variables</div>
                                    <div class="d-flex flex-wrap gap-1" id="emailTplVars">
                                        @foreach ($meta['variables'] as $var => $label)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary btn-insert-var"
                                                data-var="{{ $var }}"
                                                title="{{ $label }}">
                                                {{ '{'.'{'.$var.'}'.'}' }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <div class="form-text">Click a variable to insert at the cursor (Visual or Source).</div>
                                </div>
                            @endif

                            <form method="POST"
                                action="{{ $isCreate ? route('admin.email_templates.store') : route('admin.email_templates.update', $template->key) }}"
                                id="emailTemplateForm">
                                @csrf
                                @unless ($isCreate)
                                    @method('PUT')
                                @endunless

                                <div class="row g-3">
                                    @if ($isCreate)
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                Key <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="key" class="form-control font-monospace" required maxlength="80"
                                                pattern="[a-z][a-z0-9_]*(\.[a-z0-9_]+)*"
                                                value="{{ old('key') }}"
                                                placeholder="e.g. custom.welcome">
                                            <div class="form-text">Lowercase, dots allowed. Used in code when sending.</div>
                                        </div>
                                    @endif
                                    <div class="{{ $isCreate ? 'col-md-6' : 'col-md-8' }}">
                                        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                            Display name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="name" class="form-control" required maxlength="150"
                                            value="{{ old('name', $template->name) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                            Status <span class="text-danger">*</span>
                                        </label>
                                        <select name="status" class="form-select" required data-no-search>
                                            <option value="active" @selected(old('status', $template->status) === 'active')>Active</option>
                                            <option value="inactive" @selected(old('status', $template->status) === 'inactive')>Inactive</option>
                                        </select>
                                        @if ($isSystem ?? false)
                                            <div class="form-text">Inactive uses the code Blade fallback.</div>
                                        @endif
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                            Subject <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="subject" class="form-control" required maxlength="255"
                                            value="{{ old('subject', $template->subject) }}"
                                            placeholder="e.g. Your IV Classes login details — @{{institute_name}}">
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                            <label class="form-label fw-semibold mb-0" style="color: var(--iv-primary);">
                                                Body <span class="text-danger">*</span>
                                            </label>
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Body editor mode">
                                                <button type="button" class="btn btn-outline-secondary active" id="emailTplModeVisual"
                                                    data-mode="visual">Visual</button>
                                                <button type="button" class="btn btn-outline-secondary" id="emailTplModeSource"
                                                    data-mode="source">HTML source</button>
                                            </div>
                                        </div>

                                        <div id="emailTplVisualWrap" class="email-tpl-editor-wrap border rounded">
                                            <div id="emailTplEditor"></div>
                                        </div>

                                        <textarea name="body_html" id="emailTplBody"
                                            class="form-control font-monospace d-none"
                                            rows="18"
                                            required>{{ $bodyHtml }}</textarea>
                                        <div class="form-text">
                                            Visual editor for everyday edits. Use HTML source only for advanced email layout.
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                                    <a href="{{ route('admin.email_templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn admin-btn-primary">
                                        {{ $isCreate ? 'Create Template' : 'Save Template' }}
                                    </button>
                                </div>
                            </form>

                            @if (! $isCreate && ($isSystem ?? false))
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-3 btn-email-tpl-reset"
                                    data-url="{{ route('admin.email_templates.reset', $template->key) }}">
                                    <i class="bx bx-reset me-1"></i> Reset to default
                                </button>
                            @endif

                            @if (! $isCreate)
                                <button type="button" class="btn btn-outline-danger btn-sm mt-3 btn-email-tpl-del"
                                    data-url="{{ route('admin.email_templates.destroy', $template->key) }}"
                                    data-redirect="{{ route('admin.email_templates.index') }}">
                                    <i class="bx bx-trash me-1"></i> Delete template
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/quill/typography.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-assets/vendor/libs/quill/editor.css') }}" />
    <style>
        .email-tpl-editor-wrap {
            background: #fff;
            overflow: hidden;
        }
        .email-tpl-editor-wrap .ql-toolbar.ql-snow {
            border: 0;
            border-bottom: 1px solid #e6ebe9;
        }
        .email-tpl-editor-wrap .ql-container.ql-snow {
            border: 0;
            min-height: 320px;
        }
        .email-tpl-editor-wrap .ql-editor {
            min-height: 300px;
            font-size: 15px;
            line-height: 1.6;
        }
        #emailTplBody.is-source-visible {
            display: block !important;
            min-height: 320px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('admin-assets/vendor/libs/quill/quill.js') }}"></script>
    <script>
        (function() {
            const body = document.getElementById('emailTplBody');
            const visualWrap = document.getElementById('emailTplVisualWrap');
            const modeVisualBtn = document.getElementById('emailTplModeVisual');
            const modeSourceBtn = document.getElementById('emailTplModeSource');
            const form = document.getElementById('emailTemplateForm');
            const isCreate = @json($isCreate);
            let mode = 'visual';
            let quill = null;

            if (typeof Quill === 'undefined' || !body) {
                return;
            }

            const toolbar = [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean']
            ];

            quill = new Quill('#emailTplEditor', {
                theme: 'snow',
                placeholder: 'Write the email body…',
                modules: { toolbar: toolbar }
            });

            // Load stored HTML without wiping empty Quill default
            const initial = (body.value || '').trim();
            if (initial !== '') {
                quill.clipboard.dangerouslyPasteHTML(initial);
            }

            function syncBodyFromVisual() {
                if (!quill) return;
                const html = quill.root.innerHTML;
                const plain = quill.getText().replace(/\n/g, '').trim();
                body.value = plain === '' ? '' : html;
            }

            function syncVisualFromBody() {
                if (!quill) return;
                quill.setText('');
                const html = (body.value || '').trim();
                if (html !== '') {
                    quill.clipboard.dangerouslyPasteHTML(html);
                }
            }

            function setMode(next) {
                if (next === mode) return;

                if (next === 'source') {
                    syncBodyFromVisual();
                    visualWrap.classList.add('d-none');
                    body.classList.remove('d-none');
                    body.classList.add('is-source-visible');
                    modeVisualBtn.classList.remove('active');
                    modeSourceBtn.classList.add('active');
                } else {
                    visualWrap.classList.remove('d-none');
                    body.classList.add('d-none');
                    body.classList.remove('is-source-visible');
                    syncVisualFromBody();
                    modeSourceBtn.classList.remove('active');
                    modeVisualBtn.classList.add('active');
                }

                mode = next;
            }

            modeVisualBtn?.addEventListener('click', function() { setMode('visual'); });
            modeSourceBtn?.addEventListener('click', function() { setMode('source'); });

            function insertVariable(token) {
                if (mode === 'source') {
                    const start = body.selectionStart || body.value.length;
                    const end = body.selectionEnd || start;
                    body.value = body.value.slice(0, start) + token + body.value.slice(end);
                    body.focus();
                    body.selectionStart = body.selectionEnd = start + token.length;
                    return;
                }

                const range = quill.getSelection(true);
                const index = range ? range.index : quill.getLength();
                quill.insertText(index, token, 'user');
                quill.setSelection(index + token.length, 0, 'user');
                quill.focus();
            }

            document.querySelectorAll('.btn-insert-var').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const token = '{' + '{' + btn.getAttribute('data-var') + '}' + '}';
                    insertVariable(token);
                });
            });

            form?.addEventListener('submit', async function(e) {
                e.preventDefault();

                if (mode === 'visual') {
                    syncBodyFromVisual();
                }

                if (!((body.value || '').trim())) {
                    if (window.AdminAjax && AdminAjax.error) {
                        await AdminAjax.error('Body is required.');
                    } else {
                        alert('Body is required.');
                    }
                    return;
                }

                const btn = form.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;
                try {
                    const json = await AdminAjax.submitForm(form, {
                        redirectUrl: isCreate
                            ? null
                            : @json(route('admin.email_templates.index'))
                    });
                    if (isCreate && json && json.data && json.data.redirect) {
                        window.location.href = json.data.redirect;
                    }
                } catch (err) {
                    if (btn) btn.disabled = false;
                }
            });

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
                        window.location.href = btn.getAttribute('data-redirect')
                            || @json(route('admin.email_templates.index'));
                    } catch (e) {
                        await AdminAjax.error(e.message || 'Delete failed.');
                    }
                });
            });

            document.querySelectorAll('.btn-email-tpl-reset').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Reset to default?',
                        text: 'Subject and body will be restored from the system default.',
                        confirmText: 'Yes, reset',
                        icon: 'warning'
                    });
                    if (!ok) return;
                    try {
                        const data = await AdminAjax.post(btn.getAttribute('data-url'));
                        await AdminAjax.success(data.message || 'Template reset to default.');
                        window.location.reload();
                    } catch (e) {
                        await AdminAjax.error(e.message || 'Reset failed.');
                    }
                });
            });
        })();
    </script>
@endpush
