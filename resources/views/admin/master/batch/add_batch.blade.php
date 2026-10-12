@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($batch) && $batch;
        $user = auth()->user();
        $isPlatform = $user?->isPlatformUser();
        $defaultInstituteId = old('institute_id', $batch?->institute_id ?? $user?->institute_id);
        $defaultYear = now()->year . '-' . substr((string) (now()->year + 1), -2);
        $rows = old('subjects', $batchSubjects ?? [['subject_id' => '', 'teacher_user_id' => '', 'fees_amount' => '']]);
        $selectedTeacherIds = array_map('intval', old('teacher_ids', $assignedTeacherIds ?? []));
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-{{ $isEdit ? 'pencil' : 'plus-circle' }}"></i>
                    {{ $isEdit ? 'Edit Batch' : 'Add Batch' }}
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.master.batch') }}">Batches</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="admin-detail-block mt-4">
                    <form method="POST"
                        id="batchForm"
                        action="{{ $isEdit ? route('admin.master.batch.update', $batch) : route('admin.master.batch.store') }}"
                        data-institute-cascade
                        data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}">
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            @include('admin.partials.form_institute_field', [
                                'instituteId' => $defaultInstituteId,
                                'institutes' => $institutes,
                                'label' => 'Classes / Institute',
                            ])
                            @include('admin.partials.form_branch_field', [
                                'branchId' => old('branch_id', $batch?->branch_id),
                                'branches' => $branches,
                                'label' => 'Branch',
                            ])
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Batch Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="name"
                                    value="{{ old('name', $batch?->name) }}" placeholder="e.g. Morning A" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Standard <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="standard_id" required
                                    data-cascade-target="standards"
                                    data-placeholder="Select standard"
                                    @if ($batch?->standard_id) data-keep-value="{{ $batch->standard_id }}" @endif>
                                    <option value="">Select standard</option>
                                    @foreach ($standards as $standard)
                                        <option value="{{ $standard->id }}" @selected(old('standard_id', $batch?->standard_id) == $standard->id)>
                                            {{ $standard->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Start Time</label>
                                <input type="time" class="form-control" name="start_time"
                                    value="{{ old('start_time', $batch?->start_time ? substr((string) $batch->start_time, 0, 5) : '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">End Time</label>
                                <input type="time" class="form-control" name="end_time"
                                    value="{{ old('end_time', $batch?->end_time ? substr((string) $batch->end_time, 0, 5) : '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Capacity</label>
                                <input type="number" class="form-control" name="capacity" min="1"
                                    value="{{ old('capacity', $batch?->capacity) }}" placeholder="e.g. 40">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Academic Year</label>
                                <input type="text" class="form-control" name="academic_year"
                                    value="{{ old('academic_year', $batch?->academic_year ?? $defaultYear) }}"
                                    placeholder="2026-27">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="status" required data-no-search>
                                    <option value="active" @selected(old('status', $batch?->status ?? 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $batch?->status) === 'inactive')>Inactive</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0" style="color: var(--iv-primary);">
                                        Subjects &amp; Teachers
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddSubjectRow">
                                        <i class="bx bx-plus"></i> Assign subject and teacher
                                    </button>
                                </div>
                                <div id="subjectRows">
                                    @foreach ($rows as $index => $row)
                                        <div class="row g-2 mb-2 subject-row">
                                            <div class="col-md-4">
                                                <select class="form-select subject-select" name="subjects[{{ $index }}][subject_id]"
                                                    data-cascade-target="subjects" data-placeholder="Subject">
                                                    <option value="">Subject</option>
                                                    @foreach ($subjects as $subject)
                                                        <option value="{{ $subject->id }}"
                                                            @selected(($row['subject_id'] ?? '') == $subject->id)>
                                                            {{ $subject->name }}{{ $subject->code ? ' ('.$subject->code.')' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <select class="form-select teacher-select" name="subjects[{{ $index }}][teacher_user_id]"
                                                    data-cascade-target="teachers" data-placeholder="Teacher (optional)">
                                                    <option value="">Teacher (optional)</option>
                                                    @foreach ($teachers as $teacher)
                                                        <option value="{{ $teacher->id }}"
                                                            @selected(($row['teacher_user_id'] ?? '') == $teacher->id)>
                                                            {{ $teacher->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" step="0.01" min="0" class="form-control"
                                                    name="subjects[{{ $index }}][fees_amount]"
                                                    value="{{ $row['fees_amount'] ?? '' }}" placeholder="Fees (optional)">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-subject-row" title="Remove">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text">First assigned teacher becomes primary batch teacher.</div>
                            </div>

                            <div class="col-12">
                                <div class="border-top pt-3">
                                    <label class="form-label fw-semibold mb-1" style="color: var(--iv-primary);">
                                        Assign Teachers <span class="text-muted fw-normal">(optional)</span>
                                    </label>
                                    <p class="text-muted small mb-2">
                                        Teachers on this batch, independent of the subject rows above — this
                                        includes anyone assigned to this batch from their own Teacher profile.
                                    </p>
                                    <select class="form-select" name="teacher_ids[]" multiple
                                        data-placeholder="Select teacher(s)">
                                        @foreach ($teachers as $teacher)
                                            <option value="{{ $teacher->id }}"
                                                @selected(in_array((int) $teacher->id, $selectedTeacherIds, true))>
                                                {{ $teacher->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.master.batch') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn admin-btn-primary">
                                    {{ $isEdit ? 'Update Batch' : 'Save Batch' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const container = document.getElementById('subjectRows');
            const $ = window.jQuery;

            function reindexRows() {
                if (!container) return;
                container.querySelectorAll('.subject-row').forEach(function(row, idx) {
                    row.querySelectorAll('[name^="subjects["]').forEach(function(el) {
                        el.name = el.name.replace(/subjects\[\d+\]/, 'subjects[' + idx + ']');
                    });
                });
            }

            function resetSelect2(select) {
                if (!$ || !$.fn.select2) return;
                const $el = $(select);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                select.removeAttribute('data-select2-id');
                select.classList.remove('select2-hidden-accessible');
            }

            function prepareRowForClone(row) {
                row.querySelectorAll('.select2-container').forEach(function(el) {
                    el.remove();
                });
                row.querySelectorAll('select').forEach(function(sel) {
                    resetSelect2(sel);
                    sel.value = '';
                    sel.removeAttribute('data-keep-value');
                });
                row.querySelectorAll('input').forEach(function(inp) {
                    inp.value = '';
                });
            }

            document.getElementById('btnAddSubjectRow')?.addEventListener('click', function() {
                const first = container?.querySelector('.subject-row');
                if (!first || !container) return;

                const clone = first.cloneNode(true);
                prepareRowForClone(clone);
                container.appendChild(clone);
                reindexRows();

                if (window.initAdminSearchableSelects) {
                    window.initAdminSearchableSelects(clone);
                }
            });

            container?.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove-subject-row');
                if (!btn) return;
                const rows = container.querySelectorAll('.subject-row');
                if (rows.length <= 1) {
                    prepareRowForClone(rows[0]);
                    if (window.initAdminSearchableSelects) {
                        window.initAdminSearchableSelects(rows[0]);
                    }
                    return;
                }
                const row = btn.closest('.subject-row');
                row?.querySelectorAll('select').forEach(resetSelect2);
                row?.remove();
                reindexRows();
            });

            document.getElementById('batchForm')?.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;
                try {
                    await AdminAjax.submitForm(this, {
                        redirectUrl: @json(route('admin.master.batch'))
                    });
                } catch (err) {
                    if (btn) btn.disabled = false;
                }
            });
        })();
    </script>
@endpush
