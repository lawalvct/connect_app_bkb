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

    protected $senderId;
    protected $receiverId;
    protected $senderName;
    protected $requestId;

    /**
     * Create a new job instance.
     */
    public function __construct($senderId, $receiverId, $senderName, $requestId)
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->senderName = $senderName;
        $this->requestId = $requestId;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService)
    {
        try {
            Log::info("SendConnectionRequestNotificationJob: Processing", [
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'request_id' => $this->requestId
            ]);

            $sender = User::find($this->senderId);
            $receiver = User::find($this->receiverId);

            if (!$sender || !$receiver) {
                Log::warning("SendConnectionRequestNotificationJob: User not found", [
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

            Log::info("SendConnectionRequestNotificationJob: Completed successfully", [
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
            UserNotification::createConnectionRequestNotification(
                $this->senderId,
                $this->receiverId,
                $this->senderName,
                $this->requestId
            );

            Log::info("SendConnectionRequestNotificationJob: In-app notification created", [
                'receiver_id' => $this->receiverId
            ]);
        } catch (\Exception $e) {
            Log::error("SendConnectionRequestNotificationJob: Failed to create in-app notification", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send push notification via Firebase
     */
    protected function sendPushNotification(FirebaseService $firebaseService, $receiver, $sender)
    {
        try {
            $tokens = $receiver->fcmTokens()->where('is_active', true)->pluck('fcm_token');

            if ($tokens->isEmpty()) {
                Log::info("SendConnectionRequestNotificationJob: No active FCM tokens for user", [
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
                    $result = $firebaseService->sendNotification(
                        $token,
                        $title,
                        $body,
                        $data,
                        $this->receiverId
                    );

                    if ($result) {
                        Log::info("SendConnectionRequestNotificationJob: Push sent successfully", [
                            'receiver_id' => $this->receiverId
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("SendConnectionRequestNotificationJob: Push failed for token", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("SendConnectionRequestNotificationJob: Push notification error", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification($receiver, $sender)
    {
        try {
            // Check if user has email notifications enabled
            $notificationSettings = $receiver->notification_settings ?? [];
            $emailEnabled = $notificationSettings['email_notifications'] ?? true;

            if (!$emailEnabled) {
                Log::info("SendConnectionRequestNotificationJob: Email notifications disabled for user", [
                    'receiver_id' => $this->receiverId
                ]);
                return;
            }

            if (empty($receiver->email)) {
                Log::info("SendConnectionRequestNotificationJob: No email for user", [
                    'receiver_id' => $this->receiverId
                ]);
                return;
            }

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

            Log::info("SendConnectionRequestNotificationJob: Email sent successfully", [
                'receiver_id' => $this->receiverId,
                'receiver_email' => $receiver->email
            ]);

        } catch (\Exception $e) {
            Log::error("SendConnectionRequestNotificationJob: Email sending failed", [
                'receiver_id' => $this->receiverId,
                'error' => $e->getMessage()
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
