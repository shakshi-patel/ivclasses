@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-calendar-event"></i>
                    Demo Calendar
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 0.86rem; color: var(--color-text-secondary);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page" style="color: var(--iv-primary);">
                            Demo Calendar
                        </li>
                    </ol>
                </nav>

                <div class="row g-4 mt-1">
                    <div class="col-lg-7">
                        <div class="admin-detail-block mb-0">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <a href="{{ route('admin.demo-calendar.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}"
                                    class="btn btn-sm" style="background: var(--iv-primary); color: #fff;">
                                    <i class="bx bx-chevron-left"></i>
                                </a>
                                <h5 class="mb-0 fw-bold" style="color: var(--iv-primary);">
                                    {{ $monthStart->format('F Y') }}
                                </h5>
                                <a href="{{ route('admin.demo-calendar.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
                                    class="btn btn-sm" style="background: var(--iv-primary); color: #fff;">
                                    <i class="bx bx-chevron-right"></i>
                                </a>
                            </div>

                            <div class="demo-cal-weekdays">
                                <span>Mon</span>
                                <span>Tue</span>
                                <span>Wed</span>
                                <span>Thu</span>
                                <span>Fri</span>
                                <span>Sat</span>
                                <span>Sun</span>
                            </div>

                            <div class="demo-cal-grid">
                                @foreach ($days as $day)
                                    <button type="button"
                                        class="demo-cal-day{{ $day['in_month'] ? '' : ' is-muted' }}{{ $day['is_today'] ? ' is-today' : '' }}{{ $day['date'] === $selectedDate ? ' is-selected' : '' }}"
                                        data-date="{{ $day['date'] }}"
                                        aria-label="{{ $day['date'] }}">
                                        <span class="demo-cal-num">{{ $day['day'] }}</span>
                                        @if ($day['count'] > 0)
                                            <span class="demo-cal-count">{{ $day['count'] }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="admin-detail-block mb-0">
                            <h5 class="mb-3" id="demo-cal-side-title">Demos</h5>
                            <div id="demo-cal-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .demo-cal-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.4rem;
            margin-bottom: 0.4rem;
        }

        .demo-cal-weekdays span {
            text-align: center;
            font-size: 0.72rem;
            font-weight: 650;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--iv-text-secondary);
            padding: 0.25rem 0;
        }

        .demo-cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.4rem;
        }

        .demo-cal-day {
            position: relative;
            min-height: 4.25rem;
            border: 1px solid rgba(44, 42, 74, 0.1);
            border-radius: 0.7rem;
            background: #fff;
            color: var(--iv-primary);
            padding: 0.45rem 0.4rem 0.55rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            cursor: pointer;
        }

        .demo-cal-day:hover {
            border-color: var(--iv-primary);
        }

        .demo-cal-day.is-muted {
            color: #9aa0ab;
            background: #f7f8fa;
        }

        .demo-cal-day.is-today {
            box-shadow: inset 0 0 0 1.5px var(--iv-primary);
        }

        .demo-cal-day.is-selected {
            background: rgba(44, 42, 74, 0.08);
            border-color: var(--iv-primary);
        }

        .demo-cal-num {
            font-size: 0.9rem;
            font-weight: 650;
        }

        .demo-cal-count {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.2rem;
            padding: 0 0.35rem;
            border-radius: 999px;
            background: var(--iv-primary);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
        }

        .demo-cal-empty {
            color: var(--color-text-secondary);
            font-size: 0.9rem;
        }

        .demo-cal-item {
            border: 1px solid rgba(44, 42, 74, 0.1);
            border-radius: 0.85rem;
            padding: 0.9rem 1rem;
            margin-bottom: 0.75rem;
            background: #fff;
        }

        .demo-cal-item.is-cancelled {
            opacity: 0.7;
        }

        .demo-cal-item-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.45rem;
        }

        .demo-cal-item-code {
            font-weight: 700;
            color: var(--iv-primary);
            font-size: 0.88rem;
        }

        .demo-cal-meta {
            font-size: 0.82rem;
            color: var(--iv-primary);
            margin-bottom: 0.2rem;
        }

        .demo-cal-meta span {
            color: var(--color-text-secondary);
        }

        .demo-cal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.7rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var demosByDate = @json($demosByDate);
            var selectedDate = @json($selectedDate);
            var listEl = document.getElementById('demo-cal-list');
            var titleEl = document.getElementById('demo-cal-side-title');

            function escapeHtml(value) {
                if (value === null || value === undefined || value === '') {
                    return '--';
                }

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function formatHeading(dateStr) {
                var parts = dateStr.split('-');
                var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                return date.toLocaleDateString('en-IN', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            }

            function statusClass(status) {
                if (status === 'completed') return 'is-active';
                if (status === 'cancelled') return 'is-muted';
                return 'is-navy';
            }

            function render(dateStr) {
                selectedDate = dateStr;
                titleEl.textContent = formatHeading(dateStr);

                document.querySelectorAll('.demo-cal-day').forEach(function(btn) {
                    btn.classList.toggle('is-selected', btn.getAttribute('data-date') === dateStr);
                });

                var items = demosByDate[dateStr] || [];
                if (!items.length) {
                    listEl.innerHTML = '<p class="demo-cal-empty mb-0">No demos on this date.</p>';
                    return;
                }

                listEl.innerHTML = items.map(function(item) {
                    var cancelled = item.demo_status === 'cancelled' ? ' is-cancelled' : '';
                    var meet = item.meet_link
                        ? '<a href="' + escapeHtml(item.meet_link) + '" target="_blank" rel="noopener" class="btn btn-sm" style="background: var(--iv-primary); color: #fff;"><i class="bx bx-video me-1"></i>Meet</a>'
                        : '';

                    return '<div class="demo-cal-item' + cancelled + '">' +
                        '<div class="demo-cal-item-top">' +
                        '<div class="demo-cal-item-code">' + escapeHtml(item.inquiry_code) + '</div>' +
                        '<span class="admin-status-pill ' + statusClass(item.demo_status) + '">' +
                        escapeHtml(item.demo_status) + '</span>' +
                        '</div>' +
                        '<div class="demo-cal-meta"><span>Institute:</span> ' + escapeHtml(item.institute_name) + '</div>' +
                        '<div class="demo-cal-meta"><span>Contact:</span> ' + escapeHtml(item.contact_person_name) + '</div>' +
                        '<div class="demo-cal-meta"><span>Time:</span> ' + escapeHtml(item.time_label) + '</div>' +
                        '<div class="demo-cal-meta"><span>Phone:</span> ' + escapeHtml(item.contact_no) + '</div>' +
                        '<div class="demo-cal-actions">' +
                        '<a href="' + escapeHtml(item.url) + '" class="btn btn-sm" style="background: var(--iv-primary); color: #fff;">View</a>' +
                        meet +
                        '</div></div>';
                }).join('');
            }

            document.querySelectorAll('.demo-cal-day').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    render(btn.getAttribute('data-date'));
                });
            });

            render(selectedDate);
        });
    </script>
@endpush
