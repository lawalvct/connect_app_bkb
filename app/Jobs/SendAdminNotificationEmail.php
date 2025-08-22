<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAdminNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $subject;
    public $body;
    public $attachmentPath;
    public $attachmentName;
    public $attachmentMime;

    public function __construct($user, $subject, $body, $attachmentPath = null, $attachmentName = null, $attachmentMime = null)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachmentPath = $attachmentPath;
        $this->attachmentName = $attachmentName;
        $this->attachmentMime = $attachmentMime;
    }

    public function handle()
    {
        Mail::send([], [], function ($message) {
            $message->to($this->user->email)
                ->subject($this->subject)
                ->html($this->body);
            if ($this->attachmentPath) {
                $message->attach($this->attachmentPath, [
                    'as' => $this->attachmentName,
                    'mime' => $this->attachmentMime,
                ]);
            }
        });
    }
}
