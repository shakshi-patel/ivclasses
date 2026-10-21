<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\InventoryAssetResource;
use App\Models\InventoryAsset;
use App\Services\InventoryAssetService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryAssetController extends ApiController
{
    public function __construct(
        protected InventoryAssetService $inventoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('inventory.view');

        $instituteId = (int) $this->actor()->institute_id;
        $this->inventoryService->ensureDefaultCategories($instituteId);

        $query = $this->assetQuery()->with(['branch:id,name', 'category:id,name']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('status') && array_key_exists((string) $request->input('status'), InventoryAsset::STATUSES)) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginated(
            'OK',
            $query->latest('id')->paginate($this->perPage())
                ->through(fn (InventoryAsset $row) => (new InventoryAssetResource($row))->resolve($request))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('inventory.add');

        $asset = $this->inventoryService->create($this->validateAsset($request));

        return Ajax::success(
            'Asset saved.',
            (new InventoryAssetResource($asset->load(['branch:id,name', 'category:id,name'])))->resolve(),
            201
        );
    }

    public function update(Request $request, InventoryAsset $inventoryAsset): JsonResponse
    {
        $this->ensureCan('inventory.edit');
        $this->assertVisible($inventoryAsset);

        $asset = $this->inventoryService->update($inventoryAsset, $this->validateAsset($request, $inventoryAsset));

        return Ajax::success(
            'Asset updated.',
            (new InventoryAssetResource($asset->load(['branch:id,name', 'category:id,name'])))->resolve()
        );
    }

    public function destroy(InventoryAsset $inventoryAsset): JsonResponse
    {
        $this->ensureCan('inventory.delete');
        $this->assertVisible($inventoryAsset);
        $this->inventoryService->delete($inventoryAsset);

        return Ajax::success('Asset deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateAsset(Request $request, ?InventoryAsset $asset = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);
        $branchIds = BranchScope::for($this->actor())->allowedBranchIdsForInstitute($instituteId);

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['nullable', 'integer', Rule::in($branchIds ?: [0])],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('asset_categories', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)->whereNull('deleted_at')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'quantity' => ['required', 'integer', 'min:1'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'purchase_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(InventoryAsset::STATUSES))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    protected function assetQuery()
    {
        $query = InventoryAsset::query()->where('institute_id', $this->actor()->institute_id);
        $scope = BranchScope::for($this->actor());
        if ($scope->isRestricted()) {
            $ids = $scope->branchIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('branch_id', $ids ?: [0])->orWhereNull('branch_id');
            });
        }

        return $query;
    }

    protected function assertVisible(InventoryAsset $asset): void
    {
        if ((int) $asset->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Asset not found.');
        }
        if ($asset->branch_id) {
            BranchScope::for($this->actor())->assertCanAccessBranch((int) $asset->branch_id);
        }
    }
}
