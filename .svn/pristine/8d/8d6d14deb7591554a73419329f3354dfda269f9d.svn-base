<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionResource extends JsonResource
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
            'transaction_id' => $this->transaction_id,
            'conversion_datetime' => $this->conversion_datetime,
            'network_id' => $this->network_id,
            'network_name' => $this->network->name,
            'offer_source_id' => $this->offer_source_id,
            'offer_source_name' => $this->offer_source_name,
            'sub_1' => $this->sub_1,
            'sub_2' => $this->sub_2,
            'sub_3' => $this->sub_3,
            'sub_4' => $this->sub_4,
            'sub_5' => $this->sub_5,
            'ip' => $this->ip,
            'fb_campaign_source_id' => $this->fb_campaign_source_id,
            'fb_adset_source_id' => $this->fb_adset_source_id,
            'fb_ad_source_id' => $this->fb_ad_source_id,
            'fb_pixel_number' => $this->fb_pixel_number,
            'price' => $this->price,
            'aff_id' => $this->aff_id,
            'country_code' => $this->country_code
        ];
    }
}
