@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-user-pin"></i>
                            Inquiry
                        </h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('inquiry.export'))
                            <a href="{{ route('admin.export.inquiries', request()->query()) }}" class="btn btn-outline-secondary">
                                <i class="bx bx-download me-1"></i> Export
                            </a>
                        @endif
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('inquiry.add'))
                            <a href="{{ route('admin.inquiry.add') }}" class="btn admin-btn-primary">
                                <i class="bx bx-plus me-1"></i> Add Inquiry
                            </a>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Inquiry</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.inquiry')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-standard="true"
                    :show-status="true"
                    :statuses="['new' => 'New', 'follow_up' => 'Follow Up', 'converted' => 'Converted', 'lost' => 'Lost']"
                    :institutes="$institutes"
                    :branches="$branches"
                    :standards="$standards"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Inquiry List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1100px;">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Sr.</th>
                                    <th>Name</th>
                                    <th>Enquiry Date</th>
                                    <th>Contact</th>
                                    <th>Standard &amp; Follow Up</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 220px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inquiries as $i => $row)
                                    @php
                                        $latest = $row->followUps->first();
                                    @endphp
                                    <tr data-id="{{ $row->id }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $row->student_name }}</td>
                                        <td>{{ optional($row->inquiry_date)->format('d M Y') ?? '—' }}</td>
                                        <td>
                                            {{ $row->phone ?: '—' }}
                                            @if ($row->phone_2)
                                                <div class="text-muted small">{{ $row->phone_2 }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold" style="color: var(--iv-primary-dark); font-size: 0.86rem;">
                                                Std: {{ $row->standard?->name ?? '—' }}
                                            </div>
                                            <div class="text-muted small">
                                                Follow Up: {{ optional($row->next_follow_up_at)->format('d M Y') ?? '—' }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="admin-status-pill {{ $row->status === 'converted' ? 'is-active' : ($row->status === 'lost' ? 'is-muted' : 'is-navy') }}">
                                                {{ str_replace('_', ' ', ucfirst($row->status)) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('inquiry.show'))
                                                <button type="button" class="admin-action-icon btn-inq-view" title="View"
                                                    data-id="{{ $row->id }}">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            @endif
                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('inquiry.receipt'))
                                                <button type="button" class="admin-action-icon btn-inq-receipt" title="Receipt"
                                                    data-url="{{ route('admin.inquiry.receipt', $row) }}">
                                                    <i class="bx bx-receipt"></i>
                                                </button>
                                            @endif
                                            @if (!$row->isConverted() && (auth()->user()?->isPlatformUser() || auth()->user()?->can('inquiry.edit')))
                                                <a href="{{ route('admin.inquiry.edit', $row) }}" class="admin-action-icon" title="Edit">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            @endif
                                            @if (!$row->isConverted() && (auth()->user()?->isPlatformUser() || auth()->user()?->can('inquiry.delete')))
                                                <button type="button" class="admin-action-icon btn-delete-inquiry" title="Delete"
                                                    data-id="{{ $row->id }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                            @if (!$row->isConverted() && (auth()->user()?->isPlatformUser() || auth()->user()?->can('inquiry.convert')))
                                                <a href="{{ route('admin.inquiry.convert', $row) }}" class="admin-action-icon inq-action-convert" title="Convert">
                                                    <i class="bx bx-transfer"></i>
                                                </a>
                                            @endif
                                            @if ($row->isConverted() && $row->converted_student_id && (auth()->user()?->isPlatformUser() || auth()->user()?->can('student.show')))
                                                <a href="{{ route('admin.student.show', $row->converted_student_id) }}" class="admin-action-icon" title="View student">
                                                    <i class="bx bx-user"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No inquiries yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="inquiryViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-user-pin me-1"></i> Inquiry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="inquiryViewBody">
                    <div class="text-center text-muted py-4">Loading…</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="inquiryReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inquiryReceiptTitle">Inquiry Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="inquiryReceiptBody">
                    <p class="text-muted mb-0">Loading…</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-secondary" id="inquiryReceiptPrintBtn">
                        <i class="bx bx-printer me-1"></i> Print
                    </button>
                    <a href="#" class="btn btn-export-students" id="inquiryReceiptExportBtn">
                        <i class="bx bx-download me-1"></i> Export
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $inquiryShowTpl = str_replace('999999', '__ID__', route('admin.inquiry.show', ['inquiry' => 999999]));
    $inquiryDestroyTpl = str_replace('999999', '__ID__', route('admin.inquiry.destroy', ['inquiry' => 999999]));
@endphp

@push('styles')
    @include('admin.partials.iv_receipt_styles')
@endpush

@push('scripts')
    <script>
        (function() {
            const showUrl = @json($inquiryShowTpl);
            const destroyUrl = @json($inquiryDestroyTpl);

            document.querySelectorAll('.btn-inq-view').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const modal = new bootstrap.Modal(document.getElementById('inquiryViewModal'));
                    const body = document.getElementById('inquiryViewBody');
                    body.innerHTML = '<div class="text-center text-muted py-4">Loading…</div>';
                    modal.show();
                    try {
                        const res = await AdminAjax.get(showUrl.replace('__ID__', btn.dataset.id));
                        const d = res.data;
                        const statusClass = d.status === 'converted' ? 'is-active' : (d.status === 'lost' ? 'is-muted' : 'is-navy');
                        const followUps = (d.follow_ups || []).map(function(f) {
                            return '<div class="inq-view-row"><span class="k">' + (f.follow_up_at || '—') + '</span><span class="v">' + (f.remark || '—') + '</span></div>';
                        }).join('') || '<p class="text-muted small mb-0">No follow-ups yet.</p>';
                        body.innerHTML =
                            '<div class="inq-view-hero">' +
                            '<div class="inq-name">' + (d.student_name || '') + '</div>' +
                            '<div class="inq-date">' + (d.inquiry_code || '') + ' · ' + (d.inquiry_date || '') + '</div>' +
                            '<div class="inq-phone">' + (d.phone || '—') + ' · ' + (d.email || '—') + '</div>' +
                            '<span class="admin-status-pill ' + statusClass + ' mt-2">' + (d.status ? d.status.replace('_', ' ') : '') + '</span>' +
                            '</div>' +
                            '<div class="ie-view-section">' +
                            '<h6 class="ie-view-section-title"><i class="bx bx-detail"></i> Inquiry Information</h6>' +
                            '<div class="ie-view-grid">' +
                            '<div class="ie-view-item"><span class="k">Standard</span><span class="v">' + (d.standard?.name || '—') + '</span></div>' +
                            '<div class="ie-view-item"><span class="k">Branch</span><span class="v">' + (d.branch?.name || '—') + '</span></div>' +
                            '<div class="ie-view-item"><span class="k">Father</span><span class="v">' + (d.father_name || '—') + '</span></div>' +
                            '<div class="ie-view-item"><span class="k">Next Follow-up</span><span class="v">' + (d.next_follow_up_at || '—') + '</span></div>' +
                            '<div class="ie-view-item"><span class="k">Form Fees</span><span class="v">' + (d.form_fees || '—') + '</span></div>' +
                            '</div></div>' +
                            '<div class="inq-view-remarks"><strong>Follow-ups</strong>' + followUps + '</div>';
                    } catch (e) {
                        body.innerHTML = '<div class="text-danger">' + e.message + '</div>';
                    }
                });
            });

            document.querySelectorAll('.btn-delete-inquiry').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({ title: 'Delete inquiry?', confirmText: 'Yes, delete' });
                    if (!ok) return;
                    try {
                        await AdminAjax.destroy(destroyUrl.replace('__ID__', btn.dataset.id));
                        AdminAjax.removeRow(btn.closest('tr'));
                        await AdminAjax.success('Deleted.');
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });

            const receiptModalEl = document.getElementById('inquiryReceiptModal');
            if (receiptModalEl) {
                const receiptModal = new bootstrap.Modal(receiptModalEl);
                const body = document.getElementById('inquiryReceiptBody');
                const title = document.getElementById('inquiryReceiptTitle');
                const exportBtn = document.getElementById('inquiryReceiptExportBtn');
                const printBtn = document.getElementById('inquiryReceiptPrintBtn');

                document.querySelectorAll('.btn-inq-receipt').forEach(function (btn) {
                    btn.addEventListener('click', async function () {
                        body.innerHTML = '<p class="text-muted mb-0">Loading…</p>';
                        title.textContent = 'Inquiry Receipt';
                        exportBtn.href = '#';
                        receiptModal.show();
                        try {
                            const res = await fetch(btn.dataset.url, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const json = await res.json();
                            if (!res.ok || !json.success) throw new Error(json.message || 'Could not load receipt.');
                            const payload = json.data || {};
                            title.textContent = payload.title || 'Inquiry Receipt';
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
