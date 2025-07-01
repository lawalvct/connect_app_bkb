<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ad_name' => $this->ad_name,
            'type' => $this->type,
            'description' => $this->description,
            'media_files' => $this->media_files,
            'call_to_action' => $this->call_to_action,
            'destination_url' => $this->destination_url,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'target_audience' => $this->target_audience,
            'budget' => $this->budget,
            'daily_budget' => $this->daily_budget,
            'target_impressions' => $this->target_impressions,
            'current_impressions' => $this->current_impressions,
            'clicks' => $this->clicks,
            'conversions' => $this->conversions,
            'cost_per_click' => $this->cost_per_click,
            'total_spent' => $this->total_spent,
            'status' => $this->status,
            'admin_status' => $this->admin_status,
            'admin_comments' => $this->admin_comments,

            // Computed attributes
            'progress_percentage' => $this->progress_percentage,
            'ctr' => $this->ctr,
            'days_remaining' => $this->days_remaining,
            'is_active' => $this->is_active,
            'can_be_edited' => $this->can_be_edited,

            // Action permissions
            'can_pause' => $this->canBePaused(),
            'can_stop' => $this->canBeStopped(),
            'can_delete' => $this->canBeDeleted(),

            // Relationships
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'reviewer' => $this->when($this->reviewer, [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->name,
            ]),

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'activated_at' => $this->activated_at?->toISOString(),
            'paused_at' => $this->paused_at?->toISOString(),
            'stopped_at' => $this->stopped_at?->toISOString(),
        ];
    }
}
