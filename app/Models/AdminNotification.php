<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AdminNotification extends Model
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
        'admin_id',
        'icon'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function admin()
    {
        return $this->belongsTo(Admin::class);
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

    public function scopeForAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId)->orWhereNull('admin_id');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
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
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Static methods
    public static function createForAllAdmins($data)
    {
        return self::create(array_merge($data, ['admin_id' => null]));
    }

    public static function createForAdmin($adminId, $data)
    {
        return self::create(array_merge($data, ['admin_id' => $adminId]));
    }
}
