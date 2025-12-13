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

class SendConnectionAcceptedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60]; // Retry after 10s, 30s, 60s

    protected $accepterId;
    protected $senderId;
    protected $accepterName;
    protected $requestId;

    /**
     * Create a new job instance.
     */
    public function __construct($accepterId, $senderId, $accepterName, $requestId)
    {
        $this->accepterId = $accepterId;
        $this->senderId = $senderId;
        $this->accepterName = $accepterName;
        $this->requestId = $requestId;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService)
    {
        try {
            Log::info("SendConnectionAcceptedNotificationJob: Processing", [
                'accepter_id' => $this->accepterId,
                'sender_id' => $this->senderId,
                'request_id' => $this->requestId
            ]);

            $accepter = User::find($this->accepterId);
            $sender = User::find($this->senderId);

            if (!$accepter || !$sender) {
                Log::warning("SendConnectionAcceptedNotificationJob: User not found", [
                    'accepter_exists' => !is_null($accepter),
                    'sender_exists' => !is_null($sender)
                ]);
                return;
            }

            // 1. Create in-app notification
            $this->createInAppNotification($accepter);

            // 2. Send push notification to original sender
            $this->sendPushNotification($firebaseService, $sender, $accepter);

            // 3. Send email notification to original sender
            $this->sendEmailNotification($sender, $accepter);

            Log::info("SendConnectionAcceptedNotificationJob: Completed successfully", [
                'sender_id' => $this->senderId,
                'request_id' => $this->requestId
            ]);

        } catch (\Exception $e) {
            Log::error("SendConnectionAcceptedNotificationJob Error: " . $e->getMessage(), [
                'accepter_id' => $this->accepterId,
                'sender_id' => $this->senderId,
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
    protected function createInAppNotification($accepter)
    {
        try {
            UserNotification::createConnectionAcceptedNotification(
                $this->accepterId,
                $this->senderId,
                $this->accepterName,
                $this->requestId
            );

            Log::info("SendConnectionAcceptedNotificationJob: In-app notification created", [
                'sender_id' => $this->senderId
            ]);
        } catch (\Exception $e) {
            Log::error("SendConnectionAcceptedNotificationJob: Failed to create in-app notification", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send push notification via Firebase
     */
    protected function sendPushNotification(FirebaseService $firebaseService, $sender, $accepter)
    {
        try {
            $tokens = $sender->fcmTokens()->where('is_active', true)->pluck('fcm_token');

            if ($tokens->isEmpty()) {
                Log::info("SendConnectionAcceptedNotificationJob: No active FCM tokens for user", [
                    'sender_id' => $this->senderId
                ]);
                return;
            }

            $title = "Connection Accepted! 🎉";
            $body = "{$accepter->name} accepted your connection request";
            $data = [
                'type' => 'connection_accepted',
                'accepter_id' => (string) $this->accepterId,
                'accepter_name' => $accepter->name,
                'accepter_profile' => $accepter->profile_url ?? '',
                'request_id' => (string) $this->requestId,
                'action_url' => '/conversations',
                'click_action' => 'CONNECTION_ACCEPTED'
            ];

            foreach ($tokens as $token) {
                try {
                    $result = $firebaseService->sendNotification(
                        $token,
                        $title,
                        $body,
                        $data,
                        $this->senderId
                    );

                    if ($result) {
                        Log::info("SendConnectionAcceptedNotificationJob: Push sent successfully", [
                            'sender_id' => $this->senderId
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("SendConnectionAcceptedNotificationJob: Push failed for token", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("SendConnectionAcceptedNotificationJob: Push notification error", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification($sender, $accepter)
    {
        try {
            // Check if user has email notifications enabled
            $notificationSettings = $sender->notification_settings ?? [];
            $emailEnabled = $notificationSettings['email_notifications'] ?? true;

            if (!$emailEnabled) {
                Log::info("SendConnectionAcceptedNotificationJob: Email notifications disabled for user", [
                    'sender_id' => $this->senderId
                ]);
                return;
            }

            if (empty($sender->email)) {
                Log::info("SendConnectionAcceptedNotificationJob: No email for user", [
                    'sender_id' => $this->senderId
                ]);
                return;
            }

            // Send email
            Mail::send('emails.connection-accepted', [
                'sender' => $sender,
                'accepter' => $accepter,
                'requestId' => $this->requestId,
                'appUrl' => config('app.frontend_url', env('FRONTEND_URL', 'https://www.connectinc.app'))
            ], function ($message) use ($sender, $accepter) {
                $message->to($sender->email, $sender->name)
                        ->subject("🎉 {$accepter->name} accepted your connection request!");
            });

            Log::info("SendConnectionAcceptedNotificationJob: Email sent successfully", [
                'sender_id' => $this->senderId,
                'sender_email' => $sender->email
            ]);

        } catch (\Exception $e) {
            Log::error("SendConnectionAcceptedNotificationJob: Email sending failed", [
                'sender_id' => $this->senderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("SendConnectionAcceptedNotificationJob failed permanently", [
            'accepter_id' => $this->accepterId,
            'sender_id' => $this->senderId,
            'request_id' => $this->requestId,
            'error' => $exception->getMessage()
        ]);
    }
}
