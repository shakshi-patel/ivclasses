@forelse ($holidays as $holiday)
    @if ($loop->first)<ul class="ivd-list">@endif
    @php($ongoing = $holiday->start_date?->isPast() || $holiday->start_date?->isToday())
    <li class="ivd-list__item">
        <span class="ivd-date {{ $ongoing ? 'ivd-tone-success' : 'ivd-tone-info' }}">
            <strong>{{ $holiday->start_date?->format('d') }}</strong>
            <small>{{ $holiday->start_date?->format('M') }}</small>
        </span>
        <div class="ivd-list__main">
            <span class="ivd-list__title">{{ $holiday->title }}</span>
            <span class="ivd-list__meta">{{ $holiday->dateLabel() }}</span>
        </div>
        @if ($ongoing)
            <div class="ivd-list__end"><span class="ivd-pill ivd-tone-success">Ongoing</span></div>
        @endif
    </li>
    @if ($loop->last)</ul>@endif
@empty
    <div class="ivd-empty py-3"><i class="bx bx-sun"></i>No upcoming holidays.</div>
@endforelse
