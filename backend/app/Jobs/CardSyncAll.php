<?php

namespace App\Jobs;

use App\Models\Card;
use App\Models\CardProvider;
use App\Services\CardProviderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CardSyncAll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $cardProviderId;
    private $options;

    /**
     * Job 超时时间（秒）
     * 考虑到分页获取可能需要较长时间：
     * - 583张卡片，39页，每页间隔20秒 ≈ 14分钟
     * - 加上CVC同步可能需要21分钟
     * - 再加上数据库操作，设置为3600秒（60分钟）比较安全
     */
    public $timeout = 3600;

    /**
     * Job 最大重试次数
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct($cardProviderId, array $options = [])
    {
        $this->cardProviderId = $cardProviderId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();
        Log::info("CardSyncAll Job started", [
            'provider_id' => $this->cardProviderId,
            'options' => $this->options,
            'timeout' => $this->timeout,
            'start_time' => $startTime->toDateTimeString()
        ]);

        $cardProvider = CardProvider::findOrFail($this->cardProviderId);

        if (!$cardProvider->active) {
            Log::warning("Card provider is not active: {$cardProvider->name}");
            return;
        }

        $cardProviderService = app(CardProviderService::class);

        try {
            $provider = $cardProviderService->getProviderByModel($cardProvider);

            // 检查是否支持getAllCards功能
            if (!$provider->supports('get_all_cards')) {
                Log::warning("Provider {$cardProvider->name} does not support getAllCards");
                return;
            }

            // 使用传入的options，如果未指定sync_cvc且是Adpos Provider则默认启用
            $getAllCardsOptions = $this->options;
            if (!isset($getAllCardsOptions['sync_cvc']) && $cardProvider->name === 'ap') {
                $getAllCardsOptions['sync_cvc'] = true;
            }

            // 添加provider_id到options中，供Provider内部使用
            $getAllCardsOptions['provider_id'] = $cardProvider->id;

            $allCards = $provider->getAllCards($getAllCardsOptions);

            Log::info("Retrieved {count} cards from provider {provider} with options {options}", [
                'count' => count($allCards),
                'provider' => $cardProvider->name,
                'options' => $getAllCardsOptions
            ]);

            $syncedCount = 0;
            $createdCount = 0;
            $updatedCount = 0;
            $cvcObtainedCount = 0;

            foreach ($allCards as $cardData) {
                if (!isset($cardData['card_id'])) {
                    Log::warning("Card data missing card_id, skipping", ['data' => $cardData]);
                    continue;
                }

                $sourceId = $cardData['card_id'];

                // 查找现有卡片
                $existingCard = Card::where('source_id', $sourceId)
                    ->where('card_provider_id', $cardProvider->id)
                    ->first();

                $updateData = [
                    'source_id' => $sourceId,
                    'card_provider_id' => $cardProvider->id,
                    'name' => $cardData['name'] ?? 'Unknown',
                    'status' => $cardData['status'] ?? 'INACTIVE',
                    'balance' => $cardData['available_balance'] ?? 0,
                    'currency' => $cardData['currency'] ?? 'USD',
                    'single_transaction_limit' => $cardData['single_transaction_limit'] ?? null,
                ];

                // 如果有卡号信息，保存
                if (isset($cardData['card_number']) && !empty($cardData['card_number'])) {
                    $updateData['number'] = $cardData['card_number'];
                }

                // 如果有申请时间，保存（处理UTC+8时区）
                if (isset($cardData['applied_at']) && $cardData['applied_at']) {
                    // Adpos返回的时间是UTC+8时区，需要转换为UTC时间存储
                    $updateData['applied_at'] = Carbon::parse($cardData['applied_at'], 'Asia/Shanghai')->utc();
                }

                if ($existingCard) {
                    // 更新现有卡片
                    // 如果Provider返回了CVC信息，且现有卡片没有CVC或过期时间，则保存
                    if (isset($cardData['cvv'])) {
                        // 只有在现有卡片没有CVV或CVV为空时才更新
                        if (empty($existingCard->cvv)) {
                            $updateData['cvv'] = $cardData['cvv'];
                            $cvcObtainedCount++;
                        } else {
                            Log::info("跳过CVV更新，现有卡片已有CVV数据", [
                                'card_id' => $sourceId,
                                'existing_cvv' => '***',
                                'new_cvv' => '***'
                            ]);
                        }
                    }

                    if (isset($cardData['expiration'])) {
                        // 只有在现有卡片没有过期时间或过期时间为空时才更新
                        if (empty($existingCard->expiration)) {
                            $updateData['expiration'] = $cardData['expiration'];
                        } else {
                            Log::info("跳过expiration更新，现有卡片已有过期时间数据", [
                                'card_id' => $sourceId,
                                'existing_expiration' => $existingCard->expiration,
                                'new_expiration' => $cardData['expiration']
                            ]);
                        }
                    }

                    $existingCard->update($updateData);
                    $updatedCount++;
                } else {
                    // 创建新卡片 - 直接设置所有CVC信息
                    if (isset($cardData['cvv'])) {
                        $updateData['cvv'] = $cardData['cvv'];
                        $cvcObtainedCount++;
                    }
                    if (isset($cardData['expiration'])) {
                        $updateData['expiration'] = $cardData['expiration'];
                    }

                    Card::create($updateData);
                    $createdCount++;
                }

                $syncedCount++;
            }

            $endTime = now();
            $totalDuration = $endTime->diffInSeconds($startTime);

            Log::info("Card sync completed for provider {provider}", [
                'provider' => $cardProvider->name,
                'total_processed' => $syncedCount,
                'created' => $createdCount,
                'updated' => $updatedCount,
                'cvc_obtained' => $cvcObtainedCount,
                'options' => $this->options,
                'start_time' => $startTime->toDateTimeString(),
                'end_time' => $endTime->toDateTimeString(),
                'duration_seconds' => $totalDuration,
                'duration_minutes' => round($totalDuration / 60, 2)
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to sync all cards for provider {provider}: {error}", [
                'provider' => $cardProvider->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function tags()
    {
        $tags = [
            "Card-Sync-All",
            "Provider-{$this->cardProviderId}",
        ];

        // 添加options相关的标签
        if (isset($this->options['sync_cvc']) && $this->options['sync_cvc']) {
            $tags[] = "CVC-Sync";
        }

        return $tags;
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Card Sync All Job failed for provider {provider}: {error}', [
            'provider' => $this->cardProviderId,
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'options' => $this->options,
            'timeout_configured' => $this->timeout,
            'tries_configured' => $this->tries,
            'attempt_number' => $this->attempts(),
            'failed_at' => now()->toDateTimeString()
        ]);
    }
}
