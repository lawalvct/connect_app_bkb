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
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id')->nullable(); // Firebase message ID
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('fcm_token')->nullable();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // Additional data sent
            $table->string('status'); // sent, delivered, failed, clicked
            $table->text('response')->nullable(); // Firebase response
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->string('platform')->nullable(); // android, ios, web
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'sent_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};
