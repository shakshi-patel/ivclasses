<?php

namespace App\Http\Controllers\Admin;

use App\Services\AppSettingsService;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingsController extends AdminController implements HasMiddleware
{
    public function __construct(
        protected AppSettingsService $settings,
        protected GoogleCalendarService $googleCalendar,
    ) {}

    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                abort_unless($request->user()?->isPlatformUser(), 403);

                return $next($request);
            },
        ];
    }

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'email');
        if (!in_array($tab, ['email', 'google', 'payment', 'website'], true)) {
            $tab = 'email';
        }

        return view('admin.settings.index', [
            'tab' => $tab,
            'settings' => $this->settings,
            'redirectUri' => $this->settings->googleRedirectUri(),
            'googleConnected' => $this->googleCalendar->isConnected(),
        ]);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['nullable', 'in:tls,ssl'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'demo_notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $this->settings->put([
            'mail.host' => $validated['mail_host'] ?? '',
            'mail.port' => isset($validated['mail_port']) ? (string) $validated['mail_port'] : '',
            'mail.encryption' => $validated['mail_encryption'] ?? '',
            'mail.username' => $validated['mail_username'] ?? '',
            'mail.password' => $validated['mail_password'] ?? '',
            'mail.from_address' => $validated['mail_from_address'] ?? '',
            'mail.from_name' => $validated['mail_from_name'] ?? '',
            'demo.notification_email' => $validated['demo_notification_email'] ?? '',
        ]);

        $this->settings->applyMailConfig();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'email'])
            ->with('success', 'Email settings saved.');
    }

    public function updateGoogle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_client_id' => ['nullable', 'string', 'max:500'],
            'google_client_secret' => ['nullable', 'string', 'max:500'],
            'google_calendar_id' => ['nullable', 'string', 'max:255'],
        ]);

        $oldClientId = (string) $this->settings->get('google.client_id', '');
        $oldSecret = (string) $this->settings->get('google.client_secret', '');

        $this->settings->put([
            'google.client_id' => $validated['google_client_id'] ?? '',
            'google.client_secret' => $validated['google_client_secret'] ?? '',
            'google.calendar_id' => $validated['google_calendar_id'] ?? 'primary',
        ]);

        $this->settings->applyGoogleConfig();

        $newClientId = (string) $this->settings->get('google.client_id', '');
        $newSecret = (string) $this->settings->get('google.client_secret', '');

        $credentialsChanged =
            ($oldClientId !== $newClientId)
            || (
                filled($validated['google_client_secret'] ?? null)
                && $oldSecret !== $newSecret
            );

        if ($credentialsChanged) {
            $this->googleCalendar->disconnect();
        }

        $message = 'Google settings saved.';
        if ($credentialsChanged) {
            $message .= ' Client credentials changed — please Connect Google Calendar again.';
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'google'])
            ->with('success', $message);
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $this->settings->applyMailConfig();

        try {
            Mail::raw(
                'IV Classes SMTP test — if you received this, admin email settings are working.',
                function ($message) use ($validated) {
                    $message
                        ->to($validated['test_email'])
                        ->subject('IV Classes — SMTP test');
                }
            );

            return redirect()
                ->route('admin.settings.index', ['tab' => 'email'])
                ->with('success', 'Test email sent to ' . $validated['test_email']);
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.settings.index', ['tab' => 'email'])
                ->with('error', 'Test email failed: ' . $e->getMessage());
        }
    }

    public function disconnectGoogle(): RedirectResponse
    {
        $this->googleCalendar->disconnect();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'google'])
            ->with('success', 'Google Calendar disconnected.');
    }

    public function updateWebsite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'footer_description' => ['nullable', 'string', 'max:1000'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_whatsapp' => ['nullable', 'url', 'max:255'],
            'header_logo' => ['nullable', 'image', 'max:2048'],
            'footer_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $this->settings->put([
            'website.contact_email' => $validated['contact_email'] ?? '',
            'website.address' => $validated['address'] ?? '',
            'website.meta_title' => $validated['meta_title'] ?? '',
            'website.meta_description' => $validated['meta_description'] ?? '',
            'website.footer_description' => $validated['footer_description'] ?? '',
            'website.social.facebook' => $validated['social_facebook'] ?? '',
            'website.social.instagram' => $validated['social_instagram'] ?? '',
            'website.social.twitter' => $validated['social_twitter'] ?? '',
            'website.social.linkedin' => $validated['social_linkedin'] ?? '',
            'website.social.youtube' => $validated['social_youtube'] ?? '',
            'website.social.whatsapp' => $validated['social_whatsapp'] ?? '',
        ]);

        if ($request->hasFile('header_logo')) {
            $this->settings->put([
                'website.logo_header' => $request->file('header_logo')->store('website', 'public'),
            ]);
        }

        if ($request->hasFile('footer_logo')) {
            $this->settings->put([
                'website.logo_footer' => $request->file('footer_logo')->store('website', 'public'),
            ]);
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'website'])
            ->with('success', 'Website settings saved.');
    }
}
