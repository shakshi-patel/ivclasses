<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InquiryService
{
    public function __construct(
        protected IncomeExpenseService $incomeExpenseService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Inquiry
    {
        return DB::transaction(function () use ($data) {
            $instituteId = (int) $data['institute_id'];

            $inquiry = Inquiry::query()->create([
                'institute_id' => $instituteId,
                'branch_id' => $data['branch_id'] ?? null,
                'inquiry_code' => $this->nextInquiryCode($instituteId),
                'student_name' => $data['student_name'],
                'father_name' => $data['father_name'] ?? null,
                'mother_name' => $data['mother_name'] ?? null,
                'parent_name' => $data['father_name'] ?? $data['parent_name'] ?? null,
                'phone' => $data['phone'] ?? $data['father_phone'] ?? null,
                'email' => $data['email'] ?? $data['father_email'] ?? null,
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'aadhaar' => $data['aadhaar'] ?? null,
                'category' => $data['category'] ?? null,
                'inquiry_date' => $data['inquiry_date'] ?? now()->toDateString(),
                'school_name' => $data['school_name'] ?? null,
                'standard_id' => $data['standard_id'] ?? null,
                'interested_subject' => $data['interested_subject'] ?? null,
                'source' => $data['source'] ?? 'walk-in',
                'status' => 'new',
                'form_fees' => $data['form_fees'] ?? 0,
                'form_fees_payment_method' => $data['form_fees_payment_method'] ?? null,
                'address' => $data['address'] ?? null,
                'father_occupation' => $data['father_occupation'] ?? null,
                'next_follow_up_at' => $data['next_follow_up_at'] ?? $data['follow_up_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['photo']) && $data['photo']->isValid()) {
                $inquiry->update([
                    'photo_path' => $data['photo']->store('photos/inquiries', 'public'),
                ]);
            }

            if (! empty($data['follow_up_remark'])) {
                $this->addFollowUp($inquiry, [
                    'follow_up_at' => $data['follow_up_date'] ?? now(),
                    'remark' => $data['follow_up_remark'],
                    'next_follow_up_at' => $data['next_follow_up_at'] ?? $data['follow_up_date'] ?? null,
                ]);
                $inquiry->update(['status' => 'follow_up']);
            }

            $this->incomeExpenseService->syncFromInquiryFormFee($inquiry->fresh());

            return $inquiry->fresh(['branch', 'standard', 'followUps']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Inquiry $inquiry, array $data): Inquiry
    {
        if ($inquiry->isConverted()) {
            throw new \RuntimeException('Converted inquiries cannot be edited.');
        }

        return DB::transaction(function () use ($inquiry, $data) {
            $inquiry->update([
                'branch_id' => $data['branch_id'] ?? $inquiry->branch_id,
                'student_name' => $data['student_name'],
                'father_name' => $data['father_name'] ?? null,
                'mother_name' => $data['mother_name'] ?? null,
                'parent_name' => $data['father_name'] ?? $data['parent_name'] ?? null,
                'phone' => $data['phone'] ?? $data['father_phone'] ?? null,
                'email' => $data['email'] ?? $data['father_email'] ?? null,
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'aadhaar' => $data['aadhaar'] ?? null,
                'category' => $data['category'] ?? null,
                'inquiry_date' => $data['inquiry_date'] ?? $inquiry->inquiry_date,
                'school_name' => $data['school_name'] ?? null,
                'standard_id' => $data['standard_id'] ?? null,
                'interested_subject' => $data['interested_subject'] ?? null,
                'source' => $data['source'] ?? $inquiry->source,
                'form_fees' => $data['form_fees'] ?? 0,
                'form_fees_payment_method' => $data['form_fees_payment_method'] ?? null,
                'address' => $data['address'] ?? null,
                'father_occupation' => $data['father_occupation'] ?? null,
                'next_follow_up_at' => $data['next_follow_up_at'] ?? $data['follow_up_date'] ?? $inquiry->next_follow_up_at,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['photo']) && $data['photo']->isValid()) {
                if ($inquiry->photo_path) {
                    Storage::disk('public')->delete($inquiry->photo_path);
                }
                $inquiry->update([
                    'photo_path' => $data['photo']->store('photos/inquiries', 'public'),
                ]);
            }

            if (! empty($data['follow_up_remark'])) {
                $this->addFollowUp($inquiry, [
                    'follow_up_at' => $data['follow_up_date'] ?? now(),
                    'remark' => $data['follow_up_remark'],
                    'next_follow_up_at' => $data['next_follow_up_at'] ?? $data['follow_up_date'] ?? null,
                ]);
            }

            $this->incomeExpenseService->syncFromInquiryFormFee($inquiry->fresh());

            return $inquiry->fresh(['branch', 'standard', 'followUps']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addFollowUp(Inquiry $inquiry, array $data): InquiryFollowUp
    {
        $followUp = InquiryFollowUp::query()->create([
            'inquiry_id' => $inquiry->id,
            'follow_up_at' => $data['follow_up_at'] ?? now(),
            'remark' => $data['remark'] ?? null,
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $inquiry->update([
            'status' => $inquiry->isConverted() ? 'converted' : 'follow_up',
            'next_follow_up_at' => array_key_exists('next_follow_up_at', $data)
                ? $data['next_follow_up_at']
                : $inquiry->next_follow_up_at,
        ]);

        return $followUp;
    }

    public function delete(Inquiry $inquiry): void
    {
        if ($inquiry->isConverted()) {
            throw new \RuntimeException('Converted inquiries cannot be deleted.');
        }

        $inquiry->delete();
    }

    protected function nextInquiryCode(int $instituteId): string
    {
        return \App\Support\SequenceGenerator::forInstitute($instituteId, function () use ($instituteId) {
            $n = Inquiry::withTrashed()->where('institute_id', $instituteId)->count() + 1;

            return 'INQ-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        });
    }
}
