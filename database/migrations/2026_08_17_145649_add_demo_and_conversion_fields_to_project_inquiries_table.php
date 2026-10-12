<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // All required columns already exist in project_inquiries.
        // No changes required.
    }

    public function down(): void
    {
        // Nothing to rollback because the columns
        // were already created by the previous migration.
    }
};