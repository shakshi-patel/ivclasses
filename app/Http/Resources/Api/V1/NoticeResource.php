<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'notice_date' => $this->notice_date?->toDateString(),
            'audience' => $this->audience,
            'status' => $this->status,
            'image_url' => $this->imageUrl(),
            'branches' => $this->whenLoaded('branches', fn () => $this->branches->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
            ])->values()->all()),
            'batches' => $this->whenLoaded('batches', fn () => $this->batches->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
            ])->values()->all()),
        ];
    }
}
