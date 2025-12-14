<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserProfileUpload;
use App\Models\UserNotification;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendProfileUploadLikeNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 60];

    public $likerId;
    public $uploadOwnerId;
    public $uploadId;

    /**
     * Create a new job instance.
     */
    public function __construct($likerId, $uploadOwnerId, $uploadId)
    {
        $this->likerId = $likerId;
        $this->uploadOwnerId = $uploadOwnerId;
        $this->uploadId = $uploadId;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService)
    {
        Log::channel('daily')->info("=== SendProfileUploadLikeNotificationJob START ===", [
            'liker_id' => $this->likerId,
            'upload_owner_id' => $this->uploadOwnerId,
            'upload_id' => $this->uploadId
        ]);

        try {
            $liker = User::find($this->likerId);
            $uploadOwner = User::find($this->uploadOwnerId);
            $upload = UserProfileUpload::find($this->uploadId);

            Log::channel('daily')->info("Resources found", [
                'liker_exists' => !is_null($liker),
                'liker_name' => $liker->name ?? 'N/A',
                'owner_exists' => !is_null($uploadOwner),
                'owner_name' => $uploadOwner->name ?? 'N/A',
                'upload_exists' => !is_null($upload),
                'upload_type' => $upload->file_type ?? 'N/A'
            ]);

            if (!$liker || !$uploadOwner || !$upload) {
                Log::channel('daily')->error("Resource not found - aborting job", [
                    'liker_exists' => !is_null($liker),
                    'owner_exists' => !is_null($uploadOwner),
                    'upload_exists' => !is_null($upload)
                ]);
                return;
            }

            // Don't notify if user liked their own upload
            if ($this->likerId === $this->uploadOwnerId) {
                Log::channel('daily')->info("User liked own upload - skipping notification");
                return;
            }

            // 1. Create in-app notification
            $this->createInAppNotification($liker, $uploadOwner, $upload);

            // 2. Send push notification
            $this->sendPushNotification($firebaseService, $uploadOwner, $liker, $upload);

            Log::channel('daily')->info("=== SendProfileUploadLikeNotificationJob COMPLETED ===", [
                'upload_owner_id' => $this->uploadOwnerId,
                'upload_id' => $this->uploadId
            ]);

        } catch (\Exception $e) {
            Log::channel('daily')->error("SendProfileUploadLikeNotificationJob Error: " . $e->getMessage(), [
                'liker_id' => $this->likerId,
                'upload_owner_id' => $this->uploadOwnerId,
                'upload_id' => $this->uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Create in-app notification
     */
    protected function createInAppNotification($liker, $uploadOwner, $upload)
    {
        try {
            Log::channel('daily')->info("Creating in-app notification for profile upload like...");

            $fileTypeEmoji = $upload->file_type === 'video' ? '🎥' : '📷';
            $fileTypeText = $upload->file_type === 'video' ? 'video' : 'photo';

            $notification = UserNotification::create([
                'title' => "New Like! {$fileTypeEmoji}",
                'message' => "{$liker->name} liked your {$fileTypeText}",
                'type' => 'profile_upload_like',
                'user_id' => $this->uploadOwnerId,
                'icon' => 'fa-heart',
                'priority' => 5,
                'action_url' => "/profile/{$this->uploadOwnerId}/uploads/{$this->uploadId}",
                'data' => [
                    'action_type' => 'profile_upload_like',
                    'liker_id' => $this->likerId,
                    'liker_name' => $liker->name,
                    'liker_username' => $liker->username,
                    'liker_profile' => $liker->profile_image,
                    'upload_id' => $this->uploadId,
                    'upload_type' => $upload->file_type,
                    'upload_url' => $upload->file_url,
                    'total_likes' => $upload->like_count
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
    protected function sendPushNotification(FirebaseService $firebaseService, $uploadOwner, $liker, $upload)
    {
        try {
            Log::channel('daily')->info("Checking FCM tokens for push notification...");

            $tokens = $uploadOwner->fcmTokens()->where('is_active', true)->pluck('fcm_token');

            Log::channel('daily')->info("FCM tokens found", [
                'receiver_id' => $this->uploadOwnerId,
                'token_count' => $tokens->count(),
                'tokens' => $tokens->toArray()
            ]);

            if ($tokens->isEmpty()) {
                Log::channel('daily')->warning("No active FCM tokens found for upload owner", [
                    'upload_owner_id' => $this->uploadOwnerId
                ]);
                return;
            }

            $fileTypeEmoji = $upload->file_type === 'video' ? '🎥' : '📷';
            $fileTypeText = $upload->file_type === 'video' ? 'video' : 'photo';

            $title = "New Like! {$fileTypeEmoji}";
            $body = "{$liker->name} liked your {$fileTypeText}";

            // FCM v1 API requires camelCase keys (NOT snake_case)
            $data = [
                'type' => 'profile_upload_like',
                'likerId' => (string) $this->likerId,
                'likerName' => $liker->name,
                'likerUsername' => $liker->username ?? '',
                'likerProfile' => $liker->profile_image ?? '',
                'uploadId' => (string) $this->uploadId,
                'uploadType' => $upload->file_type,
                'uploadUrl' => $upload->file_url ?? '',
                'totalLikes' => (string) $upload->like_count,
                'actionUrl' => "/profile/{$this->uploadOwnerId}/uploads/{$this->uploadId}",
                'clickAction' => 'FLUTTER_NOTIFICATION_CLICK',
                'priority' => 'normal'
            ];

            foreach ($tokens as $token) {
                try {
                    Log::channel('daily')->info("Sending push notification via FCM", [
                        'token' => substr($token, 0, 20) . '...',
                        'title' => $title
                    ]);

                    $result = $firebaseService->sendNotification(
                        $token,
                        $title,
                        $body,
                        $data
                    );

                    Log::channel('daily')->info("Push notification sent successfully", [
                        'token' => substr($token, 0, 20) . '...',
                        'result' => $result
                    ]);
                } catch (\Exception $e) {
                    Log::channel('daily')->error("Failed to send push notification to specific token", [
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::channel('daily')->error("Failed to send push notifications", [
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
        Log::channel('daily')->error("SendProfileUploadLikeNotificationJob failed permanently", [
            'liker_id' => $this->likerId,
            'upload_owner_id' => $this->uploadOwnerId,
            'upload_id' => $this->uploadId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
