<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbPageFormResource extends JsonResource
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
            'source_id' => $this->source_id,
            'local' => $this->local,
            'name' => $this->name,
            'status' => $this->status,
            'created_time' => $this->created_time,
            'thank_you_page' => $this->thank_you_page,
            'privacy_policy_url' => $this->privacy_policy_url,
            'legal_content' => $this->legal_content,
            'follow_up_action_url' => $this->follow_up_action_url,
            'leads_count' => $this->leads_count,
            'page_source_id' => $this->page_source_id,
            'page_name' => $this->page_name,
            'notes' => $this->notes,
        ];
    }
}
