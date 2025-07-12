<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country_id' => $this->country_id,
            'country_code' => $this->country_code,
            'country_name' => $this->country_name,
            'state_code' => $this->state_code,
            'type' => $this->type,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
        ];
    }
}
