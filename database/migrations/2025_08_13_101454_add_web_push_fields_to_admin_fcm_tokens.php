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
        Schema::table('admin_fcm_tokens', function (Blueprint $table) {
            $table->text('push_endpoint')->nullable()->after('fcm_token');
            $table->text('push_p256dh')->nullable()->after('push_endpoint');
            $table->text('push_auth')->nullable()->after('push_p256dh');
            $table->string('subscription_type')->default('fcm')->after('push_auth'); // 'fcm', 'web_push', or 'both'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_fcm_tokens', function (Blueprint $table) {
            $table->dropColumn(['push_endpoint', 'push_p256dh', 'push_auth', 'subscription_type']);
        });
    }
};
