<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Result Announcement Report</title>
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
            min-width: 90px;
        }
        .section-title {
            margin: 14px 0 6px;
            padding: 6px 10px;
            background: linear-gradient(90deg, #0f5c4d 0%, #1a7a66 100%);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            border-radius: 5px;
        }
        .section-title.absent {
            background: linear-gradient(90deg, #8b3a3a 0%, #c45c5c 100%);
        }
        table.list {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.list thead th {
            background: #0f5c4d;
            color: #fff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 7px 6px;
            text-align: left;
            border: 1px solid #0c4a3e;
        }
        table.list.absent thead th {
            background: #8b3a3a;
            border-color: #6e2e2e;
        }
        table.list tbody td {
            padding: 6px;
            border: 1px solid #d7e4e0;
            font-size: 9.5px;
            vertical-align: middle;
        }
        table.list tbody tr:nth-child(even) td { background: #f5faf8; }
        table.list.absent tbody tr:nth-child(even) td { background: #faf5f5; }
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
        .marks {
            font-weight: 800;
            color: #0f5c4d;
        }
        .empty {
            text-align: center;
            color: #78909c;
            padding: 14px !important;
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
    $instituteName = $exam->institute?->name
        ?? $exam->batch?->institute?->name
        ?? $announcement->institute?->name
        ?? config('app.name');
    $resultDate = ($announcement->result_date ?? $announcement->published_at)?->format('d M Y') ?? '—';
@endphp

<div class="banner">
    <h1>Result Announcement Report</h1>
    <div class="sub">{{ $instituteName }} · {{ $exam->title ?? $announcement->title }}</div>
</div>

<table class="meta">
    <tr>
        <td><span class="label">Institute :</span> {{ $instituteName }}</td>
        <td><span class="label">Result Date :</span> {{ $resultDate }}</td>
    </tr>
    <tr>
        <td><span class="label">Exam :</span> {{ $exam->title ?? '—' }}</td>
        <td><span class="label">Subject :</span> {{ $exam->subject?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td><span class="label">Batch :</span> {{ $exam->batch?->name ?? '—' }}</td>
        <td><span class="label">Branch :</span> {{ $exam->branch?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td><span class="label">Total Marks :</span> {{ $exam->total_marks ?? '—' }}</td>
        <td><span class="label">Exported On :</span> {{ now()->format('m/d/Y, h:i:s A') }}</td>
    </tr>
</table>

<div class="section-title">Present Students ({{ $present->count() }})</div>
<table class="list">
    <thead>
        <tr>
            <th style="width: 40px;">Sr.</th>
            <th style="width: 55px;">Roll</th>
            <th>Student Name</th>
            <th style="width: 70px;">Marks</th>
            <th>Remark</th>
            <th style="width: 60px;">Attempt</th>
            <th style="width: 70px;">Attend</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($present as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $row['roll_no'] ?? '—' }}</strong></td>
                <td><strong>{{ $row['student']->name ?? '—' }}</strong></td>
                <td class="marks">{{ $row['result']?->marks_obtained ?? '—' }}</td>
                <td>{{ $row['result']?->remark ?: '—' }}</td>
                <td>
                    @if ($row['result'] === null)
                        —
                    @elseif ($row['result']->is_attempted)
                        <span class="pill yes">Yes</span>
                    @else
                        <span class="pill no">No</span>
                    @endif
                </td>
                <td>
                    @if ($row['result']?->attendance_status)
                        <span class="pill {{ $row['result']->attendance_status === 'absent' ? 'absent' : 'present' }}">
                            {{ ucfirst($row['result']->attendance_status) }}
                        </span>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No present students</td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title absent">Absent Students ({{ $absent->count() }})</div>
<table class="list absent">
    <thead>
        <tr>
            <th style="width: 40px;">Sr.</th>
            <th style="width: 55px;">Roll</th>
            <th>Student Name</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($absent as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $row['roll_no'] ?? '—' }}</strong></td>
                <td><strong>{{ $row['student']->name ?? '—' }}</strong></td>
                <td>{{ $row['result']?->remark ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty">No absent students</td></tr>
        @endforelse
    </tbody>
</table>

<div class="foot">
    <div class="left">Generated by {{ config('app.name') }}</div>
    <div class="right">{{ $exam->title ?? $announcement->title }}</div>
    <div class="clear"></div>
</div>
</body>
</html>
