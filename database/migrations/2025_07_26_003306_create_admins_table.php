<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('admin'); // super_admin, admin, moderator, content_manager
            $table->json('permissions')->nullable(); // Additional permissions
            $table->string('profile_image')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_otp_sent_at')->nullable();
            $table->boolean('force_password_change')->default(false);
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
