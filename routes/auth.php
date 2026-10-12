<?php

use App\Enums\AuthPortal;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\ForceChangePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'portalPicker'])
        ->name('login');

    Route::get('platform/login', fn () => app(AuthenticatedSessionController::class)->create(AuthPortal::Platform))
        ->name('login.platform');

    Route::post('platform/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.platform.store');

    Route::get('institute/login', fn () => app(AuthenticatedSessionController::class)->create(AuthPortal::Institute))
        ->name('login.institute');

    Route::post('institute/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.institute.store');

    Route::get('principal/login', fn () => app(AuthenticatedSessionController::class)->create(AuthPortal::Principal))
        ->name('login.principal');

    Route::post('principal/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.principal.store');

    Route::get('branch/login', fn () => app(AuthenticatedSessionController::class)->create(AuthPortal::Branch))
        ->name('login.branch');

    Route::post('branch/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.branch.store');

    Route::get('parent/login', fn () => app(AuthenticatedSessionController::class)->create(AuthPortal::Parent))
        ->name('login.parent');

    Route::post('parent/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.parent.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::get('force-change-password', [ForceChangePasswordController::class, 'edit'])
        ->name('password.force');

    Route::post('force-change-password', [ForceChangePasswordController::class, 'update'])
        ->name('password.force.update');

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
