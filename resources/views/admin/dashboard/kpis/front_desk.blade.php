<x-dashboard.stat-card label="New leads today" :value="number_format($data['new_today'])" icon="bx-door-open"
    tone="primary" :href="route('admin.inquiry', $branchParams)">
    <div class="ivd-split w-100 mt-1">
        <span class="ivd-pill {{ $data['follow_ups_due'] > 0 ? 'ivd-tone-warning' : 'ivd-tone-success' }}">
            {{ $data['follow_ups_due'] }} calls due
        </span>
        @if ($data['admissions_today'] !== null)
            <span class="ivd-pill ivd-tone-info">{{ $data['admissions_today'] }} admitted today</span>
        @endif
        <span class="ivd-pill ivd-tone-success">{{ $data['converted_this_month'] }} converted this month</span>
    </div>
</x-dashboard.stat-card>
