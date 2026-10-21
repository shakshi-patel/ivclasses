<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GstInvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'gstin' => $this->gstin,
            'taxable' => (float) $this->taxable,
            'cgst' => (float) $this->cgst,
            'sgst' => (float) $this->sgst,
            'igst' => (float) $this->igst,
            'total' => (float) $this->total,
            'created_at' => $this->created_at?->toIso8601String(),
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'standard' => $this->student->standard ? [
                    'id' => $this->student->standard->id,
                    'name' => $this->student->standard->name,
                ] : null,
                'parent' => $this->student->parent ? [
                    'father_name' => $this->student->parent->father_name,
                    'phone' => $this->student->parent->phone,
                ] : null,
            ] : null),
            'payment' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'id' => $this->payment->id,
                'receipt_no' => $this->payment->receipt_no,
                'amount' => (float) $this->payment->amount,
                'payment_date' => $this->payment->payment_date?->toDateString(),
            ] : null),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null),
        ];
    }
}
