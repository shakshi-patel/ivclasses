@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-credit-card"></i>
                    Billing
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Billing</li>
                    </ol>
                </nav>

                @if (session('success'))
                    <div id="admin-flash-success" class="d-none">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div id="admin-flash-error" class="d-none">{{ session('error') }}</div>
                @endif

                <p class="text-muted mt-3 mb-0" style="font-size: 0.9rem;">
                    Collect payment for an institute when they pay you directly (cash / UPI / bank).
                    Assign plan without payment stays on the Institutes page.
                </p>

                @if ($isBypassMode)
                    <div class="alert alert-info d-flex align-items-center mt-3 mb-0" role="alert">
                        <i class="bx bx-info-circle me-2 fs-5"></i>
                        <div>
                            Gateway checkout is in <strong>bypass</strong> mode.
                            Offline collection records a real paid invoice now.
                        </div>
                    </div>
                @endif

                <div class="master-stu-form-wrap mt-4">
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">Pay for an institute</div>
                        <div class="stu-form-body">
                            <form method="POST" action="{{ route('admin.billing.checkout') }}" id="platformBillingForm">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Institute <span class="req">*</span></label>
                                        <select name="institute_id" id="pbInstitute" class="form-select" required>
                                            <option value="">Select institute</option>
                                            @foreach ($institutes as $row)
                                                @php
                                                    $sub = $row->currentSubscription;
                                                @endphp
                                                <option value="{{ $row->id }}"
                                                    @selected((int) old('institute_id', $selected?->id) === (int) $row->id)
                                                    data-code="{{ $row->code }}"
                                                    data-status="{{ $row->status?->value }}"
                                                    data-plan="{{ $sub?->plan?->name ?? '' }}"
                                                    data-cycle="{{ $sub?->billing_cycle ?? '' }}"
                                                    data-until="{{ $sub?->ends_at?->format('d M Y') ?? ($row->trial_ends_at?->format('d M Y') ?? '') }}"
                                                    data-trial="{{ $row->trial_ends_at?->format('d M Y') ?? '' }}">
                                                    {{ $row->code }} — {{ $row->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Plan <span class="req">*</span></label>
                                        <select name="plan_id" id="pbPlan" class="form-select" required>
                                            <option value="">Select plan</option>
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->id }}"
                                                    data-monthly="{{ $plan->price_monthly }}"
                                                    data-yearly="{{ $plan->price_yearly }}">
                                                    {{ $plan->name }}
                                                    (₹{{ number_format((float) $plan->price_monthly, 0) }}/mo
                                                    · ₹{{ number_format((float) $plan->price_yearly, 0) }}/yr)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Billing cycle <span class="req">*</span></label>
                                        <select name="billing_cycle" id="pbCycle" class="form-select" required>
                                            <option value="monthly">Monthly</option>
                                            <option value="yearly">Yearly</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Payment mode <span class="req">*</span></label>
                                        <select name="payment_mode" id="pbMode" class="form-select" required>
                                            <option value="offline">Collect offline (cash / UPI / bank)</option>
                                            <option value="gateway">Pay via gateway ({{ strtoupper($gatewayDriver) }})</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Amount</label>
                                        <input type="text" class="form-control" id="pbAmount" value="—" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Reference no.</label>
                                        <input type="text" name="reference" class="form-control"
                                            placeholder="UPI / cheque / receipt no." maxlength="100">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Notes</label>
                                        <input type="text" name="notes" class="form-control"
                                            placeholder="Optional" maxlength="500">
                                    </div>
                                </div>

                                <div id="pbSnapshot" class="border rounded p-3 mt-3 d-none" style="background: #f8fafc;">
                                    <div class="small text-muted mb-1">Current subscription</div>
                                    <div class="fw-semibold" id="pbSnapName">—</div>
                                    <div class="small mb-0" id="pbSnapMeta">—</div>
                                </div>

                                <div class="master-stu-form-actions mt-4">
                                    <button type="submit" class="btn admin-btn-primary px-4" id="pbSubmit">
                                        <i class="bx bx-check me-1"></i> Collect &amp; activate
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="admin-table-wrap mt-4">
                    <div class="admin-list-card-bar">
                        <h5>Recent payments</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Institute</th>
                                    <th>Plan</th>
                                    <th>Cycle</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Mode</th>
                                    <th>By</th>
                                    <th>Paid at</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->invoice_no ?? '—' }}</td>
                                        <td>{{ $payment->institute?->name ?? '—' }}</td>
                                        <td>{{ $payment->plan?->name ?? '—' }}</td>
                                        <td>{{ ucfirst($payment->billing_cycle) }}</td>
                                        <td>₹{{ number_format((float) $payment->amount, 2) }}</td>
                                        <td>{{ ucfirst($payment->status->value) }}</td>
                                        <td>{{ strtoupper($payment->gateway) }}</td>
                                        <td>{{ $payment->createdBy?->name ?? '—' }}</td>
                                        <td>{{ $payment->paid_at?->format('d M Y H:i') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No payments yet.</td>
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
            const institute = document.getElementById('pbInstitute');
            const plan = document.getElementById('pbPlan');
            const cycle = document.getElementById('pbCycle');
            const amount = document.getElementById('pbAmount');
            const snap = document.getElementById('pbSnapshot');
            const snapName = document.getElementById('pbSnapName');
            const snapMeta = document.getElementById('pbSnapMeta');
            const form = document.getElementById('platformBillingForm');

            function formatInr(value) {
                const n = Number(value || 0);
                return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
            }

            function refreshAmount() {
                const opt = plan?.selectedOptions?.[0];
                if (!opt || !opt.value) {
                    amount.value = '—';
                    return;
                }
                const key = cycle.value === 'yearly' ? 'yearly' : 'monthly';
                amount.value = formatInr(opt.dataset[key]);
            }

            function refreshSnapshot() {
                const opt = institute?.selectedOptions?.[0];
                if (!opt || !opt.value) {
                    snap.classList.add('d-none');
                    return;
                }
                snap.classList.remove('d-none');
                snapName.textContent = opt.textContent.trim();
                const planName = opt.dataset.plan || 'No paid plan';
                const status = opt.dataset.status || '—';
                const until = opt.dataset.until || opt.dataset.trial || '—';
                snapMeta.textContent = 'Status: ' + status + ' · Plan: ' + planName + ' · Until: ' + until;
            }

            institute?.addEventListener('change', refreshSnapshot);
            plan?.addEventListener('change', refreshAmount);
            cycle?.addEventListener('change', refreshAmount);
            refreshSnapshot();
            refreshAmount();

            form?.addEventListener('submit', async function(e) {
                e.preventDefault();
                const instOpt = institute.selectedOptions[0];
                const planOpt = plan.selectedOptions[0];
                if (!instOpt?.value || !planOpt?.value) return;

                const ok = await AdminAjax.confirm({
                    title: 'Activate this plan?',
                    text: instOpt.textContent.trim() + ' → ' + planOpt.textContent.trim().split('(')[0].trim()
                        + ' (' + cycle.value + ', ' + amount.value + ')',
                    confirmText: 'Yes, activate'
                });
                if (!ok) return;

                const btn = document.getElementById('pbSubmit');
                if (btn) btn.disabled = true;
                try {
                    await AdminAjax.submitForm(form, { reload: true });
                } catch (err) {
                    if (btn) btn.disabled = false;
                }
            });
        })();
    </script>
@endpush
