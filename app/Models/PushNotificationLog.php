<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'user_id',
        'fcm_token',
        'title',
        'body',
        'data',
        'status',
        'response',
        'error_message',
        'sent_at',
        'delivered_at',
        'clicked_at',
        'platform'
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'clicked_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    // Methods
    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    public function markAsClicked()
    {
        $this->update([
            'status' => 'clicked',
            'clicked_at' => now()
        ]);
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'sent' => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'clicked' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}
