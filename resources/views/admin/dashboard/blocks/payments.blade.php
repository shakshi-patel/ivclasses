<x-dashboard.panel title="Recent payments" subtitle="Latest fee receipts" icon="bx-receipt"
    :action-url="route('admin.fees', $branchParams)">
    @forelse ($dashboard->recentPayments() as $payment)
        @if ($loop->first)<ul class="ivd-list">@endif
        <li class="ivd-list__item">
            <x-dashboard.avatar :name="$payment->student?->name" :src="$studentPhoto($payment->student)" />
            <div class="ivd-list__main">
                <span class="ivd-list__title">{{ $payment->student?->name ?? 'Unknown student' }}</span>
                <span class="ivd-list__meta">
                    {{ $payment->receipt_no ?: '#' . $payment->id }}
                    · {{ \Illuminate\Support\Str::headline($payment->payment_method ?: 'cash') }}
                    · {{ $payment->payment_date?->format('d M') }}
                </span>
            </div>
            <div class="ivd-list__end">
                <span class="ivd-list__amount">{{ $money($payment->amount) }}</span>
                @if ($payment->payment_date?->isToday())
                    <span class="ivd-pill ivd-tone-success">Today</span>
                @endif
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <div class="ivd-empty"><i class="bx bx-receipt"></i>No payments recorded yet.</div>
    @endforelse
</x-dashboard.panel>
