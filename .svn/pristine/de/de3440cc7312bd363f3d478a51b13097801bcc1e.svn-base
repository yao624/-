<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubidMappingResource extends JsonResource
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
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'subid_1' => $this->subid_1,
            'subid_2' => $this->subid_2,
            'subid_3' => $this->subid_3,
            'subid_4' => $this->subid_4,
            'subid_5' => $this->subid_5,
            'fb_campaign_id' => $this->fb_campaign_id,
            'fb_adset_id' => $this->fb_adset_id,
            'fb_ad_id' => $this->fb_ad_id,
            'user' => $this->user,
        ];
    }
}
