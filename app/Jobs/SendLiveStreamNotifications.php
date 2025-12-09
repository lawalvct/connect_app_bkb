<?php

namespace App\Jobs;

use App\Mail\NewLiveStreamNotification;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLiveStreamNotifications implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $stream;
    public $tries = 3;
    public $timeout = 300; // 5 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting live stream email notifications', [
                'stream_id' => $this->stream->id,
                'stream_title' => $this->stream->title,
                'status' => $this->stream->status
            ]);

            $emailsSent = 0;
            $emailsFailed = 0;

            // Query active users with verified emails
            // Process in chunks of 100 users at a time to avoid memory issues
            User::where('is_active', true)
                ->where('deleted_flag', 'N')
                ->where('is_banned', false)
                ->whereNotNull('email')
                ->whereNotNull('email_verified_at')
                ->where('notification_email', true) // Only send to users who enabled email notifications
                ->chunk(100, function ($users) use (&$emailsSent, &$emailsFailed) {
                    foreach ($users as $user) {
                        try {
                            // Send email notification
                            Mail::to($user->email)->send(new NewLiveStreamNotification($this->stream));
                            $emailsSent++;

                            Log::debug('Stream notification sent', [
                                'user_id' => $user->id,
                                'email' => $user->email,
                                'stream_id' => $this->stream->id
                            ]);

                        } catch (\Exception $e) {
                            $emailsFailed++;
                            Log::error('Failed to send stream notification to user', [
                                'user_id' => $user->id,
                                'email' => $user->email,
                                'stream_id' => $this->stream->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    // Small delay between chunks to avoid overwhelming mail server
                    usleep(100000); // 100ms delay
                });

            Log::info('Completed live stream email notifications', [
                'stream_id' => $this->stream->id,
                'emails_sent' => $emailsSent,
                'emails_failed' => $emailsFailed
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process live stream notifications job', [
                'stream_id' => $this->stream->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Live stream notifications job failed permanently', [
            'stream_id' => $this->stream->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
