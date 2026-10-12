@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y report-page">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                @include('admin.reports._header', [
                    'reportTitle' => 'Student Report',
                    'reportSubtitle' => 'Profile, batches and attendance summary',
                    'reportIcon' => 'bx-user',
                    'reportBackUrl' => route('admin.reports'),
                ])

                <div class="admin-filter-section no-print mt-3">
                    <form method="GET" action="{{ route('admin.reports.student') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8 col-lg-6">
                                <label class="form-label fw-semibold">Student</label>
                                <select class="form-select form-select-sm" name="student_id" required>
                                    <option value="">Select student</option>
                                    @foreach ($students as $s)
                                        <option value="{{ $s->id }}" @selected($studentId == $s->id)>
                                            {{ $s->name }}{{ $s->admission_no ? ' ('.$s->admission_no.')' : '' }} — {{ $s->standard?->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <button type="submit" class="btn btn-sm w-100 admin-btn-primary">
                                    <i class="bx bx-filter-alt me-1"></i> Show
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($data)
                    @php $student = $data['student']; @endphp
                    <div class="master-info-bar mt-3">
                        <div>
                            <strong>{{ $student->name }}</strong>
                            <div style="font-size: 0.82rem; opacity: 0.9;">
                                {{ $student->admission_no ?? '—' }} · {{ $student->standard?->name ?? '—' }} · {{ $student->branch?->name ?? '—' }}
                            </div>
                        </div>
                        <span>{{ $student->isActive() ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="report-detail-box">
                                <div class="report-detail-title">Contact & Parent</div>
                                <p class="mb-1"><strong>Phone:</strong> {{ $student->parent?->phone ?: '—' }}</p>
                                <p class="mb-1"><strong>Father:</strong> {{ $student->parent?->father_name ?? '—' }}</p>
                                <p class="mb-1"><strong>Mother:</strong> {{ $student->parent?->mother_name ?? '—' }}</p>
                                <p class="mb-0"><strong>Address:</strong> {{ $student->address ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="report-detail-box">
                                <div class="report-detail-title">Attendance (all time)</div>
                                <div class="row g-2">
                                    @forelse ($data['attendance_stats'] as $status => $total)
                                        <div class="col-6">
                                            <div class="report-stat-card">
                                                <div class="report-stat-label">{{ ucfirst(str_replace('_', ' ', $status)) }}</div>
                                                <div class="report-stat-value">{{ $total }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted small">No attendance marked yet.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mt-4 mb-2" style="color: var(--iv-primary-dark); font-weight:750;">Batches</h6>
                    <div class="admin-table-wrap">
                        <div class="table-responsive">
                            <table class="table admin-list-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Batch</th>
                                        <th>Branch</th>
                                        <th>Roll</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['enrollments'] as $en)
                                        <tr>
                                            <td class="fw-semibold">{{ $en->batch?->name ?? '—' }}</td>
                                            <td>{{ $en->batch?->branch?->name ?? '—' }}</td>
                                            <td>{{ $en->roll_no ?: '—' }}</td>
                                            <td>
                                                @if ($en->status === 'active')
                                                    <span class="admin-status-pill is-active">Active</span>
                                                @else
                                                    <span class="admin-status-pill is-muted">{{ ucfirst($en->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted">Not enrolled in any batch.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <p class="text-muted mt-3">Select a student to generate the report.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
