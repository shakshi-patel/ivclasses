<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Results Report</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2a28;
            margin: 0;
            padding: 0;
        }
        .banner {
            background: #0f5c4d;
            color: #fff;
            text-align: center;
            padding: 13px 14px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .banner h1 {
            margin: 0;
            font-size: 17px;
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
            margin-bottom: 12px;
        }
        .meta td {
            vertical-align: top;
            padding: 2px 0;
            font-size: 10px;
            width: 50%;
        }
        .meta .label {
            color: #0f5c4d;
            font-weight: 700;
            display: inline-block;
            min-width: 78px;
        }
        table.list {
            width: 100%;
            border-collapse: collapse;
        }
        table.list thead th {
            background: #0f5c4d;
            color: #fff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 7px 5px;
            text-align: left;
            border: 1px solid #0c4a3e;
        }
        table.list tbody td {
            padding: 6px 5px;
            border: 1px solid #d7e4e0;
            font-size: 9.5px;
            vertical-align: middle;
        }
        table.list tbody tr:nth-child(even) td { background: #f5faf8; }
        .marks { font-weight: 800; color: #0f5c4d; }
        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 700;
        }
        .pill.yes { background: #d9f2e8; color: #0f5c4d; }
        .pill.no { background: #fde8e8; color: #a33; }
        .pill.present { background: #d9f2e8; color: #0f5c4d; }
        .pill.absent { background: #fde8e8; color: #a33; }
        .empty {
            text-align: center;
            color: #78909c;
            padding: 16px !important;
        }
        .foot {
            margin-top: 14px;
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
    $instituteName = $student->institute?->name ?? config('app.name');
@endphp

<div class="banner">
    <h1>Student Results Report</h1>
    <div class="sub">{{ $instituteName }} · {{ $student->name }}</div>
</div>

<table class="meta">
    <tr>
        <td><span class="label">Institute :</span> {{ $instituteName }}</td>
        <td><span class="label">Branch :</span> {{ $student->branch?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td><span class="label">Student :</span> {{ $student->name }}</td>
        <td><span class="label">Admission :</span> {{ $student->admission_no ?? '—' }}</td>
    </tr>
    <tr>
        <td><span class="label">Standard :</span> {{ $student->standard?->name ?? '—' }}</td>
        <td><span class="label">Exported On :</span> {{ now()->format('m/d/Y, h:i:s A') }}</td>
    </tr>
</table>

<table class="list">
    <thead>
        <tr>
            <th style="width: 32px;">No</th>
            <th>Batch</th>
            <th>Subject</th>
            <th style="width: 58px;">Max</th>
            <th style="width: 58px;">Obtain</th>
            <th>Remarks</th>
            <th style="width: 52px;">Attempt</th>
            <th style="width: 58px;">Attend</th>
            <th style="width: 72px;">Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($results as $announcement)
            @php
                $exam = $announcement->exam;
                $result = $announcement->studentResult;
                $max = (float) ($exam?->total_marks ?? 0);
                $marks = $result ? (float) $result->marks_obtained : null;
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $exam?->batch?->name ?? '—' }}</td>
                <td>{{ $exam?->subject?->name ?? '—' }}</td>
                <td>{{ $max > 0 ? rtrim(rtrim(number_format($max, 2), '0'), '.') : '—' }}</td>
                <td class="marks">{{ $marks !== null ? rtrim(rtrim(number_format($marks, 2), '0'), '.') : '—' }}</td>
                <td>{{ $result?->remark ?: '—' }}</td>
                <td>
                    @if ($result === null)
                        —
                    @elseif ($result->is_attempted)
                        <span class="pill yes">Yes</span>
                    @else
                        <span class="pill no">No</span>
                    @endif
                </td>
                <td>
                    @if ($result?->attendance_status)
                        <span class="pill {{ $result->attendance_status === 'absent' ? 'absent' : 'present' }}">
                            {{ ucfirst($result->attendance_status) }}
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td>{{ ($announcement->result_date ?? $announcement->published_at)?->format('d M Y') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="empty">No result announcements found for this student.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="foot">
    <div class="left">Generated by {{ config('app.name') }}</div>
    <div class="right">{{ $student->name }} · Results</div>
    <div class="clear"></div>
</div>
</body>
</html>
