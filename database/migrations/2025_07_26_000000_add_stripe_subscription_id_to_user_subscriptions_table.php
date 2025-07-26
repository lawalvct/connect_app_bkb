<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_subscriptions', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('customer_id');
            }
        });
    }

    public function down()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('user_subscriptions', 'stripe_subscription_id')) {
                $table->dropColumn('stripe_subscription_id');
            }
        });
    }
};
