<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fees Follow Up Report</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 8mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
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
            font-size: 9px;
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
            min-width: 88px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .summary td {
            width: 33.33%;
            padding: 8px 10px;
            text-align: center;
            border: 1px solid #d7e4e0;
            background: #f4faf8;
        }
        .summary .num {
            display: block;
            font-size: 14px;
            font-weight: 800;
            color: #0f5c4d;
            margin-bottom: 2px;
        }
        .summary .lbl {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #5c6b68;
        }
        .summary .due .num { color: #c45c2a; }

        table.list {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.list thead { display: table-header-group; }
        table.list tr { page-break-inside: avoid; }
        table.list thead th {
            background: #0f5c4d;
            color: #fff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 7px 5px;
            text-align: left;
            border: 1px solid #0c4a3e;
        }
        table.list tbody td {
            padding: 6px 5px;
            border: 1px solid #d7e4e0;
            font-size: 9px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        table.list tbody tr:nth-child(even) td { background: #f5faf8; }
        .amt {
            font-weight: 800;
            color: #c45c2a;
            white-space: nowrap;
        }
        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 7.5px;
            font-weight: 700;
        }
        .pill.pending { background: #fff3cd; color: #856404; }
        .pill.complete { background: #d9f2e8; color: #0f5c4d; }
        .empty {
            text-align: center;
            color: #78909c;
            padding: 16px !important;
        }
        .foot {
            margin-top: 12px;
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
    $totalDue = $invoices->sum(fn ($inv) => (float) $inv->due_amount);
    $pendingCount = $invoices->filter(fn ($inv) => ($inv->latestFollowUp?->status ?? 'pending') === 'pending')->count();
    $completeCount = $invoices->count() - $pendingCount;
    $dateRange = trim(($filters['start_date'] ?? '') . (($filters['start_date'] ?? $filters['end_date'] ?? null) ? ' → ' : '') . ($filters['end_date'] ?? ''), ' →');
    if ($dateRange === '') {
        $dateRange = 'All dates';
    }
@endphp

<div class="banner">
    <h1>Fees Follow Up Report</h1>
    <div class="sub">{{ $instituteName }} · Outstanding dues</div>
</div>

<table class="meta">
    <tr>
        <td><span class="label">Institute :</span> {{ $instituteName }}</td>
        <td><span class="label">Branch :</span> {{ $branchName ?? 'All Branches' }}</td>
    </tr>
    <tr>
        <td><span class="label">Batches :</span> {{ $batchLabel ?? 'All Batches' }}</td>
        <td><span class="label">Date Range :</span> {{ $dateRange }}</td>
    </tr>
    <tr>
        <td><span class="label">Records :</span> {{ $invoices->count() }}</td>
        <td><span class="label">Exported On :</span> {{ now()->format('m/d/Y, h:i:s A') }}</td>
    </tr>
</table>

<table class="summary">
    <tr>
        <td><span class="num">{{ $invoices->count() }}</span><span class="lbl">Total Records</span></td>
        <td class="due"><span class="num">Rs. {{ number_format($totalDue, 2) }}</span><span class="lbl">Total Due Amount</span></td>
        <td><span class="num">{{ $pendingCount }} / {{ $completeCount }}</span><span class="lbl">Pending / Complete</span></td>
    </tr>
</table>

<table class="list">
    <thead>
        <tr>
            <th style="width: 28px;">Sr.</th>
            <th style="width: 85px;">Branch</th>
            <th style="width: 110px;">Student</th>
            <th style="width: 90px;">Father</th>
            <th style="width: 75px;">Contact</th>
            <th style="width: 100px;">Batch</th>
            <th style="width: 75px;">Due Amount</th>
            <th style="width: 70px;">Next Follow Up</th>
            <th style="width: 60px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($invoices as $invoice)
            @php
                $student = $invoice->student;
                $followUp = $invoice->latestFollowUp;
                $isPending = ($followUp?->status ?? 'pending') === 'pending';
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $invoice->branch?->name ?? '—' }}</td>
                <td><strong>{{ $student?->name ?? '—' }}</strong></td>
                <td>{{ $student?->parent?->father_name ?? '—' }}</td>
                <td>{{ $student?->parent?->phone ?? '—' }}</td>
                <td>{{ $invoice->enrollment?->batch?->name ?? '—' }}</td>
                <td class="amt">Rs. {{ number_format((float) $invoice->due_amount, 2) }}</td>
                <td>{{ $followUp?->next_follow_up_at?->format('d M Y') ?? '—' }}</td>
                <td>
                    <span class="pill {{ $isPending ? 'pending' : 'complete' }}">
                        {{ $isPending ? 'Pending' : 'Complete' }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="empty">No records for selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="foot">
    <div class="left">Generated by {{ config('app.name') }}</div>
    <div class="right">{{ $instituteName }} · Fees Follow Up</div>
    <div class="clear"></div>
</div>
</body>
</html>
