<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Users who manage at least one branch → user_type = branch_manager
        DB::table('users')
            ->whereIn('id', function ($q) {
                $q->select('manager_user_id')
                    ->from('branches')
                    ->whereNotNull('manager_user_id');
            })
            ->where('user_type', '!=', 'platform_admin')
            ->update(['user_type' => 'branch_manager']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('user_type', 'branch_manager')
            ->update(['user_type' => 'institute_staff']);
    }
};
