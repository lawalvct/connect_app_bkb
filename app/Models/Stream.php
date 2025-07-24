<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Stream extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel_name',
        'title',
        'description',
        'banner_image',
        'banner_image_url',
        'status',
        'is_paid',
        'price',
        'currency',
        'max_viewers',
        'current_viewers',
        'scheduled_at',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $appends = ['is_live', 'viewer_count', 'duration'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function viewers(): HasMany
    {
        return $this->hasMany(StreamViewer::class);
    }

    public function activeViewers(): HasMany
    {
        return $this->hasMany(StreamViewer::class)->where('is_active', true);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(StreamChat::class)->orderBy('created_at', 'asc');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StreamPayment::class);
    }

    public function completedPayments(): HasMany
    {
        return $this->hasMany(StreamPayment::class)->where('status', 'completed');
    }

    // Scopes
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getIsLiveAttribute(): bool
    {
        return $this->status === 'live';
    }

    public function getViewerCountAttribute(): int
    {
        return $this->activeViewers()->count();
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->ended_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    // Methods
    public function generateChannelName(): string
    {
        return 'stream_' . $this->id . '_' . Str::random(8);
    }

    public function start(): bool
    {
        if ($this->status !== 'upcoming') {
            return false;
        }

        $this->update([
            'status' => 'live',
            'started_at' => now(),
        ]);

        return true;
    }

    public function end(): bool
    {
        if ($this->status !== 'live') {
            return false;
        }

        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
            'current_viewers' => 0,
        ]);

        // Mark all viewers as inactive
        $this->viewers()->where('is_active', true)->update([
            'is_active' => false,
            'left_at' => now(),
        ]);

        return true;
    }

    public function addViewer(User $user, string $agoraUid = null, string $agoraToken = null): StreamViewer
    {
        $viewer = $this->viewers()->where('user_id', $user->id)->first();

        if ($viewer) {
            // Reactivate existing viewer
            $viewer->update([
                'is_active' => true,
                'joined_at' => now(),
                'left_at' => null,
                'agora_uid' => $agoraUid,
                'agora_token' => $agoraToken,
            ]);
        } else {
            // Create new viewer
            $viewer = $this->viewers()->create([
                'user_id' => $user->id,
                'agora_uid' => $agoraUid,
                'agora_token' => $agoraToken,
                'joined_at' => now(),
                'is_active' => true,
            ]);
        }

        $this->updateViewerCount();
        return $viewer;
    }

    public function removeViewer(User $user): bool
    {
        $viewer = $this->viewers()->where('user_id', $user->id)->where('is_active', true)->first();

        if ($viewer) {
            $viewer->update([
                'is_active' => false,
                'left_at' => now(),
            ]);

            $this->updateViewerCount();
            return true;
        }

        return false;
    }

    public function updateViewerCount(): void
    {
        $count = $this->activeViewers()->count();
        $this->update(['current_viewers' => $count]);
    }

    public function canUserJoin(User $user): bool
    {
        if ($this->status !== 'live') {
            return false;
        }

        if ($this->is_paid) {
            return $this->completedPayments()->where('user_id', $user->id)->exists();
        }

        return true;
    }

    public function hasUserPaid(User $user): bool
    {
        if (!$this->is_paid) {
            return true;
        }

        return $this->completedPayments()->where('user_id', $user->id)->exists();
    }
}
