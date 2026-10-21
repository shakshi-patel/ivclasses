<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\SubjectResource;
use App\Models\Subject;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('subject.view');

        $query = Subject::query()->where('institute_id', $this->actor()->institute_id);
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginated(
            'OK',
            $query->orderBy('name')->paginate($this->perPage())
                ->through(fn (Subject $row) => (new SubjectResource($row))->resolve($request))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('subject.add');

        $validated = $this->validateSubject($request);

        $trashed = Subject::withTrashed()
            ->where('institute_id', $validated['institute_id'])
            ->where('name', $validated['name'])
            ->whereNotNull('deleted_at')
            ->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->update($validated + ['updated_by' => $this->actor()->id]);

            return Ajax::success('Subject restored.', (new SubjectResource($trashed->fresh()))->resolve());
        }

        $validated['created_by'] = $this->actor()->id;
        $validated['updated_by'] = $this->actor()->id;
        $subject = Subject::query()->create($validated);

        return Ajax::success('Subject created.', (new SubjectResource($subject))->resolve(), 201);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $this->ensureCan('subject.edit');
        $this->assertVisible($subject);

        $validated = $this->validateSubject($request, $subject);
        $validated['updated_by'] = $this->actor()->id;
        $subject->update($validated);

        return Ajax::success('Subject updated.', (new SubjectResource($subject->fresh()))->resolve());
    }

    public function destroy(Subject $subject): JsonResponse
    {
        $this->ensureCan('subject.delete');
        $this->assertVisible($subject);
        $subject->delete();

        return Ajax::success('Subject deleted.');
    }

    public function status(Subject $subject): JsonResponse
    {
        $this->ensureCan('subject.status');
        $this->assertVisible($subject);

        $subject->update([
            'status' => $subject->status === 'active' ? 'inactive' : 'active',
            'updated_by' => $this->actor()->id,
        ]);

        return Ajax::success(
            $subject->status === 'active' ? 'Subject activated.' : 'Subject deactivated.',
            (new SubjectResource($subject))->resolve()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateSubject(Request $request, ?Subject $subject = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

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

    protected function assertVisible(Subject $subject): void
    {
        if ((int) $subject->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Subject not found.');
        }
    }
}
