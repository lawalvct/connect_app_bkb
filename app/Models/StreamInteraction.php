<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'stream_id',
        'user_id',
        'interaction_type',
        'share_platform',
        'share_metadata',
    ];

    protected $casts = [
        'share_metadata' => 'array',
    ];

    const INTERACTION_LIKE = 'like';
    const INTERACTION_DISLIKE = 'dislike';
    const INTERACTION_SHARE = 'share';

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for like interactions
     */
    public function scopeLikes($query)
    {
        return $query->where('interaction_type', self::INTERACTION_LIKE);
    }

    /**
     * Scope for dislike interactions
     */
    public function scopeDislikes($query)
    {
        return $query->where('interaction_type', self::INTERACTION_DISLIKE);
    }

    /**
     * Scope for share interactions
     */
    public function scopeShares($query)
    {
        return $query->where('interaction_type', self::INTERACTION_SHARE);
    }

    /**
     * Scope for specific stream
     */
    public function scopeForStream($query, $streamId)
    {
        return $query->where('stream_id', $streamId);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if user has liked a stream
     */
    public static function hasUserLiked($streamId, $userId): bool
    {
        return static::forStream($streamId)
            ->forUser($userId)
            ->likes()
            ->exists();
    }

    /**
     * Check if user has disliked a stream
     */
    public static function hasUserDisliked($streamId, $userId): bool
    {
        return static::forStream($streamId)
            ->forUser($userId)
            ->dislikes()
            ->exists();
    }

    /**
     * Get user's interaction with a stream
     */
    public static function getUserInteraction($streamId, $userId): ?string
    {
        $interaction = static::forStream($streamId)
            ->forUser($userId)
            ->whereIn('interaction_type', [self::INTERACTION_LIKE, self::INTERACTION_DISLIKE])
            ->first();

        return $interaction?->interaction_type;
    }

    /**
     * Toggle like for a stream
     */
    public static function toggleLike($streamId, $userId): array
    {
        $existingInteraction = static::forStream($streamId)
            ->forUser($userId)
            ->whereIn('interaction_type', [self::INTERACTION_LIKE, self::INTERACTION_DISLIKE])
            ->first();

        if ($existingInteraction) {
            if ($existingInteraction->interaction_type === self::INTERACTION_LIKE) {
                // Remove like
                $existingInteraction->delete();
                static::updateStreamCounts($streamId);
                return ['action' => 'removed', 'type' => 'like'];
            } else {
                // Change dislike to like
                $existingInteraction->update(['interaction_type' => self::INTERACTION_LIKE]);
                static::updateStreamCounts($streamId);
                return ['action' => 'changed', 'type' => 'like', 'from' => 'dislike'];
            }
        } else {
            // Add new like
            static::create([
                'stream_id' => $streamId,
                'user_id' => $userId,
                'interaction_type' => self::INTERACTION_LIKE,
            ]);
            static::updateStreamCounts($streamId);
            return ['action' => 'added', 'type' => 'like'];
        }
    }

    /**
     * Toggle dislike for a stream
     */
    public static function toggleDislike($streamId, $userId): array
    {
        $existingInteraction = static::forStream($streamId)
            ->forUser($userId)
            ->whereIn('interaction_type', [self::INTERACTION_LIKE, self::INTERACTION_DISLIKE])
            ->first();

        if ($existingInteraction) {
            if ($existingInteraction->interaction_type === self::INTERACTION_DISLIKE) {
                // Remove dislike
                $existingInteraction->delete();
                static::updateStreamCounts($streamId);
                return ['action' => 'removed', 'type' => 'dislike'];
            } else {
                // Change like to dislike
                $existingInteraction->update(['interaction_type' => self::INTERACTION_DISLIKE]);
                static::updateStreamCounts($streamId);
                return ['action' => 'changed', 'type' => 'dislike', 'from' => 'like'];
            }
        } else {
            // Add new dislike
            static::create([
                'stream_id' => $streamId,
                'user_id' => $userId,
                'interaction_type' => self::INTERACTION_DISLIKE,
            ]);
            static::updateStreamCounts($streamId);
            return ['action' => 'added', 'type' => 'dislike'];
        }
    }

    /**
     * Add share interaction (allows multiple shares)
     */
    public static function addShare($streamId, $userId, $platform = null, $metadata = null): StreamInteraction
    {
        $share = static::create([
            'stream_id' => $streamId,
            'user_id' => $userId,
            'interaction_type' => self::INTERACTION_SHARE,
            'share_platform' => $platform,
            'share_metadata' => $metadata,
        ]);

        static::updateStreamCounts($streamId);
        return $share;
    }

    /**
     * Update stream interaction counts
     */
    public static function updateStreamCounts($streamId): void
    {
        $stream = Stream::find($streamId);
        if (!$stream) return;

        $likesCount = static::forStream($streamId)->likes()->count();
        $dislikesCount = static::forStream($streamId)->dislikes()->count();
        $sharesCount = static::forStream($streamId)->shares()->count();

        $stream->update([
            'likes_count' => $likesCount,
            'dislikes_count' => $dislikesCount,
            'shares_count' => $sharesCount,
        ]);
    }
}
