<x-dashboard.panel title="Batch-wise attendance" subtitle="Average present · last 30 days" icon="bx-bar-chart-alt-2">
    <x-slot:tools>
        <span class="ivd-kpi-inline">Overall <strong data-ivd-total="batch_attendance_rate">—</strong></span>
    </x-slot:tools>
    <x-dashboard.chart widget="batch_attendance" :height="300" :params="$chartParams" />
</x-dashboard.panel>
