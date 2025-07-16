<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\SocialCircle;
use App\Models\UserSocialCircle;
use Illuminate\Support\Facades\DB;

class SocialCircleHelper
{
    /**
     * Assign default social circles to a new user
     *
     * @param User $user
     * @return void
     */
    public static function assignDefaultCirclesToUser(User $user): void
    {
        // Get default social circle IDs
        // These could be configured or determined by your system
        $defaultCircles = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 23, 26, 27, 28, 29];

        foreach ($defaultCircles as $circleId) {
            UserSocialCircle::create([
                'user_id' => $user->id,
                'social_id' => $circleId
            ]);
        }
    }

    /**
     * Get all social circles as an array
     *
     * @return array
     */
    public static function getSocialArray(): array
    {
        $socialCircles = SocialCircle::all();
        $result = [];

        foreach ($socialCircles as $circle) {
            $result[$circle->id] = $circle->name;
        }

        return $result;
    }

    /**
     * Get social circles by user ID
     *
     * @param int $userId
     * @return array
     */
    public static function getByUserId(int $userId): array
    {
        return UserSocialCircle::where('user_id', $userId)
            ->where('deleted_flag', 'N')
            ->pluck('social_id')
            ->toArray();
    }

    /**
     * Get detailed social circles for a user
     *
     * @param int $userId
     * @return array
     */
    public static function getUserSocialCircles(int $userId)
    {
        try {
            $socialCircles = SocialCircle::join('user_social_circles', 'social_circles.id', '=', 'user_social_circles.social_id')
                ->where('user_social_circles.user_id', $userId)
                ->where('user_social_circles.deleted_flag', 'N')
                ->where('social_circles.is_active', true)
                ->select('social_circles.*')
                ->get();

            return $socialCircles->map(function($circle) {
                return [
                    'id' => $circle->id,
                    'name' => $circle->name,
                    'description' => $circle->description,
                    'logo' => $circle->logo,
                    'logo_url' => $circle->logo_full_url,
                    'color' => $circle->color,
                    'is_default' => $circle->is_default,
                    'is_private' => $circle->is_private,
                    'order_by' => $circle->order_by
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error getting user social circles', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get users count by social circle
     *
     * @param int $socialCircleId
     * @return int
     */
    public static function getUsersCountBySocialCircle(int $socialCircleId): int
    {
        return UserSocialCircle::where('social_id', $socialCircleId)
            ->where('deleted_flag', 'N')
            ->count();
    }

    /**
     * Get popular social circles
     *
     * @param int $limit
     * @return array
     */
    public static function getPopularSocialCircles(int $limit = 10)
    {
        try {
            $popularCircles = DB::table('user_social_circles')
                ->select('social_id', DB::raw('COUNT(*) as user_count'))
                ->where('deleted_flag', 'N')
                ->groupBy('social_id')
                ->orderBy('user_count', 'desc')
                ->limit($limit)
                ->get();

            $result = [];
            foreach ($popularCircles as $item) {
                $circle = SocialCircle::find($item->social_id);
                if ($circle) {
                    $result[] = [
                        'id' => $circle->id,
                        'name' => $circle->name,
                        'description' => $circle->description,
                        'logo' => $circle->logo,
                        'logo_url' => $circle->logo_full_url,
                        'color' => $circle->color,
                        'user_count' => $item->user_count
                    ];
                }
            }
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error getting popular social circles', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Add user to social circle
     *
     * @param int $userId
     * @param int $socialCircleId
     * @return bool
     */
    public static function addUserToSocialCircle(int $userId, int $socialCircleId): bool
    {
        try {
            // Check if already exists
            $exists = UserSocialCircle::where('user_id', $userId)
                ->where('social_id', $socialCircleId)
                ->exists();

            if ($exists) {
                // If soft deleted, restore it
                $record = UserSocialCircle::withTrashed()
                    ->where('user_id', $userId)
                    ->where('social_id', $socialCircleId)
                    ->first();

                if ($record) {
                    if ($record->trashed() || $record->deleted_flag === 'Y') {
                        $record->deleted_flag = 'N';
                        $record->deleted_at = null;
                        $record->save();
                    }
                    return true;
                }
            }

            // Create new record
            UserSocialCircle::create([
                'user_id' => $userId,
                'social_id' => $socialCircleId
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error adding user to social circle', [
                'user_id' => $userId,
                'social_circle_id' => $socialCircleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove user from social circle
     *
     * @param int $userId
     * @param int $socialCircleId
     * @return bool
     */
    public static function removeUserFromSocialCircle(int $userId, int $socialCircleId): bool
    {
        try {
            $record = UserSocialCircle::where('user_id', $userId)
                ->where('social_id', $socialCircleId)
                ->first();

            if ($record) {
                $record->deleted_flag = 'Y';
                $record->save();
                $record->delete();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('Error removing user from social circle', [
                'user_id' => $userId,
                'social_circle_id' => $socialCircleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
