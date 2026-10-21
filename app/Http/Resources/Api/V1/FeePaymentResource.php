<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeePaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_no' => $this->receipt_no,
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'transaction_ref' => $this->transaction_ref,
            'note' => $this->note,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'admission_no' => $this->student->admission_no,
                'branch' => $this->student->branch ? [
                    'id' => $this->student->branch->id,
                    'name' => $this->student->branch->name,
                ] : null,
                'standard' => $this->student->standard ? [
                    'id' => $this->student->standard->id,
                    'name' => $this->student->standard->name,
                ] : null,
            ] : null),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice ? [
                'id' => $this->invoice->id,
                'title' => $this->invoice->title,
                'status' => $this->invoice->status,
                'batch' => $this->invoice->enrollment?->batch ? [
                    'id' => $this->invoice->enrollment->batch->id,
                    'name' => $this->invoice->enrollment->batch->name,
                ] : null,
            ] : null),
            'collector' => $this->whenLoaded('collector', fn () => $this->collector ? [
                'id' => $this->collector->id,
                'name' => $this->collector->name,
            ] : null),
        ];
    }
}
