@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <h4 class="admin-page-title"><i class="bx bx-calendar-star"></i> Holiday</h4>
                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('holiday.add'))
                        <button type="button" class="btn admin-btn-primary" id="btnAddHoliday"><i class="bx bx-plus me-1"></i> Add Holiday</button>
                    @endif
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Holiday</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.holiday')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :institutes="$institutes ?? collect()"
                    :branches="$branches ?? collect()"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Holiday List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sr.</th><th>Title</th><th>Branch</th><th>Batch</th><th>Dates</th><th>Created By</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            @php
                                $authUser = auth()->user();
                                $isPlatformUser = (bool) $authUser?->isPlatformUser();
                                $canShowHoliday = $isPlatformUser || (bool) $authUser?->can('holiday.show');
                                $canEditHoliday = $isPlatformUser || (bool) $authUser?->can('holiday.edit');
                                $canDeleteHoliday = $isPlatformUser || (bool) $authUser?->can('holiday.delete');
                            @endphp
                            <tbody>
                                @forelse ($holidays as $holiday)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $holiday->title }}</td>
                                        <td>{{ $holiday->branchesLabel() }}</td>
                                        <td>{{ $holiday->batchesLabel() }}</td>
                                        <td>{{ $holiday->dateLabel() }}</td>
                                        <td>{{ $holiday->creator?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            @if ($canShowHoliday)
                                                <button type="button" class="admin-action-icon btn-view-hol" title="View"
                                                    data-title="{{ $holiday->title }}"
                                                    data-dates="{{ $holiday->dateLabel() }}"
                                                    data-branch="{{ $holiday->branchesLabel() }}"
                                                    data-batch="{{ $holiday->batchesLabel() }}"
                                                    data-reason="{{ $holiday->reason }}"><i class="bx bx-show"></i></button>
                                            @endif
                                            @if ($canEditHoliday)
                                                <button type="button" class="admin-action-icon btn-edit-hol" title="Edit"
                                                    data-id="{{ $holiday->id }}"
                                                    data-institute-id="{{ $holiday->institute_id }}"
                                                    data-branch-ids="{{ $holiday->branches->pluck('id')->implode(',') }}"
                                                    data-batch-ids="{{ $holiday->batches->pluck('id')->implode(',') }}"
                                                    data-title="{{ $holiday->title }}"
                                                    data-start="{{ $holiday->start_date?->format('Y-m-d') }}"
                                                    data-end="{{ $holiday->end_date?->format('Y-m-d') }}"
                                                    data-reason="{{ $holiday->reason }}"><i class="bx bx-pencil"></i></button>
                                            @endif
                                            @if ($canDeleteHoliday)
                                                <button type="button" class="admin-action-icon btn-del-hol" title="Delete"
                                                    data-url="{{ route('admin.holiday.destroy', $holiday) }}"><i class="bx bx-trash"></i></button>
                                            @endif
                                            @if (! $canShowHoliday && ! $canEditHoliday && ! $canDeleteHoliday)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No holidays yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="holidayFormModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="holidayFormTitle">Add Holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="holidayId">
                    @if ($isPlatform)
                        <div class="mb-3">
                            <label class="form-label">Institute <span class="req">*</span></label>
                            <select class="form-select" id="holidayInstitute">
                                @foreach ($institutes as $inst)
                                    <option value="{{ $inst->id }}" @selected($inst->id == $defaultInstituteId)>{{ $inst->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" id="holidayInstitute" value="{{ $defaultInstituteId }}">
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <p class="text-muted small mb-1">Leave empty for all branches.</p>
                            <select class="form-select" id="holidayBranch" multiple data-placeholder="All Branches">
                                @foreach ($modalBranches ?? $branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch</label>
                            <p class="text-muted small mb-1">Leave empty for all batches.</p>
                            <select class="form-select" id="holidayBatch" multiple data-placeholder="All Batches">
                                @foreach ($batches as $batch)
                                    <option value="{{ $batch->id }}" data-branch-id="{{ $batch->branch_id }}">{{ $batch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Holiday Title <span class="req">*</span></label>
                            <input type="text" class="form-control" id="holidayTitle" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="req">*</span></label>
                            <input type="date" class="form-control" id="holidayStart" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date <span class="req">*</span></label>
                            <input type="date" class="form-control" id="holidayEnd" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" id="holidayReason" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="text-danger small mt-2" id="holidayError" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn admin-btn-primary" id="holidaySaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="holidayViewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Holiday Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="inq-view-hero"><div class="inq-name" id="holViewName">—</div><div class="inq-date" id="holViewDates">—</div></div>
                    <p class="mb-1" id="holViewBranchBatch">—</p>
                    <p class="mb-0"><strong>Reason:</strong> <span id="holViewReason">—</span></p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const storeUrl = @json(route('admin.holiday.store'));
    const updateUrlTpl = @json(route('admin.holiday.update', ['holiday' => 0])).replace(/\/0$/, '');
    const lookupsUrl = @json(\App\Support\AdminUrl::lookups());
    const formModal = new bootstrap.Modal(document.getElementById('holidayFormModal'));
    const viewModal = new bootstrap.Modal(document.getElementById('holidayViewModal'));
    const branchSelect = document.getElementById('holidayBranch');
    const batchSelect = document.getElementById('holidayBatch');
    const instituteEl = document.getElementById('holidayInstitute');
    const $ = window.jQuery;
    let pendingBranchIds = [];
    let pendingBatchIds = [];

    function getInstituteId() {
        return instituteEl?.value || '';
    }

    function destroySelect2(el) {
        if (!$ || !$.fn.select2 || !el) return;
        const $el = $(el);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }

    function initSelect2(el) {
        if (typeof window.initAdminSearchableSelects === 'function') {
            window.initAdminSearchableSelects(el?.parentElement || document.getElementById('holidayFormModal'));
        }
    }

    function getMultiValues(el) {
        if (!el) return [];
        if ($ && $(el).hasClass('select2-hidden-accessible')) {
            return ($(el).val() || []).map(String);
        }
        return Array.from(el.selectedOptions).map(opt => String(opt.value));
    }

    function setMultiValues(el, values) {
        if (!el) return;
        const ids = (values || []).map(String);
        Array.from(el.options).forEach(opt => {
            opt.selected = ids.includes(String(opt.value));
        });
        if ($ && $(el).hasClass('select2-hidden-accessible')) {
            $(el).val(ids).trigger('change');
        }
    }

    function fillSelect(el, items, keepValues) {
        if (!el) return;
        destroySelect2(el);
        el.innerHTML = '';
        (items || []).forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.text;
            if (item.branch_id) {
                opt.dataset.branchId = item.branch_id;
            }
            if ((keepValues || []).includes(String(item.id))) {
                opt.selected = true;
            }
            el.appendChild(opt);
        });
        initSelect2(el);
    }

    async function loadBranches(keepValues) {
        const instituteId = getInstituteId();
        if (!instituteId) {
            fillSelect(branchSelect, [], keepValues);
            return;
        }
        try {
            const res = await fetch(lookupsUrl + '/branches?institute_id=' + encodeURIComponent(instituteId), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            fillSelect(branchSelect, data.data || [], keepValues);
        } catch (e) {
            fillSelect(branchSelect, [], keepValues);
        }
    }

    async function loadBatches(keepValues) {
        const instituteId = getInstituteId();
        if (!instituteId) {
            fillSelect(batchSelect, [], keepValues);
            return;
        }
        const branchIds = getMultiValues(branchSelect);
        let url = lookupsUrl + '/batches?institute_id=' + encodeURIComponent(instituteId);
        branchIds.forEach(id => {
            url += '&branch_ids[]=' + encodeURIComponent(id);
        });
        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            fillSelect(batchSelect, data.data || [], keepValues);
        } catch (e) {
            fillSelect(batchSelect, [], keepValues);
        }
    }

    function resetForm() {
        document.getElementById('holidayId').value = '';
        document.getElementById('holidayTitle').value = '';
        document.getElementById('holidayStart').value = '';
        document.getElementById('holidayEnd').value = '';
        document.getElementById('holidayReason').value = '';
        document.getElementById('holidayError').hidden = true;
        pendingBranchIds = [];
        pendingBatchIds = [];
        setMultiValues(branchSelect, []);
        setMultiValues(batchSelect, []);
    }

    document.getElementById('btnAddHoliday')?.addEventListener('click', async () => {
        document.getElementById('holidayFormTitle').textContent = 'Add Holiday';
        resetForm();
        await loadBranches([]);
        await loadBatches([]);
        formModal.show();
    });

    instituteEl?.addEventListener('change', async () => {
        pendingBranchIds = [];
        pendingBatchIds = [];
        await loadBranches([]);
        await loadBatches([]);
    });

    branchSelect?.addEventListener('change', () => {
        const keep = getMultiValues(batchSelect);
        loadBatches(keep);
    });
    if ($ && branchSelect) {
        $(branchSelect).on('change.holidayCascade select2:select.holidayCascade select2:unselect.holidayCascade', () => {
            const keep = getMultiValues(batchSelect);
            loadBatches(keep);
        });
    }

    document.body.addEventListener('click', async e => {
        const v = e.target.closest('.btn-view-hol');
        if (v) {
            document.getElementById('holViewName').textContent = v.dataset.title || '—';
            document.getElementById('holViewDates').textContent = v.dataset.dates || '—';
            document.getElementById('holViewBranchBatch').textContent = 'Branch: ' + (v.dataset.branch || '—') + ' | Batch: ' + (v.dataset.batch || '—');
            document.getElementById('holViewReason').textContent = v.dataset.reason || '—';
            viewModal.show();
            return;
        }
        const ed = e.target.closest('.btn-edit-hol');
        if (ed) {
            document.getElementById('holidayId').value = ed.dataset.id;
            document.getElementById('holidayFormTitle').textContent = 'Edit Holiday';
            if (instituteEl) instituteEl.value = ed.dataset.instituteId || '';
            document.getElementById('holidayTitle').value = ed.dataset.title || '';
            document.getElementById('holidayStart').value = ed.dataset.start || '';
            document.getElementById('holidayEnd').value = ed.dataset.end || '';
            document.getElementById('holidayReason').value = ed.dataset.reason || '';
            document.getElementById('holidayError').hidden = true;
            pendingBranchIds = (ed.dataset.branchIds || '').split(',').filter(Boolean);
            pendingBatchIds = (ed.dataset.batchIds || '').split(',').filter(Boolean);
            await loadBranches(pendingBranchIds);
            await loadBatches(pendingBatchIds);
            formModal.show();
            return;
        }
        const del = e.target.closest('.btn-del-hol');
        if (del) {
            const ok = window.AdminAjax
                ? await AdminAjax.confirm({ title: 'Delete holiday?', text: 'This holiday will be removed from the list.', confirmText: 'Yes, delete', icon: 'warning' })
                : confirm('Delete this holiday?');
            if (!ok) return;
            try {
                if (window.AdminAjax) {
                    const data = await AdminAjax.destroy(del.dataset.url);
                    AdminAjax.removeRow(del.closest('tr'));
                    await AdminAjax.success(data.message || 'Holiday deleted.');
                } else {
                    const r = await fetch(del.dataset.url, {
                        method: 'DELETE',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const d = await r.json();
                    if (d.success) location.reload();
                }
            } catch (err) {
                if (window.AdminAjax) await AdminAjax.error(err.message || 'Delete failed.');
            }
            return;
        }
    });

    document.getElementById('holidaySaveBtn')?.addEventListener('click', async () => {
        const id = document.getElementById('holidayId').value;
        const branchIds = getMultiValues(branchSelect).map(v => parseInt(v, 10)).filter(Boolean);
        const batchIds = getMultiValues(batchSelect).map(v => parseInt(v, 10)).filter(Boolean);
        const payload = {
            institute_id: parseInt(getInstituteId(), 10),
            branch_ids: branchIds,
            batch_ids: batchIds,
            title: document.getElementById('holidayTitle').value.trim(),
            start_date: document.getElementById('holidayStart').value,
            end_date: document.getElementById('holidayEnd').value,
            reason: document.getElementById('holidayReason').value.trim() || null,
        };
        const err = document.getElementById('holidayError');
        try {
            const res = await fetch(id ? updateUrlTpl + '/' + id : storeUrl, {
                method: id ? 'PUT' : 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Save failed');
            if (window.AdminAjax) {
                await AdminAjax.success(data.message || (id ? 'Holiday updated.' : 'Holiday saved.'));
            }
            location.reload();
        } catch (e) { err.textContent = e.message; err.hidden = false; }
    });

    initSelect2(branchSelect);
    initSelect2(batchSelect);
})();
</script>
@endpush
