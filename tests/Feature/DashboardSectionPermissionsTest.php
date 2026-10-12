<?php

namespace Tests\Feature;

use App\Services\Dashboard\InstituteDashboardService;
use App\Services\Dashboard\ParentDashboardService;
use App\Services\Dashboard\TeacherDashboardService;
use App\Support\PlanModules;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every dashboard section gated in code must be a permission in the matrix (and vice versa),
 * and dashboard section modules stay under the core `dashboard` plan module.
 */
class DashboardSectionPermissionsTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function dashboards(): array
    {
        return [
            'institute' => [InstituteDashboardService::class],
            'teacher' => [TeacherDashboardService::class],
            'parent' => [ParentDashboardService::class],
        ];
    }

    #[DataProvider('dashboards')]
    public function test_sections_match_the_permission_matrix(string $service): void
    {
        $module = config('permission_matrix.modules.'.$service::PERMISSION_MODULE);

        $this->assertIsArray($module, $service::PERMISSION_MODULE.' is missing from the permission matrix');
        $this->assertTrue($module['enabled']);
        $this->assertEqualsCanonicalizing(array_keys($module['actions']), array_keys($service::SECTION_ABILITIES));
    }

    #[DataProvider('dashboards')]
    public function test_sections_are_gated_under_the_core_dashboard_plan_module(string $service): void
    {
        $module = $service::PERMISSION_MODULE;

        $this->assertSame('dashboard', PlanModules::moduleFromAbility($module.'.attention'));
        $this->assertContains('dashboard', PlanModules::CORE);
        $this->assertArrayNotHasKey($module, PlanModules::crmCatalog());
    }
}
