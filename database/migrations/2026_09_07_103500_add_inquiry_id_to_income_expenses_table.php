<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->foreignId('inquiry_id')
                ->nullable()
                ->after('fee_payment_id')
                ->constrained('inquiries')
                ->nullOnDelete();

            $table->unique('inquiry_id');
        });
    }

    public function down(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->dropForeign(['inquiry_id']);
            $table->dropUnique(['inquiry_id']);
            $table->dropColumn('inquiry_id');
        });
    }
};
