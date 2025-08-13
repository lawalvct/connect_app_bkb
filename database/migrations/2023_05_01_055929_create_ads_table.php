<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ad_name');
            $table->enum('type', ['banner', 'video', 'carousel', 'story', 'feed']);
            $table->text('description')->nullable();
            $table->json('media_files')->nullable(); // Store multiple images/videos
            $table->string('call_to_action')->nullable();
            $table->string('destination_url')->nullable();
            $table->json('ad_placement')->nullable(); // Social circles where ad should appear
            $table->date('start_date');
            $table->date('end_date');
            $table->json('target_audience')->nullable(); // Age, gender, location, interests
            $table->json('target_countries')->nullable(); // Target specific countries
            $table->json('target_social_circles')->nullable(); // Target specific social circles
            $table->decimal('budget', 10, 2)->default(0);
            $table->decimal('daily_budget', 8, 2)->nullable();
            $table->integer('target_impressions')->default(0);
            $table->integer('current_impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('cost_per_click', 8, 4)->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->enum('status', ['draft', 'pending_review', 'approved', 'active', 'paused', 'stopped', 'completed', 'rejected'])->default('draft');
            $table->enum('admin_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_comments')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->enum('deleted_flag', ['Y', 'N'])->default('N');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['user_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('admin_status');
            // Note: Cannot index JSON columns directly in MySQL
            // $table->index('ad_placement'); // Removed - JSON columns don't support direct indexing
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
