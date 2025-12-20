<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Advertisements table
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url'); // URL to ad video
            $table->string('thumbnail_url')->nullable();
            $table->integer('duration_seconds')->default(30);
            $table->integer('skip_after_seconds')->nullable(); // Allow skip after X seconds
            $table->string('click_url')->nullable(); // Click-through URL
            $table->boolean('is_active')->default(true);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('max_impressions')->nullable();
            $table->integer('current_impressions')->default(0);
            $table->decimal('cpm_rate', 10, 2)->nullable(); // Cost per 1000 impressions
            $table->json('targeting')->nullable(); // Targeting options
            $table->timestamps();
        });

        // Track which ads were shown in which streams
        Schema::create('advertisement_stream', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertisement_id')->constrained()->onDelete('cascade');
            $table->foreignId('stream_id')->constrained()->onDelete('cascade');
            $table->timestamp('shown_at');
            $table->integer('stream_position_seconds')->nullable(); // When in stream
            $table->integer('views')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('skips')->default(0);
            $table->timestamps();

            $table->index(['advertisement_id', 'stream_id']);
        });

        // Add ad tracking to streams table
        Schema::table('streams', function (Blueprint $table) {
            $table->timestamp('last_ad_shown_at')->nullable()->after('ended_at');
            $table->integer('total_ads_shown')->default(0)->after('last_ad_shown_at');
            $table->integer('current_position_seconds')->nullable()->after('total_ads_shown');
        });
    }

    public function down()
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn(['last_ad_shown_at', 'total_ads_shown', 'current_position_seconds']);
        });

        Schema::dropIfExists('advertisement_stream');
        Schema::dropIfExists('advertisements');
    }
};
