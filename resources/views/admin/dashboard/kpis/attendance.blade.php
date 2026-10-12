<x-dashboard.stat-card label="Student attendance" :value="$data['rate'] === null ? 'Not marked' : $data['rate'] . '%'"
    icon="bx-calendar-check" tone="info" :href="route('admin.attendance.student', $branchParams)">
    <div class="w-100 ivd-tone-{{ $rateTone($data['rate']) }}">
        <div class="ivd-progress mb-2">
            <span style="width: {{ $data['batches_total'] ? round($data['batches_marked'] * 100 / $data['batches_total']) : 0 }}%"></span>
        </div>
        <div class="ivd-split">
            <span class="ivd-stat__meta">{{ $data['batches_marked'] }} of {{ $data['batches_total'] }} batches marked today</span>
            @if ($data['marked'] > 0)
                <span class="ivd-pill ivd-tone-error">{{ $data['absent'] }} absent</span>
            @endif
        </div>
    </div>
</x-dashboard.stat-card>
