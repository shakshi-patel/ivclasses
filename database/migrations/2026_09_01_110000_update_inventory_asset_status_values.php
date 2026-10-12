<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_assets')->where('status', 'active')->update(['status' => 'working']);
        DB::table('inventory_assets')->where('status', 'inactive')->update(['status' => 'damaged']);
    }

    public function down(): void
    {
        DB::table('inventory_assets')->where('status', 'working')->update(['status' => 'active']);
        DB::table('inventory_assets')->whereIn('status', ['repair', 'damaged'])->update(['status' => 'inactive']);
    }
};
