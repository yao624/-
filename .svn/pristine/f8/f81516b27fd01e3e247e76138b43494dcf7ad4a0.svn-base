<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbCatalogProductResource extends JsonResource
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
            'currency' => $this->currency,
            'name' => $this->name,
            'source_id' => $this->source_id,
            'description' => $this->description,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'retailer_id' => $this->retailer_id,
            'price' => $this->price,
            'video_url' => $this->video_url,
            'catalog_id' => $this->catalog->id,
            'tags' => TagResource::collection($this->tags)
        ];
    }
}
