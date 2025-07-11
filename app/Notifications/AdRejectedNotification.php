<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdRejectedNotification extends Notification
{
    use Queueable;

    private $ad;

    public function __construct(Ad $ad)
    {
        $this->ad = $ad;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Advertisement Requires Revision')
            ->line('Your advertisement "' . $this->ad->ad_name . '" requires some revisions before it can be approved.')
            ->line('Reason: ' . $this->ad->admin_comments)
            ->action('Edit Advertisement', url('/ads/' . $this->ad->id . '/edit'))
            ->line('Please make the necessary changes and resubmit for review.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'ad_rejected',
            'ad_id' => $this->ad->id,
            'ad_name' => $this->ad->ad_name,
            'reason' => $this->ad->admin_comments,
            'message' => 'Your advertisement "' . $this->ad->ad_name . '" requires revision.'
        ];
    }
}
