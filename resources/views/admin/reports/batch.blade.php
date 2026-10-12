@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y report-page">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                @include('admin.reports._header', [
                    'reportTitle' => 'Batch Report',
                    'reportSubtitle' => 'Students, teachers & this month attendance snapshot',
                    'reportIcon' => 'bx-group',
                    'reportBackUrl' => route('admin.reports'),
                ])

                <div class="admin-filter-section no-print mt-3">
                    <form method="GET" action="{{ route('admin.reports.batch') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8 col-lg-6">
                                <label class="form-label fw-semibold">Batch</label>
                                <select class="form-select form-select-sm" name="batch_id" required>
                                    <option value="">Select batch</option>
                                    @foreach ($batches as $b)
                                        <option value="{{ $b->id }}" @selected($batchId == $b->id)>
                                            {{ $b->name }} — {{ $b->standard?->name }} ({{ $b->branch?->name }})
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
                    @php $batch = $data['batch']; @endphp
                    <div class="master-info-bar mt-3">
                        <div>
                            <strong>{{ $batch->name }} | {{ $batch->standard?->name ?? '—' }}</strong>
                            <div style="font-size: 0.82rem; opacity: 0.9;">
                                {{ $batch->branch?->name ?? '—' }} · Timing: {{ $batch->timingLabel() }}
                            </div>
                        </div>
                        <span>{{ $batch->isActive() ? 'Active' : 'Inactive' }}</span>
                    </div>

                    <div class="row g-3 mt-1">
                        @foreach (($data['attendance_counts'] ?? []) as $label => $count)
                            <div class="col-6 col-md-3">
                                <div class="report-stat-card">
                                    <div class="report-stat-label">{{ $label === 'P' ? 'Present' : ($label === 'A' ? 'Absent' : ($label === 'L' ? 'Late' : 'Holiday')) }} ({{ $data['attendance_month'] }})</div>
                                    <div class="report-stat-value">{{ $count }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <h6 class="mt-4 mb-2" style="color: var(--iv-primary-dark); font-weight:750;">Teachers</h6>
                    <div class="admin-table-wrap">
                        <div class="table-responsive">
                            <table class="table admin-list-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Phone</th>
                                        <th>Fees</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['teacher_rows'] as $t)
                                        <tr>
                                            <td>{{ $t['subject'] }}</td>
                                            <td class="fw-semibold">{{ $t['teacher'] }}</td>
                                            <td>{{ $t['phone'] }}</td>
                                            <td>{{ $t['fees'] !== null && $t['fees'] !== '' ? '₹'.number_format((float)$t['fees'], 2) : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted">No teachers assigned.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <h6 class="mt-4 mb-2" style="color: var(--iv-primary-dark); font-weight:750;">Students ({{ $data['students']->count() }})</h6>
                    <div class="admin-table-wrap">
                        <div class="table-responsive">
                            <table class="table admin-list-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Roll</th>
                                        <th>Student</th>
                                        <th>Admission</th>
                                        <th>Father</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($data['students'] as $en)
                                        <tr>
                                            <td>{{ $en->roll_no ?: '—' }}</td>
                                            <td class="fw-semibold">{{ $en->student?->name ?? '—' }}</td>
                                            <td>{{ $en->student?->admission_no ?? '—' }}</td>
                                            <td>{{ $en->student?->parent?->father_name ?? '—' }}</td>
                                            <td>{{ $en->student?->parent?->phone ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">No active students.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <p class="text-muted mt-3">Select a batch to generate the report.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
