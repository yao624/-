<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbCatalogResource extends JsonResource
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
            'products' => FbCatalogProductResource::collection($this->products),
            'product_sets' => FbCatalogProductSetResource::collection($this->productSets),
            'role' => $this->pivot->role,
            'relation' => $this->pivot->relation,
            'pixels' => FbPixelForCatalogResource::collection($this->pixels),
            'fb_bm_id' => $this->when(isset($this->pivot), function () {
                return $this->pivot->fb_bm_id;
            }),
            'tags' => TagResource::collection($this->tags)
        ];
    }
}
