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
        // Add 'only_me' to the privacy enum
        DB::statement("ALTER TABLE stories MODIFY COLUMN privacy ENUM('all_connections', 'close_friends', 'only_me', 'custom') DEFAULT 'all_connections'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'only_me' from the privacy enum
        DB::statement("ALTER TABLE stories MODIFY COLUMN privacy ENUM('all_connections', 'close_friends', 'custom') DEFAULT 'all_connections'");
    }
};
