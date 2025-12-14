<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Message;
use App\Models\UserNotification;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewMessageNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60];

    public $senderId;
    public $receiverId;
    public $messageId;
    public $conversationId;
    public $messagePreview;
    public $messageType;

    /**
     * Create a new job instance.
     */
    public function __construct($senderId, $receiverId, $messageId, $conversationId, $messagePreview, $messageType = 'text')
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
        $this->messagePreview = $messagePreview ?? 'Sent a message';
        $this->messageType = $messageType ?? 'text';
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService)
    {
        Log::channel('daily')->info("=== SendNewMessageNotificationJob START ===", [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId
        ]);

        try {
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
            $this->createInAppNotification($sender, $receiver);

            // 2. Send push notification
            $this->sendPushNotification($firebaseService, $receiver, $sender);

            // 3. Send email notification (optional - can be disabled by user)
            $this->sendEmailNotification($receiver, $sender);

            Log::channel('daily')->info("=== SendNewMessageNotificationJob COMPLETED ===", [
                'receiver_id' => $this->receiverId,
                'message_id' => $this->messageId
            ]);

        } catch (\Exception $e) {
            Log::channel('daily')->error("SendNewMessageNotificationJob Error: " . $e->getMessage(), [
                'sender_id' => $this->senderId,
                'receiver_id' => $this->receiverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Create in-app notification
     */
    protected function createInAppNotification($sender, $receiver)
    {
        try {
            Log::channel('daily')->info("Creating in-app notification for new message...");

            $notification = UserNotification::create([
                'title' => 'New Message! 💬',
                'message' => "{$sender->name} sent you a message: \"{$this->messagePreview}\"",
                'type' => 'new_message',
                'user_id' => $this->receiverId,
                'icon' => 'fa-comment',
                'priority' => 7,
                'action_url' => "/conversations/{$this->conversationId}",
                'data' => [
                    'action_type' => 'new_message',
                    'sender_id' => $this->senderId,
                    'sender_name' => $sender->name,
                    'message_id' => $this->messageId,
                    'conversation_id' => $this->conversationId,
                    'message_preview' => $this->messagePreview,
                    'message_type' => $this->messageType
                ]
            ]);

            Log::channel('daily')->info("In-app notification created", [
                'notification_id' => $notification->id ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            Log::channel('daily')->error("Failed to create in-app notification", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send push notification via Firebase
     */
    protected function sendPushNotification(FirebaseService $firebaseService, $receiver, $sender)
    {
        try {
            Log::channel('daily')->info("Checking FCM tokens for push notification...");

            $tokens = $receiver->fcmTokens()->where('is_active', true)->pluck('fcm_token');

            Log::channel('daily')->info("FCM tokens found", [
                'token_count' => $tokens->count()
            ]);

            if ($tokens->isEmpty()) {
                Log::channel('daily')->warning("No active FCM tokens for user", [
                    'receiver_id' => $this->receiverId
                ]);
                return;
            }

            $title = "💬 {$sender->name}";
            $body = $this->getMessageBody();

            $data = [
                'type' => 'new_message',
                'sender_id' => (string) $this->senderId,
                'sender_name' => $sender->name,
                'sender_profile' => $sender->profile_url ?? '',
                'message_id' => (string) $this->messageId,
                'conversation_id' => (string) $this->conversationId,
                'message_preview' => $this->messagePreview,
                'message_type' => $this->messageType,
                'action_url' => '/conversations/' . $this->conversationId,
                'click_action' => 'OPEN_CONVERSATION'
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
            Log::channel('daily')->info("Checking email notification settings...");

            // Check if user has email notifications enabled
            $notificationSettings = $receiver->notification_settings ?? [];
            $emailEnabled = $notificationSettings['email_notifications'] ?? true;

            if (!$emailEnabled) {
                Log::channel('daily')->info("Email notifications disabled for user");
                return;
            }

            if (empty($receiver->email)) {
                Log::channel('daily')->warning("No email for user");
                return;
            }

            Log::channel('daily')->info("Sending email notification...", [
                'to' => $receiver->email,
                'from_sender' => $sender->name
            ]);

            // Send email
            Mail::send('emails.new-message', [
                'receiver' => $receiver,
                'sender' => $sender,
                'messagePreview' => $this->messagePreview,
                'messageType' => $this->messageType,
                'conversationId' => $this->conversationId,
                'appUrl' => config('app.frontend_url', env('FRONTEND_URL', 'https://www.connectinc.app'))
            ], function ($message) use ($receiver, $sender) {
                $message->to($receiver->email, $receiver->name)
                        ->subject("💬 New message from {$sender->name}");
            });

            Log::channel('daily')->info("=== EMAIL SENT SUCCESSFULLY ===", [
                'receiver_email' => $receiver->email
            ]);

        } catch (\Exception $e) {
            Log::channel('daily')->error("Email sending failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get message body for push notification
     */
    protected function getMessageBody()
    {
        switch ($this->messageType) {
            case 'image':
                return "📷 Sent a photo";
            case 'video':
                return "🎥 Sent a video";
            case 'audio':
                return "🎵 Sent an audio";
            case 'file':
                return "📎 Sent a file";
            case 'location':
                return "📍 Shared a location";
            default:
                return $this->messagePreview;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::channel('daily')->error("SendNewMessageNotificationJob failed permanently", [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'message_id' => $this->messageId,
            'error' => $exception->getMessage()
        ]);
    }
}
