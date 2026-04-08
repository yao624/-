<?php

namespace App\Jobs;

use App\Models\FbCampaign;
use App\Models\FbAdset;
use App\Utils\FbUtils;
use App\Utils\CurrencyUtils;
use App\Utils\Telegram;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookCboToAbo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $campaignSourceId;
    private $budget;

    /**
     * Create a new job instance.
     */
    public function __construct(string $campaignSourceId, float $budget)
    {
        $this->campaignSourceId = $campaignSourceId;
        $this->budget = $budget;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("开始处理 CBO 到 ABO 转换", [
            'campaign_source_id' => $this->campaignSourceId,
            'budget' => $this->budget
        ]);

        try {
            // 查找 campaign
            $campaign = FbCampaign::where('source_id', $this->campaignSourceId)->first();
            if (!$campaign) {
                Log::error("Campaign 不存在", ['campaign_source_id' => $this->campaignSourceId]);
                throw new Exception("Campaign not found: {$this->campaignSourceId}");
            }

            // 获取关联的广告账户
            $adAccount = $campaign->fbAdAccount;
            if (!$adAccount) {
                Log::error("广告账户不存在", ['campaign_source_id' => $this->campaignSourceId]);
                throw new Exception("Ad Account not found for campaign: {$this->campaignSourceId}");
            }

            // 检查广告账户状态
            if ($adAccount->account_status !== 'ACTIVE') {
                Log::error("广告账户状态不是 ACTIVE", [
                    'campaign_source_id' => $this->campaignSourceId,
                    'ad_account_status' => $adAccount->account_status
                ]);
                throw new Exception("Ad Account status is not ACTIVE: {$adAccount->account_status}");
            }

            // 自动获取该 campaign 下所有有效的 adsets
            $validAdsets = FbAdset::where('fb_campaign_id', $campaign->id)
                ->whereNotIn('status', ['ARCHIVED', 'DELETED'])
                ->pluck('source_id')
                ->toArray();

            if (empty($validAdsets)) {
                Log::error("该 Campaign 下没有有效的 Adsets", ['campaign_source_id' => $this->campaignSourceId]);
                throw new Exception("No valid adsets found for campaign: {$this->campaignSourceId}");
            }

            // 使用与 ActionUpdateFbAdItemBudget 相同的预算转换逻辑
            $convertBudget = CurrencyUtils::convertAndFormat($this->budget, 'USD', $adAccount->currency);
            $currencyOffset = CurrencyUtils::$currencyConfig[$adAccount->currency]['offset'];
            $convertBudget = $currencyOffset * $convertBudget;

            Log::debug("预算转换结果", [
                'original_budget' => $this->budget,
                'converted_budget' => $convertBudget,
                'currency' => $adAccount->currency,
                'currency_offset' => $currencyOffset
            ]);

            // 为每个 adset 生成预算配置
            $adsetBudgets = [];
            foreach ($validAdsets as $adsetSourceId) {
                $adsetBudgets[] = [
                    'adset_id' => intval($adsetSourceId),
                    'daily_budget' => intval($convertBudget)
                ];
            }

            Log::info("生成的 Adset 预算配置", [
                'adsets_count' => count($adsetBudgets),
                'adset_budgets' => $adsetBudgets
            ]);

            // 获取 API Token
            $token = null;
            $fbAccount = null;
            $apiToken = $adAccount->apiTokens()->where('active', true)->first();
            if ($apiToken) {
                $token = $apiToken->token;
            } else {
                $fbAccount = $adAccount->fbAccounts()->where('token_valid', true)->first();
                if (!$fbAccount) {
                    Log::error("没有找到有效的认证信息", ['campaign_source_id' => $this->campaignSourceId]);
                    throw new Exception("No valid token found for campaign: {$this->campaignSourceId}");
                }
            }

            // 调用 Facebook API
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/{$this->campaignSourceId}";
            $body = [
                'adset_budgets' => $adsetBudgets
            ];

            Log::info("调用 Facebook API 进行 CBO 到 ABO 转换", [
                'endpoint' => $endpoint,
                'body' => $body
            ]);

            $response = FbUtils::makeRequest($fbAccount, $endpoint, null, 'POST', $body, '', $token);

            Log::info("Facebook API 响应", [
                'campaign_source_id' => $this->campaignSourceId,
                'response' => $response
            ]);

            // 检查响应是否成功
            if (!isset($response['success']) || $response['success'] !== true) {
                Log::error("Facebook API 调用失败", [
                    'campaign_source_id' => $this->campaignSourceId,
                    'response' => $response
                ]);
                throw new Exception("Facebook API call failed for campaign: {$this->campaignSourceId}");
            }

            Log::info("CBO 到 ABO 转换成功", [
                'campaign_source_id' => $this->campaignSourceId,
                'adsets_count' => count($adsetBudgets),
                'converted_budget' => $convertBudget
            ]);

        } catch (Exception $e) {
            Log::error("CBO 到 ABO 转换失败", [
                'campaign_source_id' => $this->campaignSourceId,
                'budget' => $this->budget,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("FacebookCboToAbo Job 失败", [
            'campaign_source_id' => $this->campaignSourceId,
            'budget' => $this->budget,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // 发送 Telegram 通知
        $message = "CBO 到 ABO 转换任务失败\r\n";
        $message .= "Campaign ID: {$this->campaignSourceId}\r\n";
        $message .= "预算: \${$this->budget}\r\n";
        $message .= "错误信息: " . $exception->getMessage();

        try {
            Telegram::sendMessage($message);
            Log::info("CBO到ABO转换失败通知已发送", [
                'campaign_source_id' => $this->campaignSourceId
            ]);
        } catch (\Exception $e) {
            Log::error('发送 Telegram 通知失败', [
                'campaign_source_id' => $this->campaignSourceId,
                'telegram_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            "CBO-to-ABO",
            "Campaign:{$this->campaignSourceId}",
            "Budget:{$this->budget}"
        ];
    }
}
