<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbAdResource extends JsonResource
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
            'fb_campaign_id' => $this->fb_campaign_id,
            'fb_adset_id' => $this->fb_adset_id,
            'adset_id' => $this->adset_id,
            'campaign_id' => $this->campaign_id,
            'configured_status' => $this->configured_status,
            'created_time' => $this->created_time,
            'creative' => $this->creative,
            'effective_status' => $this->effective_status,
            'source_id' => $this->source_id,
            'name' => $this->name,
            'preview_shareable_link' => $this->preview_shareable_link,
            'source_ad_id' => $this->source_ad_id,
            'status' => $this->status,
            'post_url' => $this->post_url,
            'updated_time' => $this->updated_time,
            'is_deleted_on_fb' => $this->is_deleted_on_fb,
            'auto_add_languages' => $this->auto_add_languages,
            'notes' => $this->notes,
            'tags' => TagResource::collection($this->tags),
        ];
    }
}
