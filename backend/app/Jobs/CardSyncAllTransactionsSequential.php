<?php

namespace App\Jobs;

use App\Models\Card;
use App\Services\CardProviderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CardSyncAllTransactionsSequential implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startTime;
    protected $stopTime;
    protected $cardIds;
    protected $currentIndex;

    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct($startTime = null, $stopTime = null, $cardIds = null, $currentIndex = 0)
    {
        $this->startTime = $startTime;
        $this->stopTime = $stopTime;
        $this->cardIds = $cardIds;
        $this->currentIndex = $currentIndex;

        // 如果没有提供卡片ID列表，获取所有卡片
        if ($this->cardIds === null) {
            $this->cardIds = Card::query()->pluck('id')->toArray();
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 检查是否还有卡片需要处理
        if ($this->currentIndex >= count($this->cardIds)) {
            Log::info("All cards transaction sync completed", [
                'total_cards' => count($this->cardIds),
                'start_time' => date('Y-m-d H:i:s', $this->startTime),
                'stop_time' => date('Y-m-d H:i:s', $this->stopTime)
            ]);
            return;
        }

        $cardId = $this->cardIds[$this->currentIndex];
        $card = Card::with('cardProvider')->find($cardId);

        if (!$card || !$card->source_id) {
            Log::warning("Card not found or missing source_id", [
                'card_id' => $cardId,
                'current_index' => $this->currentIndex
            ]);

            // 处理下一张卡片
            $this->processNextCard();
            return;
        }

        Log::info("Starting transaction sync for card", [
            'card_id' => $card->id,
            'card_name' => $card->name,
            'card_number' => substr($card->number, -4),
            'current_index' => $this->currentIndex + 1,
            'total_cards' => count($this->cardIds)
        ]);

        try {
            // 获取卡片提供商服务
            $cardProviderService = app(CardProviderService::class);
            $provider = $cardProviderService->getProviderByCard($card);

            if (!$provider) {
                Log::error("No provider found for card", ['card_id' => $card->id]);
                $this->processNextCard();
                return;
            }

            // 同步交易记录
            $this->syncCardTransactions($provider, $card);

            Log::info("Card transaction sync completed", [
                'card_id' => $card->id,
                'card_name' => $card->name,
                'current_index' => $this->currentIndex + 1,
                'remaining_cards' => count($this->cardIds) - $this->currentIndex - 1
            ]);

        } catch (\Exception $e) {
            Log::error("Card transaction sync failed", [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
                'current_index' => $this->currentIndex
            ]);
        }

        // 处理下一张卡片
        $this->processNextCard();
    }

    /**
     * 同步单张卡片的交易记录
     */
    protected function syncCardTransactions($provider, $card)
    {
        $after = null;
        $hasMore = true;

        while ($hasMore) {
            try {
                $response = $provider->syncTransactionsWithParams([
                    'start_time' => $this->startTime,
                    'stop_time' => $this->stopTime,
                    'after' => $after,
                    'status' => null,
                    'card_source_id' => $card->source_id
                ]);

                if (!$response || !isset($response['transactions'])) {
                    Log::warning("No transaction data returned", ['card_id' => $card->id]);
                    break;
                }

                $transactions = $response['transactions'];
                $this->saveTransactions($transactions, $card);

                // 检查是否还有更多数据
                $hasMore = isset($response['has_more']) && $response['has_more'];
                $after = $hasMore ? ($response['next_page'] ?? null) : null;

                Log::debug("Synced transaction page", [
                    'card_id' => $card->id,
                    'transaction_count' => count($transactions),
                    'has_more' => $hasMore,
                    'after' => $after
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to sync transaction page", [
                    'card_id' => $card->id,
                    'after' => $after,
                    'error' => $e->getMessage()
                ]);
                break;
            }
        }
    }

    /**
     * 保存交易记录到数据库
     */
    protected function saveTransactions($transactions, $card)
    {
        foreach ($transactions as $transaction) {
            try {
                // 使用updateOrCreate避免重复记录
                                \App\Models\CardTransaction::updateOrCreate(
                    ['source_id' => $transaction['source_id']],
                    [
                        'card_id' => $card->id,
                        'status' => $transaction['status'] ?? '',
                        'transaction_amount' => $transaction['transaction_amount'] ?? 0,
                        'transaction_date' => $transaction['transaction_date'],
                        'transaction_type' => $transaction['transaction_type'] ?? '',
                        'merchant_name' => $transaction['merchant_name'] ?? '',
                        'custom_1' => $transaction['custom_1'] ?? '',
                        'posted_date' => $transaction['posted_date'],
                        'failure_reason' => $transaction['failure_reason'] ?? '',
                        'notes' => $transaction['notes'] ?? '',
                    ]
                );
            } catch (\Exception $e) {
                Log::error("Failed to save transaction", [
                    'card_id' => $card->id,
                    'transaction_id' => $transaction['source_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }



    /**
     * 处理下一张卡片
     */
    protected function processNextCard()
    {
        $nextIndex = $this->currentIndex + 1;

        if ($nextIndex < count($this->cardIds)) {
            // 调度下一张卡片的同步任务
            CardSyncAllTransactionsSequential::dispatch(
                $this->startTime,
                $this->stopTime,
                $this->cardIds,
                $nextIndex
            )->onQueue('transactions');
        }
    }
}
