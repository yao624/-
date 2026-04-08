<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbCampaignResource extends JsonResource
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
            'account_id' => $this->account_id,
            'fb_ad_account_id' => $this->fb_ad_account_id,
            'bid_strategy' => $this->bid_strategy,
            'budget_remaining' => $this->budget_remaining,
            'configured_status' => $this->configured_status,
            'created_time' => $this->created_time,
            'daily_budget' => $this->daily_budget,
            'lifetime_budget' => $this->lifetime_budget,
            'effective_status' => $this->effective_status,
            'source_id' => $this->source_id,
            'name' => $this->name,
            'objective' => $this->objective,
            'source_campaign_id' => $this->source_campaign_id,
            'start_time' => $this->start_time,
            'status' => $this->status,
            'updated_time' => $this->updated_time,
            'notes' => $this->notes,
            'tags' => TagResource::collection($this->tags),
            'is_archived' => $this->is_archived,
            'is_deleted_on_fb' => $this->is_deleted_on_fb,
        ];
    }
}
