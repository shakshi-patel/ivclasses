@forelse ($exams as $exam)
    @if ($loop->first)<ul class="ivd-list">@endif
    @php($isToday = $exam->exam_date?->isToday())
    <li class="ivd-list__item">
        <span class="ivd-date {{ $isToday ? 'ivd-tone-error' : 'ivd-tone-primary' }}">
            <strong>{{ $exam->exam_date?->format('d') }}</strong>
            <small>{{ $exam->exam_date?->format('M') }}</small>
        </span>
        <div class="ivd-list__main">
            <span class="ivd-list__title">{{ $exam->title }}</span>
            <span class="ivd-list__meta">
                {{ $exam->batch?->name ?? '—' }}
                @if ($exam->subject) · {{ $exam->subject->name }} @endif
                @if ($exam->start_time) · {{ substr((string) $exam->start_time, 0, 5) }} @endif
            </span>
        </div>
        <div class="ivd-list__end">
            <span class="ivd-pill {{ $isToday ? 'ivd-tone-error' : 'ivd-tone-info' }}">
                {{ $isToday ? 'Today' : $exam->exam_date?->diffForHumans(['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW]) }}
            </span>
            @if ($exam->total_marks)
                <span class="ivd-list__meta">{{ rtrim(rtrim((string) $exam->total_marks, '0'), '.') }} marks</span>
            @endif
        </div>
    </li>
    @if ($loop->last)</ul>@endif
@empty
    <div class="ivd-empty"><i class="bx bx-calendar-check"></i>{{ $empty ?? 'No exams in the next 14 days.' }}</div>
@endforelse
