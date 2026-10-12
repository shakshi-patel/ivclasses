@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap no-print">
                    <h4 class="admin-page-title mb-0"><i class="bx bx-trophy"></i> Result — {{ $exam->title ?? $announcement->title }}</h4>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i> Print / Save PDF
                    </button>
                </div>

                <div class="fees-panel mt-4">
                    <div class="fees-panel-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4"><strong>Exam:</strong> {{ $exam->title ?? '—' }}</div>
                            <div class="col-md-4"><strong>Subject:</strong> {{ $exam->subject?->name ?? '—' }}</div>
                            <div class="col-md-4"><strong>Total Marks:</strong> {{ $exam->total_marks ?? '—' }}</div>
                            <div class="col-md-4"><strong>Batch:</strong> {{ $exam->batch?->name ?? '—' }}</div>
                            <div class="col-md-4"><strong>Branch:</strong> {{ $exam->branch?->name ?? '—' }}</div>
                            <div class="col-md-4"><strong>Date:</strong> {{ ($announcement->result_date ?? $announcement->published_at)?->format('d M Y') ?? '—' }}</div>
                        </div>

                        <h6 class="fw-semibold">Present Students</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Roll</th>
                                        <th>Name</th>
                                        <th>Marks</th>
                                        <th>Remark</th>
                                        <th>Attempt</th>
                                        <th>Attend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($present as $row)
                                        <tr>
                                            <td>{{ $row['roll_no'] ?? '—' }}</td>
                                            <td>{{ $row['student']->name ?? '—' }}</td>
                                            <td>{{ $row['result']?->marks_obtained ?? '—' }}</td>
                                            <td>{{ $row['result']?->remark ?? '—' }}</td>
                                            <td>{{ $row['result'] === null ? '—' : ($row['result']->is_attempted ? 'Yes' : 'No') }}</td>
                                            <td>{{ $row['result']?->attendance_status ? ucfirst($row['result']->attendance_status) : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted text-center">No present students</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-semibold">Absent Students</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Roll</th>
                                        <th>Name</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($absent as $row)
                                        <tr>
                                            <td>{{ $row['roll_no'] ?? '—' }}</td>
                                            <td>{{ $row['student']->name ?? '—' }}</td>
                                            <td>{{ $row['result']?->remark ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-muted text-center">No absent students</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.result_announcement') }}" class="btn btn-outline-secondary mt-3 no-print">
                    <i class="bx bx-left-arrow-alt me-1"></i> Back
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
