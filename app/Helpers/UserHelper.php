<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use App\Helpers\CountryHelper;
use App\Helpers\PostHelper;
use App\Helpers\SocialCircleHelper;
use App\Helpers\UserRequestsHelper;
use App\Helpers\UserSubscriptionHelper;
use App\Helpers\BlockUserHelper;
use Auth;
use Mail;
use Carbon\Carbon;

class UserHelper
{
    // Updated method to list users randomly
    public static function getSocialCircleWiseUsers2($socialIds, $currentUserId, $lastId = null, $countryId = null, $limit = 10)
{
    \Log::info('getSocialCircleWiseUsers2 called with params:', [
        'socialIds' => $socialIds,
        'currentUserId' => $currentUserId,
        'lastId' => $lastId,
        'countryId' => $countryId,
        'limit' => $limit
    ]);

    $query = User::where('deleted_flag', 'N')
        ->where('id', '!=', $currentUserId)
        ->whereNull('deleted_at'); // Add this to be explicit

    // Handle multiple social IDs
    if (!empty($socialIds) && is_array($socialIds)) {
        $query->whereHas('socialCircles', function ($q) use ($socialIds) {
            $q->whereIn('social_circles.id', $socialIds); // Use social_circles.id instead of social_id
        });
    }

    if ($countryId) {
        $query->where('country_id', $countryId);
    }

    // Exclude already swiped users
    try {
        $swipedUserIds = UserRequestsHelper::getSwipedUserIds($currentUserId);
        if (!empty($swipedUserIds)) {
            $query->whereNotIn('id', $swipedUserIds);
            \Log::info('Excluded swiped users:', ['count' => count($swipedUserIds)]);
        }
    } catch (\Exception $e) {
        \Log::error('Error getting swiped users:', ['error' => $e->getMessage()]);
    }

    // Exclude blocked users
    try {
        $blockedUserIds = BlockUserHelper::blockUserList($currentUserId);
        if (!empty($blockedUserIds)) {
            $query->whereNotIn('id', $blockedUserIds);
            \Log::info('Excluded blocked users:', ['count' => count($blockedUserIds)]);
        }
    } catch (\Exception $e) {
        \Log::error('Error getting blocked users:', ['error' => $e->getMessage()]);
    }

    // For pagination with random order, we need a different approach
    if ($lastId) {
        // When using random order, lastId-based pagination doesn't work well
        // Instead, we'll use offset-based pagination or skip some records
        $query->where('id', '>', $lastId);
    }

    // Get the SQL query for debugging
    $sql = $query->toSql();
    $bindings = $query->getBindings();
    \Log::info('Final query:', ['sql' => $sql, 'bindings' => $bindings]);

    // Get count before applying limit
    $totalCount = $query->count();
    \Log::info('Total users found before limit:', ['count' => $totalCount]);

    $results = $query->with(['profileImages', 'country', 'socialCircles'])
        ->inRandomOrder() // This will randomize the results
        ->limit($limit)
        ->get();

    \Log::info('Final results:', ['count' => $results->count()]);

    // Add connection counts for each user
    foreach ($results as $user) {
        $user->total_connections = UserRequestsHelper::getConnectionCount($user->id);

        // Check if the current user is connected to this user
        $user->is_connected_to_current_user = UserRequestsHelper::areUsersConnected($currentUserId, $user->id);
    }

    return $results;
}

    public static function getById($id)
    {
        return User::where('id', $id)
                   ->where('deleted_flag', 'N')
                   ->first();
    }

public static function getAllDetailByUserId($id)
{
    $user = self::getById($id);
    if (!$user) return null;

    // Get profile data
    // $allProfileData = ProfileMultiUploadHelper::getbyId($user->id);

    // Get stats
    $totalConnections = UserRequestsHelper::getConnectionCount($user->id);
    $totalLikes = UserLikeHelper::getReceivedLikesCount($user->id);
    $totalPosts = PostHelper::getTotalPostByUserId($user->id);

    // Get user's posts
    $recentPosts = PostHelper::getPostsByUserId($user->id, 10, 0);

    // Get user's social circles
    $socialCircles = SocialCircleHelper::getUserSocialCircles($user->id);

    // Get country info
    // $countryData = CountryHelper::getById($user->country_id);

    // Add computed fields
    $user->total_connections = $totalConnections;
    $user->total_likes = $totalLikes;
    $user->total_posts = $totalPosts; // Uncommented this line
    $user->recent_posts = $recentPosts; // Add recent posts to the user object
    $user->social_circles = $socialCircles;
    // $user->country_name = $countryData->country_name ?? '';
    // $user->multiple_profile = $allProfileData;

    return $user;
}



    public static function getDiscoveryUsers($currentUserId, $socialId = null, $limit = 20, $excludeIds = [])
    {
        $query = User::where('deleted_flag', 'N')
                    ->where('id', '!=', $currentUserId)
                    ->whereNotIn('id', $excludeIds);

        // Filter by social circle if provided
        if ($socialId) {
            $query->whereHas('socialCircles', function ($q) use ($socialId) {
                $q->where('social_id', $socialId);
            });
        }

        // Exclude already swiped users
        $swipedUserIds = UserRequestsHelper::getSwipedUserIds($currentUserId);
        if (!empty($swipedUserIds)) {
            $query->whereNotIn('id', $swipedUserIds);
        }

        // Exclude blocked users
        $blockedUserIds = BlockUserHelper::blockUserList($currentUserId);
        if (!empty($blockedUserIds)) {
            $query->whereNotIn('id', $blockedUserIds);
        }

        return $query->with(['profileImages', 'country'])
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get();
    }

    public static function getSocialCircleWiseUsers($socialId, $currentUserId, $lastId = null, $countryId = null)
    {
        return 4;
        $query = User::where('deleted_flag', 'N')
                    ->where('id', '!=', $currentUserId);

        if ($socialId) {
            $query->whereHas('socialCircles', function ($q) use ($socialId) {
                $q->where('social_id', $socialId);
            });
        }

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        if ($lastId) {
            $query->where('id', '>', $lastId);
        }

        // Exclude already swiped users
        $swipedUserIds = UserRequestsHelper::getSwipedUserIds($currentUserId);
        if (!empty($swipedUserIds)) {
            $query->whereNotIn('id', $swipedUserIds);
        }

        return $query->with(['profileImages', 'country'])
                    ->orderBy('id')
                    ->limit(10)
                    ->get();
    }

    public static function getConnectedUsers($userId)
    {
        return UserRequestsHelper::getConnectedUsers($userId);
    }

    public static function update($data, $where)
    {
        $userData = Auth::user();
        $data['updated_at'] = date('Y-m-d H:i:s');
        if ($userData) {
            $data['updated_by'] = $userData->id;
        }
        return User::where($where)->update($data);
    }

    public static function insert($data)
    {
        $userData = Auth::user();
        $data['created_at'] = date('Y-m-d H:i:s');
        if ($userData) {
            $data['created_by'] = $userData->id;
        }
        $user = new User($data);
        $user->save();
        return $user->id;
    }

    /**
     * Check if user can swipe today
     */
    public static function canUserSwipe($userId)
    {
        $swipeStats = self::getSwipeStats($userId);
        return $swipeStats->total_swipes < $swipeStats->daily_limit;
    }

    /**
     * Get user's swipe statistics for today
     */
    public static function getSwipeStats($userId)
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return (object) [
                    'total_swipes' => 0,
                    'left_swipes' => 0,
                    'right_swipes' => 0,
                    'super_likes' => 0,
                    'daily_limit' => 50,
                    'remaining_swipes' => 50
                ];
            }

            // Get today's swipe record
            $todaySwipes = UserSwipeHelper::getTodayRecord($userId);

            if (!$todaySwipes) {
                $todaySwipes = (object) [
                    'total_swipes' => 0,
                    'left_swipes' => 0,
                    'right_swipes' => 0,
                    'super_likes' => 0
                ];
            }

            // Determine daily limit based on subscription
            $dailyLimit = self::getUserDailySwipeLimit($userId);

            return (object) [
                'total_swipes' => $todaySwipes->total_swipes,
                'left_swipes' => $todaySwipes->left_swipes,
                'right_swipes' => $todaySwipes->right_swipes,
                'super_likes' => $todaySwipes->super_likes,
                'daily_limit' => $dailyLimit,
                'remaining_swipes' => max(0, $dailyLimit - $todaySwipes->total_swipes)
            ];
        } catch (\Exception $e) {
            // Return default stats if there's an error
            return (object) [
                'total_swipes' => 0,
                'left_swipes' => 0,
                'right_swipes' => 0,
                'super_likes' => 0,
                'daily_limit' => 50,
                'remaining_swipes' => 50
            ];
        }
    }

    /**
     * Get user's daily swipe limit based on subscription
     */
    public static function getUserDailySwipeLimit($userId)
    {
        try {
            // Check if user has unlimited access
            if (UserSubscriptionHelper::hasUnlimitedAccess($userId)) {
                return 999999; // Unlimited
            }

            // Free user
            return 50;
        } catch (\Exception $e) {
            // Default to free user limit if there's an error
            return 50;
        }
    }

    /**
     * Increment user's swipe count
     */
    public static function incrementSwipeCount($userId, $swipeType = 'right')
    {
        return UserSwipeHelper::incrementSwipe($userId, $swipeType);
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
     * Get a random user from a specific social circle
     *
     * @param int $socialCircleId
     * @param int $currentUserId
     * @param array $excludeUserIds
     * @return User|null
     */
    public static function getRandomUserFromSocialCircle($socialCircleId, $currentUserId, $excludeUserIds = [])
    {
        try {
            \Log::info('Getting random user from social circle', [
                'social_circle_id' => $socialCircleId,
                'current_user_id' => $currentUserId,
                'exclude_user_ids' => $excludeUserIds
            ]);

            // Make sure we exclude the current user
            $excludeUserIds[] = $currentUserId;
            $excludeUserIds = array_unique($excludeUserIds);

            // Get swiped user IDs to exclude
            $swipedUserIds = UserRequestsHelper::getSwipedUserIds($currentUserId);
            if (!empty($swipedUserIds)) {
                $excludeUserIds = array_merge($excludeUserIds, $swipedUserIds);
            }

            // Get blocked user IDs to exclude
            $blockedUserIds = BlockUserHelper::blockUserList($currentUserId);
            if (!empty($blockedUserIds)) {
                $excludeUserIds = array_merge($excludeUserIds, $blockedUserIds);
            }

            // Remove duplicates
            $excludeUserIds = array_unique($excludeUserIds);

            // Build the query
            $query = User::where('deleted_flag', 'N')
                ->whereNull('deleted_at')
                ->where('id', '!=', $currentUserId)
                ->whereNotIn('id', $excludeUserIds);

            // Filter by social circle
            $query->whereHas('socialCircles', function ($q) use ($socialCircleId) {
                $q->where('social_circles.id', $socialCircleId)
                  ->where('user_social_circles.deleted_flag', 'N');
            });

            // Get a random user
            $randomUser = $query->with(['profileImages', 'country', 'socialCircles'])
                ->inRandomOrder()
                ->first();

            if ($randomUser) {
                \Log::info('Found random user from social circle', [
                        'user_id' => $randomUser->id,
                        'name' => $randomUser->name
                ]);

                // Get additional user details
                $randomUser = self::getAllDetailByUserId($randomUser->id);
            } else {
                \Log::info('No random user found in social circle');
            }

            return $randomUser;
        } catch (\Exception $e) {
            \Log::error('Error getting random user from social circle', [
                'social_circle_id' => $socialCircleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
