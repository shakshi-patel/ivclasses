<x-dashboard.panel title="Highest dues" subtitle="Students with the largest balance" icon="bx-wallet"
    :action-url="$can['fee_follow_up'] ? route('admin.fees.follow_up', $branchParams) : route('admin.fees', $branchParams)">
    @forelse ($dashboard->topDues() as $row)
        @if ($loop->first)<ul class="ivd-list">@endif
        @php($overdue = $row->oldest_due && \Carbon\Carbon::parse($row->oldest_due)->isPast())
        <li class="ivd-list__item">
            <x-dashboard.avatar :name="$row->student?->name" :src="$studentPhoto($row->student)" />
            <div class="ivd-list__main">
                @if ($row->student && $can['student_show'])
                    <a href="{{ route('admin.student.show', $row->student) }}" class="ivd-list__title">{{ $row->student->name }}</a>
                @else
                    <span class="ivd-list__title">{{ $row->student?->name ?? 'Unknown student' }}</span>
                @endif
                <span class="ivd-list__meta">
                    {{ $row->student?->standard?->name ?? '—' }}
                    · {{ $row->invoice_count }} {{ \Illuminate\Support\Str::plural('invoice', (int) $row->invoice_count) }}
                </span>
            </div>
            <div class="ivd-list__end">
                <span class="ivd-list__amount">{{ $money($row->due_total) }}</span>
                @if ($overdue)
                    <span class="ivd-pill ivd-tone-error">Overdue</span>
                @endif
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <div class="ivd-empty"><i class="bx bx-happy-alt"></i>No pending dues. Great job!</div>
    @endforelse
</x-dashboard.panel>
