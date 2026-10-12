<x-dashboard.stat-card label="Pending dues" :value="$money($data['amount'])" icon="bx-wallet" tone="warning"
    :href="$can['fee_follow_up'] ? route('admin.fees.follow_up', $branchParams) : route('admin.fees', $branchParams)">
    <span class="ivd-pill ivd-tone-muted">{{ number_format($data['students']) }} {{ \Illuminate\Support\Str::plural('student', $data['students']) }}</span>
    @if ($data['overdue'] > 0)
        <span class="ivd-pill ivd-tone-error">{{ $data['overdue'] }} overdue</span>
    @else
        <span class="ivd-pill ivd-tone-success">Nothing overdue</span>
    @endif
</x-dashboard.stat-card>
