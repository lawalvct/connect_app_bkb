<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserFcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fcm_token',
        'device_id',
        'platform',
        'app_version',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the FCM token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active tokens.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include tokens for a specific platform.
     */
    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Mark the token as used.
     */
    public function markAsUsed()
    {
        $this->update(['last_used_at' => Carbon::now()]);
    }

    /**
     * Deactivate the token.
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check if the token is recent (used within last 30 days).
     */
    public function isRecent()
    {
        return $this->last_used_at && $this->last_used_at->gte(Carbon::now()->subDays(30));
    }
}
