<x-dashboard.panel title="Payment methods" subtitle="This month's collections by mode" icon="bx-credit-card"
    :action-url="route('admin.fees', $branchParams)">
    <x-dashboard.chart widget="payment_methods" :height="300" :params="$chartParams" />
</x-dashboard.panel>
