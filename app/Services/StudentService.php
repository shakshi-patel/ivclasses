<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Batch;
use App\Models\Inquiry;
use App\Models\Institute;
use App\Models\ParentGuardian;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentBatch;
use App\Models\User;
use App\Services\Dashboard\InstituteDashboardService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

class StudentService
{
    public function __construct(
        protected IncomeExpenseService $incomeExpenseService
    ) {}
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $enrollments
     */
    public function create(array $data, array $enrollments = []): Student
    {
        $student = DB::transaction(function () use ($data, $enrollments) {
            $instituteId = (int) $data['institute_id'];
            $parent = $this->resolveParent($instituteId, $data);

            $student = Student::query()->create([
                'institute_id' => $instituteId,
                'parent_id' => $parent->id,
                'branch_id' => $data['branch_id'] ?? null,
                'admission_no' => $this->nextAdmissionNo($instituteId),
                'name' => $data['name'] ?? $data['student_name'],
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'medical_notes' => $data['medical_notes'] ?? null,
                'aadhaar' => $data['aadhaar'] ?? null,
                'category' => $data['category'] ?? null,
                'admission_date' => $data['admission_date'] ?? now()->toDateString(),
                'school_name' => $data['school_name'] ?? null,
                'standard_id' => $data['standard_id'] ?? null,
                'form_fees' => $data['form_fees'] ?? 0,
                'form_fees_payment_method' => $data['form_fees_payment_method'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            if (! empty($data['photo']) && $data['photo']->isValid()) {
                $student->update([
                    'photo_path' => $data['photo']->store('photos/students', 'public'),
                ]);
            } elseif (! empty($data['photo_path'])) {
                $student->update(['photo_path' => $data['photo_path']]);
            }

            $this->syncEnrollments($student, $enrollments);

            return $student->fresh(['parent', 'branch', 'standard', 'enrollments.batch', 'enrollments.subject']);
        });

        InstituteDashboardService::forgetForInstitute((int) $student->institute_id);

        return $student;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $enrollments
     */
    public function convertFromInquiry(Inquiry $inquiry, array $data, array $enrollments): Student
    {
        if ($inquiry->isConverted()) {
            throw new \RuntimeException('Inquiry already converted.');
        }

        return DB::transaction(function () use ($inquiry, $data, $enrollments) {
            $studentData = array_merge([
                'institute_id' => $inquiry->institute_id,
                'branch_id' => $data['branch_id'] ?? $inquiry->branch_id,
                'name' => $inquiry->student_name,
                'student_name' => $inquiry->student_name,
                'dob' => $inquiry->dob,
                'gender' => $inquiry->gender,
                'aadhaar' => $inquiry->aadhaar,
                'category' => $inquiry->category,
                'school_name' => $inquiry->school_name,
                'standard_id' => $inquiry->standard_id,
                'address' => $inquiry->address,
                'form_fees' => $data['form_fees'] ?? $inquiry->form_fees,
                'form_fees_payment_method' => $data['form_fees_payment_method'] ?? $inquiry->form_fees_payment_method,
                'father_name' => $inquiry->father_name,
                'mother_name' => $inquiry->mother_name,
                'father_email' => $inquiry->email,
                'father_phone' => $inquiry->phone,
                'father_occupation' => $inquiry->father_occupation,
                'parent_password' => $data['parent_password'] ?? 'password',
                'photo_path' => $inquiry->photo_path,
            ], $data);

            $student = $this->create($studentData, $enrollments);

            $inquiry->update([
                'status' => 'converted',
                'converted_student_id' => $student->id,
                'form_fees' => $student->form_fees,
                'form_fees_payment_method' => $student->form_fees_payment_method,
            ]);

            $this->incomeExpenseService->syncFromInquiryFormFee($inquiry->fresh());

            return $student;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $enrollments
     */
    public function update(Student $student, array $data, array $enrollments = []): Student
    {
        $updated = DB::transaction(function () use ($student, $data, $enrollments) {
            $instituteId = (int) $student->institute_id;
            $parent = $this->resolveParent($instituteId, $data, $student->parent);

            $student->update([
                'parent_id' => $parent->id,
                'branch_id' => $data['branch_id'] ?? $student->branch_id,
                'name' => $data['name'] ?? $data['student_name'],
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'medical_notes' => $data['medical_notes'] ?? null,
                'aadhaar' => $data['aadhaar'] ?? null,
                'category' => $data['category'] ?? null,
                'admission_date' => $data['admission_date'] ?? $student->admission_date,
                'school_name' => $data['school_name'] ?? null,
                'standard_id' => $data['standard_id'] ?? null,
                'form_fees' => $data['form_fees'] ?? $student->form_fees,
                'form_fees_payment_method' => $data['form_fees_payment_method'] ?? $student->form_fees_payment_method,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? $student->status,
            ]);

            if (! empty($data['photo']) && $data['photo']->isValid()) {
                if ($student->photo_path) {
                    Storage::disk('public')->delete($student->photo_path);
                }
                $student->update([
                    'photo_path' => $data['photo']->store('photos/students', 'public'),
                ]);
            }

            if ($enrollments !== []) {
                $this->syncEnrollments($student, $enrollments);
            }

            return $student->fresh(['parent', 'branch', 'standard', 'enrollments.batch', 'enrollments.subject']);
        });

        InstituteDashboardService::forgetForInstitute((int) $updated->institute_id);

        return $updated;
    }

    public function delete(Student $student): void
    {
        $instituteId = (int) $student->institute_id;

        DB::transaction(function () use ($student) {
            $student->loadMissing(['user', 'parent.user']);
            $parent = $student->parent;

            StudentBatch::query()->where('student_id', $student->id)->delete();
            $student->user?->update(['is_active' => false]);
            $student->delete();

            if ($parent) {
                $siblingsLeft = Student::query()
                    ->where('parent_id', $parent->id)
                    ->count();

                if ($siblingsLeft === 0) {
                    $parent->user?->update(['is_active' => false]);
                }
            }
        });

        InstituteDashboardService::forgetForInstitute($instituteId);
    }

    public function toggleStatus(Student $student): Student
    {
        $active = $student->status !== 'active';
        $student->update(['status' => $active ? 'active' : 'inactive']);
        $student->user?->update(['is_active' => $active]);

        InstituteDashboardService::forgetForInstitute((int) $student->institute_id);

        return $student->fresh(['branch', 'standard', 'parent', 'user']);
    }

    public function studentLimitError(int $instituteId): ?string
    {
        $institute = Institute::query()->with('currentSubscription.plan')->find($instituteId);

        return app(\App\Services\Billing\PlanLimitService::class)->studentLimitError($institute);
    }

    /**
     * Next numeric roll no for a batch (max existing + 1). Non-numeric rolls are ignored.
     *
     * @param  array<int, int|string>  $reserved  Rolls already claimed in this request
     */
    public function nextRollNoForBatch(int $batchId, ?int $excludeStudentId = null, array $reserved = []): string
    {
        $query = StudentBatch::query()->where('batch_id', $batchId);

        if ($excludeStudentId) {
            $query->where('student_id', '!=', $excludeStudentId);
        }

        $max = 0;
        foreach ($query->pluck('roll_no') as $roll) {
            if ($roll !== null && $roll !== '' && is_numeric($roll)) {
                $max = max($max, (int) $roll);
            }
        }

        foreach ($reserved as $roll) {
            if ($roll !== null && $roll !== '' && is_numeric($roll)) {
                $max = max($max, (int) $roll);
            }
        }

        return (string) ($max + 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $enrollments
     */
    protected function syncEnrollments(Student $student, array $enrollments): void
    {
        StudentBatch::query()->where('student_id', $student->id)->delete();

        /** @var array<int, list<string>> $reservedByBatch */
        $reservedByBatch = [];

        foreach ($enrollments as $row) {
            if (empty($row['batch_id'])) {
                continue;
            }

            $batchId = (int) $row['batch_id'];
            $rollNo = isset($row['roll_no']) ? trim((string) $row['roll_no']) : '';

            if ($rollNo === '') {
                $rollNo = $this->nextRollNoForBatch($batchId, null, $reservedByBatch[$batchId] ?? []);
            }

            $reservedByBatch[$batchId] ??= [];
            $reservedByBatch[$batchId][] = $rollNo;

            StudentBatch::query()->create([
                'student_id' => $student->id,
                'batch_id' => $batchId,
                'subject_id' => ! empty($row['subject_id']) ? (int) $row['subject_id'] : null,
                'roll_no' => $rollNo,
                'total_fees' => $row['total_fees'] ?? 0,
                'discount' => $row['discount'] ?? 0,
                'status' => $row['status'] ?? 'active',
                'enrolled_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveParent(int $instituteId, array $data, ?ParentGuardian $existing = null): ParentGuardian
    {
        $email = $data['father_email'] ?? $data['email'] ?? $existing?->email;
        $phone = $data['father_phone'] ?? $data['phone'] ?? $existing?->phone;

        $parent = $existing;

        if (! $parent && ($email || $phone)) {
            $parent = ParentGuardian::query()
                ->where('institute_id', $instituteId)
                ->where(function ($q) use ($email, $phone) {
                    if ($email) {
                        $q->orWhere('email', $email);
                    }
                    if ($phone) {
                        $q->orWhere('phone', $phone);
                    }
                })
                ->first();
        }

        $parentData = [
            'institute_id' => $instituteId,
            'father_name' => $data['father_name'] ?? $existing?->father_name,
            'mother_name' => $data['mother_name'] ?? $existing?->mother_name,
            'email' => $email,
            'phone' => $phone,
            'occupation' => $data['father_occupation'] ?? $existing?->occupation,
            'address' => $data['address'] ?? $existing?->address,
        ];

        if ($parent) {
            $parent->update($parentData);
        } else {
            $parent = ParentGuardian::query()->create($parentData);
        }

        $this->ensureParentLogin($parent, $instituteId, $data);

        return $parent->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function ensureParentLogin(ParentGuardian $parent, int $instituteId, array $data): void
    {
        if (! $parent->email) {
            return;
        }

        $password = $data['parent_password'] ?? $data['password'] ?? null;

        $user = $parent->user;

        if (! $user) {
            $user = User::query()->where('email', $parent->email)->first();
        }

        // A staff or institute-admin login must stay that login. Using the
        // same address as the father's email used to turn the admin into a parent.
        if ($user && $user->user_type !== UserType::Parent) {
            if ((int) $parent->user_id === (int) $user->id) {
                $parent->update(['user_id' => null]);
            }

            return;
        }

        if (! $user) {
            $user = User::query()->create([
                'institute_id' => $instituteId,
                'user_type' => UserType::Parent,
                'name' => $parent->father_name ?: 'Parent',
                'email' => $parent->email,
                'phone' => $parent->phone,
                'password' => Hash::make($password ?: 'password'),
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => true,
            ]);
            $parent->update(['user_id' => $user->id]);
        } else {
            $user->update([
                'user_type' => UserType::Parent,
                'name' => $parent->father_name ?: $user->name,
                'phone' => $parent->phone,
            ]);
            if ($password) {
                $user->update([
                    'password' => Hash::make($password),
                    'must_change_password' => true,
                ]);
            }
            if (! $parent->user_id) {
                $parent->update(['user_id' => $user->id]);
            }
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($instituteId);

        $role = Role::query()->firstOrCreate(
            [
                'name' => 'parent',
                'guard_name' => 'web',
                'team_id' => $instituteId,
            ],
            [
                'is_system' => true,
                'status' => 'active',
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }

    protected function nextAdmissionNo(int $instituteId): string
    {
        return \App\Support\SequenceGenerator::forInstitute($instituteId, function () use ($instituteId) {
            $year = now()->format('Y');
            $n = Student::withTrashed()->where('institute_id', $instituteId)->count() + 1;

            return 'ADM-'.$year.'-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * @return array<int, array{id: int, name: string, subjects: array}>
     */
    public function batchesWithSubjects(int $instituteId, ?int $branchId = null): array
    {
        $query = Batch::query()
            ->where('institute_id', $instituteId)
            ->where('status', 'active')
            ->with(['subjects:id,name']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('name')->get()->map(fn (Batch $batch) => [
            'id' => $batch->id,
            'name' => $batch->name,
            'branch_id' => $batch->branch_id,
            'subjects' => $batch->subjects->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])->values()->all(),
        ])->all();
    }
}
