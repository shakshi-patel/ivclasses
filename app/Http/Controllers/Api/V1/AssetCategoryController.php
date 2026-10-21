<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\AssetCategoryResource;
use App\Models\AssetCategory;
use App\Services\InventoryAssetService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetCategoryController extends ApiController
{
    public function __construct(
        protected InventoryAssetService $inventoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('assets_category.view');

        $instituteId = (int) $this->actor()->institute_id;
        $this->inventoryService->ensureDefaultCategories($instituteId);

        $query = AssetCategory::query()->where('institute_id', $instituteId);
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginated(
            'OK',
            $query->orderBy('name')->paginate($this->perPage())
                ->through(fn (AssetCategory $row) => (new AssetCategoryResource($row))->resolve($request))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('assets_category.add');

        $validated = $this->validateCategory($request);
        $validated['created_by'] = $this->actor()->id;
        $validated['updated_by'] = $this->actor()->id;
        $category = AssetCategory::query()->create($validated);

        return Ajax::success('Category created.', (new AssetCategoryResource($category))->resolve(), 201);
    }

    public function update(Request $request, AssetCategory $assetCategory): JsonResponse
    {
        $this->ensureCan('assets_category.edit');
        $this->assertVisible($assetCategory);

        $validated = $this->validateCategory($request, $assetCategory);
        $validated['updated_by'] = $this->actor()->id;
        $assetCategory->update($validated);

        return Ajax::success(
            'Category updated.',
            (new AssetCategoryResource($assetCategory->fresh()))->resolve()
        );
    }

    public function destroy(AssetCategory $assetCategory): JsonResponse
    {
        $this->ensureCan('assets_category.delete');
        $this->assertVisible($assetCategory);

        if ($assetCategory->assets()->exists()) {
            return Ajax::error('Cannot delete — assets exist in this category.');
        }

        $assetCategory->delete();

        return Ajax::success('Category deleted.');
    }

    public function status(AssetCategory $assetCategory): JsonResponse
    {
        $this->ensureCan('assets_category.status');
        $this->assertVisible($assetCategory);

        $assetCategory->update([
            'status' => $assetCategory->status === 'active' ? 'inactive' : 'active',
        ]);

        return Ajax::success(
            $assetCategory->status === 'active' ? 'Category activated.' : 'Category deactivated.',
            (new AssetCategoryResource($assetCategory))->resolve()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateCategory(Request $request, ?AssetCategory $category = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_categories', 'name')
                    ->where(fn ($q) => $q->where('institute_id', $instituteId)->whereNull('deleted_at'))
                    ->ignore($category?->id),
            ],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    protected function assertVisible(AssetCategory $category): void
    {
        if ((int) $category->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Category not found.');
        }
    }
}
