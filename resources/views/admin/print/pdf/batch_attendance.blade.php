<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Attendance Report</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 8mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2a28;
            margin: 0;
            padding: 0;
        }
        .banner {
            background: #0f5c4d;
            color: #fff;
            text-align: center;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .banner h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .banner .sub {
            margin-top: 3px;
            font-size: 9.5px;
            color: #f0d78a;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .meta td {
            vertical-align: top;
            padding: 2px 0;
            font-size: 9.5px;
            width: 50%;
        }
        .meta .label {
            color: #0f5c4d;
            font-weight: 700;
            display: inline-block;
            min-width: 72px;
        }
        .meta .value { color: #243230; }

        table.att {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.att thead { display: table-header-group; }
        table.att tr { page-break-inside: avoid; }
        table.att thead th {
            background: #0f5c4d;
            color: #fff;
            font-size: 7.5px;
            font-weight: 700;
            padding: 5px 1px;
            text-align: center;
            border: 1px solid #0c4a3e;
            white-space: nowrap;
        }
        table.att thead th.name-col {
            text-align: left;
            padding-left: 6px;
            width: 150px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        table.att thead th.is-sun { background: #5f6b6a; }
        table.att thead th.is-holiday { background: #4a6fa5; }
        table.att tbody td {
            border: 1px solid #d5e3df;
            padding: 3px 1px;
            text-align: center;
            vertical-align: middle;
            height: 18px;
        }
        table.att tbody td.name-col {
            text-align: left;
            padding: 4px 6px;
            font-size: 8px;
            font-weight: 600;
            color: #1a2e2a;
            white-space: nowrap;
            overflow: hidden;
        }
        table.att tbody tr:nth-child(even) td { background: #f5faf8; }
        table.att tbody tr:nth-child(even) td.name-col { background: #eef6f3; }

        .badge {
            display: inline-block;
            min-width: 14px;
            padding: 1px 2px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
            text-align: center;
        }
        .badge.is-p { background: #2f9e6a; }
        .badge.is-a { background: #d64545; }
        .badge.is-lp { background: #e09b2d; color: #2c2208; }
        .badge.is-h { background: #5b7fc4; }
        .badge.is-s { background: #7a8584; }
        .dash { color: #b0bec5; font-size: 8px; }

        .legend {
            margin-top: 12px;
            padding: 8px 10px;
            background: #f4faf8;
            border: 1px solid #d7e4e0;
            border-radius: 6px;
            border-left: 4px solid #d9a441;
        }
        .legend-title {
            font-size: 9px;
            font-weight: 700;
            color: #0f5c4d;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .legend-item {
            display: inline-block;
            margin-right: 14px;
            font-size: 8.5px;
            color: #374745;
            vertical-align: middle;
        }
        .legend-item .badge { margin-right: 3px; min-width: 18px; padding: 2px 4px; font-size: 8px; }

        .foot {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 2px solid #d9a441;
            font-size: 8.5px;
            color: #5c6b68;
        }
        .foot .left { float: left; }
        .foot .right { float: right; color: #0f5c4d; font-weight: 700; }
        .clear { clear: both; }
    </style>
</head>
<body>
@php
    $instituteName = $batch->institute?->name ?? config('app.name');
    $monthTitle = date('Y F', mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth = (int) ($report['days_in_month'] ?? 0);
    $sundays = collect($report['sundays'] ?? []);
    $holidays = $report['holidays'] ?? [];
@endphp

<div class="banner">
    <h1>Student Attendance Report</h1>
    <div class="sub">{{ $instituteName }} · {{ $monthTitle }}</div>
</div>

<table class="meta">
    <tr>
        <td><span class="label">Institute :</span> <span class="value">{{ $instituteName }}</span></td>
        <td><span class="label">Month :</span> <span class="value">{{ $monthTitle }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Branch :</span> <span class="value">{{ $batch->branch?->name ?? '—' }}</span></td>
        <td><span class="label">Standard :</span> <span class="value">{{ $batch->standard?->name ?? '—' }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Batch :</span> <span class="value">{{ $batch->name }}</span></td>
        <td><span class="label">Exported On :</span> <span class="value">{{ now()->format('m/d/Y, h:i:s A') }}</span></td>
    </tr>
</table>

<table class="att">
    <thead>
        <tr>
            <th class="name-col">Name &amp; Roll No</th>
            @for ($d = 1; $d <= $daysInMonth; $d++)
                @php
                    $isSun = $sundays->contains($d);
                    $isHol = isset($holidays[$d]);
                @endphp
                <th class="{{ $isSun ? 'is-sun' : '' }} {{ $isHol ? 'is-holiday' : '' }}">{{ sprintf('%02d', $d) }}</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @forelse ($report['students'] ?? [] as $row)
            <tr>
                <td class="name-col">{{ $row['name'] }} | {{ $row['roll_no'] }}</td>
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $cell = $row['days'][$d] ?? null;
                        $isSun = $sundays->contains($d);
                        $isHol = isset($holidays[$d]);
                    @endphp
                    <td>
                        @if (is_array($cell) && ! empty($cell['label']))
                            <span class="badge {{ $cell['class'] ?? '' }}">{{ $cell['label'] }}</span>
                        @elseif ($isHol)
                            <span class="badge is-h">H</span>
                        @elseif ($isSun)
                            <span class="badge is-s">S</span>
                        @else
                            <span class="dash">-</span>
                        @endif
                    </td>
                @endfor
            </tr>
        @empty
            <tr>
                <td colspan="{{ 1 + $daysInMonth }}" style="text-align:center;color:#78909c;padding:14px;">No students in this batch.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="legend">
    <div class="legend-title">Legend</div>
    <span class="legend-item"><span class="badge is-p">P</span> = Present</span>
    <span class="legend-item"><span class="badge is-lp">LP</span> = Late Present</span>
    <span class="legend-item"><span class="badge is-a">A</span> = Absent</span>
    <span class="legend-item"><span class="badge is-s">S</span> = Sunday</span>
    <span class="legend-item"><span class="badge is-h">H</span> = Holiday</span>
</div>

<div class="foot">
    <div class="left">Generated by {{ config('app.name') }}</div>
    <div class="right">{{ $monthTitle }} · {{ $instituteName }}</div>
    <div class="clear"></div>
</div>
</body>
</html>
