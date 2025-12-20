<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Advertisement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'duration_seconds',
        'skip_after_seconds',
        'click_url',
        'is_active',
        'start_date',
        'end_date',
        'max_impressions',
        'current_impressions',
        'cpm_rate',
        'targeting'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'targeting' => 'array'
    ];

    public function streams(): BelongsToMany
    {
        return $this->belongsToMany(Stream::class)
            ->withPivot(['shown_at', 'stream_position_seconds', 'views', 'clicks', 'skips'])
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->is_active
            && now()->between($this->start_date, $this->end_date)
            && (!$this->max_impressions || $this->current_impressions < $this->max_impressions);
    }

    public function recordImpression(): void
    {
        $this->increment('current_impressions');
    }

    public function recordClick(int $streamId): void
    {
        $this->streams()
            ->wherePivot('stream_id', $streamId)
            ->increment('clicks');
    }

    public function recordSkip(int $streamId): void
    {
        $this->streams()
            ->wherePivot('stream_id', $streamId)
            ->increment('skips');
    }
}
