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
            if (!Schema::hasColumn('streams', 'free_minutes')) {
                $table->integer('free_minutes')->default(0)->after('current_viewers');
            }
            if (!Schema::hasColumn('streams', 'stream_type')) {
                $table->enum('stream_type', ['immediate', 'scheduled'])->default('immediate')->after('free_minutes');
            }
            if (!Schema::hasColumn('streams', 'go_live_immediately')) {
                $table->boolean('go_live_immediately')->default(true)->after('stream_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn(['free_minutes', 'stream_type', 'go_live_immediately']);
        });
    }
};
