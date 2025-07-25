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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Add additional Stripe-related fields
            if (!Schema::hasColumn('user_subscriptions', 'stripe_session_id')) {
                $table->string('stripe_session_id')->nullable()->after('stripe_subscription_id');
            }
            if (!Schema::hasColumn('user_subscriptions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('stripe_session_id');
            }
            if (!Schema::hasColumn('user_subscriptions', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('user_subscriptions', 'current_period_start')) {
                $table->timestamp('current_period_start')->nullable()->after('gateway_response');
            }
            if (!Schema::hasColumn('user_subscriptions', 'current_period_end')) {
                $table->timestamp('current_period_end')->nullable()->after('current_period_start');
            }
            if (!Schema::hasColumn('user_subscriptions', 'last_payment_at')) {
                $table->timestamp('last_payment_at')->nullable()->after('current_period_end');
            }
            if (!Schema::hasColumn('user_subscriptions', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('last_payment_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Remove the additional Stripe fields
            $columns = [
                'stripe_session_id',
                'paid_at',
                'gateway_response',
                'current_period_start',
                'current_period_end',
                'last_payment_at',
                'starts_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('user_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
