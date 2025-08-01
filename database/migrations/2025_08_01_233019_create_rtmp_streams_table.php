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
        Schema::create('rtmp_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->string('rtmp_url');
            $table->string('stream_key')->unique();
            $table->enum('software_type', ['manycam', 'splitcam', 'obs', 'xsplit', 'other'])->default('manycam');
            $table->string('resolution')->default('1920x1080');
            $table->integer('bitrate')->default(3000);
            $table->integer('fps')->default(30);
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_heartbeat')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rtmp_streams');
    }
};
