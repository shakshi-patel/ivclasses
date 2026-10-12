<x-dashboard.panel title="Batches today" subtitle="Attendance status per batch" icon="bx-group"
    :action-url="route('admin.attendance.student', $branchParams)">
    @forelse ($dashboard->batchesToday() as $batch)
        @if ($loop->first)<ul class="ivd-list">@endif
        <li class="ivd-list__item">
            <span class="ivd-time {{ $batch->is_marked ? 'ivd-tone-success' : 'ivd-tone-warning' }}">
                <i class="bx {{ $batch->is_marked ? 'bx-check' : 'bx-time-five' }}"></i>
            </span>
            <div class="ivd-list__main">
                <span class="ivd-list__title">{{ $batch->name }}</span>
                <span class="ivd-list__meta">
                    {{ $batch->timingLabel() }}
                    @if ($batch->standard) · {{ $batch->standard->name }} @endif
                    · {{ $batch->students_count }} {{ \Illuminate\Support\Str::plural('student', (int) $batch->students_count) }}
                </span>
            </div>
            <div class="ivd-list__end">
                @if ($batch->is_marked)
                    <span class="ivd-pill ivd-tone-success">{{ $batch->present_count }} present</span>
                    @if ($batch->absent_count > 0)
                        <span class="ivd-list__meta">{{ $batch->absent_count }} absent</span>
                    @endif
                @elseif ($can['attendance_add'])
                    <a href="{{ route('admin.attendance.student.mark', $batch) }}" class="ivd-chip-btn">Mark now</a>
                @else
                    <span class="ivd-pill ivd-tone-warning">Pending</span>
                @endif
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <div class="ivd-empty"><i class="bx bx-group"></i>No active batches.</div>
    @endforelse
</x-dashboard.panel>
