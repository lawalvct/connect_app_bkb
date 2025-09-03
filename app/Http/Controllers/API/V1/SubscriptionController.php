<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\BaseController;
use App\Models\Subscribe;
use App\Models\UserSubscription;
use App\Helpers\UserSubscriptionHelper;
use App\Helpers\NombaPyamentHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Subscription as StripeSubscription;

use Exception;

class SubscriptionController extends BaseController
{
    public $successStatus = 200;

    public function __construct()
    {
       // Try to get the key from config first, then fall back to env
    $stripeKey = config('services.stripe.secret') ?? env('STRIPE_SECRET');

    if (empty($stripeKey)) {
        \Log::error('Stripe secret key is missing or empty');

    }

    Stripe::setApiKey($stripeKey);
    }

    /**
     * Get all subscription plans
     */
    public function index()
    {
        try {
            $auth = auth()->user();
            $plans = Subscribe::active()->ordered()->get();

            // Add user's current subscription status to each plan
            foreach ($plans as $plan) {
                $userSubscription = UserSubscriptionHelper::getPremiumByUserId($auth->id, $plan->id);
                $plan->is_subscribed = $userSubscription ? true : false;
                $plan->subscription_status = $userSubscription ? $userSubscription->status : null;
                $plan->expires_at = $userSubscription ? $userSubscription->expires_at : null;
            }

            return response()->json([
                'status' => 1,
                'message' => 'Subscription plans retrieved successfully',
                'data' => $plans
            ], $this->successStatus);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error retrieving subscription plans: ' . $e->getMessage(),
                'data' => []
            ], $this->successStatus);
        }
    }

    /**
     * Get user's active subscriptions
     */
    public function userSubscriptions()
    {
        try {
            $auth = auth()->user();
            $subscriptions = UserSubscriptionHelper::getActiveSubscriptionsWithDetails($auth->id);

            return response()->json([
                'status' => 1,
                'message' => 'User subscriptions retrieved successfully',
                'data' => $subscriptions
            ], $this->successStatus);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error retrieving user subscriptions: ' . $e->getMessage(),
                'data' => []
            ], $this->successStatus);
        }
    }

    public function initializeStripePayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subscription_id' => 'required|exists:subscribes,id',
                'payment_method_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $user = $request->user();
            $subscriptionPlanId = $request->subscription_id;
            $paymentMethodId = $request->payment_method_id;

            \Log::info('Starting Stripe payment initialization', [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionPlanId,
                'payment_method_id' => $paymentMethodId
            ]);

            // Get subscription plan details
            $subscriptionPlan = Subscribe::findOrFail($subscriptionPlanId);

            // Check if the subscription plan has a valid stripe_price_id
            if (empty($subscriptionPlan->stripe_price_id)) {
                \Log::error('Subscription plan missing stripe_price_id', [
                    'subscription_plan_id' => $subscriptionPlanId,
                    'plan' => $subscriptionPlan->toArray()
                ]);
                return $this->sendError('Subscription plan is not configured for Stripe payments', [], 400);
            }

            \Log::info('Retrieved subscription plan', [
                'plan_id' => $subscriptionPlan->id,
                'plan_name' => $subscriptionPlan->name,
                'stripe_price_id' => $subscriptionPlan->stripe_price_id,
                'price' => $subscriptionPlan->price
            ]);

            // Check if user already has this subscription
            $existingSubscription = UserSubscription::where('user_id', $user->id)
                ->where('subscription_id', $subscriptionPlanId)
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                return $this->sendError('You already have an active subscription to this plan', [], 400);
            }

            // Create or get Stripe customer

            $stripeCustomerId = null;

            if ($user->stripe_customer_id) {
                try {
                    $customer = \Stripe\Customer::retrieve($user->stripe_customer_id);
                    $stripeCustomerId = $customer->id;
                    \Log::info('Retrieved existing Stripe customer', ['customer_id' => $stripeCustomerId]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to retrieve Stripe customer, creating new one', [
                        'error' => $e->getMessage()
                    ]);
                    // If customer retrieval fails, create a new one
                    $user->stripe_customer_id = null;
                }
            }

            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id
                    ]
                ]);



                $stripeCustomerId = $customer->id;
                $user->stripe_customer_id = $stripeCustomerId;
                $user->save();



                \Log::info('Created new Stripe customer', ['customer_id' => $stripeCustomerId]);
            }


            // Verify the payment method exists
            try {


                $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
                \Log::info('Retrieved payment method', ['payment_method_id' => $paymentMethodId]);
            } catch (\Exception $e) {

                \Log::error('Invalid payment method', [
                    'payment_method_id' => $paymentMethodId,
                    'error' => $e->getMessage()
                ]);
                return $this->sendError('Invalid payment method: ' . $e->getMessage(), [], 400);
            }

            // Attach payment method to customer
            try {
                // Check if payment method is already attached to this customer
                $customerPaymentMethods = \Stripe\PaymentMethod::all([
                    'customer' => $stripeCustomerId,
                    'type' => 'card'
                ]);

                $isAttached = false;
                foreach ($customerPaymentMethods->data as $method) {
                    if ($method->id === $paymentMethodId) {
                        $isAttached = true;
                        break;
                    }
                }

                if (!$isAttached) {
                    \Log::info('Attaching payment method to customer', [
                        'payment_method_id' => $paymentMethodId,
                        'customer_id' => $stripeCustomerId
                    ]);

                    $paymentMethod->attach(['customer' => $stripeCustomerId]);
                } else {
                    \Log::info('Payment method already attached to customer');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to attach payment method', [
                    'payment_method_id' => $paymentMethodId,
                    'customer_id' => $stripeCustomerId,
                    'error' => $e->getMessage()
                ]);

                // If it's already attached to another customer, we need to detach it first
                if (strpos($e->getMessage(), 'already attached') !== false) {
                    try {
                        \Log::info('Detaching payment method from previous customer');
                        $paymentMethod->detach();

                        // Now attach it to our customer
                        \Log::info('Attaching payment method to new customer');
                        $paymentMethod->attach(['customer' => $stripeCustomerId]);
                    } catch (\Exception $detachError) {
                        \Log::error('Failed to detach/reattach payment method', [
                            'error' => $detachError->getMessage()
                        ]);
                        return $this->sendError('Payment method error: ' . $detachError->getMessage(), [], 400);
                    }
                } else {
                    return $this->sendError('Payment method error: ' . $e->getMessage(), [], 400);
                }
            }

            // Set as default payment method





            try {
                \Log::info('Setting payment method as default', [
                    'payment_method_id' => $paymentMethodId,
                    'customer_id' => $stripeCustomerId
                ]);

                \Stripe\Customer::update($stripeCustomerId, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId
                    ]
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to set default payment method', [
                    'error' => $e->getMessage()
                ]);
                return $this->sendError('Failed to set default payment method: ' . $e->getMessage(), [], 400);
            }

            // Create subscription





            try {
                \Log::info('Creating Stripe subscription', [
                    'customer_id' => $stripeCustomerId,
                    'price_id' => $subscriptionPlan->stripe_price_id
                ]);

                $subscription = \Stripe\Subscription::create([
                    'customer' => $stripeCustomerId,
                    'items' => [
                        [
                            'price' => $subscriptionPlan->stripe_price_id,
                        ],
                    ],










                    'default_payment_method' => $paymentMethodId,
                    'expand' => ['latest_invoice.payment_intent'],
                    'metadata' => [
                        'user_id' => $user->id,
                        'subscription_id' => $subscriptionPlanId
                    ]
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create subscription', [
                    'error' => $e->getMessage()
                ]);
                return $this->sendError('Failed to create subscription: ' . $e->getMessage(), [], 400);
            }

            // Create local subscription record
            $userSubscription = new UserSubscription();
            $userSubscription->user_id = $user->id;
            $userSubscription->subscription_id = $subscriptionPlanId;
            $userSubscription->stripe_subscription_id = $subscription->id;
            $userSubscription->status = 'active';
            $userSubscription->starts_at = now();

            $userSubscription->expires_at = now()->addDays(30);
            $userSubscription->save();

            \Log::info('Subscription created successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id
            ]);

            return $this->sendResponse('Subscription created successfully', [
                'subscription' => $subscription,
                'client_secret' => $subscription->latest_invoice->payment_intent->client_secret ?? null,
            ]);
        } catch (\Exception $e) {

            \Log::error('Stripe payment initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Payment failed: ' . $e->getMessage(), []);
        }
    }

    /**
     * Initialize payment for subscription (Nomba)
     */
    // public function initializeNombaPayment(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'subscription_id' => 'required|exists:subscribes,id'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 0,
    //             'message' => $validator->errors()->first(),
    //             'data' => []
    //         ], $this->successStatus);
    //     }

    //     try {
    //         $auth = auth()->user();
    //         $subscription = Subscribe::findOrFail($request->subscription_id);

    //         // Check if user already has this subscription
    //         if (UserSubscriptionHelper::hasSubscription($auth->id, $subscription->id)) {
    //             return response()->json([
    //                 'status' => 0,
    //                 'message' => 'You already have an active subscription for this plan',
    //                 'data' => []
    //             ], $this->successStatus);
    //         }

    //         // Convert USD to NGN (approximate rate)
    //         $amountInNGN = $subscription->price * 1500; // 1 USD = 1500 NGN (update as needed)

    //         $nombaHelper = new NombaPyamentHelper();
    //         $callbackUrl = env('APP_URL') . '/api/v1/subscriptions/nomba/callback';

    //         $paymentResult = $nombaHelper->processPayment(
    //             $amountInNGN,
    //             $auth->email,
    //             $callbackUrl,
    //             'SUB_' . $subscription->id . '_' . $auth->id . '_' . time()
    //         );

    //         if ($paymentResult['status']) {
    //             // Store pending subscription
    //             $subscriptionData = [
    //                 'user_id' => $auth->id,
    //                 'subscription_id' => $subscription->id,
    //                 'amount' => $subscription->price,
    //                 'currency' => $subscription->currency,
    //                 'payment_method' => 'nomba',
    //                 'payment_status' => 'pending',
    //                 'transaction_reference' => $paymentResult['orderReference'],
    //                 'payment_details' => json_encode([
    //                     'checkoutLink' => $paymentResult['checkoutLink'],
    //                     'orderReference' => $paymentResult['orderReference'],
    //                     'amount' => $amountInNGN,
    //                     'currency' => 'NGN',
    //                     'callback_url' => $callbackUrl,
    //                     'created_at' => now()->toISOString()
    //                 ]),
    //                 'status' => 'pending'
    //             ];

    //             $userSubscriptionId = UserSubscriptionHelper::insert($subscriptionData);

    //             return response()->json([
    //                 'status' => 1,
    //                 'message' => 'Payment initialized successfully',
    //                 'data' => [
    //                     'checkout_url' => $paymentResult['checkoutLink'],
    //                     'order_reference' => $paymentResult['orderReference'],
    //                     'subscription_id' => $userSubscriptionId
    //                 ]
    //             ], $this->successStatus);
    //         } else {
    //             return response()->json([
    //                 'status' => 0,
    //                 'message' => 'Failed to initialize payment: ' . $paymentResult['message'],
    //                 'data' => []
    //             ], $this->successStatus);
    //         }
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status' => 0,
    //             'message' => 'Payment initialization failed: ' . $e->getMessage(),
    //             'data' => []
    //         ], $this->successStatus);
    //     }
    // }

    /**
     * Handle Nomba payment callback with signature verification
     */
    public function handleNombaCallback(Request $request)
    {
        try {
            \Log::info('Nomba callback received', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // Verify the webhook signature
            if (!$this->verifyNombaSignature($request)) {
                \Log::warning('Invalid Nomba webhook signature');
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
            }

            $orderReference = $request->input('orderReference') ?? $request->input('order_reference');

            if (!$orderReference) {
                \Log::warning('Order reference missing from Nomba callback');
                return response()->json(['status' => 'error', 'message' => 'Order reference missing'], 400);
            }

            // Find the subscription
            $userSubscription = UserSubscription::where('transaction_reference', $orderReference)->first();

            if (!$userSubscription) {
                \Log::warning('Subscription not found for order reference: ' . $orderReference);
                return response()->json(['status' => 'error', 'message' => 'Subscription not found'], 404);
            }

            // Check if already processed
            if ($userSubscription->payment_status === 'completed') {
                \Log::info('Payment already processed for order: ' . $orderReference);
                return response()->json(['status' => 'success', 'message' => 'Already processed'], 200);
            }

            // Verify payment with Nomba API
            $nombaHelper = new NombaPyamentHelper();
            $verificationResult = $nombaHelper->verifyPayment($orderReference);

            if ($verificationResult['status']) {
                // Update subscription status
                UserSubscriptionHelper::update([
                    'payment_status' => 'completed',
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => now()->addDays(30),
                    'payment_details' => json_encode(array_merge(
                        json_decode($userSubscription->payment_details, true) ?? [],
                        [
                            'webhook_received_at' => now()->toISOString(),
                            'verification_result' => $verificationResult,
                            'webhook_data' => $request->all()
                        ]
                    ))
                ], ['id' => $userSubscription->id]);

                // Refresh the subscription data
                $userSubscription->refresh();

                // Send notifications
                $this->sendPaymentSuccessNotification($userSubscription->user_id, $userSubscription);

                \Log::info('Payment successfully processed for order: ' . $orderReference);

                return response()->json(['status' => 'success'], 200);
            } else {
                \Log::warning('Payment verification failed for order: ' . $orderReference);

                // Update as failed
                UserSubscriptionHelper::update([
                    'payment_status' => 'failed',
                    'status' => 'failed'
                ], ['id' => $userSubscription->id]);

                return response()->json(['status' => 'failed'], 400);
            }

        } catch (\Exception $e) {
            \Log::error('Nomba callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * Verify Nomba webhook signature
     */
    private function verifyNombaSignature(Request $request)
    {
        try {
            // Get the signature from headers
            $signature = $request->header('X-Nomba-Signature') ??
                        $request->header('x-nomba-signature') ??
                        $request->header('Nomba-Signature');

            if (!$signature) {
                \Log::warning('No signature found in Nomba webhook headers');
                return false;
            }

            // Get your webhook secret from settings
            $webhookSecret = Setting::getValue('nomba_webhook_secret');

            if (!$webhookSecret) {
                \Log::error('Nomba webhook secret not configured');
                return false;
            }

            // Get the raw body
            $payload = $request->getContent();

            // Calculate the expected signature
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

            // Compare signatures
            $isValid = hash_equals($expectedSignature, $signature);

            \Log::info('Signature verification', [
                'received_signature' => $signature,
                'expected_signature' => $expectedSignature,
                'is_valid' => $isValid
            ]);

            return $isValid;

        } catch (\Exception $e) {
            \Log::error('Error verifying Nomba signature: ' . $e->getMessage());
            return false;
        }
    }

   /**
 * Verify payment status
 */
public function verifyPayment(Request $request)
{
    $validator = Validator::make($request->all(), [
        'transaction_reference' => 'required|string',
        'payment_method' => 'required|in:stripe,nomba'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 0,
            'message' => $validator->errors()->first(),
            'data' => []
        ], $this->successStatus);
    }

    try {
        $userSubscription = UserSubscription::where('transaction_reference', $request->transaction_reference)
            ->where('user_id', auth()->id())
            ->first();

        if (!$userSubscription) {
            return response()->json([
                'status' => 0,
                'message' => 'Transaction not found',
                'data' => []
            ], $this->successStatus);
        }

        if ($request->payment_method === 'nomba') {
            $nombaHelper = new NombaPyamentHelper();
            $verificationResult = $nombaHelper->verifyPayment($request->transaction_reference);

            if ($verificationResult['status'] && $userSubscription->payment_status === 'pending') {
                UserSubscriptionHelper::update([
                    'payment_status' => 'completed',
                    'status' => 'active',
                    'payment_details' => json_encode($verificationResult)
                ], ['id' => $userSubscription->id]);

                // Refresh the subscription data
                $userSubscription->refresh();
            }
        }

        // Prepare response data
        $responseData = [
            'payment_status' => $userSubscription->payment_status,
            'subscription_status' => $userSubscription->status,
            'expires_at' => $userSubscription->expires_at
        ];

        // If payment is still pending and it's a Nomba payment, include checkout link
        if ($userSubscription->payment_status === 'pending' && $request->payment_method === 'nomba') {
            $paymentDetails = json_decode($userSubscription->payment_details, true);

            if ($paymentDetails && isset($paymentDetails['checkoutLink'])) {
                $responseData['checkout_url'] = $paymentDetails['checkoutLink'];
                $responseData['order_reference'] = $paymentDetails['orderReference'] ?? $request->transaction_reference;
            }
        }

        return response()->json([
            'status' => 1,
            'message' => 'Payment status retrieved successfully',
            'data' => $responseData
        ], $this->successStatus);

    } catch (Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => 'Verification failed: ' . $e->getMessage(),
            'data' => []
        ], $this->successStatus);
    }
}

    /**
     * Cancel subscription
     */
    public function cancel(Request $request, $subscriptionId)
    {
        try {
            $auth = auth()->user();

            $userSubscription = UserSubscription::where('user_id', $auth->id)
                ->where('subscription_id', $subscriptionId)
                ->where('status', 'active')
                ->first();

            if (!$userSubscription) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Active subscription not found',
                    'data' => []
                ], $this->successStatus);
            }

            // Cancel on payment gateway if needed
            if ($userSubscription->payment_method === 'stripe' && $userSubscription->customer_id) {
                try {
                    // Cancel Stripe subscription if it exists
                    $stripeSubscriptions = StripeSubscription::all([
                        'customer' => $userSubscription->customer_id,
                        'status' => 'active'
                    ]);

                    foreach ($stripeSubscriptions->data as $stripeSub) {
                        $stripeSub->cancel();
                    }
                } catch (Exception $e) {
                    // Log error but continue with local cancellation
                    \Log::error('Stripe cancellation error: ' . $e->getMessage());
                }
            }

            // Update local subscription
            UserSubscriptionHelper::update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ], ['id' => $userSubscription->id]);

            return response()->json([
                'status' => 1,
                'message' => 'Subscription cancelled successfully',
                'data' => []
            ], $this->successStatus);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Cancellation failed: ' . $e->getMessage(),
                'data' => []
            ], $this->successStatus);
        }
    }

    /**
     * Restore purchases (for iOS)
     */
    public function restore()
    {
        try {
            $auth = auth()->user();
            UserSubscriptionHelper::getPremiumByUserIdRestore($auth->id);

            return response()->json([
                'status' => 1,
                'message' => 'Purchases restored successfully',
                'data' => []
            ], $this->successStatus);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Restore failed: ' . $e->getMessage(),
                'data' => []
            ], $this->successStatus);
        }
    }

    /**
     * Activate boost (for premium users)
     */
    public function activateBoost()
    {
        try {
            $auth = auth()->user();

            // Check if user has premium subscription
            $premiumSubscription = UserSubscriptionHelper::getPremiumByUserId($auth->id, 3);
            if (!$premiumSubscription) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Premium subscription required to activate boost',
                    'data' => []
                ], $this->successStatus);
            }

            // Check current boost usage
            $currentBoostCount = UserSubscriptionHelper::getCheckBoostPremium($premiumSubscription->id);
            if ($currentBoostCount >= 2) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Maximum boost usage reached (2 per month)',
                    'data' => []
                ], $this->successStatus);
            }

            // Check if boost is already active
            $activeBoost = UserSubscriptionHelper::getPremiumByUserId($auth->id, 4);
            if ($activeBoost) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Boost is already active',
                    'data' => []
                ], $this->successStatus);
            }

            // Create boost subscription
            $boostData = [
                'user_id' => $auth->id,
                'subscription_id' => 4, // Boost subscription ID
                'amount' => 0, // Free for premium users
                'currency' => 'USD',
                'payment_method' => 'premium_benefit',
                'payment_status' => 'completed',
                'transaction_reference' => 'PREMIUM_BOOST_' . time(),
                'status' => 'active',
                'parent_id' => $premiumSubscription->id
            ];

            $boostSubscriptionId = UserSubscriptionHelper::insert($boostData);

            return response()->json([
                'status' => 1,
                'message' => 'Boost activated successfully',
                'data' => [
                    'boost_subscription_id' => $boostSubscriptionId,
                    'remaining_boosts' => 2 - ($currentBoostCount + 1)
                ]
            ], $this->successStatus);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Boost activation failed: ' . $e->getMessage(),
                'data' => []
            ], $this->successStatus);
        }
    }

    /**
     * Get subscription features and limits
     */
    public function getFeatures()
    {
        try {
            $auth = auth()->user();
            $activeSubscriptions = UserSubscriptionHelper::getActiveSubscriptionsWithDetails($auth->id);

            $features = [
                'unlimited_swipes' => false,
                'travel_connections' => false,
                'boost_available' => false,
                'daily_swipe_limit' => 50,
                'boost_count_used' => 0,
                'boost_count_limit' => 0
            ];

            foreach ($activeSubscriptions as $subscription) {
                // Check if the subscription object has the necessary properties
                if (!isset($subscription->slug)) {
                    \Log::warning('Subscription missing slug property', ['subscription' => $subscription]);
                    continue;
                }

                // Get the subscription plan details
                $plan = Subscribe::find($subscription->subscription_id);

                if (!$plan) {
                    \Log::warning('Subscription plan not found', ['subscription_id' => $subscription->subscription_id]);
                    continue;
                }

                if ($plan->slug === 'connect-unlimited' || $plan->slug === 'connect-premium') {
                    $features['unlimited_swipes'] = true;
                    $features['daily_swipe_limit'] = 999999;
                }

                if ($plan->slug === 'connect-travel' || $plan->slug === 'connect-premium') {
                    $features['travel_connections'] = true;
                }

                if ($plan->slug === 'connect-boost' || $plan->slug === 'connect-premium') {
                    $features['boost_available'] = true;
                }

                if ($plan->slug === 'connect-premium') {
                    $features['boost_count_limit'] = 2;
                    $features['boost_count_used'] = UserSubscriptionHelper::getCheckBoostPremium($subscription->id);
                }
            }

            return response()->json([
                'status' => 1,
                'message' => 'Features retrieved successfully',
                'data' => $features
            ], $this->successStatus);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve features: ' . $e->getMessage(),
                'data' => []
            ], $this->successStatus);
        }
    }

/**
 * Initialize payment for subscription (Nomba NGN)
 */
public function initializeNombaPaymentNGN(Request $request)
{
    $validator = Validator::make($request->all(), [
        'subscription_id' => 'required|exists:subscribes,id',
        'amount_ngn' => 'required|numeric|min:1'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 0,
            'message' => $validator->errors()->first(),
            'data' => []
        ], $this->successStatus);
    }

    try {
        $auth = auth()->user();
        $subscription = Subscribe::findOrFail($request->subscription_id);
        $amountNGN = $request->amount_ngn;

        // Check if user already has this subscription
        if (UserSubscriptionHelper::hasSubscription($auth->id, $subscription->id)) {
            return response()->json([
                'status' => 0,
                'message' => 'You already have an active subscription for this plan',
                'data' => []
            ], $this->successStatus);
        }

        $nombaHelper = new NombaPyamentHelper();
        $callbackUrl = env('APP_URL') . '/api/v1/subscriptions/nomba/callback';

        $paymentResult = $nombaHelper->processPayment(
            $amountNGN,
            'NGN',
            $auth->email,
            $callbackUrl,
            'SUB_NGN_' . $subscription->id . '_' . $auth->id . '_' . time()
        );

        if ($paymentResult['status']) {
            // Store pending subscription
            $subscriptionData = [
                'user_id' => $auth->id,
                'subscription_id' => $subscription->id,
                'amount' => $amountNGN,
                'currency' => 'NGN',
                'payment_method' => 'nomba',
                'payment_status' => 'pending',
                'transaction_reference' => $paymentResult['orderReference'],
                'payment_details' => json_encode([
                    'checkoutLink' => $paymentResult['checkoutLink'],
                    'orderReference' => $paymentResult['orderReference'],
                    'amount' => $amountNGN,
                    'currency' => 'NGN',
                    'callback_url' => $callbackUrl,
                    'created_at' => now()->toISOString()
                ]),
                'status' => 'pending'
            ];

            $userSubscriptionId = UserSubscriptionHelper::insert($subscriptionData);

            return response()->json([
                'status' => 1,
                'message' => 'NGN payment initialized successfully',
                'data' => [
                    'checkout_url' => $paymentResult['checkoutLink'],
                    'order_reference' => $paymentResult['orderReference'],
                    'subscription_id' => $userSubscriptionId,
                    'amount' => $amountNGN,
                    'currency' => 'NGN'
                ]
            ], $this->successStatus);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to initialize NGN payment: ' . $paymentResult['message'],
                'data' => []
            ], $this->successStatus);
        }
    } catch (Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => 'NGN payment initialization failed: ' . $e->getMessage(),
            'data' => []
        ], $this->successStatus);
    }
}

/**
 * Initialize payment for subscription (Nomba USD)
 */
public function initializeNombaPaymentUSD(Request $request)
{
    $validator = Validator::make($request->all(), [
        'subscription_id' => 'required|exists:subscribes,id',
        'amount_usd' => 'required|numeric|min:1'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 0,
            'message' => $validator->errors()->first(),
            'data' => []
        ], $this->successStatus);
    }

    try {
        $auth = auth()->user();
        $subscription = Subscribe::findOrFail($request->subscription_id);
        $amountUSD = $request->amount_usd;

        // Check if user already has this subscription
        if (UserSubscriptionHelper::hasSubscription($auth->id, $subscription->id)) {
            return response()->json([
                'status' => 0,
                'message' => 'You already have an active subscription for this plan',
                'data' => []
            ], $this->successStatus);
        }

        $nombaHelper = new NombaPyamentHelper();
        $callbackUrl = env('APP_URL') . '/api/v1/subscriptions/nomba/callback';

        $paymentResult = $nombaHelper->processPayment(
            $amountUSD,
            'USD',
            $auth->email,
            $callbackUrl,
            'SUB_USD_' . $subscription->id . '_' . $auth->id . '_' . time()
        );

        if ($paymentResult['status']) {
            // Store pending subscription
            $subscriptionData = [
                'user_id' => $auth->id,
                'subscription_id' => $subscription->id,
                'amount' => $amountUSD,
                'currency' => 'USD',
                'payment_method' => 'nomba',
                'payment_status' => 'pending',
                'transaction_reference' => $paymentResult['orderReference'],
                'payment_details' => json_encode([
                    'checkoutLink' => $paymentResult['checkoutLink'],
                    'orderReference' => $paymentResult['orderReference'],
                    'amount' => $amountUSD,
                    'currency' => 'USD',
                    'callback_url' => $callbackUrl,
                    'created_at' => now()->toISOString()
                ]),
                'status' => 'pending'
            ];

            $userSubscriptionId = UserSubscriptionHelper::insert($subscriptionData);

            return response()->json([
                'status' => 1,
                'message' => 'USD payment initialized successfully',
                'data' => [
                    'checkout_url' => $paymentResult['checkoutLink'],
                    'order_reference' => $paymentResult['orderReference'],
                    'subscription_id' => $userSubscriptionId,
                    'amount' => $amountUSD,
                    'currency' => 'USD'
                ]
            ], $this->successStatus);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to initialize USD payment: ' . $paymentResult['message'],
                'data' => []
            ], $this->successStatus);
        }
    } catch (Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => 'USD payment initialization failed: ' . $e->getMessage(),
            'data' => []
        ], $this->successStatus);
    }
}

/**
 * Initialize payment for subscription (Nomba) - Original method updated
 */
public function initializeNombaPayment(Request $request)
{
    $validator = Validator::make($request->all(), [
        'subscription_id' => 'required|exists:subscribes,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 0,
            'message' => $validator->errors()->first(),
            'data' => []
        ], $this->successStatus);
    }

    try {
        $auth = auth()->user();
        $subscription = Subscribe::findOrFail($request->subscription_id);

        // Check if user already has this subscription
        if (UserSubscriptionHelper::hasSubscription($auth->id, $subscription->id)) {
            return response()->json([
                'status' => 0,
                'message' => 'You already have an active subscription for this plan',
                'data' => []
            ], $this->successStatus);
        }

        $nombaHelper = new NombaPyamentHelper();

        // Convert USD to NGN (for backward compatibility)
        $amountInNGN = $nombaHelper->convertUsdToNgn($subscription->price);
        $callbackUrl = env('APP_URL') . '/api/v1/subscriptions/nomba/callback';

        $paymentResult = $nombaHelper->processPayment(
            $amountInNGN,
            'NGN',
            $auth->email,
            $callbackUrl,
            'SUB_' . $subscription->id . '_' . $auth->id . '_' . time()
        );

        if ($paymentResult['status']) {
            // Store pending subscription
            $subscriptionData = [
                'user_id' => $auth->id,
                'subscription_id' => $subscription->id,
                'amount' => $subscription->price,
                'currency' => $subscription->currency,
                'payment_method' => 'nomba',
                'payment_status' => 'pending',
                'transaction_reference' => $paymentResult['orderReference'],
                'payment_details' => json_encode([
                    'checkoutLink' => $paymentResult['checkoutLink'],
                    'orderReference' => $paymentResult['orderReference'],
                    'amount' => $amountInNGN,
                    'original_amount' => $subscription->price,
                    'currency' => 'NGN',
                    'original_currency' => $subscription->currency,
                    'callback_url' => $callbackUrl,
                    'created_at' => now()->toISOString()
                ]),
                'status' => 'pending'
            ];

            $userSubscriptionId = UserSubscriptionHelper::insert($subscriptionData);

            return response()->json([
                'status' => 1,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'checkout_url' => $paymentResult['checkoutLink'],
                    'order_reference' => $paymentResult['orderReference'],
                    'subscription_id' => $userSubscriptionId,
                    'amount_ngn' => $amountInNGN,
                    'original_amount_usd' => $subscription->price
                ]
            ], $this->successStatus);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to initialize payment: ' . $paymentResult['message'],
                'data' => []
            ], $this->successStatus);
        }
    } catch (Exception $e) {
        return response()->json([
            'status' => 0,
            'message' => 'Payment initialization failed: ' . $e->getMessage(),
            'data' => []
        ], $this->successStatus);
    }
}



public function handleNombaCallbackWeb(Request $request)
{
    $orderReference = $request->query('orderid');

    if (!$orderReference) {
        return response()->json([
            'message' => 'Reference parameter is required',
            'status' => 0
        ], 400);
    }

    // Initialize Nomba payment helper
    $nombaHelper = new NombaPyamentHelper();

    // Verify the payment
    $verification = $nombaHelper->verifyPayment($orderReference);

    // Get payment details from database
      $userSubscription = UserSubscription::where('transaction_reference', $orderReference)->first();

     if (!$userSubscription) {
         \Log::warning('Subscription not found for order reference: ' . $orderReference);
         return response()->json(['status' => 'error', 'message' => 'Subscription not found'], 404);
     }

     // Check if already processed
     if ($userSubscription->payment_status === 'completed') {
         \Log::info('Payment already processed for order: ' . $orderReference);
         return response()->json(['status' => 'success', 'message' => 'Already processed'], 200);
     }

     $verificationResult = $nombaHelper->verifyPayment($orderReference);

     if ($verificationResult['status']) {
         // Update subscription status
         UserSubscriptionHelper::update([
             'payment_status' => 'completed',
             'status' => 'active',
             'started_at' => now(),
             'expires_at' => now()->addDays(30),
             'payment_details' => json_encode($verificationResult['data'])
         ], ['id' => $userSubscription->id]);

         // Refresh the subscription data
         $userSubscription->refresh();

         // Send notifications
      //   $this->sendPaymentSuccessNotification($userSubscription->user_id, $userSubscription);

         \Log::info('Payment successfully processed for order: ' . $orderReference);

          // Redirect to success page
        return redirect()->away(env('FRONTEND_URL') . '/subscription/success?reference=' . $orderReference.'&orderId='.$orderReference);
     } else {
         \Log::warning('Payment verification failed for order: ' . $orderReference);

         // Update as failed
         UserSubscriptionHelper::update([
             'payment_status' => 'failed',
             'status' => 'failed'
         ], ['id' => $userSubscription->id]);


             // Redirect to failure page
    return redirect()->away(env('FRONTEND_URL') . '/subscription/failure?reference=' . $orderReference);
     }




    }



    public function initializeStripeWithPaymentLink(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subscription_id' => 'required|exists:subscribes,id',
                'success_url' => 'nullable|url',
                'cancel_url' => 'nullable|url'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $user = $request->user();
            $subscriptionPlanId = $request->subscription_id;

            \Log::info('Starting Stripe checkout session initialization', [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionPlanId
            ]);

            // Get subscription plan details
            $subscriptionPlan = Subscribe::findOrFail($subscriptionPlanId);

            \Log::info('Retrieved subscription plan', [
                'plan_id' => $subscriptionPlan->id,
                'plan_name' => $subscriptionPlan->name,
                'price' => $subscriptionPlan->price
            ]);

            // Check if user already has this subscription
            $existingSubscription = UserSubscription::where('user_id', $user->id)
                ->where('subscription_id', $subscriptionPlanId)
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                return $this->sendError('You already have an active subscription to this plan', [], 400);
            }

            // Create or get Stripe customer
            $stripeCustomerId = null;

            if ($user->stripe_customer_id) {
                try {
                    $customer = \Stripe\Customer::retrieve($user->stripe_customer_id);
                    $stripeCustomerId = $customer->id;
                    \Log::info('Retrieved existing Stripe customer', ['customer_id' => $stripeCustomerId]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to retrieve Stripe customer, creating new one', [
                        'error' => $e->getMessage()
                    ]);
                    $user->stripe_customer_id = null;
                }
            }

            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id
                    ]
                ]);

                $stripeCustomerId = $customer->id;
                $user->stripe_customer_id = $stripeCustomerId;
                $user->save();

                \Log::info('Created new Stripe customer', ['customer_id' => $stripeCustomerId]);
            }

            // Set URLs
            $successUrl = $request->success_url ?? config('app.url') . '/subscription-success?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = $request->cancel_url ?? config('app.url') . '/subscription-cancel';

            // Create pending subscription record for tracking
            $pendingSubscription = new UserSubscription();
            $pendingSubscription->user_id = $user->id;
            $pendingSubscription->subscription_id = $subscriptionPlanId;
            $pendingSubscription->amount = $subscriptionPlan->price; // Add the required amount field
            $pendingSubscription->currency = 'USD'; // Add currency
            $pendingSubscription->payment_method = 'stripe'; // Add payment method
            $pendingSubscription->status = 'pending';
            $pendingSubscription->starts_at = now();
            $pendingSubscription->expires_at = now()->addDays(30);
            $pendingSubscription->save();

            // Create Stripe checkout session
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'customer' => $stripeCustomerId,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd', // You can make this dynamic
                        'product_data' => [
                            'name' => $subscriptionPlan->name,
                            'description' => $subscriptionPlan->description ?? 'Subscription Plan',
                        ],
                        'unit_amount' => $subscriptionPlan->price * 100, // Stripe expects amount in cents
                        'recurring' => [
                            'interval' => $subscriptionPlan->billing_period ?? 'month',
                            'interval_count' => 1,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => $pendingSubscription->id,
                'metadata' => [
                    'user_id' => $user->id,
                    'subscription_id' => $subscriptionPlanId,
                    'subscription_plan_name' => $subscriptionPlan->name,
                    'type' => 'subscription_payment'
                ]
            ]);

            // Update pending subscription with session ID
            $pendingSubscription->stripe_session_id = $session->id;
            $pendingSubscription->save();

            \Log::info('Stripe checkout session created successfully', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'subscription_id' => $subscriptionPlanId
            ]);

            return $this->sendResponse('Checkout session created successfully', [
                'checkout_url' => $session->url,
                'session_id' => $session->id,
                'subscription_plan' => [
                    'id' => $subscriptionPlan->id,
                    'name' => $subscriptionPlan->name,
                    'price' => $subscriptionPlan->price,
                    'billing_period' => $subscriptionPlan->billing_period ?? 'month'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Stripe checkout session initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->sendError('Payment initialization failed: ' . $e->getMessage(), []);
        }
    }

    /**
     * Handle Stripe webhook for subscription payments
     */
    public function stripeWebhook(Request $request)
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $payload = $request->getContent();
            $sig_header = $request->header('Stripe-Signature');
            $endpoint_secret = config('services.stripe.webhook_secret');

            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload, $sig_header, $endpoint_secret
                );
            } catch (\UnexpectedValueException $e) {
                \Log::error('Invalid payload', ['error' => $e->getMessage()]);
                return response('Invalid payload', 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                \Log::error('Invalid signature', ['error' => $e->getMessage()]);
                return response('Invalid signature', 400);
            }

            // Handle the event
            switch ($event['type']) {
                case 'checkout.session.completed':
                    $session = $event['data']['object'];
                    $this->handleStripeSubscriptionCheckoutCompleted($session);
                    break;

                case 'customer.subscription.created':
                    $subscription = $event['data']['object'];
                    $this->handleStripeSubscriptionCreated($subscription);
                    break;

                case 'customer.subscription.updated':
                    $subscription = $event['data']['object'];
                    $this->handleStripeSubscriptionUpdated($subscription);
                    break;

                case 'customer.subscription.deleted':
                    $subscription = $event['data']['object'];
                    $this->handleStripeSubscriptionDeleted($subscription);
                    break;

                case 'invoice.payment_succeeded':
                    $invoice = $event['data']['object'];
                    $this->handleStripeInvoicePaymentSucceeded($invoice);
                    break;

                case 'invoice.payment_failed':
                    $invoice = $event['data']['object'];
                    $this->handleStripeInvoicePaymentFailed($invoice);
                    break;

                default:
                    \Log::info('Unhandled Stripe event type: ' . $event['type']);
            }

            return response('Success', 200);

        } catch (\Exception $e) {
            \Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('Error', 500);
        }
    }

    /**
     * Handle Stripe checkout session completed for subscriptions
     */
    private function handleStripeSubscriptionCheckoutCompleted($session)
    {
        $subscriptionId = $session['client_reference_id'];

        $userSubscription = UserSubscription::where('id', $subscriptionId)
            ->where('status', 'pending')
            ->first();

        if (!$userSubscription) {
            \Log::warning('Stripe webhook: Subscription not found', ['subscription_id' => $subscriptionId]);
            return;
        }

        $userSubscription->update([
            'status' => 'active',
            'stripe_subscription_id' => $session['subscription'],
            'stripe_session_id' => $session['id'],
            'paid_at' => now(),
            'gateway_response' => $session
        ]);

        \Log::info('Stripe subscription checkout completed', [
            'subscription_id' => $userSubscription->id,
            'user_id' => $userSubscription->user_id
        ]);
    }

    /**
     * Handle Stripe subscription created
     */
    private function handleStripeSubscriptionCreated($subscription)
    {
        $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription['id'])->first();

        if ($userSubscription) {
            $userSubscription->update([
                'status' => 'active',
                'current_period_start' => $subscription['current_period_start'] ?
                    \Carbon\Carbon::createFromTimestamp($subscription['current_period_start']) : now(),
                'current_period_end' => $subscription['current_period_end'] ?
                    \Carbon\Carbon::createFromTimestamp($subscription['current_period_end']) : now()->addMonth(),
            ]);

            \Log::info('Stripe subscription created webhook processed', [
                'subscription_id' => $userSubscription->id
            ]);
        }
    }

    /**
     * Handle Stripe subscription updated
     */
    private function handleStripeSubscriptionUpdated($subscription)
    {
        $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription['id'])->first();

        if ($userSubscription) {
            $status = $subscription['status'];

            // Map Stripe status to our local status
            $localStatus = match($status) {
                'active' => 'active',
                'canceled' => 'cancelled',
                'past_due' => 'past_due',
                'unpaid' => 'unpaid',
                'trialing' => 'trialing',
                default => $status
            };

            $userSubscription->update([
                'status' => $localStatus,
                'current_period_start' => $subscription['current_period_start'] ?
                    \Carbon\Carbon::createFromTimestamp($subscription['current_period_start']) : now(),
                'current_period_end' => $subscription['current_period_end'] ?
                    \Carbon\Carbon::createFromTimestamp($subscription['current_period_end']) : now()->addMonth(),
            ]);

            \Log::info('Stripe subscription updated webhook processed', [
                'subscription_id' => $userSubscription->id,
                'new_status' => $localStatus
            ]);
        }
    }

    /**
     * Handle Stripe subscription deleted
     */
    private function handleStripeSubscriptionDeleted($subscription)
    {
        $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription['id'])->first();

        if ($userSubscription) {
            $userSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            \Log::info('Stripe subscription deleted webhook processed', [
                'subscription_id' => $userSubscription->id
            ]);
        }
    }

    /**
     * Handle Stripe invoice payment succeeded
     */
    private function handleStripeInvoicePaymentSucceeded($invoice)
    {
        $subscriptionId = $invoice['subscription'];

        if ($subscriptionId) {
            $userSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();

            if ($userSubscription) {
                $userSubscription->update([
                    'last_payment_at' => now(),
                    'status' => 'active'
                ]);

                \Log::info('Stripe invoice payment succeeded', [
                    'subscription_id' => $userSubscription->id,
                    'invoice_id' => $invoice['id']
                ]);
            }
        }
    }

    /**
     * Handle Stripe invoice payment failed
     */
    private function handleStripeInvoicePaymentFailed($invoice)
    {
        $subscriptionId = $invoice['subscription'];

        if ($subscriptionId) {
            $userSubscription = UserSubscription::where('stripe_subscription_id', $subscriptionId)->first();

            if ($userSubscription) {
                $userSubscription->update([
                    'status' => 'past_due'
                ]);

                \Log::info('Stripe invoice payment failed', [
                    'subscription_id' => $userSubscription->id,
                    'invoice_id' => $invoice['id']
                ]);
            }
        }
    }

    /**
     * Handle Stripe subscription success callback from web
     */
    public function handleStripeSuccess(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');

            if (!$sessionId) {
                return view('subscription-result', [
                    'status' => 'error',
                    'title' => 'Payment Error',
                    'message' => 'Invalid session ID provided',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

            \Log::info('Processing Stripe subscription success', [
                'session_id' => $sessionId
            ]);

            // Find the pending subscription by session ID
            $pendingSubscription = UserSubscription::where('stripe_session_id', $sessionId)
                ->where('status', 'pending')
                ->first();

            if (!$pendingSubscription) {
                return view('subscription-result', [
                    'status' => 'error',
                    'title' => 'Subscription Not Found',
                    'message' => 'No pending subscription found for this session',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

            // Retrieve the session from Stripe to get subscription details
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return view('subscription-result', [
                    'status' => 'error',
                    'title' => 'Payment Not Completed',
                    'message' => 'Payment has not been completed yet',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

            // Get the subscription from Stripe
            $stripeSubscription = null;
            if ($session->subscription) {
                $stripeSubscription = \Stripe\Subscription::retrieve($session->subscription);
            }

            // Update the user subscription record
            $pendingSubscription->update([
                'status' => 'active',
                'payment_status' => 'completed',
                'transaction_reference' => $session->payment_intent ?? $session->id,
                'customer_id' => $session->customer,
                'stripe_subscription_id' => $session->subscription,
                'started_at' => now(),
                'current_period_start' => ($stripeSubscription && $stripeSubscription->current_period_start) ?
                    \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start) : now(),
                'current_period_end' => ($stripeSubscription && $stripeSubscription->current_period_end) ?
                    \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end) : now()->addMonth(),
                'expires_at' => ($stripeSubscription && $stripeSubscription->current_period_end) ?
                    \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end) : now()->addMonth(),
                'paid_at' => now(),
                'last_payment_at' => now(),
                'payment_details' => [
                    'stripe_session_id' => $sessionId,
                    'stripe_customer_id' => $session->customer,
                    'stripe_subscription_id' => $session->subscription,
                    'amount_paid' => $session->amount_total / 100, // Convert from cents
                    'currency' => strtoupper($session->currency),
                    'payment_method' => 'stripe',
                    'processed_at' => now()->toISOString()
                ]
            ]);

            // Update user's Stripe customer ID if not already set
            $user = $pendingSubscription->user;
            if (!$user->stripe_customer_id && $session->customer) {
                $user->update(['stripe_customer_id' => $session->customer]);
            }

            \Log::info('Subscription activated successfully', [
                'user_id' => $pendingSubscription->user_id,
                'subscription_id' => $pendingSubscription->id,
                'stripe_subscription_id' => $session->subscription
            ]);

            return view('subscription-result', [
                'status' => 'success',
                'title' => 'Subscription Activated!',
                'message' => 'Your subscription has been successfully activated. Welcome to the premium experience!',
                'subscription' => $pendingSubscription->fresh(),
                'redirect_url' => env('FRONTEND_URL', config('app.url')) . '/dashboard' // Redirect to frontend
            ]);

        } catch (\Exception $e) {
            \Log::error('Stripe subscription success handling failed', [
                'session_id' => $request->get('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('subscription-result', [
                'status' => 'error',
                'title' => 'Processing Error',
                'message' => 'An error occurred while processing your subscription. Please contact support.',
                'redirect_url' => env('FRONTEND_URL', config('app.url'))
            ]);
        }
    }

    /**
     * Handle Stripe subscription cancel callback from web
     */
    public function handleStripeCancel(Request $request)
    {
        return view('subscription-result', [
            'status' => 'cancelled',
            'title' => 'Subscription Cancelled',
            'message' => 'Your subscription process was cancelled. You can try again anytime.',
            'redirect_url' => env('FRONTEND_URL', config('app.url')) . '/subscriptions' // Redirect to frontend subscriptions
        ]);
    }

}
