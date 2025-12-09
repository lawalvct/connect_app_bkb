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
        // Add new columns if they don't exist
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_guest')) {
                $table->boolean('is_guest')->default(false)->after('deleted_flag');
            }
            if (!Schema::hasColumn('users', 'guest_expires_at')) {
                $table->timestamp('guest_expires_at')->nullable()->after('is_guest');
            }
        });
        
        // Note: Phone column is already nullable in the database
        // Skipping modification to avoid datetime validation issues with existing data
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_guest', 'guest_expires_at']);
        });
    }
};
