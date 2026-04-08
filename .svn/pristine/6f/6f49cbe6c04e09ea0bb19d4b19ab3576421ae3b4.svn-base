<?php

namespace App\Services;

use App\Models\FbAd;
use App\Models\FraudConfig;
use App\Enums\FraudConfigAction;
use App\Jobs\ActionUpdateFbAdItemStauts;
use App\Services\CardProviderService;
use App\Utils\Telegram;
use Illuminate\Support\Facades\Log;

class FraudActionsService
{
    private CardProviderService $cardProviderService;

    public function __construct(CardProviderService $cardProviderService)
    {
        $this->cardProviderService = $cardProviderService;
    }

    /**
     * 执行防盗刷检测后的行动
     */
    public function executeActions(FbAd $ad, array $detectionResult): void
    {
        if (!$detectionResult['is_fraud']) {
            return;
        }

        Log::info("开始执行防盗刷行动", [
            'ad_id' => $ad->source_id,
            'reason' => $detectionResult['reason']
        ]);

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
                $this->performAction($action, $ad, $detectionResult);
            } catch (\Exception $e) {
                Log::error("执行防盗刷行动失败", [
                    'ad_id' => $ad->source_id,
                    'action' => $action,
                    'error' => $e->getMessage()
                ]);
                Telegram::sendMessage("execute anti-fraud action failed");
            }
        }
    }

    /**
     * 执行具体的行动
     */
    private function performAction(string $action, FbAd $ad, array $detectionResult): void
    {
        switch ($action) {
            case FraudConfigAction::TgAlert->value:
                $this->sendTelegramAlert($ad, $detectionResult);
                break;

            case FraudConfigAction::LockVcc->value:
                $this->lockVccCards($ad, $detectionResult);
                break;

            case FraudConfigAction::PauseAd->value:
                $this->pauseAd($ad, $detectionResult);
                break;

            default:
                Log::warning("未知的防盗刷操作", ['action' => $action]);
                break;
        }
    }

    /**
     * 发送Telegram警报
     */
    private function sendTelegramAlert(FbAd $ad, array $detectionResult): void
    {
        $adAccount = $ad->fbAdAccountV2;
        $message = "🚨 检测到广告盗刷风险 @rancher233 🚨\n\n";
        $message .= "广告ID: {$ad->source_id}\n";
        $message .= "广告名称: {$ad->name}\n";
        $message .= "广告账户: {$adAccount->name} ({$adAccount->source_id})\n";
        $message .= "检测原因: {$detectionResult['reason']}\n";

        if (!empty($detectionResult['urls'])) {
            $message .= "涉及URL:\n";
            foreach ($detectionResult['urls'] as $url) {
                $message .= "- {$url}\n";
            }
        }

        $message .= "\n请立即检查并处理！";

        Telegram::sendMessage($message);

        Log::info("已发送Telegram警报", ['ad_id' => $ad->source_id]);
    }

    /**
     * 锁定VCC卡片
     */
    private function lockVccCards(FbAd $ad, array $detectionResult): void
    {
        $adAccount = $ad->fbAdAccountV2;
        $cards = $adAccount->cards()->where('status', 'ACTIVE')->get();

        if ($cards->isEmpty()) {
            Log::info("广告账户没有ACTIVE状态的卡片", [
                'ad_id' => $ad->source_id,
                'ad_account_id' => $adAccount->source_id
            ]);
            return;
        }

        $frozenCards = [];
        $failedCards = [];

        foreach ($cards as $card) {
            try {
                $provider = $this->cardProviderService->getProviderByCard($card);
                $success = $provider->freezeCard($card->source_id);

                if ($success) {
                    $card->refresh(); // 刷新卡片状态
                    $frozenCards[] = $card->name ?: $card->number;
                    Log::info("成功冻结卡片", [
                        'card_id' => $card->id,
                        'card_number' => $card->number
                    ]);
                } else {
                    $failedCards[] = $card->name ?: $card->number;
                    Log::error("冻结卡片失败", [
                        'card_id' => $card->id,
                        'card_number' => $card->number
                    ]);
                    Telegram::sendMessage("冻结卡片失败, {$card->number}");
                }
            } catch (\Exception $e) {
                $failedCards[] = $card->name ?: $card->number;
                Log::error("冻结卡片异常", [
                    'card_id' => $card->id,
                    'card_number' => $card->number,
                    'error' => $e->getMessage()
                ]);
                Telegram::sendMessage("冻结卡片异常");
            }
        }

        // 发送Telegram通知
        $message = "🔒 广告盗刷检测 - 卡片冻结结果\n\n";
        $message .= "广告ID: {$ad->source_id}\n";
        $message .= "广告名称: {$ad->name}\n";
        $message .= "广告账户: {$adAccount->name} ({$adAccount->source_id})\n";
        $message .= "绑定 " . $cards->count() . " 张卡片\n\n";

        if (!empty($frozenCards)) {
            $message .= "✅ 已冻结: " . implode(', ', $frozenCards) . "\n";
        }

        if (!empty($failedCards)) {
            $message .= "❌ 冻结失败: " . implode(', ', $failedCards) . "\n";
        }

        $message .= "\n检测原因: {$detectionResult['reason']}";

        Telegram::sendMessage($message);
    }

    /**
     * 暂停广告
     */
    private function pauseAd(FbAd $ad, array $detectionResult): void
    {
        try {
            // 使用现有的Job来暂停广告
            ActionUpdateFbAdItemStauts::dispatch($ad->source_id, 'PAUSED', 'ad')
                ->delay(now()->addSeconds(2));

            Log::info("已提交暂停广告任务", ['ad_id' => $ad->source_id]);

            // 发送Telegram通知
            $adAccount = $ad->fbAdAccountV2;
            $message = "⏸️ 广告盗刷检测 - 广告已暂停\n\n";
            $message .= "广告ID: {$ad->source_id}\n";
            $message .= "广告名称: {$ad->name}\n";
            $message .= "广告账户: {$adAccount->name} ({$adAccount->source_id})\n";
            $message .= "检测原因: {$detectionResult['reason']}\n";
            $message .= "\n广告暂停任务已提交";

            Telegram::sendMessage($message);

        } catch (\Exception $e) {
            Log::error("暂停广告失败", [
                'ad_id' => $ad->source_id,
                'error' => $e->getMessage()
            ]);

            // 发送失败通知
            $message = "❌ 广告盗刷检测 - 暂停广告失败\n\n";
            $message .= "广告ID: {$ad->source_id}\n";
            $message .= "错误: {$e->getMessage()}";

            Telegram::sendMessage($message);
        }
    }

    /**
     * 处理广告账户权限丢失的情况
     */
    public function handleAccountPermissionLoss($adAccount): void
    {
        Log::warning("检测到广告账户权限丢失", [
            'ad_account_id' => $adAccount->source_id,
            'ad_account_name' => $adAccount->name
        ]);

        // 发送Telegram通知
        $message = "🚨 广告权限丢失，请立即检查 @rancher233\n\n";
        $message .= "广告账户ID: {$adAccount->source_id}\n";
        $message .= "广告账户名称: {$adAccount->name}";

        Telegram::sendMessage($message);

        // 检查并锁定关联的卡片
        $cards = $adAccount->cards()->where('status', 'ACTIVE')->get();

        if ($cards->isEmpty()) {
            Log::info("广告账户没有ACTIVE状态的卡片", [
                'ad_account_id' => $adAccount->source_id
            ]);
            return;
        }

        $frozenCards = [];
        $failedCards = [];

        foreach ($cards as $card) {
            try {
                $provider = $this->cardProviderService->getProviderByCard($card);
                $success = $provider->freezeCard($card->source_id);

                if ($success) {
                    $card->refresh();
                    $frozenCards[] = $card->name ?: $card->number;
                    Log::info("权限丢失 - 成功冻结卡片", [
                        'card_id' => $card->id,
                        'card_number' => $card->number
                    ]);
                } else {
                    $failedCards[] = $card->name ?: $card->number;
                    Log::error("权限丢失 - 冻结卡片失败", [
                        'card_id' => $card->id,
                        'card_number' => $card->number
                    ]);
                }
            } catch (\Exception $e) {
                $failedCards[] = $card->name ?: $card->number;
                Log::error("权限丢失 - 冻结卡片异常", [
                    'card_id' => $card->id,
                    'card_number' => $card->number,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 发送卡片冻结结果通知
        $cardMessage = "🔒 权限丢失 - 卡片冻结结果\n\n";
        $cardMessage .= "广告账户: {$adAccount->name} ({$adAccount->source_id})\n";
        $cardMessage .= "关联 " . $cards->count() . " 张卡片\n\n";

        if (!empty($frozenCards)) {
            $cardMessage .= "✅ 已冻结: " . implode(', ', $frozenCards) . "\n";
        }

        if (!empty($failedCards)) {
            $cardMessage .= "❌ 冻结失败: " . implode(', ', $failedCards);
        }

        Telegram::sendMessage($cardMessage);
    }
}
