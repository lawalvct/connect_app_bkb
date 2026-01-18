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
        Schema::table('streams', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('streams', 'is_recorded')) {
                $table->boolean('is_recorded')->default(false)->after('go_live_immediately')
                    ->comment('Whether this is a pre-recorded video or live stream');
            }

            if (!Schema::hasColumn('streams', 'video_file')) {
                $table->string('video_file')->nullable()->after('is_recorded')
                    ->comment('Path to the uploaded video file');
            }

            if (!Schema::hasColumn('streams', 'video_url')) {
                $table->string('video_url')->nullable()->after('video_file')
                    ->comment('Full URL to the video file');
            }

            if (!Schema::hasColumn('streams', 'video_duration')) {
                $table->integer('video_duration')->nullable()->after('video_url')
                    ->comment('Video duration in seconds');
            }

            if (!Schema::hasColumn('streams', 'is_downloadable')) {
                $table->boolean('is_downloadable')->default(false)->after('video_duration')
                    ->comment('Whether users can download this video');
            }

            if (!Schema::hasColumn('streams', 'available_from')) {
                $table->timestamp('available_from')->nullable()->after('is_downloadable')
                    ->comment('When the recorded video becomes available');
            }

            if (!Schema::hasColumn('streams', 'available_until')) {
                $table->timestamp('available_until')->nullable()->after('available_from')
                    ->comment('When the recorded video becomes unavailable');
            }

            // Add index for querying available recorded videos
            $table->index(['is_recorded', 'available_from', 'available_until'], 'recorded_availability_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropIndex('recorded_availability_index');
            $table->dropColumn([
                'is_recorded',
                'video_file',
                'video_url',
                'video_duration',
                'is_downloadable',
                'available_from',
                'available_until'
            ]);
        });
    }
};
