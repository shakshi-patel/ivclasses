<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\NoticeResource;
use App\Models\Notice;
use App\Services\NoticeService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoticeController extends ApiController
{
    public function __construct(
        protected NoticeService $noticeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('notice.view');

        $user = $this->actor();
        $studentId = $user->isParent() ? (int) $request->query('student_id', 0) ?: null : null;
        $query = $user->isParent()
            ? $this->noticeService->queryForUser($user, $studentId)
            : $this->noticeService->adminQueryForUser($user);

        if (! $user->isParent()) {
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

        return $this->paginated(
            'OK',
            $query->with(['batches:id,name', 'branches:id,name'])
                ->orderByDesc('notice_date')
                ->orderByDesc('id')
                ->paginate($this->perPage())
                ->through(fn (Notice $notice) => (new NoticeResource($notice))->resolve($request))
        );
    }

    public function show(Notice $notice): JsonResponse
    {
        $this->ensureCanAny(['notice.show', 'notice.view']);
        $this->noticeService->assertUserCanAccess($this->actor(), $notice);

        $notice->load(['batches:id,name', 'branches:id,name']);

        return Ajax::success('OK', (new NoticeResource($notice))->resolve());
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('notice.add');

        $notice = $this->noticeService->create($this->validateNotice($request), $request->file('image'));
        $notice->load(['batches:id,name', 'branches:id,name']);

        return Ajax::success('Notice saved.', (new NoticeResource($notice))->resolve(), 201);
    }

    public function update(Request $request, Notice $notice): JsonResponse
    {
        $this->ensureCan('notice.edit');
        $this->noticeService->assertUserCanAccess($this->actor(), $notice);

        $notice = $this->noticeService->update(
            $notice,
            $this->validateNotice($request, $notice),
            $request->file('image'),
            $request->boolean('remove_image')
        );
        $notice->load(['batches:id,name', 'branches:id,name']);

        return Ajax::success('Notice updated.', (new NoticeResource($notice))->resolve());
    }

    public function destroy(Notice $notice): JsonResponse
    {
        $this->ensureCan('notice.delete');
        $this->noticeService->assertUserCanAccess($this->actor(), $notice);
        $this->noticeService->delete($notice);

        return Ajax::success('Notice deleted.');
    }

    public function status(Notice $notice): JsonResponse
    {
        $this->ensureCan('notice.status');
        $this->noticeService->assertUserCanAccess($this->actor(), $notice);

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
        $user = $this->actor();
        $instituteId = (int) $user->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        $branchIds = $user->isTeacher()
            ? \App\Support\OwnBatchScope::branchIds($user)
            : BranchScope::for($user)->allowedBranchIdsForInstitute($instituteId);

        $validated = $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::in($branchIds ?: [0])],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => array_values(array_filter([
                'integer',
                Rule::exists('batches', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
                $user->isTeacher() ? Rule::in(\App\Support\OwnBatchScope::batchIds($user) ?: [0]) : null,
            ])),
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
            'notice_date' => ['required', 'date'],
            'audience' => ['nullable', Rule::in(array_keys(Notice::AUDIENCES))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        if (! $user->isTeacher() && BranchScope::for($user)->isRestricted() && empty($validated['branch_ids'])) {
            $validated['branch_ids'] = BranchScope::for($user)->branchIds();
        }

        $validated['audience'] = $validated['audience'] ?? Notice::AUDIENCE_ALL;
        $validated['status'] = $validated['status'] ?? 'active';

        return $validated;
    }
}
