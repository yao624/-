<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbAdsetResource extends JsonResource
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
            'pixel_id' => $this->pixel_id,
            'account_id' => $this->account_id,
            'billing_event' => $this->billing_event,
            'budget_remaining' => $this->budget_remaining,
            'campaign_id' => $this->campaign_id,
            'configured_status' => $this->configured_status,
            'created_time' => $this->created_time,
            'daily_budget' => $this->daily_budget,
            'lifetime_budget' => $this->lifetime_budget,
            'effective_status' => $this->effective_status,
            'source_id' => $this->source_id,
            'is_dynamic_creative' => $this->is_dynamic_creative,
            'name' => $this->name,
            'optimization_goal' => $this->optimization_goal,
            'promoted_object' => $this->promoted_object,
            'source_adset_id' => $this->source_adset_id,
            'start_time' => $this->start_time,
            'status' => $this->status,
            'targeting' => $this->targeting,
            'notes' => $this->notes,
            'is_deleted_on_fb' => $this->is_deleted_on_fb,
            'tags' => TagResource::collection($this->tags),
        ];
    }
}
