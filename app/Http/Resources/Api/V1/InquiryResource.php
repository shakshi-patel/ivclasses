<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InquiryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inquiry_code' => $this->inquiry_code,
            'student_name' => $this->student_name,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'phone' => $this->phone,
            'phone_2' => $this->phone_2,
            'email' => $this->email,
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender,
            'aadhaar' => $this->aadhaar,
            'category' => $this->category,
            'inquiry_date' => $this->inquiry_date?->toDateString(),
            'school_name' => $this->school_name,
            'source' => $this->source,
            'status' => $this->status,
            'form_fees' => $this->form_fees,
            'form_fees_payment_method' => $this->form_fees_payment_method,
            'address' => $this->address,
            'father_occupation' => $this->father_occupation,
            'next_follow_up_at' => $this->next_follow_up_at?->toIso8601String(),
            'notes' => $this->notes,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'converted_student_id' => $this->converted_student_id,
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null),
            'standard' => $this->whenLoaded('standard', fn () => $this->standard ? [
                'id' => $this->standard->id,
                'name' => $this->standard->name,
            ] : null),
            'follow_ups' => $this->whenLoaded('followUps', fn () => $this->followUps
                ->map(fn ($row) => (new InquiryFollowUpResource($row))->resolve($request))
                ->values()
                ->all()),
        ];
    }
}
