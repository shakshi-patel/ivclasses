<x-dashboard.panel :title="$can['holidays'] ? 'Holidays & birthdays' : 'Birthdays today'"
    :subtitle="$can['holidays'] ? 'Upcoming breaks and today\'s celebrations' : 'Wish your students'"
    icon="bx-party" :action-url="$can['holidays'] ? route('admin.holiday') : null"
    :action-label="$can['holidays'] ? 'All holidays' : 'View all'">
    @if ($can['holidays'])
        @include('admin.dashboard.shared.holiday-list', ['holidays' => $dashboard->upcomingHolidays()])
    @endif

    @if ($can['students'])
        @if ($can['holidays'])
            <p class="ivd-section-label">Birthdays today</p>
        @endif
        @forelse ($dashboard->birthdaysToday() as $student)
            @if ($loop->first)<ul class="ivd-list">@endif
            <li class="ivd-list__item">
                <x-dashboard.avatar :name="$student->name" :src="$studentPhoto($student)" />
                <div class="ivd-list__main">
                    @if ($can['student_show'])
                        <a href="{{ route('admin.student.show', $student) }}" class="ivd-list__title">{{ $student->name }}</a>
                    @else
                        <span class="ivd-list__title">{{ $student->name }}</span>
                    @endif
                    <span class="ivd-list__meta">
                        {{ $student->standard?->name ?? '—' }} · turns {{ $student->dob?->age }}
                    </span>
                </div>
                <div class="ivd-list__end"><i class="bx bx-cake ivd-days ivd-tone-secondary fs-4"></i></div>
            </li>
            @if ($loop->last)</ul>@endif
        @empty
            <div class="ivd-empty py-3"><i class="bx bx-cake"></i>No birthdays today.</div>
        @endforelse
    @endif
</x-dashboard.panel>
