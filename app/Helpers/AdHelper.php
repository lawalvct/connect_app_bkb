<?php

namespace App\Helpers;

use App\Models\Ad;
use App\Models\SocialCircle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    /**
     * Get active ads for specific social circles
     */
    // public static function getAdsForSocialCircles($socialCircleIds, $limit = 5)
    // {
    //     if (empty($socialCircleIds)) {
    //         return collect();
    //     }

    //     return Ad::where('status', 'active')
    //         ->where('admin_status', 'approved')
    //         ->where('start_date', '<=', now())
    //         ->where('end_date', '>=', now())
    //         ->where('deleted_flag', 'N')
    //         ->where(function ($query) use ($socialCircleIds) {
    //             foreach ($socialCircleIds as $socialCircleId) {
    //                 $query->orWhereJsonContains('ad_placement', $socialCircleId);
    //             }
    //         })
    //         ->with(['user', 'placementSocialCircles'])
    //         ->inRandomOrder() // Randomize ad display
    //         ->limit($limit)
    //         ->get();
    // }

    /**
     * Get ads for a single social circle
     */
    public static function getAdsForSocialCircle($socialCircleId, $limit = 3)
    {
        return self::getAdsForSocialCircles([$socialCircleId], $limit);
    }

    /**
     * Record ad impression
     */
    public static function recordImpression($adId, $userId = null)
    {
        try {
            $ad = Ad::find($adId);
            if (!$ad) {
                return false;
            }

            // Increment impression count
            $ad->increment('current_impressions');

            // You can also log detailed impression data in a separate table
            // AdImpression::create([
            //     'ad_id' => $adId,
            //     'user_id' => $userId,
            //     'ip_address' => request()->ip(),
            //     'user_agent' => request()->header('User-Agent'),
            //     'created_at' => now()
            // ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to record ad impression: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Record ad click
     */
    public static function recordClick($adId, $userId = null)
    {
        try {
            $ad = Ad::find($adId);
            if (!$ad) {
                return false;
            }

            // Increment click count
            $ad->increment('clicks');

            // Update cost per click and total spent (simplified calculation)
            if ($ad->clicks > 0 && $ad->current_impressions > 0) {
                $ctr = ($ad->clicks / $ad->current_impressions) * 100;
                $estimatedCostPerClick = ($ad->budget / $ad->target_impressions) * ($ctr / 100);
                $totalSpent = $ad->clicks * $estimatedCostPerClick;

                $ad->update([
                    'cost_per_click' => $estimatedCostPerClick,
                    'total_spent' => min($totalSpent, $ad->budget) // Don't exceed budget
                ]);
            }

            // You can also log detailed click data
            // AdClick::create([
            //     'ad_id' => $adId,
            //     'user_id' => $userId,
            //     'ip_address' => request()->ip(),
            //     'user_agent' => request()->header('User-Agent'),
            //     'created_at' => now()
            // ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to record ad click: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user can see ads from specific social circles
     */
    public static function getUserVisibleAds($userId, $socialCircleIds, $limit = 5)
    {
        // Get user's social circles if not provided
        if (empty($socialCircleIds)) {
            $user = \App\Models\User::with('socialCircles')->find($userId);
            $socialCircleIds = $user ? $user->socialCircles->pluck('id')->toArray() : [];
        }

        return self::getAdsForSocialCircles($socialCircleIds, $limit);
    }

    /**
     * Get ad performance summary for social circles
     */
//    private function getAdPerformanceBySocialCircle($socialCircleId, $dateFrom = null, $userId = null)
// {
//     try {
//         $query = Ad::whereJsonContains('target_social_circles', $socialCircleId)
//             ->where('deleted_flag', 'N');

//         // IMPORTANT: Filter by user ID to ensure only authenticated user's data
//         if ($userId) {
//             $query->where('user_id', $userId);
//         }

//         if ($dateFrom) {
//             $query->where('created_at', '>=', $dateFrom);
//         }

//         return $query->selectRaw('
//             COUNT(*) as total_ads,
//             SUM(current_impressions) as total_impressions,
//             SUM(clicks) as total_clicks,
//             SUM(conversions) as total_conversions,
//             SUM(total_spent) as total_spent,
//             AVG(CASE WHEN current_impressions > 0 THEN (clicks / current_impressions) * 100 ELSE 0 END) as avg_ctr
//         ')->first();
//     } catch (\Exception $e) {
//         \Log::error('Error getting ad performance by social circle', [
//             'social_circle_id' => $socialCircleId,
//             'user_id' => $userId,
//             'error' => $e->getMessage()
//         ]);
//         return null;
//     }
// }


    /**
 * Estimate the reach of an ad based on placement and target audience
 *
 * @param array $adPlacement
 * @param array $targetAudience
 * @return array
 */
// public static function estimateAdReach($adPlacement, $targetAudience)
// {
//     // This is a placeholder implementation
//     // In a real application, you would calculate this based on actual user data

//     $totalUsers = 0;
//     $matchingUsers = 0;

//     // Get total users in the selected social circles
//     if (!empty($adPlacement)) {
//         foreach ($adPlacement as $circleId) {
//             // Get count of users in this social circle
//             $circleUsers = \DB::table('user_social_circle')
//                 ->where('social_id', $circleId)
//                 ->count();

//             $totalUsers += $circleUsers;
//         }
//     }

//     // Calculate matching users based on target audience criteria
//     if (!empty($targetAudience)) {
//         $query = \DB::table('users')
//             ->join('user_social_circle', 'users.id', '=', 'user_social_circle.user_id')
//             ->whereIn('user_social_circle.social_id', $adPlacement)
//             ->where('users.deleted_flag', 'N');

//         // Apply age filter if provided
//         if (isset($targetAudience['age_min']) && isset($targetAudience['age_max'])) {
//             $query->whereRaw('TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) >= ?', [$targetAudience['age_min']])
//                   ->whereRaw('TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) <= ?', [$targetAudience['age_max']]);
//         }

//         // Apply gender filter if provided
//         if (isset($targetAudience['gender']) && $targetAudience['gender'] !== 'all') {
//             $query->where('users.gender', $targetAudience['gender']);
//         }

//         // Apply location filter if provided
//         if (isset($targetAudience['locations']) && !empty($targetAudience['locations'])) {
//             $query->whereIn('users.country_id', $targetAudience['locations']);
//         }

//         $matchingUsers = $query->distinct('users.id')->count('users.id');
//     }

//     // Calculate estimated metrics
//     $estimatedImpressions = $matchingUsers * 5; // Assume each user sees the ad 5 times
//     $estimatedClicks = round($estimatedImpressions * 0.02); // Assume 2% CTR
//     $estimatedConversions = round($estimatedClicks * 0.1); // Assume 10% conversion rate

//     return [
//         'total_users' => $totalUsers,
//         'matching_users' => $matchingUsers,
//         'estimated_impressions' => $estimatedImpressions,
//         'estimated_clicks' => $estimatedClicks,
//         'estimated_conversions' => $estimatedConversions,
//         'estimated_ctr' => $estimatedImpressions > 0 ? round(($estimatedClicks / $estimatedImpressions) * 100, 2) : 0,
//     ];
// }

private function getAdPerformanceBySocialCircle($socialCircleId, $dateFrom = null, $userId = null)
{
    try {
        $query = Ad::whereJsonContains('target_social_circles', $socialCircleId)
            ->where('deleted_flag', 'N');

        // IMPORTANT: Filter by user ID to ensure only authenticated user's data
        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        return $query->selectRaw('
            COUNT(*) as total_ads,
            SUM(current_impressions) as total_impressions,
            SUM(clicks) as total_clicks,
            SUM(conversions) as total_conversions,
            SUM(total_spent) as total_spent,
            AVG(CASE WHEN current_impressions > 0 THEN (clicks / current_impressions) * 100 ELSE 0 END) as avg_ctr
        ')->first();
    } catch (\Exception $e) {
        \Log::error('Error getting ad performance by social circle', [
            'social_circle_id' => $socialCircleId,
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}


/**
     * Get impressions data for line chart by year
     *
     * @param int $userId
     * @param int $year
     * @param int|null $adId - specific ad ID (optional)
     * @return array
     */
    public static function getImpressionsOvertime($userId, $year, $adId = null)
    {
        try {
            // Base query for user's ads
            $query = Ad::where('user_id', $userId)
                ->where('deleted_flag', 'N');

            // Filter by specific ad if provided
            if ($adId) {
                $query->where('id', $adId);
            }

            // Get ads created in the specified year or active during the year
            $query->where(function($q) use ($year) {
                $q->whereYear('created_at', $year)
                  ->orWhere(function($subQ) use ($year) {
                      $subQ->whereYear('start_date', '<=', $year)
                           ->whereYear('end_date', '>=', $year);
                  });
            });

            $ads = $query->get();

            // Initialize months array
            $months = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];

            $impressionsData = [];
            $clicksData = [];
            $conversionsData = [];

            // Initialize all months with 0
            foreach ($months as $monthNum => $monthName) {
                $impressionsData[] = [
                    'month' => $monthName,
                    'month_number' => $monthNum,
                    'impressions' => 0,
                    'clicks' => 0,
                    'conversions' => 0,
                    'ctr' => 0
                ];
            }

            // If we have detailed tracking table (ad_analytics), use it
            if (self::hasAnalyticsTable()) {
                $analyticsData = self::getDetailedAnalytics($userId, $year, $adId);

                foreach ($analyticsData as $data) {
                    $monthIndex = $data->month - 1; // Array is 0-indexed
                    $impressionsData[$monthIndex]['impressions'] = (int) $data->total_impressions;
                    $impressionsData[$monthIndex]['clicks'] = (int) $data->total_clicks;
                    $impressionsData[$monthIndex]['conversions'] = (int) $data->total_conversions;
                    $impressionsData[$monthIndex]['ctr'] = $data->total_impressions > 0
                        ? round(($data->total_clicks / $data->total_impressions) * 100, 2)
                        : 0;
                }
            } else {
                // Fallback: Distribute current stats across months based on ad activity
                foreach ($ads as $ad) {
                    $adStartMonth = max(1, Carbon::parse($ad->start_date)->month);
                    $adEndMonth = min(12, Carbon::parse($ad->end_date)->month);

                    // Simple distribution across active months
                    $activeMonths = $adEndMonth - $adStartMonth + 1;
                    $impressionsPerMonth = $activeMonths > 0 ? $ad->current_impressions / $activeMonths : 0;
                    $clicksPerMonth = $activeMonths > 0 ? $ad->clicks / $activeMonths : 0;
                    $conversionsPerMonth = $activeMonths > 0 ? $ad->conversions / $activeMonths : 0;

                    for ($month = $adStartMonth; $month <= $adEndMonth; $month++) {
                        $monthIndex = $month - 1;
                        $impressionsData[$monthIndex]['impressions'] += (int) $impressionsPerMonth;
                        $impressionsData[$monthIndex]['clicks'] += (int) $clicksPerMonth;
                        $impressionsData[$monthIndex]['conversions'] += (int) $conversionsPerMonth;

                        // Recalculate CTR
                        if ($impressionsData[$monthIndex]['impressions'] > 0) {
                            $impressionsData[$monthIndex]['ctr'] = round(
                                ($impressionsData[$monthIndex]['clicks'] / $impressionsData[$monthIndex]['impressions']) * 100,
                                2
                            );
                        }
                    }
                }
            }

            return [
                'year' => $year,
                'data' => $impressionsData,
                'summary' => [
                    'total_impressions' => array_sum(array_column($impressionsData, 'impressions')),
                    'total_clicks' => array_sum(array_column($impressionsData, 'clicks')),
                    'total_conversions' => array_sum(array_column($impressionsData, 'conversions')),
                    'average_ctr' => self::calculateAverageCTR($impressionsData),
                    'peak_month' => self::getPeakMonth($impressionsData),
                    'total_ads' => $ads->count()
                ]
            ];

        } catch (\Exception $e) {
            \Log::error('Error getting impressions overtime data', [
                'user_id' => $userId,
                'year' => $year,
                'ad_id' => $adId,
                'error' => $e->getMessage()
            ]);

            return [
                'year' => $year,
                'data' => [],
                'summary' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if we have a detailed analytics table
     */
    private static function hasAnalyticsTable()
    {
        try {
            return DB::getSchemaBuilder()->hasTable('ad_analytics');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get detailed analytics from ad_analytics table
     */
    private static function getDetailedAnalytics($userId, $year, $adId = null)
    {
        $query = DB::table('ad_analytics')
            ->join('ads', 'ad_analytics.ad_id', '=', 'ads.id')
            ->where('ads.user_id', $userId)
            ->whereYear('ad_analytics.date', $year)
            ->select(
                DB::raw('MONTH(ad_analytics.date) as month'),
                DB::raw('SUM(ad_analytics.impressions) as total_impressions'),
                DB::raw('SUM(ad_analytics.clicks) as total_clicks'),
                DB::raw('SUM(ad_analytics.conversions) as total_conversions')
            )
            ->groupBy(DB::raw('MONTH(ad_analytics.date)'))
            ->orderBy('month');

        if ($adId) {
            $query->where('ads.id', $adId);
        }

        return $query->get();
    }

    /**
     * Calculate average CTR across all months
     */
    private static function calculateAverageCTR($data)
    {
        $totalImpressions = array_sum(array_column($data, 'impressions'));
        $totalClicks = array_sum(array_column($data, 'clicks'));

        return $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
    }

    /**
     * Get the month with highest impressions
     */
    private static function getPeakMonth($data)
    {
        $maxImpressions = 0;
        $peakMonth = null;

        foreach ($data as $monthData) {
            if ($monthData['impressions'] > $maxImpressions) {
                $maxImpressions = $monthData['impressions'];
                $peakMonth = $monthData['month'];
            }
        }

        return [
            'month' => $peakMonth,
            'impressions' => $maxImpressions
        ];
    }



    /**
     * Get ads for multiple social circles
     */
    public static function getAdsForSocialCircles($socialCircleIds, $limit = 5)
    {
        return Ad::where('status', 'active')
            ->where('admin_status', 'approved')
            ->where('deleted_flag', 'N')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where(function($query) use ($socialCircleIds) {
                foreach ($socialCircleIds as $circleId) {
                    $query->orWhereJsonContains('target_social_circles', $circleId);
                }
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Estimate ad reach based on social circles and audience
     */
    public static function estimateAdReach($socialCircleIds, $targetAudience)
    {
        try {
            // This is a simplified estimation
            // In a real app, you'd have more sophisticated reach calculation

            $baseReach = 0;

            foreach ($socialCircleIds as $circleId) {
                $circle = SocialCircle::find($circleId);
                if ($circle) {
                    // Estimate based on social circle size
                    $circleUserCount = $circle->users()->count();
                    $baseReach += $circleUserCount;
                }
            }

            // Apply audience filters (simplified)
            $ageRange = $targetAudience['age_max'] - $targetAudience['age_min'];
            $ageMultiplier = min(1.0, $ageRange / 50); // Broader age range = higher reach

            $genderMultiplier = $targetAudience['gender'] === 'all' ? 1.0 : 0.5;

            $estimatedReach = (int) ($baseReach * $ageMultiplier * $genderMultiplier);

            return [
                'estimated_reach' => $estimatedReach,
                'confidence' => 'medium', // low, medium, high
                'factors' => [
                    'social_circles' => count($socialCircleIds),
                    'age_range' => $ageRange,
                    'gender_targeting' => $targetAudience['gender'],
                    'location_count' => count($targetAudience['locations'] ?? [])
                ]
            ];

        } catch (\Exception $e) {
            return [
                'estimated_reach' => 0,
                'confidence' => 'low',
                'error' => $e->getMessage()
            ];
        }
    }

}
