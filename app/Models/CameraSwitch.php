<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CameraSwitch extends Model
{
    use HasFactory;

    protected $fillable = [
        'stream_id',
        'from_camera_id',
        'to_camera_id',
        'switched_by',
        'switched_at',
    ];

    protected $casts = [
        'switched_at' => 'datetime',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function fromCamera(): BelongsTo
    {
        return $this->belongsTo(StreamCamera::class, 'from_camera_id');
    }

    public function toCamera(): BelongsTo
    {
        return $this->belongsTo(StreamCamera::class, 'to_camera_id');
    }

    public function switchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'switched_by');
    }

    // Scopes
    public function scopeByStream($query, $streamId)
    {
        return $query->where('stream_id', $streamId);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('switched_at', 'desc')->limit($limit);
    }
}
