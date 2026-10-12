<?php

namespace App\Http\Controllers\Website;

use App\Enums\AuthPortal;
use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\ProjectInquiry;
use App\Services\DemoBookingService;
use App\Support\AuthPortalGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DemoAdminResponseController extends Controller
{
    public function __construct(
        protected DemoBookingService $demoBookingService
    ) {}

    public function confirm(ProjectInquiry $inquiry)
    {
        try {
            $inquiry = $this->demoBookingService->confirmByAdmin($inquiry);
        } catch (InvalidArgumentException $e) {
            return view('website.demo_admin_response', [
                'ok' => false,
                'title' => 'Cannot confirm',
                'message' => $e->getMessage(),
                'inquiry' => $inquiry,
            ]);
        }

        return view('website.demo_admin_response', [
            'ok' => true,
            'title' => 'Demo confirmed',
            'message' => 'Client has been notified that the demo is confirmed.',
            'inquiry' => $inquiry,
        ]);
    }

    public function decline(ProjectInquiry $inquiry)
    {
        try {
            $inquiry = $this->demoBookingService->declineByAdmin($inquiry);
        } catch (InvalidArgumentException $e) {
            return view('website.demo_admin_response', [
                'ok' => false,
                'title' => 'Cannot decline',
                'message' => $e->getMessage(),
                'inquiry' => $inquiry,
            ]);
        }

        return view('website.demo_admin_response', [
            'ok' => true,
            'title' => 'Demo declined',
            'message' => 'Slot released. Client has been notified.',
            'inquiry' => $inquiry,
        ]);
    }

    /**
     * Public confirmation page must not open the admin panel on an existing session.
     * Always show the platform login screen; after sign-in, land on this inquiry.
     */
    public function openInAdmin(Request $request, ProjectInquiry $inquiry): RedirectResponse
    {
        $target = route('admin.project-inquiries.show', $inquiry);

        if ($user = $request->user()) {
            $portal = AuthPortal::tryFrom((string) $request->session()->get('auth_portal'))
                ?? AuthPortalGuard::logoutRedirectPortal($user);

            LoginLog::query()->create([
                'user_id' => $user->id,
                'institute_id' => $user->institute_id,
                'event' => 'logout',
                'portal' => $portal->value,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'logged_at' => now(),
            ]);

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $request->session()->put('url.intended', $target);

        return redirect()->route('login.platform');
    }
}
