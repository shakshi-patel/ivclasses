<?php

namespace App\Http\Controllers\Admin;

use App\Models\Institute;
use App\Models\Subject;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends AdminController
{
    public function index(Request $request): View
    {
        $this->ensureCan('subject.view');

        $query = Subject::query()->with('institute:id,name');

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $subjects = $query->orderBy('name')->get();
        $filters = $this->listFilterVars($request, []);

        return view('admin.master.subject.subject', array_merge(
            compact('subjects'),
            $filters,
            ['institutes' => $this->instituteOptions()]
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('subject.add');

        $validated = $this->validateSubject($request);
        $subject = Subject::query()->create($validated);
        $subject->load('institute:id,name');

        return Ajax::success('Subject created.', $subject, 201);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $this->ensureCan('subject.edit');
        $this->assertVisible($subject);

        $validated = $this->validateSubject($request, $subject);
        $subject->update($validated);

        return Ajax::success('Subject updated.', $subject->fresh()->load('institute:id,name'));
    }

    public function destroy(Request $request, Subject $subject): JsonResponse|RedirectResponse
    {
        $this->ensureCan('subject.delete');
        $this->assertVisible($subject);

        $subject->delete();

        return $this->respondDestroyed($request, 'Subject deleted.', 'admin.master.subject');
    }

    public function toggleStatus(Subject $subject): JsonResponse
    {
        $this->ensureCan('subject.status');
        $this->assertVisible($subject);

        $subject->update([
            'status' => $subject->status === 'active' ? 'inactive' : 'active',
        ]);

        return Ajax::success(
            $subject->status === 'active' ? 'Subject activated.' : 'Subject deactivated.',
            ['id' => $subject->id, 'status' => $subject->status]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateSubject(Request $request, ?Subject $subject = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id', $user?->institute_id);

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        if (! $instituteId) {
            abort(422, 'Institute is required.');
        }

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subjects', 'name')
                    ->where(fn ($q) => $q->where('institute_id', $instituteId)->whereNull('deleted_at'))
                    ->ignore($subject?->id),
            ],
            'code' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function assertVisible(Subject $subject): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $subject->institute_id !== (int) $user->institute_id) {
            abort(404);
        }
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
