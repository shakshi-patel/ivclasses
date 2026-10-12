<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permission_denials', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['team_id', 'user_id', 'permission_id'], 'user_permission_denials_primary');
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission_denials');
    }
};
