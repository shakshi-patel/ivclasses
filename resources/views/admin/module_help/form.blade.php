@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-{{ $isEdit ? 'pencil' : 'plus-circle' }}"></i>
                    {{ $isEdit ? 'Edit Guide' : 'Add Guide' }}
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.module_help.index') }}">Module Help</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit Guide' : 'Add Guide' }}</li>
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
                    <a href="{{ route('admin.module_help.index') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit Guide' : 'Add Guide' }}</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.module_help.update', $guide) : route('admin.module_help.store') }}"
                                enctype="multipart/form-data"
                                id="moduleHelpForm">
                                @csrf
                                @if ($isEdit)
                                    @method('PUT')
                                @endif

                                <div class="plan-form-section">
                                    <div class="plan-form-section-title">
                                        <i class="bx bx-info-circle"></i>
                                        <div>
                                            <strong>Guide details</strong>
                                            <span>Module, roles, title and intro for this help guide</span>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                Module <span class="text-danger">*</span>
                                            </label>
                                            <select name="module_key" class="form-select" required data-placeholder="Select module">
                                                <option value="">Select module</option>
                                                @foreach ($moduleOptions as $key => $label)
                                                    <option value="{{ $key }}" @selected(old('module_key', $guide->module_key ?? '') === $key)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                Show to roles <span class="text-danger">*</span>
                                            </label>
                                            <select name="role_names[]" class="form-select" multiple required
                                                data-placeholder="Select role(s)">
                                                @foreach ($roleOptions as $key => $label)
                                                    <option value="{{ $key }}" @selected(in_array($key, (array) $selectedRoles, true))>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">Guide auto-opens for these roles on first visit to the module.</div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                Guide title <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="title" class="form-control" required maxlength="180"
                                                value="{{ old('title', $guide->title ?? '') }}"
                                                placeholder="e.g. How to manage inquiries">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Sort order</label>
                                            <input type="number" name="sort_order" class="form-control" min="0" max="999"
                                                value="{{ old('sort_order', $guide->sort_order ?? 0) }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                Status <span class="text-danger">*</span>
                                            </label>
                                            <select name="status" class="form-select" required data-no-search>
                                                <option value="active" @selected(old('status', $guide->status ?? 'active') === 'active')>Active</option>
                                                <option value="inactive" @selected(old('status', $guide->status ?? '') === 'inactive')>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check mt-1">
                                                <input type="checkbox" class="form-check-input" id="isOnboarding"
                                                    name="is_onboarding" value="1"
                                                    @checked(old('is_onboarding', $guide->is_onboarding ?? false))>
                                                <label class="form-check-label fw-semibold" for="isOnboarding" style="color: var(--iv-primary);">
                                                    Institute first-login setup
                                                </label>
                                            </div>
                                            <div class="form-text">
                                                Only one active guide can be the setup. The institute admin must finish every compulsory step before the dashboard opens. A step finishes when that record is saved, not when Next is clicked. Optional steps stay as reminders and can be dismissed. Suggested order: Branch, Standard, Subject, Batch, then optional Teacher and Student. This guide does not replace per-page How it works.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">Short intro</label>
                                            <textarea name="intro" class="form-control" rows="2" maxlength="1000"
                                                placeholder="One-line help about what this guide covers">{{ old('intro', $guide->intro ?? '') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="plan-form-section mt-4">
                                    <div class="plan-form-section-title">
                                        <i class="bx bx-list-ol"></i>
                                        <div>
                                            <strong>Guide steps</strong>
                                            <span>Add steps with optional media. For first-login setup, mark each step compulsory or optional and choose what saving that record completes. Reorder with up / down.</span>
                                        </div>
                                    </div>

                                    <div id="moduleHelpSteps" class="module-help-steps"></div>

                                    <div class="mt-3">
                                        <button type="button" class="btn btn-sm admin-btn-primary" id="btnAddHelpStep">
                                            <i class="bx bx-plus me-1"></i> Add Step
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="{{ route('admin.module_help.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn admin-btn-primary">
                                        {{ $isEdit ? 'Update Guide' : 'Save Guide' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="moduleHelpStepTpl">
        <div class="module-help-step-card" data-step-index="__INDEX__">
            <div class="module-help-step-head">
                <div class="d-flex align-items-center gap-2">
                    <span class="module-help-step-badge">Step <span class="step-num">__NUM__</span></span>
                    <span class="text-muted small step-drag-hint">Use arrows to reorder</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-step-up" title="Move up">
                        <i class="bx bx-up-arrow-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-step-down" title="Move down">
                        <i class="bx bx-down-arrow-alt"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-step-remove" title="Remove step">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row g-3">
                <input type="hidden" name="steps[__INDEX__][id]" value="__ID__" class="step-id">
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                        Step title <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="steps[__INDEX__][title]" class="form-control step-title" required
                        value="__TITLE__" placeholder="Step title">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                        Media type <span class="text-danger">*</span>
                    </label>
                    <select name="steps[__INDEX__][media_type]" class="form-select step-media-type" required data-no-search>
                        <option value="none">None</option>
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                        <option value="embed">Embed URL</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">Description</label>
                    <textarea name="steps[__INDEX__][description]" class="form-control step-description" rows="2"
                        placeholder="Explain what to do in this step">__DESCRIPTION__</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">Setup rule</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input step-required" name="steps[__INDEX__][is_required]" value="1" __REQUIRED_CHECKED__>
                        <label class="form-check-label">Compulsory (no skip)</label>
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">Completes when</label>
                    <select name="steps[__INDEX__][completion_key]" class="form-select step-completion" data-no-search>
                        <option value="">Not a setup step</option>
                        @foreach (\App\Services\InstituteOnboardingService::COMPLETION_KEYS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Required on a first-login setup guide. The step is done when that record exists.</div>
                </div>
                <div class="col-md-6 step-field-image d-none">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">Upload image</label>
                    <input type="file" name="steps[__INDEX__][image]" class="form-control" accept="image/*">
                    <div class="form-text step-current-media d-none"></div>
                </div>
                <div class="col-md-6 step-field-video d-none">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">Upload video</label>
                    <input type="file" name="steps[__INDEX__][video]" class="form-control" accept="video/*">
                    <div class="form-text step-current-media d-none"></div>
                </div>
                <div class="col-md-6 step-field-embed d-none">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">Embed URL</label>
                    <input type="url" name="steps[__INDEX__][embed_url]" class="form-control step-embed-url"
                        value="__EMBED__" placeholder="https://www.youtube.com/embed/...">
                </div>
                <div class="col-md-6 step-field-caption d-none">
                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">Media caption <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="steps[__INDEX__][media_caption]" class="form-control step-caption"
                        value="__CAPTION__" placeholder="Short caption under media">
                </div>
                <div class="col-12">
                    <div class="module-help-step-cta-box">
                        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                            Page highlight <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="text" name="steps[__INDEX__][target]" class="form-control step-target"
                                    value="__TARGET__" maxlength="180"
                                    placeholder="CSS selector, e.g. .btn-add or #studentTable">
                            </div>
                            <div class="col-md-4">
                                <select name="steps[__INDEX__][placement]" class="form-select step-placement" data-no-search>
                                    <option value="bottom">Below</option>
                                    <option value="top">Above</option>
                                    <option value="left">Left</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-text mt-1">Shepherd points this step at that control. Leave the selector empty for a centered tip.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="module-help-step-cta-box">
                        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                            Step action button <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <input type="text" name="steps[__INDEX__][cta_label]" class="form-control step-cta-label"
                                    value="__CTA_LABEL__" maxlength="120"
                                    placeholder="Button text, e.g. Add inquiry">
                            </div>
                            <div class="col-md-7">
                                <input type="text" name="steps[__INDEX__][cta_url]" class="form-control step-cta-url"
                                    value="__CTA_URL__" maxlength="500"
                                    placeholder="App path (/admin/...) or https://...">
                            </div>
                        </div>
                        <div class="form-text mt-1">Shown in the help wizard for this step. Both fields required together.</div>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('styles')
    <style>
        .module-help-steps {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }
        .module-help-step-card {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            border-radius: 12px;
            background: #fff;
            padding: 1rem 1.1rem 1.15rem;
            box-shadow: 0 1px 0 rgba(15, 92, 77, 0.04);
        }
        .module-help-step-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.85rem;
            padding-bottom: 0.65rem;
            border-bottom: 1px dashed rgba(var(--iv-primary-rgb), 0.16);
        }
        .module-help-step-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            background: rgba(var(--iv-primary-rgb), 0.1);
            color: var(--iv-primary-dark);
            font-size: 0.78rem;
            font-weight: 700;
        }
        .module-help-step-cta-box {
            border: 1px dashed rgba(var(--iv-primary-rgb), 0.22);
            border-radius: 10px;
            padding: 0.85rem 1rem;
            background: rgba(var(--iv-primary-rgb), 0.03);
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const wrap = document.getElementById('moduleHelpSteps');
            const tpl = document.getElementById('moduleHelpStepTpl');
            const addBtn = document.getElementById('btnAddHelpStep');
            const form = document.getElementById('moduleHelpForm');
            const initialSteps = @json($steps);
            let seq = 0;

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function syncMediaFields(card) {
                const type = card.querySelector('.step-media-type')?.value || 'none';
                card.querySelector('.step-field-image')?.classList.toggle('d-none', type !== 'image');
                card.querySelector('.step-field-video')?.classList.toggle('d-none', type !== 'video');
                card.querySelector('.step-field-embed')?.classList.toggle('d-none', type !== 'embed');
                card.querySelector('.step-field-caption')?.classList.toggle('d-none', type === 'none');
            }

            function renumber() {
                const cards = [...wrap.querySelectorAll('.module-help-step-card')];
                cards.forEach(function(card, i) {
                    card.dataset.stepIndex = String(i);
                    const num = card.querySelector('.step-num');
                    if (num) num.textContent = String(i + 1);

                    card.querySelectorAll('[name]').forEach(function(el) {
                        el.name = el.name.replace(/steps\[\d+]/, 'steps[' + i + ']');
                    });

                    const up = card.querySelector('.btn-step-up');
                    const down = card.querySelector('.btn-step-down');
                    if (up) up.disabled = i === 0;
                    if (down) down.disabled = i === cards.length - 1;
                });
            }

            function bindCard(card) {
                card.querySelector('.step-media-type')?.addEventListener('change', function() {
                    syncMediaFields(card);
                });
                card.querySelector('.btn-step-remove')?.addEventListener('click', function() {
                    if (wrap.querySelectorAll('.module-help-step-card').length <= 1) {
                        if (window.AdminAjax && AdminAjax.toast) {
                            AdminAjax.toast('At least one step is required.');
                        } else {
                            alert('At least one step is required.');
                        }
                        return;
                    }
                    card.remove();
                    renumber();
                });
                card.querySelector('.btn-step-up')?.addEventListener('click', function() {
                    const prev = card.previousElementSibling;
                    if (prev) {
                        wrap.insertBefore(card, prev);
                        renumber();
                    }
                });
                card.querySelector('.btn-step-down')?.addEventListener('click', function() {
                    const next = card.nextElementSibling;
                    if (next) {
                        wrap.insertBefore(next, card);
                        renumber();
                    }
                });
                syncMediaFields(card);
            }

            function addStep(data) {
                data = data || {};
                const index = seq++;
                let html = tpl.innerHTML
                    .replace(/__INDEX__/g, String(index))
                    .replace(/__NUM__/g, String(index + 1))
                    .replace(/__ID__/g, escapeHtml(data.id ?? ''))
                    .replace(/__TITLE__/g, escapeHtml(data.title ?? ''))
                    .replace(/__DESCRIPTION__/g, escapeHtml(data.description ?? ''))
                    .replace(/__EMBED__/g, escapeHtml(data.embed_url ?? ''))
                    .replace(/__CAPTION__/g, escapeHtml(data.media_caption ?? ''))
                    .replace(/__CTA_LABEL__/g, escapeHtml(data.cta_label ?? ''))
                    .replace(/__CTA_URL__/g, escapeHtml(data.cta_url ?? ''))
                    .replace(/__TARGET__/g, escapeHtml(data.target ?? ''))
                    .replace(/__REQUIRED_CHECKED__/g, (data.is_required === true || data.is_required === 1 || data.is_required === '1') ? 'checked' : '');

                const holder = document.createElement('div');
                holder.innerHTML = html.trim();
                const card = holder.firstElementChild;
                wrap.appendChild(card);

                const typeEl = card.querySelector('.step-media-type');
                if (typeEl) {
                    typeEl.value = data.media_type || 'none';
                }
                const placeEl = card.querySelector('.step-placement');
                if (placeEl) {
                    placeEl.value = data.placement || 'bottom';
                }
                const completionEl = card.querySelector('.step-completion');
                if (completionEl) {
                    completionEl.value = data.completion_key || '';
                }

                if (data.media_original_name || data.media_url) {
                    card.querySelectorAll('.step-current-media').forEach(function(el) {
                        el.classList.remove('d-none');
                        if (data.media_url) {
                            el.innerHTML = 'Current: <a href="' + escapeHtml(data.media_url) + '" target="_blank" rel="noopener">' +
                                escapeHtml(data.media_original_name || 'View file') + '</a>';
                        } else {
                            el.textContent = 'Current: ' + (data.media_original_name || '');
                        }
                    });
                }

                if (typeof window.initAdminSearchableSelects === 'function') {
                    window.initAdminSearchableSelects(card);
                }

                bindCard(card);
                renumber();
            }

            initialSteps.forEach(function(step) {
                addStep(step);
            });

            addBtn?.addEventListener('click', function() {
                addStep({ media_type: 'none' });
            });

            form?.addEventListener('submit', async function(e) {
                e.preventDefault();
                renumber();
                const btn = form.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;
                try {
                    await AdminAjax.submitForm(form, {
                        redirectUrl: @json(route('admin.module_help.index'))
                    });
                } catch (err) {
                    if (btn) btn.disabled = false;
                }
            });
        })();
    </script>
@endpush
