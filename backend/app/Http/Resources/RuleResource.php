<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RuleResource extends JsonResource
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
            'name' => $this->name,
            'date_preset' => $this->date_preset,
            'scope' => $this->scope,
            'ad_account_ids' => $this->ad_account_ids,
            'relation' => $this->relation,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'white_list' => $this->white_list,
            'is_active' => $this->is_active,
            'resource_ids' => $this->resource_ids,
            'notes' => $this->notes,
            'fb_ad_accounts' => FbAdAccountResourceMode2::collection($this->fbAdAccounts),
            'fb_campaigns' => $this->whenLoaded('fbCampaigns'),
            'fb_adsets' => $this->whenLoaded('fbAdsets'),
            'fb_ads' => $this->whenLoaded('fbAds'),
            'tags' => TagResource::collection($this->tags),
        ];
    }
}
