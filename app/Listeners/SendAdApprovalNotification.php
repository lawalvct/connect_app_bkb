<?php

namespace App\Listeners;

use App\Events\AdApprovedEvent;
use App\Notifications\AdApprovedNotification;

class SendAdApprovalNotification
{
    public function handle(AdApprovedEvent $event)
    {
        $event->ad->user->notify(new AdApprovedNotification($event->ad));
    }
}
