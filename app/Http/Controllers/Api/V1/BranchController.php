<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\BranchResource;
use App\Models\Branch;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('branch.view');

        $query = BranchScope::for($this->actor())
            ->applyToBranches(Branch::query())
            ->with(['manager:id,name,email,phone'])
            ->where('institute_id', $this->actor()->institute_id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $branches = $query->orderBy('name')->get();
        $primaryFlags = $this->actor()->branches()->pluck('branch_user.is_primary', 'branches.id');

        $items = $branches->map(function (Branch $branch) use ($request, $primaryFlags) {
            $payload = (new BranchResource($branch))->resolve($request);
            $payload['is_primary'] = (bool) $primaryFlags->get($branch->id);

            return $payload;
        })->values()->all();

        return Ajax::success('OK', [
            'items' => $items,
        ]);
    }
}
