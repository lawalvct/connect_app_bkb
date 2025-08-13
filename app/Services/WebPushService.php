<?php

namespace App\Services;

use App\Models\PushNotificationLog;
use Illuminate\Support\Facades\Log;

class WebPushService
{
    /** @var mixed|null */
    private $webPush;

    public function __construct()
    {
        $webPushClass = '\\Minishlink\\WebPush\\WebPush';
        if (class_exists($webPushClass)) {
            $vapid = [
                'VAPID' => [
                    'subject' => config('services.vapid.subject'),
                    'publicKey' => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ];

            $this->webPush = new $webPushClass($vapid);
            // Reasonable defaults
            $this->webPush->setAutomaticPadding(0);
        } else {
            $this->webPush = null;
            Log::warning('minishlink/web-push is not installed. Run: composer require "minishlink/web-push:^9.0"');
        }
    }

    /**
     * Send notification using Web Push API (VAPID)
     */
    public function sendNotification($endpoint, $p256dh, $auth, $title, $body, $data = [], $userId = null): bool
    {
        try {
            if (empty($endpoint) || empty($p256dh) || empty($auth)) {
                throw new \InvalidArgumentException('Missing Web Push subscription parameters');
            }

            $subscriptionClass = '\\Minishlink\\WebPush\\Subscription';
            if ($this->webPush === null || !class_exists($subscriptionClass)) {
                throw new \RuntimeException('Web Push library not available. Please install with: composer require "minishlink/web-push:^9.0"');
            }

            $subscription = \call_user_func([$subscriptionClass, 'create'], [
                'endpoint' => $endpoint,
                'publicKey' => $p256dh,
                'authToken' => $auth,
                'contentEncoding' => 'aes128gcm',
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => asset('favicon.ico'),
                'badge' => asset('favicon.ico'),
                'data' => $data,
                'requireInteraction' => false,
                'tag' => 'admin-notification-' . time(),
            ]);

            $report = $this->webPush->sendOneNotification($subscription, $payload, [
                'TTL' => 60,
            ]);

            $success = $report->isSuccess();
            $status = $report->getResponse() ? $report->getResponse()->getStatusCode() : null;
            $reason = $report->getReason();

            // Log the notification
            PushNotificationLog::create([
                'user_id' => $userId,
                'fcm_token' => substr($endpoint, 0, 160),
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => $success ? 'sent' : 'failed',
                'response' => json_encode(['status' => $status, 'reason' => $reason]),
                'error_message' => $success ? null : ($reason ?: 'Unknown Web Push error'),
                'sent_at' => now(),
            ]);

            if (!$success) {
                Log::warning('Web Push failed', ['status' => $status, 'reason' => $reason]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('Web Push notification error: ' . $e->getMessage());
            PushNotificationLog::create([
                'user_id' => $userId,
                'fcm_token' => substr($endpoint ?? 'unknown', 0, 160),
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);
            return false;
        }
    }
}
