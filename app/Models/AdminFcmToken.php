<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdminFcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'fcm_token',
        'push_endpoint',
        'push_p256dh',
        'push_auth',
        'subscription_type',
        'device_id',
        'device_name',
        'platform',
        'browser',
        'is_active',
        'notification_preferences',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notification_preferences' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the admin that owns the FCM token.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
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

    /**
     * Check if admin wants to receive specific notification type.
     */
    public function wantsNotification($type)
    {
        if (!$this->notification_preferences) {
            return true; // Default to receiving all notifications
        }

        return $this->notification_preferences[$type] ?? true;
    }

    /**
     * Default notification preferences.
     */
    public static function getDefaultPreferences()
    {
        return [
            'new_user_registrations' => true,
            'new_posts' => false,
            'new_ads' => true,
            'payment_notifications' => true,
            'system_alerts' => true,
            'user_reports' => true,
            'test_notifications' => true,
        ];
    }
}
