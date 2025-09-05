<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stream_interactions', function (Blueprint $table) {
            // Drop the old unique constraint
            $table->dropUnique('unique_stream_user_interaction');

            // Add a unique constraint only for likes and dislikes (MySQL doesn't support partial unique constraints like PostgreSQL)
            // We'll handle this at the application level instead
        });

        // Add a regular index for performance
        Schema::table('stream_interactions', function (Blueprint $table) {
            $table->index(['stream_id', 'user_id', 'interaction_type'], 'idx_stream_user_interaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stream_interactions', function (Blueprint $table) {
            // Drop the index
            $table->dropIndex('idx_stream_user_interaction');

            // Restore the original unique constraint
            $table->unique(['stream_id', 'user_id', 'interaction_type'], 'unique_stream_user_interaction');
        });
    }
};
