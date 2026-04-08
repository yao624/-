<?php

namespace App\Jobs;

use App\Services\CardProviderService;
use App\Models\CardProvider;
use App\Utils\Telegram;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VccBalanceCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const BALANCE_THRESHOLD = 1000.0; // 余额阈值

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CardProviderService $cardProviderService): void
    {
        try {
            Log::info("VCC余额检查任务开始执行");

                                    // 获取 Adpos Provider（和现有卡片同步系统一样的方式）
            $cardProvider = CardProvider::where('name', 'ap')
                ->where('active', true)
                ->first();

            if (!$cardProvider) {
                Log::error("未找到活跃的 Adpos CardProvider 记录");
                return;
            }

            $provider = $cardProviderService->getProviderByModel($cardProvider);

            // 检查 Provider 是否有 getAccountBalance 方法
            if (!method_exists($provider, 'getAccountBalance')) {
                Log::error("Adpos Provider 不支持账户余额查询");
                return;
            }

            // 获取账户余额
            $balance = $provider->getAccountBalance();

            Log::info("当前VCC账户余额", [
                'balance' => $balance,
                'threshold' => self::BALANCE_THRESHOLD
            ]);

            // 检查余额是否低于阈值
            if ($balance < self::BALANCE_THRESHOLD) {
                $message = "VCC 充值 @rancher233";

                Log::warning("VCC账户余额不足", [
                    'current_balance' => $balance,
                    'threshold' => self::BALANCE_THRESHOLD,
                    'message' => $message
                ]);

                // 发送 Telegram 通知
                Telegram::sendMessage($message);

                Log::info("VCC余额不足通知已发送");
            } else {
                Log::info("VCC账户余额充足，无需通知");
            }

        } catch (Exception $e) {
            Log::error("VCC余额检查任务执行失败", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 发送错误通知
            $errorMessage = "VCC余额检查失败: " . $e->getMessage();
            try {
                Telegram::sendMessage($errorMessage);
            } catch (Exception $notificationError) {
                Log::error("发送错误通知失败", [
                    'error' => $notificationError->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("VCC余额检查任务最终失败", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}