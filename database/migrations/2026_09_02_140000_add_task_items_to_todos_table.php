<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->json('task_items')->nullable()->after('title');
        });

        foreach (DB::table('todos')->orderBy('id')->get() as $todo) {
            DB::table('todos')->where('id', $todo->id)->update([
                'task_items' => json_encode(array_values(array_filter([$todo->title]))),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn('task_items');
        });
    }
};
