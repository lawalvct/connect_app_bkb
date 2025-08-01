<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StreamCamera extends Model
{
    use HasFactory;

    protected $fillable = [
        'stream_id',
        'camera_name',
        'stream_key',
        'device_type',
        'device_id',
        'agora_uid',
        'is_active',
        'is_primary',
        'resolution',
        'connection_info',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'connection_info' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function switchesFrom(): HasMany
    {
        return $this->hasMany(CameraSwitch::class, 'from_camera_id');
    }

    public function switchesTo(): HasMany
    {
        return $this->hasMany(CameraSwitch::class, 'to_camera_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByStream($query, $streamId)
    {
        return $query->where('stream_id', $streamId);
    }

    // Static methods
    public static function generateStreamKey(): string
    {
        do {
            $key = 'CAM_' . strtoupper(Str::random(16));
        } while (self::where('stream_key', $key)->exists());

        return $key;
    }

    public static function generateAgoraUid(): int
    {
        do {
            $uid = mt_rand(100000, 999999);
        } while (self::where('agora_uid', $uid)->exists());

        return $uid;
    }

    // Methods
    public function markAsPrimary(): bool
    {
        // First, set all other cameras in the stream as non-primary
        self::where('stream_id', $this->stream_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Then mark this camera as primary
        return $this->update(['is_primary' => true]);
    }

    public function connect(): bool
    {
        return $this->update([
            'is_active' => true,
            'last_seen_at' => now(),
        ]);
    }

    public function disconnect(): bool
    {
        return $this->update([
            'is_active' => false,
            'last_seen_at' => now(),
        ]);
    }

    public function isOnline(): bool
    {
        if (!$this->is_active || !$this->last_seen_at) {
            return false;
        }

        // Consider camera offline if not seen for more than 30 seconds
        return $this->last_seen_at->diffInSeconds(now()) < 30;
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'disconnected';
        }

        if (!$this->isOnline()) {
            return 'offline';
        }

        if ($this->is_primary) {
            return 'live';
        }

        return 'connected';
    }
}
