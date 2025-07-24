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

            // Create payment record
            $payment = StreamPayment::create([
                'user_id' => $user->id,
                'stream_id' => $stream->id,
                'amount' => $stream->price,
                'currency' => $stream->currency,
                'reference' => StreamPayment::generateReference(),
                'payment_gateway' => 'stripe',
                'status' => 'pending',
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
                'success_url' => $request->success_url ?? config('app.frontend_url') . '/streams/' . $streamId . '/success',
                'cancel_url' => $request->cancel_url ?? config('app.frontend_url') . '/streams/' . $streamId,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'stream_id' => $stream->id,
                    'reference' => $payment->reference,
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
                'callback_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            // Create payment record
            $payment = StreamPayment::create([
                'user_id' => $user->id,
                'stream_id' => $stream->id,
                'amount' => $stream->price,
                'currency' => $request->currency,
                'reference' => StreamPayment::generateReference(),
                'payment_gateway' => 'nomba',
                'status' => 'pending',
            ]);

            // Initialize Nomba payment
            $nombaHelper = new NombaPyamentHelper();
            $callbackUrl = $request->callback_url ?? config('app.frontend_url') . '/streams/' . $streamId . '/payment-callback';
            
            $nombaResponse = $nombaHelper->processPayment(
                $payment->amount,
                $payment->currency,
                $user->email,
                $callbackUrl,
                $payment->reference
            );

            if (!$nombaResponse['status']) {
                $payment->markAsFailed(['error' => $nombaResponse['message']]);
                return $this->sendError('Failed to initialize Nomba payment', $nombaResponse['message'], 400);
            }

            // Update payment with Nomba transaction details
            $payment->update([
                'gateway_transaction_id' => $nombaResponse['data']['orderReference'] ?? null,
                'gateway_response' => $nombaResponse,
            ]);

            Log::info('Nomba payment initialized for stream', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'stream_id' => $stream->id,
                'user_id' => $user->id
            ]);

            return $this->sendResponse('Nomba payment initialized successfully', [
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                ],
                'nomba' => $nombaResponse['data'],
                'stream' => [
                    'id' => $stream->id,
                    'title' => $stream->title,
                    'price' => $stream->price,
                    'currency' => $stream->currency,
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
     * Verify payment status
     */
    public function verifyPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reference' => 'required|string',
                'gateway' => 'required|in:stripe,nomba',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }

            $payment = StreamPayment::where('reference', $request->reference)
                ->where('payment_gateway', $request->gateway)
                ->first();

            if (!$payment) {
                return $this->sendError('Payment not found', null, 404);
            }

            $verified = false;
            $verificationData = [];

            if ($request->gateway === 'stripe') {
                $verified = $this->verifyStripePayment($payment, $verificationData);
            } elseif ($request->gateway === 'nomba') {
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
                'reference' => $request->reference ?? null
            ]);

            return $this->sendError('Failed to verify payment', $e->getMessage(), 500);
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
}
