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
        Schema::create('user_social_circle_swipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('social_circle_id');
            $table->unsignedBigInteger('target_user_id'); // User being swiped
            $table->enum('swipe_type', ['left_swipe', 'right_swipe', 'super_like'])->default('right_swipe');
            $table->timestamp('swiped_at');
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('social_circle_id')->references('id')->on('social_circles')->onDelete('cascade');
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for better performance with custom short names
            $table->index(['user_id', 'social_circle_id', 'swiped_at'], 'usc_swipes_user_circle_time');
            $table->index(['user_id', 'social_circle_id', 'target_user_id'], 'usc_swipes_user_circle_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social_circle_swipes');
    }
};
