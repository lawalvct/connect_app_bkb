<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stream_id');
            $table->unsignedBigInteger('user_id');
            $table->string('username');
            $table->text('message');
            $table->string('user_profile_url')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->foreign('stream_id')->references('id')->on('streams')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['stream_id', 'created_at']);
            $table->index(['stream_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_chats');
    }
};
