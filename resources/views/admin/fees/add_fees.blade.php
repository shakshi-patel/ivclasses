@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-plus-circle"></i>
                    Add Fees
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 0.86rem; color: var(--color-text-secondary);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.fees') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Fees</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page" style="color: var(--iv-primary);">Add Fees</li>
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

                <div class="fees-add-wrap">
                    <a href="{{ route('admin.fees') }}" class="btn btn-sm fees-top-back"
                        style="background: var(--iv-primary); color: #fff;">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <form method="POST" action="{{ route('admin.fees.store') }}" id="feeCollectForm"
                        data-institute-cascade data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <div class="fees-panel">
                                    <div class="fees-panel-head">Add Fees</div>
                                    <div class="fees-panel-body">
                                        <div class="row g-3">
                                            @include('admin.partials.form_institute_field', [
                                                'institutes' => $institutes,
                                                'colClass' => 'col-md-6',
                                                'label' => 'Classes / Institute',
                                            ])
                                            @include('admin.partials.form_branch_field', [
                                                'branches' => $branches,
                                                'colClass' => 'col-md-6',
                                            ])
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                    Batch <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" name="batch_id" id="feeBatchId" required
                                                    data-placeholder="Select batch">
                                                    <option value="">Select batch</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                    Student <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" name="student_id" id="feeStudentId" required
                                                    data-placeholder="Select student">
                                                    <option value="">Select student</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                    Payment Date <span class="text-danger">*</span>
                                                </label>
                                                <input type="date" class="form-control" name="payment_date" id="feePaymentDate"
                                                    value="{{ old('payment_date', now()->toDateString()) }}" required>
                                            </div>
                                        </div>

                                        <div class="fees-summary-box mt-3" id="feeSummaryBox" style="display:none;">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('fees_follow_up.show'))
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="follow_up_only" value="1"
                                                        id="feeFollowupOnly" @checked(old('follow_up_only'))>
                                                    <label class="form-check-label" for="feeFollowupOnly" style="font-size: 0.84rem;">
                                                        Currently fee not receive, only follow-up add.
                                                    </label>
                                                </div>
                                            @endif
                                            <div class="row g-2 mb-0">
                                                <div class="col-md-4">
                                                    <div class="fees-mini-stat">
                                                        <div class="lbl">Total Fees Amount</div>
                                                        <div class="val is-total" id="feeTotalAmount">0.00</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="fees-mini-stat">
                                                        <div class="lbl">Received Fees</div>
                                                        <div class="val is-recv" id="feeReceivedAmount">0.00</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="fees-mini-stat">
                                                        <div class="lbl">Remaining Fees Amount</div>
                                                        <div class="val is-rem" id="feeRemainingAmount">0.00</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="fees-sub-title"><i class="bx bx-user"></i> Student Details</div>
                                            <div class="fees-detail-grid" id="feeStudentDetails">
                                                <span class="k">Student Name:</span><span class="v" id="fdStudentName">—</span>
                                                <span class="k">Father Name:</span><span class="v" id="fdFatherName">—</span>
                                                <span class="k">Father Email:</span><span class="v" id="fdFatherEmail">—</span>
                                                <span class="k">Father Contact:</span><span class="v" id="fdFatherPhone">—</span>
                                            </div>

                                            <div class="fees-sub-title"><i class="bx bx-history"></i> Payment History</div>
                                            <div id="feePaymentHistory">
                                                <div class="text-muted small">No payments yet.</div>
                                            </div>

                                            <div id="feeFollowUpFields" class="mt-3" style="display:none;">
                                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                    Follow Up Remarks <span class="text-danger">*</span>
                                                </label>
                                                <textarea class="form-control" name="remark" id="feeRemarkInput" rows="3"
                                                    placeholder="Enter follow-up remarks">{{ old('remark') }}</textarea>
                                                <div class="mt-2">
                                                    <label class="form-label fw-semibold" style="color: var(--iv-primary);">Next Follow Up Date</label>
                                                    <input type="date" class="form-control" name="next_follow_up_at"
                                                        value="{{ old('next_follow_up_at') }}">
                                                </div>
                                                <div class="text-end mt-3">
                                                    <button type="submit" class="btn admin-btn-primary" id="feeFollowUpSubmitBtn">
                                                        <i class="bx bx-save me-1"></i> Save Follow-up
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="fees-pay-card" id="feePaymentCard">
                                    <div class="pay-title">
                                        <i class="bx bx-credit-card"></i> Fee Payment
                                    </div>

                                    <div id="feePaymentFields">
                                    <label class="form-label fw-semibold mb-2" style="color: var(--iv-primary);">
                                        Mode of Payment <span class="text-danger">*</span>
                                    </label>
                                    <div class="fees-mode-toggle mb-3">
                                        <button type="button" class="fees-mode-btn" data-mode="online" id="feeModeOnline">
                                            <i class="bx bx-globe"></i> Online
                                        </button>
                                        <button type="button" class="fees-mode-btn is-active" data-mode="cash" id="feeModeCash">
                                            <i class="bx bx-money"></i> Cash
                                        </button>
                                    </div>

                                    <input type="hidden" name="payment_method" id="feePaymentMethod" value="Cash">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                            Pending Amount
                                        </label>
                                        <input type="text" class="form-control" id="feePendingInput" value="0.00" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                            Received Amount <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" class="form-control" name="amount" id="feeAmountInput"
                                            placeholder="Enter Received Amount" min="0" step="0.01">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" style="color: var(--iv-primary);">Remarks</label>
                                        <textarea class="form-control" name="note" rows="3" placeholder="Enter Remarks">{{ old('note') }}</textarea>
                                    </div>

                                    <div class="fees-online-only" id="feeOnlineFields">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                Payment Method <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="feeOnlineMethod" data-no-search>
                                                <option value="UPI">UPI</option>
                                                <option value="Net Banking">Net Banking</option>
                                                <option value="Card">Card</option>
                                                <option value="Cheque">Cheque</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                                Reference/Cheque No.
                                            </label>
                                            <input type="text" class="form-control" name="transaction_ref"
                                                placeholder="Enter Reference/Cheque No." value="{{ old('transaction_ref') }}">
                                        </div>
                                    </div>
                                    </div>

                                    <div class="text-end mt-3" id="feePaymentSubmitWrap">
                                        <button type="submit" class="btn" style="background: var(--iv-primary); color: #fff;">
                                            <i class="bx bx-save me-1"></i> <span id="feeSubmitLabel">Make Payment</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('feeCollectForm');
            const batchSelect = document.getElementById('feeBatchId');
            const studentSelect = document.getElementById('feeStudentId');
            const summaryBox = document.getElementById('feeSummaryBox');
            const paymentCard = document.getElementById('feePaymentCard');
            const followUpOnly = document.getElementById('feeFollowupOnly');
            const followUpFields = document.getElementById('feeFollowUpFields');
            const paymentFields = document.getElementById('feePaymentFields');
            const paymentSubmitWrap = document.getElementById('feePaymentSubmitWrap');
            const remarkInput = document.getElementById('feeRemarkInput');
            const amountInput = document.getElementById('feeAmountInput');
            const pendingInput = document.getElementById('feePendingInput');
            const paymentMethodInput = document.getElementById('feePaymentMethod');
            const onlineMethod = document.getElementById('feeOnlineMethod');
            const onlineBtn = document.getElementById('feeModeOnline');
            const cashBtn = document.getElementById('feeModeCash');
            const onlineFields = document.getElementById('feeOnlineFields');
            const submitLabel = document.getElementById('feeSubmitLabel');
            const instituteInput = form?.querySelector('select[name="institute_id"][data-cascade-source]');
            const instituteHidden = form?.querySelector('input[name="institute_id"][type="hidden"]');
            const branchEl = form?.querySelector('[name="branch_id"]');
            const lookupsUrl = form?.getAttribute('data-cascade-url') || @json(\App\Support\AdminUrl::lookups());
            const summaryUrl = @json(route('admin.fees.student_summary'));
            const $ = window.jQuery;
            let payMode = 'cash';

            function csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            }

            function getInstituteId() {
                return instituteInput?.value || instituteHidden?.value || '';
            }

            function resetSelect2(el) {
                if (!$ || !$.fn.select2 || !el) return;
                const $el = $(el);
                if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
            }

            function fillSelect(el, items, placeholder) {
                if (!el) return;
                resetSelect2(el);
                el.innerHTML = '';
                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = placeholder || 'Select';
                el.appendChild(opt0);
                (items || []).forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.text;
                    el.appendChild(opt);
                });
                if (typeof window.initAdminSearchableSelects === 'function') {
                    window.initAdminSearchableSelects(el.parentElement || form);
                }
            }

            async function fetchLookup(type, extra) {
                const instituteId = getInstituteId();
                if (!instituteId) return [];
                let url = lookupsUrl + '/' + type + '?institute_id=' + encodeURIComponent(instituteId);
                if (extra) url += '&' + extra;
                const res = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() }
                });
                const data = await res.json();
                return data.data || [];
            }

            async function loadBatches() {
                const branchId = branchEl?.value || '';
                fillSelect(studentSelect, [], 'Select student');
                if (!branchId) {
                    fillSelect(batchSelect, [], 'Select batch');
                    hideSummary();
                    return;
                }
                const items = await fetchLookup('batches', 'branch_id=' + encodeURIComponent(branchId));
                fillSelect(batchSelect, items, 'Select batch');
                hideSummary();
            }

            async function loadStudents() {
                const branchId = branchEl?.value || '';
                const batchId = batchSelect?.value || '';
                if (!branchId || !batchId) {
                    fillSelect(studentSelect, [], 'Select student');
                    hideSummary();
                    return;
                }
                const items = await fetchLookup('students', 'branch_id=' + encodeURIComponent(branchId) + '&batch_id=' + encodeURIComponent(batchId));
                fillSelect(studentSelect, items, 'Select student');
                hideSummary();
            }

            function hideSummary() {
                if (summaryBox) summaryBox.style.display = 'none';
            }

            function formatMoney(val) {
                return parseFloat(val || 0).toFixed(2);
            }

            async function loadSummary() {
                const studentId = studentSelect?.value;
                const batchId = batchSelect?.value;
                if (!studentId || !batchId) {
                    hideSummary();
                    return;
                }

                const res = await fetch(summaryUrl + '?student_id=' + studentId + '&batch_id=' + batchId, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() }
                });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    hideSummary();
                    return;
                }

                const d = json.data;
                summaryBox.style.display = '';
                document.getElementById('feeTotalAmount').textContent = formatMoney(d.invoice.net_amount);
                document.getElementById('feeReceivedAmount').textContent = formatMoney(d.invoice.paid_amount);
                document.getElementById('feeRemainingAmount').textContent = formatMoney(d.invoice.due_amount);
                pendingInput.value = formatMoney(d.invoice.due_amount);
                amountInput.value = d.invoice.due_amount > 0 ? formatMoney(d.invoice.due_amount) : '';
                amountInput.max = d.invoice.due_amount;

                document.getElementById('fdStudentName').textContent = d.student.name || '—';
                document.getElementById('fdFatherName').textContent = d.student.father_name || '—';
                document.getElementById('fdFatherEmail').textContent = d.student.father_email || '—';
                document.getElementById('fdFatherPhone').textContent = d.student.father_phone || '—';

                const hist = document.getElementById('feePaymentHistory');
                if (!d.payments.length) {
                    hist.innerHTML = '<div class="text-muted small">No payments yet.</div>';
                } else {
                    hist.innerHTML = d.payments.map(function (p) {
                        return '<div class="fees-history-item">₹ ' + formatMoney(p.amount) + ' — on ' + (p.payment_date || '—') +
                            ' (' + (p.payment_method || '—') + ')' + (p.collector ? ' — ' + p.collector : '') + '</div>';
                    }).join('');
                }

                toggleFollowUpMode();
            }

            function setPayMode(mode) {
                payMode = mode;
                const isOnline = mode === 'online';
                onlineBtn?.classList.toggle('is-active', isOnline);
                cashBtn?.classList.toggle('is-active', !isOnline);
                onlineFields?.classList.toggle('is-visible', isOnline);
                paymentMethodInput.value = isOnline ? (onlineMethod?.value || 'UPI') : 'Cash';
            }

            function toggleFollowUpMode() {
                const onlyFollowUp = followUpOnly?.checked;
                followUpFields.style.display = onlyFollowUp ? '' : 'none';
                if (paymentFields) paymentFields.style.display = onlyFollowUp ? 'none' : '';
                if (paymentSubmitWrap) paymentSubmitWrap.style.display = onlyFollowUp ? 'none' : '';
                if (paymentCard) {
                    paymentCard.style.display = onlyFollowUp ? 'none' : '';
                }
                submitLabel.textContent = onlyFollowUp ? 'Save Follow-up' : 'Make Payment';
                amountInput.required = !onlyFollowUp;
                document.getElementById('feePaymentDate').required = !onlyFollowUp;
                if (remarkInput) remarkInput.required = onlyFollowUp;
            }

            onlineBtn?.addEventListener('click', function () { setPayMode('online'); });
            cashBtn?.addEventListener('click', function () { setPayMode('cash'); });
            onlineMethod?.addEventListener('change', function () {
                if (payMode === 'online') paymentMethodInput.value = onlineMethod.value;
            });
            followUpOnly?.addEventListener('change', toggleFollowUpMode);

            branchEl?.addEventListener('change', loadBatches);
            batchSelect?.addEventListener('change', loadStudents);
            studentSelect?.addEventListener('change', loadSummary);
            if ($) {
                $(branchEl).on('change.feeCascade select2:select.feeCascade', loadBatches);
                $(batchSelect).on('change.feeCascade select2:select.feeCascade', loadStudents);
                $(studentSelect).on('change.feeCascade select2:select.feeCascade', loadSummary);
            }
            form?.addEventListener('institute-cascade:done', loadBatches);

            @php
                $feeUser = auth()->user();
                $feeIsPlatform = (bool) $feeUser?->isPlatformUser();
                $feeListUrl = route('admin.fees');
                $feeFollowUpUrl = route('admin.fees.follow_up');
                $canFeeList = $feeIsPlatform || $feeUser?->can('fees.view');
                $canFollowUpList = $feeIsPlatform || $feeUser?->can('fees_follow_up.view');
                $paymentRedirectUrl = $canFeeList ? $feeListUrl : ($canFollowUpList ? $feeFollowUpUrl : route('dashboard'));
                $followUpRedirectUrl = $canFollowUpList ? $feeFollowUpUrl : $paymentRedirectUrl;
            @endphp
            const paymentRedirect = @json($paymentRedirectUrl);
            const followUpRedirect = @json($followUpRedirectUrl);

            form?.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (payMode === 'online') paymentMethodInput.value = onlineMethod.value;
                this.querySelectorAll('[type="submit"]').forEach(function (btn) { btn.disabled = true; });
                try {
                    await AdminAjax.submitForm(this, {
                        redirectUrl: followUpOnly?.checked ? followUpRedirect : paymentRedirect
                    });
                } catch (err) {
                    this.querySelectorAll('[type="submit"]').forEach(function (btn) { btn.disabled = false; });
                }
            });

            toggleFollowUpMode();

            setPayMode('cash');
            if (getInstituteId() && branchEl?.value) loadBatches();
        })();
    </script>
@endpush
