<?php

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResourceForFbAdAccount extends JsonResource
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
            'name' => $this->name,
            'is_default' => boolval($this->pivot->is_default),
            'number' => $this->number,
            'status' => $this->status,
            'balance' => $this->balance,
            'single_transaction_limit' => $this->single_transaction_limit,
            'currency' => $this->currency
        ];
    }
}
