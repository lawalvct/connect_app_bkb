<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_viewers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stream_id');
            $table->unsignedBigInteger('user_id');
            $table->string('agora_uid')->nullable();
            $table->string('agora_token', 1024)->nullable();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('stream_id')->references('id')->on('streams')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['stream_id', 'user_id']);
            $table->index(['stream_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_viewers');
    }
};
