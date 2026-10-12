<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Details Report</title>
    <style>
        @page { margin: 14mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2a28;
            margin: 0;
            padding: 0;
        }
        .banner {
            background: #0f5c4d;
            color: #fff;
            text-align: center;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 14px;
        }
        .banner h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .banner .sub {
            margin-top: 4px;
            font-size: 10px;
            opacity: 0.9;
            color: #f0d78a;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .meta td {
            vertical-align: top;
            padding: 3px 0;
            font-size: 10.5px;
            width: 50%;
        }
        .meta .label {
            color: #0f5c4d;
            font-weight: 700;
            display: inline-block;
            min-width: 78px;
        }
        .meta .value { color: #243230; }
        table.list {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.list thead th {
            background: #0f5c4d;
            color: #fff;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #0c4a3e;
        }
        table.list tbody td {
            padding: 7px 6px;
            border: 1px solid #d7e4e0;
            font-size: 10px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        table.list tbody tr:nth-child(even) td {
            background: #f4faf8;
        }
        .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 700;
        }
        .status.active {
            background: #d9f2e8;
            color: #0f5c4d;
        }
        .status.other {
            background: #eceff1;
            color: #546e7a;
        }
        .foot {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 2px solid #d9a441;
            font-size: 9px;
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
@endphp

<div class="banner">
    <h1>Student Details Report</h1>
    <div class="sub">{{ $instituteName }}</div>
</div>

<table class="meta">
    <tr>
        <td><span class="label">Institute :</span> <span class="value">{{ $instituteName }}</span></td>
        <td><span class="label">Branch :</span> <span class="value">{{ $batch->branch?->name ?? '—' }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Batch :</span> <span class="value">{{ $batch->name }}</span></td>
        <td><span class="label">Standard :</span> <span class="value">{{ $batch->standard?->name ?? '—' }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Students :</span> <span class="value">{{ $students->count() }}</span></td>
        <td><span class="label">Exported On :</span> <span class="value">{{ now()->format('m/d/Y, h:i:s A') }}</span></td>
    </tr>
</table>

<table class="list">
    <thead>
        <tr>
            <th style="width: 36px;">Sr.</th>
            <th style="width: 48px;">Roll</th>
            <th>Student</th>
            <th style="width: 90px;">Admission No.</th>
            <th>Father</th>
            <th style="width: 90px;">Contact</th>
            <th style="width: 70px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($students as $enrollment)
            @php $stu = $enrollment->student; @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $enrollment->roll_no ?: '—' }}</strong></td>
                <td><strong>{{ $stu?->name ?? '—' }}</strong></td>
                <td>{{ $stu?->admission_no ?? '—' }}</td>
                <td>{{ $stu?->parent?->father_name ?? '—' }}</td>
                <td>{{ $stu?->parent?->phone ?? '—' }}</td>
                <td>
                    @if (($stu?->status ?? $enrollment->status) === 'active')
                        <span class="status active">Active</span>
                    @else
                        <span class="status other">{{ ucfirst($stu?->status ?? $enrollment->status ?? '—') }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center;color:#78909c;padding:18px;">No students enrolled in this batch.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="foot">
    <div class="left">Generated by {{ config('app.name') }}</div>
    <div class="right">{{ $instituteName }}</div>
    <div class="clear"></div>
</div>
</body>
</html>
