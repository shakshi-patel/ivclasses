<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->foreignId('fee_payment_id')
                ->nullable()
                ->after('category_id')
                ->constrained('fee_payments')
                ->nullOnDelete();

            $table->unique('fee_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->dropForeign(['fee_payment_id']);
            $table->dropUnique(['fee_payment_id']);
            $table->dropColumn('fee_payment_id');
        });
    }
};
