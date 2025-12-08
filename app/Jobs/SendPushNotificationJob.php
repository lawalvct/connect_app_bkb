<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected $userId;
    protected $title;
    protected $body;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $title, $body, $data = [])
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService)
    {
        $user = User::find($this->userId);

        if (!$user) {
            Log::warning("SendPushNotificationJob: User {$this->userId} not found");
            return;
        }

        $tokens = $user->fcmTokens()->where('is_active', true)->pluck('fcm_token');

        if ($tokens->isEmpty()) {
            Log::info("SendPushNotificationJob: No active tokens for user {$this->userId}");
            return;
        }

        foreach ($tokens as $token) {
            try {
                $result = $firebaseService->sendNotification(
                    $token,
                    $this->title,
                    $this->body,
                    $this->data,
                    $this->userId
                );

                if (!$result) {
                    Log::warning("SendPushNotificationJob: Failed to send to token for user {$this->userId}");
                }
            } catch (\Exception $e) {
                Log::error("SendPushNotificationJob Error: " . $e->getMessage(), [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("SendPushNotificationJob failed for user {$this->userId}: " . $exception->getMessage());
    }
}
