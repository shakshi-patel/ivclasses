<?php

namespace App\Http\Controllers\Admin;

use App\Models\AssetCategory;
use App\Models\Institute;
use App\Models\InventoryAsset;
use App\Services\InventoryAssetService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryAssetController extends AdminController
{
    public function __construct(
        protected InventoryAssetService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('inventory.view');

        $instituteId = $this->filterInstituteId($request);
        if ($instituteId) {
            $this->inventoryService->ensureDefaultCategories($instituteId);
        }

        $query = $this->assetQuery()
            ->with(['branch:id,name', 'category:id,name']);

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $assets = $query->latest('id')->get();
        $filters = $this->listFilterVars($request, ['branches']);

        return view('admin.inventory_assets.inventory_assets', array_merge(compact('assets'), $filters));
    }

    public function create(): View
    {
        $this->ensureCan('inventory.add');

        $instituteId = $this->resolveInstituteId();
        if ($instituteId) {
            $this->inventoryService->ensureDefaultCategories($instituteId);
        }

        return view('admin.inventory_assets.add_inventory_assets', [
            'asset' => null,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($instituteId),
            'categories' => $this->categoryOptions($instituteId),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('inventory.add');

        $validated = $this->validateAsset($request);
        $asset = $this->inventoryService->create($validated);

        if ($request->expectsJson()) {
            return Ajax::success('Asset saved.', $asset, 201);
        }

        return redirect()->route('admin.inventory_assets')->with('success', 'Asset saved.');
    }

    public function edit(InventoryAsset $inventoryAsset): View
    {
        $this->ensureCan('inventory.edit');
        $this->assertVisible($inventoryAsset);

        return view('admin.inventory_assets.add_inventory_assets', [
            'asset' => $inventoryAsset,
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions($inventoryAsset->institute_id),
            'categories' => $this->categoryOptions($inventoryAsset->institute_id),
        ]);
    }

    public function update(Request $request, InventoryAsset $inventoryAsset): JsonResponse|RedirectResponse
    {
        $this->ensureCan('inventory.edit');
        $this->assertVisible($inventoryAsset);

        $validated = $this->validateAsset($request, $inventoryAsset);
        $this->inventoryService->update($inventoryAsset, $validated);

        if ($request->expectsJson()) {
            return Ajax::success('Asset updated.', $inventoryAsset->fresh(['branch', 'category']));
        }

        return redirect()->route('admin.inventory_assets')->with('success', 'Asset updated.');
    }

    public function destroy(Request $request, InventoryAsset $inventoryAsset): JsonResponse|RedirectResponse
    {
        $this->ensureCan('inventory.delete');
        $this->assertVisible($inventoryAsset);

        $this->inventoryService->delete($inventoryAsset);

        return $this->respondDestroyed($request, 'Asset deleted.', 'admin.inventory_assets');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateAsset(Request $request, ?InventoryAsset $asset = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id');

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute($instituteId);

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['nullable', 'integer', Rule::in($branchIds)],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('asset_categories', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
            ],
            'name' => ['required', 'string', 'max:150'],
            'quantity' => ['required', 'integer', 'min:1'],
            'purchase_date' => ['required', 'date'],
            'purchase_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(InventoryAsset::STATUSES))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    protected function assetQuery()
    {
        $query = InventoryAsset::query();
        $user = auth()->user();

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if (BranchScope::for()->isRestricted()) {
            $ids = BranchScope::for()->branchIds();
            $query->whereIn('branch_id', $ids);
        }

        return $query;
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function branchOptions(?int $instituteId = null)
    {
        if (! $instituteId) {
            return collect();
        }

        return BranchScope::for()->branchesForInstitute($instituteId);
    }

    protected function categoryOptions(?int $instituteId = null)
    {
        if (! $instituteId) {
            return collect();
        }

        return AssetCategory::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function resolveInstituteId(): ?int
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return null;
    }

    protected function assertVisible(InventoryAsset $asset): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $asset->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($asset->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $asset->branch_id);
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
