<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardProviderResource extends JsonResource
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
            'nick_name' => $this->nick_name, // 前端只看到nick_name，不返回真实的name
            'active' => $this->active,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'card_bins' => CardBinResource::collection($this->whenLoaded('cardBins')),
            'card_bins_count' => $this->when($this->relationLoaded('cardBins'), function () {
                return $this->cardBins->count();
            }),
            'active_card_bins_count' => $this->when($this->relationLoaded('cardBins'), function () {
                return $this->cardBins->where('active', true)->count();
            }),
        ];
    }
}
