<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackerCampaignResource extends JsonResource
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
            'notes' => $this->notes,
            'tracker_id' => $this->tracker_id,
            'campaign_name' => $this->campaign_name,
            'campaign_source_id' => $this->campaign_source_id,
            'alias' => $this->alias,
        ];
    }
}
