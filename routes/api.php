<?php

use App\Http\Controllers\Api\AvailableSlotsController;
use App\Http\Controllers\Api\V1\AssetCategoryController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BatchController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\ExportController;
use App\Http\Controllers\Api\V1\FeeController;
use App\Http\Controllers\Api\V1\GstInvoiceController;
use App\Http\Controllers\Api\V1\IncomeExpenseController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\HolidayController;
use App\Http\Controllers\Api\V1\HomeworkController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\NoticeController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ResultAnnouncementController;
use App\Http\Controllers\Api\V1\TodoController;
use App\Http\Controllers\Api\V1\StaffAttendanceController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StandardController;
use App\Http\Controllers\Api\V1\StudyMaterialController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\InventoryAssetController;
use App\Http\Controllers\Api\V1\StudentAttendanceController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/demo/available-slots',
    [AvailableSlotsController::class, 'index']
)->name('api.demo.available-slots');

/*
|--------------------------------------------------------------------------
| Mobile API v1 (Sanctum bearer tokens)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    Route::middleware([
        'mobile.bearer',
        'auth:sanctum',
        'permissions_team',
        'institute_active',
        'mobile.api',
        'throttle:120,1',
    ])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
        // POST alias: some clients/proxies drop PATCH bodies.
        Route::match(['patch', 'post'], '/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
        Route::get('/dashboard/data/{widget}', [DashboardController::class, 'widget'])
            ->where('widget', '[a-z_]+')
            ->name('dashboard.widget');

        Route::get('/branch', [BranchController::class, 'index'])->name('branch.index');

        Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
        Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
        Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
        Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
        Route::post('/batches/{batch}/status', [BatchController::class, 'status'])->name('batches.status');
        Route::get('/batches/{batch}/students', [BatchController::class, 'students'])->name('batches.students');
        Route::get('/batches/{batch}/attendance', [BatchController::class, 'attendance'])->name('batches.attendance');
        Route::get('/batches/{batch}/attendance-report', [BatchController::class, 'attendanceReport'])->name('batches.attendance-report');
        Route::get('/batches/{batch}/exams', [BatchController::class, 'exams'])->name('batches.exams');
        Route::get('/batches/{batch}/homework', [BatchController::class, 'homework'])->name('batches.homework');
        Route::get('/batches/{batch}/notices', [BatchController::class, 'notices'])->name('batches.notices');

        Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('/teachers/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');
        Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
        Route::post('/teachers/{teacher}/status', [TeacherController::class, 'status'])->name('teachers.status');
        Route::get('/teachers/{teacher}/batches', [TeacherController::class, 'batches'])->name('teachers.batches');
        Route::get('/teachers/{teacher}/login-history', [TeacherController::class, 'loginHistory'])->name('teachers.login-history');
        Route::get('/teachers/{teacher}/attendance', [TeacherController::class, 'attendance'])->name('teachers.attendance');
        Route::get('/teachers/{teacher}/assigned-work', [TeacherController::class, 'assignedWork'])->name('teachers.assigned-work');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/roles', [StaffController::class, 'roles'])->name('staff.roles');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::post('/staff/{staff}/status', [StaffController::class, 'status'])->name('staff.status');
        Route::get('/staff/{staff}/attendance', [StaffController::class, 'attendance'])->name('staff.attendance');
        Route::get('/staff/{staff}/login-history', [StaffController::class, 'loginHistory'])->name('staff.login-history');
        Route::get('/staff/{staff}/assigned-work', [StaffController::class, 'assignedWork'])->name('staff.assigned-work');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
        Route::post('/users/{user}/permissions', [UserController::class, 'syncPermissions'])->name('users.permissions.sync');

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::post('/students/{student}/status', [StudentController::class, 'status'])->name('students.status');
        Route::get('/students/{student}/receipt', [StudentController::class, 'receipt'])->name('students.receipt');
        Route::get('/students/{student}/receipt-pdf', [StudentController::class, 'receiptPdf'])->name('students.receipt-pdf');

        Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::post('/inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
        Route::get('/inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
        Route::put('/inquiries/{inquiry}', [InquiryController::class, 'update'])->name('inquiries.update');
        Route::delete('/inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiries.destroy');
        Route::get('/inquiries/{inquiry}/receipt', [InquiryController::class, 'receipt'])->name('inquiries.receipt');
        Route::get('/inquiries/{inquiry}/receipt-pdf', [InquiryController::class, 'receiptPdf'])->name('inquiries.receipt-pdf');
        Route::get('/inquiries/{inquiry}/convert', [InquiryController::class, 'convertForm'])->name('inquiries.convert-form');
        Route::post('/inquiries/{inquiry}/convert', [InquiryController::class, 'convert'])->name('inquiries.convert');

        Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
        Route::post('/follow-ups/{inquiry}', [FollowUpController::class, 'store'])->name('follow-ups.store');

        Route::get('/attendance/student', [StudentAttendanceController::class, 'index'])->name('attendance.student.index');
        Route::post('/attendance/student/{batch}', [StudentAttendanceController::class, 'store'])->name('attendance.student.store');
        Route::get('/attendance/student/reports/batch/{batch}', [StudentAttendanceController::class, 'batchReport'])->name('attendance.student.reports.batch');
        Route::get('/attendance/student/reports/student/{student}', [StudentAttendanceController::class, 'studentReport'])->name('attendance.student.reports.student');

        Route::get('/attendance/teacher', [StaffAttendanceController::class, 'index'])->name('attendance.teacher.index');
        Route::post('/attendance/teacher', [StaffAttendanceController::class, 'store'])->name('attendance.teacher.store');
        Route::get('/attendance/teacher/reports/teacher/{teacher}', [StaffAttendanceController::class, 'teacherReport'])->name('attendance.teacher.reports.teacher');
        Route::get('/attendance/teacher/reports/staff/{staff}', [StaffAttendanceController::class, 'staffReport'])->name('attendance.teacher.reports.staff');

        Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
        Route::put('/exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
        Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');
        Route::get('/exams/{exam}/download', [ExamController::class, 'download'])->name('exams.download');
        Route::get('/exams/{exam}/result-sheet', [ExamController::class, 'resultSheet'])->name('exams.result-sheet');

        Route::get('/result-announcements', [ResultAnnouncementController::class, 'index'])->name('result-announcements.index');
        Route::post('/result-announcements', [ResultAnnouncementController::class, 'store'])->name('result-announcements.store');
        Route::get('/result-announcements/{resultAnnouncement}', [ResultAnnouncementController::class, 'show'])->name('result-announcements.show');
        Route::get('/result-announcements/{resultAnnouncement}/preview', [ResultAnnouncementController::class, 'preview'])->name('result-announcements.preview');
        Route::get('/result-announcements/{resultAnnouncement}/export-pdf', [ResultAnnouncementController::class, 'exportPdf'])->name('result-announcements.export-pdf');
        Route::delete('/result-announcements/{resultAnnouncement}', [ResultAnnouncementController::class, 'destroy'])->name('result-announcements.destroy');

        Route::get('/homework', [HomeworkController::class, 'index'])->name('homework.index');
        Route::post('/homework', [HomeworkController::class, 'store'])->name('homework.store');
        Route::get('/homework/{homework}', [HomeworkController::class, 'show'])->name('homework.show');
        Route::put('/homework/{homework}', [HomeworkController::class, 'update'])->name('homework.update');
        Route::delete('/homework/{homework}', [HomeworkController::class, 'destroy'])->name('homework.destroy');
        Route::get('/homework/{homework}/download', [HomeworkController::class, 'download'])->name('homework.download');

        Route::get('/study-materials', [StudyMaterialController::class, 'index'])->name('study-materials.index');
        Route::post('/study-materials', [StudyMaterialController::class, 'store'])->name('study-materials.store');
        Route::get('/study-materials/{studyMaterial}', [StudyMaterialController::class, 'show'])->name('study-materials.show');
        Route::put('/study-materials/{studyMaterial}', [StudyMaterialController::class, 'update'])->name('study-materials.update');
        Route::delete('/study-materials/{studyMaterial}', [StudyMaterialController::class, 'destroy'])->name('study-materials.destroy');
        Route::get('/study-materials/{studyMaterial}/download', [StudyMaterialController::class, 'download'])->name('study-materials.download');

        Route::get('/notices', [NoticeController::class, 'index'])->name('notices.index');
        Route::post('/notices', [NoticeController::class, 'store'])->name('notices.store');
        Route::get('/notices/{notice}', [NoticeController::class, 'show'])->name('notices.show');
        Route::put('/notices/{notice}', [NoticeController::class, 'update'])->name('notices.update');
        Route::delete('/notices/{notice}', [NoticeController::class, 'destroy'])->name('notices.destroy');
        Route::post('/notices/{notice}/status', [NoticeController::class, 'status'])->name('notices.status');

        Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
        Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
        Route::put('/holidays/{holiday}', [HolidayController::class, 'update'])->name('holidays.update');
        Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');

        Route::get('/todos', [TodoController::class, 'index'])->name('todos.index');
        Route::post('/todos', [TodoController::class, 'store'])->name('todos.store');
        Route::post('/todos/carry-forward', [TodoController::class, 'carryForward'])->name('todos.carry-forward');
        Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
        Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
        Route::post('/todos/{todo}/approve', [TodoController::class, 'approve'])->name('todos.approve');
        Route::post('/todos/{todo}/reject', [TodoController::class, 'reject'])->name('todos.reject');

        Route::get('/leave-requests/staff', [LeaveRequestController::class, 'staffIndex'])->name('leave-requests.staff.index');
        Route::post('/leave-requests/staff', [LeaveRequestController::class, 'storeStaff'])->name('leave-requests.staff.store');
        Route::get('/leave-requests/student', [LeaveRequestController::class, 'studentIndex'])->name('leave-requests.student.index');
        Route::post('/leave-requests/student', [LeaveRequestController::class, 'storeStudent'])->name('leave-requests.student.store');
        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
        Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');

        Route::get('/export/students', [ExportController::class, 'students'])->name('export.students');
        Route::get('/export/students/{student}/attendance', [ExportController::class, 'studentAttendance'])->name('export.students.attendance');
        Route::get('/export/students/{student}/results', [ExportController::class, 'studentResults'])->name('export.students.results');
        Route::get('/export/inquiries', [ExportController::class, 'inquiries'])->name('export.inquiries');
        Route::get('/export/follow-ups', [ExportController::class, 'followUps'])->name('export.follow-ups');
        Route::get('/export/todos', [ExportController::class, 'todos'])->name('export.todos');
        Route::get('/export/batches/{batch}/students', [ExportController::class, 'batchStudents'])->name('export.batches.students');
        Route::get('/export/batches/{batch}/attendance-report', [ExportController::class, 'batchAttendanceReport'])->name('export.batches.attendance-report');
        Route::get('/export/teachers/{teacher}/attendance', [ExportController::class, 'teacherAttendance'])->name('export.teachers.attendance');
        Route::get('/export/teachers/{teacher}/homework', [ExportController::class, 'teacherHomework'])->name('export.teachers.homework');
        Route::get('/export/staff/{staff}/attendance', [ExportController::class, 'staffAttendance'])->name('export.staff.attendance');

        Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
        Route::post('/fees', [FeeController::class, 'store'])->name('fees.store');
        Route::get('/fees/student-summary', [FeeController::class, 'studentSummary'])->name('fees.student-summary');
        Route::get('/fees/follow-ups', [FeeController::class, 'followUps'])->name('fees.follow-ups.index');
        Route::get('/fees/follow-ups/export-pdf', [FeeController::class, 'followUpExportPdf'])->name('fees.follow-ups.export-pdf');
        Route::post('/fees/follow-ups/{invoice}', [FeeController::class, 'followUpStore'])->name('fees.follow-ups.store');
        Route::get('/fees/{payment}/receipt', [FeeController::class, 'receipt'])->name('fees.receipt');
        Route::get('/fees/{payment}/receipt-pdf', [FeeController::class, 'receiptPdf'])->name('fees.receipt-pdf');
        Route::delete('/fees/{payment}', [FeeController::class, 'destroy'])->name('fees.destroy');

        Route::get('/gst-invoices', [GstInvoiceController::class, 'index'])->name('gst-invoices.index');
        Route::post('/gst-invoices/generate/{payment}', [GstInvoiceController::class, 'generate'])->name('gst-invoices.generate');
        Route::get('/gst-invoices/{gstInvoice}', [GstInvoiceController::class, 'show'])->name('gst-invoices.show');

        Route::get('/income-expense', [IncomeExpenseController::class, 'index'])->name('income-expense.index');
        Route::post('/income-expense', [IncomeExpenseController::class, 'store'])->name('income-expense.store');
        Route::delete('/income-expense/{incomeExpense}', [IncomeExpenseController::class, 'destroy'])->name('income-expense.destroy');
        Route::get('/income-expense-categories', [IncomeExpenseController::class, 'categories'])->name('income-expense-categories.index');
        Route::post('/income-expense-categories', [IncomeExpenseController::class, 'storeCategory'])->name('income-expense-categories.store');
        Route::put('/income-expense-categories/{ieCategory}', [IncomeExpenseController::class, 'updateCategory'])->name('income-expense-categories.update');
        Route::post('/income-expense-categories/{ieCategory}/status', [IncomeExpenseController::class, 'statusCategory'])->name('income-expense-categories.status');

        Route::get('/inventory-assets', [InventoryAssetController::class, 'index'])->name('inventory-assets.index');
        Route::post('/inventory-assets', [InventoryAssetController::class, 'store'])->name('inventory-assets.store');
        Route::put('/inventory-assets/{inventoryAsset}', [InventoryAssetController::class, 'update'])->name('inventory-assets.update');
        Route::delete('/inventory-assets/{inventoryAsset}', [InventoryAssetController::class, 'destroy'])->name('inventory-assets.destroy');

        Route::get('/asset-categories', [AssetCategoryController::class, 'index'])->name('asset-categories.index');
        Route::post('/asset-categories', [AssetCategoryController::class, 'store'])->name('asset-categories.store');
        Route::put('/asset-categories/{assetCategory}', [AssetCategoryController::class, 'update'])->name('asset-categories.update');
        Route::delete('/asset-categories/{assetCategory}', [AssetCategoryController::class, 'destroy'])->name('asset-categories.destroy');
        Route::post('/asset-categories/{assetCategory}/status', [AssetCategoryController::class, 'status'])->name('asset-categories.status');

        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
        Route::post('/subjects/{subject}/status', [SubjectController::class, 'status'])->name('subjects.status');

        Route::get('/standards', [StandardController::class, 'index'])->name('standards.index');
        Route::post('/standards', [StandardController::class, 'store'])->name('standards.store');
        Route::put('/standards/{standard}', [StandardController::class, 'update'])->name('standards.update');
        Route::delete('/standards/{standard}', [StandardController::class, 'destroy'])->name('standards.destroy');
        Route::post('/standards/{standard}/status', [StandardController::class, 'status'])->name('standards.status');
    });
});
