<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module')->nullable()->after('name');
            $table->string('action')->nullable()->after('module');
            $table->string('description')->nullable()->after('action');
            $table->boolean('is_active')->default(true)->after('description');
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_role_id')->nullable()->after('guard_name');
            $table->string('image_path')->nullable()->after('parent_role_id');
            $table->boolean('is_system')->default(false)->after('image_path');
            $table->string('status', 20)->default('active')->after('is_system');
            $table->softDeletes();

            $table->foreign('parent_role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_role_id']);
            $table->dropColumn(['parent_role_id', 'image_path', 'is_system', 'status', 'deleted_at']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['module', 'action', 'description', 'is_active', 'sort_order']);
        });
    }
};
