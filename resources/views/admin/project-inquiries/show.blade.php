@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-message-detail"></i>
                    Project Inquiry
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 0.86rem; color: var(--color-text-secondary);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.project-inquiries.index') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Project Inquiry</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page" style="color: var(--iv-primary);">
                            {{ $inquiry->inquiry_code }}
                        </li>
                    </ol>
                </nav>

                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center mt-4 mb-0" role="alert">
                        <i class="bx bx-check-circle me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger mt-4 mb-0" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="admin-detail-block">
                    <h5>Inquiry Details</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="admin-detail-label">Inquiry Code</div>
                            <div class="admin-detail-value">{{ $inquiry->inquiry_code }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Institute</div>
                            <div class="admin-detail-value">{{ $inquiry->institute_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Contact Person</div>
                            <div class="admin-detail-value">{{ $inquiry->contact_person_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Email</div>
                            <div class="admin-detail-value">{{ $inquiry->email }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Phone</div>
                            <div class="admin-detail-value">{{ $inquiry->contact_no }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">City</div>
                            <div class="admin-detail-value">
                                {{ $inquiry->city }}{{ $inquiry->state ? ', ' . $inquiry->state : '' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Pincode</div>
                            <div class="admin-detail-value">{{ $inquiry->pincode ?? '--' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Students</div>
                            <div class="admin-detail-value">{{ $inquiry->student_count ?? '--' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Branches</div>
                            <div class="admin-detail-value">{{ $inquiry->branch_count ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-detail-label">Requirement</div>
                            <div class="admin-detail-value">{{ $inquiry->requirement ?? '--' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="admin-detail-label">Address</div>
                            <div class="admin-detail-value">{{ $inquiry->address ?? '--' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="admin-detail-label">Message</div>
                            <div class="admin-detail-value">{{ $inquiry->message ?? '--' }}</div>
                        </div>
                    </div>
                </div>

                <div class="admin-detail-block">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="mb-0">Demo Details</h5>
                        @if ($inquiry->google_meet_link)
                            <a href="{{ $inquiry->google_meet_link }}" target="_blank" rel="noopener" class="btn btn-sm"
                                style="background: var(--iv-primary); color: #fff;">
                                <i class="bx bx-video me-1"></i> Join Google Meet
                            </a>
                        @endif
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="admin-detail-label">Demo Date</div>
                            <div class="admin-detail-value">{{ optional($inquiry->demo_date)->format('d M Y') ?? '--' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Demo Time</div>
                            <div class="admin-detail-value">
                                @if ($inquiry->demo_start_time)
                                    {{ date('h:i A', strtotime($inquiry->demo_start_time)) }}
                                    @if ($inquiry->demo_end_time)
                                        – {{ date('h:i A', strtotime($inquiry->demo_end_time)) }}
                                    @endif
                                @else
                                    --
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Timezone</div>
                            <div class="admin-detail-value">{{ $inquiry->timezone ?? '--' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Demo Status</div>
                            <div>
                                <span class="admin-status-pill {{ $inquiry->demo_status?->pillClass() ?? 'is-navy' }}">
                                    {{ $inquiry->demo_status?->label() ?? '—' }}
                                </span>
                            </div>
                        </div>
                        @if ($inquiry->demo_status === \App\Enums\DemoStatus::Requested && ! empty($confirmUrl) && ! empty($declineUrl))
                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <a href="{{ $confirmUrl }}" class="btn btn-sm px-3"
                                        style="background: var(--iv-primary); color: #fff;">
                                        <i class="bx bx-check me-1"></i> Yes — Confirm Demo
                                    </a>
                                    <a href="{{ $declineUrl }}" class="btn btn-sm btn-outline-danger px-3">
                                        <i class="bx bx-x me-1"></i> No — Decline
                                    </a>
                                </div>
                                <div class="text-muted mt-2" style="font-size: 0.82rem;">
                                    Confirm → client gets final Meet email. Decline → slot frees and client is notified.
                                </div>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <div class="admin-detail-label">Status</div>
                            <div>
                                <span class="admin-status-pill {{ $inquiry->status?->pillClass() ?? 'is-muted' }}">
                                    {{ $inquiry->status?->label() ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="admin-detail-label">Google Meet</div>
                            <div class="admin-detail-value">
                                @if ($inquiry->google_meet_link)
                                    <a href="{{ $inquiry->google_meet_link }}" target="_blank" rel="noopener">Open Meet</a>
                                @else
                                    --
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-detail-block">
                    <h5>Admin Notes</h5>
                    <form method="POST" action="{{ route('admin.project-inquiries.update', $inquiry) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="demo_status" class="form-label fw-semibold"
                                    style="color: var(--iv-primary);">Demo Status</label>
                                <select name="demo_status" id="demo_status" class="form-select" required>
                                    @foreach (\App\Enums\DemoStatus::cases() as $demoOption)
                                        <option value="{{ $demoOption->value }}" @selected($inquiry->demo_status === $demoOption)>
                                            {{ $demoOption->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="admin_notes" class="form-label fw-semibold"
                                    style="color: var(--iv-primary);">Notes</label>
                                <textarea name="admin_notes" id="admin_notes" rows="4" class="form-control"
                                    placeholder="Add notes after the demo...">{{ old('admin_notes', $inquiry->admin_notes) }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn px-4" style="background: var(--iv-primary); color: #fff;">
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="admin-detail-block iv-convert">
                    <h5 class="mb-1">Convert to Institute</h5>
                    <p class="text-muted mb-3" style="font-size: 0.88rem;">
                        Same details as institute registration. Temporary passwords are created here and emailed
                        to the institute admin — institute login, plus one branch login for each branch you add.
                    </p>

                    @if ($inquiry->isConverted())
                        <div class="alert alert-success mb-0">
                            Already converted
                            @if ($inquiry->convertedInstitute)
                                to
                                <strong>{{ $inquiry->convertedInstitute->name }}</strong>
                                (ID #{{ $inquiry->converted_institute_id }})
                            @endif
                            on {{ optional($inquiry->converted_at)->format('d M Y, h:i A') ?? '—' }}.
                        </div>
                    @else
                        @if ($errors->has('convert'))
                            <div class="alert alert-danger">{{ $errors->first('convert') }}</div>
                        @endif

                        <form method="POST" action="{{ route('admin.project-inquiries.convert', $inquiry) }}" id="convertInstituteForm">
                            @csrf

                            <div class="iv-convert__top">
                                <div class="iv-convert__label">From this demo</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Institute name</label>
                                        <input type="text" name="name" class="form-control" required
                                            value="{{ old('name', $inquiry->institute_name) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Admin name</label>
                                        <input type="text" name="admin_name" class="form-control" required
                                            value="{{ old('admin_name', $inquiry->contact_person_name) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Admin email (login)</label>
                                        <input type="email" name="admin_email" id="convertAdminEmail" class="form-control" required
                                            value="{{ old('admin_email', $inquiry->email) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Phone</label>
                                        <input type="text" name="phone" class="form-control" maxlength="15" minlength="10" pattern="[0-9]{10,15}" inputmode="numeric" data-phone-input
                                            value="{{ old('phone', $inquiry->contact_no) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">City</label>
                                        <input type="text" name="city" class="form-control"
                                            value="{{ old('city', $inquiry->city) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">State</label>
                                        <input type="text" name="state" class="form-control"
                                            value="{{ old('state', $inquiry->state) }}">
                                    </div>
                                </div>

                                <div class="iv-convert__plan">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label fw-semibold">Plan</label>
                                            <select name="plan_id" id="convertPlan" class="form-select" required data-no-search>
                                                <option value="">Select a plan</option>
                                                @foreach ($plans as $plan)
                                                    <option value="{{ $plan->id }}"
                                                        data-max="{{ $plan->max_branches === null ? '' : (int) $plan->max_branches }}"
                                                        data-trial="{{ (int) $plan->trial_days }}"
                                                        data-price="{{ (float) $plan->price_monthly }}"
                                                        @selected((int) old('plan_id') === (int) $plan->id)>
                                                        {{ $plan->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Separate branches?</label>
                                            <select name="has_branches" id="convertHasBranches" class="form-select" required data-no-search>
                                                <option value="no" @selected(old('has_branches', 'no') === 'no')>No — one centre</option>
                                                <option value="yes" @selected(old('has_branches') === 'yes')>Yes</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4" id="convertCountWrap">
                                            <label class="form-label fw-semibold">How many branches?</label>
                                            <select name="branch_count" id="convertBranchCount" class="form-select" data-no-search>
                                                @for ($i = 1; $i <= $branchFormCap; $i++)
                                                    <option value="{{ $i }}" @selected((int) old('branch_count', 1) === $i)>{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <p class="iv-convert__hint" id="convertPlanHint"></p>
                                </div>
                            </div>

                            <div id="convertBranches">
                                <div class="iv-convert__label">Branch logins</div>
                                <p class="text-muted" style="font-size: 0.84rem;">
                                    Same fields as registration, without passwords. Each manager gets a temporary password in the email.
                                </p>
                                <div id="convertBranchCards" class="d-flex flex-column gap-3"></div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" id="btnConvertInstitute" class="btn px-4 admin-btn-primary">
                                    <i class="bx bx-transfer me-1"></i> Convert &amp; Email Logins
                                </button>
                            </div>
                        </form>

                        <template id="convertBranchTpl">
                            <div class="iv-convert__card">
                                <div class="iv-convert__card-title">Branch <span class="convert-num"></span></div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Branch name</label>
                                        <input type="text" class="form-control" data-name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Branch phone</label>
                                        <input type="text" class="form-control" data-name="phone" maxlength="15" minlength="10" pattern="[0-9]{10,15}" inputmode="numeric" data-phone-input>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Branch address</label>
                                        <input type="text" class="form-control" data-name="address">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Manager name</label>
                                        <input type="text" class="form-control" data-name="manager_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Manager email (login)</label>
                                        <input type="email" class="form-control" data-name="manager_email" required>
                                    </div>
                                </div>
                            </div>
                        </template>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .iv-convert__top {
        padding: 1rem 1.1rem 1.1rem;
        border-radius: 14px;
        background: var(--iv-primary-light);
        border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
    }
    .iv-convert__label {
        font-size: 0.72rem;
        font-weight: 750;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--iv-primary);
        margin-bottom: 0.75rem;
    }
    .iv-convert__plan { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(var(--iv-primary-rgb), 0.12); }
    .iv-convert__hint { margin: 0.65rem 0 0; font-size: 0.82rem; color: var(--iv-primary-dark); }
    #convertBranches { margin-top: 1.15rem; }
    .iv-convert__card {
        padding: 1rem 1.05rem;
        border-radius: 14px;
        background: #fff;
        border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
        box-shadow: 0 8px 22px rgba(var(--iv-primary-dark-rgb), 0.06);
    }
    .iv-convert__card-title {
        font-weight: 750;
        color: var(--iv-primary-dark);
        margin-bottom: 0.75rem;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('convertInstituteForm');
    const btn = document.getElementById('btnConvertInstitute');
    if (!form || !btn) return;

    const cap = {{ (int) $branchFormCap }};
    const planEl = document.getElementById('convertPlan');
    const hasEl = document.getElementById('convertHasBranches');
    const countEl = document.getElementById('convertBranchCount');
    const countWrap = document.getElementById('convertCountWrap');
    const branchWrap = document.getElementById('convertBranches');
    const cardsEl = document.getElementById('convertBranchCards');
    const tpl = document.getElementById('convertBranchTpl');
    const hint = document.getElementById('convertPlanHint');
    const oldBranches = @json(array_values(old('branches', [])));
    const seed = {
        phone: @json((string) $inquiry->contact_no),
        address: @json((string) $inquiry->address),
        manager_name: @json((string) $inquiry->contact_person_name),
        manager_email: @json((string) ($suggestedManagerEmail ?? ''))
    };

    function planLimit() {
        const opt = planEl.options[planEl.selectedIndex];
        if (!opt || !opt.value) return cap;
        const raw = opt.getAttribute('data-max');
        if (raw === null || raw === '') return cap;
        return Math.max(1, Math.min(cap, parseInt(raw, 10) || 1));
    }

    function syncCountOptions() {
        const limit = planLimit();
        Array.from(countEl.options).forEach(function (option) {
            const n = parseInt(option.value, 10);
            option.hidden = n > limit;
            option.disabled = n > limit;
        });
        if (parseInt(countEl.value, 10) > limit) countEl.value = String(limit);
        const opt = planEl.options[planEl.selectedIndex];
        if (!opt || !opt.value) {
            hint.textContent = 'Choose the same kind of public plan used at registration.';
            return;
        }
        const days = opt.getAttribute('data-trial') || '0';
        const price = Number(opt.getAttribute('data-price') || 0);
        const priceLabel = price <= 0 ? 'Free' : ('₹' + Math.round(price) + '/mo');
        const maxLabel = (opt.getAttribute('data-max') === '' || opt.getAttribute('data-max') === null)
            ? (cap + ' on this form')
            : opt.getAttribute('data-max');
        hint.textContent = opt.text.trim() + ' · ' + priceLabel + ' · ' + days + ' trial days · up to ' + maxLabel + ' branches.';
    }

    function renderCards() {
        const yes = hasEl.value === 'yes';
        countWrap.hidden = !yes;
        branchWrap.hidden = !yes;
        cardsEl.innerHTML = '';
        if (!yes) return;

        const count = parseInt(countEl.value, 10) || 1;
        for (let i = 0; i < count; i++) {
            const node = tpl.content.firstElementChild.cloneNode(true);
            node.querySelector('.convert-num').textContent = String(i + 1);
            const saved = oldBranches[i] || {};
            node.querySelectorAll('[data-name]').forEach(function (input) {
                const key = input.getAttribute('data-name');
                input.name = 'branches[' + i + '][' + key + ']';
                if (saved[key]) {
                    input.value = saved[key];
                } else if (i === 0 && seed[key]) {
                    input.value = seed[key];
                }
            });
            cardsEl.appendChild(node);
        }
    }

    planEl.addEventListener('change', function () { syncCountOptions(); renderCards(); });
    hasEl.addEventListener('change', renderCards);
    countEl.addEventListener('change', renderCards);
    syncCountOptions();
    renderCards();

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const ok = window.AdminAjax
            ? await AdminAjax.confirm({
                title: 'Convert to institute?',
                text: 'This creates the trial institute and emails temporary logins, including every branch you added.',
                icon: 'warning',
                confirmText: 'Yes, convert'
            })
            : window.confirm('Create the institute and email temporary logins?');

        if (!ok) return;
        btn.disabled = true;
        form.submit();
    });
})();
</script>
@endpush
