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
        Schema::create('stream_mixer_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->enum('layout_type', ['single', 'picture_in_picture', 'split_screen', 'quad_view'])->default('single');
            $table->enum('transition_effect', ['fade', 'cut', 'slide', 'zoom'])->default('cut');
            $table->json('mixer_config')->nullable(); // Advanced mixer settings
            $table->integer('transition_duration')->default(1000); // Transition duration in milliseconds
            $table->timestamps();

            $table->unique('stream_id'); // One mixer setting per stream
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_mixer_settings');
    }
};
