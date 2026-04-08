<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbAdAccountResourceMode2 extends JsonResource
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
            'source_id' => $this->source_id,
            'adtrust_dsl' => $this->adtrust_dsl,
            'account_status' => $this->account_status,
            'age' => $this->age,
            'total_spent' => $this->total_spent,
            'balance' => $this->balance,
            'threshold_amount' => $threshold_amount,
            'amount_spent' => $this->amount_spent,
            'currency' => $this->currency,
            'disable_reason' => $this->disable_reason,
            'name' => $this->name,
            'timezone_name' => $this->timezone_name,
            'enable_rule' => boolval($this->enable_rule),
            'notes' => $this->notes,
            'is_archived' => $this->is_archived,
            'auto_sync' => $this->auto_sync,
            'is_topup' => $this->is_topup,
            'tags' => TagResource::collection($this->tags),
            'filters' => $this->filters,
        ];
    }
}
