<x-dashboard.panel title="Recent inquiries" subtitle="Latest admission leads" icon="bx-user-voice"
    :action-url="route('admin.inquiry', $branchParams)">
    @forelse ($dashboard->recentInquiries() as $inquiry)
        @if ($loop->first)<ul class="ivd-list">@endif
        <li class="ivd-list__item">
            <x-dashboard.avatar :name="$inquiry->student_name" />
            <div class="ivd-list__main">
                <span class="ivd-list__title">{{ $inquiry->student_name }}</span>
                <span class="ivd-list__meta">
                    {{ $inquiry->standard?->name ?? '—' }}
                    @if ($inquiry->source) · {{ \Illuminate\Support\Str::headline($inquiry->source) }} @endif
                    · {{ ($inquiry->inquiry_date ?? $inquiry->created_at)?->format('d M') }}
                </span>
            </div>
            <div class="ivd-list__end">
                <span class="ivd-pill ivd-tone-{{ $inquiryTone($inquiry->status) }}">
                    {{ \Illuminate\Support\Str::headline($inquiry->status ?: 'new') }}
                </span>
                @if ($inquiry->next_follow_up_at && ! $inquiry->isConverted())
                    <span class="ivd-list__meta">Call {{ $inquiry->next_follow_up_at->format('d M') }}</span>
                @endif
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <div class="ivd-empty"><i class="bx bx-user-voice"></i>No inquiries yet.</div>
    @endforelse
</x-dashboard.panel>
