<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbCatalogForAssignedBusinessUserResource extends JsonResource
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
            'role' => $this->pivot->role,
            'relation' => $this->pivot->relation,
            'product_sets' => FbCatalogProductSetResource::collection($this->productSets)
        ];
    }
}
