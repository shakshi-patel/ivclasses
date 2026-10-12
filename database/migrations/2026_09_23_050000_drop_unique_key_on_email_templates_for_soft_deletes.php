<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            // Soft-deleted rows keep the same key; uniqueness is enforced for active rows in app validation.
            $table->dropUnique(['key']);
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropIndex(['key']);
            $table->unique('key');
        });
    }
};
