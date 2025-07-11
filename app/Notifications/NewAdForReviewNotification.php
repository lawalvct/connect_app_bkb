<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAdForReviewNotification extends Notification
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
            ->subject('New Advertisement Pending Review')
            ->line('A new advertisement has been submitted for review.')
            ->line('Ad Name: ' . $this->ad->ad_name)
            ->line('Advertiser: ' . $this->ad->user->name)
            ->line('Type: ' . ucfirst($this->ad->type))
            ->action('Review Advertisement', url('/admin/ads/' . $this->ad->id))
            ->line('Please review and take appropriate action.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'ad_review',
            'ad_id' => $this->ad->id,
            'ad_name' => $this->ad->ad_name,
            'advertiser_name' => $this->ad->user->name,
            'message' => 'New advertisement pending review: ' . $this->ad->ad_name
        ];
    }
}
