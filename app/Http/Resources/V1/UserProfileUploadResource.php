<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileUploadResource extends JsonResource
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
            'file_name' => $this->file_name,
            'file_url' => $this->full_url, // Uses the accessor from the model
            'file_type' => $this->file_type,
            'caption' => $this->caption,
            'alt_text' => $this->alt_text,
            'tags' => $this->tags ?? [],
            'metadata' => $this->metadata ?? [],
            'formatted_file_size' => $this->formatted_file_size, // Uses the accessor
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
