<?php

use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ClassesBrandingController;
use App\Http\Controllers\Admin\AccountProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemoAvailabilityController;
use App\Http\Controllers\Admin\DemoCalendarController;
use App\Http\Controllers\Admin\GoogleCalendarController;
use App\Http\Controllers\Admin\GoogleCalendarTestController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\FollowUpController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\AssetCategoryController;
use App\Http\Controllers\Admin\IeCategoryController;
use App\Http\Controllers\Admin\InstituteController;
use App\Http\Controllers\Admin\InstituteLookupController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ModuleHelpController;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\GstInvoiceController;
use App\Http\Controllers\Admin\IncomeExpenseController;
use App\Http\Controllers\Admin\InventoryAssetController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProjectInquiryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\ResultAnnouncementController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TodoController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\HomeworkController;
use App\Http\Controllers\Admin\StudyMaterialController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\MemberUserController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\StandardController;
use App\Http\Controllers\Admin\StudentAttendanceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Website\PricingController;
use App\Http\Controllers\Website\BookDemoController;
use App\Http\Controllers\Website\DemoAdminResponseController;
use App\Http\Controllers\Website\RegisterInstituteController;
use App\Http\Controllers\Webhook\BillingWebhookController;
use App\Http\Middleware\EnsureInstituteActive;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Services\Dashboard\InstituteDashboardService;
use App\Services\Dashboard\ParentDashboardService;
use App\Services\Dashboard\PlatformDashboardService;
use App\Services\Dashboard\TeacherDashboardService;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Support\Facades\Route;



Route::get('/', function (SubscriptionBillingService $billing) {
    return view('website.home', [
        'plans' => $billing->publicPlans(),
    ]);
})->name('home');

Route::get('/about', function () {
    return view('website.about');
})->name('about');

Route::get('/features', function () {
    return view('website.features');
})->name('features');

Route::get('/pricing', PricingController::class)->name('pricing');

Route::get('/privacy', function () {
    return view('website.privacy');
})->name('privacy');

Route::get('/register-institute', [RegisterInstituteController::class, 'create'])
    ->name('register-institute');
Route::post('/register-institute', [RegisterInstituteController::class, 'store'])
    ->name('register-institute.store');
Route::get('/register-institute/verify-otp', [RegisterInstituteController::class, 'showVerify'])
    ->name('register-institute.verify');
Route::post('/register-institute/verify-otp', [RegisterInstituteController::class, 'verify'])
    ->name('register-institute.verify.store');
Route::post('/register-institute/resend-otp', [RegisterInstituteController::class, 'resend'])
    ->name('register-institute.verify.resend');

Route::get('/book-demo', [BookDemoController::class, 'create'])
    ->name('book-demo');

Route::get('/book-demo/available-slots', [BookDemoController::class, 'availableSlots'])
    ->name('book-demo.available-slots');

Route::post('/book-demo', [BookDemoController::class, 'store'])
    ->name('book-demo.store');

Route::get('/book-demo/success', [BookDemoController::class, 'success'])
    ->name('book-demo.success');

Route::get('/demo/admin-confirm/{inquiry}', [DemoAdminResponseController::class, 'confirm'])
    ->middleware('signed')
    ->name('demo.admin.confirm');

Route::get('/demo/admin-decline/{inquiry}', [DemoAdminResponseController::class, 'decline'])
    ->middleware('signed')
    ->name('demo.admin.decline');

Route::get('/demo/open-admin/{inquiry}', [DemoAdminResponseController::class, 'openInAdmin'])
    ->name('demo.admin.open');

Route::post('/webhooks/billing/razorpay', [BillingWebhookController::class, 'razorpay'])
    ->name('webhooks.billing.razorpay');
Route::post('/webhooks/billing/stripe', [BillingWebhookController::class, 'stripe'])
    ->name('webhooks.billing.stripe');

Route::middleware(['auth', 'verified', EnsureInstituteActive::class, 'password.changed'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data/{widget}', [DashboardController::class, 'data'])
        ->whereIn('widget', array_unique(array_merge(
            PlatformDashboardService::CHART_WIDGETS,
            InstituteDashboardService::CHART_WIDGETS,
            TeacherDashboardService::CHART_WIDGETS,
            ParentDashboardService::CHART_WIDGETS,
        )))
        ->name('dashboard.data');

    Route::get('/onboarding/steps/{step}', [OnboardingController::class, 'show'])->name('onboarding.step');
    Route::post('/onboarding/steps/{step}/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
});

Route::middleware(['auth', EnsureInstituteActive::class, 'password.changed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', EnsureInstituteActive::class, 'password.changed'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::middleware(EnsureSuperAdmin::class)->group(function () {
            Route::get('/institutes', [InstituteController::class, 'index'])->name('institutes.index');
            Route::get('/institutes/{institute}', [InstituteController::class, 'show'])->name('institutes.show');
            Route::post('/institutes/{institute}/suspend', [InstituteController::class, 'suspend'])->name('institutes.suspend');
            Route::post('/institutes/{institute}/activate', [InstituteController::class, 'activate'])->name('institutes.activate');
            Route::post('/institutes/{institute}/assign-plan', [InstituteController::class, 'assignPlan'])->name('institutes.assign_plan');
            Route::post('/institutes/{institute}/extend-trial', [InstituteController::class, 'extendTrial'])->name('institutes.extend_trial');
            Route::post('/institutes/{institute}/overrides', [InstituteController::class, 'updateOverrides'])->name('institutes.overrides');

            Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
            Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
            Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
            Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
            Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
            Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

            Route::get('/module-help', [ModuleHelpController::class, 'index'])->name('module_help.index');
            Route::get('/module-help/add', [ModuleHelpController::class, 'create'])->name('module_help.create');
            Route::post('/module-help', [ModuleHelpController::class, 'store'])->name('module_help.store');
            Route::get('/module-help/{module_help}/edit', [ModuleHelpController::class, 'edit'])->name('module_help.edit');
            Route::put('/module-help/{module_help}', [ModuleHelpController::class, 'update'])->name('module_help.update');
            Route::delete('/module-help/{module_help}', [ModuleHelpController::class, 'destroy'])->name('module_help.destroy');

            Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email_templates.index');
            Route::get('/email-templates/add', [EmailTemplateController::class, 'create'])->name('email_templates.create');
            Route::post('/email-templates', [EmailTemplateController::class, 'store'])->name('email_templates.store');
            Route::get('/email-templates/{key}', [EmailTemplateController::class, 'show'])->name('email_templates.show');
            Route::get('/email-templates/{key}/edit', [EmailTemplateController::class, 'edit'])->name('email_templates.edit');
            Route::put('/email-templates/{key}', [EmailTemplateController::class, 'update'])->name('email_templates.update');
            Route::post('/email-templates/{key}/reset', [EmailTemplateController::class, 'reset'])->name('email_templates.reset');
            Route::delete('/email-templates/{key}', [EmailTemplateController::class, 'destroy'])->name('email_templates.destroy');
        });

        // Module Help — institute runtime (lazy show; not platform-only)
        Route::get('/module-help/show/{moduleKey}', [ModuleHelpController::class, 'show'])
            ->where('moduleKey', '[a-z0-9_]+')
            ->name('module_help.show');

        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');

        Route::get('/project-inquiries', [ProjectInquiryController::class, 'index'])
            ->name('project-inquiries.index');

        // Institute-scoped cascading dropdown lookups (platform: pass institute_id)
        Route::prefix('lookups')->name('lookups.')->group(function () {
            Route::get('/branches', [InstituteLookupController::class, 'branches'])->name('branches');
            Route::get('/roles', [InstituteLookupController::class, 'roles'])->name('roles');
            Route::get('/managers', [InstituteLookupController::class, 'managers'])->name('managers');
            Route::get('/standards', [InstituteLookupController::class, 'standards'])->name('standards');
            Route::get('/subjects', [InstituteLookupController::class, 'subjects'])->name('subjects');
            Route::get('/teachers', [InstituteLookupController::class, 'teachers'])->name('teachers');
            Route::get('/batches', [InstituteLookupController::class, 'batches'])->name('batches');
            Route::get('/next-roll-no', [InstituteLookupController::class, 'nextRollNo'])->name('next-roll-no');
            Route::get('/students', [InstituteLookupController::class, 'students'])->name('students');
            Route::get('/exams', [InstituteLookupController::class, 'exams'])->name('exams');
        });

        // Master — design-only (Route::view, no controllers)
        Route::prefix('master')->name('master.')->group(function () {
            Route::get('/branch', [BranchController::class, 'index'])->name('branch');
            Route::get('/branch/add', [BranchController::class, 'create'])->name('branch.add');
            Route::post('/branch', [BranchController::class, 'store'])->name('branch.store');
            Route::get('/branch/{branch}/edit', [BranchController::class, 'edit'])->name('branch.edit');
            Route::put('/branch/{branch}', [BranchController::class, 'update'])->name('branch.update');
            Route::delete('/branch/{branch}', [BranchController::class, 'destroy'])->name('branch.destroy');
            Route::post('/branch/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branch.toggle');

            Route::get('/batch', [BatchController::class, 'index'])->name('batch');
            Route::get('/batch/add', [BatchController::class, 'create'])->name('batch.add');
            Route::post('/batch', [BatchController::class, 'store'])->name('batch.store');
            Route::get('/batch/{batch}', [BatchController::class, 'show'])->name('batch.show');
            Route::get('/batch/{batch}/edit', [BatchController::class, 'edit'])->name('batch.edit');
            Route::put('/batch/{batch}', [BatchController::class, 'update'])->name('batch.update');
            Route::delete('/batch/{batch}', [BatchController::class, 'destroy'])->name('batch.destroy');
            Route::post('/batch/{batch}/toggle-status', [BatchController::class, 'toggleStatus'])->name('batch.toggle');
            Route::get('/teacher', [TeacherController::class, 'index'])->name('teacher');
            Route::get('/teacher/add', [TeacherController::class, 'create'])->name('teacher.add');
            Route::post('/teacher', [TeacherController::class, 'store'])->name('teacher.store');
            Route::get('/teacher/{teacher}', [TeacherController::class, 'show'])->name('teacher.show');
            Route::get('/teacher/{teacher}/edit', [TeacherController::class, 'edit'])->name('teacher.edit');
            Route::put('/teacher/{teacher}', [TeacherController::class, 'update'])->name('teacher.update');
            Route::delete('/teacher/{teacher}', [TeacherController::class, 'destroy'])->name('teacher.destroy');
            Route::post('/teacher/{teacher}/toggle-status', [TeacherController::class, 'toggleStatus'])->name('teacher.toggle');
            Route::get('/subject', [SubjectController::class, 'index'])->name('subject');
            Route::post('/subject', [SubjectController::class, 'store'])->name('subject.store');
            Route::put('/subject/{subject}', [SubjectController::class, 'update'])->name('subject.update');
            Route::delete('/subject/{subject}', [SubjectController::class, 'destroy'])->name('subject.destroy');
            Route::post('/subject/{subject}/toggle-status', [SubjectController::class, 'toggleStatus'])->name('subject.toggle');

            Route::get('/standard', [StandardController::class, 'index'])->name('standard');
            Route::post('/standard', [StandardController::class, 'store'])->name('standard.store');
            Route::put('/standard/{standard}', [StandardController::class, 'update'])->name('standard.update');
            Route::delete('/standard/{standard}', [StandardController::class, 'destroy'])->name('standard.destroy');
            Route::post('/standard/{standard}/toggle-status', [StandardController::class, 'toggleStatus'])->name('standard.toggle');

            Route::get('/income-expense', [IeCategoryController::class, 'index'])->name('income_expense');
            Route::post('/income-expense', [IeCategoryController::class, 'store'])->name('income_expense.store');
            Route::put('/income-expense/{ieCategory}', [IeCategoryController::class, 'update'])->name('income_expense.update');
            Route::delete('/income-expense/{ieCategory}', [IeCategoryController::class, 'destroy'])->name('income_expense.destroy');
            Route::post('/income-expense/{ieCategory}/toggle-status', [IeCategoryController::class, 'toggleStatus'])->name('income_expense.toggle');

            Route::get('/assets-category', [AssetCategoryController::class, 'index'])->name('assets_category');
            Route::post('/assets-category', [AssetCategoryController::class, 'store'])->name('assets_category.store');
            Route::put('/assets-category/{assetCategory}', [AssetCategoryController::class, 'update'])->name('assets_category.update');
            Route::delete('/assets-category/{assetCategory}', [AssetCategoryController::class, 'destroy'])->name('assets_category.destroy');
            Route::post('/assets-category/{assetCategory}/toggle-status', [AssetCategoryController::class, 'toggleStatus'])->name('assets_category.toggle');

            Route::get('/roles', [RoleController::class, 'index'])->name('roles');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
            Route::get('/roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
            Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions.sync');

            Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions');
            Route::post('/permissions/sync', [PermissionController::class, 'sync'])->name('permissions.sync');
        });

        Route::get('/profile', [AccountProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [AccountProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AccountProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('/classes', [ClassesBrandingController::class, 'edit'])->name('classes');
        Route::put('/classes', [ClassesBrandingController::class, 'update'])->name('classes.update');
        Route::put('/classes/theme', [ClassesBrandingController::class, 'updateTheme'])->name('classes.theme.update');

        Route::get('/student', [StudentController::class, 'index'])->name('student');
        Route::get('/student/add', [StudentController::class, 'create'])->name('student.add');
        Route::post('/student', [StudentController::class, 'store'])->name('student.store');
        Route::get('/student/{student}', [StudentController::class, 'show'])->name('student.show');
        Route::get('/student/{student}/edit', [StudentController::class, 'edit'])->name('student.edit');
        Route::put('/student/{student}', [StudentController::class, 'update'])->name('student.update');
        Route::post('/student/{student}/toggle-status', [StudentController::class, 'toggleStatus'])->name('student.toggle');
        Route::get('/student/{student}/receipt', [StudentController::class, 'receipt'])->name('student.receipt');
        Route::get('/student/{student}/receipt-pdf', [StudentController::class, 'receiptPdf'])->name('student.receipt_pdf');

        Route::get('/inquiry', [InquiryController::class, 'index'])->name('inquiry');
        Route::get('/inquiry/add', [InquiryController::class, 'create'])->name('inquiry.add');
        Route::post('/inquiry', [InquiryController::class, 'store'])->name('inquiry.store');
        Route::get('/inquiry/{inquiry}/edit', [InquiryController::class, 'edit'])->name('inquiry.edit');
        Route::put('/inquiry/{inquiry}', [InquiryController::class, 'update'])->name('inquiry.update');
        Route::delete('/inquiry/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiry.destroy');
        Route::get('/inquiry/{inquiry}/json', [InquiryController::class, 'show'])->name('inquiry.show');
        Route::get('/inquiry/{inquiry}/receipt', [InquiryController::class, 'receipt'])->name('inquiry.receipt');
        Route::get('/inquiry/{inquiry}/receipt-pdf', [InquiryController::class, 'receiptPdf'])->name('inquiry.receipt_pdf');
        Route::post('/inquiry/{inquiry}/follow-up', [InquiryController::class, 'followUp'])->name('inquiry.follow_up');
        Route::get('/inquiry/{inquiry}/convert', [InquiryController::class, 'convertForm'])->name('inquiry.convert');
        Route::post('/inquiry/{inquiry}/convert', [InquiryController::class, 'convert'])->name('inquiry.convert.store');

        Route::get('/fees', [FeeController::class, 'index'])->name('fees');
        Route::get('/fees/add', [FeeController::class, 'create'])->name('fees.add');
        Route::post('/fees', [FeeController::class, 'store'])->name('fees.store');
        Route::get('/fees/student-summary', [FeeController::class, 'studentSummary'])->name('fees.student_summary');
        Route::get('/fees/follow-up', [FeeController::class, 'followUpIndex'])->name('fees.follow_up');
        Route::post('/fees/follow-up/{invoice}', [FeeController::class, 'followUpStore'])->name('fees.follow_up.store');
        Route::get('/fees/follow-up-export-pdf', [FeeController::class, 'followUpExportPdf'])->name('fees.follow_up.export_pdf');
        Route::get('/fees/{payment}/receipt', [FeeController::class, 'receipt'])->name('fees.receipt');
        Route::get('/fees/{payment}/receipt-pdf', [FeeController::class, 'receiptPdf'])->name('fees.receipt_pdf');
        Route::delete('/fees/{payment}', [FeeController::class, 'destroy'])->name('fees.destroy');

        Route::get('/users', [MemberUserController::class, 'index'])->name('users');
        Route::get('/users/{user}/permissions', [UserPermissionController::class, 'permissions'])->name('users.permissions');
        Route::post('/users/{user}/permissions', [UserPermissionController::class, 'syncPermissions'])->name('users.permissions.sync');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff');
        Route::get('/staff/add', [StaffController::class, 'create'])->name('staff.add');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::post('/staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle');
        Route::get('/follow-up', [FollowUpController::class, 'index'])->name('follow_up');
        Route::post('/follow-up/{inquiry}', [FollowUpController::class, 'store'])->name('follow_up.store');
        Route::get('/to-do-task', [TodoController::class, 'index'])->name('to_do_task');
        Route::get('/to-do-task/add', [TodoController::class, 'create'])->name('to_do_task.add');
        Route::get('/to-do-task/{todo}/edit', [TodoController::class, 'edit'])->name('to_do_task.edit');
        Route::post('/to-do-task', [TodoController::class, 'store'])->name('to_do_task.store');
        Route::put('/to-do-task/{todo}', [TodoController::class, 'update'])->name('to_do_task.update');
        Route::delete('/to-do-task/{todo}', [TodoController::class, 'destroy'])->name('to_do_task.destroy');
        Route::post('/to-do-task/{todo}/approve', [TodoController::class, 'approve'])->name('to_do_task.approve');
        Route::post('/to-do-task/{todo}/reject', [TodoController::class, 'reject'])->name('to_do_task.reject');
        Route::post('/to-do-task/carry-forward', [TodoController::class, 'carryForward'])->name('to_do_task.carry_forward');
        Route::get('/study-material', [StudyMaterialController::class, 'index'])->name('study_material');
        Route::get('/study-material/add', [StudyMaterialController::class, 'create'])->name('study_material.add');
        Route::get('/study-material/{studyMaterial}/edit', [StudyMaterialController::class, 'edit'])->name('study_material.edit');
        Route::get('/study-material/{studyMaterial}/download', [StudyMaterialController::class, 'download'])->name('study_material.download');
        Route::post('/study-material', [StudyMaterialController::class, 'store'])->name('study_material.store');
        Route::put('/study-material/{studyMaterial}', [StudyMaterialController::class, 'update'])->name('study_material.update');
        Route::delete('/study-material/{studyMaterial}', [StudyMaterialController::class, 'destroy'])->name('study_material.destroy');
        Route::get('/income-expense', [IncomeExpenseController::class, 'index'])->name('income_expense');
        Route::post('/income-expense', [IncomeExpenseController::class, 'store'])->name('income_expense.store');
        Route::delete('/income-expense/{incomeExpense}', [IncomeExpenseController::class, 'destroy'])->name('income_expense.destroy');
        Route::get('/inventory-assets', [InventoryAssetController::class, 'index'])->name('inventory_assets');
        Route::get('/inventory-assets/add', [InventoryAssetController::class, 'create'])->name('inventory_assets.add');
        Route::post('/inventory-assets', [InventoryAssetController::class, 'store'])->name('inventory_assets.store');
        Route::get('/inventory-assets/{inventoryAsset}/edit', [InventoryAssetController::class, 'edit'])->name('inventory_assets.edit');
        Route::put('/inventory-assets/{inventoryAsset}', [InventoryAssetController::class, 'update'])->name('inventory_assets.update');
        Route::delete('/inventory-assets/{inventoryAsset}', [InventoryAssetController::class, 'destroy'])->name('inventory_assets.destroy');
        Route::get('/homework', [HomeworkController::class, 'index'])->name('homework');
        Route::get('/homework/add', [HomeworkController::class, 'create'])->name('homework.add');
        Route::get('/homework/{homework}/edit', [HomeworkController::class, 'edit'])->name('homework.edit');
        Route::get('/homework/{homework}/download', [HomeworkController::class, 'download'])->name('homework.download');
        Route::post('/homework', [HomeworkController::class, 'store'])->name('homework.store');
        Route::put('/homework/{homework}', [HomeworkController::class, 'update'])->name('homework.update');
        Route::delete('/homework/{homework}', [HomeworkController::class, 'destroy'])->name('homework.destroy');
        Route::get('/holiday', [HolidayController::class, 'index'])->name('holiday');
        Route::post('/holiday', [HolidayController::class, 'store'])->name('holiday.store');
        Route::put('/holiday/{holiday}', [HolidayController::class, 'update'])->name('holiday.update');
        Route::delete('/holiday/{holiday}', [HolidayController::class, 'destroy'])->name('holiday.destroy');
        Route::get('/staff-leave-request', [LeaveRequestController::class, 'staffIndex'])->name('staff_leave_request');
        Route::post('/staff-leave-request', [LeaveRequestController::class, 'storeStaff'])->name('staff_leave_request.store');
        Route::post('/staff-leave-request/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('staff_leave_request.approve');
        Route::post('/staff-leave-request/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('staff_leave_request.reject');
        Route::get('/student-leave-request', [LeaveRequestController::class, 'studentIndex'])->name('student_leave_request');
        Route::post('/student-leave-request', [LeaveRequestController::class, 'storeStudent'])->name('student_leave_request.store');
        Route::post('/student-leave-request/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('student_leave_request.approve');
        Route::post('/student-leave-request/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('student_leave_request.reject');
        Route::get('/exam', [ExamController::class, 'index'])->name('exam');
        Route::get('/exam/add', [ExamController::class, 'create'])->name('exam.add');
        Route::get('/exam/{exam}/edit', [ExamController::class, 'edit'])->name('exam.edit');
        Route::post('/exam', [ExamController::class, 'store'])->name('exam.store');
        Route::put('/exam/{exam}', [ExamController::class, 'update'])->name('exam.update');
        Route::delete('/exam/{exam}', [ExamController::class, 'destroy'])->name('exam.destroy');
        Route::get('/exam/{exam}/result-sheet', [ExamController::class, 'resultSheet'])->name('exam.result_sheet');
        Route::get('/exam/{exam}/download', [ExamController::class, 'download'])->name('exam.download');
        Route::get('/notice', [NoticeController::class, 'index'])->name('notice');
        Route::get('/notice/add', [NoticeController::class, 'create'])->name('notice.add');
        Route::get('/notice/{notice}/edit', [NoticeController::class, 'edit'])->name('notice.edit');
        Route::post('/notice', [NoticeController::class, 'store'])->name('notice.store');
        Route::put('/notice/{notice}', [NoticeController::class, 'update'])->name('notice.update');
        Route::delete('/notice/{notice}', [NoticeController::class, 'destroy'])->name('notice.destroy');
        Route::post('/notice/{notice}/toggle-status', [NoticeController::class, 'toggleStatus'])->name('notice.toggle_status');
        Route::get('/attendance/student', [StudentAttendanceController::class, 'index'])->name('attendance.student');
        Route::get('/attendance/student/{batch}/mark', [StudentAttendanceController::class, 'mark'])->name('attendance.student.mark');
        Route::get('/attendance/student/{batch}/data', [StudentAttendanceController::class, 'data'])->name('attendance.student.data');
        Route::get('/attendance/reports/batch/{batch}', [StudentAttendanceController::class, 'batchReport'])->name('attendance.report.batch');
        Route::get('/attendance/reports/student/{student}', [StudentAttendanceController::class, 'studentReport'])->name('attendance.report.student');
        Route::post('/attendance/student/{batch}', [StudentAttendanceController::class, 'store'])->name('attendance.student.store');
        Route::get('/attendance/teacher', [StaffAttendanceController::class, 'index'])->name('attendance.teacher');
        Route::get('/attendance/teacher/mark', [StaffAttendanceController::class, 'mark'])->name('attendance.teacher.mark');
        Route::get('/attendance/teacher/data', [StaffAttendanceController::class, 'data'])->name('attendance.teacher.data');
        Route::get('/attendance/reports/teacher/{teacher}', [StaffAttendanceController::class, 'teacherReport'])->name('attendance.report.teacher');
        Route::get('/attendance/reports/staff/{staff}', [StaffAttendanceController::class, 'staffReport'])->name('attendance.report.staff');
        Route::post('/attendance/teacher', [StaffAttendanceController::class, 'store'])->name('attendance.teacher.store');
        Route::get('/gst-invoice', [GstInvoiceController::class, 'index'])->name('gst_invoice');
        Route::post('/gst-invoice/generate/{payment}', [GstInvoiceController::class, 'generate'])->name('gst_invoice.generate');
        Route::get('/gst-invoice/{gstInvoice}', [GstInvoiceController::class, 'show'])->name('gst_invoice.show');
        Route::get('/result-announcement', [ResultAnnouncementController::class, 'index'])->name('result_announcement');
        Route::get('/result-announcement/add', [ResultAnnouncementController::class, 'create'])->name('result_announcement.add');
        Route::get('/result-announcement/{resultAnnouncement}/edit', [ResultAnnouncementController::class, 'edit'])->name('result_announcement.edit');
        Route::post('/result-announcement', [ResultAnnouncementController::class, 'store'])->name('result_announcement.store');
        Route::get('/result-announcement/{resultAnnouncement}/preview', [ResultAnnouncementController::class, 'preview'])->name('result_announcement.preview');
        Route::get('/result-announcement/{resultAnnouncement}/export-pdf', [ResultAnnouncementController::class, 'exportPdf'])->name('result_announcement.export_pdf');
        Route::delete('/result-announcement/{resultAnnouncement}', [ResultAnnouncementController::class, 'destroy'])->name('result_announcement.destroy');

        Route::get('/project-inquiries/create', [ProjectInquiryController::class, 'create'])
            ->name('project-inquiries.create');

        Route::post('/project-inquiries', [ProjectInquiryController::class, 'store'])
            ->name('project-inquiries.store');

        Route::get('/project-inquiries/{projectInquiry}', [ProjectInquiryController::class, 'show'])
            ->name('project-inquiries.show');

        Route::put('/project-inquiries/{projectInquiry}', [ProjectInquiryController::class, 'update'])
            ->name('project-inquiries.update');

        Route::post('/project-inquiries/{projectInquiry}/convert', [ProjectInquiryController::class, 'convert'])
            ->name('project-inquiries.convert');

        Route::get('/demo-calendar', [DemoCalendarController::class, 'index'])
            ->name('demo-calendar.index');

        Route::get('/settings', [SettingsController::class, 'index'])
            ->name('settings.index');
        Route::post('/settings/email', [SettingsController::class, 'updateEmail'])
            ->name('settings.email.update');
        Route::post('/settings/email/test', [SettingsController::class, 'sendTestEmail'])
            ->name('settings.email.test');
        Route::post('/settings/google', [SettingsController::class, 'updateGoogle'])
            ->name('settings.google.update');
        Route::post('/settings/google/disconnect', [SettingsController::class, 'disconnectGoogle'])
            ->name('settings.google.disconnect');
        Route::post('/settings/website', [SettingsController::class, 'updateWebsite'])
            ->name('settings.website.update');

        Route::get('/google-calendar', [GoogleCalendarController::class, 'index'])
            ->name('google-calendar.index');

        Route::get('/google-calendar/connect', [GoogleCalendarController::class, 'connect'])
            ->name('google-calendar.connect');
        Route::get('/demo-availability', [DemoAvailabilityController::class, 'index'])
            ->name('demo-availability.index');

        Route::post('/demo-availability', [DemoAvailabilityController::class, 'store'])
            ->name('demo-availability.store');
        Route::put('/demo-availability/{demoAvailability}', [DemoAvailabilityController::class, 'update'])
            ->name('demo-availability.update');
        Route::delete('/demo-availability/{demoAvailability}', [DemoAvailabilityController::class, 'destroy'])
            ->name('demo-availability.destroy');

        Route::get('/google-calendar/callback', [GoogleCalendarController::class, 'callback'])
            ->name('google-calendar.callback');

        Route::get('/google-calendar-test', [GoogleCalendarTestController::class, 'index'])
            ->name('google-calendar-test.index');
        Route::post('/google-calendar-test', [GoogleCalendarTestController::class, 'store'])
            ->name('google-calendar-test.store');

        // CSV exports (module pages — not a separate Reports menu)
        Route::get('/export/students', [ExportController::class, 'students'])->name('export.students');
        Route::get('/export/teachers', [ExportController::class, 'teachers'])->name('export.teachers');
        Route::get('/export/staff', [ExportController::class, 'staff'])->name('export.staff');
        Route::get('/export/inquiries', [ExportController::class, 'inquiries'])->name('export.inquiries');
        Route::get('/export/follow-ups', [ExportController::class, 'followUps'])->name('export.follow_ups');
        Route::get('/export/todos', [ExportController::class, 'todos'])->name('export.todos');
        Route::get('/export/roles', [ExportController::class, 'roles'])->name('export.roles');
        Route::get('/export/batches/{batch}/students', [ExportController::class, 'batchStudents'])->name('export.batch_students');
        Route::get('/export/teachers/{teacher}/attendance', [ExportController::class, 'teacherAttendance'])->name('export.teacher_attendance');
        Route::get('/export/teachers/{teacher}/login-history', [ExportController::class, 'teacherLoginHistory'])->name('export.teacher_login');
        Route::get('/export/teachers/{teacher}/homework', [ExportController::class, 'teacherHomework'])->name('export.teacher_homework');
        Route::get('/export/staff/{staff}/attendance', [ExportController::class, 'staffAttendance'])->name('export.staff_attendance');
        Route::get('/export/staff/{staff}/login-history', [ExportController::class, 'staffLoginHistory'])->name('export.staff_login');
        Route::get('/export/staff/{staff}/todos', [ExportController::class, 'staffTodos'])->name('export.staff_todos');
        Route::get('/export/students/{student}/attendance', [ExportController::class, 'studentAttendance'])->name('export.student_attendance');
        Route::get('/export/students/{student}/results', [ExportController::class, 'studentResults'])->name('export.student_results');
        Route::get('/export/batches/{batch}/attendance-report', [ExportController::class, 'batchAttendanceReport'])->name('export.batch_attendance');

        // Reports hub (kept for deep links / print views; not in sidebar)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/reports/todo', [ReportController::class, 'todo'])->name('reports.todo');
        Route::get('/reports/batch', [ReportController::class, 'batch'])->name('reports.batch');
        Route::get('/reports/student', [ReportController::class, 'student'])->name('reports.student');
        Route::get('/reports/roles', [ReportController::class, 'roles'])->name('reports.roles');
    });

require __DIR__ . '/auth.php';
