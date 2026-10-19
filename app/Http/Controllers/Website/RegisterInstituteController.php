<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\SubscriptionBillingService;
use App\Services\InstituteRegistrationService;
use App\Services\MailTemplateService;
use App\Support\Ajax;
use App\Support\EmailTemplateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisterInstituteController extends Controller
{
    public function create(Request $request, SubscriptionBillingService $billing): View
    {
        $plans = $billing->publicPlans();

        $selectedPlanId = (int) old('plan_id', 0);
        if (! $selectedPlanId && $request->filled('plan')) {
            $selectedPlanId = (int) ($plans->firstWhere('code', $request->string('plan')->toString())?->id ?? 0);
        }

        return view('website.register_institute', [
            'plans' => $plans,
            'selectedPlanId' => $selectedPlanId,
            'startOnForm' => $selectedPlanId > 0,
        ]);
    }

    public function store(Request $request, InstituteRegistrationService $service): JsonResponse|RedirectResponse
    {
        $hasBranches = $request->input('has_branches') === 'yes';

        $rules = [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(fn ($q) => $q
                    ->where('is_public', true)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')),
            ],
            'has_branches' => ['required', Rule::in(['yes', 'no'])],
            'name' => ['required', 'string', 'max:150'],
            'admin_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'branch_count' => ['nullable', 'integer', 'min:1', 'max:'.InstituteRegistrationService::BRANCH_FORM_CAP],
            'branches' => [$hasBranches ? 'required' : 'nullable', 'array', 'min:1'],
            'branches.*.name' => [$hasBranches ? 'required' : 'nullable', 'string', 'max:150'],
            'branches.*.phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'branches.*.address' => ['nullable', 'string', 'max:500'],
            'branches.*.manager_name' => [$hasBranches ? 'required' : 'nullable', 'string', 'max:150'],
            'branches.*.manager_email' => [
                $hasBranches ? 'required' : 'nullable',
                'email',
                'max:150',
                'distinct',
                'different:email',
                'unique:users,email',
            ],
            'branches.*.manager_password' => [$hasBranches ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            // Legacy single-branch keys (demo convert / fallback)
            'branch_name' => ['nullable', 'string', 'max:150'],
            'branch_phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'branch_address' => ['nullable', 'string', 'max:500'],
            'manager_name' => ['nullable', 'string', 'max:150'],
            'manager_email' => ['nullable', 'email', 'max:150'],
            'manager_password' => ['nullable', 'string', 'min:8'],
        ];

        $validated = $request->validate($rules, [
            'plan_id.required' => 'Please choose a plan to continue.',
            'has_branches.required' => 'Please tell us if you have branches.',
            'branches.required' => 'Please add branch details.',
            'branches.*.manager_email.different' => 'Branch manager email must be different from institute admin email.',
            'branches.*.manager_email.distinct' => 'Each branch manager must use a different email.',
        ]);

        if ($hasBranches) {
            $plan = Plan::query()->findOrFail((int) $validated['plan_id']);
            $count = max(1, (int) ($validated['branch_count'] ?? count($validated['branches'] ?? [])));
            if ($plan->max_branches !== null && $count > (int) $plan->max_branches) {
                $allowed = (int) $plan->max_branches;
                $planName = $plan->name;

                return back()
                    ->withInput()
                    ->withErrors([
                        'branch_count' => "Your selected plan “{$planName}” allows only {$allowed} branch".($allowed === 1 ? '' : 'es').". Please choose {$allowed} or less, or pick a higher plan.",
                    ]);
            }
            if (count($validated['branches'] ?? []) !== $count) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'branches' => 'Please fill details for all '.$count.' branch(es).',
                    ]);
            }
        }

        $result = $service->register($validated);

        $branchLogins = [];
        if ($hasBranches) {
            foreach ($validated['branches'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $branchName = trim((string) ($row['name'] ?? ''));
                if ($branchName === '') {
                    continue;
                }
                $branchLogins[] = [
                    'branch_name' => $branchName,
                    'manager_name' => (string) ($row['manager_name'] ?? ''),
                    'manager_email' => (string) ($row['manager_email'] ?? ''),
                    'manager_password' => (string) ($row['manager_password'] ?? ''),
                ];
            }
        }

        $firstBranch = $branchLogins[0] ?? null;
        $request->session()->put('pending_registration_user_id', $result['user']->id);
        $request->session()->put('pending_registration_credentials', [
            'institute_name' => $result['institute']->name,
            'branch_name' => $firstBranch['branch_name'] ?? $result['branch']->name,
            'admin_name' => $result['user']->name,
            'admin_email' => $result['user']->email,
            'admin_password' => $validated['password'],
            'manager_name' => $firstBranch['manager_name'] ?? $result['user']->name,
            'manager_email' => $firstBranch['manager_email'] ?? $result['user']->email,
            'manager_password' => $firstBranch['manager_password'] ?? $validated['password'],
            'branches' => $branchLogins,
            'has_separate_branch_login' => $hasBranches && $branchLogins !== [],
            'institute_login_url' => route('login.institute'),
            'branch_login_url' => route('login.branch'),
        ]);
        $this->sendOtpEmail($result['user'], $result['institute']->name, $result['otp']);

        if ($request->expectsJson()) {
            return Ajax::success('OTP sent. Please verify to activate your account.', [
                'redirect' => route('register-institute.verify'),
                'email' => $result['user']->email,
                'branch_id' => $result['branch']->id,
            ], 201);
        }

        return redirect()->route('register-institute.verify');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()
                ->route('register-institute')
                ->withErrors(['email' => 'Please register your institute first.']);
        }

        if ($user->email_verified_at && $user->is_active && ! $user->registration_otp) {
            $request->session()->forget('pending_registration_user_id');

            return redirect()
                ->route('login.institute')
                ->with('success', 'Account already verified. Please sign in.');
        }

        return view('website.register_institute_otp', [
            'email' => $user->email,
            'expiresAt' => $user->registration_otp_expires_at,
        ]);
    }

    public function verify(Request $request, InstituteRegistrationService $service): JsonResponse|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            if ($request->expectsJson()) {
                return Ajax::error('Registration session expired. Please register again.', null, 422);
            }

            return redirect()
                ->route('register-institute')
                ->withErrors(['email' => 'Registration session expired. Please register again.']);
        }

        $validated = $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        try {
            $service->verifyOtp($user, $validated['otp']);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return Ajax::error($e->getMessage(), null, 422);
            }

            return back()->withErrors(['otp' => $e->getMessage()]);
        }

        $this->sendCredentialsEmail($request, app(MailTemplateService::class));

        $request->session()->forget([
            'pending_registration_user_id',
            'pending_registration_credentials',
        ]);

        $message = 'Verified. Login details have been sent to your institute admin email.';

        if ($request->expectsJson()) {
            return Ajax::success($message, [
                'redirect' => route('login.institute'),
            ]);
        }

        return redirect()
            ->route('login.institute')
            ->with('success', $message);
    }

    public function resend(Request $request, InstituteRegistrationService $service): JsonResponse|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            if ($request->expectsJson()) {
                return Ajax::error('Registration session expired. Please register again.', null, 422);
            }

            return redirect()->route('register-institute');
        }

        $result = $service->regenerateOtp($user);
        $this->sendOtpEmail($result['user'], $result['user']->institute?->name ?? '', $result['otp']);

        if ($request->expectsJson()) {
            return Ajax::success('New code sent to your email.', []);
        }

        return redirect()
            ->route('register-institute.verify')
            ->with('success', 'New code sent to your email.');
    }

    protected function pendingUser(Request $request): ?User
    {
        $userId = (int) $request->session()->get('pending_registration_user_id', 0);
        if ($userId < 1) {
            return null;
        }

        return User::query()->find($userId);
    }

    protected function sendOtpEmail(User $user, string $instituteName, string $otp): void
    {
        try {
            $mailTemplates = app(MailTemplateService::class);
            $mailTemplates->send(
                EmailTemplateRegistry::REGISTRATION_OTP,
                $user->email,
                $mailTemplates->variablesForOtp($instituteName, $user->name, $otp)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function sendCredentialsEmail(Request $request, MailTemplateService $mailTemplates): void
    {
        /** @var array<string, mixed>|null $credentials */
        $credentials = $request->session()->get('pending_registration_credentials');
        if (! is_array($credentials) || empty($credentials['admin_email'])) {
            return;
        }

        try {
            $mailTemplates->send(
                EmailTemplateRegistry::REGISTRATION_CREDENTIALS,
                $credentials['admin_email'],
                $mailTemplates->variablesForCredentials($credentials)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
