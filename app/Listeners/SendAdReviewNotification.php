<?php

namespace App\Listeners;

use App\Events\AdCreatedEvent;
use App\Models\User;
use App\Notifications\NewAdForReviewNotification;

class SendAdReviewNotification
{
    public function handle(AdCreatedEvent $event)
    {
        // Get all admin users
        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new NewAdForReviewNotification($event->ad));
        }
    }
}
