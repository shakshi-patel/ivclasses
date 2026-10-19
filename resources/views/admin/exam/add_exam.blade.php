@extends('admin.layouts.layout')

@section('content')
    @php $isEdit = isset($exam) && $exam; @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title"><i class="bx bx-notepad"></i> {{ $isEdit ? 'Edit' : 'Create' }} Exam</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.exam') }}">Exam</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Create' }}</li>
                    </ol>
                </nav>
                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.exam') }}" class="btn btn-sm stu-top-back admin-btn-primary"><i class="bx bx-left-arrow-alt me-1"></i> Back</a>
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit' : 'Create' }} Exam</div>
                        <div class="stu-form-body">
                            <form method="POST" action="{{ $isEdit ? route('admin.exam.update', $exam) : route('admin.exam.store') }}"
                                id="examForm" enctype="multipart/form-data" data-institute-cascade data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}">
                                @csrf
                                @if ($isEdit) @method('PUT') @endif
                                <div class="row g-3">
                                    @include('admin.partials.form_institute_field', ['instituteId' => old('institute_id', $defaultInstituteId), 'institutes' => $institutes, 'label' => 'Classes / Institute'])
                                    @include('admin.partials.form_branch_field', ['branchId' => old('branch_id', $defaultBranchId), 'branches' => $branches, 'required' => true])
                                    <div class="col-md-6">
                                        <label class="form-label">Batch <span class="req">*</span></label>
                                        <select class="form-select" name="batch_id" id="exBatchSelect" required><option value="">Select batch</option></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Subject</label>
                                        <select class="form-select" name="subject_id" id="exSubjectSelect"><option value="">Select subject (optional)</option></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Exam Title <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="title" required value="{{ old('title', $exam?->title) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Exam Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="exam_date" required value="{{ old('exam_date', optional($exam?->exam_date)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" class="form-control" name="start_time" value="{{ old('start_time', $exam?->start_time ? substr($exam->start_time, 0, 5) : '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">End Time</label>
                                        <input type="time" class="form-control" name="end_time" value="{{ old('end_time', $exam?->end_time ? substr($exam->end_time, 0, 5) : '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Exam Hours</label>
                                        <input type="text" class="form-control" name="duration_label" value="{{ old('duration_label', $exam?->duration_label) }}" placeholder="e.g. 1 Hour">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Maximum Marks <span class="req">*</span></label>
                                        <input type="number" class="form-control" name="total_marks" min="1" step="0.01" required value="{{ old('total_marks', $exam?->total_marks ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Exam Document</label>
                                        @if ($isEdit && $exam?->attachment_path)
                                            <div class="mb-2"><a href="{{ $exam->attachmentUrl() }}" target="_blank" class="small">Current file</a>
                                                <div class="form-check"><input class="form-check-input" type="checkbox" name="remove_attachment" value="1"><label class="form-check-label small">Remove file</label></div>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Exam Text</label>
                                        <textarea class="form-control" name="description" rows="4">{{ old('description', $exam?->description) }}</textarea>
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
    const form = document.getElementById('examForm');
    const batchSelect = document.getElementById('exBatchSelect');
    const subjectSelect = document.getElementById('exSubjectSelect');
    const branchEl = form?.querySelector('[name="branch_id"]');
    const lookupsUrl = form?.getAttribute('data-cascade-url') || @json(\App\Support\AdminUrl::lookups());
    let defaultBatchId = @json(old('batch_id', $defaultBatchId));
    let defaultSubjectId = @json(old('subject_id', $defaultSubjectId));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = window.jQuery;
    let batchesSeq = 0;

    function getInstituteId() {
        const el = form?.querySelector('[data-cascade-source]');
        if (!el) return '';
        if ($ && $(el).hasClass('select2-hidden-accessible')) {
            return $(el).val() || '';
        }
        return el.value || '';
    }

    function getBranchId() {
        if (!branchEl) return '';
        if ($ && $(branchEl).hasClass('select2-hidden-accessible')) {
            return $(branchEl).val() || '';
        }
        return branchEl.value || '';
    }

    function getBatchId() {
        if (!batchSelect) return '';
        if ($ && $(batchSelect).hasClass('select2-hidden-accessible')) {
            return $(batchSelect).val() || '';
        }
        return batchSelect.value || '';
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
            window.initAdminSearchableSelects(el);
        }
    }

    function bindSelect(el, handler, ns) {
        if (!el || !$) return;
        $(el).off('.' + ns).on('change.' + ns + ' select2:select.' + ns + ' select2:clear.' + ns, handler);
    }

    async function loadBatches() {
        const seq = ++batchesSeq;
        const instituteId = getInstituteId();
        const branchId = getBranchId();
        if (!batchSelect || !instituteId || !branchId) {
            if (batchSelect) {
                destroySelect2(batchSelect);
                batchSelect.innerHTML = '<option value="">Select batch</option>';
                initSelect2(batchSelect);
            }
            if (subjectSelect) {
                destroySelect2(subjectSelect);
                subjectSelect.innerHTML = '<option value="">Select subject (optional)</option>';
                initSelect2(subjectSelect);
            }
            return;
        }
        try {
            const res = await fetch(
                lookupsUrl + '/batches?institute_id=' + encodeURIComponent(instituteId) + '&branch_id=' + encodeURIComponent(branchId),
                { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } }
            );
            const json = await res.json();
            if (seq !== batchesSeq) return;
            if (!res.ok || json.success === false) throw new Error(json.message || 'Failed to load batches');
            destroySelect2(batchSelect);
            batchSelect.innerHTML = '<option value="">Select batch</option>';
            const seen = new Set();
            (json.data || []).forEach(item => {
                const id = String(item.id);
                if (seen.has(id)) return;
                seen.add(id);
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.text;
                opt.dataset.subjects = JSON.stringify(item.subjects || []);
                if (String(item.id) === String(defaultBatchId)) opt.selected = true;
                batchSelect.appendChild(opt);
            });
            initSelect2(batchSelect);
            await fillSubjects();
            defaultBatchId = null;
        } catch (e) {
            console.error(e);
            destroySelect2(batchSelect);
            batchSelect.innerHTML = '<option value="">Select batch</option>';
            initSelect2(batchSelect);
        }
    }

    async function fillSubjects() {
        if (!subjectSelect) return;
        destroySelect2(subjectSelect);
        subjectSelect.innerHTML = '<option value="">Select subject (optional)</option>';
        const batchId = getBatchId();
        const opt = batchId ? batchSelect?.querySelector('option[value="' + batchId + '"]') : null;
        let subjects = [];
        if (opt?.dataset.subjects) {
            try { subjects = JSON.parse(opt.dataset.subjects); } catch (e) {}
        }
        if (subjects.length === 0) {
            const instituteId = getInstituteId();
            if (instituteId) {
                try {
                    const res = await fetch(
                        lookupsUrl + '/subjects?institute_id=' + encodeURIComponent(instituteId),
                        { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } }
                    );
                    const json = await res.json();
                    subjects = json.data || [];
                } catch (e) {
                    console.error(e);
                }
            }
        }
        subjects.forEach(item => {
            const s = document.createElement('option');
            s.value = item.id;
            s.textContent = item.text;
            if (String(item.id) === String(defaultSubjectId)) s.selected = true;
            subjectSelect.appendChild(s);
        });
        initSelect2(subjectSelect);
        defaultSubjectId = null;
    }

    function onBranchChanged() {
        defaultBatchId = null;
        defaultSubjectId = null;
        loadBatches();
    }

    bindSelect(branchEl, onBranchChanged, 'exCascade');
    bindSelect(batchSelect, () => fillSubjects(), 'exCascade');
    form?.addEventListener('institute-cascade:done', () => setTimeout(loadBatches, 50));

    if (getInstituteId() && getBranchId()) {
        loadBatches();
    }

    form?.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = form.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        try {
            await AdminAjax.submitForm(form, { redirectUrl: @json(route('admin.exam')) });
        } catch (err) {
            if (btn) btn.disabled = false;
        }
    });
})();
</script>
@endpush
