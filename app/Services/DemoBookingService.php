<?php

namespace App\Services;

use App\Enums\DemoStatus;
use App\Enums\ProjectInquiryStatus;
use App\Models\ProjectInquiry;
use App\Support\EmailTemplateRegistry;
use Carbon\Carbon;
use Google\Service\Calendar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class DemoBookingService
{
    protected string $timezone;

    protected string $demoStartTime;

    protected string $demoEndTime;

    protected int $slotDuration;

    protected int $slotGap;

    protected string $calendarId;

    protected string $eventTitlePrefix;

    protected string $inquiryCodePrefix;

    protected int $confirmationLinkDays;

    public function __construct(
        protected GoogleCalendarService $googleCalendarService,
        protected AppSettingsService $appSettings,
        protected MailTemplateService $mailTemplates,
    ) {
        $this->timezone = (string) config('demo.timezone');
        $this->demoStartTime = (string) config('demo.business_start');
        $this->demoEndTime = (string) config('demo.business_end');
        $this->slotDuration = (int) config('demo.duration_minutes');
        $this->slotGap = (int) config('demo.gap_minutes');
        $this->calendarId = (string) ($this->appSettings->get('google.calendar_id', 'primary') ?: 'primary');
        $this->eventTitlePrefix = (string) config('demo.event_title_prefix');
        $this->inquiryCodePrefix = (string) config('demo.inquiry_code_prefix');
        $this->confirmationLinkDays = (int) config('demo.confirmation_link_days', 7);
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function isCalendarConnected(): bool
    {
        return $this->googleCalendarService->isConnected();
    }

    public function getSlots(Carbon $date, bool $checkGoogle = true): array
    {
        $busyPeriods = $this->busyPeriodsForDate($date, $checkGoogle);

        $slots = [];

        $current = Carbon::createFromFormat(
            'Y-m-d H:i',
            $date->format('Y-m-d') . ' ' . $this->demoStartTime,
            $this->timezone
        );

        $businessEnd = Carbon::createFromFormat(
            'Y-m-d H:i',
            $date->format('Y-m-d') . ' ' . $this->demoEndTime,
            $this->timezone
        );

        $now = Carbon::now($this->timezone);

        while ($current->copy()->addMinutes($this->slotDuration)->lte($businessEnd)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes($this->slotDuration);

            if ($date->isToday() && $slotStart->lte($now)) {
                $current->addMinutes($this->slotDuration + $this->slotGap);
                continue;
            }

            $isBusy = false;

            foreach ($busyPeriods as $busy) {
                if ($slotStart->lt($busy['end']) && $slotEnd->gt($busy['start'])) {
                    $isBusy = true;
                    break;
                }
            }

            $slots[] = [
                'start' => $slotStart->toRfc3339String(),
                'end' => $slotEnd->toRfc3339String(),
                'date' => $slotStart->format('Y-m-d'),
                'start_time' => $slotStart->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'available' => !$isBusy,
                'booked' => $isBusy,
            ];

            $current->addMinutes($this->slotDuration + $this->slotGap);
        }

        return $slots;
    }

    public function isSlotAvailable(Carbon $start, Carbon $end): bool
    {
        foreach ($this->getSlots($start->copy()->startOfDay()) as $slot) {
            if (
                $slot['start_time'] === $start->format('H:i') &&
                $slot['end_time'] === $end->format('H:i') &&
                $slot['available'] === true
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Step 1 — client books:
     * - Meet event is created for the team (no client Google invite / no Yes-No RSVP)
     * - demo_status = requested (waiting for admin)
     * - client gets "we received your request"
     * - admin gets Confirm / Decline buttons
     */
    public function book(array $validated, string $source = 'website'): ProjectInquiry
    {
        if (!$this->googleCalendarService->isConnected()) {
            throw new \RuntimeException('Demo booking is temporarily unavailable. Please try again later.');
        }

        $demoStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['demo_date'] . ' ' . $validated['demo_start_time'],
            $this->timezone
        );

        $demoEnd = $demoStart->copy()->addMinutes($this->slotDuration);

        $businessStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['demo_date'] . ' ' . $this->demoStartTime,
            $this->timezone
        );

        $businessEnd = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['demo_date'] . ' ' . $this->demoEndTime,
            $this->timezone
        );

        if ($demoStart->lt($businessStart) || $demoEnd->gt($businessEnd)) {
            throw new \InvalidArgumentException('Selected demo time is outside working hours.');
        }

        if ($demoStart->lte(Carbon::now($this->timezone))) {
            throw new \InvalidArgumentException('Please select a future demo time.');
        }

        $lock = Cache::lock('demo-slot:' . $demoStart->format('Y-m-d-H-i'), 30);

        if (!$lock->get()) {
            throw new \InvalidArgumentException('This time is already booked for a demo.');
        }

        $googleEventId = null;

        try {
            if (!$this->isSlotAvailable($demoStart, $demoEnd)) {
                throw new \InvalidArgumentException('This time is already booked for a demo.');
            }

            $inquiryCode = $this->generateInquiryCode();

            // No client attendee → client does not get Google Calendar Yes / No / Maybe.
            $calendarResult = $this->googleCalendarService->createMeetEvent([
                'summary' => $this->eventTitlePrefix . ' - ' . $validated['institute_name'],
                'description' =>
                    $this->eventTitlePrefix . "\n\n" .
                    "Institute: {$validated['institute_name']}\n" .
                    "Contact Person: {$validated['contact_person_name']}\n" .
                    "Phone: {$validated['contact_no']}\n" .
                    "Email: {$validated['email']}\n" .
                    "City: {$validated['city']}\n\n" .
                    "Inquiry Code: {$inquiryCode}\n" .
                    "Status: Waiting for admin confirmation",
                'start' => $demoStart,
                'end' => $demoEnd,
                'timezone' => $this->timezone,
                'attendees' => [],
                'send_updates' => 'none',
                'request_id' => (string) Str::uuid(),
            ]);

            $googleEventId = $calendarResult['event_id'];

            $inquiry = ProjectInquiry::create([
                'inquiry_code' => $inquiryCode,
                'institute_name' => $validated['institute_name'],
                'contact_person_name' => $validated['contact_person_name'],
                'email' => $validated['email'],
                'contact_no' => $validated['contact_no'],
                'city' => $validated['city'],
                'state' => $validated['state'] ?? null,
                'pincode' => $validated['pincode'] ?? null,
                'address' => $validated['address'] ?? null,
                'student_count' => $validated['student_count'] ?? null,
                'branch_count' => $validated['branch_count'] ?? null,
                'requirement' => $validated['requirement'] ?? null,
                'message' => $validated['message'] ?? null,
                'source' => $source,
                'status' => ProjectInquiryStatus::New,
                'demo_status' => DemoStatus::Requested,
                'demo_date' => $demoStart->format('Y-m-d'),
                'demo_start_time' => $demoStart->format('H:i:s'),
                'demo_end_time' => $demoEnd->format('H:i:s'),
                'timezone' => $this->timezone,
                'demo_scheduled_at' => $demoStart,
                'google_event_id' => $calendarResult['event_id'],
                'google_meet_link' => $calendarResult['meet_link'],
            ]);

            [$dateLabel, $timeLabel] = $this->labelsFor($inquiry);

            defer(fn () => $this->sendRequestEmails($inquiry, $dateLabel, $timeLabel));

            return $inquiry;
        } catch (\Throwable $e) {
            if ($googleEventId) {
                $this->googleCalendarService->deleteEvent($googleEventId);
            }

            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Step 2a — admin clicks Confirm (Yes):
     * - status becomes scheduled
     * - client gets final confirmation + Meet link (Laravel mail, not Google RSVP)
     */
    public function confirmByAdmin(ProjectInquiry $inquiry): ProjectInquiry
    {
        if ($inquiry->demo_status === DemoStatus::Scheduled) {
            return $inquiry;
        }

        if ($inquiry->demo_status !== DemoStatus::Requested) {
            throw new \InvalidArgumentException('This demo can no longer be confirmed.');
        }

        $inquiry->update([
            'demo_status' => DemoStatus::Scheduled,
            'status' => ProjectInquiryStatus::New,
        ]);

        $inquiry->refresh();
        [$dateLabel, $timeLabel] = $this->labelsFor($inquiry);

        defer(fn () => $this->sendClientConfirmedEmail($inquiry, $dateLabel, $timeLabel));

        return $inquiry;
    }

    /**
     * Step 2b — admin clicks Decline (No):
     * - status becomes cancelled
     * - Meet event removed
     * - client gets decline notice
     */
    public function declineByAdmin(ProjectInquiry $inquiry): ProjectInquiry
    {
        if ($inquiry->demo_status === DemoStatus::Cancelled) {
            return $inquiry;
        }

        if ($inquiry->demo_status !== DemoStatus::Requested) {
            throw new \InvalidArgumentException('This demo can no longer be declined.');
        }

        $eventId = $inquiry->google_event_id;

        $inquiry->update([
            'demo_status' => DemoStatus::Cancelled,
            'status' => ProjectInquiryStatus::Cancelled,
        ]);

        $inquiry->refresh();
        [$dateLabel, $timeLabel] = $this->labelsFor($inquiry);

        if ($eventId) {
            $this->googleCalendarService->deleteEvent($eventId);
        }

        defer(fn () => $this->sendClientDeclinedEmail($inquiry, $dateLabel, $timeLabel));

        return $inquiry;
    }

    public function adminConfirmUrl(ProjectInquiry $inquiry): string
    {
        return URL::temporarySignedRoute(
            'demo.admin.confirm',
            now()->addDays($this->confirmationLinkDays),
            ['inquiry' => $inquiry->id]
        );
    }

    public function adminDeclineUrl(ProjectInquiry $inquiry): string
    {
        return URL::temporarySignedRoute(
            'demo.admin.decline',
            now()->addDays($this->confirmationLinkDays),
            ['inquiry' => $inquiry->id]
        );
    }

    protected function sendRequestEmails(
        ProjectInquiry $inquiry,
        string $dateLabel,
        string $timeLabel
    ): void {
        try {
            $this->mailTemplates->send(
                EmailTemplateRegistry::DEMO_CLIENT_RECEIVED,
                $inquiry->email,
                $this->mailTemplates->variablesForDemoInquiry($inquiry, $dateLabel, $timeLabel)
            );
        } catch (\Throwable $e) {
            report($e);
        }

        $adminEmail = $this->appSettings->get('demo.notification_email');

        if (!$adminEmail) {
            return;
        }

        try {
            $this->mailTemplates->send(
                EmailTemplateRegistry::DEMO_ADMIN_ACTION,
                $adminEmail,
                $this->mailTemplates->variablesForDemoInquiry(
                    $inquiry,
                    $dateLabel,
                    $timeLabel,
                    $this->adminConfirmUrl($inquiry),
                    $this->adminDeclineUrl($inquiry),
                    'Google Meet (for you)',
                )
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function sendClientConfirmedEmail(
        ProjectInquiry $inquiry,
        string $dateLabel,
        string $timeLabel
    ): void {
        try {
            $this->mailTemplates->send(
                EmailTemplateRegistry::DEMO_CLIENT_CONFIRMED,
                $inquiry->email,
                $this->mailTemplates->variablesForDemoInquiry($inquiry, $dateLabel, $timeLabel)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function sendClientDeclinedEmail(
        ProjectInquiry $inquiry,
        string $dateLabel,
        string $timeLabel
    ): void {
        try {
            $this->mailTemplates->send(
                EmailTemplateRegistry::DEMO_CLIENT_DECLINED,
                $inquiry->email,
                $this->mailTemplates->variablesForDemoInquiry($inquiry, $dateLabel, $timeLabel)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function labelsFor(ProjectInquiry $inquiry): array
    {
        $dateLabel = optional($inquiry->demo_date)->format('d M Y') ?? '';
        $timeLabel = '';

        if ($inquiry->demo_start_time && $inquiry->demo_end_time) {
            $timeLabel =
                date('h:i A', strtotime($inquiry->demo_start_time)) .
                ' – ' .
                date('h:i A', strtotime($inquiry->demo_end_time));
        }

        return [$dateLabel, $timeLabel];
    }

    protected function busyPeriodsForDate(Carbon $date, bool $checkGoogle = true): array
    {
        $busyPeriods = [];

        if ($checkGoogle && $this->googleCalendarService->isConnected()) {
            try {
                $calendar = new Calendar($this->googleCalendarService->getClient());

                $events = $calendar->events->listEvents($this->calendarId, [
                    'timeMin' => $date->copy()->startOfDay()->toRfc3339String(),
                    'timeMax' => $date->copy()->endOfDay()->toRfc3339String(),
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                ]);

                foreach ($events->getItems() as $event) {
                    if ($event->getStatus() === 'cancelled') {
                        continue;
                    }

                    $start = $event->getStart();
                    $end = $event->getEnd();
                    $startDateTime = $start->getDateTime();
                    $endDateTime = $end->getDateTime();

                    if (!$startDateTime || !$endDateTime) {
                        if ($start->getDate()) {
                            $busyPeriods[] = [
                                'start' => $date->copy()->startOfDay(),
                                'end' => $date->copy()->endOfDay(),
                            ];
                        }

                        continue;
                    }

                    $busyPeriods[] = [
                        'start' => Carbon::parse($startDateTime)->setTimezone($this->timezone),
                        'end' => Carbon::parse($endDateTime)->setTimezone($this->timezone),
                    ];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $inquiries = ProjectInquiry::query()
            ->whereDate('demo_date', $date->toDateString())
            ->whereIn('demo_status', [DemoStatus::Requested, DemoStatus::Scheduled])
            ->get(['demo_start_time', 'demo_end_time']);

        foreach ($inquiries as $inquiry) {
            if (!$inquiry->demo_start_time || !$inquiry->demo_end_time) {
                continue;
            }

            $busyPeriods[] = [
                'start' => Carbon::parse(
                    $date->format('Y-m-d') . ' ' . $inquiry->demo_start_time,
                    $this->timezone
                ),
                'end' => Carbon::parse(
                    $date->format('Y-m-d') . ' ' . $inquiry->demo_end_time,
                    $this->timezone
                ),
            ];
        }

        return $busyPeriods;
    }

    protected function generateInquiryCode(): string
    {
        do {
            $code =
                $this->inquiryCodePrefix . '-' .
                now()->format('Ymd') . '-' .
                strtoupper(Str::random(6));
        } while (ProjectInquiry::where('inquiry_code', $code)->exists());

        return $code;
    }
}
