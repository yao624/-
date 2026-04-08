<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbCatalogProductSetResource extends JsonResource
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
            'source_id' => $this->source_id,
            'name' => $this->name,
            'filter' => $this->filter,
            'products' => FbCatalogProductResource::collection($this->products),
            'tags' => TagResource::collection($this->tags)
        ];
    }
}
