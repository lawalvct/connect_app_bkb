<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfileUploadLikesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profile_upload_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // User who liked
            $table->unsignedBigInteger('upload_id'); // Upload that was liked
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('upload_id')
                  ->references('id')
                  ->on('user_profile_uploads')
                  ->onDelete('cascade');

            // Ensure a user can only like an upload once
            $table->unique(['user_id', 'upload_id']);

            // Indexes for better performance
            $table->index('user_id');
            $table->index('upload_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_profile_upload_likes');
    }
}
