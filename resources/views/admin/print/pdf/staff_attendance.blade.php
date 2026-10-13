<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Staff Attendance Report</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2a28; margin: 0; padding: 0; }
        .banner { background: #0f5c4d; color: #fff; text-align: center; padding: 13px 14px; border-radius: 8px; margin-bottom: 12px; }
        .banner h1 { margin: 0; font-size: 17px; font-weight: 700; letter-spacing: 0.3px; }
        .banner .sub { margin-top: 3px; font-size: 9.5px; color: #f0d78a; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { vertical-align: top; padding: 2px 0; font-size: 10px; width: 50%; }
        .meta .label { color: #0f5c4d; font-weight: 700; display: inline-block; min-width: 78px; }
        .stats { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .stats td { width: 25%; padding: 8px 10px; text-align: center; border: 1px solid #d7e4e0; background: #f4faf8; }
        .stats .num { display: block; font-size: 16px; font-weight: 800; margin-bottom: 2px; }
        .stats .lbl { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.04em; color: #5c6b68; }
        .stats .is-p .num { color: #2f9e6a; }
        .stats .is-a .num { color: #d64545; }
        .stats .is-lp .num { color: #c48a1a; }
        .stats .is-lv .num { color: #7c4fc0; }
        table.cal { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 12px; }
        table.cal th { background: #0f5c4d; color: #fff; font-size: 8.5px; font-weight: 700; padding: 7px 2px; text-align: center; border: 1px solid #0c4a3e; text-transform: uppercase; }
        table.cal td { border: 1px solid #d5e3df; height: 52px; vertical-align: top; padding: 4px; background: #fff; }
        table.cal td.is-sun { background: #f3f4f4; }
        table.cal td.is-empty { background: #fafbfb; }
        .day-num { font-size: 8px; font-weight: 700; color: #5c6b68; margin-bottom: 4px; }
        .badge { display: inline-block; min-width: 22px; padding: 2px 5px; border-radius: 4px; font-size: 8px; font-weight: 700; color: #fff; text-align: center; }
        .badge.is-p { background: #2f9e6a; }
        .badge.is-a { background: #d64545; }
        .badge.is-lp { background: #e09b2d; color: #2c2208; }
        .badge.is-lv { background: #9b6bd4; }
        .badge.is-h { background: #5b7fc4; }
        .badge.is-s { background: #7a8584; }
        .dash { color: #b0bec5; font-size: 10px; }
        .list { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .list thead th { background: #0f5c4d; color: #fff; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.03em; padding: 7px 6px; text-align: left; border: 1px solid #0c4a3e; }
        .list tbody td { padding: 6px; border: 1px solid #d7e4e0; font-size: 9.5px; }
        .list tbody tr:nth-child(even) td { background: #f5faf8; }
        .legend { padding: 8px 10px; background: #f4faf8; border: 1px solid #d7e4e0; border-radius: 6px; border-left: 4px solid #d9a441; margin-bottom: 10px; }
        .legend-title { font-size: 9px; font-weight: 700; color: #0f5c4d; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.04em; }
        .legend-item { display: inline-block; margin-right: 12px; font-size: 8.5px; color: #374745; }
        .legend-item .badge { margin-right: 3px; }
        .foot { margin-top: 8px; padding-top: 6px; border-top: 2px solid #d9a441; font-size: 8.5px; color: #5c6b68; }
        .foot .left { float: left; }
        .foot .right { float: right; color: #0f5c4d; font-weight: 700; }
        .clear { clear: both; }
    </style>
</head>
<body>
@php
    $staffName = $staff->user?->name ?? 'Staff';
    $instituteName = $staff->institute?->name ?? config('app.name');
    $monthTitle = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth = (int) ($calendar['days_in_month'] ?? 0);
    $holidays = $calendar['holidays'] ?? [];
    $days = $calendar['days'] ?? [];
    $summary = $calendar['summary'] ?? [];
    $firstWeekday = (int) date('w', mktime(0, 0, 0, $month, 1, $year));
    $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $sundays = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        if ((int) date('w', mktime(0, 0, 0, $month, $d, $year)) === 0) {
            $sundays[] = $d;
        }
    }
    $sundays = collect($sundays);

    $statusBadge = function (?string $cls, int $day) use ($sundays, $holidays): array {
        if ($cls === 'is-present') {
            return ['class' => 'is-p', 'label' => 'P'];
        }
        if ($cls === 'is-absent') {
            return ['class' => 'is-a', 'label' => 'A'];
        }
        if ($cls === 'is-late') {
            return ['class' => 'is-lp', 'label' => 'LP'];
        }
        if ($cls === 'is-leave') {
            return ['class' => 'is-lv', 'label' => 'LV'];
        }
        if ($cls === 'is-holiday' || isset($holidays[$day])) {
            return ['class' => 'is-h', 'label' => 'H'];
        }
        if ($sundays->contains($day)) {
            return ['class' => 'is-s', 'label' => 'S'];
        }

        return ['class' => '', 'label' => ''];
    };
@endphp

<div class="banner">
    <h1>Staff Attendance Report</h1>
    <div class="sub">{{ $instituteName }} · {{ $monthTitle }}</div>
</div>

<table class="meta">
    <tr>
        <td><span class="label">Institute :</span> {{ $instituteName }}</td>
        <td><span class="label">Month :</span> {{ $monthTitle }}</td>
    </tr>
    <tr>
        <td><span class="label">Staff :</span> {{ $staffName }}</td>
        <td><span class="label">Code :</span> {{ $staff->employee_code ?? '—' }}</td>
    </tr>
    <tr>
        <td><span class="label">Branch :</span> {{ $branchName ?? '—' }}</td>
        <td><span class="label">Exported On :</span> {{ now()->format('m/d/Y, h:i:s A') }}</td>
    </tr>
</table>

<table class="stats">
    <tr>
        <td class="is-p"><span class="num">{{ (int) ($summary['present'] ?? 0) }}</span><span class="lbl">Present</span></td>
        <td class="is-a"><span class="num">{{ (int) ($summary['absent'] ?? 0) }}</span><span class="lbl">Absent</span></td>
        <td class="is-lp"><span class="num">{{ (int) ($summary['late'] ?? 0) }}</span><span class="lbl">Late</span></td>
        <td class="is-lv"><span class="num">{{ (int) ($summary['leave'] ?? 0) }}</span><span class="lbl">Leave</span></td>
    </tr>
</table>

<table class="cal">
    <thead>
        <tr>
            @foreach ($weekdays as $wd)
                <th>{{ $wd }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php $day = 1; @endphp
        @while ($day <= $daysInMonth)
            <tr>
                @for ($col = 0; $col < 7; $col++)
                    @if (($day === 1 && $col < $firstWeekday) || $day > $daysInMonth)
                        <td class="is-empty"></td>
                    @else
                        @php
                            $cls = $days[$day] ?? '';
                            $badge = $statusBadge(is_string($cls) ? $cls : null, $day);
                            $isSun = $sundays->contains($day);
                        @endphp
                        <td class="{{ $isSun ? 'is-sun' : '' }}">
                            <div class="day-num">{{ sprintf('%02d', $day) }}</div>
                            @if ($badge['label'] !== '')
                                <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            @else
                                <span class="dash">-</span>
                            @endif
                        </td>
                        @php $day++; @endphp
                    @endif
                @endfor
            </tr>
        @endwhile
    </tbody>
</table>

<table class="list">
    <thead>
        <tr>
            <th style="width: 90px;">Date</th>
            <th style="width: 70px;">Day</th>
            <th style="width: 90px;">Status</th>
            <th>Holiday / Note</th>
        </tr>
    </thead>
    <tbody>
        @for ($d = 1; $d <= $daysInMonth; $d++)
            @php
                $cls = $days[$d] ?? '';
                $badge = $statusBadge(is_string($cls) ? $cls : null, $d);
                $holidayTitle = $holidays[$d]['title'] ?? '';
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $dayName = date('D', strtotime($dateStr));
            @endphp
            <tr>
                <td>{{ date('d M Y', strtotime($dateStr)) }}</td>
                <td>{{ $dayName }}</td>
                <td>
                    @if ($badge['label'] !== '')
                        <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                    @else
                        <span class="dash">-</span>
                    @endif
                </td>
                <td>{{ $holidayTitle ?: '—' }}</td>
            </tr>
        @endfor
    </tbody>
</table>

<div class="legend">
    <div class="legend-title">Legend</div>
    <span class="legend-item"><span class="badge is-p">P</span> Present</span>
    <span class="legend-item"><span class="badge is-lp">LP</span> Late</span>
    <span class="legend-item"><span class="badge is-a">A</span> Absent</span>
    <span class="legend-item"><span class="badge is-lv">LV</span> Leave</span>
    <span class="legend-item"><span class="badge is-s">S</span> Sunday</span>
    <span class="legend-item"><span class="badge is-h">H</span> Holiday</span>
</div>

<div class="foot">
    <div class="left">Generated by {{ config('app.name') }}</div>
    <div class="right">{{ $staffName }} · {{ $monthTitle }}</div>
    <div class="clear"></div>
</div>
</body>
</html>
