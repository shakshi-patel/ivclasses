<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_helps', function (Blueprint $table) {
            $table->boolean('is_onboarding')->default(false)->after('status');
        });

        Schema::table('module_help_steps', function (Blueprint $table) {
            $table->boolean('is_required')->default(false)->after('placement');
            $table->string('completion_key', 40)->nullable()->after('is_required');
        });

        Schema::create('institute_onboarding_skips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('module_help_step_id')->constrained('module_help_steps')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['institute_id', 'module_help_step_id'], 'inst_onboarding_skip_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_onboarding_skips');

        Schema::table('module_help_steps', function (Blueprint $table) {
            $table->dropColumn(['is_required', 'completion_key']);
        });

        Schema::table('module_helps', function (Blueprint $table) {
            $table->dropColumn('is_onboarding');
        });
    }
};
