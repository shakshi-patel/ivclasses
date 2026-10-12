@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h4 class="admin-page-title mb-0">
                        <i class="bx bx-user"></i>
                        View Student
                    </h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.student') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back
                        </a>
                        @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('student.edit'))
                            <a href="{{ route('admin.student.edit', $student) }}" class="btn btn-sm admin-btn-primary">
                                <i class="bx bx-pencil me-1"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.student') }}">Student</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $student->name }}</li>
                    </ol>
                </nav>

                <div class="row g-3 mt-3">
                    <div class="col-lg-4">
                        <div class="admin-detail-block h-100">
                            <div class="text-center mb-3">
                                <img src="{{ $student->photo_path ? asset('storage/'.$student->photo_path) : asset('admin-assets/img/avatars/1.png') }}"
                                    alt="{{ $student->name }}" class="master-profile-avatar mx-auto">
                                <h5 class="mt-3 mb-1" style="color: var(--iv-primary-dark);">{{ $student->name }}</h5>
                                <span class="admin-status-pill is-navy">Branch : {{ $student->branch?->name ?? '—' }}</span>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2 rounded-3 text-center" style="background: rgba(var(--iv-primary-rgb), 0.08); border: 1px solid rgba(var(--iv-primary-rgb), 0.12);">
                                        <div class="text-muted small">Student Id</div>
                                        <strong style="color: var(--iv-primary-dark); font-size: 0.82rem;">#{{ $student->admission_no }}</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded-3 text-center" style="background: rgba(var(--iv-primary-rgb), 0.08); border: 1px solid rgba(var(--iv-primary-rgb), 0.12);">
                                        <div class="text-muted small">Roll No.</div>
                                        <strong class="iv-text-primary-dark" style="font-size: 0.82rem;">{{ $rollNo }}</strong>
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-2" style="color: var(--iv-primary-dark); font-weight: 750;">Student Details</h6>
                            <div class="master-profile-rows">
                                <div class="master-profile-row"><span>Name</span><strong>{{ $student->name }}</strong></div>
                                <div class="master-profile-row"><span>Contact No</span><strong>{{ $student->parent?->phone ?? '—' }}</strong></div>
                                <div class="master-profile-row"><span>Standards</span><strong>{{ $student->standard?->name ?? '—' }}</strong></div>
                                <div class="master-profile-row"><span>Date Of Birth</span><strong>{{ optional($student->dob)->format('d M Y') ?? '—' }}</strong></div>
                                <div class="master-profile-row"><span>Gender</span><strong>{{ $student->gender ? ucfirst($student->gender) : '—' }}</strong></div>
                                <div class="master-profile-row"><span>Aadhar No.</span><strong>{{ $student->aadhaar ?: '—' }}</strong></div>
                                <div class="master-profile-row"><span>Category</span><strong>{{ $student->category ?: '—' }}</strong></div>
                                <div class="master-profile-row"><span>Admission Date</span><strong>{{ optional($student->admission_date)->format('d M Y') ?? '—' }}</strong></div>
                                <div class="master-profile-row"><span>School Name</span><strong>{{ $student->school_name ?: '—' }}</strong></div>
                                <div class="master-profile-row"><span>Batch</span><strong>{{ $batchName }}</strong></div>
                                <div class="master-profile-row"><span>Address</span><strong>{{ $student->address ?: '—' }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <ul class="nav stu-view-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#svAtt" type="button">
                                    <i class="bx bx-calendar"></i> Attendance
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#svExam" type="button">
                                    <i class="bx bx-edit"></i> Exam
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#svFees" type="button">
                                    <i class="bx bx-rupee"></i> Fees
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#svHw" type="button">
                                    <i class="bx bx-book-open"></i> Home Work
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#svBatch" type="button">
                                    <i class="bx bx-group"></i> Assign Batch
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#svNotice" type="button">
                                    <i class="bx bx-bell"></i> Notice
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#svGst" type="button">
                                    <i class="bx bx-receipt"></i> GST Invoice
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#svResult" type="button">
                                    <i class="bx bx-trophy"></i> Result Announcement
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content admin-detail-block">
                            <div class="tab-pane fade show active" id="svAtt">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="master-att-nav">
                                        <button type="button" class="btn-att-nav" id="stuAttPrev" aria-label="Previous month">
                                            <i class="bx bx-chevron-left"></i>
                                        </button>
                                        <h6 id="stuAttMonthLabel">—</h6>
                                        <button type="button" class="btn-att-nav" id="stuAttNext" aria-label="Next month">
                                            <i class="bx bx-chevron-right"></i>
                                        </button>
                                    </div>
                                    <a href="#" id="stuAttExport" class="btn btn-sm btn-export-students"
                                        data-base="{{ route('admin.export.student_attendance', $student) }}">
                                        <i class="bx bx-download me-1"></i> Export PDF
                                    </a>
                                </div>
                                @if ($activeEnrollments->isEmpty())
                                    <p class="text-muted mb-0">No active batch enrollment found.</p>
                                @else
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small mb-1">Select Batch</label>
                                        <select class="form-select form-select-sm" id="stuAttBatchSelect" style="max-width: 320px;">
                                            @foreach ($activeEnrollments as $enr)
                                                <option value="{{ $enr->batch_id }}" @selected($enr->batch_id == $defaultBatchId)>
                                                    {{ $enr->batch?->name ?? 'Batch #'.$enr->batch_id }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-4"><div class="master-att-stat is-present py-2"><span>Present: <strong id="stuSumPresent">0</strong></span></div></div>
                                        <div class="col-4"><div class="master-att-stat is-absent py-2"><span>Absent: <strong id="stuSumAbsent">0</strong></span></div></div>
                                        <div class="col-4"><div class="master-att-stat is-late py-2"><span>Late: <strong id="stuSumLate">0</strong></span></div></div>
                                    </div>
                                    <div class="master-att-cal mb-3" id="stuAttCal"></div>
                                    <div class="master-att-legend">
                                        <span><i class="master-att-dot is-present"></i> Present</span>
                                        <span><i class="master-att-dot is-absent"></i> Absent</span>
                                        <span><i class="master-att-dot is-late"></i> Late Present</span>
                                        <span><span class="master-att-badge is-h">H</span> Holiday</span>
                                    </div>
                                @endif
                            </div>

                            <div class="tab-pane fade" id="svExam">
                                <div class="row g-2 g-lg-3">
                                    @forelse ($studentExams ?? [] as $exam)
                                        <div class="col-sm-6 col-xl-4">
                                            <div class="master-exam-card">
                                                <strong>{{ $exam->title }}</strong>
                                                <div class="c-date">{{ $exam->exam_date?->format('d M Y') ?? '—' }}</div>
                                                <div class="c-meta">
                                                    @if ($exam->subject?->name){{ $exam->subject->name }} · @endif
                                                    @if ($exam->duration_label)Timing: {{ $exam->duration_label }} · @endif
                                                    Maximum Marks: {{ rtrim(rtrim(number_format((float) $exam->total_marks, 2), '0'), '.') }}
                                                </div>
                                                @if ($exam->description)
                                                    <div class="c-body">{{ \Illuminate\Support\Str::limit($exam->description, 120) }}</div>
                                                @endif
                                                @if ($exam->batch?->name)
                                                    <div class="text-muted small mt-1">Batch: {{ $exam->batch->name }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12"><p class="text-muted mb-0">No exams found for this student’s batches.</p></div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="tab-pane fade" id="svFees">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="stu-fee-stat">
                                            <div class="ico is-total"><i class="bx bx-wallet"></i></div>
                                            <div>
                                                <div class="lbl">Total Fees</div>
                                                <div class="val">₹{{ number_format($feeTotal, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stu-fee-stat">
                                            <div class="ico is-recv"><i class="bx bx-down-arrow-circle"></i></div>
                                            <div>
                                                <div class="lbl">Received Fees</div>
                                                <div class="val">₹{{ number_format($feePaid, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stu-fee-stat">
                                            <div class="ico is-rem"><i class="bx bx-time-five"></i></div>
                                            <div>
                                                <div class="lbl">Remaining Fees</div>
                                                <div class="val">₹{{ number_format($feeDue, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <h6 class="mb-0" style="color: var(--iv-primary-dark); font-weight: 750;">Fees History</h6>
                                    @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('fees.add'))
                                        <a href="{{ route('admin.fees.add', ['student_id' => $student->id]) }}" class="btn btn-sm" style="background: var(--iv-primary); color: #fff;">
                                            <i class="bx bx-plus me-1"></i> Add Fees
                                        </a>
                                    @endif
                                </div>
                                @forelse ($feePayments as $payment)
                                    <div class="stu-fee-history-card mb-3">
                                        <div class="fh-top">
                                            <span class="fh-title">{{ $payment->invoice?->title ?: 'Fee Payment' }}</span>
                                            <span class="stu-method-pill">{{ $payment->payment_method ?: '—' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <div class="fh-amt">₹{{ number_format((float) $payment->amount, 2) }}</div>
                                            <span class="admin-status-pill is-active">Paid</span>
                                            @if ($payment->receipt_no)
                                                <span class="text-muted small">{{ $payment->receipt_no }}</span>
                                            @endif
                                        </div>
                                        <div class="fh-date">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</div>
                                        <div class="fh-remarks"><strong style="color: var(--iv-primary-dark);">Remarks :</strong> {{ $payment->note ?: '—' }}</div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-3">No fee payments recorded yet.</p>
                                @endforelse

                                <h6 class="mb-2" style="color: var(--iv-primary-dark); font-weight: 750;">Fees Followup Details</h6>
                                <div class="admin-table-wrap mb-0">
                                    <div class="table-responsive">
                                        <table class="table admin-list-table align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Sr No.</th>
                                                    <th>Followup Date</th>
                                                    <th>Next Followup Date</th>
                                                    <th>Remark</th>
                                                    <th>Status</th>
                                                    <th>Created By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($feeFollowUps as $i => $fu)
                                                    <tr>
                                                        <td>{{ $i + 1 }}</td>
                                                        <td>{{ $fu->follow_up_at?->format('d M Y') ?? '—' }}</td>
                                                        <td>{{ $fu->next_follow_up_at?->format('d M Y') ?? '—' }}</td>
                                                        <td>{{ $fu->remark ?: '—' }}</td>
                                                        <td>
                                                            <span class="admin-status-pill {{ $fu->status === 'complete' ? 'is-active' : 'is-navy' }}">
                                                                {{ $fu->status === 'complete' ? 'Done' : ucfirst($fu->status ?? '—') }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $fu->creator?->name ?? '—' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-muted text-center">No fee follow-ups yet.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="svHw">
                                <div class="row g-2 g-lg-3">
                                    @forelse ($studentHomeworks ?? [] as $hw)
                                        <div class="col-sm-6 col-xl-4">
                                            @php
                                                $dueDate = $hw->due_date;
                                                $dueState = ! $dueDate ? 'no-date' : ($dueDate->isBefore(today()) ? 'overdue' : ($dueDate->isToday() ? 'today' : (today()->diffInDays($dueDate) <= 3 ? 'soon' : 'upcoming')));
                                                $canDownloadHomework = $hw->attachment_path && (auth()->user()?->isPlatformUser() || auth()->user()?->can('homework.download'));
                                            @endphp
                                            <div class="master-hw-card">
                                                @if ($canDownloadHomework)
                                                    <a href="{{ route('admin.homework.download', $hw) }}" class="master-hw-attachment-badge" title="Download homework document" aria-label="Download homework document">
                                                        <i class="bx bx-download"></i>
                                                    </a>
                                                @endif
                                                <strong>{{ $hw->title }}</strong>
                                                @if ($hw->subject?->name)
                                                    <div class="text-muted small">{{ $hw->subject->name }}</div>
                                                @endif
                                                <div class="master-hw-due is-{{ $dueState }}">
                                                    <i class="bx bx-calendar-exclamation"></i>
                                                    <span>{{ $dueState === 'overdue' ? 'Overdue' : ($dueState === 'today' ? 'Due today' : 'Due') }}</span>
                                                    <b>{{ $dueDate?->format('d M Y') ?? 'Not set' }}</b>
                                                </div>
                                                <div class="c-body">{{ \Illuminate\Support\Str::limit($hw->description ?? '—', 120) }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12"><p class="text-muted mb-0">No homework assigned yet.</p></div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="tab-pane fade" id="svBatch">
                                @if ($student->enrollments->isEmpty())
                                    <p class="text-muted mb-0">No batch assigned yet.</p>
                                @else
                                    <div class="row g-2 g-lg-3">
                                        @foreach ($student->enrollments as $enr)
                                            <div class="col-sm-6 col-xl-4">
                                                <div class="stu-assign-batch-card">
                                                    <strong>{{ $enr->batch?->name ?? '—' }}</strong>
                                                    <div class="meta">
                                                        Subject: {{ $enr->subject?->name ?? '—' }}<br>
                                                        Roll No: {{ $enr->roll_no ?? '—' }}<br>
                                                        Branch: {{ $enr->batch?->branch?->name ?? '—' }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="tab-pane fade" id="svNotice">
                                <div class="row g-2 g-lg-3">
                                    @forelse ($studentNotices ?? [] as $notice)
                                        <div class="col-sm-6 col-xl-4">
                                            <div class="master-notice-card">
                                                <strong>{{ $notice->title }}</strong>
                                                <div class="c-date">
                                                    {{ \App\Models\Notice::AUDIENCES[$notice->audience] ?? ucfirst($notice->audience ?? 'Notice') }}
                                                    · {{ $notice->scopeLabel() }}
                                                    · {{ $notice->notice_date?->format('d M Y') ?? '—' }}
                                                </div>
                                                <div class="c-body">{{ \Illuminate\Support\Str::limit($notice->body ?? '—', 140) }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12"><p class="text-muted mb-0">No notices for this student yet.</p></div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="tab-pane fade" id="svGst">
                                @forelse ($studentGstInvoices ?? [] as $gst)
                                    <div class="stu-gst-card mb-3">
                                        <div class="gst-head d-flex flex-wrap align-items-center justify-content-between gap-2">
                                            <span>GST Invoice — {{ $gst->invoice_no }}</span>
                                            <a href="{{ route('admin.gst_invoice.show', $gst) }}" class="btn btn-sm btn-outline-light" target="_blank" rel="noopener">
                                                <i class="bx bx-show me-1"></i> View
                                            </a>
                                        </div>
                                        <div class="gst-body">
                                            <div class="row g-3">
                                                <div class="col-md-6 gst-party">
                                                    <h6>Institute Details:</h6>
                                                    <p><strong>{{ $gst->institute?->name ?? '—' }}</strong></p>
                                                    <p>{{ $gst->institute?->address ?: '—' }}</p>
                                                    <p>GSTIN: {{ $gst->gstin ?: ($gst->institute?->gstin ?: '—') }}</p>
                                                </div>
                                                <div class="col-md-6 gst-party text-md-end">
                                                    <h6>Student Details:</h6>
                                                    <p><strong>{{ $student->name }}</strong></p>
                                                    <p>Contact No.: {{ $student->parent?->phone ?? '—' }}</p>
                                                    <p>Email: {{ $student->parent?->email ?? '—' }}</p>
                                                </div>
                                            </div>

                                            <table class="stu-gst-table">
                                                <thead>
                                                    <tr>
                                                        <th>Description</th>
                                                        <th class="amt">Amount (₹)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            Fee Payment
                                                            @if ($gst->payment?->receipt_no)
                                                                ({{ $gst->payment->receipt_no }})
                                                            @endif
                                                            · {{ $gst->payment?->payment_date?->format('d M Y') ?? $gst->created_at?->format('d M Y') }}
                                                        </td>
                                                        <td class="amt">₹{{ number_format((float) ($gst->payment?->amount ?? $gst->total), 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Total Fees (Taxable)</td>
                                                        <td class="amt">₹{{ number_format((float) $gst->taxable, 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>CGST @ 9%</td>
                                                        <td class="amt">₹{{ number_format((float) $gst->cgst, 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>SGST @ 9%</td>
                                                        <td class="amt">₹{{ number_format((float) $gst->sgst, 2) }}</td>
                                                    </tr>
                                                    @if ((float) $gst->igst > 0)
                                                        <tr>
                                                            <td>IGST</td>
                                                            <td class="amt">₹{{ number_format((float) $gst->igst, 2) }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr class="is-total">
                                                        <td>Grand Total</td>
                                                        <td class="amt">₹{{ number_format((float) $gst->total, 2) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <div class="stu-gst-foot">
                                                <div class="note"><strong>Note:</strong> This is a computer-generated invoice and does not require a signature.</div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No GST invoices generated for this student yet.</p>
                                @endforelse
                            </div>

                            <div class="tab-pane fade" id="svResult">
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('admin.export.student_results', $student) }}" class="btn btn-sm btn-export-students">
                                        <i class="bx bx-download me-1"></i> Export PDF
                                    </a>
                                </div>
                                <div class="admin-table-wrap mb-0">
                                    <div class="table-responsive">
                                        <table class="table admin-list-table align-middle mb-0" style="min-width: 980px;">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Batch</th>
                                                    <th>Subject</th>
                                                    <th>Max Marks</th>
                                                    <th>Obtain Marks</th>
                                                    <th>Remarks</th>
                                                    <th>Attempt</th>
                                                    <th>Attend</th>
                                                    <th>Date</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($studentResults ?? [] as $announcement)
                                                    @php($exam = $announcement->exam)
                                                    @php($result = $announcement->studentResult)
                                                    @php($max = (float) ($exam?->total_marks ?? 0))
                                                    @php($marks = $result ? (float) $result->marks_obtained : null)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $exam?->batch?->name ?? '—' }}</td>
                                                        <td>{{ $exam?->subject?->name ?? '—' }}</td>
                                                        <td>{{ $max > 0 ? rtrim(rtrim(number_format($max, 2), '0'), '.') : '—' }}</td>
                                                        <td>{{ $marks !== null ? rtrim(rtrim(number_format($marks, 2), '0'), '.') : '—' }}</td>
                                                        <td>{{ $result?->remark ?: '—' }}</td>
                                                        <td>{{ $result === null ? '—' : ($result->is_attempted ? 'Yes' : 'No') }}</td>
                                                        <td>{{ $result?->attendance_status ? ucfirst($result->attendance_status) : '—' }}</td>
                                                        <td>{{ ($announcement->result_date ?? $announcement->published_at)?->format('d M Y') ?? '—' }}</td>
                                                        <td class="text-center">
                                                            @if (auth()->user()?->isPlatformUser() || auth()->user()?->can('result_announcement.export'))
                                                                <a href="{{ route('admin.result_announcement.export_pdf', $announcement) }}?student_id={{ $student->id }}"
                                                                    class="admin-action-icon" title="Export PDF">
                                                                    <i class="bx bx-download"></i>
                                                                </a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="10" class="text-muted text-center py-4">No published results for this student yet.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
            const label = document.getElementById('stuAttMonthLabel');
            const grid = document.getElementById('stuAttCal');
            const prev = document.getElementById('stuAttPrev');
            const next = document.getElementById('stuAttNext');
            const batchSelect = document.getElementById('stuAttBatchSelect');
            const reportUrl = @json(route('admin.attendance.report.student', $student));
            const sumP = document.getElementById('stuSumPresent');
            const sumA = document.getElementById('stuSumAbsent');
            const sumL = document.getElementById('stuSumLate');
            const exportBtn = document.getElementById('stuAttExport');

            if (label && grid && prev && next && batchSelect) {
                let view = new Date();
                let dayMap = {};

                function syncExport() {
                    if (!exportBtn) return;
                    const base = exportBtn.dataset.base || '';
                    const batchId = batchSelect.value;
                    if (!batchId) return;
                    exportBtn.href = base + '?batch_id=' + encodeURIComponent(batchId)
                        + '&year=' + view.getFullYear()
                        + '&month=' + (view.getMonth() + 1);
                }

                function renderCalendar(data) {
                    dayMap = data?.days || {};
                    const holidays = data?.holidays || {};
                    if (sumP) sumP.textContent = data?.summary?.present ?? 0;
                    if (sumA) sumA.textContent = data?.summary?.absent ?? 0;
                    if (sumL) sumL.textContent = data?.summary?.late ?? 0;

                    const y = view.getFullYear();
                    const m = view.getMonth();
                    label.textContent = view.toLocaleDateString('en-IN', { month: 'long', year: 'numeric' });
                    grid.innerHTML = '';
                    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function(t) {
                        const el = document.createElement('div');
                        el.className = 'dow';
                        el.textContent = t;
                        grid.appendChild(el);
                    });
                    const first = new Date(y, m, 1);
                    const pad = (first.getDay() + 6) % 7;
                    const daysInMonth = new Date(y, m + 1, 0).getDate();
                    for (let i = 0; i < pad; i++) {
                        const empty = document.createElement('div');
                        empty.className = 'master-att-day is-empty';
                        grid.appendChild(empty);
                    }
                    for (let day = 1; day <= daysInMonth; day++) {
                        const cell = document.createElement('div');
                        const cls = dayMap[day] || dayMap[String(day)] || '';
                        const isHoliday = cls === 'is-holiday';
                        cell.className = 'master-att-day' + (cls ? ' ' + cls : '');
                        cell.textContent = isHoliday ? 'H' : String(day);
                        const holiday = holidays[day] || holidays[String(day)];
                        if (holiday?.title) {
                            cell.title = holiday.title;
                        }
                        grid.appendChild(cell);
                    }
                }

                async function loadReport() {
                    syncExport();
                    const batchId = batchSelect.value;
                    if (!batchId) return;
                    const url = reportUrl + '?batch_id=' + encodeURIComponent(batchId)
                        + '&year=' + view.getFullYear()
                        + '&month=' + (view.getMonth() + 1);
                    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const json = await res.json();
                    if (json.success) renderCalendar(json.data);
                }

                prev.addEventListener('click', function() {
                    view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
                    loadReport();
                });
                next.addEventListener('click', function() {
                    view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                    loadReport();
                });
                batchSelect.addEventListener('change', loadReport);
                loadReport();
            }
        })();
    </script>
@endpush
