<x-dashboard.panel title="Branch comparison"
    :subtitle="$can['fees'] ? 'Active students and fees collected this month' : 'Active students per branch'"
    icon="bx-git-compare">
    <x-dashboard.chart widget="branches" :height="300" />
</x-dashboard.panel>
