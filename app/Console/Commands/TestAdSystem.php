<?php

namespace App\Console\Commands;

use App\Helpers\AdHelper;
use App\Models\Ad;
use App\Models\SocialCircle;
use App\Models\User;
use Illuminate\Console\Command;

class TestAdSystem extends Command
{
    protected $signature = 'ads:test';
    protected $description = 'Test the advertising system functionality';

    public function handle()
    {
        $this->info('Testing Advertising System...');

        // Test 1: Check if ads table exists and has data
        $adCount = Ad::count();
        $this->info("Total ads in database: {$adCount}");

        // Test 2: Check social circle integration
        $socialCircles = SocialCircle::active()->get();
        $this->info("Active social circles: {$socialCircles->count()}");

        // Test 3: Test ad placement functionality
        if ($socialCircles->count() > 0) {
            $firstCircle = $socialCircles->first();
            $adsInCircle = AdHelper::getAdsForSocialCircle($firstCircle->id, 5);
            $this->info("Ads in '{$firstCircle->name}' social circle: {$adsInCircle->count()}");
        }

        // Test 4: Check active ads
        $activeAds = Ad::active()->count();
        $this->info("Active ads: {$activeAds}");

        // Test 5: Check pending ads
        $pendingAds = Ad::pending()->count();
        $this->info("Pending ads: {$pendingAds}");

        $this->info('✅ Advertising system test completed!');

        return 0;
    }
}
