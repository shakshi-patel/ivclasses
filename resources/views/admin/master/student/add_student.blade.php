@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($student) && $student;
        $user = auth()->user();
        $isPlatform = $user?->isPlatformUser();
        $enrollments = old('enrollments', $isEdit ? $student->enrollments->map(fn ($e) => [
            'batch_id' => $e->batch_id,
            'subject_id' => $e->subject_id,
            'roll_no' => $e->roll_no,
            'total_fees' => $e->total_fees,
        ])->toArray() : [['batch_id' => '', 'subject_id' => '', 'roll_no' => '', 'total_fees' => '']]);
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title"><i class="bx bx-user-plus"></i> {{ $isEdit ? 'Edit Student' : 'Add Student' }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.student') }}">Student</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.student') }}" class="btn btn-sm stu-top-back admin-btn-primary"><i class="bx bx-left-arrow-alt me-1"></i> Back</a>
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit Student' : 'Add Student' }}</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.student.update', $student) : route('admin.student.store') }}"
                                enctype="multipart/form-data"
                                data-institute-cascade
                                data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}"
                                id="studentForm">
                                @csrf
                                @if ($isEdit) @method('PUT') @endif

                                <div class="row g-3">
                                    @include('admin.partials.form_institute_field', [
                                        'instituteId' => old('institute_id', $student?->institute_id),
                                        'institutes' => $institutes,
                                    ])
                                    @include('admin.partials.form_branch_field', [
                                        'branchId' => old('branch_id', $student?->branch_id),
                                        'branches' => $branches,
                                    ])
                                    <div class="col-md-6">
                                        <label class="form-label">Student Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="name" required value="{{ old('name', $student?->name) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">DOB <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="dob" required value="{{ old('dob', optional($student?->dob)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Gender <span class="req">*</span></label>
                                        <select class="form-select" name="gender" required data-no-search>
                                            @foreach (['Male', 'Female', 'Other'] as $g)
                                                <option value="{{ $g }}" @selected(old('gender', $student?->gender) === $g)>{{ $g }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Standard <span class="req">*</span></label>
                                        <select class="form-select" name="standard_id" required data-cascade-target="standards"
                                            @if (old('standard_id', $student?->standard_id)) data-keep-value="{{ old('standard_id', $student?->standard_id) }}" @endif>
                                            @foreach ($standards as $s)
                                                <option value="{{ $s->id }}" @selected(old('standard_id', $student?->standard_id) == $s->id)>{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Admission Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="admission_date" required
                                            value="{{ old('admission_date', optional($student?->admission_date)->format('Y-m-d') ?? date('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Student Photo</label>
                                        <input type="file" class="form-control" name="photo" accept="image/*">
                                        @if ($isEdit && $student->photo_path)
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/'.$student->photo_path) }}" alt="Current photo"
                                                    style="width: 64px; height: 64px; object-fit: cover; border-radius: 8px; border: 1px solid var(--iv-border);">
                                                <span class="text-muted small ms-1">Current photo — choose a file to replace it</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Category <span class="req">*</span></label>
                                        <select class="form-select" name="category" required data-no-search>
                                            @foreach (['General', 'OBC', 'SC', 'ST'] as $c)
                                                <option value="{{ $c }}" @selected(old('category', $student?->category) === $c)>{{ $c }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Father Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="father_name" required value="{{ old('father_name', $student?->parent?->father_name) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Father Email <span class="req">*</span></label>
                                        <input type="email" class="form-control" name="father_email" required value="{{ old('father_email', $student?->parent?->email) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact 1 <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="father_phone" required maxlength="15" inputmode="numeric" data-phone-input value="{{ old('father_phone', $student?->parent?->phone) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact 2</label>
                                        <input type="text" class="form-control" name="father_phone_2" maxlength="15" inputmode="numeric" data-phone-input value="{{ old('father_phone_2', $student?->parent?->phone_2) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Parent Password</label>
                                        <input type="password" class="form-control" name="parent_password" placeholder="Default: password">
                                    </div>
                                </div>

                                <h6 class="mt-4 mb-2">Batch Enrollment</h6>
                                    <div id="enrollmentRows">
                                        @foreach ($enrollments as $idx => $enr)
                                            <div class="inq-batch-strip enrollment-row mb-2">
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <select class="form-select batch-select" name="enrollments[{{ $idx }}][batch_id]" required data-no-search
                                                            data-selected-batch="{{ $enr['batch_id'] ?? '' }}">
                                                            <option value="">Select batch</option>
                                                            @foreach ($batches as $batch)
                                                                <option value="{{ $batch['id'] }}" @selected(($enr['batch_id'] ?? '') == $batch['id'])>{{ $batch['name'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select class="form-select subject-select" name="enrollments[{{ $idx }}][subject_id]" data-no-search
                                                            data-selected-subject="{{ $enr['subject_id'] ?? '' }}">
                                                            <option value="">Select subject</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="text" class="form-control roll-no-input" name="enrollments[{{ $idx }}][roll_no]" placeholder="Auto" readonly value="{{ $enr['roll_no'] ?? '' }}" title="Auto-assigned from batch">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" name="enrollments[{{ $idx }}][total_fees]" placeholder="Fees" min="0" step="0.01" required value="{{ $enr['total_fees'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
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
            const form = document.getElementById('studentForm');
            const isEdit = @json($isEdit);

            form?.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;
                try {
                    await AdminAjax.submitForm(this, { redirectUrl: @json(route('admin.student')) });
                } catch (err) {
                    if (btn) btn.disabled = false;
                }
            });

            if (isEdit) {
                // enrollment wiring runs for create + edit
            }

            const instituteInput = form?.querySelector('select[name="institute_id"][data-cascade-source]');
            const instituteHidden = form?.querySelector('input[name="institute_id"][type="hidden"]');
            const branchEl = form?.querySelector('[name="branch_id"]');
            const wrap = document.getElementById('enrollmentRows');
            const lookupsUrl = @json(\App\Support\AdminUrl::lookups());
            const nextRollUrl = @json(route('admin.lookups.next-roll-no'));
            const excludeStudentId = @json($isEdit ? $student->id : null);
            let batches = @json($batches);
            const $ = window.jQuery;

            function getInstituteId() {
                return instituteInput?.value || instituteHidden?.value || '';
            }

            function resetSelect2(select) {
                if (!$ || !$.fn.select2) return;
                const $el = $(select);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
            }

            function fillSubjects(batchSelect) {
                const row = batchSelect.closest('.enrollment-row');
                const subjectSelect = row?.querySelector('.subject-select');
                if (!subjectSelect) return;

                const batchId = parseInt(batchSelect.value, 10);
                const batch = batches.find(function(b) {
                    return parseInt(b.id, 10) === batchId;
                });

                resetSelect2(subjectSelect);
                subjectSelect.innerHTML = '<option value="">Select subject</option>';

                if (batch?.subjects?.length) {
                    batch.subjects.forEach(function(s) {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.name;
                        subjectSelect.appendChild(opt);
                    });
                } else if (batchId) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'No subjects in this batch';
                    opt.disabled = true;
                    subjectSelect.appendChild(opt);
                }
            }

            function reservedRollsForBatch(batchId, exceptInput) {
                const rolls = [];
                wrap?.querySelectorAll('.enrollment-row').forEach(function(row) {
                    const sel = row.querySelector('.batch-select');
                    const rollInput = row.querySelector('.roll-no-input');
                    if (!sel || !rollInput || rollInput === exceptInput) return;
                    if (String(sel.value) !== String(batchId)) return;
                    const n = parseInt(rollInput.value, 10);
                    if (!isNaN(n)) rolls.push(n);
                });
                return rolls;
            }

            async function fillNextRoll(batchSelect) {
                const row = batchSelect.closest('.enrollment-row');
                const rollInput = row?.querySelector('.roll-no-input');
                if (!rollInput) return;

                const batchId = batchSelect.value;
                if (!batchId) {
                    rollInput.value = '';
                    return;
                }

                try {
                    let url = nextRollUrl + '?batch_id=' + encodeURIComponent(batchId);
                    const instituteId = getInstituteId();
                    if (instituteId) url += '&institute_id=' + encodeURIComponent(instituteId);
                    if (excludeStudentId) url += '&exclude_student_id=' + encodeURIComponent(excludeStudentId);

                    const res = await AdminAjax.get(url);
                    let next = parseInt(res.data?.next_roll, 10);
                    if (isNaN(next) || next < 1) next = 1;

                    const reserved = reservedRollsForBatch(batchId, rollInput);
                    while (reserved.indexOf(next) !== -1) next++;

                    rollInput.value = String(next);
                } catch (e) {
                    rollInput.value = '';
                }
            }

            function repopulateBatchSelects() {
                wrap?.querySelectorAll('.batch-select').forEach(function(sel) {
                    const row = sel.closest('.enrollment-row');
                    const subjectSelect = row?.querySelector('.subject-select');
                    resetSelect2(sel);
                    if (subjectSelect) resetSelect2(subjectSelect);

                    sel.innerHTML = '<option value="">Select batch</option>';
                    batches.forEach(function(b) {
                        const opt = document.createElement('option');
                        opt.value = b.id;
                        opt.textContent = b.name;
                        sel.appendChild(opt);
                    });
                    const keepBatch = sel.dataset.selectedBatch;
                    if (keepBatch) {
                        sel.value = keepBatch;
                    }

                    if (subjectSelect) {
                        subjectSelect.innerHTML = '<option value="">Select subject</option>';
                    }
                });
            }

            async function loadBatchesForBranch(branchId) {
                const instituteId = getInstituteId();
                if (!instituteId || !branchId) {
                    batches = [];
                    repopulateBatchSelects();
                    return;
                }
                try {
                    const res = await AdminAjax.get(
                        lookupsUrl + '/batches?institute_id=' + encodeURIComponent(instituteId) + '&branch_id=' + encodeURIComponent(branchId)
                    );
                    batches = (res.data || []).map(function(b) {
                        return {
                            id: b.id,
                            name: b.text,
                            branch_id: b.branch_id,
                            subjects: (b.subjects || []).map(function(s) {
                                return { id: s.id, name: s.text || s.name };
                            })
                        };
                    });
                    repopulateBatchSelects();
                    wrap?.querySelectorAll('.batch-select').forEach(function(sel) {
                        if (sel.value) {
                            fillSubjects(sel);
                            const row = sel.closest('.enrollment-row');
                            const subjectSelect = row?.querySelector('.subject-select');
                            const keepSubject = subjectSelect?.dataset?.selectedSubject;
                            if (keepSubject && subjectSelect) {
                                subjectSelect.value = keepSubject;
                            }
                        }
                    });
                } catch (e) {
                    await AdminAjax.error(e.message || 'Could not load batches.');
                }
            }

            function onBranchChanged() {
                loadBatchesForBranch(branchEl?.value || '');
            }

            wrap?.addEventListener('change', function(e) {
                if (e.target.classList.contains('batch-select')) {
                    fillSubjects(e.target);
                    fillNextRoll(e.target);
                }
            });

            if (branchEl) {
                branchEl.addEventListener('change', onBranchChanged);
                if ($) {
                    $(branchEl).on('change.studentEnroll select2:select.studentEnroll', onBranchChanged);
                }
            }

            function onInstituteChanged() {
                batches = [];
                repopulateBatchSelects();
            }

            if (instituteInput) {
                instituteInput.addEventListener('change', onInstituteChanged);
                if ($) {
                    $(instituteInput).on('change.studentEnroll', onInstituteChanged);
                }
            }

            form?.addEventListener('institute-cascade:done', function() {
                onBranchChanged();
            });

            function initBatches() {
                if (getInstituteId() && branchEl?.value) {
                    onBranchChanged();
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(initBatches, 100);
                });
            } else {
                setTimeout(initBatches, 100);
            }
        })();
    </script>
@endpush
