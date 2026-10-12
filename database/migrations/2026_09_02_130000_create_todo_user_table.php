<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['todo_id', 'user_id']);
        });

        foreach (DB::table('todos')->whereNotNull('assigned_to_user_id')->get() as $todo) {
            DB::table('todo_user')->insertOrIgnore([
                'todo_id' => $todo->id,
                'user_id' => $todo->assigned_to_user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_user');
    }
};
