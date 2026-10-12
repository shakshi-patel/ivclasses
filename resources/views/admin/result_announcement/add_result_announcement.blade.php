@extends('admin.layouts.layout')

@section('content')
    @php
        $announcement = $announcement ?? null;
        $isEdit = $announcement !== null;
        $editExam = $announcement?->exam;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title"><i class="bx bx-trophy"></i> {{ $isEdit ? 'Edit' : 'Add' }} Result Announcement</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.result_announcement') }}">Result Announcement</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.result_announcement') }}" class="btn btn-sm stu-top-back admin-btn-primary"><i class="bx bx-left-arrow-alt me-1"></i> Back</a>
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Update Result Sheet' : 'Create Result Sheet' }}</div>
                        <div class="stu-form-body">
                            <form id="resultForm" @unless ($isEdit) data-institute-cascade @endunless data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}">
                                <div class="row g-3">
                                    @if ($isEdit)
                                        <div class="col-md-4">
                                            <label class="form-label">Branch</label>
                                            <input type="text" class="form-control" value="{{ $editExam?->branch?->name ?? '—' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Batch</label>
                                            <input type="text" class="form-control" value="{{ $editExam?->batch?->name ?? '—' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Exam</label>
                                            <input type="text" class="form-control" value="{{ $editExam?->title }} | {{ $editExam?->exam_date?->format('Y-m-d') }}" readonly>
                                            <input type="hidden" name="exam_id" id="resultExamFixed" value="{{ $announcement->exam_id }}">
                                        </div>
                                    @else
                                        @include('admin.partials.form_institute_field', ['instituteId' => old('institute_id', $defaultInstituteId), 'institutes' => $institutes, 'label' => 'Classes / Institute'])
                                        @include('admin.partials.form_branch_field', ['branchId' => old('branch_id', $defaultBranchId), 'branches' => $branches, 'required' => true])
                                        <div class="col-md-4">
                                            <label class="form-label">Batch <span class="req">*</span></label>
                                            <select class="form-select result-required" id="resultBatch" required><option value="">Select batch</option></select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Exam <span class="req">*</span></label>
                                            <select class="form-select result-required" name="exam_id" id="resultExam" required><option value="">Select exam</option></select>
                                        </div>
                                    @endif
                                    <div class="col-md-4">
                                        <label class="form-label">Result Date</label>
                                        <input type="date" class="form-control" name="result_date" value="{{ $announcement?->result_date?->format('Y-m-d') ?? date('Y-m-d') }}">
                                    </div>
                                </div>

                                @unless ($isEdit)
                                    <div class="result-filter-note">Required fields choose karia pachi niche result sheet section open thase. Present / Absent list exam date ni attendance par thi auto fill thase.</div>
                                @endunless

                                <div class="result-sheet-card mt-4 d-none" id="resultSheetPanel">
                                    <div class="result-sheet-head">Exam Result Sheet</div>
                                    <div class="result-sheet-body">
                                        <div id="resultAttendanceNote" class="result-filter-note d-none mb-3"></div>
                                        <div class="result-sheet-meta" id="resultMeta"></div>
                                        <div class="result-section-chip is-present">Present Students <span class="fw-normal small">(Exam day attendance: Present / Late)</span></div>
                                        <div class="table-responsive mb-4" id="presentTableWrap"></div>
                                        <div class="result-section-chip is-absent">Absent Students <span class="fw-normal small">(Exam day absent or attendance not marked)</span></div>
                                        <div class="table-responsive" id="absentTableWrap"></div>
                                        <div class="master-stu-form-actions mt-4">
                                            <button type="submit" class="btn admin-btn-primary px-4"><i class="bx bx-save me-1"></i> {{ $isEdit ? 'Update' : 'Submit' }}</button>
                                        </div>
                                    </div>
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
    const form = document.getElementById('resultForm');
    const batchSelect = document.getElementById('resultBatch');
    const examSelect = document.getElementById('resultExam');
    const fixedExamId = document.getElementById('resultExamFixed')?.value || '';
    const panel = document.getElementById('resultSheetPanel');
    const branchEl = form?.querySelector('[name="branch_id"]');
    const lookupsUrl = form?.getAttribute('data-cascade-url') || @json(\App\Support\AdminUrl::lookups());
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = window.jQuery;
    let sheetData = null;
    let batchesSeq = 0;
    let examsSeq = 0;

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

    function getExamId() {
        if (fixedExamId) return fixedExamId;
        if (!examSelect) return '';
        if ($ && $(examSelect).hasClass('select2-hidden-accessible')) {
            return $(examSelect).val() || '';
        }
        return examSelect.value || '';
    }

    function fillOptions(select, items, placeholder) {
        if (!select) return;
        destroySelect2(select);
        select.innerHTML = '';
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = placeholder;
        select.appendChild(empty);
        const seen = new Set();
        (items || []).forEach(item => {
            const id = String(item.id);
            if (seen.has(id)) return;
            seen.add(id);
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.text;
            select.appendChild(opt);
        });
        initSelect2(select);
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
        fillOptions(batchSelect, [], 'Select batch');
        fillOptions(examSelect, [], 'Select exam');
        panel.classList.add('d-none');
        if (!instituteId || !branchId) return;
        try {
            const res = await fetch(
                lookupsUrl + '/batches?institute_id=' + encodeURIComponent(instituteId) + '&branch_id=' + encodeURIComponent(branchId),
                { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } }
            );
            const json = await res.json();
            if (seq !== batchesSeq) return;
            if (!res.ok || json.success === false) throw new Error(json.message || 'Failed to load batches');
            fillOptions(batchSelect, json.data || [], 'Select batch');
        } catch (e) {
            console.error(e);
        }
    }

    async function loadExams() {
        const seq = ++examsSeq;
        const instituteId = getInstituteId();
        const batchId = getBatchId();
        fillOptions(examSelect, [], 'Select exam');
        panel.classList.add('d-none');
        if (!instituteId || !batchId) return;
        try {
            const res = await fetch(
                lookupsUrl + '/exams?institute_id=' + encodeURIComponent(instituteId) + '&batch_id=' + encodeURIComponent(batchId),
                { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } }
            );
            const json = await res.json();
            if (seq !== examsSeq) return;
            if (!res.ok || json.success === false) throw new Error(json.message || 'Failed to load exams');
            fillOptions(examSelect, json.data || [], 'Select exam');
        } catch (e) {
            console.error(e);
        }
    }

    async function loadSheet() {
        const examId = getExamId();
        if (!examId) {
            panel.classList.add('d-none');
            return;
        }
        try {
            const res = await fetch(@json(route('admin.exam')) + '/' + examId + '/result-sheet', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
            });
            const json = await res.json();
            if (!json.success) return;
            sheetData = json.data;
            const exam = sheetData.exam;
            const attNote = document.getElementById('resultAttendanceNote');
            if (attNote) {
                if (sheetData.attendance_marked) {
                    attNote.className = 'alert alert-info py-2 px-3 mb-3';
                    attNote.textContent = 'Attendance is marked for exam date (' + (exam.exam_date_label || exam.exam_date) + '). Present / Late students are listed under Present; Absent students are listed under Absent.';
                } else {
                    attNote.className = 'alert alert-warning py-2 px-3 mb-3';
                    attNote.textContent = 'Attendance is not marked for exam date (' + (exam.exam_date_label || exam.exam_date) + ') — all students are listed under Absent. Mark attendance first, or submit the result manually.';
                }
                attNote.classList.remove('d-none');
            }
            document.getElementById('resultMeta').innerHTML =
                '<div class="result-meta-box"><span class="lbl">Exam Title</span><div class="val">' + exam.title + '</div></div>' +
                '<div class="result-meta-box"><span class="lbl">Exam Date</span><div class="val">' + (exam.exam_date_label || exam.exam_date || '—') + '</div></div>' +
                '<div class="result-meta-box"><span class="lbl">Subject</span><div class="val">' + (exam.subject || '—') + '</div></div>' +
                '<div class="result-meta-box"><span class="lbl">Exam Time</span><div class="val">' + (exam.duration_label || '—') + '</div></div>' +
                '<div class="result-meta-box"><span class="lbl">Total Marks</span><div class="val">' + exam.total_marks + '</div></div>';

            let presentHtml = '<table class="result-entry-table"><thead><tr><th>Roll</th><th>Student</th><th>Standard</th><th>Max</th><th>Obtain</th><th>Remark</th><th>Attempt</th></tr></thead><tbody>';
            sheetData.present.forEach(row => {
                const attempted = row.is_attempted !== false && row.is_attempted !== 0;
                presentHtml += '<tr data-student-id="' + row.student_id + '"><td>' + (row.roll_no || '—') + '</td><td>' + row.name + '</td><td>' + (row.standard || '—') + '</td><td><input type="text" value="' + exam.total_marks + '" readonly class="form-control form-control-sm"></td><td><input type="number" step="0.01" class="form-control form-control-sm marks-input" value="' + (attempted ? (row.marks_obtained ?? '') : '') + '"' + (attempted ? '' : ' readonly') + '></td><td><input type="text" class="form-control form-control-sm remark-input" value="' + (row.remark || '') + '"></td><td><select class="form-select form-select-sm attempt-input"><option value="1" ' + (attempted ? 'selected' : '') + '>Attempt</option><option value="0" ' + (!attempted ? 'selected' : '') + '>Not Attempt</option></select></td></tr>';
            });
            presentHtml += '</tbody></table>';
            document.getElementById('presentTableWrap').innerHTML = presentHtml;
            document.querySelectorAll('#presentTableWrap .attempt-input').forEach(sel => {
                sel.addEventListener('change', function() {
                    const marks = this.closest('tr')?.querySelector('.marks-input');
                    if (!marks) return;
                    if (this.value === '0') {
                        marks.value = '';
                        marks.readOnly = true;
                    } else {
                        marks.readOnly = false;
                    }
                });
            });

            let absentHtml = '<table class="result-entry-table"><thead><tr><th>Roll</th><th>Student</th><th>Standard</th><th>Remark</th></tr></thead><tbody>';
            sheetData.absent.forEach(row => {
                absentHtml += '<tr><td>' + (row.roll_no || '—') + '</td><td>' + row.name + '</td><td>' + (row.standard || '—') + '</td><td class="text-muted small">' + (row.remark || 'Absent') + '</td></tr>';
            });
            absentHtml += '</tbody></table>';
            document.getElementById('absentTableWrap').innerHTML = absentHtml;
            panel.classList.remove('d-none');
        } catch (e) {
            console.error(e);
        }
    }

    function onBranchChanged() {
        loadBatches();
    }

    function onBatchChanged() {
        loadExams();
    }

    bindSelect(branchEl, onBranchChanged, 'resCascade');
    bindSelect(batchSelect, onBatchChanged, 'resCascade');
    bindSelect(examSelect, loadSheet, 'resCascade');
    form?.addEventListener('institute-cascade:done', () => setTimeout(loadBatches, 50));

    if (fixedExamId) {
        loadSheet();
    } else if (getInstituteId() && getBranchId()) {
        loadBatches();
    }

    form?.addEventListener('submit', async e => {
        e.preventDefault();
        const examId = getExamId();
        if (!examId) {
            if (window.AdminAjax) AdminAjax.error('Please select an exam.');
            return;
        }
        const results = [];
        document.querySelectorAll('#presentTableWrap tbody tr').forEach(tr => {
            const attempted = tr.querySelector('.attempt-input')?.value === '1';
            results.push({
                student_id: parseInt(tr.dataset.studentId, 10),
                marks_obtained: attempted ? (tr.querySelector('.marks-input')?.value || null) : null,
                remark: tr.querySelector('.remark-input')?.value || null,
                is_attempted: attempted,
                attendance_status: 'present'
            });
        });
        (sheetData?.absent || []).forEach(row => {
            results.push({
                student_id: row.student_id,
                marks_obtained: null,
                remark: row.remark || 'Absent',
                is_attempted: false,
                attendance_status: 'absent'
            });
        });
        const payload = {
            exam_id: parseInt(examId, 10),
            result_date: form.querySelector('[name="result_date"]')?.value,
            publish: true,
            results
        };
        const r = await fetch(@json(route('admin.result_announcement.store')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (!d.success) { if (window.AdminAjax) AdminAjax.error(d.message); return; }
        if (window.AdminAjax) await AdminAjax.success(d.message);
        window.location.href = @json(route('admin.result_announcement'));
    });
})();
</script>
@endpush
