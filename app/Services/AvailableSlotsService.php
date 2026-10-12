<?php

namespace App\Services;

use App\Models\DemoAvailability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailableSlotsService
{
    public function __construct(
        protected GoogleCalendarService $googleCalendarService
    ) {
    }

    /**
     * Get available demo slots for a specific date.
     */
    public function getAvailableSlots(string $date): array
    {
        $timezone = config(
            'services.google.calendar_timezone',
            'Asia/Kolkata'
        );

        /*
        |--------------------------------------------------------------------------
        | Parse selected date
        |--------------------------------------------------------------------------
        */

        $selectedDate = Carbon::createFromFormat(
            'Y-m-d',
            $date,
            $timezone
        )->startOfDay();

        /*
        |--------------------------------------------------------------------------
        | Get day of week
        |--------------------------------------------------------------------------
        */

        $dayOfWeek = $selectedDate->dayOfWeek;

        /*
        |--------------------------------------------------------------------------
        | Get active availability
        |--------------------------------------------------------------------------
        */

        $availabilities = DemoAvailability::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | No availability
        |--------------------------------------------------------------------------
        */

        if ($availabilities->isEmpty()) {
            return [];
        }

        $availableSlots = [];

        /*
        |--------------------------------------------------------------------------
        | Process each availability window
        |--------------------------------------------------------------------------
        */

        foreach ($availabilities as $availability) {

            $windowStart = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $selectedDate->format('Y-m-d') . ' ' . $availability->start_time,
                $timezone
            );

            $windowEnd = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $selectedDate->format('Y-m-d') . ' ' . $availability->end_time,
                $timezone
            );

            /*
            |--------------------------------------------------------------------------
            | Get Google Calendar busy periods
            |--------------------------------------------------------------------------
            */

            $busyPeriods = $this->googleCalendarService->getBusyPeriods(
                $windowStart,
                $windowEnd
            );

            /*
            |--------------------------------------------------------------------------
            | Generate possible slots
            |--------------------------------------------------------------------------
            */

            $slotDuration = (int) $availability->slot_duration_minutes;

            $bufferDuration = (int) $availability->buffer_minutes;

            $slotStart = $windowStart->copy();

            while (true) {

                $slotEnd = $slotStart->copy()->addMinutes(
                    $slotDuration
                );

                /*
                |--------------------------------------------------------------------------
                | Slot must completely fit inside availability
                |--------------------------------------------------------------------------
                */

                if ($slotEnd->greaterThan($windowEnd)) {
                    break;
                }

                /*
                |--------------------------------------------------------------------------
                | Check Google Calendar conflict
                |--------------------------------------------------------------------------
                */

                $isBusy = $this->slotOverlapsBusyPeriod(
                    $slotStart,
                    $slotEnd,
                    $busyPeriods
                );

                if (!$isBusy) {
                    $availableSlots[] = [
                        'start' => $slotStart->format('Y-m-d\TH:i:sP'),
                        'end' => $slotEnd->format('Y-m-d\TH:i:sP'),
                        'date' => $selectedDate->format('Y-m-d'),
                        'start_time' => $slotStart->format('H:i'),
                        'end_time' => $slotEnd->format('H:i'),
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Move to next slot
                |--------------------------------------------------------------------------
                */

                $slotStart->addMinutes(
                    $slotDuration + $bufferDuration
                );
            }
        }

        return $availableSlots;
    }

    /**
     * Check whether a slot overlaps any Google Calendar busy period.
     */
    protected function slotOverlapsBusyPeriod(
        Carbon $slotStart,
        Carbon $slotEnd,
        array $busyPeriods
    ): bool {
        foreach ($busyPeriods as $busyPeriod) {

            $busyStart = Carbon::parse(
                $busyPeriod['start']
            );

            $busyEnd = Carbon::parse(
                $busyPeriod['end']
            );

            /*
            |--------------------------------------------------------------------------
            | Overlap condition
            |--------------------------------------------------------------------------
            |
            | Slot overlaps when:
            |
            | slotStart < busyEnd
            | AND
            | slotEnd > busyStart
            |
            */

            if (
                $slotStart->lessThan($busyEnd) &&
                $slotEnd->greaterThan($busyStart)
            ) {
                return true;
            }
        }

        return false;
    }
}