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
            $table->integer('total_swipes')->default(0)->after('super_likes');
            $table->timestamp('archived_at')->nullable()->after('daily_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_swipes', function (Blueprint $table) {
            $table->dropColumn(['total_swipes', 'archived_at']);
        });
    }
};
