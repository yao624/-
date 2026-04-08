<?php

namespace App\Http\Resources;

use App\Models\FbAdAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbAdAccountForBmResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'relation' => $this->pivot->relation,
            'role' => $this->pivot->role,
            'is_original' => $this->is_original === null ? null : boolval($this->is_original),
            'is_prepay_account' => boolval($this->is_prepay_account),
            'timezone_name' => $this->timezone_name,
            'default_funding' => $this->default_funding,
            'funding_type' => $this->funding_type,
            'enable_rule' => boolval($this->enable_rule),
//            'campaigns' => FbCampaignResource::collection($this->whenLoaded('fbCampaigns')),
//            'camp' => $this->fbCampaigns,
//            'bms' => FbBmResource::collection($this->whenLoaded('fbBms')),
            'fb_accounts' => FbAccountResource::collection($this->whenLoaded('fbAccounts')),
            'pixels' => FbPixelResource::collection($this->whenLoaded('fbPixels')),
//            'fb_business_users' => $this->fbBusinessUsers,
            'fb_business_users' => FbBusinessUserResource::collection($this->whenLoaded('fbBusinessUsers')),
//            'bm_system_users' => FbApiTokenResource2::collection($this->apiTokens),
            'notes' => $this->notes,
            'is_archived' => $this->is_archived,
            'auto_sync' => $this->auto_sync,
            'subscribed_apps' => FbAppResource::collection($this->whenLoaded('subscribedApps')),
            'tags' => TagResource::collection($this->tags),
//            'users' => $this->when($request->user()->hasRole('admin'), function () {
//                return $this->users;
//            }),
        ];
    }
}
