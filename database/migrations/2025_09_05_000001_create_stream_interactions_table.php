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
        Schema::create('stream_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stream_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('interaction_type', ['like', 'dislike', 'share']);
            $table->string('share_platform')->nullable(); // facebook, twitter, whatsapp, etc.
            $table->json('share_metadata')->nullable(); // Additional data for shares
            $table->timestamps();

            $table->foreign('stream_id')->references('id')->on('streams')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Prevent duplicate likes/dislikes per user per stream
            $table->unique(['stream_id', 'user_id', 'interaction_type'], 'unique_stream_user_interaction');

            $table->index(['stream_id', 'interaction_type']);
            $table->index(['user_id', 'interaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_interactions');
    }
};
