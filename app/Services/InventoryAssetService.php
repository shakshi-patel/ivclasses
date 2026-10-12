<?php

namespace App\Services;

use App\Models\AssetCategory;
use App\Models\InventoryAsset;
use Illuminate\Support\Facades\DB;

class InventoryAssetService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InventoryAsset
    {
        return DB::transaction(function () use ($data) {
            $instituteId = (int) $data['institute_id'];
            $this->ensureDefaultCategories($instituteId);

            return InventoryAsset::query()->create([
                'institute_id' => $instituteId,
                'branch_id' => $data['branch_id'] ?? null,
                'category_id' => (int) $data['category_id'],
                'name' => $data['name'],
                'quantity' => (int) ($data['quantity'] ?? 1),
                'purchase_date' => $data['purchase_date'] ?? null,
                'purchase_amount' => $data['purchase_amount'] ?? 0,
                'status' => $data['status'] ?? InventoryAsset::STATUS_WORKING,
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InventoryAsset $asset, array $data): InventoryAsset
    {
        $asset->update([
            'branch_id' => $data['branch_id'] ?? $asset->branch_id,
            'category_id' => (int) ($data['category_id'] ?? $asset->category_id),
            'name' => $data['name'] ?? $asset->name,
            'quantity' => (int) ($data['quantity'] ?? $asset->quantity),
            'purchase_date' => $data['purchase_date'] ?? $asset->purchase_date,
            'purchase_amount' => $data['purchase_amount'] ?? $asset->purchase_amount,
            'status' => $data['status'] ?? $asset->status,
            'note' => $data['note'] ?? $asset->note,
            'updated_by' => auth()->id(),
        ]);

        return $asset->fresh(['branch', 'category']);
    }

    public function delete(InventoryAsset $asset): void
    {
        $asset->delete();
    }

    public function ensureDefaultCategories(int $instituteId): void
    {
        foreach (['Furniture', 'Electronics', 'Books & Kits', 'Stationery', 'Other'] as $name) {
            AssetCategory::query()->firstOrCreate(
                ['institute_id' => $instituteId, 'name' => $name],
                ['status' => 'active', 'created_by' => auth()->id()]
            );
        }
    }
}
