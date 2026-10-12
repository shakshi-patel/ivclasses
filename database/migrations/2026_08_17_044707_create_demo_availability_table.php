<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demo_availability', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('day_of_week');

            $table->time('start_time');
            $table->time('end_time');

            $table->unsignedSmallInteger('slot_duration_minutes')->default(45);
            $table->unsignedSmallInteger('buffer_minutes')->default(15);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['day_of_week', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_availability');
    }
};
