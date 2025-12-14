<?php

namespace App\Helpers;

use App\Models\UserLike;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserLikeHelper
{
    public static function insert($data)
    {
        try {
            // Ensure all required fields are present
            if (!isset($data['user_id']) || !isset($data['liked_user_id'])) {
                \Log::error('UserLikeHelper::insert - Missing required fields', ['data' => $data]);
                throw new \Exception('Missing required fields: user_id or liked_user_id');
            }

            // Create the like record using mass assignment
            $userLike = UserLike::create([
                'user_id' => $data['user_id'],
                'liked_user_id' => $data['liked_user_id'],
                'type' => $data['type'] ?? 'profile',
                'is_active' => $data['is_active'] ?? true,
            ]);

            \Log::info('UserLike created successfully', [
                'id' => $userLike->id,
                'user_id' => $userLike->user_id,
                'liked_user_id' => $userLike->liked_user_id,
                'type' => $userLike->type
            ]);

            // Send notification
            if (isset($data['liked_user_id']) && isset($data['user_id'])) {
              //  NotificationHelper::sendUserLikeNotification(
              //      $data['liked_user_id'],
              //      $data['user_id']
              //  );
            }

            return $userLike->id;
        } catch (\Exception $e) {
            \Log::error('UserLikeHelper::insert failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public static function toggleLike($userId, $likedUserId, $type = 'profile')
    {
        try {
            \Log::info('UserLikeHelper::toggleLike called', [
                'user_id' => $userId,
                'liked_user_id' => $likedUserId,
                'type' => $type
            ]);

            $existingLike = UserLike::where('user_id', $userId)
                                   ->where('liked_user_id', $likedUserId)
                                   ->where('type', $type)
                                   ->first();

            if ($existingLike) {
                \Log::info('Existing like found, toggling is_active', [
                    'like_id' => $existingLike->id,
                    'current_is_active' => $existingLike->is_active
                ]);

                $existingLike->update(['is_active' => !$existingLike->is_active]);
                return $existingLike->is_active ? 'liked' : 'unliked';
            } else {
                \Log::info('No existing like found, creating new like');

                self::insert([
                    'user_id' => $userId,
                    'liked_user_id' => $likedUserId,
                    'type' => $type,
                    'is_active' => true
                ]);
                return 'liked';
            }
        } catch (\Exception $e) {
            \Log::error('UserLikeHelper::toggleLike failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'liked_user_id' => $likedUserId,
                'type' => $type,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public static function getReceivedLikesCount($userId)
    {
        return UserLike::where('liked_user_id', $userId)
                      ->where('is_active', true)
                      ->count();
    }

    public static function getGivenLikesCount($userId)
    {
        return UserLike::where('user_id', $userId)
                      ->where('is_active', true)
                      ->count();
    }

    public static function getUsersWhoLikedMe($userId, $limit = 20)
    {
        try {
            // Get user IDs who liked this user
            $likeRecords = UserLike::where('liked_user_id', $userId)
                                  ->where('is_active', true)
                                  ->pluck('user_id');

            if ($likeRecords->isEmpty()) {
                return collect([]); // Return empty collection
            }

            // Get the actual user records
            $users = User::whereIn('id', $likeRecords)
                        ->where('deleted_flag', 'N')
                        ->select('id', 'name', 'username', 'profile', 'profile_url', 'bio')
                        ->limit($limit)
                        ->get();

            return $users;

        } catch (\Exception $e) {
            \Log::error('UserLikeHelper::getUsersWhoLikedMe error: ' . $e->getMessage());
            return collect([]); // Return empty collection on error
        }
    }

    public static function hasUserLiked($userId, $likedUserId, $type = 'profile')
    {
        return UserLike::where('user_id', $userId)
                      ->where('liked_user_id', $likedUserId)
                      ->where('type', $type)
                      ->where('is_active', true)
                      ->exists();
    }

    public static function getMutualLikes($userId)
    {
        // Users who liked me and I liked back
        return UserLike::where('user_id', $userId)
                      ->where('is_active', true)
                      ->whereExists(function ($query) use ($userId) {
                          $query->select(DB::raw(1))
                                ->from('user_likes as ul2')
                                ->whereColumn('ul2.user_id', 'user_likes.liked_user_id')
                                ->where('ul2.liked_user_id', $userId)
                                ->where('ul2.is_active', true);
                      })
                      ->with(['likedUser' => function ($query) {
                          $query->select('id', 'name', 'username', 'profile', 'profile_url', 'bio');
                      }])
                      ->get()
                      ->pluck('likedUser');
    }
}
