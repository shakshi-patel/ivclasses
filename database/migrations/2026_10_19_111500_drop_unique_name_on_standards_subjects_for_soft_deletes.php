<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standards', function (Blueprint $table) {
            // Soft-deleted rows keep the same name; active uniqueness is enforced in app validation.
            $table->dropUnique(['institute_id', 'name']);
            $table->index(['institute_id', 'name']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['institute_id', 'name']);
            $table->index(['institute_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('standards', function (Blueprint $table) {
            $table->dropIndex(['institute_id', 'name']);
            $table->unique(['institute_id', 'name']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['institute_id', 'name']);
            $table->unique(['institute_id', 'name']);
        });
    }
};
