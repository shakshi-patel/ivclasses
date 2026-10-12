<x-dashboard.panel title="Today's call list" subtitle="Leads to call back today or overdue" icon="bx-phone-call"
    :action-url="$can['follow_up'] ? route('admin.follow_up', $branchParams) : route('admin.inquiry', $branchParams)">
    @forelse ($dashboard->followUpsToday() as $inquiry)
        @if ($loop->first)<ul class="ivd-list">@endif
        @php($overdue = $inquiry->next_follow_up_at?->lt(today()))
        <li class="ivd-list__item">
            <x-dashboard.avatar :name="$inquiry->student_name" />
            <div class="ivd-list__main">
                <span class="ivd-list__title">{{ $inquiry->student_name }}</span>
                <span class="ivd-list__meta">
                    {{ $inquiry->standard?->name ?? '—' }}
                    @if ($inquiry->source) · {{ \Illuminate\Support\Str::headline($inquiry->source) }} @endif
                </span>
            </div>
            <div class="ivd-list__end">
                @if ($inquiry->phone)
                    <a href="tel:{{ $inquiry->phone }}" class="ivd-link"><i class="bx bx-phone"></i> {{ $inquiry->phone }}</a>
                @endif
                <span class="ivd-pill {{ $overdue ? 'ivd-tone-error' : 'ivd-tone-warning' }}">
                    {{ $overdue ? 'Overdue · ' . $inquiry->next_follow_up_at->format('d M') : 'Today' }}
                </span>
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <div class="ivd-empty"><i class="bx bx-check-double"></i>No calls pending. Nice work!</div>
    @endforelse
</x-dashboard.panel>
