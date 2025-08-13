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
        Schema::create('admin_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string('fcm_token', 500); // Firebase token
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable(); // Browser/Device info
            $table->string('platform')->nullable(); // web, mobile
            $table->string('browser')->nullable(); // Chrome, Firefox, etc.
            $table->boolean('is_active')->default(true);
            $table->json('notification_preferences')->nullable(); // What notifications to receive
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['admin_id', 'fcm_token'], 'admin_fcm_unique');
            $table->index(['admin_id', 'is_active']);
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_fcm_tokens');
    }
};
