<x-dashboard.stat-card label="Fees collected" :value="$money($data['this_month'])" icon="bx-rupee"
    tone="success" :href="route('admin.fees', $branchParams)" with-change
    :change="$data['change']" change-label="vs same days last month">
    <div class="ivd-split w-100 mt-1">
        <span class="ivd-kpi-inline">Today <strong>{{ $money($data['today']) }}</strong></span>
    </div>
</x-dashboard.stat-card>
