<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResourceWithLatestTransaction extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 获取最新的交易记录，优先使用预加载的数据
        if ($this->relationLoaded('transactions')) {
            // 如果已经预加载了transactions，直接使用
            $latestTransaction = $this->transactions->first();
        } else {
            // 如果没有预加载，则查询数据库
            $latestTransaction = $this->transactions()
                ->orderBy('transaction_date', 'desc')
                ->first();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_default' => $this->when(isset($this->pivot), function() {
                return boolval($this->pivot->is_default);
            }),
            'number' => $this->number,
            'status' => $this->status,
            'balance' => $this->balance,
            'single_transaction_limit' => $this->single_transaction_limit,
            'currency' => $this->currency,
            'latest_transaction' => $latestTransaction ? new CardTransactionResourceWithoutCard($latestTransaction) : null,
        ];
    }
}
