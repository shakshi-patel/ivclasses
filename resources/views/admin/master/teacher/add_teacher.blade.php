@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($teacher) && $teacher;
        $user = auth()->user();
        $isPlatform = $user?->isPlatformUser();
        $selectedBranchId = old('primary_branch_id', $teacher?->primaryBranch()?->id);
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-{{ $isEdit ? 'pencil' : 'plus-circle' }}"></i>
                    {{ $isEdit ? 'Edit Teacher' : 'Add Teacher' }}
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.users', ['role' => 'teacher']) }}">Users</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $isEdit ? 'Edit Teacher' : 'Add Teacher' }}
                        </li>
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
                        action="{{ $isEdit ? route('admin.master.teacher.update', $teacher) : route('admin.master.teacher.store') }}"
                        enctype="multipart/form-data"
                        data-institute-cascade
                        data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}"
                        id="teacherForm">
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            @include('admin.partials.form_institute_field', [
                                'instituteId' => old('institute_id', $teacher?->institute_id),
                                'institutes' => $institutes,
                                'label' => 'Classes / Institute',
                                'colClass' => 'col-md-4',
                            ])
                            @include('admin.partials.form_branch_field', [
                                'branchId' => $selectedBranchId,
                                'branches' => $branches,
                                'name' => 'primary_branch_id',
                                'label' => 'Branch',
                                'colClass' => 'col-md-4',
                            ])

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Teacher Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="name"
                                    value="{{ old('name', $teacher?->user?->name) }}" placeholder="Full name" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Mobile Number</label>
                                <input type="text" class="form-control" name="phone" maxlength="15" inputmode="numeric" data-phone-input
                                    value="{{ old('phone', $teacher?->user?->phone) }}" placeholder="Mobile">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Email (login) <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" name="email"
                                    value="{{ old('email', $teacher?->user?->email) }}" placeholder="Email" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Specialization</label>
                                <select class="form-select" name="specialization_ids[]" id="teacherSpecializationSelect" multiple
                                    data-placeholder="Select subject(s)">
                                    @foreach ($subjects ?? [] as $subject)
                                        <option value="{{ $subject->id }}" @selected(in_array((int) $subject->id, $selectedSpecializationIds ?? [], true))>
                                            {{ $subject->code ? $subject->name.' ('.$subject->code.')' : $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="status" required data-no-search>
                                    <option value="active" @selected(old('status', $teacher?->status ?? 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $teacher?->status) === 'inactive')>Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Password {{ $isEdit ? '' : '*' }}
                                </label>
                                <input type="password" class="form-control" name="password"
                                    placeholder="{{ $isEdit ? 'Leave blank to keep' : 'Min. 8 characters' }}"
                                    {{ $isEdit ? '' : 'required' }}>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">
                                    Confirm Password {{ $isEdit ? '' : '*' }}
                                </label>
                                <input type="password" class="form-control" name="password_confirmation"
                                    placeholder="Repeat password" {{ $isEdit ? '' : 'required' }}>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Joining Date</label>
                                <input type="date" class="form-control" name="joining_date"
                                    value="{{ old('joining_date', optional($teacher?->joining_date)->format('Y-m-d')) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Login Time</label>
                                <input type="time" class="form-control" name="expected_login_time"
                                    value="{{ old('expected_login_time', $teacher?->expected_login_time ? substr((string) $teacher->expected_login_time, 0, 5) : '08:00') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Grace Time (Minutes)</label>
                                <input type="number" class="form-control" name="grace_minutes" min="0" max="120"
                                    value="{{ old('grace_minutes', $teacher?->grace_minutes ?? 10) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Profile Image</label>
                                <input type="file" class="form-control" name="avatar" accept="image/*">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" style="color: var(--iv-primary);">Full Address</label>
                                <textarea class="form-control" name="address" rows="2"
                                    placeholder="Address">{{ old('address', $teacher?->address) }}</textarea>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="border-top pt-3">
                                    <label class="form-label fw-semibold mb-1" style="color: var(--iv-primary);">
                                        Assign Batches <span class="text-danger">*</span>
                                    </label>
                                    <p class="text-muted small mb-2">Select one or more batches for this teacher (based on branch above).</p>
                                    <select class="form-select" name="batch_ids[]" id="teacherBatchSelect" multiple required
                                        data-placeholder="Select batch(es)">
                                        @foreach ($batches as $batch)
                                            <option value="{{ $batch->id }}" @selected(in_array((int) $batch->id, $selectedBatchIds, true))>
                                                {{ $batch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.users', ['role' => 'teacher']) }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn admin-btn-primary">
                                    {{ $isEdit ? 'Update Teacher' : 'Save Teacher' }}
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
        (function () {
            const form = document.getElementById('teacherForm');
            const batchSelect = document.getElementById('teacherBatchSelect');
            const specializationSelect = document.getElementById('teacherSpecializationSelect');
            const instituteInput = form?.querySelector('select[name="institute_id"][data-cascade-source]');
            const instituteHidden = form?.querySelector('input[name="institute_id"][type="hidden"]');
            const branchEl = form?.querySelector('[name="primary_branch_id"]');
            const lookupsUrl = form?.getAttribute('data-cascade-url') || @json(\App\Support\AdminUrl::lookups());
            let selectedBatchIds = @json($selectedBatchIds);
            let selectedSpecializationIds = @json($selectedSpecializationIds ?? []);
            const $ = window.jQuery;

            function getInstituteId() {
                return instituteInput?.value || instituteHidden?.value || '';
            }

            function getBranchId() {
                return branchEl?.value || '';
            }

            function resetSelect2(el) {
                if (!$ || !$.fn.select2 || !el) return;
                const $el = $(el);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
            }

            function currentSelectedValues(el, fallbackIds) {
                const current = [];
                if ($ && $(el).hasClass('select2-hidden-accessible')) {
                    current.push(...($(el).val() || []).map(String));
                } else {
                    Array.from(el.selectedOptions).forEach(function (opt) {
                        current.push(String(opt.value));
                    });
                }
                return current.length ? current : fallbackIds.map(String);
            }

            function fillSelect(el, items, keepIds) {
                if (!el) return;

                resetSelect2(el);
                el.innerHTML = '';

                (items || []).forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.text;
                    if (keepIds.includes(String(item.id))) {
                        opt.selected = true;
                    }
                    el.appendChild(opt);
                });

                if (typeof window.initAdminSearchableSelects === 'function') {
                    window.initAdminSearchableSelects(el.parentElement || form);
                }
            }

            function fillBatches(items) {
                if (!batchSelect) return;
                fillSelect(batchSelect, items, currentSelectedValues(batchSelect, selectedBatchIds));
            }

            function fillSubjects(items) {
                if (!specializationSelect) return;
                fillSelect(specializationSelect, items, currentSelectedValues(specializationSelect, selectedSpecializationIds));
            }

            async function loadBatches() {
                const instituteId = getInstituteId();
                const branchId = getBranchId();

                if (!instituteId || !branchId) {
                    fillBatches([]);
                    return;
                }

                try {
                    const res = await fetch(
                        lookupsUrl + '/batches?institute_id=' + encodeURIComponent(instituteId) +
                        '&branch_id=' + encodeURIComponent(branchId),
                        {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        }
                    );
                    const data = await res.json();
                    fillBatches(data.data || []);
                } catch (e) {
                    fillBatches([]);
                }
            }

            async function loadSubjects() {
                const instituteId = getInstituteId();
                if (!instituteId) {
                    fillSubjects([]);
                    return;
                }

                try {
                    const res = await fetch(
                        lookupsUrl + '/subjects?institute_id=' + encodeURIComponent(instituteId),
                        {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        }
                    );
                    const data = await res.json();
                    fillSubjects(data.data || []);
                } catch (e) {
                    fillSubjects([]);
                }
            }

            function onBranchChanged() {
                selectedBatchIds = [];
                loadBatches();
            }

            function onInstituteChanged() {
                selectedSpecializationIds = [];
                selectedBatchIds = [];
                loadSubjects();
                loadBatches();
            }

            if (branchEl) {
                branchEl.addEventListener('change', onBranchChanged);
                if ($) {
                    $(branchEl).on('change.teacherBatches select2:select.teacherBatches', onBranchChanged);
                }
            }

            if (instituteInput) {
                instituteInput.addEventListener('change', onInstituteChanged);
                if ($) {
                    $(instituteInput).on('change.teacherSubjects select2:select.teacherSubjects', onInstituteChanged);
                }
            }

            form?.addEventListener('institute-cascade:done', onInstituteChanged);

            if (getInstituteId() && specializationSelect && specializationSelect.options.length === 0) {
                loadSubjects();
            }

            if (getInstituteId() && getBranchId() && batchSelect && batchSelect.options.length === 0) {
                loadBatches();
            }
        })();

        document.getElementById('teacherForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;
            try {
                await AdminAjax.submitForm(this, {
                    redirectUrl: @json(route('admin.users', ['role' => 'teacher']))
                });
            } catch (err) {
                if (btn) btn.disabled = false;
            }
        });
    </script>
@endpush
