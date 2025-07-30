<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamMixerSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'stream_id',
        'layout_type',
        'transition_effect',
        'mixer_config',
        'transition_duration',
    ];

    protected $casts = [
        'mixer_config' => 'array',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    // Default mixer configuration
    public static function getDefaultConfig(): array
    {
        return [
            'layout_type' => 'single',
            'transition_effect' => 'cut',
            'transition_duration' => 1000,
            'mixer_config' => [
                'audio_mixing' => true,
                'auto_switch' => false,
                'preview_enabled' => true,
                'recording_enabled' => false,
            ]
        ];
    }

    // Create default settings for a stream
    public static function createDefault($streamId): self
    {
        $config = self::getDefaultConfig();
        $config['stream_id'] = $streamId;

        return self::create($config);
    }
}
