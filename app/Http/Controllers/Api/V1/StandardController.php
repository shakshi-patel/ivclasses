<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\StandardResource;
use App\Models\Standard;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StandardController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('standard.view');

        $query = Standard::query()->where('institute_id', $this->actor()->institute_id);
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginated(
            'OK',
            $query->orderBy('sort_order')->orderBy('name')->paginate($this->perPage())
                ->through(fn (Standard $row) => (new StandardResource($row))->resolve($request))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('standard.add');

        $validated = $this->validateStandard($request);

        $trashed = Standard::withTrashed()
            ->where('institute_id', $validated['institute_id'])
            ->where('name', $validated['name'])
            ->whereNotNull('deleted_at')
            ->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->update($validated + ['updated_by' => $this->actor()->id]);

            return Ajax::success('Standard restored.', (new StandardResource($trashed->fresh()))->resolve());
        }

        $validated['created_by'] = $this->actor()->id;
        $validated['updated_by'] = $this->actor()->id;
        $standard = Standard::query()->create($validated);

        return Ajax::success('Standard created.', (new StandardResource($standard))->resolve(), 201);
    }

    public function update(Request $request, Standard $standard): JsonResponse
    {
        $this->ensureCan('standard.edit');
        $this->assertVisible($standard);

        $validated = $this->validateStandard($request, $standard);
        $validated['updated_by'] = $this->actor()->id;
        $standard->update($validated);

        return Ajax::success('Standard updated.', (new StandardResource($standard->fresh()))->resolve());
    }

    public function destroy(Standard $standard): JsonResponse
    {
        $this->ensureCan('standard.delete');
        $this->assertVisible($standard);

        $batchCount = $standard->batches()->count();
        $studentCount = $standard->students()->count();
        $inquiryCount = $standard->inquiries()->count();

        if ($batchCount > 0 || $studentCount > 0 || $inquiryCount > 0) {
            $parts = [];
            if ($batchCount > 0) {
                $parts[] = $batchCount.' batch'.($batchCount === 1 ? '' : 'es');
            }
            if ($studentCount > 0) {
                $parts[] = $studentCount.' student'.($studentCount === 1 ? '' : 's');
            }
            if ($inquiryCount > 0) {
                $parts[] = $inquiryCount.' inquir'.($inquiryCount === 1 ? 'y' : 'ies');
            }

            return Ajax::error('Cannot delete — this standard is used by '.implode(', ', $parts).'. Reassign them first.');
        }

        $standard->delete();

        return Ajax::success('Standard deleted.');
    }

    public function status(Standard $standard): JsonResponse
    {
        $this->ensureCan('standard.status');
        $this->assertVisible($standard);

        $standard->update([
            'status' => $standard->status === 'active' ? 'inactive' : 'active',
            'updated_by' => $this->actor()->id,
        ]);

        return Ajax::success(
            $standard->status === 'active' ? 'Standard activated.' : 'Standard deactivated.',
            (new StandardResource($standard))->resolve()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateStandard(Request $request, ?Standard $standard = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('standards', 'name')
                    ->where(fn ($q) => $q->where('institute_id', $instituteId)->whereNull('deleted_at'))
                    ->ignore($standard?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    protected function assertVisible(Standard $standard): void
    {
        if ((int) $standard->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Standard not found.');
        }
    }
}
