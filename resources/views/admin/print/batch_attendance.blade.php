@extends('admin.layouts.layout')

@section('content')
    @php
        $instituteName = $batch->institute?->name ?? ($brandName ?? config('app.name'));
        $monthTitle = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $daysInMonth = (int) ($report['days_in_month'] ?? 0);
        $sundays = collect($report['sundays'] ?? []);
        $holidays = $report['holidays'] ?? [];
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap no-print">
                    <h4 class="admin-page-title mb-0">
                        <i class="bx bx-calendar-check"></i> Attendance Report — {{ $batch->name }}
                    </h4>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.master.batch.show', $batch) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="bx bx-printer me-1"></i> Print / Save PDF
                        </button>
                    </div>
                </div>

                <div class="iv-print-sheet mt-4">
                    <div class="iv-print-sheet__head">
                        <div>
                            <p class="iv-print-sheet__brand">{{ $instituteName }}</p>
                            <h3 class="iv-print-sheet__title">Attendance report</h3>
                            <p class="iv-print-sheet__sub mb-0">
                                {{ $batch->name }}
                                @if ($batch->standard?->name) · {{ $batch->standard->name }} @endif
                                @if ($batch->branch?->name) · {{ $batch->branch->name }} @endif
                                · {{ $monthTitle }}
                            </p>
                        </div>
                        <div class="iv-print-sheet__meta text-md-end">
                            <span class="iv-print-pill">{{ count($report['students'] ?? []) }} students</span>
                            <p class="small text-muted mb-0 mt-2">Printed {{ now()->format('d M Y, h:i A') }}</p>
                        </div>
                    </div>

                    <div class="master-att-legend px-3 pt-3 mb-0">
                        <span><span class="master-att-badge is-p">P</span> Present</span>
                        <span><span class="master-att-badge is-a">A</span> Absent</span>
                        <span><span class="master-att-badge is-lp">LP</span> Late</span>
                        <span><span class="master-att-badge is-lv">LV</span> Leave</span>
                        <span><span class="master-att-badge is-h">H</span> Holiday</span>
                    </div>

                    <div class="master-att-report-wrap report-table-scroll px-2 pb-2">
                        <table class="master-att-report">
                            <thead>
                                <tr>
                                    <th class="name-col">Student</th>
                                    <th>Roll</th>
                                    @for ($d = 1; $d <= $daysInMonth; $d++)
                                        @php
                                            $isSun = $sundays->contains($d);
                                            $isHol = isset($holidays[$d]);
                                        @endphp
                                        <th class="day-head {{ $isSun ? 'is-sun' : '' }} {{ $isHol ? 'is-holiday' : '' }}">{{ $d }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report['students'] ?? [] as $row)
                                    <tr>
                                        <td class="name-col text-start fw-semibold">{{ $row['name'] }}</td>
                                        <td>{{ $row['roll_no'] }}</td>
                                        @for ($d = 1; $d <= $daysInMonth; $d++)
                                            @php $cell = $row['days'][$d] ?? null; @endphp
                                            <td>
                                                @if (is_array($cell) && ! empty($cell['label']))
                                                    <span class="master-att-badge {{ $cell['class'] ?? '' }}">{{ $cell['label'] }}</span>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 2 + $daysInMonth }}" class="text-muted p-3">No students in this batch.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="iv-print-sheet__foot">
                        <span>Generated by {{ config('app.name') }}</span>
                        <span>{{ $monthTitle }} · {{ $instituteName }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .iv-print-sheet {
            border: 1px solid rgba(var(--iv-primary-rgb), 0.16);
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 40, 35, 0.06);
        }
        .iv-print-sheet__head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.4rem;
            background: linear-gradient(135deg, rgba(var(--iv-primary-rgb), 0.08), rgba(var(--iv-secondary-rgb), 0.1));
            border-bottom: 1px solid rgba(var(--iv-primary-rgb), 0.12);
        }
        .iv-print-sheet__brand {
            margin: 0;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--iv-primary);
        }
        .iv-print-sheet__title {
            margin: 0.2rem 0 0.15rem;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--iv-primary-dark);
        }
        .iv-print-sheet__sub {
            color: var(--iv-text-secondary);
            font-size: 0.9rem;
        }
        .iv-print-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: var(--iv-primary);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .iv-print-sheet__foot {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1.25rem;
            border-top: 1px solid rgba(var(--iv-primary-rgb), 0.1);
            background: rgba(var(--iv-primary-rgb), 0.03);
            color: var(--iv-text-secondary);
            font-size: 0.78rem;
        }
        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            .iv-print-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .master-att-report {
                font-size: 10px !important;
            }
            .master-att-badge {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
@endpush

@push('scripts')
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 350);
    });
</script>
@endpush
