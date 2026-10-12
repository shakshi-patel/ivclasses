@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($homework) && $homework;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-edit"></i>
                    {{ $isEdit ? 'Edit' : 'Add' }} Homework
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.homework') }}">Homework</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.homework') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit' : 'Add' }} Homework</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.homework.update', $homework) : route('admin.homework.store') }}"
                                id="homeworkForm"
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
                                        <select class="form-select" name="batch_id" id="hwBatchSelect" required data-placeholder="Select batch">
                                            <option value="">Select batch</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Subject</label>
                                        <select class="form-select" name="subject_id" id="hwSubjectSelect" data-placeholder="Select subject">
                                            <option value="">Select subject (optional)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Title <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="title" required
                                            value="{{ old('title', $homework?->title) }}" placeholder="e.g. Chapter 4 Exercise">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type <span class="req">*</span></label>
                                        <select class="form-select" name="type" id="hwTypeSelect" required data-no-search>
                                            @foreach (\App\Models\Homework::TYPES as $value => $label)
                                                <option value="{{ $value }}" @selected(old('type', $homework?->type ?? 'text') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Homework Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="homework_date" required
                                            value="{{ old('homework_date', optional($homework?->homework_date)->format('Y-m-d') ?? date('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Submission Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="due_date" required
                                            value="{{ old('due_date', optional($homework?->due_date)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-12" id="hwFileWrap">
                                        <label class="form-label">Homework Document</label>
                                        <p class="text-muted small mb-2">Upload PDF, Word, image or ZIP (optional for Text type; required for File / File &amp; Text).</p>
                                        @if ($isEdit && $homework?->attachment_path)
                                            <div class="mb-2">
                                                <a href="{{ $homework->attachmentUrl() }}" target="_blank" rel="noopener" class="small">
                                                    <i class="bx bx-link-external"></i> Current file
                                                </a>
                                                <div class="form-check mt-1">
                                                    <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="hwRemoveFile">
                                                    <label class="form-check-label small" for="hwRemoveFile">Remove current file</label>
                                                </div>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" name="attachment" id="hwAttachment"
                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                    </div>
                                    <div class="col-12" id="hwTextWrap">
                                        <label class="form-label">Homework Text <span class="req">*</span></label>
                                        <textarea class="form-control" name="description" id="hwDescription" rows="4"
                                            placeholder="Enter homework instructions">{{ old('description', $homework?->description) }}</textarea>
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
    const form = document.getElementById('homeworkForm');
    const batchSelect = document.getElementById('hwBatchSelect');
    const subjectSelect = document.getElementById('hwSubjectSelect');
    const typeSelect = document.getElementById('hwTypeSelect');
    const fileWrap = document.getElementById('hwFileWrap');
    const textWrap = document.getElementById('hwTextWrap');
    const fileInput = document.getElementById('hwAttachment');
    const descInput = document.getElementById('hwDescription');
    const branchEl = form?.querySelector('[name="branch_id"]');
    const lookupsUrl = form?.getAttribute('data-cascade-url') || @json(\App\Support\AdminUrl::lookups());
    let defaultBatchId = @json(old('batch_id', $defaultBatchId));
    let defaultSubjectId = @json(old('subject_id', $defaultSubjectId));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = window.jQuery;
    const hasExistingFile = @json($isEdit && !empty($homework?->attachment_path));

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

    function toggleTypeFields() {
        const type = typeSelect?.value || 'text';
        const needsFile = type === 'file' || type === 'file_text';
        const needsText = type === 'text' || type === 'file_text';
        if (fileWrap) fileWrap.style.display = '';
        if (textWrap) textWrap.style.display = needsText ? '' : 'none';
        if (fileInput) fileInput.required = needsFile && !hasExistingFile;
        if (descInput) descInput.required = needsText;
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
        $(branchEl).on('change.hwCascade select2:select.hwCascade select2:clear.hwCascade', onBranchChanged);
    }
    if ($ && batchSelect) {
        $(batchSelect).on('change.hwCascade select2:select.hwCascade', fillSubjects);
    }
    form?.addEventListener('institute-cascade:done', function() {
        setTimeout(loadBatches, 50);
    });
    typeSelect?.addEventListener('change', toggleTypeFields);
    toggleTypeFields();

    if (getInstituteId() && getBranchId()) {
        loadBatches();
    }

    form?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        try {
            await AdminAjax.submitForm(this, { redirectUrl: @json(route('admin.homework')) });
        } catch (err) {
            if (btn) btn.disabled = false;
        }
    });
})();
</script>
@endpush
