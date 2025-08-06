<?php

namespace App\Services;

use App\Models\PushNotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private $serverKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

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
            ])->post($this->fcmUrl, $payload);

            $result = $response->json();
            $success = $response->successful() && isset($result['success']) && $result['success'] > 0;

            // Log the notification
            PushNotificationLog::create([
                'user_id' => $userId,
                'fcm_token' => $fcmToken,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => $success ? 'sent' : 'failed',
                'response' => json_encode($result),
                'error_message' => $success ? null : ($result['results'][0]['error'] ?? 'Unknown error'),
                'sent_at' => now()
            ]);

            return $success;

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
