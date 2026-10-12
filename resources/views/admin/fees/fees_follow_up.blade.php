@extends('admin.layouts.layout')

@section('content')
    @php
        $authUser = auth()->user();
        $isPlatformUser = (bool) $authUser?->isPlatformUser();
        $canShowUpdates = $isPlatformUser || $authUser?->can('fees_follow_up.show');
        $canAddUpdate = $canShowUpdates;
        $canExportPdf = $isPlatformUser || $authUser?->can('fees_follow_up.export');
        $canCollect = $isPlatformUser || $authUser?->can('fees_follow_up.add');
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-phone-call"></i>
                            Fees Follow Up
                        </h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($canExportPdf)
                            <button type="button" class="btn btn-sm btn-export-students" data-bs-toggle="modal" data-bs-target="#feesFuExportModal">
                                <i class="bx bx-download me-1"></i> Export PDF
                            </button>
                        @endif
                        @if ($canCollect)
                            <a href="{{ route('admin.fees.add') }}" class="btn admin-btn-primary">
                                <i class="bx bx-plus me-1"></i> Collect Fees
                            </a>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.fees') }}">Fees</a></li>
                        <li class="breadcrumb-item active">Follow Up</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.fees.follow_up')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-status="true"
                    :statuses="['pending' => 'Pending', 'complete' => 'Complete']"
                    :institutes="$institutes"
                    :branches="$branches"
                >
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-semibold">Next Follow Up Date</label>
                        <input type="date" class="form-control form-control-sm" name="follow_up_date"
                            value="{{ request('follow_up_date') }}">
                    </div>
                </x-admin.list-filters>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Fees Follow Up List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1200px;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr.</th>
                                    <th>Branch</th>
                                    <th>Student</th>
                                    <th>Father</th>
                                    <th>Contact</th>
                                    <th>Standard</th>
                                    <th>Due Amount</th>
                                    <th>Next Follow Up</th>
                                    <th class="iv-col-action-md">Status</th>
                                    <th class="text-center iv-col-action-sm">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $invoice->branch?->name ?? '—' }}</td>
                                        <td>{{ $invoice->student?->name ?? '—' }}</td>
                                        <td>{{ $invoice->student?->parent?->father_name ?? '—' }}</td>
                                        <td>{{ $invoice->student?->parent?->phone ?? '—' }}</td>
                                        <td>{{ $invoice->student?->standard?->name ?? '—' }}</td>
                                        <td class="fw-bold iv-text-primary">₹{{ number_format((float) $invoice->due_amount, 2) }}</td>
                                        <td>{{ $invoice->latestFollowUp?->next_follow_up_at?->format('d M Y') ?? '—' }}</td>
                                        <td>
                                            @if ($invoice->isFollowUpPending())
                                                <span class="admin-status-pill is-navy">Pending</span>
                                            @else
                                                <span class="admin-status-pill is-active">Complete</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($canShowUpdates)
                                                <button type="button" class="admin-action-icon btn-fu-view" title="View"
                                                    data-bs-toggle="modal" data-bs-target="#followUpModal"
                                                    data-invoice-id="{{ $invoice->id }}"
                                                    data-student="{{ $invoice->student?->name }}"
                                                    data-next-date="{{ $invoice->latestFollowUp?->next_follow_up_at?->format('Y-m-d') }}"
                                                    data-history="{{ json_encode($invoice->followUpHistoryForUi()) }}">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-5">No pending fee follow-ups.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="followUpModal" tabindex="-1" aria-labelledby="followUpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="followUpModalLabel">Follow Up Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="fuModalStudent"></p>
                    @if ($canAddUpdate)
                    <form method="POST" id="fuModalForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Next Follow Up Date</label>
                                <input type="date" class="form-control" name="next_follow_up_at" id="fuNextDate">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Follow Up Remarks <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" rows="4" name="remark" id="fuRemarks" required placeholder="Enter Remarks"></textarea>
                                <div class="fu-form-note">
                                    Note: If Next Follow-Up Date is not selected, the follow-up will be marked as complete.
                                </div>
                            </div>
                        </div>
                    </form>
                    @endif

                    <div class="fu-history-title">Follow Up History</div>
                    <div class="table-responsive">
                        <table class="table fu-history-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Follow By</th>
                                    <th>Note</th>
                                    <th>Complete Date</th>
                                    <th>Next Follow Date</th>
                                </tr>
                            </thead>
                            <tbody id="fuHistoryBody">
                                <tr><td colspan="4" class="text-center text-muted py-3">No history</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ $canAddUpdate ? 'Cancel' : 'Close' }}</button>
                    @if ($canAddUpdate)
                        <button type="button" class="btn admin-btn-primary" id="fuSubmitBtn">Submit</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($canExportPdf)
    <div class="modal fade admin-modal" id="feesFuExportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('admin.fees.follow_up.export_pdf') }}" id="feesFuExportForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Export Fees Follow Up PDF</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            @if (!empty($isPlatform))
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Institute <span class="text-danger">*</span></label>
                                    <select class="form-select" name="institute_id" id="fuExportInstitute" required data-no-search>
                                        <option value="">Select Institute</option>
                                        @foreach ($institutes as $inst)
                                            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="institute_id" id="fuExportInstitute" value="{{ $instituteId }}">
                            @endif
                            <div class="col-12">
                                <label class="form-label fw-semibold">Branch</label>
                                <select class="form-select" name="branch_id" id="fuExportBranch" data-no-search
                                    @if (!empty($isPlatform)) disabled @endif>
                                    <option value="">{{ !empty($isPlatform) ? 'Select institute first' : 'All Branches' }}</option>
                                    @unless (!empty($isPlatform))
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    @endunless
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Batch (multiple)</label>
                                <select class="form-select no-search-select" name="batch_ids[]" id="fuExportBatches" multiple size="6"
                                    @if (!empty($isPlatform)) disabled @endif>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple batches.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="fuExportStart">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="fuExportEnd">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-export-students">
                            <i class="bx bx-download me-1"></i> Export PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            const followUpUrl = @json(route('admin.fees.follow_up'));
            let activeInvoiceId = null;

            document.querySelectorAll('.btn-fu-view').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activeInvoiceId = btn.dataset.invoiceId;
                    document.getElementById('fuModalStudent').textContent = btn.dataset.student || '';
                    const nextDateEl = document.getElementById('fuNextDate');
                    const remarksEl = document.getElementById('fuRemarks');
                    if (nextDateEl) nextDateEl.value = btn.dataset.nextDate || '';
                    if (remarksEl) remarksEl.value = '';

                    let history = [];
                    try { history = JSON.parse(btn.getAttribute('data-history') || '[]'); } catch (e) {}

                    const tbody = document.getElementById('fuHistoryBody');
                    tbody.innerHTML = '';
                    if (!history.length) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No history</td></tr>';
                        return;
                    }
                    history.forEach(function (item) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = '<td>' + (item.by || '—') + '</td><td>' + (item.note || '—') + '</td><td>' +
                            (item.complete || '—') + '</td><td>' + (item.next || '—') + '</td>';
                        tbody.appendChild(tr);
                    });
                });
            });

            document.getElementById('fuSubmitBtn')?.addEventListener('click', async function () {
                if (!activeInvoiceId) return;
                const form = document.getElementById('fuModalForm');
                const btn = this;
                btn.disabled = true;
                try {
                    const body = new FormData(form);
                    const res = await fetch(followUpUrl + '/' + activeInvoiceId, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: body
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.message || 'Failed');
                    if (window.Swal) {
                        await Swal.fire({ icon: 'success', title: 'Saved', text: data.message, timer: 1800, showConfirmButton: false });
                    }
                    window.location.reload();
                } catch (e) {
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Could not save.' });
                } finally {
                    btn.disabled = false;
                }
            });

            const lookupsUrl = @json(\App\Support\AdminUrl::lookups());
            const isPlatform = @json(!empty($isPlatform));
            const fixedInstituteId = @json($instituteId ? (string) $instituteId : '');
            const instituteSelect = document.getElementById('fuExportInstitute');
            const batchSelect = document.getElementById('fuExportBatches');
            const branchSelect = document.getElementById('fuExportBranch');

            function currentInstituteId() {
                if (isPlatform) {
                    return (instituteSelect && instituteSelect.value) ? String(instituteSelect.value) : '';
                }
                return fixedInstituteId || (instituteSelect && instituteSelect.value ? String(instituteSelect.value) : '');
            }

            function lookupItems(json) {
                const raw = json && json.data !== undefined ? json.data : json;
                if (Array.isArray(raw)) return raw;
                if (raw && typeof raw === 'object') return Object.values(raw);
                return [];
            }

            async function fetchLookup(path, params) {
                const qs = new URLSearchParams(params || {});
                const res = await fetch(lookupsUrl + '/' + path + '?' + qs.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const json = await res.json().catch(function () { return {}; });
                if (!res.ok || json.success === false) {
                    throw new Error(json.message || 'Lookup failed');
                }
                return lookupItems(json);
            }

            async function loadExportBranches() {
                if (!branchSelect) return;
                const iid = currentInstituteId();
                if (!iid) {
                    branchSelect.innerHTML = '<option value="">Select institute first</option>';
                    branchSelect.disabled = true;
                    if (batchSelect) {
                        batchSelect.innerHTML = '';
                        batchSelect.disabled = true;
                    }
                    return;
                }

                branchSelect.disabled = true;
                branchSelect.innerHTML = '<option value="">Loading…</option>';
                try {
                    const items = await fetchLookup('branches', { institute_id: iid });
                    branchSelect.innerHTML = '<option value="">All Branches</option>';
                    items.forEach(function (item) {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.text || item.name || ('#' + item.id);
                        branchSelect.appendChild(opt);
                    });
                    branchSelect.disabled = false;
                } catch (e) {
                    branchSelect.innerHTML = '<option value="">Failed to load branches</option>';
                    branchSelect.disabled = false;
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Branches', text: e.message || 'Could not load branches.' });
                    }
                    return;
                }
                await loadExportBatches();
            }

            async function loadExportBatches() {
                if (!batchSelect) return;
                const iid = currentInstituteId();
                if (!iid) {
                    batchSelect.innerHTML = '';
                    batchSelect.disabled = true;
                    return;
                }

                batchSelect.disabled = true;
                const params = { institute_id: iid };
                if (branchSelect && branchSelect.value) {
                    params.branch_id = branchSelect.value;
                }
                batchSelect.innerHTML = '<option value="">Loading…</option>';
                try {
                    const items = await fetchLookup('batches', params);
                    batchSelect.innerHTML = '';
                    items.forEach(function (item) {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.text || item.name || ('#' + item.id);
                        batchSelect.appendChild(opt);
                    });
                    if (!items.length) {
                        batchSelect.innerHTML = '<option value="" disabled>No batches</option>';
                    }
                    batchSelect.disabled = false;
                } catch (e) {
                    batchSelect.innerHTML = '<option value="" disabled>Failed to load</option>';
                    batchSelect.disabled = false;
                }
            }

            if (isPlatform && instituteSelect) {
                instituteSelect.addEventListener('change', function () {
                    loadExportBranches();
                });
                branchSelect?.addEventListener('change', function () {
                    loadExportBatches();
                });
                document.getElementById('feesFuExportModal')?.addEventListener('show.bs.modal', function () {
                    instituteSelect.value = '';
                    if (branchSelect) {
                        branchSelect.innerHTML = '<option value="">Select institute first</option>';
                        branchSelect.disabled = true;
                    }
                    if (batchSelect) {
                        batchSelect.innerHTML = '';
                        batchSelect.disabled = true;
                    }
                });
                document.getElementById('feesFuExportForm')?.addEventListener('submit', function (e) {
                    if (!currentInstituteId()) {
                        e.preventDefault();
                        if (window.Swal) {
                            Swal.fire({ icon: 'warning', title: 'Select institute', text: 'Please select an institute before exporting.' });
                        }
                    }
                });
            } else {
                branchSelect?.addEventListener('change', loadExportBatches);
                document.getElementById('feesFuExportModal')?.addEventListener('show.bs.modal', loadExportBatches);
            }
        })();
    </script>
@endpush
