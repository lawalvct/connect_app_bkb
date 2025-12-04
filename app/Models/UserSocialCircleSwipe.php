<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserSocialCircleSwipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_circle_id',
        'target_user_id',
        'swipe_type',
        'swiped_at'
    ];

    protected $casts = [
        'swiped_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialCircle()
    {
        return $this->belongsTo(SocialCircle::class);
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Record a swipe in a social circle
     */
    public static function recordSwipe($userId, $socialCircleId, $targetUserId, $swipeType = 'right_swipe')
    {
        return static::create([
            'user_id' => $userId,
            'social_circle_id' => $socialCircleId,
            'target_user_id' => $targetUserId,
            'swipe_type' => $swipeType,
            'swiped_at' => now()
        ]);
    }

    /**
     * Get swipe count for a user in a social circle within specific hours
     *
     * @param int $userId
     * @param int $socialCircleId
     * @param int $hours Number of hours to look back
     * @return int
     */
    public static function getSwipeCountWithinHours($userId, $socialCircleId, $hours = 12)
    {
        return static::where('user_id', $userId)
            ->where('social_circle_id', $socialCircleId)
            ->where('swiped_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    /**
     * Check if user can swipe in a social circle based on time limit
     *
     * @param int $userId
     * @param int $socialCircleId
     * @param int $limit Maximum swipes allowed
     * @param int $hours Time period in hours
     * @return array ['can_swipe' => bool, 'swipes_used' => int, 'limit' => int, 'resets_at' => datetime]
     */
    public static function canSwipeInCircle($userId, $socialCircleId, $limit = 10, $hours = 12)
    {
        $swipesUsed = static::getSwipeCountWithinHours($userId, $socialCircleId, $hours);

        // Get the oldest swipe in the current window to calculate reset time
        $oldestSwipe = static::where('user_id', $userId)
            ->where('social_circle_id', $socialCircleId)
            ->where('swiped_at', '>=', Carbon::now()->subHours($hours))
            ->orderBy('swiped_at', 'asc')
            ->first();

        $resetsAt = $oldestSwipe
            ? Carbon::parse($oldestSwipe->swiped_at)->addHours($hours)
            : Carbon::now()->addHours($hours);

        return [
            'can_swipe' => $swipesUsed < $limit,
            'swipes_used' => $swipesUsed,
            'limit' => $limit,
            'remaining_swipes' => max(0, $limit - $swipesUsed),
            'resets_at' => $resetsAt
        ];
    }

    /**
     * Check if user has already swiped on a specific target in this social circle
     */
    public static function hasSwipedOnUser($userId, $socialCircleId, $targetUserId)
    {
        return static::where('user_id', $userId)
            ->where('social_circle_id', $socialCircleId)
            ->where('target_user_id', $targetUserId)
            ->exists();
    }
}
