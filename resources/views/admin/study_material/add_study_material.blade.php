@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($studyMaterial) && $studyMaterial;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-book-add"></i>
                    {{ $isEdit ? 'Edit' : 'Add' }} Study Material
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.study_material') }}">Study Material</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.study_material') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit' : 'Add' }} Study Material</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.study_material.update', $studyMaterial) : route('admin.study_material.store') }}"
                                id="studyMaterialForm"
                                enctype="multipart/form-data"
                                data-institute-cascade
                                data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}">
                                @csrf
                                @if ($isEdit) @method('PUT') @endif
                                <div class="row g-3">
                                    @include('admin.partials.form_institute_field', [
                                        'instituteId' => old('institute_id', $defaultInstituteId),
                                        'institutes' => $institutes,
                                        'label' => 'Classes / Institute',
                                    ])
                                    @include('admin.partials.form_branch_field', [
                                        'branchId' => old('branch_id', $defaultBranchId),
                                        'branches' => $branches,
                                        'required' => true,
                                    ])
                                    <div class="col-md-6">
                                        <label class="form-label">Batch <span class="req">*</span></label>
                                        <select class="form-select" name="batch_id" id="smBatchSelect" required data-placeholder="Select batch">
                                            <option value="">Select batch</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Subject</label>
                                        <select class="form-select" name="subject_id" id="smSubjectSelect" data-placeholder="Select subject">
                                            <option value="">Select subject (optional)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Material Title <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="title" required
                                            value="{{ old('title', $studyMaterial?->title) }}" placeholder="Enter material title">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Material Document <span class="req">*</span></label>
                                        @if ($isEdit && $studyMaterial?->file_path)
                                            <div class="mb-2">
                                                <a href="{{ $studyMaterial->fileUrl() }}" target="_blank" rel="noopener" class="small">
                                                    <i class="bx bx-link-external"></i> Current file{{ $studyMaterial->file_name ? ': '.$studyMaterial->file_name : '' }}
                                                </a>
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="remove_file" value="1" id="smRemoveFile">
                                                    <label class="form-check-label small" for="smRemoveFile">Remove current file</label>
                                                </div>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" name="file" id="smFile"
                                            accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.zip"
                                            {{ $isEdit && $studyMaterial?->file_path ? '' : 'required' }}>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Material Description</label>
                                        <textarea class="form-control" name="description" rows="4"
                                            placeholder="Enter material description">{{ old('description', $studyMaterial?->description) }}</textarea>
                                    </div>
                                </div>
                                <div class="master-stu-form-actions mt-4">
                                    <button type="submit" class="btn admin-btn-primary px-4">
                                        <i class="bx bx-save me-1"></i> Save
                                    </button>
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
    const form = document.getElementById('studyMaterialForm');
    const batchSelect = document.getElementById('smBatchSelect');
    const subjectSelect = document.getElementById('smSubjectSelect');
    const fileInput = document.getElementById('smFile');
    const branchEl = form?.querySelector('[name="branch_id"]');
    const lookupsUrl = form?.getAttribute('data-cascade-url') || @json(\App\Support\AdminUrl::lookups());
    let defaultBatchId = @json(old('batch_id', $defaultBatchId));
    let defaultSubjectId = @json(old('subject_id', $defaultSubjectId));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = window.jQuery;
    const hasExistingFile = @json($isEdit && !empty($studyMaterial?->file_path));

    function getInstituteId() {
        return form?.querySelector('[data-cascade-source]')?.value || '';
    }

    function getBranchId() {
        if (!branchEl) return '';
        if ($ && $(branchEl).hasClass('select2-hidden-accessible')) {
            return $(branchEl).val() || '';
        }
        return branchEl.value || '';
    }

    function refreshSelect(el) {
        if (!$ || !$.fn.select2 || !el || el.hasAttribute('data-no-search')) return;
        const $el = $(el);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        if (typeof window.initAdminSearchableSelects === 'function') {
            window.initAdminSearchableSelects(el.parentElement || form);
        }
    }

    async function loadBatches() {
        const instituteId = getInstituteId();
        const branchId = getBranchId();
        if (!batchSelect || !instituteId || !branchId) {
            if (batchSelect) {
                batchSelect.innerHTML = '<option value="">Select batch</option>';
                refreshSelect(batchSelect);
            }
            if (subjectSelect) {
                subjectSelect.innerHTML = '<option value="">Select subject (optional)</option>';
                refreshSelect(subjectSelect);
            }
            return;
        }
        try {
            const res = await fetch(
                lookupsUrl + '/batches?institute_id=' + encodeURIComponent(instituteId) + '&branch_id=' + encodeURIComponent(branchId),
                { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } }
            );
            const json = await res.json();
            if (!res.ok || json.success === false) throw new Error(json.message || 'Failed to load batches');
            const items = json.data || [];
            batchSelect.innerHTML = '<option value="">Select batch</option>';
            items.forEach(function(item) {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.text;
                opt.dataset.subjects = JSON.stringify(item.subjects || []);
                if (String(item.id) === String(defaultBatchId)) opt.selected = true;
                batchSelect.appendChild(opt);
            });
            refreshSelect(batchSelect);
            fillSubjects();
            defaultBatchId = null;
        } catch (e) {
            console.error(e);
            batchSelect.innerHTML = '<option value="">Select batch</option>';
            refreshSelect(batchSelect);
        }
    }

    function fillSubjects() {
        if (!subjectSelect || !batchSelect) return;
        subjectSelect.innerHTML = '<option value="">Select subject (optional)</option>';
        const opt = batchSelect.selectedOptions[0];
        if (!opt || !opt.dataset.subjects) {
            refreshSelect(subjectSelect);
            return;
        }
        let subjects = [];
        try { subjects = JSON.parse(opt.dataset.subjects); } catch (e) {}
        subjects.forEach(function(item) {
            const sopt = document.createElement('option');
            sopt.value = item.id;
            sopt.textContent = item.text;
            if (String(item.id) === String(defaultSubjectId)) sopt.selected = true;
            subjectSelect.appendChild(sopt);
        });
        refreshSelect(subjectSelect);
        defaultSubjectId = null;
    }

    function onBranchChanged() {
        defaultBatchId = null;
        defaultSubjectId = null;
        loadBatches();
    }

    batchSelect?.addEventListener('change', fillSubjects);
    branchEl?.addEventListener('change', onBranchChanged);
    if ($ && branchEl) {
        $(branchEl).on('change.smCascade select2:select.smCascade select2:clear.smCascade', onBranchChanged);
    }
    if ($ && batchSelect) {
        $(batchSelect).on('change.smCascade select2:select.smCascade', fillSubjects);
    }
    form?.addEventListener('institute-cascade:done', function() {
        setTimeout(loadBatches, 50);
    });

    if (fileInput) {
        fileInput.required = !hasExistingFile;
    }

    if (getInstituteId() && getBranchId()) {
        loadBatches();
    }

    form?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        try {
            await AdminAjax.submitForm(this, { redirectUrl: @json(route('admin.study_material')) });
        } catch (err) {
            if (btn) btn.disabled = false;
        }
    });
})();
</script>
@endpush
