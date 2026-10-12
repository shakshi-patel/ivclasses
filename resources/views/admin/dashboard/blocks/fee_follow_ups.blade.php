<x-dashboard.panel title="Fee follow-ups" subtitle="Reminders due today or overdue" icon="bx-phone-outgoing"
    :action-url="route('admin.fees.follow_up', array_merge($branchParams, ['status' => 'pending']))">
    @forelse ($dashboard->feeFollowUpsDue() as $invoice)
        @if ($loop->first)<ul class="ivd-list">@endif
        @php($next = $invoice->latestFollowUp?->next_follow_up_at)
        @php($overdue = $next?->lt(today()))
        <li class="ivd-list__item">
            <x-dashboard.avatar :name="$invoice->student?->name" :src="$studentPhoto($invoice->student)" />
            <div class="ivd-list__main">
                <span class="ivd-list__title">{{ $invoice->student?->name ?? 'Unknown student' }}</span>
                <span class="ivd-list__meta">
                    {{ $invoice->title ?: 'Invoice #' . $invoice->id }}
                    @if ($invoice->student?->parent?->phone) · {{ $invoice->student->parent->phone }} @endif
                </span>
            </div>
            <div class="ivd-list__end">
                <span class="ivd-list__amount">{{ $money($invoice->due_amount) }}</span>
                <span class="ivd-pill {{ $overdue ? 'ivd-tone-error' : 'ivd-tone-warning' }}">
                    {{ $overdue ? 'Since ' . $next->format('d M') : 'Today' }}
                </span>
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <div class="ivd-empty"><i class="bx bx-check-double"></i>No fee reminders due.</div>
    @endforelse
</x-dashboard.panel>
