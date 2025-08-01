<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtmpStream extends Model
{
    protected $fillable = [
        'stream_id',
        'rtmp_url',
        'stream_key',
        'software_type',
        'resolution',
        'bitrate',
        'fps',
        'is_active',
        'last_heartbeat',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_heartbeat' => 'datetime',
        'metadata' => 'array'
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function generateStreamKey(): string
    {
        return $this->stream_id . '_' . bin2hex(random_bytes(16));
    }

    public function getFullRtmpUrl(): string
    {
        return rtrim($this->rtmp_url, '/') . '/' . $this->stream_key;
    }

    public function isStreamActive(): bool
    {
        return $this->is_active &&
               $this->last_heartbeat &&
               $this->last_heartbeat->gt(now()->subSeconds(30));
    }
}
