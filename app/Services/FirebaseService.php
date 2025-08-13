<?php

namespace App\Services;

use App\Models\PushNotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private $serverKey;
    private $fcmUrl = 'https://fcm.googleapis.com/v1/projects/connect-app-fbaca/messages:send';
    private $legacyFcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * Send notification to a specific FCM token
     */
    public function sendNotification($fcmToken, $title, $body, $data = [], $userId = null)
    {
        try {
            // Try FCM Legacy API first
            $fcmSuccess = $this->sendWithLegacyApi($fcmToken, $title, $body, $data);

            if ($fcmSuccess) {
                // Log successful FCM notification
                PushNotificationLog::create([
                    'user_id' => $userId,
                    'fcm_token' => $fcmToken,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'status' => 'sent',
                    'response' => '{"success": true, "method": "fcm"}',
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
                'error_message' => 'FCM Legacy API unavailable and Web Push not configured',
                'sent_at' => now()
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Firebase notification error: ' . $e->getMessage());

            // Log the failed notification
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
            // For now, return false as we need service account setup
            // This is a placeholder for future implementation
            Log::info('FCM HTTP v1 API not yet implemented');
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
