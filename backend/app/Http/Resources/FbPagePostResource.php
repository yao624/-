<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbPagePostResource extends JsonResource
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
            'primary_text' => $this->primary_text,
            'headline' => $this->headline,
            'description' => $this->description,
            'post_type' => $this->post_type,
            'url' => $this->url,
            'permalink_url' => $this->permalink_url,
            'created_time' => $this->created_time,
            'source_id' => $this->source_id,
            'campaign_source_id' => $this->campaign_source_id,
            'adset_source_id' => $this->adset_source_id,
            'ad_source_id' => $this->ad_source_id,
            'page_source_id' => $this->page_source_id,
            'ad_account_source_id' => $this->ad_account_source_id,
        ];
    }
}
