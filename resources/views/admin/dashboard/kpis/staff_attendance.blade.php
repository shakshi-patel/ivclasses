<x-dashboard.stat-card label="Staff attendance" :value="$data['rate'] === null ? 'Not marked' : $data['rate'] . '%'"
    icon="bx-id-card" tone="primary" :href="route('admin.attendance.teacher', $branchParams)">
    <div class="w-100 ivd-tone-{{ $rateTone($data['rate']) }}">
        <div class="ivd-progress mb-2">
            <span style="width: {{ $data['total'] ? round($data['marked'] * 100 / $data['total']) : 0 }}%"></span>
        </div>
        <div class="ivd-split">
            <span class="ivd-stat__meta">{{ $data['marked'] }} of {{ $data['total'] }} marked today</span>
            @if ($data['absent'] > 0)
                <span class="ivd-pill ivd-tone-error">{{ $data['absent'] }} absent</span>
            @endif
            @if ($data['late'] > 0)
                <span class="ivd-pill ivd-tone-warning">{{ $data['late'] }} late</span>
            @endif
        </div>
    </div>
</x-dashboard.stat-card>
