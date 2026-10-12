<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_helps', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 80);
            $table->string('title');
            $table->text('intro')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('module_key');
            $table->index(['status', 'sort_order']);
        });

        Schema::create('module_help_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_help_id')->constrained('module_helps')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('media_type', 20)->default('none');
            $table->string('media_path')->nullable();
            $table->string('media_original_name')->nullable();
            $table->string('embed_url', 500)->nullable();
            $table->string('media_caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['module_help_id', 'sort_order']);
        });

        Schema::create('module_help_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_help_id')->constrained('module_helps')->cascadeOnDelete();
            $table->string('role_name', 100);
            $table->timestamps();

            $table->unique(['module_help_id', 'role_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_help_role');
        Schema::dropIfExists('module_help_steps');
        Schema::dropIfExists('module_helps');
    }
};
