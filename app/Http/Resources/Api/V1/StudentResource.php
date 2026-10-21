<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admission_no' => $this->admission_no,
            'name' => $this->name,
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'category' => $this->category,
            'admission_date' => $this->admission_date?->toDateString(),
            'school_name' => $this->school_name,
            'address' => $this->address,
            'status' => $this->status,
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null),
            'standard' => $this->whenLoaded('standard', fn () => $this->standard ? [
                'id' => $this->standard->id,
                'name' => $this->standard->name,
            ] : null),
            'parent' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'father_name' => $this->parent->father_name,
                'mother_name' => $this->parent->mother_name,
                'phone' => $this->parent->phone,
                'email' => $this->parent->email,
            ] : null),
            'enrollments' => $this->whenLoaded('enrollments', fn () => $this->enrollments->map(fn ($row) => [
                'id' => $row->id,
                'batch_id' => $row->batch_id,
                'subject_id' => $row->subject_id,
                'roll_no' => $row->roll_no,
                'total_fees' => $row->total_fees,
                'discount' => $row->discount,
                'status' => $row->status,
                'batch' => $row->batch ? ['id' => $row->batch->id, 'name' => $row->batch->name] : null,
                'subject' => $row->subject ? ['id' => $row->subject->id, 'name' => $row->subject->name] : null,
            ])->values()->all()),
        ];
    }
}
