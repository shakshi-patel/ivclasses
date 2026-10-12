<x-dashboard.stat-card label="Active students" :value="number_format($data['active'])" icon="bxs-graduation"
    tone="primary" :href="route('admin.student', $branchParams)" with-change
    :change="$data['change']" :change-label="$data['new_this_month'] . ' new admissions this month'">
    <div class="ivd-split w-100 mt-1">
        <span class="ivd-pill ivd-tone-muted">{{ number_format($data['total']) }} total</span>
        @if ($data['birthdays_today'] > 0)
            <span class="ivd-pill ivd-tone-secondary">{{ $data['birthdays_today'] }} birthdays today</span>
        @endif
    </div>
</x-dashboard.stat-card>
