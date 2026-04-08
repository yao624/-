<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackerOfferClickResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tracker_id' => $this->tracker_id,
            'tracker_campaign_id' => $this->tracker_campaign_id,
            'campaign_source_id' => $this->campaign_source_id,
            'subid' => $this->subid,
            'ip' => $this->ip,
            'sub_1' => $this->sub_1,
            'sub_2' => $this->sub_2,
            'sub_3' => $this->sub_3,
            'sub_4' => $this->sub_4,
            'sub_5' => $this->sub_5,
        ];
    }
}
