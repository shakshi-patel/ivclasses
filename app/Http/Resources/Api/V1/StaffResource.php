<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'designation' => $this->designation,
            'joining_date' => $this->joining_date?->toDateString(),
            'status' => $this->status,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
            ] : null,
            'roles' => $user ? $user->getRoleNames()->values()->all() : [],
            'branches' => $this->whenLoaded('branches', fn () => $this->branches->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'is_primary' => (bool) ($b->pivot->is_primary ?? false),
            ])->values()->all()),
        ];
    }
}
