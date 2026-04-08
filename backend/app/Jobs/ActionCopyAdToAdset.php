<?php

namespace App\Jobs;

use App\Models\FbAd;
use App\Models\FbAdset;
use App\Models\FbAdAccount;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ActionCopyAdToAdset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 360;
    private string $adSourceId;
    private string $adsetSourceId;
    private int $copyIndex;

    /**
     * Create a new job instance.
     */
    public function __construct(string $adSourceId, string $adsetSourceId, int $copyIndex = 1)
    {
        $this->adSourceId = $adSourceId;
        $this->adsetSourceId = $adsetSourceId;
        $this->copyIndex = $copyIndex;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("开始将广告复制到广告组", [
                'ad_source_id' => $this->adSourceId,
                'adset_source_id' => $this->adsetSourceId,
                'copy_index' => $this->copyIndex
            ]);

            // 查找原始广告
            $originalAd = FbAd::where('source_id', $this->adSourceId)->first();
            if (!$originalAd) {
                throw new Exception("找不到广告: {$this->adSourceId}");
            }

            // 查找目标广告组
            $targetAdset = FbAdset::where('source_id', $this->adsetSourceId)->first();
            if (!$targetAdset) {
                throw new Exception("找不到广告组: {$this->adsetSourceId}");
            }

            // 获取广告账户信息
            $adAccount = $originalAd->fbAdAccountV2;
            if (!$adAccount) {
                throw new Exception("无法获取广告账户信息");
            }

            // 验证广告组属于同一个广告账户
            $adsetAdAccount = $targetAdset->fbAdAccount;
            if (!$adsetAdAccount || $adsetAdAccount->source_id !== $adAccount->source_id) {
                throw new Exception("广告组不属于同一个广告账户");
            }

            // 获取API Token
            $apiToken = $adAccount->apiTokens()
                ->where('active', true)
                ->where('token_type', 1)
                ->first();

            if (!$apiToken) {
                throw new Exception("广告账户没有有效的API Token");
            }

            // 执行拷贝操作
            $this->executeCopyOperation($originalAd, $targetAdset, $apiToken->token);

            Log::info("广告复制到广告组完成", [
                'ad_source_id' => $this->adSourceId,
                'adset_source_id' => $this->adsetSourceId,
                'copy_index' => $this->copyIndex
            ]);

        } catch (Exception $e) {
            Log::error("广告复制到广告组失败", [
                'ad_source_id' => $this->adSourceId,
                'adset_source_id' => $this->adsetSourceId,
                'copy_index' => $this->copyIndex,
                'error' => $e->getMessage()
            ]);

            Telegram::sendMessage("广告复制到广告组失败\r\n广告ID: {$this->adSourceId}\r\n广告组ID: {$this->adsetSourceId}\r\n错误: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * 执行广告拷贝操作
     */
    private function executeCopyOperation(FbAd $originalAd, FbAdset $targetAdset, string $apiToken): void
    {
        try {
            $prefix = $this->generateTimezonePrefix();

            Log::info("开始拷贝广告到指定广告组", [
                'original_ad_id' => $originalAd->source_id,
                'target_adset_id' => $targetAdset->source_id,
                'copy_index' => $this->copyIndex,
                'prefix' => $prefix
            ]);

            // 构建拷贝Ad的请求
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/{$originalAd->source_id}/copies";

            $body = [
                'rename_options' => json_encode([
                    'rename_prefix' => $prefix,
                    'rename_suffix' => '.'
                ]),
                'status_option' => 'PAUSED',
                'adset_id' => $targetAdset->source_id
            ];

            // 调用Facebook API拷贝Ad
            $response = FbUtils::makeRequest(
                null,
                $endpoint,
                null,
                'POST',
                $body,
                'copy_fb_items',
                $apiToken
            );

            if (!$response['success']) {
                throw new Exception("拷贝广告失败: " . ($response['error'] ?? '未知错误'));
            }

            $copiedAdId = $response['copied_ad_id'] ?? null;
            if (!$copiedAdId) {
                throw new Exception("拷贝广告失败: 未返回新广告ID");
            }

            Log::info("广告拷贝到广告组成功", [
                'original_ad_id' => $originalAd->source_id,
                'new_ad_id' => $copiedAdId,
                'target_adset_id' => $targetAdset->source_id,
                'copy_index' => $this->copyIndex,
                'prefix' => $prefix
            ]);

        } catch (Exception $e) {
            Log::error("拷贝广告到广告组失败", [
                'original_ad_id' => $originalAd->source_id,
                'target_adset_id' => $targetAdset->source_id,
                'copy_index' => $this->copyIndex,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 生成时区前缀 CAT(06/28-11:24 - 1)- (Copy Ad To adset)
     */
    private function generateTimezonePrefix(): string
    {
        // 使用UTC+8时区
        $now = Carbon::now('Asia/Shanghai');
        return 'CAT(' . $now->format('m/d-H:i') . ' - ' . $this->copyIndex . ')-';
    }

    /**
     * 处理失败情况
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ActionCopyAdToAdset Job失败", [
            'ad_source_id' => $this->adSourceId,
            'adset_source_id' => $this->adsetSourceId,
            'copy_index' => $this->copyIndex,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        Telegram::sendMessage("广告复制到广告组Job失败\r\n广告ID: {$this->adSourceId}\r\n广告组ID: {$this->adsetSourceId}\r\n错误: {$exception->getMessage()}");
    }
}
