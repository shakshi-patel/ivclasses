@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = (bool) $plan;
        $selected = $plan?->modules_json ?? [];
        $modeAll = in_array('*', $selected, true);
        $coreLabels = $coreLabels ?? '';
        $benefitPoints = old('benefits', $plan?->benefits_json ?? ['', '', '']);
        if (! is_array($benefitPoints) || $benefitPoints === []) {
            $benefitPoints = ['', '', ''];
        }
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-{{ $isEdit ? 'pencil' : 'plus-circle' }}"></i>
                    {{ $isEdit ? 'Edit Plan' : 'Add Plan' }}
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit Plan' : 'Add Plan' }}</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit Plan' : 'Add Plan' }}</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.plans.update', $plan) : route('admin.plans.store') }}"
                                id="planForm">
                                @csrf
                                @if ($isEdit)
                                    @method('PUT')
                                @endif

                                <div class="plan-form-section">
                                    <div class="plan-form-section-title">
                                        <i class="bx bx-info-circle"></i>
                                        <div>
                                            <strong>Basic details</strong>
                                            <span>Code, name and pricing for this subscription plan</span>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Code <span class="req">*</span></label>
                                            <input type="text" name="code" class="form-control"
                                                value="{{ old('code', $plan?->code) }}" required
                                                placeholder="e.g. starter"
                                                {{ $isEdit && $plan?->code === 'trial' ? 'readonly' : '' }}>
                                            <div class="form-text">Unique slug — letters, numbers, dash</div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Name <span class="req">*</span></label>
                                            <input type="text" name="name" class="form-control"
                                                value="{{ old('name', $plan?->name) }}" required
                                                placeholder="Plan display name">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="2"
                                                placeholder="Short summary shown on pricing / billing">{{ old('description', $plan?->description) }}</textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Monthly price (₹) <span class="req">*</span></label>
                                            <input type="number" step="0.01" min="0" name="price_monthly"
                                                class="form-control"
                                                value="{{ old('price_monthly', $plan?->price_monthly ?? 0) }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Yearly price (₹) <span class="req">*</span></label>
                                            <input type="number" step="0.01" min="0" name="price_yearly"
                                                class="form-control"
                                                value="{{ old('price_yearly', $plan?->price_yearly ?? 0) }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Currency</label>
                                            <input type="text" name="currency" class="form-control" maxlength="3"
                                                value="{{ old('currency', $plan?->currency ?? 'INR') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="plan-form-section">
                                    <div class="plan-form-section-title">
                                        <i class="bx bx-slider-alt"></i>
                                        <div>
                                            <strong>Limits &amp; visibility</strong>
                                            <span>Leave max fields blank for unlimited</span>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Trial days</label>
                                            <input type="number" min="0" name="trial_days" class="form-control"
                                                value="{{ old('trial_days', $plan?->trial_days ?? 14) }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Sort order</label>
                                            <input type="number" min="0" name="sort_order" class="form-control"
                                                value="{{ old('sort_order', $plan?->sort_order ?? 0) }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Status <span class="req">*</span></label>
                                            <select name="status" class="form-select" required>
                                                <option value="active" @selected(old('status', $plan?->status ?? 'active') === 'active')>Active</option>
                                                <option value="inactive" @selected(old('status', $plan?->status) === 'inactive')>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Max branches</label>
                                            <input type="number" min="1" name="max_branches" class="form-control"
                                                value="{{ old('max_branches', $plan?->max_branches) }}"
                                                placeholder="Unlimited">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Max students</label>
                                            <input type="number" min="1" name="max_students" class="form-control"
                                                value="{{ old('max_students', $plan?->max_students) }}"
                                                placeholder="Unlimited">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Max staff</label>
                                            <input type="number" min="1" name="max_staff" class="form-control"
                                                value="{{ old('max_staff', $plan?->max_staff) }}"
                                                placeholder="Unlimited">
                                        </div>
                                        <div class="col-12">
                                            <label class="plan-toggle">
                                                <input type="checkbox" name="is_public" value="1" id="is_public"
                                                    @checked(old('is_public', $plan?->is_public ?? true))>
                                                <span>
                                                    <strong>Public plan</strong>
                                                    <small>Show on website pricing and institute billing upgrade</small>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="plan-form-section">
                                    <div class="plan-form-section-title">
                                        <i class="bx bx-list-plus"></i>
                                        <div>
                                            <strong>Benefit points</strong>
                                            <span>Shown on register / pricing plan cards — add with +</span>
                                        </div>
                                    </div>
                                    <div id="planBenefitsList" class="plan-benefits-list">
                                        @foreach ($benefitPoints as $i => $point)
                                            <div class="plan-benefit-row">
                                                <span class="plan-benefit-num">{{ $i + 1 }}</span>
                                                <input type="text" name="benefits[]" class="form-control"
                                                    value="{{ $point }}" maxlength="200"
                                                    placeholder="e.g. Up to 200 students">
                                                <button type="button" class="btn btn-outline-danger btn-sm plan-benefit-remove" title="Remove">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-sm admin-btn-primary mt-2" id="planBenefitAdd">
                                        <i class="bx bx-plus me-1"></i> Add point
                                    </button>
                                </div>

                                <div class="plan-form-section mb-0">
                                    <div class="plan-form-section-title">
                                        <i class="bx bx-grid-alt"></i>
                                        <div>
                                            <strong>Feature modules</strong>
                                            <span>Core modules are always included: {{ $coreLabels }}…</span>
                                        </div>
                                    </div>

                                    <div class="plan-mode-pills mb-3">
                                        <label class="plan-mode-pill">
                                            <input type="radio" name="modules_mode" id="modAll" value="all"
                                                @checked(old('modules_mode', $modeAll ? 'all' : 'selected') === 'all')>
                                            <span><i class="bx bx-check-double"></i> All features</span>
                                        </label>
                                        <label class="plan-mode-pill">
                                            <input type="radio" name="modules_mode" id="modSelected" value="selected"
                                                @checked(old('modules_mode', $modeAll ? 'all' : 'selected') === 'selected')>
                                            <span><i class="bx bx-list-check"></i> Selected features</span>
                                        </label>
                                    </div>

                                    <div class="row g-2" id="moduleChecks">
                                        @foreach ($sellableModules as $key => $label)
                                            <div class="col-6 col-md-4 col-xl-3">
                                                <label class="plan-module-card">
                                                    <input class="module-check" type="checkbox" name="modules[]"
                                                        value="{{ $key }}"
                                                        @checked(in_array($key, old('modules', $modeAll ? [] : $selected), true))>
                                                    <span class="plan-module-card-body">
                                                        <i class="bx bx-check-circle"></i>
                                                        <strong>{{ $label }}</strong>
                                                        <small>{{ $key }}</small>
                                                    </span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="master-stu-form-actions">
                                    <button type="submit" class="btn btn-stu-primary" id="planSubmitBtn">
                                        <i class="bx bx-save me-1"></i>
                                        {{ $isEdit ? 'Update Plan' : 'Create Plan' }}
                                    </button>
                                    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .plan-form-section {
            padding: 1.25rem 0 1.5rem;
            border-bottom: 1px solid rgba(15, 92, 77, 0.08);
            margin-bottom: 1.25rem;
        }
        .plan-form-section-title {
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
            margin-bottom: 1.1rem;
        }
        .plan-form-section-title > i {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 92, 77, 0.08);
            color: var(--iv-primary, #0f5c4d);
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .plan-form-section-title strong {
            display: block;
            color: var(--iv-primary, #0f5c4d);
            font-size: 0.98rem;
        }
        .plan-form-section-title span {
            display: block;
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 0.15rem;
            line-height: 1.4;
        }
        .plan-toggle {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            padding: 0.9rem 1rem;
            border: 1px solid rgba(15, 92, 77, 0.12);
            border-radius: 12px;
            background: rgba(15, 92, 77, 0.03);
            cursor: pointer;
            margin: 0;
        }
        .plan-toggle input {
            margin-top: 0.2rem;
        }
        .plan-toggle strong {
            display: block;
            font-size: 0.92rem;
        }
        .plan-toggle small {
            display: block;
            color: #6c757d;
            margin-top: 0.15rem;
        }
        .plan-mode-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }
        .plan-mode-pill {
            margin: 0;
            cursor: pointer;
        }
        .plan-mode-pill input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .plan-mode-pill span {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 0.95rem;
            border-radius: 999px;
            border: 1px solid rgba(15, 92, 77, 0.18);
            background: #fff;
            color: var(--iv-primary, #0f5c4d);
            font-weight: 600;
            font-size: 0.86rem;
            transition: 0.15s ease;
        }
        .plan-mode-pill input:checked + span {
            background: var(--iv-primary, #0f5c4d);
            border-color: var(--iv-primary, #0f5c4d);
            color: #fff;
        }
        .plan-module-card {
            display: block;
            margin: 0;
            cursor: pointer;
            height: 100%;
        }
        .plan-module-card input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .plan-module-card-body {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            height: 100%;
            padding: 0.85rem 0.9rem;
            border-radius: 12px;
            border: 1px solid rgba(15, 92, 77, 0.12);
            background: #fff;
            transition: 0.15s ease;
            position: relative;
        }
        .plan-module-card-body > i {
            position: absolute;
            top: 0.65rem;
            right: 0.65rem;
            color: transparent;
            font-size: 1.05rem;
        }
        .plan-module-card-body strong {
            font-size: 0.88rem;
            color: #24312d;
            padding-right: 1.2rem;
        }
        .plan-module-card-body small {
            color: #8a9591;
            font-size: 0.72rem;
        }
        .plan-module-card input:checked + .plan-module-card-body {
            border-color: var(--iv-primary, #0f5c4d);
            background: rgba(15, 92, 77, 0.06);
            box-shadow: 0 0 0 1px rgba(15, 92, 77, 0.12);
        }
        .plan-module-card input:checked + .plan-module-card-body > i {
            color: var(--iv-primary, #0f5c4d);
        }
        .plan-module-card input:disabled + .plan-module-card-body {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .plan-benefits-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        .plan-benefit-row {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }
        .plan-benefit-num {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.75rem;
            font-weight: 800;
            background: rgba(15, 92, 77, 0.1);
            color: var(--iv-primary, #0f5c4d);
        }
        .plan-benefit-row .form-control {
            flex: 1;
        }
        .plan-benefit-remove {
            flex-shrink: 0;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            function syncModuleChecks() {
                var all = document.getElementById('modAll')?.checked;
                document.querySelectorAll('.module-check').forEach(function(cb) {
                    cb.disabled = !!all;
                    if (all) cb.checked = false;
                });
            }

            document.querySelectorAll('input[name="modules_mode"]').forEach(function(r) {
                r.addEventListener('change', syncModuleChecks);
            });
            syncModuleChecks();

            var list = document.getElementById('planBenefitsList');
            var addBtn = document.getElementById('planBenefitAdd');

            function renumberBenefits() {
                if (!list) return;
                list.querySelectorAll('.plan-benefit-row').forEach(function(row, i) {
                    var num = row.querySelector('.plan-benefit-num');
                    if (num) num.textContent = String(i + 1);
                });
            }

            function addBenefitRow(value) {
                if (!list) return;
                var row = document.createElement('div');
                row.className = 'plan-benefit-row';
                row.innerHTML =
                    '<span class="plan-benefit-num"></span>' +
                    '<input type="text" name="benefits[]" class="form-control" maxlength="200" placeholder="e.g. Up to 200 students">' +
                    '<button type="button" class="btn btn-outline-danger btn-sm plan-benefit-remove" title="Remove">' +
                    '<i class="bx bx-trash"></i></button>';
                var input = row.querySelector('input');
                if (input && value) input.value = value;
                list.appendChild(row);
                renumberBenefits();
                input?.focus();
            }

            addBtn?.addEventListener('click', function() {
                addBenefitRow('');
            });

            list?.addEventListener('click', function(e) {
                var btn = e.target.closest('.plan-benefit-remove');
                if (!btn) return;
                var rows = list.querySelectorAll('.plan-benefit-row');
                if (rows.length <= 1) {
                    var input = btn.closest('.plan-benefit-row')?.querySelector('input');
                    if (input) input.value = '';
                    return;
                }
                btn.closest('.plan-benefit-row')?.remove();
                renumberBenefits();
            });

            document.getElementById('planForm')?.addEventListener('submit', async function(e) {
                e.preventDefault();
                var btn = document.getElementById('planSubmitBtn');
                if (btn) btn.disabled = true;
                try {
                    await AdminAjax.submitForm(this, {
                        redirectUrl: @json(route('admin.plans.index'))
                    });
                } catch (err) {
                    if (btn) btn.disabled = false;
                }
            });
        })();
    </script>
@endpush
