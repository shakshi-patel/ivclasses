<?php

namespace Database\Seeders;

use App\Enums\SubscriptionStatus;
use App\Models\Batch;
use App\Models\Branch;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FeeFollowUp;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Holiday;
use App\Models\Homework;
use App\Models\IncomeExpense;
use App\Models\Inquiry;
use App\Models\Institute;
use App\Models\InventoryAsset;
use App\Models\LeaveRequest;
use App\Models\LoginLog;
use App\Models\Notice;
use App\Models\Plan;
use App\Models\ResultAnnouncement;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentBatch;
use App\Models\StudyMaterial;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Models\Todo;
use App\Models\User;
use App\Services\IncomeExpenseService;
use App\Services\InquiryService;
use App\Services\InventoryAssetService;
use App\Services\StaffService;
use App\Services\StudentService;
use App\Services\TodoService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fills institute_id=5 (kaksha academy) with ~2 months of CRM demo data.
 *
 * Run: php artisan db:seed --class=Institute5CrmDemoSeeder
 */
class Institute5CrmDemoSeeder extends Seeder
{
    protected int $instituteId = 5;

    protected string $emailPrefix = 'seed5.';

    protected Carbon $from;

    protected Carbon $to;

    public function run(): void
    {
        $this->to = now('Asia/Kolkata')->startOfDay();
        $this->from = $this->to->copy()->subDays(55);

        $institute = Institute::query()->find($this->instituteId);
        if (! $institute) {
            $this->command?->error("Institute {$this->instituteId} not found.");

            return;
        }

        $this->command?->info("Seeding CRM demo data for [{$institute->id}] {$institute->name} ({$this->from->toDateString()} → {$this->to->toDateString()})");

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->instituteId);
        app()->instance('currentInstituteId', $this->instituteId);

        $admin = User::query()
            ->where('institute_id', $this->instituteId)
            ->where('email', 'anandraval@gmail.com')
            ->first()
            ?? User::query()->where('institute_id', $this->instituteId)->orderBy('id')->first();

        if ($admin) {
            auth()->login($admin);
        }

        $this->upgradePlan($institute);
        app(StaffService::class)->ensureDefaultRoles($this->instituteId);
        app(IncomeExpenseService::class)->ensureDefaultCategories($this->instituteId);
        app(InventoryAssetService::class)->ensureDefaultCategories($this->instituteId);

        $branches = Branch::query()->where('institute_id', $this->instituteId)->where('status', 'active')->get();
        $batches = Batch::query()
            ->where('institute_id', $this->instituteId)
            ->where('status', 'active')
            ->with(['subjects:id,name', 'teachers'])
            ->get();
        $teachers = Teacher::query()->where('institute_id', $this->instituteId)->where('status', 'active')->with('user')->get();
        $staff = Staff::query()->where('institute_id', $this->instituteId)->where('status', 'active')->with('user')->get();

        if ($branches->isEmpty() || $batches->isEmpty()) {
            $this->command?->error('Need at least 1 branch + 1 batch on institute 5.');

            return;
        }

        $students = $this->ensureStudents($batches);
        $this->command?->info('Students ready: '.$students->count());

        $this->seedHolidays($branches);
        $this->seedNotices($branches, $batches);
        $this->seedInquiries($branches, $batches);
        $this->seedHomework($batches, $teachers);
        $this->seedExamsAndResults($batches, $students);
        $this->seedStudentAttendance($batches, $students);
        $this->seedStaffAttendance($branches, $teachers, $staff);
        $this->seedFees($students);
        $this->seedIncomeExpense($branches);
        $this->seedInventory($branches);
        $this->seedTodos($staff, $teachers);
        $this->seedStudyMaterials($batches, $teachers, $admin);
        $this->seedLeaves($branches, $teachers, $staff, $students);
        $this->seedLoginLogs($admin, $teachers, $staff);

        auth()->logout();
        $this->command?->info('Institute 5 CRM demo seed complete.');
    }

    protected function upgradePlan(Institute $institute): void
    {
        $plan = Plan::query()->where('code', 'enterprise')->first()
            ?? Plan::query()->where('code', 'growth')->first();

        if (! $plan) {
            return;
        }

        // Keep a single active subscription row so latestOfMany() resolves correctly.
        $latest = Subscription::query()
            ->where('institute_id', $institute->id)
            ->latest('id')
            ->first();

        if ($latest) {
            $latest->update([
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->addYear(),
                'status' => SubscriptionStatus::Active,
                'amount' => $plan->price_monthly ?? 0,
            ]);
        } else {
            Subscription::query()->create([
                'institute_id' => $institute->id,
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->addYear(),
                'status' => SubscriptionStatus::Active,
                'amount' => $plan->price_monthly ?? 0,
            ]);
        }

        Subscription::query()
            ->where('institute_id', $institute->id)
            ->when($latest, fn ($q) => $q->where('id', '!=', $latest->id))
            ->update(['status' => SubscriptionStatus::Cancelled]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Batch>  $batches
     * @return \Illuminate\Support\Collection<int, Student>
     */
    protected function ensureStudents($batches)
    {
        $service = app(StudentService::class);
        $firstNames = ['Aarav', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Krishna', 'Ishaan', 'Shaurya', 'Ananya', 'Aadhya', 'Diya', 'Myra', 'Anika', 'Ira', 'Kiara', 'Pari', 'Sara', 'Navya', 'Kabir', 'Atharv', 'Dhruv', 'Rudra', 'Advait', 'Prisha', 'Meera', 'Riya', 'Tara', 'Nisha'];
        $lastNames = ['Patel', 'Shah', 'Mehta', 'Desai', 'Joshi', 'Trivedi', 'Raval', 'Pandya', 'Chauhan', 'Parmar'];

        $targetPerBatch = 3;
        $created = 0;

        foreach ($batches as $batchIndex => $batch) {
            $existing = StudentBatch::query()
                ->where('batch_id', $batch->id)
                ->where('status', 'active')
                ->distinct('student_id')
                ->count('student_id');

            $need = max(0, $targetPerBatch - $existing);
            $subjectId = (int) ($batch->subjects->first()?->id ?? 0);

            for ($i = 0; $i < $need; $i++) {
                $n = ($batchIndex * 10) + $i + 1;
                $fname = $firstNames[($batchIndex * 3 + $i) % count($firstNames)];
                $lname = $lastNames[($batchIndex + $i) % count($lastNames)];
                $name = $fname.' '.$lname;
                $parentEmail = $this->emailPrefix.'parent'.$batch->id.'_'.$n.'@kaksha.test';

                if (User::query()->where('email', $parentEmail)->exists()) {
                    continue;
                }

                $roll = (string) ($existing + $i + 1);
                $service->create([
                    'institute_id' => $this->instituteId,
                    'branch_id' => $batch->branch_id,
                    'name' => $name,
                    'gender' => $i % 2 === 0 ? 'Male' : 'Female',
                    'dob' => now()->subYears(14 + ($i % 4))->subDays($n)->toDateString(),
                    'standard_id' => $batch->standard_id,
                    'school_name' => 'Demo School '.$batch->branch_id,
                    'admission_date' => $this->from->copy()->addDays($n % 20)->toDateString(),
                    'address' => 'Seed street '.$n.', Gujarat',
                    'category' => 'General',
                    'form_fees' => 200,
                    'form_fees_payment_method' => 'Cash',
                    'status' => 'active',
                    'father_name' => $lastNames[($batchIndex + $i + 2) % count($lastNames)] === $lname
                        ? $firstNames[($i + 5) % count($firstNames)].' '.$lname
                        : $firstNames[($i + 7) % count($firstNames)].' '.$lname,
                    'mother_name' => $firstNames[($i + 11) % count($firstNames)].' '.$lname,
                    'father_email' => $parentEmail,
                    'father_phone' => '98'.str_pad((string) (50000000 + $batch->id * 100 + $n), 8, '0', STR_PAD_LEFT),
                    'father_occupation' => 'Business',
                    'parent_password' => 'password',
                ], [[
                    'batch_id' => $batch->id,
                    'subject_id' => $subjectId ?: null,
                    'roll_no' => $roll,
                    'total_fees' => 12000,
                    'discount' => ($i === 0) ? 500 : 0,
                    'status' => 'active',
                    'enrolled_at' => $this->from->copy()->addDays($i)->toDateString(),
                ]]);
                $created++;
            }
        }

        $this->command?->info("Created {$created} seed students.");

        return Student::query()
            ->where('institute_id', $this->instituteId)
            ->where('status', 'active')
            ->with(['enrollments'])
            ->get();
    }

    protected function seedHolidays($branches): void
    {
        $items = [
            ['Independence Day', $this->to->copy()->month(8)->day(15)->year($this->from->year)],
            ['Ganesh Chaturthi', $this->from->copy()->addDays(12)],
            ['Staff Training Holiday', $this->from->copy()->addDays(28)],
            ['Diwali Prep Holiday', $this->to->copy()->subDays(9)],
        ];

        foreach ($items as [$title, $date]) {
            if ($date->lt($this->from) || $date->gt($this->to)) {
                continue;
            }
            $holiday = Holiday::query()->firstOrCreate(
                [
                    'institute_id' => $this->instituteId,
                    'title' => $title,
                    'start_date' => $date->toDateString(),
                ],
                [
                    'end_date' => $date->toDateString(),
                    'reason' => 'Demo holiday',
                ]
            );
            $holiday->branches()->syncWithoutDetaching($branches->pluck('id')->all());
        }
    }

    protected function seedNotices($branches, $batches): void
    {
        $titles = [
            'Fee reminder for this month',
            'Parent-teacher meeting schedule',
            'Weekly test syllabus update',
            'Holiday notice for festival',
            'Bring notebooks tomorrow',
            'Sports day practice',
            'New batch timing update',
            'Library books return reminder',
        ];

        foreach ($titles as $i => $title) {
            $date = $this->from->copy()->addDays(3 + $i * 6);
            if ($date->gt($this->to)) {
                $date = $this->to->copy()->subDays($i);
            }

            $notice = Notice::query()->firstOrCreate(
                [
                    'institute_id' => $this->instituteId,
                    'title' => '[SEED] '.$title,
                ],
                [
                    'body' => $title.' — demo notice for kaksha academy.',
                    'notice_date' => $date->toDateString(),
                    'audience' => match ($i % 4) {
                        1 => Notice::AUDIENCE_PARENTS,
                        2 => Notice::AUDIENCE_STUDENTS,
                        3 => Notice::AUDIENCE_TEACHERS,
                        default => Notice::AUDIENCE_ALL,
                    },
                    'status' => 'active',
                    'published_at' => $date,
                ]
            );

            if ($i % 2 === 0) {
                $notice->branches()->syncWithoutDetaching([$branches[$i % $branches->count()]->id]);
            } else {
                $batch = $batches[$i % $batches->count()];
                $notice->batches()->syncWithoutDetaching([$batch->id]);
                $notice->branches()->syncWithoutDetaching([(int) $batch->branch_id]);
            }
        }
    }

    protected function seedInquiries($branches, $batches): void
    {
        $service = app(InquiryService::class);
        $sources = ['walk-in', 'phone', 'referral', 'online', 'campaign'];

        for ($i = 1; $i <= 18; $i++) {
            $branch = $branches[$i % $branches->count()];
            $batch = $batches->firstWhere('branch_id', $branch->id) ?? $batches->first();
            $date = $this->from->copy()->addDays($i * 2);
            if ($date->gt($this->to)) {
                $date = $this->to->copy()->subDays($i % 10);
            }

            $phone = '97'.str_pad((string) (60000000 + $i), 8, '0', STR_PAD_LEFT);
            $exists = Inquiry::query()
                ->where('institute_id', $this->instituteId)
                ->where('phone', $phone)
                ->exists();
            if ($exists) {
                continue;
            }

            $inquiry = $service->create([
                'institute_id' => $this->instituteId,
                'branch_id' => $branch->id,
                'student_name' => 'Inquiry Student '.$i,
                'father_name' => 'Inquiry Father '.$i,
                'mother_name' => 'Inquiry Mother '.$i,
                'phone' => $phone,
                'email' => $this->emailPrefix.'inquiry'.$i.'@kaksha.test',
                'gender' => $i % 2 ? 'Male' : 'Female',
                'inquiry_date' => $date->toDateString(),
                'school_name' => 'Nearby School '.$i,
                'standard_id' => $batch->standard_id,
                'interested_subject' => 'Maths / Science',
                'source' => $sources[$i % count($sources)],
                'form_fees' => $i % 3 === 0 ? 100 : 0,
                'form_fees_payment_method' => $i % 3 === 0 ? 'Cash' : null,
                'address' => 'Inquiry address '.$i,
                'follow_up_date' => $date->copy()->addDays(2)->toDateString(),
                'follow_up_remark' => 'Called parent — interested in '.$batch->name,
                'next_follow_up_at' => $date->copy()->addDays(5)->toDateString(),
                'notes' => 'Seed inquiry',
            ]);

            if ($i % 4 === 0) {
                $service->addFollowUp($inquiry, [
                    'follow_up_at' => $date->copy()->addDays(4),
                    'remark' => 'Second follow-up — visiting campus.',
                    'next_follow_up_at' => $date->copy()->addDays(8),
                ]);
            }
        }
    }

    protected function seedHomework($batches, $teachers): void
    {
        if (Homework::query()->where('institute_id', $this->instituteId)->where('title', 'like', '[SEED]%')->exists()) {
            $this->command?->info('Homework seed already present — skipping create.');

            return;
        }

        $topics = ['Algebra worksheet', 'Chapter revision', 'Grammar practice', 'Science diagram', 'Weekly test prep', 'Reading comprehension'];

        foreach ($batches as $bIndex => $batch) {
            $subjectId = $batch->subjects->first()?->id;
            $teacherUserId = $batch->teachers->first()?->id
                ?? $teachers[$bIndex % max(1, $teachers->count())]->user_id
                ?? null;

            for ($w = 0; $w < 8; $w++) {
                $hwDate = $this->from->copy()->addWeeks($w)->next(Carbon::MONDAY)->addDays($bIndex % 3);
                if ($hwDate->gt($this->to)) {
                    break;
                }
                Homework::query()->create([
                    'institute_id' => $this->instituteId,
                    'branch_id' => $batch->branch_id,
                    'batch_id' => $batch->id,
                    'subject_id' => $subjectId,
                    'teacher_user_id' => $teacherUserId,
                    'title' => '[SEED] '.$topics[$w % count($topics)].' — '.$batch->name,
                    'type' => 'text',
                    'description' => 'Complete exercises from class notes. Demo homework.',
                    'homework_date' => $hwDate->toDateString(),
                    'due_date' => $hwDate->copy()->addDays(3)->toDateString(),
                ]);
            }
        }
    }

    protected function seedExamsAndResults($batches, $students): void
    {
        if (Exam::query()->where('institute_id', $this->instituteId)->where('title', 'like', '[SEED]%')->exists()) {
            $this->command?->info('Exam seed already present — skipping create.');

            return;
        }

        $enrollByBatch = StudentBatch::query()
            ->whereIn('batch_id', $batches->pluck('id'))
            ->where('status', 'active')
            ->get()
            ->groupBy('batch_id');

        foreach ($batches as $bIndex => $batch) {
            $subjectId = $batch->subjects->first()?->id;
            $studentIds = ($enrollByBatch->get($batch->id) ?? collect())->pluck('student_id')->unique()->values();

            for ($e = 0; $e < 3; $e++) {
                $examDate = $this->from->copy()->addDays(10 + $e * 18 + ($bIndex % 4));
                if ($examDate->gt($this->to)) {
                    $examDate = $this->to->copy()->subDays(2 + $e);
                }

                $exam = Exam::query()->create([
                    'institute_id' => $this->instituteId,
                    'branch_id' => $batch->branch_id,
                    'batch_id' => $batch->id,
                    'subject_id' => $subjectId,
                    'title' => '[SEED] Unit Test '.($e + 1).' — '.$batch->name,
                    'exam_date' => $examDate->toDateString(),
                    'start_time' => '10:00:00',
                    'end_time' => '11:30:00',
                    'duration_label' => '1.5 hours',
                    'total_marks' => 50,
                    'description' => 'Demo unit test',
                    'status' => 'completed',
                ]);

                $rows = [];
                foreach ($studentIds as $si => $studentId) {
                    $absent = $si % 7 === 0;
                    $marks = $absent ? null : random_int(18, 48);
                    $rows[] = [
                        'student_id' => $studentId,
                        'marks_obtained' => $marks,
                        'grade' => $absent ? null : ($marks >= 40 ? 'A' : ($marks >= 30 ? 'B' : 'C')),
                        'is_attempted' => ! $absent,
                        'attendance_status' => $absent ? 'absent' : 'present',
                        'remark' => $absent ? 'Absent' : null,
                    ];

                    ExamResult::query()->updateOrCreate(
                        ['exam_id' => $exam->id, 'student_id' => $studentId],
                        [
                            'marks_obtained' => $marks,
                            'grade' => $absent ? null : ($marks >= 40 ? 'A' : ($marks >= 30 ? 'B' : 'C')),
                            'is_attempted' => ! $absent,
                            'attendance_status' => $absent ? 'absent' : 'present',
                            'remark' => $absent ? 'Absent' : null,
                        ]
                    );
                }

                ResultAnnouncement::query()->updateOrCreate(
                    ['exam_id' => $exam->id],
                    [
                        'institute_id' => $this->instituteId,
                        'title' => $exam->title.' Results',
                        'body' => 'Results published for demo.',
                        'result_date' => $examDate->copy()->addDays(2)->toDateString(),
                        'published_at' => $examDate->copy()->addDays(2),
                    ]
                );
            }
        }
    }

    protected function seedStudentAttendance($batches, $students): void
    {
        $holidayDates = Holiday::query()
            ->where('institute_id', $this->instituteId)
            ->get()
            ->flatMap(function (Holiday $h) {
                return collect(CarbonPeriod::create($h->start_date, $h->end_date))
                    ->map(fn (Carbon $d) => $d->toDateString());
            })
            ->unique()
            ->all();

        $enrollments = StudentBatch::query()
            ->whereIn('batch_id', $batches->pluck('id'))
            ->where('status', 'active')
            ->get()
            ->groupBy('batch_id');

        $statuses = ['present', 'present', 'present', 'present', 'late', 'absent'];
        $inserted = 0;

        foreach ($batches as $batch) {
            $batchStudents = $enrollments->get($batch->id) ?? collect();
            if ($batchStudents->isEmpty()) {
                continue;
            }

            foreach (CarbonPeriod::create($this->from, $this->to) as $day) {
                /** @var Carbon $day */
                if ($day->isWeekend()) {
                    continue;
                }
                $date = $day->toDateString();
                if (in_array($date, $holidayDates, true)) {
                    continue;
                }

                foreach ($batchStudents as $enroll) {
                    $status = $statuses[($enroll->student_id + $day->dayOfYear) % count($statuses)];
                    StudentAttendance::query()->updateOrCreate(
                        [
                            'student_id' => $enroll->student_id,
                            'batch_id' => $batch->id,
                            'attendance_date' => $date,
                        ],
                        [
                            'institute_id' => $this->instituteId,
                            'branch_id' => $batch->branch_id,
                            'status' => $status,
                            'remark' => $status === 'absent' ? 'Seed absent' : null,
                        ]
                    );
                    $inserted++;
                }
            }
        }

        $this->command?->info("Student attendance upserts: {$inserted}");
    }

    protected function seedStaffAttendance($branches, $teachers, $staff): void
    {
        $users = collect()
            ->merge($teachers->pluck('user_id'))
            ->merge($staff->pluck('user_id'))
            ->unique()
            ->values();

        $branchByUser = [];
        foreach ($users as $userId) {
            $primary = DB::table('branch_user')
                ->where('user_id', $userId)
                ->orderByDesc('is_primary')
                ->value('branch_id');
            $branchByUser[$userId] = $primary ?: $branches->first()->id;
        }

        $statuses = ['present', 'present', 'present', 'late', 'absent'];
        $count = 0;

        foreach (CarbonPeriod::create($this->from, $this->to) as $day) {
            /** @var Carbon $day */
            if ($day->isWeekend()) {
                continue;
            }
            $date = $day->toDateString();

            foreach ($users as $userId) {
                $status = $statuses[($userId + $day->dayOfYear) % count($statuses)];
                StaffAttendance::query()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'attendance_date' => $date,
                    ],
                    [
                        'institute_id' => $this->instituteId,
                        'branch_id' => $branchByUser[$userId],
                        'status' => $status,
                        'source' => 'manual',
                        'late_minutes' => $status === 'late' ? random_int(5, 25) : 0,
                        'check_in_at' => $status === 'absent' ? null : $date.' 09:'.str_pad((string) random_int(0, 25), 2, '0', STR_PAD_LEFT).':00',
                        'check_out_at' => $status === 'absent' ? null : $date.' 17:00:00',
                        'remark' => $status === 'absent' ? 'On leave / absent' : null,
                    ]
                );
                $count++;
            }
        }

        $this->command?->info("Staff attendance upserts: {$count}");
    }

    protected function seedFees($students): void
    {
        $methods = ['Cash', 'UPI', 'Online', 'Cheque'];
        $payments = 0;

        foreach ($students as $student) {
            foreach ($student->enrollments->where('status', 'active') as $enrollment) {
                $total = (float) $enrollment->total_fees;
                $discount = (float) ($enrollment->discount ?? 0);
                $net = max(0, $total - $discount);

                $invoice = FeeInvoice::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'student_batch_id' => $enrollment->id,
                    ],
                    [
                        'institute_id' => $this->instituteId,
                        'branch_id' => $student->branch_id,
                        'title' => ($enrollment->batch?->name ?? 'Batch').' Fees',
                        'total_amount' => $total,
                        'discount' => $discount,
                        'net_amount' => $net,
                        'paid_amount' => 0,
                        'due_amount' => $net,
                        'status' => $net > 0 ? 'unpaid' : 'paid',
                        'due_date' => $this->from->copy()->addDays(20)->toDateString(),
                    ]
                );

                if ($net <= 0) {
                    continue;
                }

                $existingPaid = (float) FeePayment::query()->where('fee_invoice_id', $invoice->id)->sum('amount');
                if ($existingPaid > 0) {
                    continue;
                }

                $first = round($net * 0.4, 2);
                $second = round($net * 0.3, 2);

                foreach ([[$first, 5], [$second, 25]] as [$amount, $offset]) {
                    if ($amount <= 0) {
                        continue;
                    }
                    $paymentDate = $this->from->copy()->addDays($offset + ($student->id % 7));
                    $payment = FeePayment::query()->create([
                        'fee_invoice_id' => $invoice->id,
                        'student_id' => $student->id,
                        'amount' => $amount,
                        'payment_date' => $paymentDate->toDateString(),
                        'payment_method' => $methods[$student->id % count($methods)],
                        'receipt_no' => 'RCP-SEED-'.$invoice->id.'-'.$offset,
                        'note' => 'Seed installment',
                    ]);
                    app(IncomeExpenseService::class)->createFromFeePayment($payment);
                    $payments++;
                }

                $paid = (float) FeePayment::query()->where('fee_invoice_id', $invoice->id)->sum('amount');
                $due = max(0, $net - $paid);
                $invoice->update([
                    'paid_amount' => $paid,
                    'due_amount' => $due,
                    'status' => $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                ]);

                if ($due > 0) {
                    FeeFollowUp::query()->firstOrCreate(
                        [
                            'fee_invoice_id' => $invoice->id,
                            'remark' => '[SEED] Pending fee reminder call',
                        ],
                        [
                            'follow_up_at' => $this->to->copy()->subDays(3),
                            'next_follow_up_at' => $this->to->copy()->addDays(3),
                            'status' => 'pending',
                        ]
                    );
                }
            }
        }

        $this->command?->info("Fee payments created: {$payments}");
    }

    protected function seedIncomeExpense($branches): void
    {
        $incomeCats = DB::table('ie_categories')
            ->where('institute_id', $this->instituteId)
            ->where('type', 'income')
            ->whereNull('deleted_at')
            ->pluck('id');
        $expenseCats = DB::table('ie_categories')
            ->where('institute_id', $this->instituteId)
            ->where('type', 'expense')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($incomeCats->isEmpty() || $expenseCats->isEmpty()) {
            return;
        }

        $expenseTitles = ['Electricity bill', 'Rent', 'Stationery purchase', 'Internet bill', 'Cleaning supplies', 'Marketing flyers', 'AC service', 'Tea / refreshment'];
        $incomeTitles = ['Donation', 'Misc income', 'Workshop fee', 'Book sale'];

        for ($i = 0; $i < 16; $i++) {
            $date = $this->from->copy()->addDays(2 + $i * 3);
            if ($date->gt($this->to)) {
                $date = $this->to->copy()->subDays($i % 5);
            }
            $branch = $branches[$i % $branches->count()];
            $isExpense = $i % 3 !== 0;

            IncomeExpense::query()->firstOrCreate(
                [
                    'institute_id' => $this->instituteId,
                    'title' => '[SEED] '.($isExpense ? $expenseTitles[$i % count($expenseTitles)] : $incomeTitles[$i % count($incomeTitles)]).' #'.$i,
                    'entry_date' => $date->toDateString(),
                ],
                [
                    'branch_id' => $branch->id,
                    'category_id' => $isExpense
                        ? $expenseCats[$i % $expenseCats->count()]
                        : $incomeCats[$i % $incomeCats->count()],
                    'type' => $isExpense ? 'expense' : 'income',
                    'amount' => $isExpense ? random_int(500, 8000) : random_int(300, 3000),
                    'payment_method' => $i % 2 ? 'Cash' : 'UPI',
                    'note' => 'Seed entry',
                ]
            );
        }
    }

    protected function seedInventory($branches): void
    {
        $cats = DB::table('asset_categories')
            ->where('institute_id', $this->instituteId)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($cats->isEmpty()) {
            return;
        }

        $items = [
            ['Whiteboard', 4, 'working'],
            ['Projector', 2, 'working'],
            ['Bench set', 20, 'working'],
            ['Ceiling fan', 8, 'repair'],
            ['Computer desktop', 3, 'working'],
            ['Printer', 1, 'damaged'],
            ['Water cooler', 2, 'working'],
            ['Sound system', 1, 'working'],
        ];

        foreach ($branches as $bi => $branch) {
            foreach ($items as $ii => [$name, $qty, $status]) {
                InventoryAsset::query()->firstOrCreate(
                    [
                        'institute_id' => $this->instituteId,
                        'branch_id' => $branch->id,
                        'name' => '[SEED] '.$name,
                    ],
                    [
                        'category_id' => $cats[($bi + $ii) % $cats->count()],
                        'quantity' => $qty,
                        'purchase_date' => $this->from->copy()->addDays($ii * 4)->toDateString(),
                        'purchase_amount' => random_int(1500, 45000),
                        'status' => $status,
                        'note' => 'Demo inventory',
                    ]
                );
            }
        }
    }

    protected function seedTodos($staff, $teachers): void
    {
        $todoService = app(TodoService::class);
        $assignees = collect()
            ->merge($staff->pluck('user_id'))
            ->merge($teachers->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        if ($assignees->isEmpty()) {
            return;
        }

        if (Todo::query()->where('institute_id', $this->instituteId)->where('title', 'like', '[SEED]%')->exists()) {
            return;
        }

        $tasks = [
            ['Collect pending fees list', 'Call parents with due > 2000'],
            ['Prepare monthly attendance sheet', 'Share with branch managers'],
            ['Update notice board', 'Print fee reminder'],
            ['Check inventory stock', 'Order stationery'],
            ['Follow up new inquiries', 'Schedule campus visit'],
            ['Exam paper printing', 'Arrange invigilation'],
        ];

        foreach ($tasks as $i => $lines) {
            $userId = (int) $assignees[$i % $assignees->count()];
            $due = $this->from->copy()->addDays(7 + $i * 7);
            if ($due->gt($this->to)) {
                $due = $this->to->copy()->addDays($i);
            }
            $todoService->createGrouped(
                $this->instituteId,
                [$userId],
                array_map(fn ($t) => '[SEED] '.$t, $lines),
                $due->toDateString()
            );
        }
    }

    protected function seedStudyMaterials($batches, $teachers, ?User $admin): void
    {
        if (StudyMaterial::query()->where('institute_id', $this->instituteId)->where('title', 'like', '[SEED]%')->exists()) {
            $this->command?->info('Study material seed already present — skipping create.');

            return;
        }

        $titles = [
            'Chapter notes PDF',
            'Worksheet pack',
            'Formula sheet',
            'Revision booklet',
            'Practice questions',
            'Diagram handout',
        ];

        $created = 0;
        foreach ($batches as $bIndex => $batch) {
            $subjectId = $batch->subjects->first()?->id;
            $creatorId = $batch->teachers->first()?->id
                ?? $teachers[$bIndex % max(1, $teachers->count())]->user_id
                ?? $admin?->id;

            for ($n = 0; $n < 2; $n++) {
                $title = $titles[($bIndex + $n) % count($titles)];
                $fileName = 'seed-'.$batch->id.'-'.($n + 1).'.txt';
                $relative = "study-material/{$this->instituteId}/{$fileName}";
                Storage::disk('public')->put(
                    $relative,
                    "[SEED] {$title}\nBatch: {$batch->name}\nInstitute: {$this->instituteId}\nDemo study material for CRM testing.\n"
                );

                $createdAt = $this->from->copy()->addDays(4 + $bIndex * 2 + $n * 3);
                if ($createdAt->gt($this->to)) {
                    $createdAt = $this->to->copy()->subDays(($bIndex + $n) % 8);
                }

                $material = StudyMaterial::query()->create([
                    'institute_id' => $this->instituteId,
                    'branch_id' => $batch->branch_id,
                    'batch_id' => $batch->id,
                    'subject_id' => $subjectId,
                    'title' => '[SEED] '.$title.' — '.$batch->name,
                    'description' => 'Demo study material for '.$batch->name,
                    'file_path' => $relative,
                    'file_name' => $fileName,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ]);
                $material->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->save();
                $created++;
            }
        }

        $this->command?->info("Study materials created: {$created}");
    }

    protected function seedLeaves($branches, $teachers, $staff, $students): void
    {
        $staffUsers = $staff->take(4);
        foreach ($staffUsers as $i => $member) {
            $from = $this->from->copy()->addDays(8 + $i * 9);
            LeaveRequest::query()->firstOrCreate(
                [
                    'institute_id' => $this->instituteId,
                    'requester_user_id' => $member->user_id,
                    'from_date' => $from->toDateString(),
                    'reason' => '[SEED] Personal work',
                ],
                [
                    'branch_id' => $member->primaryBranch()?->id ?? $branches->first()->id,
                    'requester_type' => LeaveRequest::TYPE_STAFF,
                    'to_date' => $from->copy()->addDays(1)->toDateString(),
                    'status' => $i % 3 === 0 ? LeaveRequest::STATUS_PENDING : LeaveRequest::STATUS_APPROVED,
                    'reviewed_at' => $i % 3 === 0 ? null : $from,
                    'review_note' => $i % 3 === 0 ? null : 'Approved',
                ]
            );
        }

        foreach ($teachers->take(3) as $i => $teacher) {
            $from = $this->from->copy()->addDays(15 + $i * 10);
            LeaveRequest::query()->firstOrCreate(
                [
                    'institute_id' => $this->instituteId,
                    'requester_user_id' => $teacher->user_id,
                    'from_date' => $from->toDateString(),
                    'reason' => '[SEED] Family function',
                ],
                [
                    'branch_id' => $teacher->primaryBranch()?->id ?? $branches->first()->id,
                    'requester_type' => LeaveRequest::TYPE_STAFF,
                    'to_date' => $from->toDateString(),
                    'status' => LeaveRequest::STATUS_APPROVED,
                    'reviewed_at' => $from,
                ]
            );
        }

        foreach ($students->take(6) as $i => $student) {
            $from = $this->from->copy()->addDays(6 + $i * 7);
            LeaveRequest::query()->firstOrCreate(
                [
                    'institute_id' => $this->instituteId,
                    'student_id' => $student->id,
                    'from_date' => $from->toDateString(),
                    'reason' => '[SEED] Student medical leave',
                ],
                [
                    'branch_id' => $student->branch_id,
                    'requester_type' => LeaveRequest::TYPE_STUDENT,
                    'requester_user_id' => $student->parent?->user_id,
                    'to_date' => $from->copy()->addDays(1)->toDateString(),
                    'status' => $i % 2 ? LeaveRequest::STATUS_APPROVED : LeaveRequest::STATUS_PENDING,
                ]
            );
        }
    }

    protected function seedLoginLogs(?User $admin, $teachers, $staff): void
    {
        $users = collect([$admin])
            ->merge($teachers->pluck('user'))
            ->merge($staff->pluck('user'))
            ->filter()
            ->unique('id')
            ->values();

        foreach ($users as $i => $user) {
            for ($d = 0; $d < 12; $d++) {
                $at = $this->from->copy()->addDays($d * 4 + ($i % 3))->setTime(9, random_int(0, 40));
                if ($at->gt($this->to)) {
                    break;
                }
                LoginLog::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'event' => 'login',
                        'logged_at' => $at,
                    ],
                    [
                        'institute_id' => $this->instituteId,
                        'portal' => 'web',
                        'email' => $user->email,
                        'ip' => '127.0.0.'.(($i + $d) % 200 + 1),
                        'user_agent' => 'SeedBot/1.0',
                    ]
                );
                LoginLog::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'event' => 'logout',
                        'logged_at' => $at->copy()->addHours(8),
                    ],
                    [
                        'institute_id' => $this->instituteId,
                        'portal' => 'web',
                        'email' => $user->email,
                        'ip' => '127.0.0.'.(($i + $d) % 200 + 1),
                        'user_agent' => 'SeedBot/1.0',
                    ]
                );
            }
        }
    }
}
