<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbPixelResource extends JsonResource
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
            'pixel' => $this->pixel,
            'is_dataset' => $this->is_dataset,
            'is_created_by_business' => boolval($this->is_created_by_business),
            'is_unavailable' => boolval($this->is_unavailable),
            'owner_business' => $this->owner_business,
            'creator' => $this->creator,
            'notes' => $this->notes,
            'tags' => TagResource::collection($this->tags),
        ];
    }
}
