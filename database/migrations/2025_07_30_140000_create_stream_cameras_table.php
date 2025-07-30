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
        Schema::create('stream_cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->string('camera_name'); // e.g., "Main Camera", "Close-up", "Wide Shot"
            $table->string('stream_key')->unique(); // Unique key for each camera
            $table->string('device_type')->nullable(); // phone, laptop, camera, etc.
            $table->integer('agora_uid')->unique(); // Unique Agora UID for this camera
            $table->boolean('is_active')->default(false); // Camera currently connected
            $table->boolean('is_primary')->default(false); // Current active camera being broadcast
            $table->string('resolution')->default('720p'); // Camera resolution
            $table->json('connection_info')->nullable(); // Additional connection details
            $table->timestamp('last_seen_at')->nullable(); // Last time camera was active
            $table->timestamps();

            $table->index(['stream_id', 'is_active']);
            $table->index(['stream_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_cameras');
    }
};
