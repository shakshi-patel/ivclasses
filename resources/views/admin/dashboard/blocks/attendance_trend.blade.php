<x-dashboard.panel title="Attendance trend" subtitle="Student attendance · last 14 days"
    icon="bx-calendar-check" :action-url="route('admin.attendance.student', $branchParams)">
    <x-slot:tools>
        <span class="ivd-kpi-inline">Avg. present <strong data-ivd-total="attendance_rate">—</strong></span>
    </x-slot:tools>
    <x-dashboard.chart widget="attendance_trend" :height="300" :params="$chartParams" />
</x-dashboard.panel>
