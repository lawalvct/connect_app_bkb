<?php

namespace App\Services;

use App\Models\PushNotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoNotificationService
{
    private $expoApiUrl = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send notification to an Expo push token
     *
     * @param string $expoPushToken The Expo push token (e.g., ExponentPushToken[xxx])
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param int|null $userId User ID for logging
     * @return bool Success status
     */
    public function sendNotification($expoPushToken, $title, $body, $data = [], $userId = null)
    {
        try {
            // Validate Expo push token format
            if (!$this->isValidExpoPushToken($expoPushToken)) {
                Log::warning('Invalid Expo push token format', ['token' => $expoPushToken]);
                PushNotificationLog::create([
                    'user_id' => $userId,
                    'fcm_token' => $expoPushToken,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'status' => 'failed',
                    'error_message' => 'Invalid Expo push token format',
                    'sent_at' => now()
                ]);
                return false;
            }

            // Prepare the notification payload for Expo
            $payload = [
                'to' => $expoPushToken,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'priority' => 'high',
                'channelId' => 'default',
            ];

            // Send notification to Expo's push notification service
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->post($this->expoApiUrl, $payload);

            $result = $response->json();

            // Check if the notification was sent successfully
            if ($response->successful() && isset($result['data'])) {
                $ticketData = $result['data'][0] ?? null;

                if ($ticketData && isset($ticketData['status']) && $ticketData['status'] === 'ok') {
                    Log::info('Expo notification sent successfully', [
                        'token' => substr($expoPushToken, 0, 30) . '...',
                        'ticket_id' => $ticketData['id'] ?? null
                    ]);

                    PushNotificationLog::create([
                        'user_id' => $userId,
                        'fcm_token' => $expoPushToken,
                        'title' => $title,
                        'body' => $body,
                        'data' => $data,
                        'status' => 'sent',
                        'response' => json_encode([
                            'success' => true,
                            'method' => 'expo',
                            'ticket_id' => $ticketData['id'] ?? null
                        ]),
                        'error_message' => null,
                        'sent_at' => now()
                    ]);
                    return true;
                } else {
                    // Expo returned an error
                    $errorMessage = $ticketData['message'] ?? 'Unknown Expo error';
                    $errorDetails = $ticketData['details'] ?? null;

                    Log::warning('Expo notification failed', [
                        'error' => $errorMessage,
                        'details' => $errorDetails
                    ]);

                    PushNotificationLog::create([
                        'user_id' => $userId,
                        'fcm_token' => $expoPushToken,
                        'title' => $title,
                        'body' => $body,
                        'data' => $data,
                        'status' => 'failed',
                        'response' => json_encode($result),
                        'error_message' => $errorMessage,
                        'sent_at' => now()
                    ]);
                    return false;
                }
            } else {
                Log::error('Expo API request failed', [
                    'status' => $response->status(),
                    'response' => $result
                ]);

                PushNotificationLog::create([
                    'user_id' => $userId,
                    'fcm_token' => $expoPushToken,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'status' => 'failed',
                    'response' => json_encode($result),
                    'error_message' => 'Expo API request failed with status ' . $response->status(),
                    'sent_at' => now()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Expo notification error: ' . $e->getMessage(), [
                'exception' => $e,
                'token' => substr($expoPushToken, 0, 30) . '...'
            ]);

            PushNotificationLog::create([
                'user_id' => $userId,
                'fcm_token' => $expoPushToken,
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
     * Validate Expo push token format
     *
     * @param string $token
     * @return bool
     */
    private function isValidExpoPushToken($token)
    {
        // Expo push tokens follow the format: ExponentPushToken[xxxxxx]
        // or can be in the new format: ExpoPushToken[xxxxxx]
        return preg_match('/^Exponent(ial)?PushToken\[.+\]$/', $token) === 1;
    }

    /**
     * Check if a token is an Expo push token
     *
     * @param string $token
     * @return bool
     */
    public static function isExpoPushToken($token)
    {
        return preg_match('/^Exponent(ial)?PushToken\[.+\]$/', $token) === 1;
    }

    /**
     * Send notifications to multiple Expo tokens
     *
     * @param array $tokens Array of Expo push tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @param int|null $userId
     * @return array ['success' => int, 'failed' => int]
     */
    public function sendBulkNotifications($tokens, $title, $body, $data = [], $userId = null)
    {
        $results = ['success' => 0, 'failed' => 0];

        foreach ($tokens as $token) {
            $sent = $this->sendNotification($token, $title, $body, $data, $userId);
            if ($sent) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }
}
