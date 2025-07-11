<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type ?? 'unknown',
            'filename' => $this->filename ?? $this->file_name ?? $this->profile,
            'url' => $this->url ?? $this->file_url ?? $this->profile_url,
            'full_url' => $this->full_url ?? url($this->file_url ?? $this->profile_url),
            'file_type' => $this->file_type ?? 'image',
            'is_main' => $this->is_main ?? false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
