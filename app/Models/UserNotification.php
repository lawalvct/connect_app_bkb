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
            'connection_request' => 'text-pink-600',
            'connection_accepted' => 'text-green-600',
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
            'connection_request' => 'bg-pink-100 text-pink-800',
            'connection_accepted' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Static methods
    public static function createWelcomeNotification($userId)
    {
        return self::create([
            'title' => 'Welcome to ConnectInc! 🎉💫',
            'message' => "🌟 Welcome to your new social universe! 🌟\n\n🔥 Ready to make genuine connections?\n\n✨ Swipe 👉 to send connection requests\n💬 Chat in real-time with your matches\n📞🎥 Make audio & video calls instantly\n📸 Share posts & stories with your network\n🎬 Watch exclusive live streams from creators\n💎 Upgrade to premium for unlimited swipes!\n\nYour journey to meaningful connections starts now! 🚀",
            'type' => 'welcome',
            'user_id' => $userId,
            'icon' => 'fa-heart',
            'priority' => 10,
            'data' => [
                'action_type' => 'welcome',
                'show_tutorial' => true,
                'features_highlighted' => [
                    'swiping',
                    'messaging',
                    'calling',
                    'stories',
                    'streaming',
                    'premium'
                ]
            ]
        ]);
    }

    public static function createTutorialNotification($userId)
    {
        return self::create([
            'title' => 'Master ConnectInc in 5 Steps! 🎯',
            'message' => "🚀 Your complete guide to ConnectInc success:\n\n1️⃣ 📱 **Build Your Profile**: Add stunning photos & write an engaging bio\n\n2️⃣ 🎯 **Join Social Circles**: Find your tribes & interests\n\n3️⃣ 💫 **Start Discovering**: Swipe 👉 to connect, 👈 to pass\n\n4️⃣ 💬 **Engage & Connect**: Chat, call, share posts & stories\n\n5️⃣ 🎬 **Explore Live Streams**: Watch exclusive content from creators\n\n💎 **Pro Tip**: Upgrade to premium for unlimited daily swipes and exclusive features!\n\nReady to connect? Let's go! 🔥",
            'type' => 'tutorial',
            'user_id' => $userId,
            'icon' => 'fa-graduation-cap',
            'priority' => 9,
            'data' => [
                'action_type' => 'tutorial',
                'tutorial_version' => '2.0',
                'steps' => [
                    'Build Your Amazing Profile',
                    'Join Relevant Social Circles',
                    'Discover & Swipe on Users',
                    'Chat, Call & Share Content',
                    'Watch Live Streams',
                    'Upgrade to Premium'
                ],
                'estimated_completion_time' => '5 minutes',
                'features_covered' => [
                    'profile_setup',
                    'social_circles',
                    'user_discovery',
                    'messaging',
                    'calling',
                    'posts_stories',
                    'live_streaming',
                    'premium_features'
                ]
            ]
        ]);
    }

    public static function createConnectionRequestNotification($senderId, $receiverId, $senderName, $requestId)
    {
        return self::create([
            'title' => 'New Connection Request! 💫',
            'message' => "🎉 {$senderName} sent you a connection request!\n\n✨ Check out their profile and see if you want to connect.\n\n💬 If you both swipe right, you can start chatting instantly!",
            'type' => 'connection_request',
            'user_id' => $receiverId,
            'icon' => 'fa-heart',
            'priority' => 8,
            'action_url' => '/connections/requests',
            'data' => [
                'action_type' => 'connection_request',
                'sender_id' => $senderId,
                'sender_name' => $senderName,
                'request_id' => $requestId
            ]
        ]);
    }

    public static function createConnectionAcceptedNotification($accepterId, $senderId, $accepterName, $requestId)
    {
        return self::create([
            'title' => 'Connection Accepted! 🎉',
            'message' => "🌟 Great news! {$accepterName} accepted your connection request!\n\n💬 You can now start chatting with each other.\n📞🎥 Make calls and share stories together!\n\nTime to break the ice! 🚀",
            'type' => 'connection_accepted',
            'user_id' => $senderId,
            'icon' => 'fa-check-circle',
            'priority' => 9,
            'action_url' => '/conversations',
            'data' => [
                'action_type' => 'connection_accepted',
                'accepter_id' => $accepterId,
                'accepter_name' => $accepterName,
                'request_id' => $requestId
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
