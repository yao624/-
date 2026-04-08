<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardTransactionResourceWithoutCard extends JsonResource
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
            'status' => $this->status,
            'transaction_amount' => $this->transaction_amount,
            'currency' => $this->currency,
            'transaction_date' => $this->transaction_date,
            'transaction_type' => $this->transaction_type,
            'merchant_name' => $this->merchant_name,
            'failure_reason' => $this->failure_reason,
            'notes' => $this->notes,
        ];
    }
}