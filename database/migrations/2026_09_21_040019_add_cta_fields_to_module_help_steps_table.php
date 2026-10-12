<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_help_steps', function (Blueprint $table) {
            $table->string('cta_label', 120)->nullable()->after('media_caption');
            $table->string('cta_url', 500)->nullable()->after('cta_label');
        });
    }

    public function down(): void
    {
        Schema::table('module_help_steps', function (Blueprint $table) {
            $table->dropColumn(['cta_label', 'cta_url']);
        });
    }
};
