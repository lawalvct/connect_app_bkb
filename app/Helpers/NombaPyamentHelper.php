<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\UserSubscription;
use Illuminate\Support\Str;
use Auth;
use Mail;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class NombaPyamentHelper {

    function nombaAccessToken()
    {
        $AccountId = Setting::where(['slug' => 'nombaAccountID'])->first()->value;
        $client_id = Setting::where(['slug' => 'nombaClientID'])->first()->value;
        $client_secret = Setting::where(['slug' => 'nombaPrivatekey'])->first()->value;

        $response = Http::withHeaders([
            'AccountId' => $AccountId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://api.nomba.com/v1/auth/token/issue', [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ]);

        $result = $response->json();

        if (isset($result['data']['access_token'])) {
            $accessToken = $result['data']['access_token'];
            return ["accessToken" => $accessToken, "accountId" => $AccountId];
        }

        return null;
    }

    function processPayment($amount, $currency = 'NGN', $email = null, $callbackUrl = null, $customReference = null)
    {
        $tokenData = $this->nombaAccessToken();

        if (!$tokenData) {
            return [
                'status' => false,
                'message' => 'Failed to get access token'
            ];
        }

        $AccountId = $tokenData['accountId'];
        $accessToken = $tokenData['accessToken'];

        // Use authenticated user's email if not provided
        if (!$email && Auth::check()) {
            $email = Auth::user()->email;
        }

        // Default callback URL if not provided
        if (!$callbackUrl) {
            $callbackUrl = env('FE_APP_URL').'/customer';
        }

        // Generate a unique reference if not provided
        if (!$customReference) {
            $customReference = Str::uuid();
        }

        // Validate currency
        $supportedCurrencies = ['NGN', 'USD'];
        if (!in_array(strtoupper($currency), $supportedCurrencies)) {
            return [
                'status' => false,
                'message' => 'Unsupported currency. Supported currencies: ' . implode(', ', $supportedCurrencies)
            ];
        }

        $response = Http::withHeaders([
            'accountId' => $AccountId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post('https://api.nomba.com/v1/checkout/order', [
            'order' => [
                'orderReference' => $customReference,
                'callbackUrl' => $callbackUrl,
                'customerEmail' => $email,
                'amount' => $amount,
                'currency' => strtoupper($currency),
            ],
            'tokenizeCard' => 'true',
        ]);

        $result = $response->json();

        if (isset($result['data']['checkoutLink']) && isset($result['data']['orderReference'])) {
            $checkoutLink = $result['data']['checkoutLink'];
            $orderReference = $result['data']['orderReference'];

            return [
                'status' => true,
                'checkoutLink' => $checkoutLink,
                'orderReference' => $orderReference,
                'currency' => strtoupper($currency),
                'amount' => $amount
            ];
        }

        return [
            'status' => false,
            'message' => 'Failed to process payment',
            'response' => $result
        ];
    }

    function verifyPayment($orderReference)
    {
        $tokenData = $this->nombaAccessToken();

        if (!$tokenData) {
            return [
                'status' => false,
                'message' => 'Failed to get access token'
            ];
        }

        $AccountId = $tokenData['accountId'];
        $accessToken = $tokenData['accessToken'];

        $response = Http::withHeaders([
            'accountId' => $AccountId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("https://api.nomba.com/v1/checkout/transaction?idType=ORDER_ID&id=$orderReference");

        $result = $response->json();

        // Check for success based on the provided response format
        if (isset($result['data'])) {
            // Check if success field exists directly
            if (isset($result['data']['success'])) {
                $isSuccessful = $result['data']['success'] === true;
                $paymentStatus = $isSuccessful ? 'successful' : 'failed';
            }
            // Check if message field contains "PAYMENT SUCCESSFUL"
            elseif (isset($result['data']['message']) && $result['data']['message'] === 'PAYMENT SUCCESSFUL') {
                $isSuccessful = true;
                $paymentStatus = 'successful';
            }
            // Fallback to the original status check if available
            elseif (isset($result['data']['status'])) {
                $paymentStatus = $result['data']['status'];
                $isSuccessful = strtolower($paymentStatus) === 'successful';
            }
            // If none of the above conditions are met
            else {
                $isSuccessful = false;
                $paymentStatus = 'unknown';
            }

            return [
                'status' => $isSuccessful,
                'payment_status' => $paymentStatus,
                'data' => $result['data'],
                'response' => $result
            ];
        }

        return [
            'status' => false,
            'message' => 'Failed to verify payment',
            'response' => $result
        ];
    }

    /**
     * Convert USD to NGN using current exchange rate
     */
    public function convertUsdToNgn($usdAmount, $exchangeRate = 1500)
    {
        return $usdAmount * $exchangeRate;
    }

    /**
     * Get current USD to NGN exchange rate (you can implement API call here)
     */
    public function getExchangeRate()
    {
        // You can implement an API call to get real-time exchange rates
        // For now, return a default rate
        return 1500; // 1 USD = 1500 NGN
    }

    /**
     * Generate webhook signature for Nomba setup
     */
    public function generateWebhookSignature($payload, $secret)
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify incoming webhook signature
     */
    public function verifyWebhookSignature($payload, $signature, $secret)
    {
        $expectedSignature = $this->generateWebhookSignature($payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * NEW: Initiate payment for advertisements (uses existing processPayment method)
     */
    public function initiatePayment($paymentData)
    {
        try {
            $result = $this->processPayment(
                $paymentData['amount'],
                $paymentData['currency'],
                $paymentData['email'],
                $paymentData['callback_url'],
                $paymentData['reference']
            );

            if ($result['status']) {
                return [
                    'success' => true,
                    'message' => 'Payment initiated successfully',
                    'data' => [
                        'checkout_url' => $result['checkoutLink'],
                        'payment_link' => $result['checkoutLink'],
                        'reference' => $result['orderReference'],
                        'currency' => $result['currency'],
                        'amount' => $result['amount']
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Payment initiation failed'
            ];

        } catch (\Exception $e) {
            Log::error('Nomba payment initiation exception', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData
            ]);

            return [
                'success' => false,
                'message' => 'Payment service unavailable'
            ];
        }
    }

    /**
     * NEW: Verify webhook signature using settings
     */
    public function verifyWebhookSignatureFromRequest($request)
    {
        try {
            $signature = $request->header('X-Nomba-Signature');
            $payload = $request->getContent();

            // Get webhook secret from settings
            $webhookSecret = Setting::where(['slug' => 'nombaWebhookSecret'])->first();

            if (!$webhookSecret || !$webhookSecret->value) {
                Log::warning('Nomba webhook secret not found in settings');
                return false;
            }

            return $this->verifyWebhookSignature($payload, $signature, $webhookSecret->value);

        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * NEW: Process refund using existing token system
     */
    public function processRefund($transactionId, $amount, $reason = null)
    {
        try {
            $tokenData = $this->nombaAccessToken();

            if (!$tokenData) {
                return [
                    'success' => false,
                    'message' => 'Failed to get access token'
                ];
            }

            $AccountId = $tokenData['accountId'];
            $accessToken = $tokenData['accessToken'];

            $response = Http::withHeaders([
                'accountId' => $AccountId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post('https://api.nomba.com/v1/transaction/refund', [
                'transactionId' => $transactionId,
                'amount' => $amount,
                'reason' => $reason ?? 'Advertisement refund'
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Refund processing failed'
            ];

        } catch (\Exception $e) {
            Log::error('Nomba refund processing failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Refund service unavailable'
            ];
        }
    }

    /**
     * NEW: Enhanced verify payment method that works with existing verifyPayment
     */
    public function verifyPaymentByReference($reference)
    {
        $result = $this->verifyPayment($reference);

        return [
            'success' => $result['status'],
            'data' => $result['data'] ?? null,
            'payment_status' => $result['payment_status'] ?? 'unknown',
            'message' => $result['message'] ?? null
        ];
    }
}
