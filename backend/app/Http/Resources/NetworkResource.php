<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NetworkResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'name' => $this->name,
            'system_type' => $this->system_type,
            'aff_id' => $this->aff_id,
            'endpoint' => $this->endpoint,
            'apikey' => $this->apikey,
            'click_placeholder' => $this->click_placeholder,
            'notes' => $this->notes,
            'active' => boolval($this->active),
            'tags' => TagResource::collection($this->tags),
            'user_id' => $this->user_id,
            'is_subnetwork' => $this->is_subnetwork,
        ];
    }
}
