@forelse ($notices as $notice)
    @if ($loop->first)<ul class="ivd-list">@endif
    <li class="ivd-list__item">
        <span class="ivd-date ivd-tone-secondary">
            <strong>{{ $notice->notice_date?->format('d') }}</strong>
            <small>{{ $notice->notice_date?->format('M') }}</small>
        </span>
        <div class="ivd-list__main">
            <span class="ivd-list__title">{{ $notice->title }}</span>
            <span class="ivd-list__meta">
                For {{ \App\Models\Notice::AUDIENCES[$notice->audience] ?? \Illuminate\Support\Str::headline((string) $notice->audience) }}
            </span>
            @if ($notice->body)
                <span class="ivd-list__meta">{{ \Illuminate\Support\Str::limit($notice->body, 100) }}</span>
            @endif
        </div>
        @if ($notice->notice_date?->isToday())
            <div class="ivd-list__end"><span class="ivd-pill ivd-tone-success">New</span></div>
        @endif
    </li>
    @if ($loop->last)</ul>@endif
@empty
    <div class="ivd-empty"><i class="bx bx-news"></i>No active notices.</div>
@endforelse
