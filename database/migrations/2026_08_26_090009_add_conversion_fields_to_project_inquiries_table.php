<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_inquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('project_inquiries', 'demo_status')) {
                $table->string('demo_status', 30)->default('requested')->after('status');
            }
            if (! Schema::hasColumn('project_inquiries', 'demo_scheduled_at')) {
                $table->timestamp('demo_scheduled_at')->nullable()->after('demo_end_time');
            }
            if (! Schema::hasColumn('project_inquiries', 'demo_completed_at')) {
                $table->timestamp('demo_completed_at')->nullable()->after('demo_scheduled_at');
            }
            if (! Schema::hasColumn('project_inquiries', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('admin_notes');
            }
            if (! Schema::hasColumn('project_inquiries', 'converted_institute_id')) {
                $table->foreignId('converted_institute_id')->nullable()->after('converted_at')
                    ->constrained('institutes')->nullOnDelete();
            }
            if (! Schema::hasColumn('project_inquiries', 'converted_class_id')) {
                $table->unsignedBigInteger('converted_class_id')->nullable()->after('converted_institute_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_inquiries', function (Blueprint $table) {
            if (Schema::hasColumn('project_inquiries', 'converted_institute_id')) {
                $table->dropConstrainedForeignId('converted_institute_id');
            }
            foreach (['demo_status', 'demo_scheduled_at', 'demo_completed_at', 'converted_at', 'converted_class_id'] as $col) {
                if (Schema::hasColumn('project_inquiries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
