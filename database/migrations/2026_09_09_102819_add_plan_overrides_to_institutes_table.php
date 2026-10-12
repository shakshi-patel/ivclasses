<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->json('modules_extra_json')->nullable()->after('settings_json');
            $table->json('modules_blocked_json')->nullable()->after('modules_extra_json');
            $table->unsignedInteger('max_branches_override')->nullable()->after('modules_blocked_json');
            $table->unsignedInteger('max_students_override')->nullable()->after('max_branches_override');
            $table->unsignedInteger('max_staff_override')->nullable()->after('max_students_override');
        });
    }

    public function down(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->dropColumn([
                'modules_extra_json',
                'modules_blocked_json',
                'max_branches_override',
                'max_students_override',
                'max_staff_override',
            ]);
        });
    }
};
