<?php

namespace App\Http\Controllers\Admin;

use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoogleCalendarTestController extends AdminController
{
    public function freeBusy(
        Request $request,
        GoogleCalendarService $googleCalendarService
    ) {
        $validated = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $timezone = config(
            'services.google.calendar_timezone',
            'Asia/Kolkata'
        );

        $date = Carbon::createFromFormat(
            'Y-m-d',
            $validated['date'],
            $timezone
        );

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        try {
            $busyPeriods = $googleCalendarService->getBusyPeriods(
                $start,
                $end
            );

            return response()->json([
                'success' => true,
                'date' => $date->format('Y-m-d'),
                'timezone' => $timezone,
                'busy_periods' => $busyPeriods,
            ]);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve Google Calendar availability.',
            ], 500);
        }
    }
}