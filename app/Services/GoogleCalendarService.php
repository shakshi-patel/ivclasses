<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\FreeBusyRequest;
use Google\Service\Calendar\FreeBusyRequestItem;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    protected Client $client;

    protected string $tokenPath = 'google/calendar-token';

    public function __construct(
        protected AppSettingsService $settings
    ) {
        $this->client = new Client();

        $this->client->setClientId((string) $this->settings->get('google.client_id'));
        $this->client->setClientSecret((string) $this->settings->get('google.client_secret'));
        $this->client->setRedirectUri($this->settings->googleRedirectUri());

        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $this->client->setScopes([
            Calendar::CALENDAR,
        ]);
    }

    public function getAuthorizationUrl(): string
    {
        if (!filled($this->settings->get('google.client_id')) || !filled($this->settings->get('google.client_secret'))) {
            throw new \RuntimeException('Google Client ID and Client Secret must be saved in Settings first.');
        }

        // Rebuild in case settings changed after construct.
        $this->client->setClientId((string) $this->settings->get('google.client_id'));
        $this->client->setClientSecret((string) $this->settings->get('google.client_secret'));
        $this->client->setRedirectUri($this->settings->googleRedirectUri());

        return $this->client->createAuthUrl();
    }

    public function authenticate(string $code): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException(
                $token['error_description'] ?? 'Google authorization failed.'
            );
        }

        Storage::disk('local')->put(
            $this->tokenPath,
            Crypt::encryptString(json_encode($token))
        );
    }

    public function getClient(): Client
    {
        if (!Storage::disk('local')->exists($this->tokenPath)) {
            throw new \RuntimeException('Google Calendar is not connected.');
        }

        $encryptedToken = Storage::disk('local')->get($this->tokenPath);

        $token = json_decode(
            Crypt::decryptString($encryptedToken),
            true
        );

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();

            if (!$refreshToken) {
                throw new \RuntimeException(
                    'Google refresh token is missing. Please reconnect Google Calendar.'
                );
            }

            $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                $refreshToken
            );

            $newToken['refresh_token'] = $refreshToken;

            Storage::disk('local')->put(
                $this->tokenPath,
                Crypt::encryptString(json_encode($newToken))
            );

            $this->client->setAccessToken($newToken);
        }

        return $this->client;
    }
    public function getCalendar(): Calendar
    {
        return new Calendar(
            $this->getClient()
        );
    }
    public function getBusyPeriods(
        Carbon|string $startDateTime,
        Carbon|string $endDateTime
    ): array {
        $timezone = config(
            'services.google.calendar_timezone',
            'Asia/Kolkata'
        );

        $start = $startDateTime instanceof Carbon
            ? $startDateTime->copy()->setTimezone($timezone)
            : Carbon::parse($startDateTime, $timezone);

        $end = $endDateTime instanceof Carbon
            ? $endDateTime->copy()->setTimezone($timezone)
            : Carbon::parse($endDateTime, $timezone);

        if ($end->lessThanOrEqualTo($start)) {
            throw new \InvalidArgumentException(
                'End datetime must be after start datetime.'
            );
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Get authenticated Google Calendar service
            |--------------------------------------------------------------------------
            */

            $calendar = $this->getCalendar();

            /*
            |--------------------------------------------------------------------------
            | Calendar ID
            |--------------------------------------------------------------------------
            */

            $calendarId = $this->calendarId();

            /*
            |--------------------------------------------------------------------------
            | Create FreeBusy Request
            |--------------------------------------------------------------------------
            */

            $request = new FreeBusyRequest();

            $request->setTimeMin(
                $start->toRfc3339String()
            );

            $request->setTimeMax(
                $end->toRfc3339String()
            );

            $request->setTimeZone($timezone);

            /*
            |--------------------------------------------------------------------------
            | Calendar to check
            |--------------------------------------------------------------------------
            */

            $item = new FreeBusyRequestItem();

            $item->setId($calendarId);

            $request->setItems([
                $item
            ]);

            /*
            |--------------------------------------------------------------------------
            | Call Google Calendar FreeBusy API
            |--------------------------------------------------------------------------
            */

            $response = $calendar
                ->freebusy
                ->query($request);

            /*
            |--------------------------------------------------------------------------
            | Get calendar response
            |--------------------------------------------------------------------------
            */

            $calendars = $response->getCalendars();

            if (!isset($calendars[$calendarId])) {
                return [];
            }

            $calendarData = $calendars[$calendarId];

            /*
            |--------------------------------------------------------------------------
            | Google Calendar API errors
            |--------------------------------------------------------------------------
            */

            $errors = $calendarData->getErrors();

            if (!empty($errors)) {
                Log::error('Google Calendar FreeBusy error.', [
                    'calendar_id' => $calendarId,
                    'errors' => $errors,
                ]);

                throw new \RuntimeException(
                    'Unable to retrieve Google Calendar availability.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Normalize busy periods
            |--------------------------------------------------------------------------
            */

            $busyPeriods = [];

            foreach ($calendarData->getBusy() ?? [] as $busy) {

                $busyStart = Carbon::parse(
                    $busy->getStart()
                )->setTimezone($timezone);

                $busyEnd = Carbon::parse(
                    $busy->getEnd()
                )->setTimezone($timezone);

                $busyPeriods[] = [
                    'start' => $busyStart->toIso8601String(),
                    'end' => $busyEnd->toIso8601String(),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Sort by start time
            |--------------------------------------------------------------------------
            */

            usort(
                $busyPeriods,
                fn($a, $b) => strcmp(
                    $a['start'],
                    $b['start']
                )
            );

            return $busyPeriods;

        } catch (\Throwable $e) {

            Log::error(
                'Google Calendar FreeBusy request failed.',
                [
                    'message' => $e->getMessage(),
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                ]
            );

            throw new \RuntimeException(
                'Unable to retrieve Google Calendar availability.',
                0,
                $e
            );
        }
    }
    public function isConnected(): bool
    {
        return Storage::disk('local')->exists($this->tokenPath);
    }

    public function disconnect(): void
    {
        if (Storage::disk('local')->exists($this->tokenPath)) {
            Storage::disk('local')->delete($this->tokenPath);
        }
    }

    public function createMeetEvent(array $details): array
    {
        $calendar = $this->getCalendar();
        $calendarId = $this->calendarId();
        $timezone = $details['timezone'];

        $attendees = $this->normalizeAttendees($details);
        $sendUpdates = $details['send_updates'] ?? (empty($attendees) ? 'none' : 'all');

        $payload = [
            'summary' => $details['summary'],
            'description' => $details['description'],
            'start' => new EventDateTime([
                'dateTime' => $details['start']->toRfc3339String(),
                'timeZone' => $timezone,
            ]),
            'end' => new EventDateTime([
                'dateTime' => $details['end']->toRfc3339String(),
                'timeZone' => $timezone,
            ]),
            'conferenceData' => new ConferenceData([
                'createRequest' => new CreateConferenceRequest([
                    'requestId' => $details['request_id'] ?? (string) Str::uuid(),
                    'conferenceSolutionKey' => new ConferenceSolutionKey([
                        'type' => 'hangoutsMeet',
                    ]),
                ]),
            ]),
        ];

        if (!empty($attendees)) {
            $payload['attendees'] = $attendees;
        }

        $event = new Event($payload);

        $createdEvent = $calendar->events->insert(
            $calendarId,
            $event,
            [
                'conferenceDataVersion' => 1,
                'sendUpdates' => $sendUpdates,
            ]
        );

        $meetLink = $this->extractMeetLink($createdEvent);

        if (!$meetLink) {
            $this->deleteEvent($createdEvent->getId());

            throw new \RuntimeException(
                'Google Meet link could not be generated.'
            );
        }

        return [
            'event_id' => $createdEvent->getId(),
            'meet_link' => $meetLink,
        ];
    }

    /**
     * Add a guest and email them a Google Calendar invite (Yes / No / Maybe).
     */
    public function inviteAttendee(string $eventId, string $email, ?string $displayName = null): void
    {
        $calendar = $this->getCalendar();
        $calendarId = $this->calendarId();
        $event = $calendar->events->get($calendarId, $eventId);

        $attendees = [];
        foreach ($event->getAttendees() ?? [] as $attendee) {
            $attendees[] = [
                'email' => $attendee->getEmail(),
                'displayName' => $attendee->getDisplayName(),
            ];
        }

        $alreadyInvited = collect($attendees)->contains(
            fn (array $row) => strcasecmp((string) $row['email'], $email) === 0
        );

        if (!$alreadyInvited) {
            $attendees[] = array_filter([
                'email' => $email,
                'displayName' => $displayName,
            ]);
        }

        $event->setAttendees($attendees);

        $calendar->events->update($calendarId, $eventId, $event, [
            'sendUpdates' => 'all',
        ]);
    }

    protected function normalizeAttendees(array $details): array
    {
        if (!empty($details['attendees']) && is_array($details['attendees'])) {
            return array_values(array_filter($details['attendees']));
        }

        if (!empty($details['attendee_email'])) {
            return [array_filter([
                'email' => $details['attendee_email'],
                'displayName' => $details['attendee_name'] ?? null,
            ])];
        }

        return [];
    }

    public function deleteEvent(?string $eventId): void
    {
        if (!$eventId) {
            return;
        }

        try {
            $this->getCalendar()->events->delete(
                $this->calendarId(),
                $eventId,
                ['sendUpdates' => 'none']
            );
        } catch (\Throwable $e) {
            Log::error('Failed to delete Google Calendar event.', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function calendarId(): string
    {
        return (string) ($this->settings->get('google.calendar_id', 'primary') ?: 'primary');
    }

    protected function extractMeetLink(Event $event): ?string
    {
        $conferenceData = $event->getConferenceData();

        if ($conferenceData) {
            $entryPoints = $conferenceData->getEntryPoints();

            if ($entryPoints) {
                foreach ($entryPoints as $entryPoint) {
                    if ($entryPoint->getEntryPointType() === 'video') {
                        return $entryPoint->getUri();
                    }
                }
            }
        }

        return $event->getHangoutLink() ?: null;
    }
}