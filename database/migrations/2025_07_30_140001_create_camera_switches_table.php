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
        Schema::create('camera_switches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_camera_id')->nullable()->constrained('stream_cameras')->onDelete('set null');
            $table->foreignId('to_camera_id')->constrained('stream_cameras')->onDelete('cascade');
            $table->unsignedBigInteger('switched_by'); // Admin who made the switch
            $table->timestamp('switched_at');
            $table->timestamps();

            $table->index(['stream_id', 'switched_at']);
            $table->foreign('switched_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_switches');
    }
};
