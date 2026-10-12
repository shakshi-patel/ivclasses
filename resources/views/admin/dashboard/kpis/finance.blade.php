<x-dashboard.stat-card label="Net this month" :value="$money($data['net'])" icon="bx-transfer-alt"
    :tone="$data['net'] < 0 ? 'error' : 'info'" :href="route('admin.income_expense', $branchParams)">
    <div class="ivd-split w-100 mt-1">
        <span class="ivd-pill ivd-tone-success">In {{ $money($data['income']) }}</span>
        <span class="ivd-pill ivd-tone-error">Out {{ $money($data['expense']) }}</span>
    </div>
</x-dashboard.stat-card>
