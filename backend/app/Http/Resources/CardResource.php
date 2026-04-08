<?php

namespace App\Http\Resources;

use App\Models\Card;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 计算总的交易金额
        $totalAmount = $this->transactions->sum('transaction_amount');
        // 计算交易数量
        $transactionsCount = $this->transactions->count();

        return [
            'id' => $this->id,
            'source_id' => $this->source_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'applied_at' => $this->applied_at,
            'name' => $this->name,
            'status' => $this->status,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'limits' => $this->single_transaction_limit,
//            'number' => CardUtils::mask_number($this->number),
            'number' => $this->number,
            'cvv' => $this->cvv,
            'expiration' => $this->expiration,
            'notes' => $this->notes,
            'total_transactions_amount' => $totalAmount,
            'transactions_count' => $transactionsCount,
            'tags' => TagResource::collection($this->tags),
            'card_provider' => $this->when($this->relationLoaded('cardProvider'), function () {
                return [
                    'id' => $this->cardProvider->id,
                    'nick_name' => $this->cardProvider->nick_name, // 只返回nick_name
                    'active' => $this->cardProvider->active,
                ];
            }),
        ];
    }
}
