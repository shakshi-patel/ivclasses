<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('institute_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('user_type', 40)->default('institute_staff')->after('institute_id');
            // platform_admin | institute_admin | institute_staff | teacher | parent | student
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('password');
            $table->boolean('must_change_password')->default(false)->after('avatar_path');
            $table->boolean('is_active')->default(true)->after('must_change_password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institute_id');
            $table->dropColumn([
                'user_type',
                'phone',
                'avatar_path',
                'must_change_password',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'deleted_at',
            ]);
        });
    }
};
