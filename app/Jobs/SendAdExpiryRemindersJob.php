<?php

namespace App\Jobs;

use App\Helpers\AdHelper;
use App\Notifications\AdExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAdExpiryRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $expiringAds = AdHelper::getAdsExpiringSoon(3); // 3 days

        foreach ($expiringAds as $ad) {
            $ad->user->notify(new AdExpiringNotification($ad));
        }
    }
}
