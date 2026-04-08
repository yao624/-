<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardBinResource extends JsonResource
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
            'card_bin' => $this->card_bin,
            'card_type' => $this->card_type,
            'active' => $this->active,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'card_provider' => $this->when($this->relationLoaded('cardProvider'), function () {
                return [
                    'id' => $this->cardProvider->id,
                    'nick_name' => $this->cardProvider->nick_name, // 只返回nick_name
                    'active' => $this->cardProvider->active,
                ];
            }),
        ];
    }
}
