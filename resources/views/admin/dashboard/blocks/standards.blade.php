<x-dashboard.panel title="Students by standard" subtitle="Active students per class"
    icon="bx-bar-chart-square" :action-url="route('admin.student', $branchParams)">
    <x-dashboard.chart widget="standards" :height="290" :params="$chartParams" />
</x-dashboard.panel>
