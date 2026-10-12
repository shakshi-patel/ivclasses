@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y report-page">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                @include('admin.reports._header', [
                    'reportTitle' => 'Attendance Report',
                    'reportSubtitle' => 'Monthly batch attendance grid',
                    'reportIcon' => 'bx-calendar-check',
                    'reportBackUrl' => route('admin.reports'),
                ])

                <div class="admin-filter-section no-print mt-3">
                    <form method="GET" action="{{ route('admin.reports.attendance') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5 col-lg-4">
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
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label fw-semibold">Month</label>
                                <select class="form-select form-select-sm" name="month" data-no-search>
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" @selected($month == $m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label fw-semibold">Year</label>
                                <select class="form-select form-select-sm" name="year" data-no-search>
                                    @for ($y = now()->year; $y >= now()->year - 3; $y--)
                                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <button type="submit" class="btn btn-sm w-100 admin-btn-primary">
                                    <i class="bx bx-filter-alt me-1"></i> Show
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($report && $batch)
                    <div class="admin-list-card-bar mt-3">
                        <h5 class="mb-0">{{ $batch->name }} — {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</h5>
                    </div>
                    <div class="master-att-legend mb-2 no-print">
                        <span><i class="master-att-badge is-p">P</i> Present</span>
                        <span><i class="master-att-badge is-a">A</i> Absent</span>
                        <span><i class="master-att-badge is-lp">L</i> Late</span>
                        <span><i class="master-att-badge is-h">H</i> Holiday</span>
                    </div>
                    <div class="master-att-report-wrap report-table-scroll">
                        <table class="master-att-report">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Roll</th>
                                    @for ($d = 1; $d <= ($report['days_in_month'] ?? 0); $d++)
                                        <th>{{ $d }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['students'] ?? [] as $row)
                                    <tr>
                                        <td class="text-start fw-semibold">{{ $row['name'] }}</td>
                                        <td>{{ $row['roll_no'] }}</td>
                                        @for ($d = 1; $d <= ($report['days_in_month'] ?? 0); $d++)
                                            @php $cell = $row['days'][$d] ?? null; @endphp
                                            <td>
                                                @if (is_array($cell))
                                                    <span class="master-att-badge {{ $cell['class'] ?? '' }}">{{ $cell['label'] ?? '' }}</span>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 2 + ($report['days_in_month'] ?? 0) }}" class="text-muted">No students in this batch.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($batchId)
                    <p class="text-muted mt-3">No data for selected filters.</p>
                @else
                    <p class="text-muted mt-3">Select a batch and month to generate the report.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
