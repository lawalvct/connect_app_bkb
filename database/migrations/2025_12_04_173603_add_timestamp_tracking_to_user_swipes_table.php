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
        Schema::table('user_swipes', function (Blueprint $table) {
            // Add timestamp column to track individual swipes for 12-hour rolling window
            $table->timestamp('swiped_at')->nullable()->after('archived_at');

            // Add index for efficient querying of swipes within time window
            $table->index(['user_id', 'swiped_at'], 'user_swipes_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_swipes', function (Blueprint $table) {
            $table->dropIndex('user_swipes_time_idx');
            $table->dropColumn('swiped_at');
        });
    }
};
