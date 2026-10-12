<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institute_id', 'phone']);
            $table->index(['institute_id', 'email']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('parents')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('admission_no', 30);
            $table->string('name');
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('aadhaar', 20)->nullable();
            $table->string('category', 30)->nullable();
            $table->date('admission_date')->nullable();
            $table->string('school_name')->nullable();
            $table->foreignId('standard_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('form_fees', 10, 2)->default(0);
            $table->string('form_fees_payment_method', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institute_id', 'admission_no']);
            $table->index(['institute_id', 'branch_id', 'status']);
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('inquiry_code', 30);
            $table->string('student_name');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('aadhaar', 20)->nullable();
            $table->string('category', 30)->nullable();
            $table->date('inquiry_date')->nullable();
            $table->string('school_name')->nullable();
            $table->foreignId('standard_id')->nullable()->constrained()->nullOnDelete();
            $table->string('interested_subject')->nullable();
            $table->string('source', 30)->default('walk-in');
            $table->string('status', 20)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->decimal('form_fees', 10, 2)->default(0);
            $table->string('form_fees_payment_method', 30)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('father_occupation')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institute_id', 'inquiry_code']);
            $table->index(['institute_id', 'branch_id', 'status']);
        });

        Schema::create('inquiry_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->timestamp('follow_up_at');
            $table->text('remark')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('student_batch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('roll_no', 30)->nullable();
            $table->decimal('total_fees', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'batch_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_batch');
        Schema::dropIfExists('inquiry_follow_ups');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('students');
        Schema::dropIfExists('parents');
    }
};
