<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AuthPortal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginLog;
use App\Support\AuthPortalGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function portalPicker(Request $request): View|RedirectResponse
    {
        $tenant = AuthPortalGuard::currentTenant();

        if ($tenant) {
            return redirect()->route('login.institute');
        }

        return view('auth.portal-picker');
    }

    public function create(AuthPortal $portal): View|RedirectResponse
    {
        $tenant = AuthPortalGuard::currentTenant();

        if ($portal === AuthPortal::Platform && $tenant) {
            return redirect()->route('login.institute');
        }

        return view('auth.login', $this->loginViewData($portal, $tenant));
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()?->must_change_password) {
            return redirect()->route('password.force');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $portal = AuthPortal::tryFrom((string) $request->session()->get('auth_portal'))
            ?? AuthPortalGuard::logoutRedirectPortal($user);

        if ($user) {
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
        }

        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($portal->routeName());
    }

    /**
     * @return array<string, mixed>
     */
    protected function loginViewData(AuthPortal $portal, ?\App\Models\Institute $tenant = null): array
    {
        $tenant ??= AuthPortalGuard::currentTenant();

        return match ($portal) {
            AuthPortal::Platform => [
                'portal' => $portal,
                'formAction' => route('login.platform.store'),
                'pageTitle' => 'Platform sign in',
                'kicker' => 'Class Flow Platform',
                'heading' => 'Platform console',
                'lead' => 'Super admin and developer access only.',
                'visualTitleLines' => ['Operate the', 'entire network.'],
                'visualLead' => 'Institutes, demos, billing and platform settings.',
                'tenant' => null,
            ],
            AuthPortal::Parent => [
                'portal' => $portal,
                'formAction' => route('login.parent.store'),
                'pageTitle' => 'Parent sign in',
                'kicker' => $tenant ? $tenant->name : 'Parent Portal',
                'heading' => $tenant ? 'Welcome, parent' : 'Parent portal',
                'lead' => 'Homework, notices, attendance and results for your child.',
                'visualTitleLines' => ['Stay close to', "your child's progress."],
                'visualLead' => 'Updates from class, without chasing the institute.',
                'tenant' => $tenant,
            ],
            AuthPortal::Principal => [
                'portal' => $portal,
                'formAction' => route('login.principal.store'),
                'pageTitle' => 'Principal sign in',
                'kicker' => $tenant ? $tenant->name : 'Principal',
                'heading' => 'Principal workspace',
                'lead' => 'Academics and operations for your assigned branch(es).',
                'visualTitleLines' => ['Lead the', 'branch with clarity.'],
                'visualLead' => 'Batches, students, attendance and exams in one place.',
                'tenant' => $tenant,
            ],
            AuthPortal::Branch => [
                'portal' => $portal,
                'formAction' => route('login.branch.store'),
                'pageTitle' => 'Branch manager sign in',
                'kicker' => $tenant ? $tenant->name : 'Branch Manager',
                'heading' => 'Branch workspace',
                'lead' => 'Manage day-to-day operations for your branch only.',
                'visualTitleLines' => ['One branch.', 'Full control.'],
                'visualLead' => 'Staff, students and ops scoped to your branch.',
                'tenant' => $tenant,
            ],
            AuthPortal::Institute => [
                'portal' => $portal,
                'formAction' => route('login.institute.store'),
                'pageTitle' => $tenant ? $tenant->name.' sign in' : 'Institute sign in',
                'kicker' => $tenant ? $tenant->name : 'Institute CRM',
                'heading' => $tenant ? 'Sign in to '.$tenant->name : 'Institute workspace',
                'lead' => 'Institute admin, accountant, receptionist and teachers.',
                'visualTitleLines' => ['Where coaching', 'becomes clarity.'],
                'visualLead' => 'One calm workspace for batches, fees, attendance and parent trust.',
                'tenant' => $tenant,
            ],
        };
    }
}
