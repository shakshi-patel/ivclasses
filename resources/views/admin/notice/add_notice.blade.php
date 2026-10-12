@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($notice) && $notice;
        $formScope = \App\Support\AdminFormScope::for();
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title"><i class="bx bx-bell"></i> {{ $isEdit ? 'Edit' : 'Add' }} Notice</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.notice') }}">Notice</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.notice') }}" class="btn btn-sm stu-top-back admin-btn-primary"><i class="bx bx-left-arrow-alt me-1"></i> Back</a>
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit' : 'Add' }} Notice</div>
                        <div class="stu-form-body">
                            <form method="POST" action="{{ $isEdit ? route('admin.notice.update', $notice) : route('admin.notice.store') }}"
                                id="noticeForm" enctype="multipart/form-data" data-institute-cascade data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}">
                                @csrf
                                @if ($isEdit) @method('PUT') @endif
                                <div class="row g-3">
                                    @include('admin.partials.form_institute_field', ['instituteId' => old('institute_id', $defaultInstituteId), 'institutes' => $institutes, 'label' => 'Classes / Institute'])

                                    @if ($formScope->showBranchField())
                                        <div class="col-md-6">
                                            <label class="form-label">Branch</label>
                                            <select class="form-select" name="branch_ids[]" id="noticeBranchSelect" multiple
                                                data-placeholder="Select branch (optional — leave empty for all)">
                                            </select>
                                            <div class="form-text">Select one or more branches. Leave empty for all branches.</div>
                                        </div>
                                    @endif

                                    <div class="col-md-6">
                                        <label class="form-label">Batch</label>
                                        <select class="form-select" name="batch_ids[]" id="noticeBatchSelect" multiple
                                            data-placeholder="Select batch (optional — leave empty for all)">
                                        </select>
                                        <div class="form-text">Select one or more batches. Leave empty for all batches.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Notice <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="title" required value="{{ old('title', $notice?->title) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="notice_date" required value="{{ old('notice_date', optional($notice?->notice_date)->format('Y-m-d') ?? date('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Audience</label>
                                        <select class="form-select" name="audience">
                                            @foreach (\App\Models\Notice::AUDIENCES as $value => $label)
                                                <option value="{{ $value }}" @selected(old('audience', $notice?->audience ?? 'all') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="active" @selected(old('status', $notice?->status ?? 'active') === 'active')>Active</option>
                                            <option value="inactive" @selected(old('status', $notice?->status) === 'inactive')>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notice Photo</label>
                                        @if ($isEdit && $notice?->image_path)
                                            <div class="mb-2"><a href="{{ $notice->imageUrl() }}" target="_blank" class="small">Current image</a>
                                                <div class="form-check"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeNoticeImage"><label class="form-check-label small" for="removeNoticeImage">Remove image</label></div>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" name="image" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Notice Description</label>
                                        <textarea class="form-control" name="body" rows="4">{{ old('body', $notice?->body) }}</textarea>
                                    </div>
                                </div>
                                <div class="master-stu-form-actions mt-4">
                                    <button type="submit" class="btn admin-btn-primary px-4"><i class="bx bx-save me-1"></i> Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const form = document.getElementById('noticeForm');
    const branchSelect = document.getElementById('noticeBranchSelect');
    const batchSelect = document.getElementById('noticeBatchSelect');
    const instituteEl = form?.querySelector('[data-cascade-source]');
    const lookupsUrl = form?.getAttribute('data-cascade-url') || @json(\App\Support\AdminUrl::lookups());
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = window.jQuery;
    let pendingBranchIds = @json($defaultBranchIds ?? []);
    let pendingBatchIds = @json($defaultBatchIds ?? []);

    function getInstituteId() {
        if (!instituteEl) return '';
        if ($ && $(instituteEl).hasClass('select2-hidden-accessible')) {
            return $(instituteEl).val() || '';
        }
        return instituteEl.value || '';
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
            window.initAdminSearchableSelects(el?.parentElement || form);
        }
    }

    function getMultiValues(el) {
        if (!el) return [];
        if ($ && $(el).hasClass('select2-hidden-accessible')) {
            return ($(el).val() || []).map(String);
        }
        return Array.from(el.selectedOptions).map(opt => String(opt.value));
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
        if (!branchSelect) return;
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
        if (!batchSelect) return;
        if (!instituteId) {
            fillSelect(batchSelect, [], keepValues);
            return;
        }
        const branchIds = branchSelect ? getMultiValues(branchSelect) : pendingBranchIds;
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

    async function initCascade() {
        const branchKeep = pendingBranchIds.length ? pendingBranchIds : getMultiValues(branchSelect);
        const batchKeep = pendingBatchIds.length ? pendingBatchIds : getMultiValues(batchSelect);
        pendingBranchIds = [];
        pendingBatchIds = [];
        await loadBranches(branchKeep);
        await loadBatches(batchKeep);
    }

    instituteEl?.addEventListener('change', async () => {
        await loadBranches([]);
        await loadBatches([]);
    });
    if ($ && instituteEl) {
        $(instituteEl).on('change.noticeCascade select2:select.noticeCascade select2:clear.noticeCascade', async () => {
            await loadBranches([]);
            await loadBatches([]);
        });
    }

    branchSelect?.addEventListener('change', () => {
        loadBatches(getMultiValues(batchSelect));
    });
    if ($ && branchSelect) {
        $(branchSelect).on('change.noticeCascade select2:select.noticeCascade select2:unselect.noticeCascade', () => {
            loadBatches(getMultiValues(batchSelect));
        });
    }

    form?.addEventListener('institute-cascade:done', () => setTimeout(initCascade, 50));
    if (getInstituteId()) {
        initCascade();
    } else if (!branchSelect) {
        loadBatches(pendingBatchIds);
        pendingBatchIds = [];
    }

    form?.addEventListener('submit', async e => {
        e.preventDefault();
        await AdminAjax.submitForm(form, { redirectUrl: @json(route('admin.notice')) });
    });
})();
</script>
@endpush
