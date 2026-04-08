<?php

namespace App\Http\Resources;

use App\Models\FbAdAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FbAdAccountResource3 extends JsonResource
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
            'amount_spent' => $this->amount_spent,
            'spend_cap' => $this->spend_cap,
            'threshold_amount' => $threshold_amount,
            'created_time' => $this->created_time,
            'currency' => $this->currency,
            'disable_reason' => $this->disable_reason,
            'name' => $this->name,
            'role' => $this->pivot->role,
            'default_funding' => $this->default_funding,
            'funding_type' => $this->funding_type,
            'filters' => $this->filters,
            'is_topup' => $this->is_topup,
        ];
    }
}
