<?php

/**
 * Permission / Gate conventions.
 *
 * Parent portal access is granted via Spatie roles (see RoleTemplateService).
 * Do not re-introduce a Gate::before short-circuit for parents — keep Spatie
 * as the single source of truth so institute role edits actually apply.
 *
 * Plan module denial is prepended in AppServiceProvider::prependPlanModuleGate()
 * (reflection) so it runs before Spatie's Gate::before. See tests/Feature/PlanModuleGateTest.php.
 */
return [
    // Documented parent template abilities (must stay in sync with RoleTemplateService).
    'parent_abilities' => [
        'dashboard.view',
        'homework.view',
        'study_material.view',
        'study_material.download',
        'notice.view',
        'exam.view',
        'result_announcement.view',
        'student_leave.add',
    ],
];
