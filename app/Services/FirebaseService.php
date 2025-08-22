<?php

namespace App\Services;

use App\Models\PushNotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private $serverKey;
    private $projectId;
    private $credentialsPath;
    private $fcmV1Url;
    private $legacyFcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
        $this->projectId = config('services.firebase.project_id');
        $this->credentialsPath = config('services.firebase.credentials_path');
        $this->fcmV1Url = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';
    }

    /**
     * Send notification to a specific FCM token
     */
    public function sendNotification($fcmToken, $title, $body, $data = [], $userId = null)
    {
        try {
            // FCM requires all data values to be strings
            $data = collect($data)->map(function($v) {
                return is_null($v) ? '' : (string)$v;
            })->toArray();

            // Try FCM HTTP v1 API first
            $fcmSuccess = $this->sendWithV1Api($fcmToken, $title, $body, $data);

            if ($fcmSuccess) {
                PushNotificationLog::create([
                    'user_id' => $userId,
                    'fcm_token' => $fcmToken,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'status' => 'sent',
                    'response' => '{"success": true, "method": "fcm_v1"}',
                    'error_message' => null,
                    'sent_at' => now()
                ]);
                return true;
            }

            // Fallback to legacy API if v1 fails
            $fcmLegacySuccess = $this->sendWithLegacyApi($fcmToken, $title, $body, $data);
            if ($fcmLegacySuccess) {
                PushNotificationLog::create([
                    'user_id' => $userId,
                    'fcm_token' => $fcmToken,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'status' => 'sent',
                    'response' => '{"success": true, "method": "fcm_legacy"}',
                    'error_message' => null,
                    'sent_at' => now()
                ]);
                return true;
            }

            // FCM failed - try Web Push for admin notifications
            $isAdminNotification = isset($data['type']) && $data['type'] === 'admin_notification';
            if ($isAdminNotification && isset($data['admin_id'])) {
                $webPushSuccess = $this->tryWebPushForAdmin($data['admin_id'], $title, $body, $data, $userId);
                if ($webPushSuccess) {
                    return true;
                }
            }

            // Both FCM and Web Push failed
            PushNotificationLog::create([
                'user_id' => $userId,
                'fcm_token' => $fcmToken,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => 'failed',
                'response' => '{"error": "All notification methods failed"}',
                'error_message' => 'FCM v1 and Legacy API unavailable and Web Push not configured',
                'sent_at' => now()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Firebase notification error: ' . $e->getMessage());
            PushNotificationLog::create([
                'user_id' => $userId,
                'fcm_token' => $fcmToken,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => now()
            ]);
            return false;
        }
    }

    /**
     * Try sending Web Push notification to admin
     */
    private function tryWebPushForAdmin($adminId, $title, $body, $data, $userId)
    {
        try {
            // Find admin tokens with Web Push data
            $adminTokens = \App\Models\AdminFcmToken::where('admin_id', $adminId)
                ->where('is_active', true)
                ->whereNotNull('push_endpoint')
                ->whereNotNull('push_p256dh')
                ->whereNotNull('push_auth')
                ->get();

            $sent = false;
            foreach ($adminTokens as $token) {
                $webPushService = new \App\Services\WebPushService();
                $result = $webPushService->sendNotification(
                    $token->push_endpoint,
                    $token->push_p256dh,
                    $token->push_auth,
                    $title,
                    $body,
                    $data,
                    $userId
                );

                if ($result) {
                    $sent = true;
                    $token->markAsUsed();
                }
            }

            if ($sent) {
                Log::info('Web Push notification sent successfully for admin', ['admin_id' => $adminId]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Web Push error for admin: ' . $e->getMessage());
            return false;
        }
    }    /**
     * Send notification using Legacy FCM API
     */
    private function sendWithLegacyApi($fcmToken, $title, $body, $data = [])
    {
        try {
            $notification = [
                'title' => $title,
                'body' => $body,
                'icon' => asset('images/connect_logo.png'),
                'click_action' => url('/'),
                'sound' => 'default'
            ];

            // Ensure all data values are strings
            $data = collect($data)->map(function($v) {
                return is_null($v) ? '' : (string)$v;
            })->toArray();

            $payload = [
                'to' => $fcmToken,
                'notification' => $notification,
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]),
                'priority' => 'high'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json'
            ])->post($this->legacyFcmUrl, $payload);

            $result = $response->json();

            if ($response->successful() && isset($result['success']) && $result['success'] > 0) {
                Log::info('FCM Legacy API: Notification sent successfully');
                return true;
            }

            Log::warning('FCM Legacy API failed', ['status' => $response->status(), 'result' => $result]);
            return false;

        } catch (\Exception $e) {
            Log::error('FCM Legacy API error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification using FCM HTTP v1 API (fallback)
     */
    private function sendWithV1Api($fcmToken, $title, $body, $data = [])
    {
        try {
            // Load service account credentials
            $credentialsPath = $this->credentialsPath;
            if (!file_exists($credentialsPath)) {
                Log::warning('FCM HTTP v1 API: Service account credentials not found at ' . $credentialsPath);
                return false;
            }
            $credentials = json_decode(file_get_contents($credentialsPath), true);
            if (!isset($credentials['client_email'], $credentials['private_key'], $credentials['project_id'])) {
                Log::warning('FCM HTTP v1 API: Invalid service account credentials');
                return false;
            }

            // Get OAuth2 access token
            $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now = time();
            $jwtClaimSet = base64_encode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $jwtToSign = $jwtHeader . '.' . $jwtClaimSet;
            openssl_sign($jwtToSign, $signature, $credentials['private_key'], 'sha256');
            $jwt = $jwtToSign . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
            $accessToken = $tokenResponse->json('access_token');
            if (!$accessToken) {
                Log::warning('FCM HTTP v1 API: Failed to obtain access token', ['response' => $tokenResponse->json()]);
                return false;
            }


            // Ensure all data values are strings
            $data = collect($data)->map(function($v) {
                return is_null($v) ? '' : (string)$v;
            })->toArray();

            // Build v1 message payload
            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ]
            ];

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->fcmV1Url, $message);

            $result = $response->json();
            if ($response->successful() && isset($result['name'])) {
                Log::info('FCM HTTP v1 API: Notification sent successfully');
                return true;
            }
            Log::warning('FCM HTTP v1 API failed', ['status' => $response->status(), 'result' => $result]);
            return false;
        } catch (\Exception $e) {
            Log::error('FCM HTTP v1 API error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to multiple tokens
     */
    public function sendToMultiple($fcmTokens, $title, $body, $data = [])
    {
        $results = [];

        foreach ($fcmTokens as $token => $userId) {
            $results[] = $this->sendNotification(
                is_numeric($token) ? $userId : $token,
                $title,
                $body,
                $data,
                is_numeric($token) ? null : $userId
            );
        }

        return $results;
    }

    /**
     * Send notification to a topic
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        try {
            $notification = [
                'title' => $title,
                'body' => $body,
                'icon' => asset('images/connect_logo.png'),
                'click_action' => url('/'),
                'sound' => 'default'
            ];

            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => $notification,
                'data' => $data,
                'priority' => 'high'
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json'
            ])->post($this->fcmUrl, $payload);

            $result = $response->json();
            return $response->successful() && isset($result['message_id']);

        } catch (\Exception $e) {
            Log::error('Firebase topic notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Subscribe user to topic
     */
    public function subscribeToTopic($fcmToken, $topic)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json'
            ])->post('https://iid.googleapis.com/iid/v1/' . $fcmToken . '/rel/topics/' . $topic);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Firebase topic subscription error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsubscribe user from topic
     */
    public function unsubscribeFromTopic($fcmToken, $topic)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json'
            ])->delete('https://iid.googleapis.com/iid/v1/' . $fcmToken . '/rel/topics/' . $topic);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Firebase topic unsubscription error: ' . $e->getMessage());
            return false;
        }
    }
}
