<?php

namespace App\Jobs;

use App\Models\FbAd;
use App\Services\FraudDetectionService;
use App\Services\FraudActionsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FraudDetectionScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $batchSize;
    private $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct($batchSize = 100)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("开始执行防盗刷定时扫描");

        // 预检查：如果没有配置白名单，直接跳过扫描
        if (!FraudDetectionService::shouldPerformScan()) {
            Log::info("没有配置防盗刷白名单，跳过定时扫描");
            return;
        }

        $fraudDetectionService = app(FraudDetectionService::class);
        $fraudActionsService = app(FraudActionsService::class);

        // 查询条件：updated_at在最近1天，且状态不是DELETED或ARCHIVED
        $oneDayAgo = Carbon::now()->subDay();

        $query = FbAd::where('updated_at', '>=', $oneDayAgo)
            ->whereNotIn('status', ['DELETED', 'ARCHIVED', 'PAUSED'])
            ->whereNotNull('creative');

        $totalCount = $query->count();
        Log::info("找到需要扫描的广告数量", ['count' => $totalCount]);

        if ($totalCount === 0) {
            Log::info("没有需要扫描的广告");
            return;
        }

        $processedCount = 0;
        $fraudCount = 0;
        $errorCount = 0;

        // 分批处理
        $query->chunk($this->batchSize, function ($ads) use (
            $fraudDetectionService,
            $fraudActionsService,
            &$processedCount,
            &$fraudCount,
            &$errorCount
        ) {
            foreach ($ads as $ad) {
                try {
                    $detectionResult = $fraudDetectionService->checkAd($ad);

                    if ($detectionResult['is_fraud']) {
                        $fraudCount++;
                        Log::warning("定时扫描发现异常广告", [
                            'ad_id' => $ad->source_id,
                            'reason' => $detectionResult['reason']
                        ]);

                        // 执行相应的行动
                        $fraudActionsService->executeActions($ad, $detectionResult);
                    }

                    $processedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("定时扫描广告失败", [
                        'ad_id' => $ad->source_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info("批次处理完成", [
                'processed' => $processedCount,
                'fraud_detected' => $fraudCount,
                'errors' => $errorCount
            ]);
        });

        Log::info("防盗刷定时扫描完成", [
            'total_scanned' => $processedCount,
            'fraud_detected' => $fraudCount,
            'errors' => $errorCount
        ]);

        // 如果发现了异常广告，发送汇总通知
        if ($fraudCount > 0) {
            $message = "🔍 防盗刷定时扫描结果\n\n";
            $message .= "扫描广告数量: {$processedCount}\n";
            $message .= "发现异常广告: {$fraudCount}\n";
            if ($errorCount > 0) {
                $message .= "处理错误: {$errorCount}\n";
            }
            $message .= "\n详细信息请查看日志";

            \App\Utils\Telegram::sendMessage($message);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("防盗刷定时扫描Job失败", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $message = "❌ 防盗刷定时扫描Job失败\n\n";
        $message .= "错误信息: {$exception->getMessage()}";

        \App\Utils\Telegram::sendMessage($message);
    }

    public function tags(): array
    {
        return ['fraud-detection', 'scheduled-scan'];
    }
}
