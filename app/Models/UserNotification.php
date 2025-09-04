<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'data',
        'action_url',
        'is_read',
        'read_at',
        'user_id',
        'icon',
        'priority'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'priority' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
    }

    // Methods
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'success' => 'text-green-600',
            'warning' => 'text-yellow-600',
            'error' => 'text-red-600',
            'info' => 'text-blue-600',
            'welcome' => 'text-purple-600',
            'tutorial' => 'text-indigo-600',
            default => 'text-gray-600'
        };
    }

    public function getTypeBadgeAttribute()
    {
        return match($this->type) {
            'success' => 'bg-green-100 text-green-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'error' => 'bg-red-100 text-red-800',
            'info' => 'bg-blue-100 text-blue-800',
            'welcome' => 'bg-purple-100 text-purple-800',
            'tutorial' => 'bg-indigo-100 text-indigo-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Static methods
    public static function createWelcomeNotification($userId)
    {
        return self::create([
            'title' => 'Welcome to ConnectInc! 🎉',
            'message' => 'Welcome to ConnectInc! We are excited to have you join our community. Here are some tips to get you started: Complete your profile, upload photos, explore user discovery, and start connecting with people who share your interests.',
            'type' => 'welcome',
            'user_id' => $userId,
            'icon' => 'fa-heart',
            'priority' => 10,
            'data' => [
                'action_type' => 'welcome',
                'show_tutorial' => true
            ]
        ]);
    }

    public static function createTutorialNotification($userId)
    {
        return self::create([
            'title' => 'How to Use ConnectInc',
            'message' => 'Learn how to make the most of ConnectInc: 1) Complete your profile with photos and details 2) Join social circles that interest you 3) Discover and swipe on potential connections 4) Start conversations with your matches 5) Consider premium subscriptions for unlimited features',
            'type' => 'tutorial',
            'user_id' => $userId,
            'icon' => 'fa-graduation-cap',
            'priority' => 9,
            'data' => [
                'action_type' => 'tutorial',
                'steps' => [
                    'Complete Profile',
                    'Join Social Circles',
                    'Start Swiping',
                    'Make Connections',
                    'Upgrade to Premium'
                ]
            ]
        ]);
    }

    public static function createForUser($userId, $data)
    {
        return self::create(array_merge($data, ['user_id' => $userId]));
    }

    public static function getUnreadCountForUser($userId)
    {
        return self::where('user_id', $userId)->unread()->count();
    }
}
