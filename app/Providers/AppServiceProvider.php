<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AppSettingsService;
use App\Support\AdminFormScope;
use App\Support\PlanModules;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppSettingsService::class);
        $this->app->singleton(\App\Services\Billing\BillingGatewayManager::class);
        $this->app->singleton(\App\Services\Billing\SubscriptionBillingService::class);
        $this->app->singleton(\App\Services\Billing\PlanLimitService::class);
    }

    public function boot(): void
    {
        // Admin UI is Bootstrap 5 — avoid default Tailwind pagination (giant SVG arrows).
        Paginator::useBootstrapFive();

        // Must prepend after Spatie registers its Gate::before (otherwise Spatie
        // returns true first and plan module denial never runs).
        $this->app->booted(function () {
            $this->prependPlanModuleGate();
        });

        View::composer('admin.*', function ($view) {
            $view->with('formScope', AdminFormScope::for());

            $user = auth()->user();
            $institute = null;
            if ($user && ! $user->isPlatformUser() && $user->institute_id) {
                $institute = $user->relationLoaded('institute')
                    ? $user->institute
                    : $user->institute()->first();
            }

            $hasCustomLogo = $institute?->hasCustomLogo() ?? false;

            $moduleHelpRuntime = null;
            if ($user && ! $user->isPlatformUser()) {
                try {
                    $moduleHelpRuntime = app(\App\Services\ModuleHelpService::class)
                        ->runtimeForRequest($user, request()->route()?->getName());
                } catch (\Throwable $e) {
                    report($e);
                    $moduleHelpRuntime = null;
                }
            }

            $view->with([
                'brandInstitute' => $institute,
                'brandName' => $institute?->name,
                'brandHasCustomLogo' => $hasCustomLogo,
                'brandLogoFull' => $hasCustomLogo ? $institute->logoUrl() : null,
                'brandLogoMini' => $hasCustomLogo ? $institute->logoMiniUrl() : null,
                'moduleHelpRuntime' => $moduleHelpRuntime,
            ]);
        });

        try {
            $settings = $this->app->make(AppSettingsService::class);
            $settings->applyMailConfig();
            $settings->applyGoogleConfig();
        } catch (\Throwable $e) {
            // First boot / missing APP_KEY / corrupt file — keep .env defaults.
            report($e);
        }
    }

    protected function prependPlanModuleGate(): void
    {
        $gate = $this->app->make(GateContract::class);

        // Plan module check must run first (prepended below). Parents rely on Spatie
        // permissions only — see config/permissions.php + RoleTemplateService.
        $callback = function ($user, string $ability, array $arguments = []) {
            if (! $user instanceof User) {
                return null;
            }

            if ($user->isPlatformUser()) {
                return true;
            }

            if (! PlanModules::allowsAbility($user, $ability)) {
                return false;
            }

            return null;
        };

        $reflection = new ReflectionClass($gate);
        $property = $reflection->getProperty('beforeCallbacks');
        $callbacks = $property->getValue($gate);
        array_unshift($callbacks, $callback);
        $property->setValue($gate, $callbacks);
    }
}
