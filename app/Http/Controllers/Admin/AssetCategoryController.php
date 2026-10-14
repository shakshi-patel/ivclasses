<?php

namespace App\Http\Controllers\Admin;

use App\Models\AssetCategory;
use App\Models\Institute;
use App\Services\InventoryAssetService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetCategoryController extends AdminController
{
    public function __construct(
        protected InventoryAssetService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('assets_category.view');

        $filters = $this->listFilterVars($request, []);
        $instituteId = $this->filterInstituteId($request);
        if ($instituteId) {
            $this->inventoryService->ensureDefaultCategories($instituteId);
        }

        $query = AssetCategory::query()->with('institute:id,name');

        if (! $this->isPlatform()) {
            $ownId = $this->resolveInstituteId();
            if ($ownId) {
                $query->where('institute_id', $ownId);
            }
        } else {
            $this->applyInstituteFilter($query, $request);
        }

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        $categories = $query->orderBy('name')->get();

        return view('admin.master.assets_category.assets_category', array_merge($filters, [
            'categories' => $categories,
            'institutes' => $this->instituteOptions(),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('assets_category.add');

        $validated = $this->validateCategory($request);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $category = AssetCategory::query()->create($validated);
        $category->load('institute:id,name');

        return Ajax::success('Category created.', $category, 201);
    }

    public function update(Request $request, AssetCategory $assetCategory): JsonResponse
    {
        $this->ensureCan('assets_category.edit');
        $this->assertVisible($assetCategory);

        $validated = $this->validateCategory($request, $assetCategory);
        $validated['updated_by'] = auth()->id();
        $assetCategory->update($validated);

        return Ajax::success('Category updated.', $assetCategory->fresh()->load('institute:id,name'));
    }

    public function destroy(Request $request, AssetCategory $assetCategory): JsonResponse|RedirectResponse
    {
        $this->ensureCan('assets_category.delete');
        $this->assertVisible($assetCategory);

        if ($assetCategory->assets()->exists()) {
            return $this->respondDestroyFailed($request, 'Cannot delete — assets exist in this category.');
        }

        $assetCategory->delete();

        return $this->respondDestroyed($request, 'Category deleted.', 'admin.master.assets_category');
    }

    public function toggleStatus(AssetCategory $assetCategory): JsonResponse
    {
        $this->ensureCan('assets_category.status');
        $this->assertVisible($assetCategory);

        $assetCategory->update([
            'status' => $assetCategory->status === 'active' ? 'inactive' : 'active',
        ]);

        return Ajax::success(
            $assetCategory->status === 'active' ? 'Category activated.' : 'Category deactivated.',
            ['id' => $assetCategory->id, 'status' => $assetCategory->status]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateCategory(Request $request, ?AssetCategory $category = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id', $user?->institute_id);

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        abort_unless($instituteId, 422, 'Institute is required.');

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
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return null;
    }

    protected function assertVisible(AssetCategory $category): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $category->institute_id !== (int) $user->institute_id) {
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
