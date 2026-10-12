<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Services\Dashboard\InstituteDashboardService;
use App\Services\InstituteOnboardingService;
use App\Services\Dashboard\ParentDashboardService;
use App\Services\Dashboard\PlatformDashboardService;
use App\Services\Dashboard\TeacherDashboardService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends AdminController
{
    /** User types that get the institute management dashboard. */
    private const INSTITUTE_TYPES = [
        UserType::InstituteAdmin,
        UserType::InstituteStaff,
        UserType::BranchManager,
    ];

    public function __construct(private readonly PlatformDashboardService $platform) {}

    public function index(Request $request): View
    {
        if ($this->isPlatform()) {
            return view('admin.dashboard.platform', [
                'summary' => $this->platform->summary(),
                'attention' => $this->platform->attention(),
                'recentInstitutes' => $this->platform->recentInstitutes(),
                'recentPayments' => $this->platform->recentPayments(),
                'upcomingRenewals' => $this->platform->upcomingRenewals(),
            ]);
        }

        $user = auth()->user();

        if (! $user?->institute_id) {
            return view('admin.dashboard');
        }

        if (in_array($user->user_type, self::INSTITUTE_TYPES, true)) {
            $dashboard = $this->instituteDashboard($request);

            return view('admin.dashboard.institute', [
                'dashboard' => $dashboard,
                'can' => $dashboard->abilities(),
                'summary' => $dashboard->summary(),
                'attention' => $dashboard->attention(),
                'plan' => $dashboard->planStatus(),
                'onboarding' => app(InstituteOnboardingService::class)->state($user),
            ]);
        }

        if ($user->isTeacher()) {
            $dashboard = TeacherDashboardService::for($user);

            return view('admin.dashboard.teacher', [
                'dashboard' => $dashboard,
                'can' => $dashboard->abilities(),
                'show' => $dashboard->sections(),
                'summary' => $dashboard->summary(),
                'attention' => $dashboard->attention(),
                'batches' => $dashboard->batches(),
            ]);
        }

        if ($user->isParent()) {
            $dashboard = $this->parentDashboard($request);

            return view('admin.dashboard.parent', [
                'dashboard' => $dashboard,
                'can' => $dashboard->abilities(),
                'show' => $dashboard->sections(),
                'summary' => $dashboard->summary(),
                'attention' => $dashboard->attention(),
            ]);
        }

        return view('admin.dashboard');
    }

    public function data(Request $request, string $widget): JsonResponse
    {
        if ($this->isPlatform()) {
            abort_unless(in_array($widget, PlatformDashboardService::CHART_WIDGETS, true), 404);

            return Ajax::success('OK', $this->platform->chart($widget));
        }

        $user = auth()->user();
        abort_unless($user?->institute_id, 403, 'Unauthorized.');

        $dashboard = match (true) {
            in_array($user->user_type, self::INSTITUTE_TYPES, true) => $this->instituteDashboard($request),
            $user->isTeacher() => TeacherDashboardService::for($user),
            $user->isParent() => $this->parentDashboard($request),
            default => null,
        };

        abort_unless($dashboard && $dashboard->allowsWidget($widget), 403, 'Unauthorized.');

        return Ajax::success('OK', $dashboard->chart($widget));
    }

    private function instituteDashboard(Request $request): InstituteDashboardService
    {
        return InstituteDashboardService::for(
            auth()->user(),
            $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
        );
    }

    private function parentDashboard(Request $request): ParentDashboardService
    {
        return ParentDashboardService::for(
            auth()->user(),
            $request->filled('student_id') ? (int) $request->input('student_id') : null,
        );
    }
}
