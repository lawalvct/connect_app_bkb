<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'likes_count',
        'dislikes_count',
        'shares_count',
        'free_minutes',
        'stream_type',
        'go_live_immediately',
        'scheduled_at',
        'started_at',
        'ended_at',
        'is_recorded',
        'video_file',
        'video_url',
        'video_duration',
        'is_downloadable',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'go_live_immediately' => 'boolean',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_recorded' => 'boolean',
        'is_downloadable' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
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

    // Stream interaction relationships
    public function interactions(): HasMany
    {
        return $this->hasMany(StreamInteraction::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(StreamInteraction::class)->where('interaction_type', 'like');
    }

    public function dislikes(): HasMany
    {
        return $this->hasMany(StreamInteraction::class)->where('interaction_type', 'dislike');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(StreamInteraction::class)->where('interaction_type', 'share');
    }

    // Alias methods for backward compatibility with views
    public function streamViewers(): HasMany
    {
        return $this->viewers();
    }

    public function streamChats(): HasMany
    {
        return $this->chats();
    }

    public function streamPayments(): HasMany
    {
        return $this->payments();
    }

    // Multi-camera relationships
    public function cameras(): HasMany
    {
        return $this->hasMany(StreamCamera::class);
    }

    public function activeCameras(): HasMany
    {
        return $this->hasMany(StreamCamera::class)->where('is_active', true);
    }

    public function primaryCamera(): HasMany
    {
        return $this->hasMany(StreamCamera::class)->where('is_primary', true);
    }

    public function cameraSwithces(): HasMany
    {
        return $this->hasMany(CameraSwitch::class);
    }

    public function mixerSettings(): HasOne
    {
        return $this->hasOne(StreamMixerSetting::class);
    }

    public function rtmpStream(): HasOne
    {
        return $this->hasOne(RtmpStream::class);
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

    public function scopeRecorded($query)
    {
        return $query->where('is_recorded', true);
    }

    public function scopeLiveStreams($query)
    {
        return $query->where('is_recorded', false);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->where('is_recorded', false)
              ->orWhere(function ($subQuery) {
                  $subQuery->where('is_recorded', true)
                           ->where(function ($dateQuery) {
                               $dateQuery->whereNull('available_from')
                                        ->orWhere('available_from', '<=', now());
                           })
                           ->where(function ($dateQuery) {
                               $dateQuery->whereNull('available_until')
                                        ->orWhere('available_until', '>=', now());
                           });
              });
        });
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
            // Check if user has already paid
            if ($this->completedPayments()->where('user_id', $user->id)->exists()) {
                return true;
            }

            // Check if stream has free minutes
            if ($this->free_minutes > 0) {
                return true; // Allow join, but will check duration later
            }

            // No free minutes and hasn't paid
            return false;
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

    public function hasUserExceededFreeMinutes(User $user): bool
    {
        // If not paid stream or user has paid, they haven't exceeded
        if (!$this->is_paid || $this->hasUserPaid($user)) {
            return false;
        }

        // If no free minutes, they've exceeded from the start
        if (!$this->free_minutes || $this->free_minutes <= 0) {
            return true;
        }

        // Get the first viewer record for this user
        $viewer = $this->viewers()->where('user_id', $user->id)->orderBy('joined_at', 'asc')->first();

        if (!$viewer || !$viewer->joined_at) {
            return false; // First time joining
        }

        // Calculate minutes watched
        $minutesWatched = $viewer->joined_at->diffInMinutes(now());

        return $minutesWatched >= $this->free_minutes;
    }

    // Multi-camera methods
    public function addCamera(string $cameraName, string $deviceType = null): StreamCamera
    {
        return StreamCamera::create([
            'stream_id' => $this->id,
            'camera_name' => $cameraName,
            'stream_key' => StreamCamera::generateStreamKey(),
            'device_type' => $deviceType,
            'agora_uid' => StreamCamera::generateAgoraUid(),
            'is_active' => false,
            'is_primary' => $this->cameras()->count() === 0, // First camera is primary
            'resolution' => '720p',
        ]);
    }

    public function switchToCamera(int $cameraId, int $switchedBy): bool
    {
        $camera = $this->cameras()->find($cameraId);

        if (!$camera || !$camera->is_active) {
            return false;
        }

        $currentPrimary = $this->primaryCamera()->first();

        // Record the switch
        CameraSwitch::create([
            'stream_id' => $this->id,
            'from_camera_id' => $currentPrimary?->id,
            'to_camera_id' => $cameraId,
            'switched_by' => $switchedBy,
            'switched_at' => now(),
        ]);

        // Make the camera primary
        return $camera->markAsPrimary();
    }

    public function getCurrentCamera(): ?StreamCamera
    {
        return $this->primaryCamera()->first();
    }

    public function initializeMixerSettings(): StreamMixerSetting
    {
        return $this->mixerSettings ?: StreamMixerSetting::createDefault($this->id);
    }

    public function hasMultipleCameras(): bool
    {
        return $this->cameras()->count() > 1;
    }

    public function getActiveCameraCount(): int
    {
        return $this->activeCameras()->count();
    }

    // RTMP Stream methods
    public function createRtmpStream($softwareType = 'manycam'): RtmpStream
    {
        return $this->rtmpStream()->create([
            'rtmp_url' => 'rtmp://admin.connectinc.app/live',
            'stream_key' => $this->id . '_' . bin2hex(random_bytes(16)),
            'software_type' => $softwareType,
            'resolution' => config('streaming.streaming_software.default_resolution', '1920x1080'),
            'bitrate' => config('streaming.streaming_software.default_bitrate', 3000),
            'fps' => config('streaming.streaming_software.default_fps', 30),
            'is_active' => false
        ]);
    }

    public function getRtmpConnectionDetails(): array
    {
        $rtmpStream = $this->rtmpStream;

        if (!$rtmpStream) {
            $rtmpStream = $this->createRtmpStream();
        }

        return [
            'rtmp_url' => $rtmpStream->rtmp_url,
            'stream_key' => $rtmpStream->stream_key,
            'full_url' => $rtmpStream->getFullRtmpUrl()
        ];
    }

    // Stream interaction methods
    public function hasUserLiked(User $user): bool
    {
        return StreamInteraction::hasUserLiked($this->id, $user->id);
    }

    public function hasUserDisliked(User $user): bool
    {
        return StreamInteraction::hasUserDisliked($this->id, $user->id);
    }

    public function getUserInteraction(User $user): ?string
    {
        return StreamInteraction::getUserInteraction($this->id, $user->id);
    }

    public function toggleLike(User $user): array
    {
        return StreamInteraction::toggleLike($this->id, $user->id);
    }

    public function toggleDislike(User $user): array
    {
        return StreamInteraction::toggleDislike($this->id, $user->id);
    }

    public function addShare(User $user, string $platform = null, array $metadata = null): StreamInteraction
    {
        return StreamInteraction::addShare($this->id, $user->id, $platform, $metadata);
    }

    public function getInteractionStats(): array
    {
        return [
            'likes_count' => $this->likes_count ?? 0,
            'dislikes_count' => $this->dislikes_count ?? 0,
            'shares_count' => $this->shares_count ?? 0,
        ];
    }

    // Guest user methods
    public function canGuestJoin(User $guest): bool
    {
        return $this->canUserJoin($guest);
    }

    public function addGuestViewer(User $guest, string $agoraUid = null, string $agoraToken = null)
    {
        return $this->addViewer($guest, $agoraUid, $agoraToken);
    }

    public function hasGuestPaid(User $guest): bool
    {
        return $this->hasUserPaid($guest);
    }

    // Recorded video methods
    public function isAvailable(): bool
    {
        if (!$this->is_recorded) {
            return true; // Live streams are always available when they're live
        }

        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false; // Not yet available
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false; // No longer available
        }

        return true;
    }

    public function getFormattedDuration(): string
    {
        if (!$this->video_duration) {
            return 'N/A';
        }

        $hours = floor($this->video_duration / 3600);
        $minutes = floor(($this->video_duration % 3600) / 60);
        $seconds = $this->video_duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getAvailabilityStatus(): string
    {
        if (!$this->is_recorded) {
            return $this->status;
        }

        if (!$this->isAvailable()) {
            $now = now();
            if ($this->available_from && $now->lt($this->available_from)) {
                return 'scheduled';
            }
            return 'expired';
        }

        return 'available';
    }
}
