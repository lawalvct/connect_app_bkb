<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfileUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profile_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('file_name');
            $table->string('file_url');
            $table->string('file_type')->default('image'); // 'image' or 'video'
            $table->string('caption')->nullable(); // Image caption
            $table->string('alt_text')->nullable(); // Alt text for accessibility
            $table->json('tags')->nullable(); // Tags for the image
            $table->json('metadata')->nullable(); // Additional metadata (dimensions, etc.)
            $table->enum('deleted_flag', ['Y', 'N'])->default('N');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->index(['user_id', 'deleted_flag']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_profile_uploads');
    }
}
