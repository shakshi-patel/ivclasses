@extends('website.layouts.app')

@section('content')
    @php
        $plans = $plans ?? collect();
        $selectedPlanId = (int) ($selectedPlanId ?? 0);
        $startOnForm = (bool) ($startOnForm ?? false);
        $chosenPlan = $plans->firstWhere('id', $selectedPlanId);
        $hasBranchFieldErrors = collect($errors->keys())->contains(fn ($k) => $k === 'branches' || str_starts_with((string) $k, 'branches.'));
    @endphp
    <main class="demo-page">
        <div class="container" id="trialPicker" @if ($startOnForm) hidden @endif>
            <div class="section-head center">
                <p class="eyebrow">Start free trial</p>
                <h1 class="section-title">Choose a plan</h1>
                <p class="section-sub">Same plans as the homepage. Select one and the institute form opens underneath.</p>
            </div>
            @include('website.partials.plan_cards', [
                'plans' => $plans,
                'cardMode' => 'pick',
                'selectedPlanId' => $selectedPlanId,
            ])
        </div>

        <div class="container" id="trialFormWrap" @unless ($startOnForm) hidden @endunless>
            <div class="demo-layout demo-layout-form">
                <div class="demo-form-wrap reveal" data-reveal data-delay="80">
                    <div class="demo-form-head">
                        <h2>Register your institute</h2>
                        <p>
                            <strong id="regChosenName">{{ $chosenPlan->name ?? 'Selected plan' }}</strong>
                            <span id="regChosenTrial">@if ((int) ($chosenPlan->trial_days ?? 0) > 0) · {{ (int) $chosenPlan->trial_days }}-day free trial @endif</span>
                            <button type="button" class="iv-plan-change" id="regChangePlan">Change plan</button>
                        </p>
                    </div>

                    <div class="reg-steps" aria-label="Registration steps">
                        <button type="button" class="reg-steps-item {{ $startOnForm ? '' : 'is-active' }}" data-step-tab="1">
                            <span class="n">1</span>
                            <span class="t">Plan</span>
                        </button>
                        <span class="reg-steps-line" aria-hidden="true"></span>
                        <button type="button" class="reg-steps-item {{ $startOnForm ? 'is-active' : '' }}" data-step-tab="2">
                            <span class="n">2</span>
                            <span class="t">Institute</span>
                        </button>
                        <span class="reg-steps-line" id="regStepLine3" hidden aria-hidden="true"></span>
                        <button type="button" class="reg-steps-item" data-step-tab="3" id="regStepTab3" hidden>
                            <span class="n">3</span>
                            <span class="t">Branch</span>
                        </button>
                    </div>

                    @if ($errors->any())
                        <div class="trial-alert trial-alert-error" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register-institute.store') }}" class="demo-form" id="regStepForm" novalidate>
                        @csrf
                        <input type="hidden" name="plan_id" id="regPlanId" value="{{ $selectedPlanId }}" required>

                        {{-- STEP 2: Institute --}}
                        <div class="reg-panel {{ $startOnForm ? 'is-active' : '' }}" data-step-panel="2" @unless ($startOnForm) hidden @endunless>
                            <div class="reg-panel-title">Institute admin login</div>

                            <div class="form-row">
                                <div class="field {{ $errors->has('name') ? 'has-error' : '' }}">
                                    <label for="trialName">Institute / Classes Name *</label>
                                    <input type="text" id="trialName" name="name" value="{{ old('name') }}"
                                        required autocomplete="organization" placeholder="e.g. IV Excellence Classes" />
                                    <span class="error">{{ $errors->first('name') }}</span>
                                </div>
                                <div class="field {{ $errors->has('admin_name') ? 'has-error' : '' }}">
                                    <label for="trialAdmin">Admin Name</label>
                                    <input type="text" id="trialAdmin" name="admin_name" value="{{ old('admin_name') }}"
                                        autocomplete="name" placeholder="Your name" />
                                    <span class="error">{{ $errors->first('admin_name') }}</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
                                    <label for="trialEmail">Admin Email (login) *</label>
                                    <input type="email" id="trialEmail" name="email" value="{{ old('email') }}"
                                        required autocomplete="email" placeholder="you@institute.com" />
                                    <span class="error">{{ $errors->first('email') }}</span>
                                </div>
                                <div class="field {{ $errors->has('phone') ? 'has-error' : '' }}">
                                    <label for="trialPhone">Phone / WhatsApp</label>
                                    <input type="tel" id="trialPhone" name="phone" value="{{ old('phone') }}"
                                        autocomplete="tel" maxlength="15" minlength="10" pattern="[0-9]{10,15}" inputmode="numeric" data-phone-input placeholder="+91" />
                                    <span class="error">{{ $errors->first('phone') }}</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field {{ $errors->has('city') ? 'has-error' : '' }}">
                                    <label for="trialCity">City</label>
                                    <input type="text" id="trialCity" name="city" value="{{ old('city') }}"
                                        autocomplete="address-level2" placeholder="City" />
                                    <span class="error">{{ $errors->first('city') }}</span>
                                </div>
                                <div class="field {{ $errors->has('state') ? 'has-error' : '' }}">
                                    <label for="trialState">State</label>
                                    <input type="text" id="trialState" name="state" value="{{ old('state') }}"
                                        autocomplete="address-level1" placeholder="State" />
                                    <span class="error">{{ $errors->first('state') }}</span>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
                                    <label for="trialPassword">Admin Password *</label>
                                    <input type="password" id="trialPassword" name="password" required
                                        autocomplete="new-password" placeholder="Min. 8 characters" />
                                    <span class="error">{{ $errors->first('password') }}</span>
                                </div>
                                <div class="field {{ $errors->has('password_confirmation') ? 'has-error' : '' }}">
                                    <label for="trialPasswordConfirm">Confirm Password *</label>
                                    <input type="password" id="trialPasswordConfirm" name="password_confirmation"
                                        required autocomplete="new-password" placeholder="Repeat password" />
                                    <span class="error">{{ $errors->first('password_confirmation') }}</span>
                                </div>
                            </div>

                            <div class="reg-branches-ask">
                                <div class="reg-panel-title" style="margin-bottom: 8px;">Do you have any branches?</div>
                                <p class="reg-panel-sub">If no, we’ll create a default Main Branch from your institute.</p>
                                <div class="reg-radio-row" role="radiogroup" aria-label="Do you have any branches?">
                                    <label class="reg-radio-card">
                                        <input type="radio" name="has_branches" value="yes" id="hasBranchesYes"
                                            @checked(old('has_branches') === 'yes')>
                                        <span class="reg-radio-card-body">
                                            <strong>Yes</strong>
                                            <small>We have one or more branches</small>
                                        </span>
                                    </label>
                                    <label class="reg-radio-card">
                                        <input type="radio" name="has_branches" value="no" id="hasBranchesNo"
                                            @checked(old('has_branches', 'no') === 'no')>
                                        <span class="reg-radio-card-body">
                                            <strong>No</strong>
                                            <small>Single centre — institute becomes Main Branch</small>
                                        </span>
                                    </label>
                                </div>

                                <div class="reg-branch-count-wrap" id="regBranchCountWrap" hidden>
                                    <div class="field {{ $errors->has('branch_count') ? 'has-error' : '' }}">
                                        <label for="regBranchCount">How many branches? *</label>
                                        <select id="regBranchCount" name="branch_count">
                                            @for ($i = 1; $i <= \App\Services\InstituteRegistrationService::BRANCH_FORM_CAP; $i++)
                                                <option value="{{ $i }}" @selected((int) old('branch_count', 1) === $i)>{{ $i }}</option>
                                            @endfor
                                        </select>
                                        <span class="error" id="regBranchCountError">{{ $errors->first('branch_count') }}</span>
                                    </div>
                                    <p class="reg-count-hint">Must match the branch limit on your selected plan.</p>
                                </div>
                            </div>

                            <div class="reg-step-actions">
                                <button type="button" class="btn btn-ghost" data-reg-prev>Back</button>
                                <button type="button" class="btn btn-primary" id="regNextFromInstitute">Continue to OTP</button>
                            </div>
                        </div>

                        {{-- STEP 3: Branch details --}}
                        <div class="reg-panel" data-step-panel="3" hidden>
                            <div class="reg-panel-title">Branch details</div>
                            <p class="reg-panel-sub">
                                Fill details for each branch. Manager email must differ from institute admin.
                            </p>
                            <div class="reg-branch-cards" id="regBranchCards"></div>
                            <div class="reg-step-actions">
                                <button type="button" class="btn btn-ghost" data-reg-prev>Back</button>
                                <button type="submit" class="btn btn-primary">Continue to OTP</button>
                            </div>
                        </div>

                        <p class="demo-form-note">
                            Already have an account?
                            <a href="{{ route('login.institute') }}">Institute sign in</a>
                            ·
                            <a href="{{ route('login.branch') }}">Branch sign in</a>
                            · Prefer a call first?
                            <a href="{{ route('book-demo') }}">Book Demo</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <template id="regBranchCardTpl">
        <article class="reg-branch-card" data-branch-index="__INDEX__">
            <header class="reg-branch-card-head">
                <span class="reg-branch-badge">Branch __NUM__</span>
                <span class="reg-branch-card-title">Branch + manager login</span>
            </header>
            <div class="form-row">
                <div class="field">
                    <label>Branch Name *</label>
                    <input type="text" name="branches[__INDEX__][name]" required placeholder="e.g. Andheri Centre" />
                </div>
                <div class="field">
                    <label>Branch Phone</label>
                    <input type="tel" name="branches[__INDEX__][phone]" maxlength="15" minlength="10" pattern="[0-9]{10,15}" inputmode="numeric" data-phone-input placeholder="+91" />
                </div>
            </div>
            <div class="field" style="margin-bottom: 14px;">
                <label>Branch Address</label>
                <input type="text" name="branches[__INDEX__][address]" placeholder="Street, area" />
            </div>
            <div class="reg-branch-login-note">Branch Manager portal login</div>
            <div class="form-row">
                <div class="field">
                    <label>Manager Name *</label>
                    <input type="text" name="branches[__INDEX__][manager_name]" required placeholder="Full name" />
                </div>
                <div class="field">
                    <label>Manager Email (login) *</label>
                    <input type="email" name="branches[__INDEX__][manager_email]" required placeholder="manager@institute.com" />
                </div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label>Manager Password *</label>
                    <input type="password" name="branches[__INDEX__][manager_password]" required minlength="8"
                        autocomplete="new-password" placeholder="Min. 8 characters" />
                </div>
                <div class="field">
                    <label>Confirm Password *</label>
                    <input type="password" name="branches[__INDEX__][manager_password_confirmation]" required minlength="8"
                        autocomplete="new-password" placeholder="Repeat password" />
                </div>
            </div>
        </article>
    </template>
@endsection

@push('scripts')
    <script>
        (function() {
            const form = document.getElementById('regStepForm');
            if (!form) return;

            const panels = form.querySelectorAll('[data-step-panel]');
            const tabs = document.querySelectorAll('[data-step-tab]');
            const planInput = document.getElementById('regPlanId');
            const planCards = document.querySelectorAll('.iv-plan-pick');
            const picker = document.getElementById('trialPicker');
            const formWrap = document.getElementById('trialFormWrap');
            const chosenName = document.getElementById('regChosenName');
            const chosenTrial = document.getElementById('regChosenTrial');
            const stepTab3 = document.getElementById('regStepTab3');
            const stepLine3 = document.getElementById('regStepLine3');
            const countWrap = document.getElementById('regBranchCountWrap');
            const countEl = document.getElementById('regBranchCount');
            const nextInstBtn = document.getElementById('regNextFromInstitute');
            const cardsWrap = document.getElementById('regBranchCards');
            const tpl = document.getElementById('regBranchCardTpl');
            const oldBranches = @json(old('branches', []));
            let step = 1;
            let hasBranches = false;

            function showPicker(andScroll) {
                if (picker) picker.hidden = false;
                if (formWrap) formWrap.hidden = true;
                if (andScroll) picker?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function revealForm() {
                if (picker) picker.hidden = true;
                if (formWrap) formWrap.hidden = false;
                if (chosenName) chosenName.textContent = planName();
                if (chosenTrial) chosenTrial.textContent = planTrialLabel();
                goTo(2);
                formWrap?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function goTo(n) {
                if (n === 1) {
                    showPicker(true);
                    return;
                }
                if (n === 3 && !hasBranches) n = 2;
                step = n;
                panels.forEach(function(panel) {
                    const on = parseInt(panel.getAttribute('data-step-panel'), 10) === step;
                    panel.hidden = !on;
                    panel.classList.toggle('is-active', on);
                });
                tabs.forEach(function(tab) {
                    const on = parseInt(tab.getAttribute('data-step-tab'), 10) === step;
                    tab.classList.toggle('is-active', on);
                });
            }

            function selectedPlanCard() {
                return document.querySelector('.iv-plan-pick.is-selected');
            }

            function planMaxBranches() {
                const card = selectedPlanCard();
                const raw = card?.getAttribute('data-max-branches');
                if (raw === null || raw === undefined || raw === '') return null; // unlimited
                const n = parseInt(raw, 10);
                return Number.isFinite(n) && n > 0 ? n : null;
            }

            function planName() {
                return selectedPlanCard()?.getAttribute('data-plan-name') || 'selected plan';
            }

            function planTrialLabel() {
                const days = parseInt(selectedPlanCard()?.getAttribute('data-trial-days') || '0', 10);
                return days > 0 ? '· ' + days + '-day free trial' : '';
            }

            function setBranchCountError(msg) {
                const err = document.getElementById('regBranchCountError');
                const field = countEl?.closest('.field');
                if (err) err.textContent = msg || '';
                if (field) field.classList.toggle('has-error', !!msg);
            }

            function validateBranchCountAgainstPlan() {
                if (!hasBranches) {
                    setBranchCountError('');
                    return true;
                }
                const max = planMaxBranches();
                const count = parseInt(countEl?.value || '1', 10) || 1;
                if (max !== null && count > max) {
                    const label = max === 1 ? '1 branch' : (max + ' branches');
                    setBranchCountError(
                        'Your plan “' + planName() + '” allows only ' + label +
                        '. Reduce the count to ' + max + ' or less, or go back and choose a higher plan.'
                    );
                    return false;
                }
                setBranchCountError('');
                return true;
            }

            function refreshBranchCountHint() {
                // Keep 1–5 options; validation shows plan limit error instead of hiding choices.
                validateBranchCountAgainstPlan();
            }

            function selectPlan(card) {
                const id = card.getAttribute('data-plan-id');
                const name = card.getAttribute('data-plan-name') || 'Plan';
                if (planInput) planInput.value = id;
                planCards.forEach(function(c) {
                    const on = c === card;
                    c.classList.toggle('is-selected', on);
                    c.closest('.iv-plan')?.classList.toggle('is-selected', on);
                });
                if (chosenName) chosenName.textContent = name;
                if (chosenTrial) chosenTrial.textContent = planTrialLabel();
                refreshBranchCountHint();
                if (hasBranches) syncBranchCards();
            }

            function fillCard(card, index, data) {
                const map = {
                    name: data?.name || '',
                    phone: data?.phone || '',
                    address: data?.address || '',
                    manager_name: data?.manager_name || '',
                    manager_email: data?.manager_email || '',
                    manager_password: '',
                    manager_password_confirmation: ''
                };
                Object.keys(map).forEach(function(key) {
                    const el = card.querySelector('[name="branches[' + index + '][' + key + ']"]');
                    if (el) el.value = map[key];
                });
            }

            function syncBranchCards() {
                if (!cardsWrap || !tpl) return;
                if (!validateBranchCountAgainstPlan()) return;

                const count = Math.max(1, Math.min(5, parseInt(countEl?.value || '1', 10) || 1));
                if (countEl && String(countEl.value) !== String(count)) countEl.value = String(count);

                const previous = [];
                cardsWrap.querySelectorAll('.reg-branch-card').forEach(function(card, i) {
                    previous[i] = {
                        name: card.querySelector('[name="branches[' + i + '][name]"]')?.value || '',
                        phone: card.querySelector('[name="branches[' + i + '][phone]"]')?.value || '',
                        address: card.querySelector('[name="branches[' + i + '][address]"]')?.value || '',
                        manager_name: card.querySelector('[name="branches[' + i + '][manager_name]"]')?.value || '',
                        manager_email: card.querySelector('[name="branches[' + i + '][manager_email]"]')?.value || ''
                    };
                });

                cardsWrap.innerHTML = '';
                for (let i = 0; i < count; i++) {
                    const html = tpl.innerHTML
                        .replace(/__INDEX__/g, String(i))
                        .replace(/__NUM__/g, String(i + 1));
                    const wrap = document.createElement('div');
                    wrap.innerHTML = html.trim();
                    const card = wrap.firstElementChild;
                    if (!card) continue;
                    cardsWrap.appendChild(card);
                    fillCard(card, i, previous[i] || oldBranches[i] || null);
                }
            }

            function setHasBranches(yes) {
                hasBranches = !!yes;
                if (countWrap) countWrap.hidden = !hasBranches;
                if (stepTab3) stepTab3.hidden = !hasBranches;
                if (stepLine3) stepLine3.hidden = !hasBranches;
                if (nextInstBtn) {
                    nextInstBtn.textContent = hasBranches ? 'Next: Branch details' : 'Continue to OTP';
                    nextInstBtn.type = hasBranches ? 'button' : 'submit';
                }
                if (hasBranches) {
                    refreshBranchCountHint();
                    if (validateBranchCountAgainstPlan()) syncBranchCards();
                } else {
                    setBranchCountError('');
                    if (cardsWrap) cardsWrap.innerHTML = '';
                    if (step === 3) goTo(2);
                }
            }

            function planValid() {
                if (!planInput || !planInput.value) {
                    showPicker(true);
                    return false;
                }
                return true;
            }

            function panelValid(panelStep) {
                const required = form.querySelectorAll('[data-step-panel="' + panelStep + '"] [required]');
                for (let i = 0; i < required.length; i++) {
                    if (required[i].disabled) continue;
                    if (!required[i].checkValidity()) {
                        required[i].reportValidity();
                        return false;
                    }
                }
                return true;
            }

            function instituteValid() {
                if (!panelValid(2)) return false;
                const pass = form.querySelector('#trialPassword');
                const confirm = form.querySelector('#trialPasswordConfirm');
                if (pass && confirm && pass.value !== confirm.value) {
                    confirm.setCustomValidity('Passwords do not match');
                    confirm.reportValidity();
                    confirm.setCustomValidity('');
                    return false;
                }
                if (!validateBranchCountAgainstPlan()) {
                    countEl?.focus();
                    return false;
                }
                return true;
            }

            function branchValid() {
                if (!panelValid(3)) return false;
                const adminEmail = (form.querySelector('#trialEmail')?.value || '').trim().toLowerCase();
                const emails = [];
                const cards = cardsWrap ? cardsWrap.querySelectorAll('.reg-branch-card') : [];
                for (let i = 0; i < cards.length; i++) {
                    const pass = cards[i].querySelector('[name="branches[' + i + '][manager_password]"]');
                    const confirm = cards[i].querySelector('[name="branches[' + i + '][manager_password_confirmation]"]');
                    if (pass && confirm && pass.value !== confirm.value) {
                        confirm.setCustomValidity('Passwords do not match');
                        confirm.reportValidity();
                        confirm.setCustomValidity('');
                        return false;
                    }
                    const emailEl = cards[i].querySelector('[name="branches[' + i + '][manager_email]"]');
                    const email = (emailEl?.value || '').trim().toLowerCase();
                    if (email && adminEmail && email === adminEmail) {
                        emailEl.setCustomValidity('Manager email must be different from admin email');
                        emailEl.reportValidity();
                        emailEl.setCustomValidity('');
                        return false;
                    }
                    if (email && emails.includes(email)) {
                        emailEl.setCustomValidity('Each branch manager must use a different email');
                        emailEl.reportValidity();
                        emailEl.setCustomValidity('');
                        return false;
                    }
                    if (email) emails.push(email);
                }
                return true;
            }

            refreshBranchCountHint();

            planCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    selectPlan(card);
                    revealForm();
                });
            });

            document.getElementById('regChangePlan')?.addEventListener('click', function() {
                showPicker(true);
            });

            form.querySelectorAll('input[name="has_branches"]').forEach(function(r) {
                r.addEventListener('change', function() {
                    setHasBranches(r.value === 'yes' && r.checked);
                });
            });
            countEl?.addEventListener('change', function() {
                validateBranchCountAgainstPlan();
                if (validateBranchCountAgainstPlan()) syncBranchCards();
            });
            setHasBranches(document.getElementById('hasBranchesYes')?.checked);

            nextInstBtn?.addEventListener('click', function(e) {
                if (!instituteValid()) {
                    e.preventDefault();
                    return;
                }
                if (hasBranches) {
                    e.preventDefault();
                    syncBranchCards();
                    goTo(3);
                }
            });
            form.querySelectorAll('[data-reg-prev]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    goTo(Math.max(1, step - 1));
                });
            });

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    if (tab.hidden) return;
                    const target = parseInt(tab.getAttribute('data-step-tab'), 10);
                    if (target >= 2 && !planValid()) return;
                    if (target >= 3 && (!hasBranches || !instituteValid())) return;
                    if (target === 3) syncBranchCards();
                    goTo(target);
                });
            });

            form.addEventListener('submit', function(e) {
                if (!planValid()) {
                    e.preventDefault();
                    goTo(1);
                    return;
                }
                if (!instituteValid()) {
                    e.preventDefault();
                    goTo(2);
                    return;
                }
                if (hasBranches) {
                    if (step !== 3) {
                        e.preventDefault();
                        syncBranchCards();
                        goTo(3);
                        return;
                    }
                    if (!branchValid()) e.preventDefault();
                }
            });

            @if ($errors->has('plan_id'))
                goTo(1);
            @elseif ($hasBranchFieldErrors)
                setHasBranches(true);
                document.getElementById('hasBranchesYes').checked = true;
                document.getElementById('hasBranchesNo').checked = false;
                syncBranchCards();
                goTo(3);
            @elseif ($startOnForm)
                goTo(2);
            @else
                showPicker();
            @endif
        })();
    </script>
@endpush
