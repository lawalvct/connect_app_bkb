<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('post_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by_admin')->nullable()->after('reviewed_by');
            $table->foreign('reviewed_by_admin')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_reports', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_admin']);
            $table->dropColumn('reviewed_by_admin');
        });
    }
};
