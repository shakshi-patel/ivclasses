<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_branch', function (Blueprint $table) {
            $table->foreignId('notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->primary(['notice_id', 'branch_id']);
        });

        Schema::create('notice_batch', function (Blueprint $table) {
            $table->foreignId('notice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->primary(['notice_id', 'batch_id']);
        });

        $notices = DB::table('notices')->select('id', 'branch_id', 'batch_id')->get();
        foreach ($notices as $notice) {
            if ($notice->branch_id) {
                DB::table('notice_branch')->insert([
                    'notice_id' => $notice->id,
                    'branch_id' => $notice->branch_id,
                ]);
            }
            if ($notice->batch_id) {
                DB::table('notice_batch')->insert([
                    'notice_id' => $notice->id,
                    'batch_id' => $notice->batch_id,
                ]);
            }
        }

        Schema::table('notices', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['branch_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('institute_id')->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('notice_batch');
        Schema::dropIfExists('notice_branch');
    }
};
