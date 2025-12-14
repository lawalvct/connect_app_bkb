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

class SendCallNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [5, 10, 15]; // Shorter backoff for urgent call notifications

    public $callerId;
    public $receiverId;
    public $callId;
    public $conversationId;
    public $callType;

    /**
     * Create a new job instance.
     */
    public function __construct($callerId, $receiverId, $callId, $conversationId, $callType)
    {
        $this->callerId = $callerId;
        $this->receiverId = $receiverId;
        $this->callId = $callId;
        $this->conversationId = $conversationId;
        $this->callType = $callType;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService)
    {
        Log::channel('daily')->info("=== SendCallNotificationJob START ===", [
            'caller_id' => $this->callerId,
            'receiver_id' => $this->receiverId,
            'call_id' => $this->callId,
            'call_type' => $this->callType
        ]);

        try {
            $caller = User::find($this->callerId);
            $receiver = User::find($this->receiverId);

            Log::channel('daily')->info("Users found", [
                'caller_exists' => !is_null($caller),
                'caller_name' => $caller->name ?? 'N/A',
                'receiver_exists' => !is_null($receiver),
                'receiver_email' => $receiver->email ?? 'N/A'
            ]);

            if (!$caller || !$receiver) {
                Log::channel('daily')->error("User not found - aborting job", [
                    'caller_exists' => !is_null($caller),
                    'receiver_exists' => !is_null($receiver)
                ]);
                return;
            }

            // 1. Create in-app notification
            $this->createInAppNotification($caller, $receiver);

            // 2. Send push notification (HIGH PRIORITY for calls)
            $this->sendPushNotification($firebaseService, $receiver, $caller);

            Log::channel('daily')->info("=== SendCallNotificationJob COMPLETED ===", [
                'receiver_id' => $this->receiverId,
                'call_id' => $this->callId
            ]);

        } catch (\Exception $e) {
            Log::channel('daily')->error("SendCallNotificationJob Error: " . $e->getMessage(), [
                'caller_id' => $this->callerId,
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
    protected function createInAppNotification($caller, $receiver)
    {
        try {
            Log::channel('daily')->info("Creating in-app notification for incoming call...");

            $callTypeLabel = $this->callType === 'video' ? 'Video Call' : 'Audio Call';
            $icon = $this->callType === 'video' ? '📹' : '📞';

            $notification = UserNotification::create([
                'title' => "Incoming {$callTypeLabel}! {$icon}",
                'message' => "{$caller->name} is calling you. Tap to answer!",
                'type' => 'incoming_call',
                'user_id' => $this->receiverId,
                'icon' => $this->callType === 'video' ? 'fa-video' : 'fa-phone',
                'priority' => 10, // Highest priority
                'action_url' => "/calls/{$this->callId}",
                'data' => [
                    'action_type' => 'incoming_call',
                    'caller_id' => $this->callerId,
                    'caller_name' => $caller->name,
                    'call_id' => $this->callId,
                    'conversation_id' => $this->conversationId,
                    'call_type' => $this->callType
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
     * Send push notification via Firebase (HIGH PRIORITY for calls)
     */
    protected function sendPushNotification(FirebaseService $firebaseService, $receiver, $caller)
    {
        try {
            Log::channel('daily')->info("Checking FCM tokens for call push notification...");

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

            $callTypeEmoji = $this->callType === 'video' ? '📹' : '📞';
            $callTypeLabel = $this->callType === 'video' ? 'Video Call' : 'Audio Call';

            $title = "{$callTypeEmoji} Incoming {$callTypeLabel}";
            $body = "{$caller->name} is calling you...";

            $data = [
                'type' => 'incoming_call',
                'callerId' => (string) $this->callerId,
                'callerName' => $caller->name,
                'callerProfile' => $caller->profile_url ?? '',
                'callId' => (string) $this->callId,
                'conversationId' => (string) $this->conversationId,
                'callType' => $this->callType,
                'actionUrl' => '/calls/' . $this->callId,
                'clickAction' => 'INCOMING_CALL',
                'priority' => 'high',
                'urgent' => 'true'
            ];

            foreach ($tokens as $token) {
                try {
                    Log::channel('daily')->info("Sending HIGH PRIORITY call push to token...", [
                        'token_preview' => substr($token, 0, 20) . '...'
                    ]);

                    $result = $firebaseService->sendNotification(
                        $token,
                        $title,
                        $body,
                        $data,
                        $this->receiverId
                    );

                    Log::channel('daily')->info("Call push notification result", [
                        'success' => $result ? 'yes' : 'no'
                    ]);
                } catch (\Exception $e) {
                    Log::channel('daily')->warning("Call push failed for token", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::channel('daily')->error("Call push notification error", [
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
        Log::channel('daily')->error("SendCallNotificationJob failed permanently", [
            'caller_id' => $this->callerId,
            'receiver_id' => $this->receiverId,
            'call_id' => $this->callId,
            'error' => $exception->getMessage()
        ]);
    }
}
