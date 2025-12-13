<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendConnectionRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60]; // Retry after 10s, 30s, 60s

    public $senderId;
    public $receiverId;
    public $senderName;
    public $requestId;

    /**
     * Create a new job instance.
     */
    public function __construct($senderId, $receiverId, $senderName, $requestId)
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->senderName = $senderName ?? 'Someone';
        $this->requestId = $requestId;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService)
    {
        Log::channel('daily')->info("=== SendConnectionRequestNotificationJob START ===", [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'sender_name' => $this->senderName,
            'request_id' => $this->requestId
        ]);

        try {
            Log::info("SendConnectionRequestNotificationJob: Processing", [
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'request_id' => $this->requestId
            ]);

            $sender = User::find($this->senderId);
            $receiver = User::find($this->receiverId);

            Log::channel('daily')->info("Users found", [
                'sender_exists' => !is_null($sender),
                'sender_name' => $sender->name ?? 'N/A',
                'receiver_exists' => !is_null($receiver),
                'receiver_email' => $receiver->email ?? 'N/A'
            ]);

            if (!$sender || !$receiver) {
                Log::channel('daily')->error("User not found - aborting job", [
                    'sender_exists' => !is_null($sender),
                    'receiver_exists' => !is_null($receiver)
                ]);
                return;
            }

            // 1. Create in-app notification
            $this->createInAppNotification($sender);

            // 2. Send push notification
            $this->sendPushNotification($firebaseService, $receiver, $sender);

            // 3. Send email notification
            $this->sendEmailNotification($receiver, $sender);

            Log::channel('daily')->info("=== SendConnectionRequestNotificationJob COMPLETED ===", [
                'receiver_id' => $this->receiverId,
                'request_id' => $this->requestId
            ]);

        } catch (\Exception $e) {
            Log::error("SendConnectionRequestNotificationJob Error: " . $e->getMessage(), [
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'request_id' => $this->requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Create in-app notification
     */
    protected function createInAppNotification($sender)
    {
        try {
            Log::channel('daily')->info("Creating in-app notification...", [
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'sender_name' => $this->senderName
            ]);

            $notification = UserNotification::createConnectionRequestNotification(
                $this->senderId,
                $this->receiverId,
                $this->senderName,
                $this->requestId
            );

            Log::channel('daily')->info("In-app notification created", [
                'notification_id' => $notification->id ?? 'unknown',
                'receiver_id' => $this->receiverId
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error("Failed to create in-app notification", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - continue with push and email
        }
    }

    /**
     * Send push notification via Firebase
     */
    protected function sendPushNotification(FirebaseService $firebaseService, $receiver, $sender)
    {
        try {
            Log::channel('daily')->info("Checking FCM tokens for push notification...", [
                'receiver_id' => $this->receiverId
            ]);

            $tokens = $receiver->fcmTokens()->where('is_active', true)->pluck('fcm_token');

            Log::channel('daily')->info("FCM tokens found", [
                'receiver_id' => $this->receiverId,
                'token_count' => $tokens->count()
            ]);

            if ($tokens->isEmpty()) {
                Log::channel('daily')->warning("No active FCM tokens for user", [
                    'receiver_id' => $this->receiverId
                ]);
                return;
            }

            $title = "New Connection Request! 💫";
            $body = "{$sender->name} wants to connect with you";
            $data = [
                'type' => 'connection_request',
                'sender_id' => (string) $this->senderId,
                'sender_name' => $sender->name,
                'sender_profile' => $sender->profile_url ?? '',
                'request_id' => (string) $this->requestId,
                'action_url' => '/connections/requests',
                'click_action' => 'CONNECTION_REQUEST'
            ];

            foreach ($tokens as $token) {
                try {
                    Log::channel('daily')->info("Sending push to token...", [
                        'token_preview' => substr($token, 0, 20) . '...'
                    ]);

                    $result = $firebaseService->sendNotification(
                        $token,
                        $title,
                        $body,
                        $data,
                        $this->receiverId
                    );

                    Log::channel('daily')->info("Push notification result", [
                        'receiver_id' => $this->receiverId,
                        'success' => $result ? 'yes' : 'no'
                    ]);
                } catch (\Exception $e) {
                    Log::channel('daily')->warning("Push failed for token", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::channel('daily')->error("Push notification error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification($receiver, $sender)
    {
        try {
            Log::channel('daily')->info("Checking email notification settings...", [
                'receiver_id' => $this->receiverId,
                'receiver_email' => $receiver->email ?? 'none'
            ]);

            // Check if user has email notifications enabled
            $notificationSettings = $receiver->notification_settings ?? [];
            $emailEnabled = $notificationSettings['email_notifications'] ?? true;

            if (!$emailEnabled) {
                Log::channel('daily')->info("Email notifications disabled for user", [
                    'receiver_id' => $this->receiverId
                ]);
                return;
            }

            if (empty($receiver->email)) {
                Log::channel('daily')->warning("No email for user", [
                    'receiver_id' => $this->receiverId
                ]);
                return;
            }

            Log::channel('daily')->info("Sending email notification...", [
                'to' => $receiver->email,
                'from_sender' => $sender->name
            ]);

            // Send email
            Mail::send('emails.connection-request', [
                'receiver' => $receiver,
                'sender' => $sender,
                'requestId' => $this->requestId,
                'appUrl' => config('app.frontend_url', env('FRONTEND_URL', 'https://www.connectinc.app'))
            ], function ($message) use ($receiver, $sender) {
                $message->to($receiver->email, $receiver->name)
                        ->subject("✨ {$sender->name} wants to connect with you on Connect!");
            });

            Log::channel('daily')->info("=== EMAIL SENT SUCCESSFULLY ===", [
                'receiver_id' => $this->receiverId,
                'receiver_email' => $receiver->email
            ]);

        } catch (\Exception $e) {
            Log::channel('daily')->error("Email sending failed", [
                'receiver_id' => $this->receiverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("SendConnectionRequestNotificationJob failed permanently", [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'request_id' => $this->requestId,
            'error' => $exception->getMessage()
        ]);
    }
}
