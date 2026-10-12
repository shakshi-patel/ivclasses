@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-buildings"></i>
                    Institutes
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Institutes</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.institutes.index')"
                    :show-status="true"
                    :statuses="[
                        'trial' => 'Trial',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>All Institutes</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sr</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Plan</th>
                                    <th>Features</th>
                                    <th>Trial ends</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($institutes as $i => $row)
                                    <tr data-institute-id="{{ $row->id }}">
                                        <td>{{ $institutes->firstItem() + $i }}</td>
                                        <td class="fw-semibold">{{ $row->name }}</td>
                                        <td><code>{{ $row->slug }}</code></td>
                                        <td>{{ $row->email }}</td>
                                        <td>
                                            <span class="admin-status-pill {{ $row->status?->pillClass() ?? 'is-navy' }}">
                                                {{ $row->status?->label() ?? $row->status?->value }}
                                            </span>
                                        </td>
                                        <td>{{ $row->currentSubscription?->plan?->name ?? '—' }}</td>
                                        <td>
                                            @php
                                                $planMods = $row->currentSubscription?->plan?->modules_json;
                                                $label = \App\Support\PlanModules::summaryLabel(is_array($planMods) ? $planMods : null);
                                                $extraCount = count($row->modules_extra_json ?? []);
                                                $blockedCount = count($row->modules_blocked_json ?? []);
                                                $hasLimitOverride = filled($row->max_branches_override)
                                                    || filled($row->max_students_override)
                                                    || filled($row->max_staff_override);
                                            @endphp
                                            <span class="text-muted small">{{ $label }}</span>
                                            @if ($extraCount || $blockedCount || $hasLimitOverride)
                                                <div class="mt-1">
                                                    @if ($extraCount)
                                                        <span class="badge bg-label-success">+{{ $extraCount }}</span>
                                                    @endif
                                                    @if ($blockedCount)
                                                        <span class="badge bg-label-danger">−{{ $blockedCount }}</span>
                                                    @endif
                                                    @if ($hasLimitOverride)
                                                        <span class="badge bg-label-warning">limits</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ optional($row->trial_ends_at)->format('d M Y') ?? '—' }}</td>
                                        <td class="text-center">
                                            <button type="button" class="admin-action-icon btn-view-institute"
                                                title="View details"
                                                data-id="{{ $row->id }}">
                                                <i class="bx bx-show"></i>
                                            </button>
                                            <button type="button" class="admin-action-icon btn-assign-plan"
                                                title="Assign plan"
                                                data-id="{{ $row->id }}"
                                                data-name="{{ $row->name }}">
                                                <i class="bx bx-purchase-tag"></i>
                                            </button>
                                            <button type="button" class="admin-action-icon btn-overrides"
                                                title="Module & limit overrides"
                                                data-id="{{ $row->id }}"
                                                data-name="{{ $row->name }}">
                                                <i class="bx bx-slider-alt"></i>
                                            </button>
                                            @if ($row->status === \App\Enums\InstituteStatus::Suspended)
                                                <button type="button" class="admin-action-icon btn-activate"
                                                    title="Activate" data-id="{{ $row->id }}">
                                                    <i class="bx bx-check"></i>
                                                </button>
                                            @else
                                                <button type="button" class="admin-action-icon btn-suspend"
                                                    title="Suspend" data-id="{{ $row->id }}">
                                                    <i class="bx bx-block"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No institutes yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    <div class="p-3">
                        {{ $institutes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="viewInstituteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Institute details — <span id="viewInstituteName">…</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewInstituteBody">
                    <div class="text-center text-muted py-4">Loading…</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="assignPlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign plan — <span id="apInstituteName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="apInstituteId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Plan</label>
                        <select class="form-select" id="apPlanId">
                            @foreach ($plans as $plan)
                                @php
                                    $mods = $plan->modules_json ?? [];
                                    $modLabel = in_array('*', $mods, true) ? 'All modules' : implode(', ', $mods);
                                @endphp
                                <option value="{{ $plan->id }}" data-code="{{ $plan->code }}" data-modules="{{ $modLabel }}">
                                    {{ $plan->name }} ({{ $plan->code }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text" id="apModulesHint">Modules come from the selected plan.</div>
                    </div>
                    <div class="mb-3" id="apTrialDaysWrap">
                        <label class="form-label fw-semibold">Trial / period days</label>
                        <input type="number" class="form-control" id="apTrialDays" value="14" min="1">
                        <div class="form-text">Only used for Free Trial plan.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Billing cycle</label>
                        <select class="form-select" id="apCycle">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="apSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="overridesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-slider-alt me-1"></i> Module &amp; limit overrides
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ovInstituteId">

                    @php
                        $coreLabelsShort = collect(\App\Support\PlanModules::CORE)
                            ->map(fn ($key) => \App\Support\PlanModules::crmCatalog()[$key] ?? $key)
                            ->take(6)
                            ->implode(', ');
                    @endphp

                    <div class="plan-form-section pt-0">
                        <div class="plan-form-section-title">
                            <i class="bx bx-buildings"></i>
                            <div>
                                <strong>Institute: <span id="ovInstituteName">—</span></strong>
                                <span id="ovPlanBadge">Plan modules are pre-selected. Tick more to add, untick to remove for this institute only.</span>
                            </div>
                        </div>

                        <div class="plan-form-section-title mb-2">
                            <i class="bx bx-grid-alt"></i>
                            <div>
                                <strong>Feature modules</strong>
                                <span>Core modules are always included: {{ $coreLabelsShort }}…</span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <div class="plan-mode-pills mb-0">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="ovSelectPlanBtn">
                                    <i class="bx bx-reset"></i> Reset to plan
                                </button>
                            </div>
                            <input type="search" class="form-control form-control-sm" id="ovModuleSearch"
                                placeholder="Search module..." style="max-width: 220px;">
                        </div>

                        <div class="row g-2" id="ovModuleChecks">
                            @foreach ($sellableModules as $key => $label)
                                <div class="col-6 col-md-4 col-xl-3 ov-module-col" data-module-name="{{ strtolower($label.' '.$key) }}">
                                    <label class="plan-module-card">
                                        <input class="ov-module-check" type="checkbox"
                                            value="{{ $key }}" data-module="{{ $key }}">
                                        <span class="plan-module-card-body">
                                            <i class="bx bx-check-circle"></i>
                                            <strong>{{ $label }}</strong>
                                            <small class="ov-module-meta" data-module="{{ $key }}">{{ $key }}</small>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="plan-form-section mb-0 border-0 pb-0">
                        <div class="plan-form-section-title">
                            <i class="bx bx-bar-chart-alt-2"></i>
                            <div>
                                <strong>Limit overrides</strong>
                                <span>Leave blank to use the plan default</span>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Max branches</label>
                                <input type="number" min="1" class="form-control" id="ovMaxBranches" placeholder="Unlimited">
                                <div class="form-text" id="ovMaxBranchesHint"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max students</label>
                                <input type="number" min="1" class="form-control" id="ovMaxStudents" placeholder="Unlimited">
                                <div class="form-text" id="ovMaxStudentsHint"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Max staff</label>
                                <input type="number" min="1" class="form-control" id="ovMaxStaff" placeholder="Unlimited">
                                <div class="form-text" id="ovMaxStaffHint"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="ovSaveBtn">
                        <i class="bx bx-save me-1"></i> Save overrides
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $instituteRoutePlaceholder = '__ID__';
    $instituteRoutes = [
        'show' => route('admin.institutes.show', ['institute' => $instituteRoutePlaceholder]),
        'suspend' => route('admin.institutes.suspend', ['institute' => $instituteRoutePlaceholder]),
        'activate' => route('admin.institutes.activate', ['institute' => $instituteRoutePlaceholder]),
        'assignPlan' => route('admin.institutes.assign_plan', ['institute' => $instituteRoutePlaceholder]),
        'overrides' => route('admin.institutes.overrides', ['institute' => $instituteRoutePlaceholder]),
    ];
@endphp

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
        .plan-mode-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
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
        .plan-module-card.is-from-plan input:checked + .plan-module-card-body small::after {
            content: " · on plan";
            color: var(--iv-primary, #0f5c4d);
            font-weight: 650;
        }
        .plan-module-card.is-extra input:checked + .plan-module-card-body small::after {
            content: " · added";
            color: #2e8b68;
            font-weight: 650;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const routes = @json($instituteRoutes);

            function instituteUrl(template, id) {
                return template.replace('__ID__', id);
            }

            const viewModalEl = document.getElementById('viewInstituteModal');
            const viewModal = viewModalEl ? new bootstrap.Modal(viewModalEl) : null;
            const viewBody = document.getElementById('viewInstituteBody');
            const viewName = document.getElementById('viewInstituteName');

            document.querySelectorAll('.btn-view-institute').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    if (viewName) viewName.textContent = '…';
                    if (viewBody) viewBody.innerHTML = '<div class="text-center text-muted py-4">Loading…</div>';
                    viewModal?.show();
                    try {
                        const data = await AdminAjax.get(instituteUrl(routes.show, btn.dataset.id));
                        if (viewName) viewName.textContent = data.data?.name || 'Institute';
                        if (viewBody) viewBody.innerHTML = data.data?.html || '<p class="text-muted mb-0">No details.</p>';
                    } catch (e) {
                        if (viewBody) viewBody.innerHTML = '<p class="text-danger mb-0">' + (e.message || 'Failed to load') + '</p>';
                        await AdminAjax.error(e.message);
                    }
                });
            });

            const statusPillMap = {
                trial: { label: 'Trial', className: 'admin-status-pill is-muted' },
                active: { label: 'Active', className: 'admin-status-pill is-active' },
                suspended: { label: 'Suspended', className: 'admin-status-pill is-navy' },
                cancelled: { label: 'Cancelled', className: 'admin-status-pill is-navy' }
            };

            function updateStatusPill(tr, status) {
                const pill = tr?.querySelector('.admin-status-pill');
                if (!pill) return;
                const key = String(status || '').toLowerCase();
                const meta = statusPillMap[key] || statusPillMap.suspended;
                pill.textContent = meta.label;
                pill.className = meta.className;
            }

            function replaceStatusToggle(tr, id, mode) {
                if (!tr) return;
                const oldBtn = tr.querySelector('.btn-suspend, .btn-activate');
                if (!oldBtn) return;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'admin-action-icon ' + (mode === 'activate' ? 'btn-activate' : 'btn-suspend');
                btn.title = mode === 'activate' ? 'Activate' : 'Suspend';
                btn.dataset.id = String(id);
                btn.innerHTML = mode === 'activate'
                    ? '<i class="bx bx-check"></i>'
                    : '<i class="bx bx-block"></i>';
                oldBtn.replaceWith(btn);
            }

            document.addEventListener('click', async function(ev) {
                const suspendBtn = ev.target.closest('.btn-suspend');
                if (suspendBtn && suspendBtn.closest('table')) {
                    const ok = await AdminAjax.confirm({
                        title: 'Suspend institute?',
                        text: 'Users of this institute will not be able to log in.',
                        confirmText: 'Yes, suspend'
                    });
                    if (!ok) return;
                    const tr = suspendBtn.closest('tr');
                    const id = suspendBtn.dataset.id;
                    try {
                        const data = await AdminAjax.post(instituteUrl(routes.suspend, id));
                        updateStatusPill(tr, data.data?.status || 'suspended');
                        replaceStatusToggle(tr, id, 'activate');
                        await AdminAjax.success(data.message || 'Suspended.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                    return;
                }

                const activateBtn = ev.target.closest('.btn-activate');
                if (activateBtn && activateBtn.closest('table')) {
                    const tr = activateBtn.closest('tr');
                    const id = activateBtn.dataset.id;
                    try {
                        const data = await AdminAjax.post(instituteUrl(routes.activate, id));
                        updateStatusPill(tr, data.data?.status || 'active');
                        replaceStatusToggle(tr, id, 'suspend');
                        await AdminAjax.success(data.message || 'Activated.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                }
            });

            const modalEl = document.getElementById('assignPlanModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
            const planSelect = document.getElementById('apPlanId');
            const trialWrap = document.getElementById('apTrialDaysWrap');

            function syncTrialDaysField() {
                if (!planSelect || !trialWrap) return;
                const code = planSelect.options[planSelect.selectedIndex]?.dataset.code || '';
                trialWrap.style.display = code === 'trial' ? '' : 'none';
                const hint = document.getElementById('apModulesHint');
                if (hint) {
                    const mods = planSelect.options[planSelect.selectedIndex]?.dataset.modules || '';
                    hint.textContent = mods ? ('Plan modules: ' + mods + ' (+ core basics)') : 'Modules come from the selected plan.';
                }
            }

            planSelect?.addEventListener('change', syncTrialDaysField);
            syncTrialDaysField();

            document.querySelectorAll('.btn-assign-plan').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('apInstituteId').value = btn.dataset.id;
                    document.getElementById('apInstituteName').textContent = btn.dataset.name;
                    syncTrialDaysField();
                    modal?.show();
                });
            });

            document.getElementById('apSaveBtn')?.addEventListener('click', async function() {
                const id = document.getElementById('apInstituteId').value;
                try {
                    const planSelect = document.getElementById('apPlanId');
                    const selectedOption = planSelect.options[planSelect.selectedIndex];
                    const planCode = selectedOption?.dataset.code || '';
                    const payload = {
                        plan_id: planSelect.value,
                        billing_cycle: document.getElementById('apCycle').value
                    };
                    if (planCode === 'trial') {
                        payload.trial_days = document.getElementById('apTrialDays').value;
                    }
                    const data = await AdminAjax.post(instituteUrl(routes.assignPlan, id), payload);
                    modal?.hide();
                    await AdminAjax.success(data.message || 'Plan assigned.');
                    window.location.reload();
                } catch (e) {
                    await AdminAjax.error(e.message);
                }
            });

            const ovModalEl = document.getElementById('overridesModal');
            const ovModal = ovModalEl ? new bootstrap.Modal(ovModalEl) : null;
            let ovPlanModules = [];

            function planHint(elId, planVal) {
                const el = document.getElementById(elId);
                if (!el) return;
                el.textContent = planVal == null || planVal === ''
                    ? 'Plan: unlimited'
                    : ('Plan default: ' + planVal);
            }

            function selectedModules() {
                return Array.from(document.querySelectorAll('.ov-module-check:checked'))
                    .map(function(el) { return el.value; });
            }

            function setModuleChecks(values) {
                const set = new Set(values || []);
                document.querySelectorAll('.ov-module-check').forEach(function(el) {
                    el.checked = set.has(el.value);
                });
                refreshModuleCardMeta();
            }

            function refreshModuleCardMeta() {
                const planSet = new Set(ovPlanModules || []);
                document.querySelectorAll('.ov-module-check').forEach(function(el) {
                    const card = el.closest('.plan-module-card');
                    if (!card) return;
                    const onPlan = planSet.has(el.value);
                    const isExtra = el.checked && !onPlan;
                    card.classList.toggle('is-from-plan', onPlan && el.checked);
                    card.classList.toggle('is-extra', isExtra);
                });
            }

            function effectiveFromOverrides(plan, extra, blocked) {
                const planSet = new Set(plan || []);
                const extraSet = new Set(extra || []);
                const blockedSet = new Set(blocked || []);
                const out = [];
                planSet.forEach(function(m) {
                    if (!blockedSet.has(m)) out.push(m);
                });
                extraSet.forEach(function(m) {
                    if (!out.includes(m)) out.push(m);
                });
                return out;
            }

            function diffOverrides(selected, plan) {
                const planSet = new Set(plan || []);
                const selectedSet = new Set(selected || []);
                const extra = [];
                const blocked = [];
                selectedSet.forEach(function(m) {
                    if (!planSet.has(m)) extra.push(m);
                });
                planSet.forEach(function(m) {
                    if (!selectedSet.has(m)) blocked.push(m);
                });
                return { extra: extra, blocked: blocked };
            }

            ovModalEl?.addEventListener('change', function(e) {
                if (e.target.classList.contains('ov-module-check')) {
                    refreshModuleCardMeta();
                }
            });

            document.getElementById('ovModuleSearch')?.addEventListener('input', function() {
                const q = (this.value || '').trim().toLowerCase();
                document.querySelectorAll('.ov-module-col').forEach(function(col) {
                    const name = col.dataset.moduleName || '';
                    col.style.display = (!q || name.includes(q)) ? '' : 'none';
                });
            });

            document.getElementById('ovSelectPlanBtn')?.addEventListener('click', function() {
                setModuleChecks(ovPlanModules);
            });

            document.querySelectorAll('.btn-overrides').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    document.getElementById('ovInstituteId').value = btn.dataset.id;
                    document.getElementById('ovInstituteName').textContent = btn.dataset.name;
                    document.getElementById('ovPlanBadge').textContent = 'Loading plan modules…';
                    document.getElementById('ovModuleSearch').value = '';
                    ovPlanModules = [];
                    setModuleChecks([]);
                    document.getElementById('ovMaxBranches').value = '';
                    document.getElementById('ovMaxStudents').value = '';
                    document.getElementById('ovMaxStaff').value = '';
                    document.querySelectorAll('.ov-module-col').forEach(function(col) {
                        col.style.display = '';
                    });
                    ovModal?.show();
                    try {
                        const data = await AdminAjax.get(instituteUrl(routes.show, btn.dataset.id));
                        const ov = data.data?.overrides || {};
                        ovPlanModules = ov.plan_modules || [];
                        document.getElementById('ovPlanBadge').textContent =
                            'Plan: ' + (ov.plan_name || '—') + '. Tick more to add, untick to remove for this institute only.';
                        setModuleChecks(effectiveFromOverrides(
                            ovPlanModules,
                            ov.modules_extra || [],
                            ov.modules_blocked || []
                        ));
                        document.getElementById('ovMaxBranches').value = ov.max_branches_override ?? '';
                        document.getElementById('ovMaxStudents').value = ov.max_students_override ?? '';
                        document.getElementById('ovMaxStaff').value = ov.max_staff_override ?? '';
                        planHint('ovMaxBranchesHint', ov.plan_max_branches);
                        planHint('ovMaxStudentsHint', ov.plan_max_students);
                        planHint('ovMaxStaffHint', ov.plan_max_staff);
                    } catch (e) {
                        await AdminAjax.error(e.message || 'Failed to load overrides');
                    }
                });
            });

            document.getElementById('ovSaveBtn')?.addEventListener('click', async function() {
                const id = document.getElementById('ovInstituteId').value;
                const diff = diffOverrides(selectedModules(), ovPlanModules);
                const payload = {
                    modules_extra: diff.extra,
                    modules_blocked: diff.blocked,
                    max_branches_override: document.getElementById('ovMaxBranches').value || null,
                    max_students_override: document.getElementById('ovMaxStudents').value || null,
                    max_staff_override: document.getElementById('ovMaxStaff').value || null
                };
                try {
                    const data = await AdminAjax.post(instituteUrl(routes.overrides, id), payload);
                    ovModal?.hide();
                    await AdminAjax.success(data.message || 'Overrides saved.');
                    window.location.reload();
                } catch (e) {
                    await AdminAjax.error(e.message);
                }
            });
        })();
    </script>
@endpush
