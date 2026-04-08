<?php

namespace App\Http\Resources;

use App\Models\FbAdAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class FbAdAccountWithCampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var FbAdAccount $model */
        $model = $this->resource;
        Log::info('FbAdAccountWithCampaignResource: toArray', [
            'fb_ad_account_id' => $model->id,
            'fbCampaigns_loaded' => $model->relationLoaded('fbCampaigns'),
            'fbCampaigns_count' => $model->relationLoaded('fbCampaigns') ? $model->fbCampaigns->count() : null,
            'fbAccounts_loaded' => $model->relationLoaded('fbAccounts'),
            'fbAccounts_count' => $model->relationLoaded('fbAccounts') ? $model->fbAccounts->count() : null,
            'fbBms_loaded' => $model->relationLoaded('fbBms'),
            'fbPixels_loaded' => $model->relationLoaded('fbPixels'),
            'fbBusinessUsers_loaded' => $model->relationLoaded('fbBusinessUsers'),
            'hint' => 'fb_accounts/pixels 等依赖 whenLoaded；未预加载时响应里对应字段为空',
        ]);

        $threshold_amount = data_get($this->adspaymentcycle, 'data.0.threshold_amount', -1);

        if ($threshold_amount != -1) {
            $threshold_amount = number_format($threshold_amount / 100, 2, '.', '');
        }

        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'source_id' => $this->source_id,
            'adtrust_dsl' => $this->adtrust_dsl,
            'account_status' => $this->account_status,
            'age' => $this->age,
            'total_spent' => $this->total_spent,
            'balance' => $this->balance,
            'amount_spent' => $this->amount_spent,
            'spend_cap' => $this->spend_cap,
            'threshold_amount' => $threshold_amount,
            'created_time' => $this->created_time,
            'currency' => $this->currency,
            'disable_reason' => $this->disable_reason,
            'name' => $this->name,
//            'is_original' => $this->is_original === null ? null : boolval($this->is_original),
            'is_prepay_account' => boolval($this->is_prepay_account),
            'timezone_name' => $this->timezone_name,
            'default_funding' => $this->default_funding,
            'funding_type' => $this->funding_type,
//            'enable_rule' => boolval($this->enable_rule),
            'campaigns' => FbCampaignForCreateAdsResource::collection($this->fbCampaigns),
//            'camp' => $this->fbCampaigns,
            'bms' => FbBmResource::collection($this->whenLoaded('fbBms')),
            'fb_accounts' => FbAccountResource::collection($this->whenLoaded('fbAccounts')),
            'pixels' => FbPixelResource::collection($this->whenLoaded('fbPixels')),
//            'fb_business_users' => $this->fbBusinessUsers,
            'fb_business_users' => FbBusinessUserResource::collection($this->whenLoaded('fbBusinessUsers')),
            'bm_system_users' => FbApiTokenResource2::collection($this->apiTokens),
            'fb_api_token' => FbApiTokenResource2::collection($this->apiTokens),
            'notes' => $this->notes,
            'is_archived' => $this->is_archived,
            'auto_sync' => $this->auto_sync,
            'tags' => TagResource::collection($this->tags),
            'filters' => $this->filters,
//            'users' => $this->when($request->user()->hasRole('admin'), function () {
//                return $this->users;
//            }),
        ];
    }
}
