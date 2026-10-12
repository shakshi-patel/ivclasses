<x-dashboard.panel title="Recent results" subtitle="Published exams and class average" icon="bx-trophy"
    :action-url="route('admin.result_announcement')">
    @forelse ($dashboard->recentResults() as $announcement)
        @if ($loop->first)<ul class="ivd-list">@endif
        @php($avg = $announcement->average_percent)
        <li class="ivd-list__item">
            <span class="ivd-time ivd-tone-{{ $rateTone($avg) }}"><i class="bx bx-medal"></i></span>
            <div class="ivd-list__main">
                <span class="ivd-list__title">{{ $announcement->exam?->title ?? $announcement->title }}</span>
                <span class="ivd-list__meta">
                    {{ $announcement->exam?->batch?->name ?? '—' }}
                    @if ($announcement->exam?->subject) · {{ $announcement->exam->subject->name }} @endif
                    · {{ $announcement->students_count }} {{ \Illuminate\Support\Str::plural('student', $announcement->students_count) }}
                </span>
            </div>
            <div class="ivd-list__end">
                <span class="ivd-pill ivd-tone-{{ $rateTone($avg) }}">{{ $avg === null ? 'No marks' : $avg . '% avg' }}</span>
                <span class="ivd-list__meta">{{ $announcement->published_at?->format('d M') }}</span>
            </div>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <div class="ivd-empty"><i class="bx bx-trophy"></i>No results published yet.</div>
    @endforelse
</x-dashboard.panel>
