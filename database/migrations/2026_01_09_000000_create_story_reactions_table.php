<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoryReactionsTable extends Migration
{
    public function up()
    {
        Schema::create('story_reactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('story_id');
            $table->unsignedBigInteger('user_id');
            $table->string('reaction_type', 50); // emoji like: 'like', 'love', 'haha', 'wow', 'sad', 'angry', or actual emoji
            $table->timestamps();

            $table->foreign('story_id')->references('id')->on('stories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // User can only have one reaction per story
            $table->unique(['story_id', 'user_id']);

            $table->index(['story_id', 'reaction_type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('story_reactions');
    }
}
