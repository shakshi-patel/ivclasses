<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'branch_id' => $this->branch_id,
            'standard_id' => $this->standard_id,
            'start_time' => $this->start_time ? substr((string) $this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr((string) $this->end_time, 0, 5) : null,
            'timing' => $this->timingLabel(),
            'capacity' => $this->capacity,
            'academic_year' => $this->academic_year,
            'status' => $this->status,
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null),
            'standard' => $this->whenLoaded('standard', fn () => $this->standard ? [
                'id' => $this->standard->id,
                'name' => $this->standard->name,
            ] : null),
            'subjects' => $this->whenLoaded('subjects', fn () => $this->subjects->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'teacher_user_id' => $subject->pivot->teacher_user_id ? (int) $subject->pivot->teacher_user_id : null,
                'fees_amount' => $subject->pivot->fees_amount,
            ])->values()->all()),
            'teachers' => $this->whenLoaded('teachers', fn () => $this->teachers->map(fn ($teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'is_primary' => (bool) ($teacher->pivot->is_primary ?? false),
            ])->values()->all()),
            'enrollments_count' => $this->whenCounted('enrollments'),
        ];
    }
}
