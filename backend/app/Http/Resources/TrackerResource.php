<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackerResource extends JsonResource
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
            'notes' => $this->notes,
            'name' => $this->name,
            'type' => $this->type,
            'username' => $this->username,
            'url' => $this->url,
            'is_archived' => $this->is_archived,
        ];
    }
}
