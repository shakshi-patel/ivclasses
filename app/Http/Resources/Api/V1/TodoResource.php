<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'tasks' => $this->taskLines(),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'remark' => $this->remark,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'assignees' => $this->whenLoaded('assignees', fn () => $this->assignees->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values()->all()),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
        ];
    }
}
