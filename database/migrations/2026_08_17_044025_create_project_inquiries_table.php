<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_inquiries', function (Blueprint $table) {
            $table->id();

            $table->string('inquiry_code', 30)->unique();

            $table->string('institute_name', 150);
            $table->string('contact_person_name', 100);

            $table->string('email', 150);
            $table->string('contact_no', 20);

            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->text('address')->nullable();

            $table->unsignedInteger('student_count')->nullable();
            $table->unsignedInteger('branch_count')->nullable();

            $table->text('requirement')->nullable();
            $table->text('message')->nullable();

            $table->string('source', 50)->default('website');

            $table->string('status', 30)->default('new');

            $table->date('demo_date')->nullable();
            $table->time('demo_start_time')->nullable();
            $table->time('demo_end_time')->nullable();
            $table->string('timezone', 50)->default('Asia/Kolkata');

            $table->string('google_event_id', 255)->nullable();
            $table->string('google_meet_link', 500)->nullable();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_inquiries');
    }
};
