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
        // First, set invalid foreign key values to null
        DB::statement('UPDATE ads SET reviewed_by = NULL WHERE reviewed_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM admins WHERE admins.id = ads.reviewed_by)');
        DB::statement('UPDATE ads SET created_by = NULL WHERE created_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM admins WHERE admins.id = ads.created_by)');
        DB::statement('UPDATE ads SET updated_by = NULL WHERE updated_by IS NOT NULL AND NOT EXISTS (SELECT 1 FROM admins WHERE admins.id = ads.updated_by)');

        // Drop existing foreign keys if they exist
        try {
            DB::statement('ALTER TABLE ads DROP FOREIGN KEY ads_reviewed_by_foreign');
        } catch (\Exception $e) {
            // Foreign key doesn't exist, continue
        }

        try {
            DB::statement('ALTER TABLE ads DROP FOREIGN KEY ads_created_by_foreign');
        } catch (\Exception $e) {
            // Foreign key doesn't exist, continue
        }

        try {
            DB::statement('ALTER TABLE ads DROP FOREIGN KEY ads_updated_by_foreign');
        } catch (\Exception $e) {
            // Foreign key doesn't exist, continue
        }

        // Add new foreign keys referencing admins table
        DB::statement('ALTER TABLE ads ADD CONSTRAINT ads_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE ads ADD CONSTRAINT ads_created_by_foreign FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE ads ADD CONSTRAINT ads_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL');
    }    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Drop admins foreign key constraints
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Restore users foreign key constraints
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};
