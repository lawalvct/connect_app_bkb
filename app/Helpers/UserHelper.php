<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use App\Models\UserSwipe;
use App\Helpers\CountryHelper;
use App\Helpers\PostHelper;
use App\Helpers\SocialCircleHelper;
use App\Helpers\UserRequestsHelper;
use App\Helpers\UserSubscriptionHelper;
use App\Helpers\BlockUserHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
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

    // New method to get latest users with some randomness based on user ID
    // Excludes testing users (ID < 500) to only show real users
    // Prioritizes Connect Boost users (plan ID 4) to appear in front of line
    public static function getLatestSocialCircleUsers($socialIds, $currentUserId, $lastId = null, $countryId = null, $limit = 10)
    {
        Log::info('getLatestSocialCircleUsers called with params:', [
            'socialIds' => $socialIds,
            'currentUserId' => $currentUserId,
            'lastId' => $lastId,
            'countryId' => $countryId,
            'limit' => $limit
        ]);

        $query = User::where('deleted_flag', 'N')
            ->where('id', '!=', $currentUserId)
            ->where('id', '>=', 500) // Only include users with ID 500 and above (exclude testing users)
            ->whereNull('deleted_at');

        // Handle multiple social IDs
        if (!empty($socialIds) && is_array($socialIds)) {
            $query->whereHas('socialCircles', function ($q) use ($socialIds) {
                $q->whereIn('social_circles.id', $socialIds);
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
                Log::info('Excluded swiped users:', ['count' => count($swipedUserIds)]);
            }
        } catch (\Exception $e) {
            Log::error('Error getting swiped users:', ['error' => $e->getMessage()]);
        }

        // Exclude blocked users
        try {
            $blockedUserIds = BlockUserHelper::blockUserList($currentUserId);
            if (!empty($blockedUserIds)) {
                $query->whereNotIn('id', $blockedUserIds);
                Log::info('Excluded blocked users:', ['count' => count($blockedUserIds)]);
            }
        } catch (\Exception $e) {
            Log::error('Error getting blocked users:', ['error' => $e->getMessage()]);
        }

        // Apply pagination with lastId for latest users
        if ($lastId) {
            $query->where('id', '<', $lastId); // Use < for descending order pagination
        }

        // Get count before applying limit
        $totalCount = $query->count();
        Log::info('Total users found before limit:', ['count' => $totalCount]);

        // Fetch more users than needed for sorting and shuffling
        $fetchLimit = min($limit * 3, 50); // Get 3x the needed amount or max 50

        $results = $query->with(['profileImages', 'country', 'socialCircles'])
            ->orderBy('id', 'desc') // Latest users first
            ->limit($fetchLimit)
            ->get();

        // Separate Connect Boost users from regular users
        $boostUsers = collect();
        $regularUsers = collect();

        foreach ($results as $user) {
            // Check if user has active Connect Boost subscription (plan ID 4)
            $hasBoost = UserSubscriptionHelper::hasBoostAccess($user->id);

            if ($hasBoost) {
                $boostUsers->push($user);
            } else {
                $regularUsers->push($user);
            }
        }

        Log::info('Separated users by boost status:', [
            'boost_users' => $boostUsers->count(),
            'regular_users' => $regularUsers->count()
        ]);

        // Take up to 5 boost users and shuffle them for variety
        $priorityBoostUsers = $boostUsers->shuffle()->take(5);

        // Calculate how many regular users we need
        $remainingSlots = $limit - $priorityBoostUsers->count();

        // Shuffle regular users and take what we need
        $selectedRegularUsers = $regularUsers->shuffle()->take(max(0, $remainingSlots));

        // Combine: Boost users first (front of line), then regular users
        $finalResults = $priorityBoostUsers->concat($selectedRegularUsers);

        Log::info('Final results with front-of-line priority:', [
            'total_count' => $finalResults->count(),
            'boost_users_shown' => $priorityBoostUsers->count(),
            'regular_users_shown' => $selectedRegularUsers->count()
        ]);

        // Add connection counts for each user
        foreach ($finalResults as $user) {
            $user->total_connections = UserRequestsHelper::getConnectionCount($user->id);
            $user->is_connected_to_current_user = UserRequestsHelper::areUsersConnected($currentUserId, $user->id);
        }

        return $finalResults;
    }

    // Fallback method to get any latest users (ID >= 500) when social circle filtering fails
    public static function getAnyLatestUsers($currentUserId, $lastId = null, $countryId = null, $limit = 10)
    {
        Log::info('getAnyLatestUsers called with params:', [
            'currentUserId' => $currentUserId,
            'lastId' => $lastId,
            'countryId' => $countryId,
            'limit' => $limit
        ]);

        $query = User::where('deleted_flag', 'N')
            ->where('id', '!=', $currentUserId)
            ->where('id', '>=', 500) // Only include users with ID 500 and above (exclude testing users)
            ->whereNull('deleted_at');

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        // Exclude already swiped users
        try {
            $swipedUserIds = UserRequestsHelper::getSwipedUserIds($currentUserId);
            if (!empty($swipedUserIds)) {
                $query->whereNotIn('id', $swipedUserIds);
                Log::info('Excluded swiped users:', ['count' => count($swipedUserIds)]);
            }
        } catch (\Exception $e) {
            Log::error('Error getting swiped users:', ['error' => $e->getMessage()]);
        }

        // Exclude blocked users
        try {
            $blockedUserIds = BlockUserHelper::blockUserList($currentUserId);
            if (!empty($blockedUserIds)) {
                $query->whereNotIn('id', $blockedUserIds);
                Log::info('Excluded blocked users:', ['count' => count($blockedUserIds)]);
            }
        } catch (\Exception $e) {
            Log::error('Error getting blocked users:', ['error' => $e->getMessage()]);
        }

        // Apply pagination with lastId for latest users
        if ($lastId) {
            $query->where('id', '<', $lastId); // Use < for descending order pagination
        }

        // Get count before applying limit
        $totalCount = $query->count();
        Log::info('Total available users found before limit:', ['count' => $totalCount]);

        // Fetch more users than needed for sorting and shuffling
        $fetchLimit = min($limit * 3, 50); // Get 3x the needed amount or max 50

        $results = $query->with(['profileImages', 'country', 'socialCircles'])
            ->orderBy('id', 'desc') // Latest users first
            ->limit($fetchLimit)
            ->get();

        // Separate Connect Boost users from regular users (same as main method)
        $boostUsers = collect();
        $regularUsers = collect();

        foreach ($results as $user) {
            // Check if user has active Connect Boost subscription (plan ID 4)
            $hasBoost = UserSubscriptionHelper::hasBoostAccess($user->id);

            if ($hasBoost) {
                $boostUsers->push($user);
            } else {
                $regularUsers->push($user);
            }
        }

        Log::info('Fallback: Separated users by boost status:', [
            'boost_users' => $boostUsers->count(),
            'regular_users' => $regularUsers->count()
        ]);

        // Take up to 5 boost users and shuffle them for variety
        $priorityBoostUsers = $boostUsers->shuffle()->take(5);

        // Calculate how many regular users we need
        $remainingSlots = $limit - $priorityBoostUsers->count();

        // Shuffle regular users and take what we need
        $selectedRegularUsers = $regularUsers->shuffle()->take(max(0, $remainingSlots));

        // Combine: Boost users first (front of line), then regular users
        $finalResults = $priorityBoostUsers->concat($selectedRegularUsers);

        Log::info('Fallback: Final results with front-of-line priority:', [
            'total_count' => $finalResults->count(),
            'boost_users_shown' => $priorityBoostUsers->count(),
            'regular_users_shown' => $selectedRegularUsers->count()
        ]);

        // Add connection counts for each user
        foreach ($finalResults as $user) {
            $user->total_connections = UserRequestsHelper::getConnectionCount($user->id);
            $user->is_connected_to_current_user = UserRequestsHelper::areUsersConnected($currentUserId, $user->id);
        }

        return $finalResults;
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
     * Check if user can swipe (has remaining swipes in 12-hour window)
     */
    public static function canUserSwipe($userId)
    {
        $swipeStats = self::getSwipeStats($userId);
        return $swipeStats->remaining_swipes > 0;
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
                    'swipe_limit' => 50,
                    'remaining_swipes' => 50,
                    'resets_at' => null,
                    'has_boost' => false
                ];
            }

            // Use 12-hour rolling window instead of daily
            $windowStats = UserSwipeHelper::canSwipeInWindow($userId, 50, 12);

            // Get swipe type breakdown for last 12 hours
            $recentSwipes = \App\Models\UserSwipe::where('user_id', $userId)
                ->where('swiped_at', '>=', \Carbon\Carbon::now()->subHours(12))
                ->selectRaw('
                    SUM(total_swipes) as total_swipes,
                    SUM(left_swipes) as left_swipes,
                    SUM(right_swipes) as right_swipes,
                    SUM(super_likes) as super_likes
                ')
                ->first();

            if (!$recentSwipes) {
                $recentSwipes = (object) [
                    'total_swipes' => 0,
                    'left_swipes' => 0,
                    'right_swipes' => 0,
                    'super_likes' => 0
                ];
            }

            return (object) [
                'total_swipes' => $recentSwipes->total_swipes ?? 0,
                'left_swipes' => $recentSwipes->left_swipes ?? 0,
                'right_swipes' => $recentSwipes->right_swipes ?? 0,
                'super_likes' => $recentSwipes->super_likes ?? 0,
                'swipe_limit' => $windowStats['limit'],
                'remaining_swipes' => $windowStats['remaining_swipes'],
                'resets_at' => $windowStats['resets_at'],
                'has_boost' => $windowStats['has_boost']
            ];
        } catch (\Exception $e) {
            // Return default stats if there's an error
            return (object) [
                'total_swipes' => 0,
                'left_swipes' => 0,
                'right_swipes' => 0,
                'super_likes' => 0,
                'swipe_limit' => 50,
                'remaining_swipes' => 50,
                'resets_at' => null,
                'has_boost' => false
            ];
        }
    }

    /**
     * Get user's daily swipe limit based on subscription
     */
    public static function getUserDailySwipeLimit($userId)
    {
        try {
            $baseLimit = 50; // Free user default limit

            // Check if user has unlimited access
            if (UserSubscriptionHelper::hasUnlimitedAccess($userId)) {
                return 999999; // Unlimited
            }

            // Check if user has Connect Boost subscription (+50 additional swipes)
            if (UserSubscriptionHelper::hasConnectBoost($userId)) {
                $baseLimit += 50; // Add 50 swipes for Connect Boost
            }

            return $baseLimit;
        } catch (\Exception $e) {
            // Default to free user limit if there's an error
            \Log::error('Error getting daily swipe limit: ' . $e->getMessage());
            return 50;
        }
    }

    /**
     * Increment user's swipe count (now using 12-hour rolling window)
     */
    public static function incrementSwipeCount($userId, $swipeType = 'right')
    {
        return UserSwipeHelper::recordSwipeWithTimestamp($userId, $swipeType);
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
            // Log::info('Getting random user from social circle', [
            //     'social_circle_id' => $socialCircleId,
            //     'current_user_id' => $currentUserId,
            //     'exclude_user_ids' => $excludeUserIds
            // ]);

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
                Log::info('Found random user from social circle', [
                        'user_id' => $randomUser->id,
                        'name' => $randomUser->name
                ]);

                // Get additional user details
                $randomUser = self::getAllDetailByUserId($randomUser->id);
            } else {
                Log::info('No random user found in social circle');
            }

            return $randomUser;
        } catch (\Exception $e) {
            Log::error('Error getting random user from social circle', [
                'social_circle_id' => $socialCircleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    // Get random user from anywhere in the system (no social circle filter)
    public static function getRandomUser($currentUserId, $excludeUserIds = [])
    {
        try {
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

            // Build the query - get any user in the system
            $query = User::where('deleted_flag', 'N')
                ->whereNull('deleted_at')
                ->where('id', '!=', $currentUserId)
                ->where('id', '>=', 500) // Exclude testing users below ID 500
                ->whereNotIn('id', $excludeUserIds);

            // Get a random user
            $randomUser = $query->with(['profileImages', 'country', 'socialCircles'])
                ->inRandomOrder()
                ->first();

            if ($randomUser) {
                Log::info('Found random user from system', [
                    'user_id' => $randomUser->id,
                    'name' => $randomUser->name
                ]);

                // Get additional user details
                $randomUser = self::getAllDetailByUserId($randomUser->id);
            } else {
                Log::info('No random user found in system');
            }

            return $randomUser;
        } catch (\Exception $e) {
            Log::error('Error getting random user from system', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}
