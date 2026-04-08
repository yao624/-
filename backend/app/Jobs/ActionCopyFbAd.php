<?php

namespace App\Jobs;

use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbApiToken;
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
use Illuminate\Support\Facades\Pipeline;

class ActionCopyFbAd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 360;
    private string $adSourceId;
    private int $count;
    private int $mode;
    private int $copyIndex;

    /**
     * Create a new job instance.
     */
    public function __construct(string $adSourceId, int $count = 1, int $mode = 1, int $copyIndex = 1)
    {
        $this->adSourceId = $adSourceId;
        $this->count = $count;
        $this->mode = $mode;
        $this->copyIndex = $copyIndex;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("开始拷贝广告", [
                'ad_source_id' => $this->adSourceId,
                'count' => $this->count
            ]);

            // 查找原始广告
            $originalAd = FbAd::where('source_id', $this->adSourceId)->first();
            if (!$originalAd) {
                throw new Exception("找不到广告: {$this->adSourceId}");
            }

            // 获取广告账户信息
            $adAccount = $originalAd->fbAdAccountV2;
            if (!$adAccount) {
                throw new Exception("无法获取广告账户信息");
            }

            // 获取API Token
            $apiToken = $adAccount->apiTokens()
                ->where('active', true)
                ->where('token_type', 1)
                ->first();

            if (!$apiToken) {
                throw new Exception("广告账户没有有效的API Token");
            }

            // 执行拷贝操作（循环count次）
            for ($i = 0; $i < $this->count; $i++) {
                $this->executeCopyOperation($originalAd, $adAccount, $apiToken->token, $this->copyIndex);
            }

            Log::info("拷贝广告完成", [
                'ad_source_id' => $this->adSourceId,
                'count' => $this->count
            ]);

        } catch (Exception $e) {
            Log::error("拷贝广告失败", [
                'ad_source_id' => $this->adSourceId,
                'count' => $this->count,
                'error' => $e->getMessage()
            ]);

            Telegram::sendMessage("拷贝广告失败\r\n广告ID: {$this->adSourceId}\r\n错误: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * 执行单次拷贝操作
     */
    private function executeCopyOperation(FbAd $originalAd, FbAdAccount $adAccount, string $apiToken, int $copyIndex): void
    {
        $context = [
            'original_ad' => $originalAd,
            'ad_account' => $adAccount,
            'api_token' => $apiToken,
            'copy_index' => $copyIndex,
            'timezone_prefix' => $this->generateTimezonePrefix($copyIndex),
            'mode' => $this->mode
        ];

        // 根据mode选择要执行的Pipeline步骤
        $steps = [];
        switch ($this->mode) {
            case 1: // N-1-1: 拷贝Campaign -> Adset -> Ad
                $steps = [CopyCampaignStep::class, CopyAdsetStep::class, CopyAdStep::class];
                break;
            case 2: // 1-N-1: 只拷贝Adset -> Ad，使用原始Campaign
                $steps = [CopyAdsetStep::class, CopyAdStep::class];
                break;
            case 3: // 1-1-N: 只拷贝Ad，使用原始Adset
                $steps = [CopyAdStep::class];
                break;
            default:
                $steps = [CopyCampaignStep::class, CopyAdsetStep::class, CopyAdStep::class];
                break;
        }

        Pipeline::send($context)
            ->through($steps)
            ->then(function ($context) {
                Log::info("拷贝操作完成", [
                    'original_ad_id' => $context['original_ad']->source_id,
                    'new_campaign_id' => $context['new_campaign_id'] ?? null,
                    'new_adset_id' => $context['new_adset_id'] ?? null,
                    'new_ad_id' => $context['new_ad_id'] ?? null,
                    'copy_index' => $context['copy_index'],
                    'mode' => $this->mode
                ]);
            });
    }

    /**
     * 生成时区前缀 CP(06/28-11:24 - 1)-
     */
    private function generateTimezonePrefix(int $copyIndex): string
    {
        // 使用UTC+8时区
        $now = Carbon::now('Asia/Shanghai');
        return 'CP(' . $now->format('m/d-H:i') . ' - ' . $copyIndex . ')-';
    }

    /**
     * 处理失败情况
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ActionCopyFbAd Job失败", [
            'ad_source_id' => $this->adSourceId,
            'count' => $this->count,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        Telegram::sendMessage("拷贝广告Job失败\r\n广告ID: {$this->adSourceId}\r\n数量: {$this->count}\r\n错误: {$exception->getMessage()}");
    }
}

/**
 * Pipeline步骤：拷贝Campaign
 */
class CopyCampaignStep
{
    public function handle($context, \Closure $next)
    {
        try {
            $originalAd = $context['original_ad'];
            $adAccount = $context['ad_account'];
            $apiToken = $context['api_token'];
            $prefix = $context['timezone_prefix'];

            $originalCampaign = $originalAd->fbCampaign;
            if (!$originalCampaign) {
                throw new Exception("无法获取原始Campaign");
            }

            Log::info("开始拷贝Campaign", [
                'original_campaign_id' => $originalCampaign->source_id,
                'copy_index' => $context['copy_index'],
                'mode' => $context['mode'] ?? 1
            ]);

            // 构建拷贝Campaign的请求
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/{$originalCampaign->source_id}/copies";

            $body = [
                'rename_options' => json_encode([
                    'rename_prefix' => $prefix,
                    'rename_suffix' => '.'
                ]),
                'status_option' => 'PAUSED'
            ];

            // 调用Facebook API拷贝Campaign
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
                throw new Exception("拷贝Campaign失败");
            }

            $copiedCampaignId = $response['copied_campaign_id'];
            $context['new_campaign_id'] = $copiedCampaignId;

            Log::info("Campaign拷贝成功", [
                'original_campaign_id' => $originalCampaign->source_id,
                'new_campaign_id' => $copiedCampaignId,
                'copy_index' => $context['copy_index'],
                'mode' => $context['mode'] ?? 1
            ]);

            return $next($context);

        } catch (Exception $e) {
            Log::error("拷贝Campaign失败", [
                'error' => $e->getMessage(),
                'copy_index' => $context['copy_index']
            ]);
            throw $e;
        }
    }
}

/**
 * Pipeline步骤：拷贝Adset
 */
class CopyAdsetStep
{
    public function handle($context, \Closure $next)
    {
        try {
            $originalAd = $context['original_ad'];
            $apiToken = $context['api_token'];
            $prefix = $context['timezone_prefix'];
            $mode = $context['mode'] ?? 1;

            $originalAdset = $originalAd->fbAdset;
            if (!$originalAdset) {
                throw new Exception("无法获取原始Adset");
            }

            // 根据mode决定使用哪个Campaign ID
            $targetCampaignId = null;
            if ($mode == 1) {
                // mode 1: 使用新拷贝的Campaign ID
                $targetCampaignId = $context['new_campaign_id'];
            } else {
                // mode 2: 使用原始的Campaign ID
                $originalCampaign = $originalAd->fbCampaign;
                if (!$originalCampaign) {
                    throw new Exception("无法获取原始Campaign");
                }
                $targetCampaignId = $originalCampaign->source_id;
            }

            Log::info("开始拷贝Adset", [
                'original_adset_id' => $originalAdset->source_id,
                'target_campaign_id' => $targetCampaignId,
                'copy_index' => $context['copy_index'],
                'mode' => $mode
            ]);

            // 构建拷贝Adset的请求
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/{$originalAdset->source_id}/copies";

            $body = [
                'rename_options' => json_encode([
                    'rename_prefix' => $prefix,
                    'rename_suffix' => '.'
                ]),
                'status_option' => 'PAUSED',
                'campaign_id' => $targetCampaignId
            ];

            // 调用Facebook API拷贝Adset
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
                throw new Exception("拷贝Adset失败");
            }

            $copiedAdsetId = $response['copied_adset_id'];
            $context['new_adset_id'] = $copiedAdsetId;

            Log::info("Adset拷贝成功", [
                'original_adset_id' => $originalAdset->source_id,
                'new_adset_id' => $copiedAdsetId,
                'target_campaign_id' => $targetCampaignId,
                'copy_index' => $context['copy_index'],
                'mode' => $mode
            ]);

            return $next($context);

        } catch (Exception $e) {
            Log::error("拷贝Adset失败", [
                'error' => $e->getMessage(),
                'copy_index' => $context['copy_index']
            ]);
            throw $e;
        }
    }
}

/**
 * Pipeline步骤：拷贝Ad
 */
class CopyAdStep
{
    public function handle($context, \Closure $next)
    {
        try {
            $originalAd = $context['original_ad'];
            $apiToken = $context['api_token'];
            $prefix = $context['timezone_prefix'];
            $mode = $context['mode'] ?? 1;

            // 根据mode决定使用哪个Adset ID
            $targetAdsetId = null;
            if ($mode == 3) {
                // mode 3: 使用原始的Adset ID
                $originalAdset = $originalAd->fbAdset;
                if (!$originalAdset) {
                    throw new Exception("无法获取原始Adset");
                }
                $targetAdsetId = $originalAdset->source_id;
            } else {
                // mode 1 和 mode 2: 使用新拷贝的Adset ID
                $targetAdsetId = $context['new_adset_id'];
            }

            Log::info("开始拷贝Ad", [
                'original_ad_id' => $originalAd->source_id,
                'target_adset_id' => $targetAdsetId,
                'copy_index' => $context['copy_index'],
                'mode' => $mode
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
                'adset_id' => $targetAdsetId
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
                throw new Exception("拷贝Ad失败");
            }

            $copiedAdId = $response['copied_ad_id'];
            $context['new_ad_id'] = $copiedAdId;

            Log::info("Ad拷贝成功", [
                'original_ad_id' => $originalAd->source_id,
                'new_ad_id' => $copiedAdId,
                'target_adset_id' => $targetAdsetId,
                'copy_index' => $context['copy_index'],
                'mode' => $mode
            ]);

            return $next($context);

        } catch (Exception $e) {
            Log::error("拷贝Ad失败", [
                'error' => $e->getMessage(),
                'copy_index' => $context['copy_index']
            ]);
            throw $e;
        }
    }
}