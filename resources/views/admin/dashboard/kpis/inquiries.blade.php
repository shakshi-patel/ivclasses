<x-dashboard.stat-card label="Inquiries this month" :value="number_format($data['this_month'])"
    icon="bx-user-voice" tone="secondary" :href="route('admin.inquiry', $branchParams)"
    with-change :change="$data['change']" change-label="vs same days last month">
    <div class="ivd-split w-100 mt-1">
        @if ($data['follow_ups_due'] > 0)
            <span class="ivd-pill ivd-tone-warning">{{ $data['follow_ups_due'] }} follow-ups due</span>
        @endif
        <span class="ivd-pill ivd-tone-success">{{ $data['conversion_rate'] }}% converted</span>
    </div>
</x-dashboard.stat-card>
