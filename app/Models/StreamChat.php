<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'stream_id',
        'user_id',
        'username',
        'message',
        'user_profile_url',
        'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
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
    public function scopeByStream($query, $streamId)
    {
        return $query->where('stream_id', $streamId);
    }

    public function scopeAfter($query, $messageId)
    {
        return $query->where('id', '>', $messageId);
    }

    public function scopeBefore($query, $messageId)
    {
        return $query->where('id', '<', $messageId);
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
