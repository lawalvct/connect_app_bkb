<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\UserSwipe;
use Carbon\Carbon;
use Auth;

class UserSwipeHelper
{
    public static function insert($data)
    {
        $user_data = Auth::user();
        $data['created_at'] = date('Y-m-d H:i:s');

        if ($user_data) {
            $data['created_by'] = $user_data->id;
        }

        $insert_id = new UserSwipe($data);
        $insert_id->save();
        $Insert = $insert_id->id;
        return $Insert;
    }

    public static function update($data, $where)
    {
        $user_data = Auth::user();
        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($user_data) {
            $data['updated_by'] = $user_data->id;
        }

        $update = UserSwipe::where($where)->update($data);
        return $update;
    }

    public static function softDelete($data, $where)
    {
        $user_data = Auth::user();

        if ($user_data) {
            $data['deleted_by'] = $user_data->id;
            $data['deleted_at'] = date('Y-m-d H:i:s');
        }

        $update = UserSwipe::where($where)->update($data);
        return $update;
    }

    /**
     * Get today's swipe record for user
     */
    public static function getTodayRecord($userId)
    {
        return UserSwipe::where('user_id', $userId)
            ->where('swipe_date', Carbon::today())
            ->first();
    }

    /**
     * Get or create today's swipe record for user
     */
    public static function getOrCreateTodayRecord($userId)
    {
        $record = self::getTodayRecord($userId);

        if (!$record) {
            $data = [
                'user_id' => $userId,
                'swipe_date' => Carbon::today(),
                'left_swipes' => 0,
                'right_swipes' => 0,
                'super_likes' => 0,
                'total_swipes' => 0
            ];

            $recordId = self::insert($data);
            $record = UserSwipe::find($recordId);
        }

        return $record;
    }

    /**
     * Increment swipe count for user
     */
    public static function incrementSwipe($userId, $swipeType = 'right')
    {
        $record = self::getOrCreateTodayRecord($userId);

        $updateData = ['total_swipes' => $record->total_swipes + 1];

        switch ($swipeType) {
            case 'left':
                $updateData['left_swipes'] = $record->left_swipes + 1;
                break;
            case 'super':
                $updateData['super_likes'] = $record->super_likes + 1;
                $updateData['right_swipes'] = $record->right_swipes + 1;
                break;
            default: // right
                $updateData['right_swipes'] = $record->right_swipes + 1;
                break;
        }

        self::update($updateData, ['id' => $record->id]);

        return $record;
    }

    /**
     * Get user's swipe history
     */
    public static function getSwipeHistory($userId, $days = 7)
    {
        return UserSwipe::where('user_id', $userId)
            ->where('swipe_date', '>=', Carbon::today()->subDays($days))
            ->orderBy('swipe_date', 'desc')
            ->get();
    }

    /**
     * Get total swipes for today by user ID
     */
    public static function getTodayTotalSwipes($userId)
    {
        $record = self::getTodayRecord($userId);
        return $record ? $record->total_swipes : 0;
    }

    /**
     * Get swipe count within specified hours (for rolling window)
     *
     * @param int $userId
     * @param int $hours Number of hours to look back (default 12)
     * @return int
     */
    public static function getSwipeCountWithinHours($userId, $hours = 12)
    {
        return UserSwipe::where('user_id', $userId)
            ->where('swiped_at', '>=', Carbon::now()->subHours($hours))
            ->sum('total_swipes') ?? 0;
    }

    /**
     * Check if user can swipe based on 12-hour rolling window
     *
     * @param int $userId
     * @param int $baseLimit Base swipe limit (default 50)
     * @param int $hours Time window in hours (default 12)
     * @return array ['can_swipe' => bool, 'swipes_used' => int, 'limit' => int, 'remaining_swipes' => int, 'resets_at' => string|null]
     */
    public static function canSwipeInWindow($userId, $baseLimit = 50, $hours = 12)
    {
        // Get user's subscription to check for Connect Boost
        $user = \App\Models\User::find($userId);
        $hasBoost = false;

        if ($user && $user->activeSubscription) {
            // Check if user has Connect Boost subscription (ID 4)
            $hasBoost = $user->activeSubscription->subscription_plan_id == 4;
        }

        // Calculate total limit
        $totalLimit = $baseLimit;
        if ($hasBoost) {
            $totalLimit = $baseLimit + 50; // Add 50 more swipes for Connect Boost users
        }

        // Get swipes within time window
        $swipesUsed = self::getSwipeCountWithinHours($userId, $hours);

        // Calculate remaining swipes
        $remainingSwipes = max(0, $totalLimit - $swipesUsed);
        $canSwipe = $swipesUsed < $totalLimit;

        // Get reset time (when oldest swipe in window expires)
        $resetAt = null;
        if ($swipesUsed > 0) {
            $oldestSwipe = UserSwipe::where('user_id', $userId)
                ->where('swiped_at', '>=', Carbon::now()->subHours($hours))
                ->orderBy('swiped_at', 'asc')
                ->first();

            if ($oldestSwipe && $oldestSwipe->swiped_at) {
                $resetAt = Carbon::parse($oldestSwipe->swiped_at)
                    ->addHours($hours)
                    ->toDateTimeString();
            }
        }

        return [
            'can_swipe' => $canSwipe,
            'swipes_used' => $swipesUsed,
            'limit' => $totalLimit,
            'remaining_swipes' => $remainingSwipes,
            'resets_at' => $resetAt,
            'has_boost' => $hasBoost
        ];
    }

    /**
     * Record a swipe with timestamp for 12-hour window tracking
     *
     * @param int $userId
     * @param string $swipeType 'left', 'right', or 'super'
     * @return \App\Models\UserSwipe
     */
    public static function recordSwipeWithTimestamp($userId, $swipeType = 'right')
    {
        // Get or create today's record
        $record = self::getTodayRecord($userId);

        if ($record) {
            // Update existing record
            $updateData = [
                'total_swipes' => $record->total_swipes + 1,
                'swiped_at' => Carbon::now() // Update timestamp to latest swipe
            ];

            switch ($swipeType) {
                case 'left':
                    $updateData['left_swipes'] = $record->left_swipes + 1;
                    break;
                case 'super':
                    $updateData['super_likes'] = $record->super_likes + 1;
                    $updateData['right_swipes'] = $record->right_swipes + 1;
                    break;
                default: // right
                    $updateData['right_swipes'] = $record->right_swipes + 1;
                    break;
            }

            self::update($updateData, ['id' => $record->id]);
            return UserSwipe::find($record->id);
        } else {
            // Create new record for today
            $data = [
                'user_id' => $userId,
                'swipe_date' => Carbon::today(),
                'swiped_at' => Carbon::now(),
                'left_swipes' => $swipeType === 'left' ? 1 : 0,
                'right_swipes' => ($swipeType === 'right' || $swipeType === 'super') ? 1 : 0,
                'super_likes' => $swipeType === 'super' ? 1 : 0,
                'total_swipes' => 1
            ];

            $swipeId = self::insert($data);
            return UserSwipe::find($swipeId);
        }
    }
}
