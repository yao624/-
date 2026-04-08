<?php

namespace App\Http\Resources;

use App\Models\FbAdAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdLogResource extends JsonResource
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
            'user' => $this->user->email ?? '',
            'is_success' => $this->is_success,
            'operator_type' => $this->operator_type,
            'ad_account' => new FbAdAccountResourceMode2($this->whenLoaded('adAccount')),
            'bm_user' => new FbApiTokenResource2($this->whenLoaded('adAccount')),
            'failed_reason' => $this->failed_reason,
            'campaigns' => $this->whenLoaded('campaignPivot', function() {
                return $this->campaignPivot->map(function($pivot) {
                    return [
                        'campaign_source_id' => $pivot->campaign_source_id,
                        'campaign_created' => $pivot->campaign_created,
                        'campaign_failed_reason' => $pivot->campaign_failed_reason,
                    ];
                });
            }),
            'adsets' => $this->whenLoaded('adsetPivot', function() {
                return $this->adsetPivot->map(function($pivot) {
                    return [
                        'adset_source_id' => $pivot->adset_source_id,
                        'adset_created' => $pivot->adset_created,
                        'adset_failed_reason' => $pivot->adset_failed_reason,
                    ];
                });
            }),
            'ads' => $this->whenLoaded('adPivot', function() {
                return $this->adsetPivot->map(function($pivot) {
                    return [
                        'ad_source_id' => $pivot->adset_source_id,
                        'ad_created' => $pivot->adset_created,
                        'ad_failed_reason' => $pivot->adset_failed_reason,
                    ];
                });
            }),
            'materials' => MaterialResource::collection($this->whenLoaded('materials')),
            'template' => new FbAdTemplateResource($this->whenLoaded('adTemplate')),
            'copywriting' => new CopywritingResource($this->whenLoaded('copywriting')),
            'link' => new LinkResource($this->whenLoaded('link')),
            'page' => new FbPageResource($this->whenLoaded('page')),
            'pixel' => new FbPixelResource($this->whenLoaded('pixel')),
        ];
    }
}
