<?php

namespace App\Jobs;

use App\Models\FbCampaign;
use App\Models\FbAd;
use App\Models\FbAdset;
use App\Utils\FbWebhook;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FbWebhookProcessor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $webhookData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $webhookData)
    {
        $this->webhookData = $webhookData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing Facebook Webhook', [
                'data' => $this->webhookData
            ]);

            // 验证webhook数据结构
            if (!$this->isValidWebhookData($this->webhookData)) {
                $this->handleUnknownWebhookType();
                return;
            }

            // 处理每个entry
            foreach ($this->webhookData['entry'] as $entry) {
                $this->processEntry($entry);
            }

            Log::info('Facebook Webhook processed successfully', [
                'entry_count' => count($this->webhookData['entry'])
            ]);

        } catch (\Exception $e) {
            $this->handleProcessingError($e);
            throw $e; // 重新抛出异常以便队列系统处理失败
        }
    }

    /**
     * 处理单个entry
     */
    private function processEntry(array $entry): void
    {
        if (!isset($entry['changes']) || !is_array($entry['changes'])) {
            Log::warning('Facebook Webhook: Entry missing changes', ['entry' => $entry]);
            return;
        }

        foreach ($entry['changes'] as $change) {
            $this->processChange($change);
        }
    }

    /**
     * 处理单个change
     */
    private function processChange(array $change): void
    {
        // 检查是否是我们支持的field类型
        if (!isset($change['field']) || $change['field'] !== 'in_process_ad_objects') {
            Log::info('Facebook Webhook: Unsupported field type', [
                'field' => $change['field'] ?? 'null'
            ]);
            return;
        }

        if (!isset($change['value'])) {
            Log::warning('Facebook Webhook: Change missing value', ['change' => $change]);
            return;
        }

        $value = $change['value'];
        $level = $value['level'] ?? null;
        $statusName = $value['status_name'] ?? null;
        $objectId = $value['id'] ?? null;

        if (!$level || !$statusName || !$objectId) {
            Log::warning('Facebook Webhook: Missing required fields in value', [
                'value' => $value
            ]);
            return;
        }

        // 根据level类型处理不同的对象
        switch (strtoupper($level)) {
            case 'CAMPAIGN':
                $this->processCampaignStatusChange($objectId, $statusName);
                break;

            case 'AD':
                $this->processAdStatusChange($objectId, $statusName);
                break;

            case 'AD_SET':
            case 'ADSET':
                $this->processAdsetStatusChange($objectId, $statusName);
                break;

            default:
                Log::info('Facebook Webhook: Unsupported level type', [
                    'level' => $level,
                    'status_name' => $statusName,
                    'object_id' => $objectId
                ]);
                $this->handleUnknownWebhookType();
                break;
        }
    }

    /**
     * 处理Campaign状态变化
     */
    private function processCampaignStatusChange(string $campaignSourceId, string $statusName): void
    {
        try {
            $campaign = FbCampaign::where('source_id', $campaignSourceId)->first();

            if (!$campaign) {
                Log::warning('Facebook Webhook: Campaign not found', [
                    'source_id' => $campaignSourceId,
                    'status_name' => $statusName
                ]);
                return;
            }

            $oldStatus = $campaign->status;
            $newStatus = FbWebhook::mapCampaignStatus($statusName);

            // 更新Campaign状态
            $campaign->update(['status' => $newStatus]);

            Log::info('Facebook Webhook: Campaign status updated', [
                'source_id' => $campaignSourceId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'original_status_name' => $statusName
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process campaign status change', [
                'source_id' => $campaignSourceId,
                'status_name' => $statusName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理Ad状态变化
     */
    private function processAdStatusChange(string $adSourceId, string $statusName): void
    {
        try {
            $ad = FbAd::where('source_id', $adSourceId)->first();

            if (!$ad) {
                Log::warning('Facebook Webhook: Ad not found', [
                    'source_id' => $adSourceId,
                    'status_name' => $statusName
                ]);
                return;
            }

            $oldStatus = $ad->effective_status;
            $newStatus = FbWebhook::mapAdStatus($statusName);

            // 更新Ad状态
            $ad->update(['effective_status' => $newStatus]);

            Log::info('Facebook Webhook: Ad status updated', [
                'source_id' => $adSourceId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'original_status_name' => $statusName
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process ad status change', [
                'source_id' => $adSourceId,
                'status_name' => $statusName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理Adset状态变化
     */
    private function processAdsetStatusChange(string $adsetSourceId, string $statusName): void
    {
        try {
            $adset = FbAdset::where('source_id', $adsetSourceId)->first();

            if (!$adset) {
                Log::warning('Facebook Webhook: Adset not found', [
                    'source_id' => $adsetSourceId,
                    'status_name' => $statusName
                ]);
                return;
            }

            $oldStatus = $adset->effective_status;
            $newStatus = FbWebhook::mapAdsetStatus($statusName);

            // 更新Adset effective_status
            $adset->update(['effective_status' => $newStatus]);

            Log::info('Facebook Webhook: Adset effective_status updated', [
                'source_id' => $adsetSourceId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'original_status_name' => $statusName
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process adset status change', [
                'source_id' => $adsetSourceId,
                'status_name' => $statusName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 验证webhook数据是否有效
     */
    private function isValidWebhookData(array $data): bool
    {
        return isset($data['object']) &&
               isset($data['entry']) &&
               is_array($data['entry']) &&
               count($data['entry']) > 0;
    }

    /**
     * 处理未知的webhook类型
     */
    private function handleUnknownWebhookType(): void
    {
        $message = "🔔 Facebook Webhook: 收到未知类型数据\n" .
                  "数据: " . json_encode($this->webhookData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        Telegram::sendMessage($message);

        Log::info('Facebook Webhook: Unknown webhook type received', [
            'data' => $this->webhookData
        ]);
    }

    /**
     * 处理处理过程中的错误
     */
    private function handleProcessingError(\Exception $e): void
    {
        $message = "🚨 Facebook Webhook 处理失败\n" .
                  "错误: {$e->getMessage()}\n" .
                  "文件: {$e->getFile()}:{$e->getLine()}\n" .
                  "数据: " . json_encode($this->webhookData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        Telegram::sendMessage($message);

        Log::error('Facebook Webhook processing failed in job', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'webhook_data' => $this->webhookData
        ]);
    }

    /**
     * Job失败时的处理
     */
    public function failed(\Throwable $exception): void
    {
        $message = "🚨 Facebook Webhook Job 执行失败\n" .
                  "错误: {$exception->getMessage()}\n" .
                  "数据: " . json_encode($this->webhookData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        Telegram::sendMessage($message);

        Log::critical('Facebook Webhook job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'webhook_data' => $this->webhookData
        ]);
    }
}
