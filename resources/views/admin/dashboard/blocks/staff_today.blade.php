@php
    $staffStates = [
        'present' => ['success', 'Present'],
        'late' => ['warning', 'Late'],
        'absent' => ['error', 'Absent'],
        'unmarked' => ['muted', 'Not marked'],
    ];
@endphp
<x-dashboard.panel title="Staff today" subtitle="Teachers & staff attendance" icon="bx-id-card"
    :action-url="route('admin.attendance.teacher', $branchParams)" action-label="Manage">
    @forelse ($dashboard->staffToday() as $person)
        @if ($loop->first)<ul class="ivd-list">@endif
        @php
            [$tone, $label] = $staffStates[$person['state']] ?? ['muted', \Illuminate\Support\Str::headline($person['state'])];
        @endphp
        <li class="ivd-list__item">
            <x-dashboard.avatar :name="$person['name']" />
            <div class="ivd-list__main">
                <span class="ivd-list__title">{{ $person['name'] }}</span>
                <span class="ivd-list__meta">
                    {{ $person['role'] }}
                    @if ($dashboard->isMultiBranchView() && $person['branch_name'] !== '—') · {{ $person['branch_name'] }} @endif
                </span>
            </div>
            <div class="ivd-list__end">
                <span class="ivd-pill ivd-tone-{{ $tone }}">{{ $label }}</span>
                @if ($person['check_in_at'])
                    <span class="ivd-list__meta">
                        In {{ $person['check_in_at'] }}
                        @if ($person['late_minutes'])
                            · {{ $person['late_minutes'] }}m late
                        @endif
                    </span>
                @endif
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <div class="ivd-empty"><i class="bx bx-id-card"></i>No active teachers or staff yet.</div>
    @endforelse
</x-dashboard.panel>
