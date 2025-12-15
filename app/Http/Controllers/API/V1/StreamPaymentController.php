<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\NombaPyamentHelper;
use App\Http\Controllers\API\BaseController;
use App\Models\Stream;
use App\Models\StreamPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Illuminate\Support\Facades\Log;

class StreamPaymentController extends BaseController
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Initialize Stripe payment for stream access
     */
    public function initializeStripePayment(Request $request, $streamId)
    {
        try {
            $stream = Stream::find($streamId);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            if (!$stream->is_paid) {
                return $this->sendError('This stream is free to access', null, 400);
            }

            if ($stream->status === 'ended') {
                return $this->sendError('Cannot purchase access to ended stream', null, 400);
            }

            $user = $request->user();

            // Check if user already has access
            if ($stream->hasUserPaid($user)) {
                return $this->sendError('You already have access to this stream', null, 400);
            }

            $validator = Validator::make($request->all(), [
                'success_url' => 'nullable|url',
                'cancel_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            // Store client URLs for later redirection
            $clientSuccessUrl = $request->success_url ?? null;
            $clientCancelUrl = $request->cancel_url ?? null;

            // Backend URLs that will handle the payment and then redirect to client
            $successUrl = route('api.v1.stream-payments.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('api.v1.stream-payments.stripe.cancel');

            // Create payment record
            $payment = StreamPayment::create([
                'user_id' => $user->id,
                'stream_id' => $stream->id,
                'amount' => $stream->price,
                'currency' => $stream->currency,
                'reference' => StreamPayment::generateReference(),
                'payment_gateway' => 'stripe',
                'status' => 'pending',
                'gateway_response' => [
                    'client_success_url' => $clientSuccessUrl,
                    'client_cancel_url' => $clientCancelUrl
                ]
            ]);

            // Create Stripe checkout session
            $stripeSession = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($stream->currency),
                        'product_data' => [
                            'name' => 'Stream Access: ' . $stream->title,
                            'description' => 'Access to live stream: ' . $stream->title,
                        ],
                        'unit_amount' => (int)($stream->price * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => $payment->id,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'stream_id' => $stream->id,
                    'reference' => $payment->reference,
                    'type' => 'stream_payment'
                ],
            ]);

            // Update payment with Stripe session ID
            $payment->update([
                'gateway_transaction_id' => $stripeSession->id,
            ]);

            Log::info('Stripe payment session created for stream', [
                'payment_id' => $payment->id,
                'session_id' => $stripeSession->id,
                'stream_id' => $stream->id,
                'user_id' => $user->id
            ]);

            return $this->sendResponse('Stripe payment session created successfully', [
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                ],
                'stripe' => [
                    'session_id' => $stripeSession->id,
                    'checkout_url' => $stripeSession->url,
                ],
                'stream' => [
                    'id' => $stream->id,
                    'title' => $stream->title,
                    'price' => $stream->price,
                    'currency' => $stream->currency,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe payment initialization failed', [
                'error' => $e->getMessage(),
                'stream_id' => $streamId,
                'user_id' => $request->user()->id ?? null
            ]);

            return $this->sendError('Failed to initialize Stripe payment', $e->getMessage(), 500);
        }
    }

    /**
     * Initialize Nomba payment for stream access
     */
    public function initializeNombaPayment(Request $request, $streamId)
    {
        try {
            $stream = Stream::find($streamId);

            if (!$stream) {
                return $this->sendError('Stream not found', null, 404);
            }

            if (!$stream->is_paid) {
                return $this->sendError('This stream is free to access', null, 400);
            }

            if ($stream->status === 'ended') {
                return $this->sendError('Cannot purchase access to ended stream', null, 400);
            }

            $user = $request->user();

            // Check if user already has access
            if ($stream->hasUserPaid($user)) {
                return $this->sendError('You already have access to this stream', null, 400);
            }

            $validator = Validator::make($request->all(), [
                'currency' => 'required|in:NGN,USD',
                'success_url' => 'nullable|url',
                'cancel_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            // Store client URLs for later redirection
            $clientSuccessUrl = $request->success_url ?? null;
            $clientCancelUrl = $request->cancel_url ?? null;

            // Backend callback URL for Nomba
            $callbackUrl = route('api.v1.stream-payments.nomba.callback');

            // Currency conversion logic
            $streamCurrency = $stream->currency;
            $requestedCurrency = $request->currency;
            $streamPrice = $stream->price;
            $conversionRate = 1500; // 1 USD = 1500 NGN

            // Calculate the amount in requested currency
            $paymentAmount = $streamPrice;
            $originalAmount = $streamPrice;
            $originalCurrency = $streamCurrency;

            if ($streamCurrency !== $requestedCurrency) {
                if ($streamCurrency === 'NGN' && $requestedCurrency === 'USD') {
                    // Convert NGN to USD (NGN / 1500)
                    $paymentAmount = round($streamPrice / $conversionRate, 2);
                } elseif ($streamCurrency === 'USD' && $requestedCurrency === 'NGN') {
                    // Convert USD to NGN (USD * 1500)
                    $paymentAmount = round($streamPrice * $conversionRate, 2);
                }
            }

            // Create payment record
            $payment = StreamPayment::create([
                'user_id' => $user->id,
                'stream_id' => $stream->id,
                'amount' => $paymentAmount,
                'currency' => $requestedCurrency,
                'reference' => StreamPayment::generateReference(),
                'payment_gateway' => 'nomba',
                'status' => 'pending',
                'gateway_response' => [
                    'client_success_url' => $clientSuccessUrl,
                    'client_cancel_url' => $clientCancelUrl,
                    'original_amount' => $originalAmount,
                    'original_currency' => $originalCurrency,
                    'conversion_rate' => $conversionRate,
                    'currency_converted' => $streamCurrency !== $requestedCurrency
                ]
            ]);

            // Initialize Nomba payment
            $nombaHelper = new NombaPyamentHelper();

            $nombaResponse = $nombaHelper->processPayment(
                $paymentAmount,
                $requestedCurrency,
                $user->email,
                $callbackUrl,
                $payment->reference
            );

            if (!$nombaResponse['status']) {
                $payment->markAsFailed(['error' => $nombaResponse['message'] ?? 'Unknown error']);
                return $this->sendError('Failed to initialize Nomba payment', $nombaResponse['message'] ?? 'Unknown error', 400);
            }

            // Update payment with Nomba transaction details
            $payment->update([
                'gateway_transaction_id' => $nombaResponse['orderReference'] ?? null,
                'gateway_response' => array_merge($nombaResponse, [
                    'client_success_url' => $clientSuccessUrl,
                    'client_cancel_url' => $clientCancelUrl,
                    'original_amount' => $originalAmount,
                    'original_currency' => $originalCurrency,
                    'conversion_rate' => $conversionRate,
                    'currency_converted' => $streamCurrency !== $requestedCurrency
                ]),
            ]);

            Log::info('Nomba payment initialized for stream', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'stream_id' => $stream->id,
                'user_id' => $user->id,
                'original_amount' => $originalAmount,
                'original_currency' => $originalCurrency,
                'payment_amount' => $paymentAmount,
                'payment_currency' => $requestedCurrency,
                'currency_converted' => $streamCurrency !== $requestedCurrency
            ]);

            return $this->sendResponse('Nomba payment initialized successfully', [
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $paymentAmount,
                    'currency' => $requestedCurrency,
                    'status' => $payment->status,
                    'original_amount' => $originalAmount,
                    'original_currency' => $originalCurrency,
                    'currency_converted' => $streamCurrency !== $requestedCurrency,
                    'conversion_rate' => $conversionRate
                ],
                'nomba' => [
                    'checkout_url' => $nombaResponse['checkoutLink'],
                    'order_reference' => $nombaResponse['orderReference'],
                ],
                'stream' => [
                    'id' => $stream->id,
                    'title' => $stream->title,
                    'price' => $streamPrice,
                    'currency' => $streamCurrency,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Nomba payment initialization failed', [
                'error' => $e->getMessage(),
                'stream_id' => $streamId,
                'user_id' => $request->user()->id ?? null
            ]);

            return $this->sendError('Failed to initialize Nomba payment', $e->getMessage(), 500);
        }
    }

    /**
     * Verify payment status (Mobile-friendly - accepts multiple ID types)
     */
    public function verifyPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reference' => 'nullable|string',
                'transaction_id' => 'nullable|string',
                'session_id' => 'nullable|string',
                'payment_intent' => 'nullable|string',
                'gateway' => 'nullable|in:stripe,nomba',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            // Find payment by reference, transaction_id, session_id, or payment_intent
            $payment = null;
            $gateway = $request->gateway;

            // Try to find by reference first (most common)
            if ($request->reference) {
                $query = StreamPayment::where('reference', $request->reference);
                if ($gateway) {
                    $query->where('payment_gateway', $gateway);
                }
                $payment = $query->first();
            }

            // Try to find by Stripe session ID or payment intent
            if (!$payment && ($request->session_id || $request->payment_intent || $request->transaction_id)) {
                $transactionId = $request->session_id ?? $request->payment_intent ?? $request->transaction_id;
                $query = StreamPayment::where('gateway_transaction_id', $transactionId);
                if ($gateway) {
                    $query->where('payment_gateway', $gateway);
                }
                $payment = $query->first();
            }

            if (!$payment) {
                // If no payment found, try to verify directly with Stripe
                if ($request->session_id || $request->payment_intent) {
                    return $this->verifyStripeDirectly($request);
                }

                return $this->sendError('Payment not found', null, 404);
            }

            // If payment already completed, return success
            if ($payment->status === 'completed') {
                return $this->sendResponse('Payment already verified', [
                    'payment' => [
                        'id' => $payment->id,
                        'reference' => $payment->reference,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'paid_at' => $payment->paid_at,
                    ],
                    'stream' => [
                        'id' => $payment->stream->id,
                        'title' => $payment->stream->title,
                        'access_granted' => true,
                    ]
                ]);
            }

            $verified = false;
            $verificationData = [];

            if ($payment->payment_gateway === 'stripe') {
                $verified = $this->verifyStripePayment($payment, $verificationData);
            } elseif ($payment->payment_gateway === 'nomba') {
                $verified = $this->verifyNombaPayment($payment, $verificationData);
            }

            if ($verified) {
                $payment->markAsCompleted($verificationData['transaction_id'] ?? null, $verificationData);

                return $this->sendResponse('Payment verified successfully', [
                    'payment' => [
                        'id' => $payment->id,
                        'reference' => $payment->reference,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'paid_at' => $payment->paid_at,
                    ],
                    'stream' => [
                        'id' => $payment->stream->id,
                        'title' => $payment->stream->title,
                        'access_granted' => true,
                    ]
                ]);
            } else {
                $payment->markAsFailed($verificationData);
                return $this->sendError('Payment verification failed', $verificationData['error'] ?? 'Unknown error', 400);
            }

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'error' => $e->getMessage(),
                'reference' => $request->reference ?? null,
                'transaction_id' => $request->transaction_id ?? null
            ]);

            return $this->sendError('Failed to verify payment', $e->getMessage(), 500);
        }
    }

    /**
     * Verify Stripe payment directly without pre-existing payment record
     */
    private function verifyStripeDirectly(Request $request)
    {
        try {
            $sessionId = $request->session_id ?? $request->payment_intent;

            if (!$sessionId) {
                return $this->sendError('Session ID or Payment Intent required', null, 400);
            }

            Log::info('Verifying Stripe payment directly', ['session_id' => $sessionId]);

            // Try as checkout session first
            try {
                $session = StripeSession::retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    $paymentId = $session->metadata->payment_id ?? null;
                    $streamId = $session->metadata->stream_id ?? null;
                    $userId = $session->metadata->user_id ?? null;

                    // Find or create payment record
                    $payment = null;
                    if ($paymentId) {
                        $payment = StreamPayment::find($paymentId);
                    }

                    if ($payment) {
                        if ($payment->status !== 'completed') {
                            $payment->markAsCompleted($session->id, $session->toArray());
                        }

                        return $this->sendResponse('Payment verified successfully', [
                            'payment' => [
                                'id' => $payment->id,
                                'reference' => $payment->reference,
                                'amount' => $payment->amount,
                                'currency' => $payment->currency,
                                'status' => $payment->status,
                                'paid_at' => $payment->paid_at,
                            ],
                            'stream' => [
                                'id' => $payment->stream->id,
                                'title' => $payment->stream->title,
                                'access_granted' => true,
                            ]
                        ]);
                    } else {
                        return $this->sendResponse('Payment successful on Stripe', [
                            'verified' => true,
                            'session_id' => $session->id,
                            'payment_status' => $session->payment_status,
                            'amount_total' => $session->amount_total / 100,
                            'currency' => strtoupper($session->currency),
                            'message' => 'Payment verified with Stripe, but local record not found'
                        ]);
                    }
                }

                return $this->sendError('Payment not completed on Stripe', [
                    'session_id' => $session->id,
                    'payment_status' => $session->payment_status
                ], 400);

            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Not a session, might be a payment intent
                Log::info('Not a Stripe session, might be payment intent', ['error' => $e->getMessage()]);
                return $this->sendError('Invalid session or payment not found', $e->getMessage(), 404);
            }

        } catch (\Exception $e) {
            Log::error('Direct Stripe verification failed', [
                'error' => $e->getMessage(),
                'session_id' => $request->session_id ?? null
            ]);

            return $this->sendError('Failed to verify payment with Stripe', $e->getMessage(), 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, $paymentId)
    {
        try {
            $payment = StreamPayment::with('stream:id,title,status')->find($paymentId);

            if (!$payment) {
                return $this->sendError('Payment not found', null, 404);
            }

            // Check if user owns this payment
            if ($payment->user_id !== $request->user()->id) {
                return $this->sendError('Unauthorized', null, 403);
            }

            return $this->sendResponse('Payment status retrieved successfully', [
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'payment_gateway' => $payment->payment_gateway,
                    'paid_at' => $payment->paid_at,
                    'created_at' => $payment->created_at,
                ],
                'stream' => $payment->stream,
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to get payment status', $e->getMessage(), 500);
        }
    }

    /**
     * Get user's stream payments
     */
    public function getUserPayments(Request $request)
    {
        try {
            $payments = StreamPayment::with('stream:id,title,status,banner_image_url')
                ->where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendResponse('User stream payments retrieved successfully', [
                'payments' => $payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'reference' => $payment->reference,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'payment_gateway' => $payment->payment_gateway,
                        'paid_at' => $payment->paid_at,
                        'created_at' => $payment->created_at,
                        'stream' => $payment->stream,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to get user payments', $e->getMessage(), 500);
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleStripeWebhook(Request $request)
    {
        try {
            $payload = $request->getContent();
            $sig_header = $request->header('Stripe-Signature');
            $endpoint_secret = config('services.stripe.webhook_secret');

            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            Log::info('Stripe webhook received', ['type' => $event['type']]);

            if ($event['type'] === 'checkout.session.completed') {
                $session = $event['data']['object'];
                $paymentId = $session['metadata']['payment_id'] ?? null;

                if ($paymentId) {
                    $payment = StreamPayment::find($paymentId);
                    if ($payment && $payment->status === 'pending') {
                        $payment->markAsCompleted($session['id'], $session);
                        Log::info('Stream payment completed via Stripe webhook', ['payment_id' => $paymentId]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Stripe webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle Nomba webhook
     */
    public function handleNombaWebhook(Request $request)
    {
        try {
            Log::info('Nomba webhook received', $request->all());

            $reference = $request->input('orderReference');
            $status = $request->input('status');

            if ($reference && in_array($status, ['COMPLETED', 'SUCCESS'])) {
                $payment = StreamPayment::where('reference', $reference)->first();
                if ($payment && $payment->status === 'pending') {
                    $payment->markAsCompleted($request->input('transactionReference'), $request->all());
                    Log::info('Stream payment completed via Nomba webhook', ['reference' => $reference]);
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Nomba webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify Stripe payment
     */
    private function verifyStripePayment(StreamPayment $payment, &$verificationData): bool
    {
        try {
            if (!$payment->gateway_transaction_id) {
                $verificationData['error'] = 'No Stripe session ID found';
                return false;
            }

            $session = StripeSession::retrieve($payment->gateway_transaction_id);

            if ($session->payment_status === 'paid') {
                $verificationData['transaction_id'] = $session->id;
                $verificationData['stripe_session'] = $session->toArray();
                return true;
            }

            $verificationData['error'] = 'Payment not completed on Stripe';
            return false;

        } catch (\Exception $e) {
            $verificationData['error'] = 'Stripe verification failed: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Verify Nomba payment
     */
    private function verifyNombaPayment(StreamPayment $payment, &$verificationData): bool
    {
        try {
            $nombaHelper = new NombaPyamentHelper();
            $tokenData = $nombaHelper->nombaAccessToken();

            if (!$tokenData) {
                $verificationData['error'] = 'Failed to get Nomba access token';
                return false;
            }

            // You would implement the Nomba payment verification API call here
            // This is a placeholder - implement according to Nomba's verification API

            $verificationData['transaction_id'] = $payment->reference;
            return true; // Simplified for now

        } catch (\Exception $e) {
            $verificationData['error'] = 'Nomba verification failed: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Handle Stripe payment success callback from web
     */
    public function handleStripeSuccess(Request $request)
    {
        try {
            $sessionId = $request->get('session_id');

            if (!$sessionId) {
                return view('payment-result', [
                    'status' => 'error',
                    'title' => 'Payment Error',
                    'message' => 'Invalid session ID provided',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

            Log::info('Processing Stripe stream payment success', [
                'session_id' => $sessionId
            ]);

            // Find the pending payment by session ID
            $payment = StreamPayment::where('gateway_transaction_id', $sessionId)
                ->where('status', 'pending')
                ->first();

            if (!$payment) {
                return view('payment-result', [
                    'status' => 'error',
                    'title' => 'Payment Not Found',
                    'message' => 'No pending payment found for this session',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

            // Retrieve the session from Stripe
            $session = StripeSession::retrieve($sessionId);

            Log::info('Retrieved Stripe session', [
                'session_id' => $sessionId,
                'payment_status' => $session->payment_status
            ]);

            if ($session->payment_status !== 'paid') {
                Log::warning('Payment not completed', [
                    'session_id' => $sessionId,
                    'payment_status' => $session->payment_status
                ]);
                return view('payment-result', [
                    'status' => 'error',
                    'title' => 'Payment Not Completed',
                    'message' => 'Payment has not been completed yet',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

            // Get client URLs from existing gateway_response
            $existingResponse = $payment->gateway_response;
            $clientSuccessUrl = null;
            $clientCancelUrl = null;

            if (is_array($existingResponse)) {
                $clientSuccessUrl = $existingResponse['client_success_url'] ?? null;
                $clientCancelUrl = $existingResponse['client_cancel_url'] ?? null;
            } elseif (is_string($existingResponse)) {
                $decoded = json_decode($existingResponse, true);
                if ($decoded) {
                    $clientSuccessUrl = $decoded['client_success_url'] ?? null;
                    $clientCancelUrl = $decoded['client_cancel_url'] ?? null;
                }
            }

            // Mark payment as completed
            $payment->markAsCompleted($session->id, [
                'client_success_url' => $clientSuccessUrl,
                'client_cancel_url' => $clientCancelUrl,
                'stripe_session_id' => $sessionId,
                'amount_paid' => $session->amount_total / 100,
                'currency' => strtoupper($session->currency),
                'payment_method' => 'stripe',
                'processed_at' => now()->toISOString()
            ]);

            Log::info('Stream payment completed successfully', [
                'payment_id' => $payment->id,
                'stream_id' => $payment->stream_id,
                'user_id' => $payment->user_id
            ]);

            // If client provided a success URL, redirect there
            if ($clientSuccessUrl) {
                $separator = parse_url($clientSuccessUrl, PHP_URL_QUERY) ? '&' : '?';
                $redirectUrl = $clientSuccessUrl . $separator . http_build_query([
                    'session_id' => $sessionId,
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'status' => 'success'
                ]);

                Log::info('Redirecting to client success URL', [
                    'client_url' => $redirectUrl
                ]);

                return redirect()->away($redirectUrl);
            }

            // Otherwise show success view
            return view('payment-result', [
                'status' => 'success',
                'title' => 'Payment Successful!',
                'message' => 'Your payment was successful. You now have access to the stream.',
                'payment' => $payment->fresh(),
                'redirect_url' => env('FRONTEND_URL', config('app.url')) . '/streams/' . $payment->stream_id
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe stream payment success handling failed', [
                'session_id' => $request->get('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('payment-result', [
                'status' => 'error',
                'title' => 'Processing Error',
                'message' => 'An error occurred while processing your payment. Please contact support.',
                'redirect_url' => env('FRONTEND_URL', config('app.url'))
            ]);
        }
    }

    /**
     * Handle Stripe payment cancel callback from web
     */
    public function handleStripeCancel(Request $request)
    {
        return view('payment-result', [
            'status' => 'cancelled',
            'title' => 'Payment Cancelled',
            'message' => 'Your payment was cancelled. You can try again anytime.',
            'redirect_url' => env('FRONTEND_URL', config('app.url')) . '/streams'
        ]);
    }

    /**
     * Handle Nomba payment callback from web
     */
    public function handleNombaCallback(Request $request)
    {
        try {
            $orderId = $request->query('orderId') ?? $request->query('orderid');
            $orderReference = $orderId;

            if (!$orderId && !$orderReference) {
                Log::warning('No order ID or reference found in Nomba callback', [
                    'url' => $request->fullUrl(),
                    'query_params' => $request->query()
                ]);

                return view('payment-result', [
                    'status' => 'error',
                    'title' => 'Payment Error',
                    'message' => 'Invalid payment reference',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

            Log::info('Nomba stream payment callback received', [
                'orderId' => $orderId,
                'orderReference' => $orderReference,
                'all_params' => $request->all()
            ]);

            // Find payment by reference
            $payment = null;

            if ($orderReference) {
                $payment = StreamPayment::where('reference', $orderReference)->first();
            }

            if (!$payment && $orderId) {
                $payment = StreamPayment::where('gateway_response', 'like', '%' . $orderId . '%')
                    ->where('payment_gateway', 'nomba')
                    ->first();
            }

            if (!$payment) {
                Log::warning('Stream payment not found', [
                    'orderReference' => $orderReference,
                    'orderId' => $orderId
                ]);

                return view('payment-result', [
                    'status' => 'error',
                    'title' => 'Payment Not Found',
                    'message' => 'Payment record not found',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

            // Check if already processed
            if ($payment->status === 'completed') {
                Log::info('Payment already processed', [
                    'payment_id' => $payment->id,
                    'orderReference' => $orderReference
                ]);

                // Get client URL from gateway_response
                $gatewayResponse = $payment->gateway_response;
                $clientSuccessUrl = null;

                if (is_array($gatewayResponse)) {
                    $clientSuccessUrl = $gatewayResponse['client_success_url'] ?? null;
                } elseif (is_string($gatewayResponse)) {
                    $decoded = json_decode($gatewayResponse, true);
                    if ($decoded) {
                        $clientSuccessUrl = $decoded['client_success_url'] ?? null;
                    }
                }

                if ($clientSuccessUrl) {
                    $separator = parse_url($clientSuccessUrl, PHP_URL_QUERY) ? '&' : '?';
                    return redirect()->away($clientSuccessUrl . $separator . http_build_query([
                        'orderId' => $orderId,
                        'reference' => $payment->reference,
                        'status' => 'success'
                    ]));
                }

                return view('payment-result', [
                    'status' => 'success',
                    'title' => 'Payment Already Processed',
                    'message' => 'This payment has already been completed',
                    'redirect_url' => env('FRONTEND_URL', config('app.url')) . '/streams/' . $payment->stream_id
                ]);
            }

            // Verify payment with Nomba
            $nombaHelper = new NombaPyamentHelper();
            $verificationResult = $nombaHelper->verifyPayment($orderId);

            Log::info('Nomba verification result', [
                'orderId' => $orderId,
                'result' => $verificationResult
            ]);

            if ($verificationResult['status']) {
                // Get client URLs before updating
                $gatewayResponse = $payment->gateway_response;
                $clientSuccessUrl = null;
                $clientCancelUrl = null;

                if (is_array($gatewayResponse)) {
                    $clientSuccessUrl = $gatewayResponse['client_success_url'] ?? null;
                    $clientCancelUrl = $gatewayResponse['client_cancel_url'] ?? null;
                } elseif (is_string($gatewayResponse)) {
                    $decoded = json_decode($gatewayResponse, true);
                    if ($decoded) {
                        $clientSuccessUrl = $decoded['client_success_url'] ?? null;
                        $clientCancelUrl = $decoded['client_cancel_url'] ?? null;
                    }
                }

                // Mark payment as completed
                $payment->markAsCompleted($orderId, [
                    'client_success_url' => $clientSuccessUrl,
                    'client_cancel_url' => $clientCancelUrl,
                    'nomba_order_id' => $orderId,
                    'verification_result' => $verificationResult,
                    'processed_at' => now()->toISOString()
                ]);

                Log::info('Stream payment completed successfully', [
                    'payment_id' => $payment->id,
                    'stream_id' => $payment->stream_id,
                    'user_id' => $payment->user_id
                ]);

                // Redirect to client success URL if provided
                if ($clientSuccessUrl) {
                    $separator = parse_url($clientSuccessUrl, PHP_URL_QUERY) ? '&' : '?';
                    return redirect()->away($clientSuccessUrl . $separator . http_build_query([
                        'orderId' => $orderId,
                        'reference' => $payment->reference,
                        'status' => 'success'
                    ]));
                }

                // Otherwise show success view
                return view('payment-result', [
                    'status' => 'success',
                    'title' => 'Payment Successful!',
                    'message' => 'Your payment was successful. You now have access to the stream.',
                    'payment' => $payment->fresh(),
                    'redirect_url' => env('FRONTEND_URL', config('app.url')) . '/streams/' . $payment->stream_id
                ]);
            } else {
                // Payment verification failed
                $payment->markAsFailed([
                    'verification_result' => $verificationResult,
                    'failed_at' => now()->toISOString()
                ]);

                return view('payment-result', [
                    'status' => 'error',
                    'title' => 'Payment Verification Failed',
                    'message' => 'Payment verification failed. Please contact support.',
                    'redirect_url' => env('FRONTEND_URL', config('app.url'))
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Nomba stream payment callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('payment-result', [
                'status' => 'error',
                'title' => 'Processing Error',
                'message' => 'An error occurred while processing your payment. Please contact support.',
                'redirect_url' => env('FRONTEND_URL', config('app.url'))
            ]);
        }
    }
}
