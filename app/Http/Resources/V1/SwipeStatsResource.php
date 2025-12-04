<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class SwipeStatsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'date' => $this->swipe_date ? $this->swipe_date->toDateString() : null,
            'right_swipes' => $this->right_swipes,
            'left_swipes' => $this->left_swipes,
            'super_likes' => $this->super_likes,
            'total_swipes' => $this->total_swipes,
            'swipe_limit' => $this->swipe_limit,
            'remaining_swipes' => $this->remaining_swipes,
            'resets_at' => $this->resets_at ?? null,
            'has_boost' => $this->has_boost ?? false,
        ];
    }
}
