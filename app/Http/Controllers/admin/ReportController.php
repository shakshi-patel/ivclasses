<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Student;
use App\Services\ReportService;
use App\Services\TodoService;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends AdminController
{
    public function __construct(
        protected ReportService $reports,
        protected TodoService $todoService
    ) {}

    public function index(): View
    {
        $this->ensureCanView();

        $cards = [
            [
                'title' => 'Attendance Report',
                'desc' => 'Monthly batch attendance grid (P / A / L / H).',
                'icon' => 'bx-calendar-check',
                'route' => route('admin.reports.attendance'),
                'can' => $this->canAny(['attendance.view', 'reports.view']),
            ],
            [
                'title' => 'To Do Task Report',
                'desc' => 'Filter tasks by date, status and assignee.',
                'icon' => 'bx-task',
                'route' => route('admin.reports.todo'),
                'can' => $this->canAny(['todo.view', 'reports.view']),
            ],
            [
                'title' => 'Batch Report',
                'desc' => 'Students, teachers and attendance snapshot for one batch.',
                'icon' => 'bx-group',
                'route' => route('admin.reports.batch'),
                'can' => $this->canAny(['batch.view', 'reports.view']),
            ],
            [
                'title' => 'Student Report',
                'desc' => 'Student profile, batches and attendance summary.',
                'icon' => 'bx-user',
                'route' => route('admin.reports.student'),
                'can' => $this->canAny(['student.view', 'reports.view']),
            ],
            [
                'title' => 'Roles Report',
                'desc' => 'Roles with permission and user counts.',
                'icon' => 'bx-shield-quarter',
                'route' => route('admin.reports.roles'),
                'can' => $this->canAny(['roles.view', 'reports.view']),
            ],
        ];

        return view('admin.reports.index', [
            'cards' => collect($cards)->where('can', true)->values(),
        ]);
    }

    public function attendance(Request $request): View
    {
        $this->ensureCanAny(['attendance.view', 'reports.view']);

        $batches = $this->reports->batchOptions();
        $batchId = (int) $request->input('batch_id');
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $batch = null;
        $report = null;
        if ($batchId > 0) {
            $batch = Batch::query()->findOrFail($batchId);
            $this->assertBatchVisible($batch);
            $report = $this->reports->attendanceBatchReport($batch, $year, $month);
        }

        return view('admin.reports.attendance', compact('batches', 'batch', 'report', 'year', 'month', 'batchId'));
    }

    public function todo(Request $request): View
    {
        $this->ensureCanAny(['todo.view', 'reports.view']);
        $user = auth()->user();

        $filters = [
            'from' => $request->input('from', now()->startOfMonth()->toDateString()),
            'to' => $request->input('to', now()->endOfMonth()->toDateString()),
            'status' => $request->input('status', ''),
            'assignee_id' => $request->input('assignee_id'),
        ];

        $rows = $this->reports->todoReport($filters, $user);
        $assignees = $this->todoService->assigneeOptions($user);

        return view('admin.reports.todo', compact('rows', 'filters', 'assignees'));
    }

    public function batch(Request $request): View
    {
        $this->ensureCanAny(['batch.view', 'reports.view']);

        $batches = $this->reports->batchOptions();
        $batchId = (int) $request->input('batch_id');
        $data = null;

        if ($batchId > 0) {
            $batch = Batch::query()->findOrFail($batchId);
            $this->assertBatchVisible($batch);
            $data = $this->reports->batchDetailReport($batch);
        }

        return view('admin.reports.batch', compact('batches', 'batchId', 'data'));
    }

    public function student(Request $request): View
    {
        $this->ensureCanAny(['student.view', 'reports.view']);

        $students = $this->reports->studentOptions();
        $studentId = (int) $request->input('student_id');
        $data = null;

        if ($studentId > 0) {
            $student = Student::query()->findOrFail($studentId);
            $this->assertStudentVisible($student);
            $data = $this->reports->studentDetailReport($student);
        }

        return view('admin.reports.student', compact('students', 'studentId', 'data'));
    }

    public function roles(): View
    {
        $this->ensureCanAny(['roles.view', 'reports.view']);

        $rows = $this->reports->rolesReport();

        return view('admin.reports.roles', compact('rows'));
    }

    protected function ensureCanView(): void
    {
        $this->ensureCanAny([
            'reports.view',
            'attendance.view',
            'todo.view',
            'batch.view',
            'student.view',
            'roles.view',
        ]);
    }

    /**
     * @param  list<string>  $abilities
     */
    protected function ensureCanAny(array $abilities): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || collect($abilities)->contains(fn ($a) => $user->can($a)))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    /**
     * @param  list<string>  $abilities
     */
    protected function canAny(array $abilities): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->isPlatformUser() || collect($abilities)->contains(fn ($a) => $user->can($a));
    }

    protected function assertBatchVisible(Batch $batch): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $batch->institute_id !== (int) $user->institute_id) {
            abort(404);
        }
        BranchScope::for($user)->assertCanAccessBranch($batch->branch_id);
    }

    protected function assertStudentVisible(Student $student): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $student->institute_id !== (int) $user->institute_id) {
            abort(404);
        }
        BranchScope::for($user)->assertCanAccessBranch($student->branch_id);
    }
}
