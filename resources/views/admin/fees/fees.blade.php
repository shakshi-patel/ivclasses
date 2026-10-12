@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-rupee"></i>
                            Fees
                        </h4>
                    </div>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('fees.add'))
                        <a href="{{ route('admin.fees.add') }}" class="btn admin-btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Fees
                        </a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Fees</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.fees')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-batch="true"
                    :institutes="$institutes"
                    :branches="$branches"
                    :batches="$batches"
                >
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select class="form-select form-select-sm" name="payment_method">
                            <option value="">All Methods</option>
                            @foreach (['Cash', 'Online', 'UPI', 'Cheque'] as $method)
                                <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control form-control-sm" name="start_date" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control form-control-sm" name="end_date" value="{{ request('end_date') }}">
                    </div>
                </x-admin.list-filters>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Fees Collection List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1100px;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr. No.</th>
                                    <th>Student Info</th>
                                    <th>Branch &amp; Batch</th>
                                    <th>Amount &amp; Status</th>
                                    <th>Date &amp; Time</th>
                                    <th>Collected By</th>
                                    <th>Payment Mode</th>
                                    <th class="text-center iv-col-action-lg">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    @php($invoiceStatus = $payment->invoiceStatus())
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fees-student-info">
                                                <div class="s-name">{{ $payment->student?->name ?? '—' }}</div>
                                                <div class="s-meta">#{{ $payment->student?->admission_no ?? '—' }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold iv-text-primary-dark iv-text-sm">{{ $payment->student?->branch?->name ?? '—' }}</div>
                                            <div class="text-muted small">{{ $payment->invoice?->enrollment?->batch?->name ?? ($payment->invoice?->title ?? 'Fee payment') }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold iv-text-primary">₹{{ number_format((float) $payment->amount, 2) }}</div>
                                            <span class="admin-status-pill {{ $invoiceStatus->pillClass() }}">{{ $invoiceStatus->label() }}</span>
                                        </td>
                                        <td>
                                            <div class="iv-text-sm">{{ optional($payment->payment_date)->format('d F Y') }}</div>
                                            <div class="text-muted small">{{ optional($payment->created_at)->format('h:i A') }}</div>
                                        </td>
                                        <td>{{ $payment->collector?->name ?? '—' }}</td>
                                        <td><span class="stu-method-pill">{{ $payment->payment_method }}</span></td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('fees.show'))
                                            <button type="button" class="admin-action-icon btn-fee-view" title="View"
                                                data-bs-toggle="modal" data-bs-target="#feeViewModal"
                                                data-fee-type="{{ $payment->invoice?->title ?? 'Fee' }}"
                                                data-student="{{ $payment->student?->name ?? '—' }}"
                                                data-amount="{{ number_format((float) $payment->amount, 2) }}"
                                                data-date="{{ optional($payment->payment_date)->format('d F Y') }}"
                                                data-status="{{ $invoiceStatus->label() }}"
                                                data-mode="{{ $payment->payment_method }}"
                                                data-standard="{{ $payment->standardDisplayName() }}"
                                                data-collected="{{ $payment->collector?->name ?? '—' }}"
                                                data-note="{{ $payment->note ?? '—' }}">
                                                <i class="bx bx-show"></i>
                                            </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('fees.receipt'))
                                                <button type="button" class="admin-action-icon btn-fee-receipt" title="Receipt"
                                                    data-url="{{ route('admin.fees.receipt', $payment) }}">
                                                    <i class="bx bx-receipt"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('fees.delete'))
                                                <button type="button" class="admin-action-icon btn-fee-delete" title="Delete"
                                                    data-url="{{ route('admin.fees.destroy', $payment) }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            No fee payments yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="feeViewModal" tabindex="-1" aria-labelledby="feeViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feeViewModalLabel">Fee Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="fees-view-hero">
                        <div class="fee-type" id="fvFeeType">Education Fee</div>
                        <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mb-1">
                            <span class="stu-method-pill" id="fvMode">Cash</span>
                            <span class="admin-status-pill is-active" id="fvStatus">Paid</span>
                        </div>
                        <div class="fee-amt" id="fvAmount">₹ 10000.00/-</div>
                        <div class="fee-date" id="fvDate">03 August 2026</div>
                        <div class="text-muted small mt-2" id="fvStudent">Amliyar Rajvant Mehulbhai</div>
                    </div>
                    <div class="fees-view-meta">
                        <div class="fees-view-row">
                            <span class="k">Std:</span>
                            <span class="v" id="fvStandard">8th</span>
                        </div>
                        <div class="fees-view-row">
                            <span class="k">Collected By:</span>
                            <span class="v" id="fvCollected">SuperAdmin</span>
                        </div>
                    </div>
                    <div class="fees-view-note">
                        <strong>Note:</strong>
                        <span id="fvNote">testing</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade admin-modal" id="feeReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feeReceiptTitle">Fee Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="feeReceiptBody">
                    <p class="text-muted mb-0">Loading…</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-secondary" id="feeReceiptPrintBtn">
                        <i class="bx bx-printer me-1"></i> Print
                    </button>
                    <a href="#" class="btn btn-export-students" id="feeReceiptExportBtn">
                        <i class="bx bx-download me-1"></i> Export
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    @include('admin.partials.iv_receipt_styles')
@endpush

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('.btn-fee-view').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('fvFeeType').textContent = btn.dataset.feeType || '—';
                    document.getElementById('fvMode').textContent = btn.dataset.mode || '—';
                    document.getElementById('fvAmount').textContent = '₹ ' + (btn.dataset.amount || '0') + '/-';
                    document.getElementById('fvDate').textContent = btn.dataset.date || '—';
                    document.getElementById('fvStudent').textContent = btn.dataset.student || '—';
                    document.getElementById('fvStandard').textContent = btn.dataset.standard || '—';
                    document.getElementById('fvCollected').textContent = btn.dataset.collected || '—';
                    document.getElementById('fvNote').textContent = btn.dataset.note || '—';

                    var statusEl = document.getElementById('fvStatus');
                    var status = btn.dataset.status || 'Paid';
                    statusEl.textContent = status;
                    statusEl.className = 'admin-status-pill ' + (status === 'Paid' ? 'is-active' : 'is-navy');
                });
            });

            document.querySelectorAll('.btn-fee-delete').forEach(function (btn) {
                btn.addEventListener('click', async function () {
                    const ok = window.AdminAjax
                        ? await AdminAjax.confirm({ title: 'Delete this payment?', icon: 'warning' })
                        : confirm('Delete this payment?');
                    if (!ok) return;
                    try {
                        const data = await AdminAjax.destroy(btn.dataset.url);
                        btn.closest('tr')?.remove();
                        await AdminAjax.success(data.message || 'Deleted.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });

            const receiptModalEl = document.getElementById('feeReceiptModal');
            if (receiptModalEl) {
                const receiptModal = new bootstrap.Modal(receiptModalEl);
                const body = document.getElementById('feeReceiptBody');
                const title = document.getElementById('feeReceiptTitle');
                const exportBtn = document.getElementById('feeReceiptExportBtn');
                const printBtn = document.getElementById('feeReceiptPrintBtn');

                document.querySelectorAll('.btn-fee-receipt').forEach(function (btn) {
                    btn.addEventListener('click', async function () {
                        body.innerHTML = '<p class="text-muted mb-0">Loading…</p>';
                        title.textContent = 'Fee Receipt';
                        exportBtn.href = '#';
                        receiptModal.show();
                        try {
                            const res = await fetch(btn.dataset.url, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const json = await res.json();
                            if (!res.ok || !json.success) throw new Error(json.message || 'Could not load receipt.');
                            const payload = json.data || {};
                            title.textContent = payload.title || 'Fee Receipt';
                            body.innerHTML = payload.html || '<p class="text-muted mb-0">No receipt data.</p>';
                            exportBtn.href = payload.export_url || '#';
                        } catch (e) {
                            body.innerHTML = '<p class="text-danger mb-0">' + (e.message || 'Failed to load receipt.') + '</p>';
                        }
                    });
                });

                printBtn?.addEventListener('click', function () {
                    if (document.getElementById('ivReceiptPrintArea')) window.print();
                });
            }
        })();
    </script>
@endpush
