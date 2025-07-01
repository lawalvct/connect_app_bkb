<?php

namespace App\Helpers;

use App\Models\Ad;
use Carbon\Carbon;

class AdHelper
{
    /**
     * Get ad statistics for a user
     */
    public static function getUserAdStats($userId)
    {
        return [
            'total_ads' => Ad::byUser($userId)->notDeleted()->count(),
            'active_ads' => Ad::byUser($userId)->where('status', 'active')->count(),
            'paused_ads' => Ad::byUser($userId)->where('status', 'paused')->count(),
            'pending_ads' => Ad::byUser($userId)->where('admin_status', 'pending')->count(),
            'approved_ads' => Ad::byUser($userId)->where('admin_status', 'approved')->count(),
            'rejected_ads' => Ad::byUser($userId)->where('admin_status', 'rejected')->count(),
            'total_spent' => Ad::byUser($userId)->sum('total_spent'),
            'total_budget' => Ad::byUser($userId)->sum('budget'),
            'total_impressions' => Ad::byUser($userId)->sum('current_impressions'),
            'total_clicks' => Ad::byUser($userId)->sum('clicks'),
        ];
    }

    /**
     * Calculate estimated daily spend
     */
    public static function calculateEstimatedDailySpend($budget, $startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end) + 1;

        return $totalDays > 0 ? round($budget / $totalDays, 2) : 0;
    }

    /**
     * Check if user can create more ads (implement your business logic)
     */
    public static function canUserCreateAd($userId)
    {
        $activeAds = Ad::byUser($userId)->whereIn('status', ['active', 'pending_review'])->count();
        $maxAds = 10; // Define your limit

        return $activeAds < $maxAds;
    }

    /**
     * Get ad performance summary
     */
    public static function getAdPerformanceSummary($adId)
    {
        $ad = Ad::find($adId);

        if (!$ad) {
            return null;
        }

        $daysRunning = $ad->activated_at ? $ad->activated_at->diffInDays(now()) + 1 : 0;

        return [
            'impressions_per_day' => $daysRunning > 0 ? round($ad->current_impressions / $daysRunning) : 0,
            'clicks_per_day' => $daysRunning > 0 ? round($ad->clicks / $daysRunning) : 0,
            'spend_per_day' => $daysRunning > 0 ? round($ad->total_spent / $daysRunning, 2) : 0,
            'days_running' => $daysRunning,
            'conversion_rate' => $ad->clicks > 0 ? round(($ad->conversions / $ad->clicks) * 100, 2) : 0,
            'cost_per_conversion' => $ad->conversions > 0 ? round($ad->total_spent / $ad->conversions, 2) : 0,
        ];
    }

    /**
     * Update ad metrics (called by external systems)
     */
    public static function updateAdMetrics($adId, $impressions, $clicks, $conversions = 0, $spent = 0)
    {
        $ad = Ad::find($adId);

        if (!$ad || $ad->status !== 'active') {
            return false;
        }

        $ad->increment('current_impressions', $impressions);
        $ad->increment('clicks', $clicks);
        $ad->increment('conversions', $conversions);
        $ad->increment('total_spent', $spent);

        // Update cost per click
        if ($ad->clicks > 0) {
            $ad->cost_per_click = round($ad->total_spent / $ad->clicks, 4);
            $ad->save();
        }

        // Check if ad should be completed
        if ($ad->current_impressions >= $ad->target_impressions ||
            $ad->total_spent >= $ad->budget ||
            $ad->end_date->isPast()) {
            $ad->update(['status' => 'completed']);
        }

        return true;
    }

    /**
     * Get ads expiring soon
     */
    public static function getAdsExpiringSoon($days = 3)
    {
        return Ad::where('status', 'active')
                 ->where('end_date', '<=', now()->addDays($days))
                 ->where('end_date', '>=', now())
                 ->with(['user'])
                 ->get();
    }

    /**
     * Get ads with low performance
     */
    public static function getLowPerformingAds($ctrThreshold = 0.5)
    {
        return Ad::where('status', 'active')
                 ->where('current_impressions', '>', 1000) // Only consider ads with enough impressions
                 ->get()
                 ->filter(function ($ad) use ($ctrThreshold) {
                     return $ad->ctr < $ctrThreshold;
                 });
    }
}
