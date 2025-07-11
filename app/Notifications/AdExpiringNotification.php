<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdExpiringNotification extends Notification
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
        $daysRemaining = $this->ad->days_remaining;

        return (new MailMessage)
            ->subject('Advertisement Expiring Soon')
            ->line('Your advertisement "' . $this->ad->ad_name . '" will expire in ' . $daysRemaining . ' day(s).')
            ->line('End Date: ' . $this->ad->end_date->format('M d, Y'))
            ->line('To extend your campaign, please create a new advertisement or contact support.')
            ->action('View Advertisement', url('/ads/' . $this->ad->id))
            ->line('Thank you for advertising with us!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'ad_expiring',
            'ad_id' => $this->ad->id,
            'ad_name' => $this->ad->ad_name,
            'days_remaining' => $this->ad->days_remaining,
            'message' => 'Your advertisement "' . $this->ad->ad_name . '" expires in ' . $this->ad->days_remaining . ' day(s).'
        ];
    }
}
