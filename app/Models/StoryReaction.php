<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'user_id',
        'reaction_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Supported reaction types
    public static function supportedReactions(): array
    {
        return [
            'like' => '👍',
            'love' => '❤️',
            'haha' => '😂',
            'wow' => '😮',
            'sad' => '😢',
            'angry' => '😠',
            'fire' => '🔥',
            'clap' => '👏',
            'heart_eyes' => '😍',
            'thinking' => '🤔',
        ];
    }

    /**
     * Check if reaction type is valid
     */
    public static function isValidReaction(string $reactionType): bool
    {
        return array_key_exists($reactionType, self::supportedReactions());
    }
}
