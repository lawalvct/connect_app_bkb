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
            if (!Schema::hasColumn('streams', 'likes_count')) {
                $table->integer('likes_count')->default(0)->after('current_viewers');
            }
            if (!Schema::hasColumn('streams', 'dislikes_count')) {
                $table->integer('dislikes_count')->default(0)->after('likes_count');
            }
            if (!Schema::hasColumn('streams', 'shares_count')) {
                $table->integer('shares_count')->default(0)->after('dislikes_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn(['likes_count', 'dislikes_count', 'shares_count']);
        });
    }
};
