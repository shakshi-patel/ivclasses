<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_branch', function (Blueprint $table) {
            $table->foreignId('holiday_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->primary(['holiday_id', 'branch_id']);
        });

        Schema::create('holiday_batch', function (Blueprint $table) {
            $table->foreignId('holiday_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->primary(['holiday_id', 'batch_id']);
        });

        $holidays = DB::table('holidays')->select('id', 'branch_id', 'batch_id')->get();
        foreach ($holidays as $holiday) {
            if ($holiday->branch_id) {
                DB::table('holiday_branch')->insert([
                    'holiday_id' => $holiday->id,
                    'branch_id' => $holiday->branch_id,
                ]);
            }
            if ($holiday->batch_id) {
                DB::table('holiday_batch')->insert([
                    'holiday_id' => $holiday->id,
                    'batch_id' => $holiday->batch_id,
                ]);
            }
        }

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['branch_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('institute_id')->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('holiday_batch');
        Schema::dropIfExists('holiday_branch');
    }
};
