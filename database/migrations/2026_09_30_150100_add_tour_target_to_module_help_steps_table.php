<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_help_steps', function (Blueprint $table) {
            $table->string('target_selector', 180)->nullable()->after('cta_url');
            $table->string('placement', 20)->default('bottom')->after('target_selector');
        });
    }

    public function down(): void
    {
        Schema::table('module_help_steps', function (Blueprint $table) {
            $table->dropColumn(['target_selector', 'placement']);
        });
    }
};
