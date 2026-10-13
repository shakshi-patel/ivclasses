<?php

namespace App\Http\Controllers\Admin;

use App\Models\Institute;
use App\Models\Notice;
use App\Services\NoticeService;
use App\Support\Ajax;
use App\Support\BranchScope;
use App\Support\ParentBatchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NoticeController extends AdminController
{
    public function __construct(
        protected NoticeService $noticeService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCanView();

        $user = auth()->user();
        $studentId = $user && $user->isParent() ? (int) $request->query('student_id', 0) ?: null : null;
        $isParent = $user && $user->isParent();

        $query = $isParent
            ? $this->noticeService->queryForUser($user, $studentId)
            : $this->noticeService->adminQueryForUser($user);

        if (! $isParent) {
            $this->applyInstituteFilter($query, $request);

            if ($request->filled('branch_id')) {
                $branchId = (int) $request->input('branch_id');
                $query->where(function ($q) use ($branchId) {
                    $q->whereDoesntHave('branches')
                        ->orWhereHas('branches', fn ($bq) => $bq->where('branches.id', $branchId));
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
        }

        $notices = $query
            ->with(['batches:id,name', 'branches:id,name', 'creator:id,name'])
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->get(['id', 'institute_id', 'title', 'body', 'notice_date', 'audience', 'status', 'image_path', 'created_by']);

        $filters = $isParent ? ['isPlatform' => false] : $this->listFilterVars($request, ['branches']);

        return view('admin.notice.notice', array_merge([
            'notices' => $notices,
            'isParentView' => $isParent,
            'parentStudents' => $isParent ? ParentBatchScope::studentOptions($user) : collect(),
            'selectedStudentId' => $studentId,
            'canAdd' => $user && ($user->isPlatformUser() || $user->can('notice.add')),
            'canEdit' => $user && ($user->isPlatformUser() || $user->can('notice.edit')),
            'canDelete' => $user && ($user->isPlatformUser() || $user->can('notice.delete')),
            'canShow' => $user && ($user->isPlatformUser() || $user->can('notice.show')),
            'canStatus' => $user && ($user->isPlatformUser() || $user->can('notice.status')),
        ], $filters));
    }

    public function create(): View
    {
        $this->ensureCan('notice.add');

        return view('admin.notice.add_notice', [
            'notice' => null,
            'institutes' => $this->instituteOptions(),
            'defaultInstituteId' => $this->resolveInstituteId(),
            'defaultBranchIds' => old('branch_ids', []),
            'defaultBatchIds' => old('batch_ids', []),
        ]);
    }

    public function edit(Notice $notice): View
    {
        $this->ensureCan('notice.edit');
        $this->assertVisible($notice);

        $notice->load(['branches:id', 'batches:id']);

        return view('admin.notice.add_notice', [
            'notice' => $notice,
            'institutes' => $this->instituteOptions(),
            'defaultInstituteId' => $notice->institute_id,
            'defaultBranchIds' => old('branch_ids', $notice->branches->pluck('id')->all()),
            'defaultBatchIds' => old('batch_ids', $notice->batches->pluck('id')->all()),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('notice.add');
        $notice = $this->noticeService->create($this->validateNotice($request), $request->file('image'));

        if ($request->expectsJson()) {
            return Ajax::success('Notice saved.', $notice, 201);
        }

        return redirect()->route('admin.notice')->with('success', 'Notice saved.');
    }

    public function update(Request $request, Notice $notice): JsonResponse|RedirectResponse
    {
        $this->ensureCan('notice.edit');
        $this->assertVisible($notice);

        $notice = $this->noticeService->update(
            $notice,
            $this->validateNotice($request, $notice),
            $request->file('image'),
            $request->boolean('remove_image')
        );

        if ($request->expectsJson()) {
            return Ajax::success('Notice updated.', $notice);
        }

        return redirect()->route('admin.notice')->with('success', 'Notice updated.');
    }

    public function destroy(Request $request, Notice $notice): JsonResponse|RedirectResponse
    {
        $this->ensureCan('notice.delete');
        $this->assertVisible($notice);
        $this->noticeService->delete($notice);

        return $this->respondDestroyed($request, 'Notice deleted.', 'admin.notice');
    }

    public function toggleStatus(Notice $notice): JsonResponse
    {
        $this->ensureCan('notice.status');
        $this->assertVisible($notice);

        $notice = $this->noticeService->toggleStatus($notice);

        return Ajax::success(
            $notice->status === 'active' ? 'Notice activated.' : 'Notice deactivated.',
            ['id' => $notice->id, 'status' => $notice->status]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateNotice(Request $request, ?Notice $notice = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute($instituteId);

        $validated = $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds)],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => [
                'integer',
                Rule::exists('batches', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
            ],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
            'notice_date' => ['required', 'date'],
            'audience' => ['nullable', Rule::in(array_keys(Notice::AUDIENCES))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        if (! BranchScope::for($user)->isRestricted() && empty($validated['branch_ids'])) {
            // empty = all branches
        } elseif (BranchScope::for($user)->isRestricted() && empty($validated['branch_ids'])) {
            $validated['branch_ids'] = BranchScope::for($user)->branchIds();
        }

        $validated['audience'] = $validated['audience'] ?? Notice::AUDIENCE_ALL;
        $validated['status'] = $validated['status'] ?? 'active';

        return $validated;
    }

    protected function assertVisible(Notice $notice): void
    {
        $user = auth()->user();
        if ($user) {
            $this->noticeService->assertUserCanAccess($user, $notice);

            return;
        }

        abort(404);
    }

    protected function ensureCanView(): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can('notice.view'))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function resolveInstituteId(): ?int
    {
        $user = auth()->user();

        return $user && ! $user->isPlatformUser() ? (int) $user->institute_id : null;
    }

    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
