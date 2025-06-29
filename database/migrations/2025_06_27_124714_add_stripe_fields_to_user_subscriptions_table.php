<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStripeFieldsToUserSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Add the stripe_subscription_id column if it doesn't exist
            if (!Schema::hasColumn('user_subscriptions', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('subscription_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('user_subscriptions', 'stripe_subscription_id')) {
                $table->dropColumn('stripe_subscription_id');
            }
        });
    }
}
