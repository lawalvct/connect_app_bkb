<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ad_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');

            // Payment Details
            $table->decimal('amount', 10, 2); // Original amount
            $table->string('currency', 3); // USD, NGN
            $table->decimal('amount_usd', 10, 2)->nullable(); // USD equivalent
            $table->decimal('exchange_rate', 10, 4)->nullable(); // Exchange rate used

            // Payment Gateway
            $table->enum('payment_gateway', ['nomba', 'stripe']);
            $table->string('payment_method')->nullable(); // card, bank_transfer, etc.

            // Payment Status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])
                  ->default('pending');

            // Gateway References
            $table->string('gateway_reference')->nullable(); // Nomba order reference or Stripe payment intent ID
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable(); // Store full gateway response

            // Payment Metadata
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Payment link expiry
            $table->string('payment_link')->nullable(); // Nomba checkout link or Stripe checkout URL
            $table->text('failure_reason')->nullable();
 $table->string('external_callback_url')->nullable();
            // Tracking
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['ad_id', 'status']);
            $table->index('gateway_reference');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_payments');
    }
};
