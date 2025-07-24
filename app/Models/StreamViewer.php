<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamViewer extends Model
{
    use HasFactory;

    protected $fillable = [
        'stream_id',
        'user_id',
        'agora_uid',
        'agora_token',
        'joined_at',
        'left_at',
        'is_active',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStream($query, $streamId)
    {
        return $query->where('stream_id', $streamId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Generate unique Agora UID
    public static function generateAgoraUid(): string
    {
        do {
            $uid = mt_rand(100000, 999999);
        } while (self::where('agora_uid', $uid)->exists());

        return (string)$uid;
    }
}
