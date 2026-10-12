<?php

namespace App\Http\Requests\Website;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_person_name' => ['required', 'string', 'max:100'],
            'institute_name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'contact_no' => ['required', 'string', 'max:15'],
            'email' => ['required', 'email', 'max:150'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'branch_count' => ['nullable', 'integer', 'min:1'],
            'student_count' => ['nullable', 'integer', 'min:1'],
            'requirement' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:5000'],
            'demo_date' => ['required', 'date', 'after_or_equal:today'],
            'demo_start_time' => ['required', 'date_format:H:i'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
