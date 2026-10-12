<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_otp', 10)->nullable()->after('email_verified_at');
            $table->timestamp('registration_otp_expires_at')->nullable()->after('registration_otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['registration_otp', 'registration_otp_expires_at']);
        });
    }
};
