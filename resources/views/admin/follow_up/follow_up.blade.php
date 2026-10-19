@extends('admin.layouts.layout')

@section('content')
    @php
        $canShowUpdates = auth()->user()?->isPlatformUser() || auth()->user()?->can('follow_up.show');
        $canAddUpdate = auth()->user()?->isPlatformUser() || auth()->user()?->can('follow_up.add');
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <h4 class="admin-page-title mb-0">
                        <i class="bx bx-calendar-check"></i>
                        Follow Up
                    </h4>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('follow_up.export'))
                        <a href="{{ route('admin.export.follow_ups', request()->query()) }}" class="btn btn-outline-secondary">
                            <i class="bx bx-download me-1"></i> Export
                        </a>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Follow Up</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.follow_up')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-status="true"
                    :statuses="['pending' => 'Pending', 'complete' => 'Complete']"
                    :institutes="$institutes"
                    :branches="$branches"
                >
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Followup Date</label>
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
                        <h5>Follow Up List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1100px;">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Student Name</th>
                                    <th>Father Name</th>
                                    <th>Contact</th>
                                    <th>Standard</th>
                                    <th>Inquiry Date</th>
                                    <th>Followup Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inquiries as $i => $row)
                                    <tr data-status="{{ $row->isFollowUpPending() ? 'Pending' : 'Complete' }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $row->student_name }}</td>
                                        <td>{{ $row->father_name ?? '—' }}</td>
                                        <td>{{ $row->phone ?? '—' }}</td>
                                        <td>{{ $row->standard?->name ?? '—' }}</td>
                                        <td>{{ optional($row->inquiry_date)->format('d M Y') ?? '—' }}</td>
                                        <td>{{ optional($row->next_follow_up_at)->format('d M Y') ?? '—' }}</td>
                                        <td class="text-center">
                                            @if ($canShowUpdates)
                                                <button type="button"
                                                    class="btn btn-sm admin-btn-primary btn-fu-updates"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#followUpDetailsModal"
                                                    data-inquiry-id="{{ $row->id }}"
                                                    data-student="{{ $row->student_name }}"
                                                    data-inquiry-date="{{ optional($row->inquiry_date)->format('Y-m-d') }}"
                                                    data-followup="{{ optional($row->next_follow_up_at)->format('Y-m-d') }}"
                                                    data-history="{{ json_encode($row->followUpHistoryForUi()) }}">
                                                    <i class="bx bx-show me-1"></i> View Updates
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No follow-ups yet. Add an inquiry with follow-up remarks first.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="followUpDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Follow Up — <span id="fuStudentName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="fuInquiryId">
                    @if ($canAddUpdate)
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Next Follow Up Date</label>
                                <input type="date" class="form-control" id="fuNextDate">
                                <div class="form-text">Leave empty to mark follow-up as complete.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Follow Up Remarks <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="4" id="fuRemarks" placeholder="Enter remarks" required></textarea>
                            </div>
                        </div>
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
@endsection

@php
    $followUpStoreTpl = str_replace('999999', '__ID__', route('admin.follow_up.store', ['inquiry' => 999999]));
@endphp

@push('scripts')
    <script>
        (function() {
            const storeUrl = @json($followUpStoreTpl);

            document.querySelectorAll('.btn-fu-updates').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.getElementById('fuInquiryId').value = btn.dataset.inquiryId;
                    document.getElementById('fuStudentName').textContent = btn.dataset.student || '';
                    const nextDateEl = document.getElementById('fuNextDate');
                    const remarksEl = document.getElementById('fuRemarks');
                    const inquiryDate = btn.dataset.inquiryDate || '';
                    if (nextDateEl) {
                        nextDateEl.min = inquiryDate;
                        nextDateEl.value = btn.dataset.followup || '';
                        if (inquiryDate && nextDateEl.value && nextDateEl.value < inquiryDate) {
                            nextDateEl.value = inquiryDate;
                        }
                    }
                    if (remarksEl) remarksEl.value = '';

                    let history = [];
                    try { history = JSON.parse(btn.dataset.history || '[]'); } catch (e) {}

                    const tbody = document.getElementById('fuHistoryBody');
                    tbody.innerHTML = '';
                    if (!history.length) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No history</td></tr>';
                    } else {
                        history.forEach(function(item) {
                            const tr = document.createElement('tr');
                            tr.innerHTML = '<td>' + (item.by || '—') + '</td><td>' + (item.note || '—') + '</td><td>' + (item.complete || '—') + '</td><td>' + (item.next || '—') + '</td>';
                            tbody.appendChild(tr);
                        });
                    }
                });
            });

            document.getElementById('fuSubmitBtn')?.addEventListener('click', async function() {
                const id = document.getElementById('fuInquiryId').value;
                const remark = document.getElementById('fuRemarks').value.trim();
                if (!remark) {
                    await AdminAjax.error('Please enter follow-up remarks.');
                    return;
                }
                const nextDateEl = document.getElementById('fuNextDate');
                const nextDate = nextDateEl?.value || null;
                const minDate = nextDateEl?.min || '';
                if (nextDate && minDate && nextDate < minDate) {
                    await AdminAjax.error('Follow-up date must be on or after the enquiry date.');
                    nextDateEl.focus();
                    return;
                }
                try {
                    await AdminAjax.post(storeUrl.replace('__ID__', id), {
                        remark: remark,
                        next_follow_up_at: nextDate
                    });
                    bootstrap.Modal.getInstance(document.getElementById('followUpDetailsModal'))?.hide();
                    await AdminAjax.success('Follow-up saved.');
                    window.location.reload();
                } catch (e) {
                    await AdminAjax.error(e.message);
                }
            });
        })();
    </script>
@endpush
