<?php

namespace App\Http\Requests\Auth;

use App\Enums\AuthPortal;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\LoginLog;
use App\Models\User;
use App\Support\AuthPortalGuard;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function portal(): AuthPortal
    {
        return match (true) {
            $this->routeIs('login.platform.store') => AuthPortal::Platform,
            $this->routeIs('login.parent.store') => AuthPortal::Parent,
            $this->routeIs('login.principal.store') => AuthPortal::Principal,
            $this->routeIs('login.branch.store') => AuthPortal::Branch,
            default => AuthPortal::Institute,
        };
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $portal = $this->portal();
        $credentials = $this->only('email', 'password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $this->logEvent('failed', null, $portal);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        try {
            AuthPortalGuard::assertUserMatchesPortal($user, $portal);
        } catch (ValidationException $e) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            $this->logEvent('failed', $user, $portal);

            throw $e;
        }

        if (! $user->is_active) {
            Auth::logout();
            $this->logEvent('failed', $user, $portal);

            $message = $user->registration_otp
                ? 'Please verify your registration OTP before signing in.'
                : 'This account is inactive.';

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        if ($portal === AuthPortal::Branch && ! $user->hasActiveBranchAccess()) {
            Auth::logout();
            $this->logEvent('failed', $user, $portal);

            throw ValidationException::withMessages([
                'email' => 'Your branch is inactive. Please contact the institute admin.',
            ]);
        }

        // Teachers / staff / managers scoped to branches cannot work if all assigned branches are inactive.
        if (
            in_array($portal, [AuthPortal::Institute, AuthPortal::Branch, AuthPortal::Principal], true)
            && ! $user->isPlatformUser()
            && $user->user_type !== UserType::InstituteAdmin
            && ($user->branches()->exists() || Branch::query()->where('manager_user_id', $user->id)->exists())
            && ! $user->hasActiveBranchAccess()
        ) {
            Auth::logout();
            $this->logEvent('failed', $user, $portal);

            throw ValidationException::withMessages([
                'email' => 'Your branch is inactive. Please contact the institute admin.',
            ]);
        }

        if ($user->institute && ! $user->institute->canAccessCrm()) {
            Auth::logout();
            $this->logEvent('failed', $user, $portal);

            throw ValidationException::withMessages([
                'email' => 'Institute is suspended or cancelled.',
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $this->ip(),
        ])->save();

        $this->session()->put('auth_portal', $portal->value);
        $this->logEvent('login', $user, $portal);

        RateLimiter::clear($this->throttleKey());
    }

    protected function logEvent(string $event, ?User $user, AuthPortal $portal): void
    {
        LoginLog::query()->create([
            'user_id' => $user?->id,
            'institute_id' => $user?->institute_id,
            'event' => $event,
            'portal' => $portal->value,
            'email' => $this->string('email')->toString(),
            'ip' => $this->ip(),
            'user_agent' => (string) $this->userAgent(),
            'logged_at' => now(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            $this->portal()->value.'|'.Str::lower($this->string('email')).'|'.$this->ip()
        );
    }
}
