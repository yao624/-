<?php

namespace App\Listeners;

use App\Enums\FraudConfigAction;
use App\Enums\FraudConfigType;
use App\Events\PagePostSaved;
use App\Models\FbPagePost;
use App\Models\FraudConfig;
use App\Utils\Telegram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandlePagePostSaved
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PagePostSaved $event): void
    {
        $post = $event->pagePost;

        Log::info("处理PagePost保存事件", ['post_id' => $post->source_id, 'url' => $post->url]);

        // 检查是否需要进行防盗刷检测
        if (!\App\Services\FraudDetectionService::shouldPerformScan()) {
            Log::info("没有配置防盗刷白名单，跳过PagePost检测");
            return;
        }

        // 检查广告是否在排除列表中
        if ($post->ad_source_id && $this->isAdExcluded($post->ad_source_id)) {
            Log::info("广告在排除列表中，跳过PagePost检测", ['ad_id' => $post->ad_source_id]);
            return;
        }

        // 检查URL是否符合白名单
        $checkResult = $this->checkUrlAgainstWhitelist($post->url);

        if ($checkResult['is_fraud']) {
            Log::warning("PagePost URL不在白名单中", [
                'post_id' => $post->source_id,
                'url' => $post->url,
                'reason' => $checkResult['reason']
            ]);

            // 执行所有激活的FraudConfig的actions
            $this->executeAllActions($post, $checkResult);
        } else {
            Log::info("PagePost URL通过白名单检查", [
                'post_id' => $post->source_id,
                'url' => $post->url
            ]);
        }
    }

    /**
     * 检查广告是否在排除列表中
     */
    private function isAdExcluded(string $adSourceId): bool
    {
        // 获取所有激活的FraudConfig
        $fraudConfigs = FraudConfig::where('active', true)->get();

        if ($fraudConfigs->isEmpty()) {
            return false;
        }

        // 合并所有排除的广告列表
        $excludedAds = [];
        foreach ($fraudConfigs as $config) {
            if ($config->excluded_ads && is_array($config->excluded_ads)) {
                $excludedAds = array_merge($excludedAds, $config->excluded_ads);
            }
        }

        $excludedAds = array_unique($excludedAds);
        return in_array($adSourceId, $excludedAds);
    }

    /**
     * 检查URL是否符合白名单（使用与FraudDetectionService相同的逻辑）
     */
    private function checkUrlAgainstWhitelist(string $url): array
    {
        if (!$url) {
            return [
                'is_fraud' => false,
                'reason' => '没有需要检查的URL'
            ];
        }

        // 获取所有激活的FraudConfig
        $fraudConfigs = FraudConfig::where('active', true)->get();

        if ($fraudConfigs->isEmpty()) {
            return [
                'is_fraud' => false,
                'reason' => '没有激活的防盗刷配置'
            ];
        }

        // 合并所有白名单
        $domainWhitelist = [];
        $urlWhitelist = [];
        $hasDomainWhitelist = false;
        $hasUrlWhitelist = false;

        foreach ($fraudConfigs as $config) {
            if ($config->type === FraudConfigType::DomainWhitelist->value && $config->value) {
                $domainWhitelist = array_merge($domainWhitelist, $config->value);
                $hasDomainWhitelist = true;
            } elseif ($config->type === FraudConfigType::UrlWhitelist->value && $config->value) {
                $urlWhitelist = array_merge($urlWhitelist, $config->value);
                $hasUrlWhitelist = true;
            }
        }

        $domainWhitelist = array_unique($domainWhitelist);
        $urlWhitelist = array_unique($urlWhitelist);

        // 如果没有配置任何白名单，跳过检查
        if (!$hasDomainWhitelist && !$hasUrlWhitelist) {
            return [
                'is_fraud' => false,
                'reason' => '没有配置任何白名单，允许所有URL'
            ];
        }

        $parsedUrl = parse_url($url);
        if (!$parsedUrl) {
            return [
                'is_fraud' => true,
                'reason' => "无效的URL格式: {$url}"
            ];
        }

        $domain = $parsedUrl['host'] ?? '';

        // 移除查询参数的URL用于URL白名单检查
        $urlWithoutQuery = ($parsedUrl['scheme'] ?? 'https') . '://' . $domain . ($parsedUrl['path'] ?? '');

        $isUrlValid = false;

        // 检查域名白名单（如果配置了）
        if ($hasDomainWhitelist && in_array($domain, $domainWhitelist)) {
            $isUrlValid = true;
            Log::info("PagePost域名通过白名单检查", [
                'domain' => $domain,
                'url' => $url
            ]);
        }

        // 检查URL白名单（如果配置了且域名检查未通过）
        if (!$isUrlValid && $hasUrlWhitelist && in_array($urlWithoutQuery, $urlWhitelist)) {
            $isUrlValid = true;
            Log::info("PagePost URL通过白名单检查", [
                'url_without_query' => $urlWithoutQuery,
                'original_url' => $url
            ]);
        }

        // 如果配置了白名单但URL都不匹配，则为异常
        if (($hasDomainWhitelist || $hasUrlWhitelist) && !$isUrlValid) {
            return [
                'is_fraud' => true,
                'reason' => "URL不在任何白名单中: {$url}"
            ];
        }

        return [
            'is_fraud' => false,
            'reason' => 'URL通过了白名单检查'
        ];
    }

    /**
     * 执行所有激活的FraudConfig的actions
     */
    private function executeAllActions(FbPagePost $pagePost, array $checkResult): void
    {
        // 获取所有激活的FraudConfig的actions
        $fraudConfigs = FraudConfig::where('active', true)->get();
        $allActions = [];

        foreach ($fraudConfigs as $config) {
            if ($config->actions) {
                $allActions = array_merge($allActions, $config->actions);
            }
        }

        $allActions = array_unique($allActions);

        foreach ($allActions as $action) {
            try {
                $this->performAction($action, $pagePost);
            } catch (\Exception $e) {
                Log::error("执行PagePost防盗刷行动失败", [
                    'post_id' => $pagePost->source_id,
                    'action' => $action,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function performAction($action, FbPagePost $pagePost)
    {
        // 根据 action 类型来进行相应的逻辑处理
        switch ($action) {
            case FraudConfigAction::TgAlert->value:
                $this->sendTelegramAlert($pagePost);
                break;

            case FraudConfigAction::LockVcc->value:
                $this->lockVccCards($pagePost);
                break;

            case FraudConfigAction::PauseAd->value:
                $this->pauseAd($pagePost);
                break;

            default:
                Log::warning("未知的防盗刷行动", ['action' => $action]);
                break;
        }
    }

    /**
     * 发送Telegram警报
     */
    private function sendTelegramAlert(FbPagePost $pagePost): void
    {
        $message = "🚨 PagePost检测到异常URL\n\n";
        $message .= "PagePost ID: {$pagePost->source_id}\n";
        $message .= "异常URL: {$pagePost->url}\n";
        $message .= "Campaign ID: {$pagePost->campaign_source_id}\n";
        $message .= "Adset ID: {$pagePost->adset_source_id}\n";
        $message .= "Ad ID: {$pagePost->ad_source_id}\n";
        $message .= "Ad Account ID: {$pagePost->ad_account_source_id}";

        Log::info("发送PagePost Telegram警报", ['post_id' => $pagePost->source_id]);
        Telegram::sendMessage($message);
    }

    /**
     * 锁定VCC卡片
     */
    private function lockVccCards(FbPagePost $pagePost): void
    {
        if (!$pagePost->ad_source_id) {
            Log::warning("PagePost没有关联的广告，无法锁定卡片", ['post_id' => $pagePost->source_id]);
            return;
        }

        // 查找对应的广告
        $ad = \App\Models\FbAd::where('source_id', $pagePost->ad_source_id)->first();
        if (!$ad) {
            Log::warning("找不到对应的广告", [
                'post_id' => $pagePost->source_id,
                'ad_source_id' => $pagePost->ad_source_id
            ]);
            return;
        }

        // 使用FraudActionsService来锁定卡片
        $fraudActionsService = app(\App\Services\FraudActionsService::class);
        $detectionResult = [
            'is_fraud' => true,
            'reason' => "PagePost URL异常: {$pagePost->url}",
            'urls' => [$pagePost->url]
        ];

        // 调用锁定卡片的方法
        try {
            $adAccount = $ad->fbAdAccountV2;
            if (!$adAccount) {
                Log::warning("找不到广告账户", ['ad_id' => $ad->source_id]);
                return;
            }

            $cards = $adAccount->cards()->where('status', 'ACTIVE')->get();
            if ($cards->isEmpty()) {
                Log::info("广告账户没有ACTIVE状态的卡片", [
                    'ad_account_id' => $adAccount->source_id
                ]);
                return;
            }

            $cardProviderService = app(\App\Services\CardProviderService::class);
            $frozenCards = [];
            $failedCards = [];

            foreach ($cards as $card) {
                try {
                    $provider = $cardProviderService->getProviderByCard($card);
                    $success = $provider->freezeCard($card->source_id);

                    if ($success) {
                        $card->refresh();
                        $frozenCards[] = $card->name ?: $card->number;
                        Log::info("PagePost检测 - 成功冻结卡片", [
                            'card_id' => $card->id,
                            'card_number' => $card->number
                        ]);
                    } else {
                        $failedCards[] = $card->name ?: $card->number;
                        Log::error("PagePost检测 - 冻结卡片失败", [
                            'card_id' => $card->id,
                            'card_number' => $card->number
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedCards[] = $card->name ?: $card->number;
                    Log::error("PagePost检测 - 冻结卡片异常", [
                        'card_id' => $card->id,
                        'card_number' => $card->number,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 发送Telegram通知
            $message = "🔒 PagePost检测 - 卡片冻结结果\n\n";
            $message .= "PagePost ID: {$pagePost->source_id}\n";
            $message .= "异常URL: {$pagePost->url}\n";
            $message .= "广告: {$ad->source_id}\n";
            $message .= "广告账户: {$adAccount->name} ({$adAccount->source_id})\n";
            $message .= "绑定 " . $cards->count() . " 张卡片\n\n";

            if (!empty($frozenCards)) {
                $message .= "✅ 已冻结: " . implode(', ', $frozenCards) . "\n";
            }

            if (!empty($failedCards)) {
                $message .= "❌ 冻结失败: " . implode(', ', $failedCards);
            }

            Telegram::sendMessage($message);

        } catch (\Exception $e) {
            Log::error("PagePost检测 - 锁定卡片失败", [
                'post_id' => $pagePost->source_id,
                'ad_id' => $ad->source_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 暂停广告
     */
    private function pauseAd(FbPagePost $pagePost): void
    {
        if (!$pagePost->ad_source_id) {
            Log::warning("PagePost没有关联的广告，无法暂停", ['post_id' => $pagePost->source_id]);
            return;
        }

        try {
            // 使用现有的Job来暂停广告
            \App\Jobs\ActionUpdateFbAdItemStauts::dispatch($pagePost->ad_source_id, 'PAUSED', 'ad')
                ->delay(now()->addSeconds(2));

            Log::info("PagePost检测 - 已提交暂停广告任务", [
                'post_id' => $pagePost->source_id,
                'ad_id' => $pagePost->ad_source_id
            ]);

            // 发送Telegram通知
            $message = "⏸️ PagePost检测 - 广告已暂停\n\n";
            $message .= "PagePost ID: {$pagePost->source_id}\n";
            $message .= "异常URL: {$pagePost->url}\n";
            $message .= "广告ID: {$pagePost->ad_source_id}\n";
            $message .= "Campaign ID: {$pagePost->campaign_source_id}\n";
            $message .= "Adset ID: {$pagePost->adset_source_id}\n";
            $message .= "\n广告暂停任务已提交";

            Telegram::sendMessage($message);

        } catch (\Exception $e) {
            Log::error("PagePost检测 - 暂停广告失败", [
                'post_id' => $pagePost->source_id,
                'ad_id' => $pagePost->ad_source_id,
                'error' => $e->getMessage()
            ]);

            // 发送失败通知
            $message = "❌ PagePost检测 - 暂停广告失败\n\n";
            $message .= "PagePost ID: {$pagePost->source_id}\n";
            $message .= "广告ID: {$pagePost->ad_source_id}\n";
            $message .= "错误: {$e->getMessage()}";

            Telegram::sendMessage($message);
        }
    }
}
