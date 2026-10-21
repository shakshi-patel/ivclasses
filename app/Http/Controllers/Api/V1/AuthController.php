<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SyncPermissionsTeamFromAuth;
use App\Http\Resources\Api\V1\UserMeResource;
use App\Models\Branch;
use App\Models\LoginLog;
use App\Models\User;
use App\Services\Mobile\MobileUserAccountService;
use App\Support\Ajax;
use App\Support\MobileApiRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        protected MobileUserAccountService $accounts
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate('mobile|'.Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => [trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ])],
            ]);
        }

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey);
            $this->logEvent('failed', $user, $credentials['email'], $request);

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        SyncPermissionsTeamFromAuth::sync($user);

        if (! MobileApiRole::isAllowedOnMobile($user)) {
            RateLimiter::hit($throttleKey);
            $this->logEvent('failed', $user, $credentials['email'], $request);

            return Ajax::error(
                'Platform and Institute Admin accounts use the web portal only.',
                null,
                403
            );
        }

        if (! $user->is_active) {
            RateLimiter::hit($throttleKey);
            $this->logEvent('failed', $user, $credentials['email'], $request);

            return Ajax::error(
                $user->registration_otp
                    ? 'Please verify your registration OTP before signing in.'
                    : 'This account is inactive.',
                null,
                403
            );
        }

        if (
            ($user->branches()->exists() || Branch::query()->where('manager_user_id', $user->id)->exists())
            && ! $user->hasActiveBranchAccess()
        ) {
            RateLimiter::hit($throttleKey);
            $this->logEvent('failed', $user, $credentials['email'], $request);

            return Ajax::error('Your branch is inactive. Please contact the institute admin.', null, 403);
        }

        if ($user->institute && ! $user->institute->canAccessCrm()) {
            RateLimiter::hit($throttleKey);
            $this->logEvent('failed', $user, $credentials['email'], $request);

            return Ajax::error('Institute is suspended or cancelled.', null, 403);
        }

        $role = MobileApiRole::resolve($user);
        if (! $role) {
            return Ajax::error('This account cannot use the mobile API.', null, 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $user->tokens()->where('name', 'mobile')->delete();

        $token = $user->createToken(
            'mobile',
            [MobileApiRole::ability($role)],
        )->plainTextToken;

        RateLimiter::clear($throttleKey);
        $this->logEvent('login', $user, $credentials['email'], $request);

        return Ajax::success('Logged in.', [
            'token' => $token,
            'token_type' => 'Bearer',
            'mobile_role' => $role,
            'must_change_password' => (bool) $user->must_change_password,
            'user' => (new UserMeResource($user->fresh()))->resolve($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return Ajax::error('Unauthenticated.', null, 401);
        }

        $this->accounts->revokeMobileTokens($user, $request->bearerToken());

        $this->logEvent('logout', $user, (string) $user->email, $request);

        return Ajax::success('Logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        return Ajax::success('OK', (new UserMeResource($request->user()))->resolve($request));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        /** @var User $user */
        $user = User::query()->findOrFail($request->user()->id);

        if (! Hash::check($data['current_password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        try {
            $this->accounts->updatePassword($user, $data['password']);
        } catch (RuntimeException $e) {
            return Ajax::error($e->getMessage(), null, 500);
        }

        return Ajax::success('Password updated. Please login again with your new password.', [
            'database' => $this->accounts->databaseName(),
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    protected function logEvent(string $event, ?User $user, string $email, Request $request): void
    {
        LoginLog::query()->create([
            'user_id' => $user?->id,
            'institute_id' => $user?->institute_id,
            'event' => $event,
            'portal' => 'mobile',
            'email' => $email,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'logged_at' => now(),
        ]);
    }
}
