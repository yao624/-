<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbBmResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'name' => $this->name,
            'created_time' => $this->created_time,
            'timezone_id' => $this->timezone_id,
            'verification_status' => $this->verification_status,
            'two_factor_type' => $this->two_factor_type,
            'notes' => $this->notes,
            'users' => FbBusinessUserResource::collection($this->whenLoaded('fbBusinessUsers')),
            'users_count' => $this->fbBusinessUsers->count(),
            'ad_accounts' => FbAdAccountForBmResource::collection($this->whenLoaded('fbAdAccounts')),
            'ad_accounts_count' => $this->fbAdAccounts->count(),
            'is_disabled_for_integrity_reasons' => $this->is_disabled_for_integrity_reasons, # TODO: 这个字段只对 admin 可见
            'fb_api_token' => FbApiTokenResource2::collection($this->fbApiTokens),
            'pixels' => $this->Pixels,
            'pages' => FbPageForBmResource::collection($this->fbPages),
            'catalogs' => FbCatalogResource::collection($this->catalogs),
            'apps' => FbAppResource::collection($this->whenLoaded('fbApps')),
            'apps_count' => $this->fbApps->count(),
            'tags' => TagResource::collection($this->tags)
        ];
    }
}
