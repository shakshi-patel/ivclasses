<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('standard_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institute_id', 'branch_id']);
            $table->index(['institute_id', 'standard_id']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('batch_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('fees_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'subject_id']);
        });

        Schema::create('batch_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['batch_id', 'teacher_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_teacher');
        Schema::dropIfExists('batch_subject');
        Schema::dropIfExists('batches');
    }
};
