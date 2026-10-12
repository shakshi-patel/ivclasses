@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title"><i class="bx bx-transfer"></i> Convert Student</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.inquiry') }}">Inquiry</a></li>
                        <li class="breadcrumb-item active">Convert</li>
                    </ol>
                </nav>

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.inquiry') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>

                    <div class="inq-convert-summary">
                        <div class="inq-convert-summary-head">
                            <div class="inq-convert-avatar"><i class="bx bx-user"></i></div>
                            <div class="flex-grow-1">
                                <h5>{{ $inquiry->student_name }}</h5>
                                <p>Inquiry: {{ $inquiry->inquiry_code }} · {{ optional($inquiry->inquiry_date)->format('d M Y') }}</p>
                            </div>
                            <span class="admin-status-pill is-navy">{{ ucfirst($inquiry->status) }}</span>
                        </div>
                        <div class="inq-convert-summary-body">
                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="inq-convert-meta"><span class="lbl">Standard</span><span class="val">{{ $inquiry->standard?->name ?? '—' }}</span></div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="inq-convert-meta"><span class="lbl">Contact</span><span class="val">{{ $inquiry->phone }}</span></div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="inq-convert-meta"><span class="lbl">Father</span><span class="val">{{ $inquiry->father_name }}</span></div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="inq-convert-meta"><span class="lbl">Email</span><span class="val">{{ $inquiry->email }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="master-stu-form-card mt-3">
                        <div class="stu-form-head">Convert to Student</div>
                        <div class="stu-form-body">
                            <form method="POST" action="{{ route('admin.inquiry.convert.store', $inquiry) }}" id="convertForm">
                                @csrf
                                <div class="row g-3">
                                    @include('admin.partials.form_branch_field', [
                                        'branchId' => old('branch_id', $inquiry->branch_id),
                                        'branches' => $branches,
                                        'colClass' => 'col-md-6',
                                        'id' => 'convertBranchId',
                                    ])
                                    <div class="col-md-3">
                                        <label class="form-label">Form Fee</label>
                                        <input type="number" class="form-control" name="form_fees" min="0" step="0.01"
                                            value="{{ old('form_fees', $inquiry->form_fees) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Payment Mode <span class="req">*</span></label>
                                        <select class="form-select" name="form_fees_payment_method" required>
                                            @foreach (['Cash', 'Online', 'UPI', 'Cheque'] as $pm)
                                                <option value="{{ $pm }}" @selected(old('form_fees_payment_method', $inquiry->form_fees_payment_method ?? 'Cash') === $pm)>{{ $pm }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="inq-convert-section-title mt-4"><i class="bx bx-layer"></i> Batch &amp; Subject</div>
                                <div id="inqBatchRows">
                                    <div class="inq-batch-strip enrollment-row">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label">Batch <span class="req">*</span></label>
                                                <select class="form-select batch-select" name="enrollments[0][batch_id]" required
                                                    data-placeholder="Select batch">
                                                    <option value="">Select batch</option>
                                                    @foreach ($batches as $batch)
                                                        <option value="{{ $batch['id'] }}">{{ $batch['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Subject</label>
                                                <select class="form-select subject-select" name="enrollments[0][subject_id]"
                                                    data-placeholder="Select subject">
                                                    <option value="">Select subject</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Roll No</label>
                                                <input type="text" class="form-control roll-no-input" name="enrollments[0][roll_no]" placeholder="Auto" readonly title="Auto-assigned from batch">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Fees <span class="req">*</span></label>
                                                <input type="number" class="form-control" name="enrollments[0][total_fees]" min="0" step="0.01" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addEnrollmentRow">
                                    <i class="bx bx-plus"></i> Add batch row
                                </button>

                                <div class="inq-parent-box mt-4">
                                    <div class="inq-parent-box-head"><i class="bx bx-lock-alt"></i> Parent Login</div>
                                    <div class="inq-parent-box-body">
                                        <p class="mb-2 small text-muted">Parent will log in with <strong>{{ $inquiry->email }}</strong></p>
                                        <label class="form-label">Parent Password (optional, default: password)</label>
                                        <input type="password" class="form-control" name="parent_password" placeholder="Min 8 characters">
                                    </div>
                                </div>

                                <div class="master-stu-form-actions mt-4 justify-content-end">
                                    <a href="{{ route('admin.inquiry') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                                    <button type="submit" class="btn admin-btn-primary px-4">
                                        <i class="bx bx-transfer me-1"></i> Convert Student
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
            const instituteId = @json($inquiry->institute_id);
            const lookupsUrl = @json(\App\Support\AdminUrl::lookups());
            const nextRollUrl = @json(route('admin.lookups.next-roll-no'));
            let batches = @json($batches);
            const wrap = document.getElementById('inqBatchRows');
            let rowIndex = 1;
            const $ = window.jQuery;

            function resetSelect2(select) {
                if (!$ || !$.fn.select2) return;
                const $el = $(select);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
            }

            function fillSubjects(batchSelect) {
                const row = batchSelect.closest('.enrollment-row');
                const subjectSelect = row.querySelector('.subject-select');
                const batchId = parseInt(batchSelect.value, 10);
                const batch = batches.find(function(b) {
                    return parseInt(b.id, 10) === batchId;
                });

                resetSelect2(subjectSelect);
                subjectSelect.innerHTML = '<option value="">Select subject</option>';

                if (batch && batch.subjects && batch.subjects.length) {
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

                if (window.initAdminSearchableSelects) {
                    window.initAdminSearchableSelects(subjectSelect);
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
                    let url = nextRollUrl + '?batch_id=' + encodeURIComponent(batchId)
                        + '&institute_id=' + encodeURIComponent(instituteId);
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
                document.querySelectorAll('.batch-select').forEach(function(sel) {
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

                    if (subjectSelect) {
                        subjectSelect.innerHTML = '<option value="">Select subject</option>';
                    }

                    if (window.initAdminSearchableSelects) {
                        window.initAdminSearchableSelects(sel);
                        if (subjectSelect) window.initAdminSearchableSelects(subjectSelect);
                    }
                });
            }

            async function loadBatchesForBranch(branchId) {
                if (!branchId) {
                    batches = [];
                    repopulateBatchSelects();
                    return;
                }
                try {
                    const res = await AdminAjax.get(
                        lookupsUrl + '/batches?institute_id=' + instituteId + '&branch_id=' + branchId
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
                } catch (e) {
                    await AdminAjax.error(e.message || 'Could not load batches.');
                }
            }

            wrap?.addEventListener('change', function(e) {
                if (e.target.classList.contains('batch-select')) {
                    fillSubjects(e.target);
                    fillNextRoll(e.target);
                }
            });

            const branchEl = document.getElementById('convertBranchId') || document.querySelector('[name="branch_id"]');
            branchEl?.addEventListener('change', function() {
                loadBatchesForBranch(this.value);
            });

            document.getElementById('addEnrollmentRow')?.addEventListener('click', function() {
                const first = wrap.querySelector('.enrollment-row');
                const clone = first.cloneNode(true);
                clone.querySelectorAll('.select2-container').forEach(function(el) {
                    el.remove();
                });
                clone.querySelectorAll('select').forEach(function(el) {
                    resetSelect2(el);
                    const name = el.getAttribute('name');
                    if (name) {
                        el.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
                    }
                    if (el.classList.contains('batch-select')) {
                        el.selectedIndex = 0;
                    } else if (el.classList.contains('subject-select')) {
                        el.innerHTML = '<option value="">Select subject</option>';
                    }
                });
                clone.querySelectorAll('input').forEach(function(el) {
                    el.value = '';
                });
                rowIndex++;
                wrap.appendChild(clone);

                if (window.initAdminSearchableSelects) {
                    window.initAdminSearchableSelects(clone);
                }
            });

            if (branchEl?.value && batches.length === 0) {
                loadBatchesForBranch(branchEl.value);
            } else {
                wrap?.querySelectorAll('.batch-select').forEach(function(sel) {
                    if (sel.value) fillSubjects(sel);
                });
            }

            document.getElementById('convertForm')?.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;
                try {
                    const fd = new FormData(this);
                    const enrollments = [];
                    const keys = {};
                    for (const [k, v] of fd.entries()) {
                        const m = k.match(/^enrollments\[(\d+)\]\[(\w+)\]$/);
                        if (m) {
                            keys[m[1]] = keys[m[1]] || {};
                            keys[m[1]][m[2]] = v;
                        }
                    }
                    Object.values(keys).forEach(row => enrollments.push(row));
                    const payload = {
                        branch_id: fd.get('branch_id'),
                        form_fees: fd.get('form_fees'),
                        form_fees_payment_method: fd.get('form_fees_payment_method'),
                        parent_password: fd.get('parent_password') || null,
                        enrollments: enrollments
                    };
                    const res = await AdminAjax.post(@json(route('admin.inquiry.convert.store', $inquiry)), payload);
                    await AdminAjax.success(res.message || 'Converted.');
                    window.location.href = res.data?.redirect_url || @json(route('admin.inquiry'));
                } catch (err) {
                    if (btn) btn.disabled = false;
                    await AdminAjax.error(err.message);
                }
            });
        })();
    </script>
@endpush
