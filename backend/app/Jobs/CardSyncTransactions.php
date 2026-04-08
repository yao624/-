<?php

namespace App\Jobs;

use App\Models\Card;
use App\Models\CardTransaction;
use App\Services\CardProviderService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CardSyncTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5分钟超时

    private $startTime;
    private $stopTime;
    private $after;
    private $status;
    private $provider;
    private $cardSourceId;

    /**
     * Create a new job instance.
     *
     * @param string|null $startTime 开始时间 (timestamp)
     * @param string|null $stopTime 结束时间 (timestamp)
     * @param string|null $after 分页参数
     * @param string|null $status 交易状态 (approved, declined)
     * @param string|null $provider 指定的provider
     * @param string|null $cardSourceId 特定卡片的source_id
     */
    public function __construct(
        $startTime = null,
        $stopTime = null,
        $after = null,
        $status = null,
        $provider = null,
        $cardSourceId = null
    ) {
        $this->startTime = $startTime;
        $this->stopTime = $stopTime;
        $this->after = $after;
        $this->status = $status;
        $this->provider = $provider;
        $this->cardSourceId = $cardSourceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting CardSyncTransactions job", [
            'start_time' => $this->startTime,
            'stop_time' => $this->stopTime,
            'after' => $this->after,
            'status' => $this->status,
            'provider' => $this->provider,
            'card_source_id' => $this->cardSourceId
        ]);

        try {
            // 获取CardProviderService实例
            $cardProviderService = app(CardProviderService::class);

            // 确定使用的provider
            $provider = $this->getProvider($cardProviderService);

            // 检查provider是否支持交易同步
            if (!$provider->supports($provider::CAPABILITY_SYNC_TRANSACTIONS)) {
                Log::error("Provider does not support transaction sync", [
                    'provider' => get_class($provider)
                ]);
                throw new \Exception('Provider does not support transaction sync');
            }

            // 准备参数
            $params = [];
            if ($this->startTime) {
                $params['start_time'] = $this->startTime;
            }
            if ($this->stopTime) {
                $params['stop_time'] = $this->stopTime;
            }
            if ($this->after) {
                $params['after'] = $this->after;
            }
            if ($this->status) {
                $params['status'] = $this->status;
            }
            if ($this->cardSourceId) {
                $params['card_source_id'] = $this->cardSourceId;
            }

            // 调用provider的交易同步方法
            $result = $provider->syncTransactionsWithParams($params);

            // 处理交易数据
            $this->processTransactions($result['transactions']);

            // 检查是否有更多数据需要处理
            if ($result['has_more'] && $result['next_page']) {
                Log::info("More transactions available, dispatching next job", [
                    'next_page' => $result['next_page']
                ]);

                // 调度下一个Job处理剩余数据
                self::dispatch(
                    $this->startTime,
                    $this->stopTime,
                    $result['next_page'],
                    $this->status,
                    $this->provider,
                    $this->cardSourceId
                )->onQueue('transactions')->delay(now()->addSeconds(5));
            }

            Log::info("CardSyncTransactions job completed successfully", [
                'transactions_processed' => count($result['transactions']),
                'has_more' => $result['has_more']
            ]);

        } catch (\Exception $e) {
            Log::error("CardSyncTransactions job failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 获取Provider实例
     */
    private function getProvider(CardProviderService $cardProviderService)
    {
        if ($this->provider) {
            // 使用指定的provider
            return $cardProviderService->getProvider($this->provider);
        } elseif ($this->cardSourceId) {
            // 根据card_source_id获取对应的provider
            $card = Card::where('source_id', $this->cardSourceId)
                ->with('cardProvider')
                ->first();

            if (!$card || !$card->cardProvider) {
                throw new \Exception("Card not found or no provider associated: {$this->cardSourceId}");
            }

            return $cardProviderService->getProviderByCard($card);
        } else {
            // 使用默认provider
            return $cardProviderService->getDefaultProvider();
        }
    }

    /**
     * 处理交易数据
     */
    private function processTransactions(array $transactions): void
    {
        foreach ($transactions as $transactionData) {
            try {
                $this->processTransaction($transactionData);
            } catch (\Exception $e) {
                Log::error("Failed to process transaction", [
                    'transaction_data' => $transactionData,
                    'error' => $e->getMessage()
                ]);
                // 继续处理其他交易，不中断整个Job
            }
        }
    }

    /**
     * 处理单个交易
     */
    private function processTransaction(array $transactionData): void
    {
        $sourceId = $transactionData['source_id'];
        $cardSourceId = $transactionData['card_source_id'] ?? null;
        $cardNumber = $transactionData['card_number'] ?? null;

        // 查找对应的卡片 - 优先使用card_source_id，然后使用card_number
        $card = null;

        if ($cardSourceId) {
            $card = Card::where('source_id', $cardSourceId)->first();
        } elseif ($cardNumber) {
            $card = Card::where('number', $cardNumber)->first();
        }

        if (!$card) {
            Log::warning("Card not found for transaction", [
                'card_source_id' => $cardSourceId,
                'card_number' => $cardNumber ? substr($cardNumber, 0, 4) . '****' : null,
                'transaction_source_id' => $sourceId
            ]);
            return;
        }

        // 检查交易是否已存在
        $transaction = CardTransaction::where('source_id', $sourceId)
            ->where('card_id', $card->id)
            ->first();

        if (!$transaction) {
            $transaction = new CardTransaction();
            $transaction->card_id = $card->id;
            $transaction->source_id = $sourceId;
//            Log::debug("Creating new transaction", [
//                'transaction_id' => $sourceId,
//                'card_id' => $card->id,
//                'card_name' => $card->name
//            ]);
        } else {
//            Log::debug("Updating existing transaction", [
//                'transaction_id' => $sourceId,
//                'card_id' => $card->id,
//                'card_name' => $card->name
//            ]);
        }

        // 映射字段
        $transaction->status = $transactionData['status'];
        $transaction->transaction_amount = $transactionData['transaction_amount'];
        $transaction->currency = $transactionData['currency'] ?? null;
        $transaction->transaction_date = $transactionData['transaction_date'];
        $transaction->transaction_type = $transactionData['transaction_type'];
        $transaction->merchant_name = $transactionData['merchant_name'];
        $transaction->custom_1 = $transactionData['custom_1'];
        $transaction->posted_date = $transactionData['posted_date'];
        $transaction->failure_reason = $transactionData['failure_reason'];
        $transaction->notes = $transactionData['notes'];
        // Log::debug("Transaction data", [
        //     'transaction_data' => $transactionData
        // ]);
        // 保存交易
        if ($transaction->isDirty()) {
            $transaction->save();
//            Log::debug("Transaction saved", [
//                'transaction_id' => $sourceId,
//                'card_name' => $card->name,
//                'changes' => $transaction->getChanges()
//            ]);
        } else {
            $transaction->touch();
            Log::debug("Transaction touched (no changes)", [
                'transaction_id' => $sourceId,
                'card_name' => $card->name
            ]);
        }
    }

    /**
     * 获取Job标签
     */
    public function tags(): array
    {
        $tags = ['CardSyncTransactions', 'Transactions'];

        if ($this->provider) {
            $tags[] = "Provider-{$this->provider}";
        }

        if ($this->cardSourceId) {
            $tags[] = "Card-{$this->cardSourceId}";
        }

        if ($this->status) {
            $tags[] = "Status-{$this->status}";
        }

        if ($this->after) {
            $tags[] = "Page-{$this->after}";
        }

        return $tags;
    }

    /**
     * 处理Job失败
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CardSyncTransactions job failed', [
            'error' => $exception->getMessage(),
            'start_time' => $this->startTime,
            'stop_time' => $this->stopTime,
            'after' => $this->after,
            'status' => $this->status,
            'provider' => $this->provider,
            'card_source_id' => $this->cardSourceId
        ]);
    }
}
