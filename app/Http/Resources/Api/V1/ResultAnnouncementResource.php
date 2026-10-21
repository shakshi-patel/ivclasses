<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultAnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'title' => $this->title,
            'body' => $this->body,
            'result_date' => $this->result_date?->toDateString(),
            'published_at' => $this->published_at?->toIso8601String(),
            'pdf_url' => route('api.v1.result-announcements.export-pdf', $this->resource),
            'exam' => $this->whenLoaded('exam', fn () => $this->exam ? [
                'id' => $this->exam->id,
                'title' => $this->exam->title,
                'exam_date' => $this->exam->exam_date?->toDateString(),
                'total_marks' => $this->exam->total_marks,
                'status' => $this->exam->status,
                'branch' => $this->exam->relationLoaded('branch') && $this->exam->branch ? [
                    'id' => $this->exam->branch->id,
                    'name' => $this->exam->branch->name,
                ] : null,
                'batch' => $this->exam->relationLoaded('batch') && $this->exam->batch ? [
                    'id' => $this->exam->batch->id,
                    'name' => $this->exam->batch->name,
                ] : null,
                'subject' => $this->exam->relationLoaded('subject') && $this->exam->subject ? [
                    'id' => $this->exam->subject->id,
                    'name' => $this->exam->subject->name,
                ] : null,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'student_result' => $this->when(
                $this->relationLoaded('studentResult'),
                function () {
                    $result = $this->studentResult;
                    if (! $result) {
                        return null;
                    }

                    return [
                        'marks_obtained' => $result->marks_obtained !== null ? (float) $result->marks_obtained : null,
                        'grade' => $result->grade,
                        'remark' => $result->remark,
                        'is_attempted' => (bool) $result->is_attempted,
                        'attendance_status' => $result->attendance_status,
                    ];
                }
            ),
        ];
    }
}
