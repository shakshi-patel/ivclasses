<?php

namespace App\Http\Controllers\Admin;

use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;

class GoogleCalendarController extends AdminController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                abort_unless($request->user()?->isPlatformUser(), 403);

                return $next($request);
            },
        ];
    }
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.settings.index', ['tab' => 'google']);
    }

    public function connect(GoogleCalendarService $googleCalendar): RedirectResponse
    {
        try {
            return redirect()->away($googleCalendar->getAuthorizationUrl());
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.settings.index', ['tab' => 'google'])
                ->with('error', $e->getMessage());
        }
    }

    public function callback(GoogleCalendarService $googleCalendar): RedirectResponse
    {
        $code = request('code');

        if (!$code) {
            return redirect()
                ->route('admin.settings.index', ['tab' => 'google'])
                ->with('error', 'Google authorization was cancelled or failed.');
        }

        try {
            $googleCalendar->authenticate($code);

            return redirect()
                ->route('admin.settings.index', ['tab' => 'google'])
                ->with('success', 'Google Calendar connected successfully.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.settings.index', ['tab' => 'google'])
                ->with('error', 'Unable to connect Google Calendar.');
        }
    }
}
