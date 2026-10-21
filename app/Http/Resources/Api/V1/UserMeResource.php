<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Batch;
use App\Models\User;
use App\Support\BranchScope;
use App\Support\MobileApiRole;
use App\Support\OwnBatchScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserMeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $role = MobileApiRole::resolve($user);

        $user->loadMissing([
            'institute:id,name,code,status,trial_ends_at',
            'branches:id,institute_id,name,code,status',
            'teacher:id,user_id,employee_code',
            'staff:id,user_id,employee_code',
            'parentGuardian:id,user_id,father_name,mother_name,phone',
        ]);

        $children = [];
        if ($user->parentGuardian) {
            $children = $user->parentGuardian->students()
                ->with(['standard:id,name', 'branch:id,name'])
                ->get(['id', 'name', 'admission_no', 'standard_id', 'branch_id', 'status', 'photo_path'])
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'admission_no' => $s->admission_no,
                    'standard_id' => $s->standard_id,
                    'branch_id' => $s->branch_id,
                    'status' => $s->status,
                    'photo_url' => $s->photo_path ? asset('storage/'.$s->photo_path) : null,
                    'standard' => $s->standard ? ['id' => $s->standard->id, 'name' => $s->standard->name] : null,
                    'branch' => $s->branch ? ['id' => $s->branch->id, 'name' => $s->branch->name] : null,
                ])
                ->values()
                ->all();
        }

        $batches = [];
        if ($user->isTeacher()) {
            $batchIds = OwnBatchScope::batchIds($user);
            $batches = $batchIds === [] ? [] : Batch::query()
                ->whereIn('id', $batchIds)
                ->get(['id', 'name', 'branch_id', 'standard_id', 'status'])
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'branch_id' => $b->branch_id,
                    'standard_id' => $b->standard_id,
                    'status' => $b->status,
                ])
                ->values()
                ->all();
        }

        $branchScope = BranchScope::for($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
            'user_type' => $user->user_type?->value,
            'mobile_role' => $role,
            'must_change_password' => (bool) $user->must_change_password,
            'institute' => $user->institute ? [
                'id' => $user->institute->id,
                'name' => $user->institute->name,
                'code' => $user->institute->code,
                'status' => $user->institute->status?->value ?? $user->institute->status,
            ] : null,
            'branches' => $user->branches->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'status' => $b->status,
                'is_primary' => (bool) ($b->pivot->is_primary ?? false),
            ])->values()->all(),
            'branch_ids' => $branchScope->branchIds(),
            'teacher' => $user->teacher ? [
                'id' => $user->teacher->id,
                'employee_code' => $user->teacher->employee_code,
            ] : null,
            'staff' => $user->staff ? [
                'id' => $user->staff->id,
                'employee_code' => $user->staff->employee_code,
            ] : null,
            'children' => $children,
            'batches' => $batches,
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'roles' => $user->getRoleNames()->values()->all(),
        ];
    }
}
