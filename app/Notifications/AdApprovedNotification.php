<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdApprovedNotification extends Notification
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
            ->subject('Advertisement Approved!')
            ->greeting('Great news!')
            ->line('Your advertisement "' . $this->ad->ad_name . '" has been approved and is now live.')
            ->line('Campaign will run from ' . $this->ad->start_date->format('M d, Y') . ' to ' . $this->ad->end_date->format('M d, Y'))
            ->action('View Campaign', url('/ads/' . $this->ad->id))
            ->line('Thank you for advertising with us!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'ad_approved',
            'ad_id' => $this->ad->id,
            'ad_name' => $this->ad->ad_name,
            'message' => 'Your advertisement "' . $this->ad->ad_name . '" has been approved!'
        ];
    }
}
