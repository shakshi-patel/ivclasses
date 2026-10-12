<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProjectInquiry;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DemoCalendarController extends AdminController
{
    public function index(Request $request)
    {
        $timezone = config('demo.timezone', 'Asia/Kolkata');
        $today = Carbon::now($timezone)->startOfDay();

        $year = (int) $request->integer('year', $today->year);
        $month = (int) $request->integer('month', $today->month);

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            $year = $today->year;
            $month = $today->month;
        }

        $monthStart = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfDay();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $inquiries = ProjectInquiry::query()
            ->whereNotNull('demo_date')
            ->whereBetween('demo_date', [
                $gridStart->toDateString(),
                $gridEnd->toDateString(),
            ])
            ->orderBy('demo_date')
            ->orderBy('demo_start_time')
            ->get();

        $demosByDate = $inquiries
            ->groupBy(fn (ProjectInquiry $inquiry) => $inquiry->demo_date->toDateString())
            ->map(function ($dayInquiries) {
                return $dayInquiries->map(function (ProjectInquiry $inquiry) {
                    $start = $inquiry->demo_start_time
                        ? date('h:i A', strtotime($inquiry->demo_start_time))
                        : null;
                    $end = $inquiry->demo_end_time
                        ? date('h:i A', strtotime($inquiry->demo_end_time))
                        : null;

                    return [
                        'id' => $inquiry->id,
                        'inquiry_code' => $inquiry->inquiry_code,
                        'institute_name' => $inquiry->institute_name,
                        'contact_person_name' => $inquiry->contact_person_name,
                        'email' => $inquiry->email,
                        'contact_no' => $inquiry->contact_no,
                        'demo_status' => $inquiry->demo_status,
                        'time_label' => $start
                            ? ($end ? "{$start} – {$end}" : $start)
                            : '--',
                        'meet_link' => $inquiry->google_meet_link,
                        'url' => route('admin.project-inquiries.show', $inquiry),
                    ];
                })->values();
            });

        $days = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $dateKey = $cursor->toDateString();
            $dayInquiries = $demosByDate->get($dateKey, collect());

            $days[] = [
                'date' => $dateKey,
                'day' => $cursor->day,
                'in_month' => $cursor->month === $monthStart->month,
                'is_today' => $cursor->isSameDay($today),
                'count' => $dayInquiries->count(),
            ];

            $cursor->addDay();
        }

        $prevMonth = $monthStart->copy()->subMonth();
        $nextMonth = $monthStart->copy()->addMonth();

        $selectedDate = $request->string('date')->toString();
        if ($selectedDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = $today->month === $monthStart->month && $today->year === $monthStart->year
                ? $today->toDateString()
                : $monthStart->toDateString();
        }

        return view('admin.demo-calendar.index', [
            'monthStart' => $monthStart,
            'days' => $days,
            'demosByDate' => $demosByDate,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'selectedDate' => $selectedDate,
        ]);
    }
}
