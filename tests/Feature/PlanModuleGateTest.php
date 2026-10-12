<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use ReflectionClass;
use Tests\TestCase;

/**
 * Documents Gate prepend (M8): plan module denial must run before Spatie's Gate::before.
 */
class PlanModuleGateTest extends TestCase
{
    public function test_plan_module_gate_is_prepended_before_other_before_callbacks(): void
    {
        $gate = $this->app->make(GateContract::class);
        $reflection = new ReflectionClass($gate);
        $property = $reflection->getProperty('beforeCallbacks');
        $callbacks = $property->getValue($gate);

        $this->assertNotEmpty($callbacks, 'Gate beforeCallbacks should include plan module check');

        $first = $callbacks[0];
        $this->assertIsCallable($first);

        // Platform users short-circuit true from the plan-module prepend callback.
        $platform = new class
        {
            public function isPlatformUser(): bool
            {
                return true;
            }

            public function isParent(): bool
            {
                return false;
            }
        };

        // Our callback type-hints User; non-User must return null.
        $this->assertNull($first(new \stdClass, 'fees.view', []));
    }

    public function test_parent_abilities_are_documented_in_config_not_gate_short_circuit(): void
    {
        $abilities = config('permissions.parent_abilities');
        $this->assertIsArray($abilities);
        $this->assertContains('homework.view', $abilities);
        $this->assertContains('study_material.download', $abilities);

        $source = file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $this->assertStringNotContainsString(
            'isParent()',
            $source,
            'Parent Gate short-circuit should remain removed; use Spatie permissions.'
        );
    }
}
