<?php

namespace App\Jobs;

use App\Jobs\FacebookUpdateAdCreative;
use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookUpdateAdCreativeRetry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 1;

    protected $adAccountSourceId;
    protected $adSourceId;
    protected $retryCount;

    /**
     * Create a new job instance.
     */
    public function __construct($adAccountSourceId, $adSourceId, $retryCount = 1)
    {
        $this->adAccountSourceId = $adAccountSourceId;
        $this->adSourceId = $adSourceId;
        $this->retryCount = $retryCount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("开始重试广告Creative更新", [
            'ad_account' => $this->adAccountSourceId,
            'ad_id' => $this->adSourceId,
            'retry_count' => $this->retryCount
        ]);

        try {
            // 1. 获取FbAd
            $fbAd = FbAd::where('source_id', $this->adSourceId)->first();
            if (!$fbAd) {
                Log::error("重试失败：找不到广告", ['ad_id' => $this->adSourceId]);
                return;
            }

            // 2. 获取FbAdAccount
            $fbAdAccount = FbAdAccount::where('source_id', $this->adAccountSourceId)->first();
            if (!$fbAdAccount) {
                Log::error("重试失败：找不到广告账户", ['ad_account' => $this->adAccountSourceId]);
                return;
            }

            // 3. 获取API Token来检查广告状态
            $fbApiToken = $fbAdAccount->apiTokens()
                ->where('token_type', 1)
                ->where('active', true)
                ->first();

            if (!$fbApiToken) {
                Log::error("重试失败：找不到可用的API Token", ['ad_account' => $this->adAccountSourceId]);
                return;
            }

            // 4. 获取广告的最新状态
            $adStatus = $this->getAdStatus($fbApiToken->token);
            if (!$adStatus) {
                Log::error("重试失败：无法获取广告状态", ['ad_id' => $this->adSourceId]);
                return;
            }

            // 5. 检查effective_status是否仍为DISAPPROVED
            if ($adStatus['effective_status'] !== 'DISAPPROVED') {
                Log::info("重试取消：广告状态不是DISAPPROVED", [
                    'ad_id' => $this->adSourceId,
                    'effective_status' => $adStatus['effective_status']
                ]);
                return;
            }

            // 6. 使用现有的 handleDisapprovedAd 方法来处理重试
            // 这个方法会自动检查所有必要的条件，并生成新的语言来重试
            $handleMethod = new \ReflectionMethod(FbAd::class, 'handleDisapprovedAd');
            $handleMethod->setAccessible(true);
            $handleMethod->invoke(null, $fbAd);

            Log::info("重试任务已通过handleDisapprovedAd处理", [
                'ad_account' => $this->adAccountSourceId,
                'ad_id' => $this->adSourceId,
                'retry_count' => $this->retryCount
            ]);

            // 7 所有条件满足，发送Telegram通知
            $this->sendRetryNotification();

        } catch (\Exception $e) {
            Telegram::sendMessage("重试任务执行失败: " . $this->adSourceId);
            Log::error("重试任务执行失败", [
                'ad_account' => $this->adAccountSourceId,
                'ad_id' => $this->adSourceId,
                'retry_count' => $this->retryCount,
                'error' => $e->getMessage()
            ]);

            // 如果重试次数少于某个阈值，可以继续调度下一次重试
            if ($this->retryCount < 10) { // 最多重试3次
                FacebookUpdateAdCreativeRetry::dispatch(
                    $this->adAccountSourceId,
                    $this->adSourceId,
                    $this->retryCount + 1
                )->onQueue('frontend')->delay(now()->addMinutes(20));

                Log::info("调度下一次重试", [
                    'ad_id' => $this->adSourceId,
                    'next_retry_count' => $this->retryCount + 1
                ]);
            } else {
                Log::error("已达到最大重试次数，停止重试", [
                    'ad_id' => $this->adSourceId,
                    'retry_count' => $this->retryCount
                ]);
            }
        }
    }

    /**
     * 获取广告状态
     */
    private function getAdStatus($apiToken)
    {
        try {
            $url = "https://graph.facebook.com/v21.0/{$this->adSourceId}";
            $query = [
                'fields' => 'effective_status'
            ];

            // $response = FbUtils::makeRequest($url, 'GET', $params);
            $response = FbUtils::makeRequest(null, $url, $query, 'GET', '', '', $apiToken);

            if ($response && isset($response['effective_status'])) {
                return $response;
            }

            return null;
        } catch (\Exception $e) {
            Telegram::sendMessage("获取广告状态失败: " . $this->adSourceId);
            Log::error("获取广告状态失败", [
                'ad_id' => $this->adSourceId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 发送重试通知
     */
    private function sendRetryNotification()
    {
        $message = "🔄 FB广告Creative更新自动重试\r\n";
        $message .= "广告账户: {$this->adAccountSourceId}\r\n";
        $message .= "广告ID: {$this->adSourceId}\r\n";
        $message .= "重试次数: {$this->retryCount}\r\n";
        $message .= "处理方式: 自动添加新语言\r\n";
        $message .= "时间: " . now()->format('Y-m-d H:i:s');

        try {
            Telegram::sendMessage($message);
        } catch (\Exception $e) {
            Log::error('发送重试通知失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取队列标签
     */
    public function tags(): array
    {
        return [
            'fb-ad-creative-retry',
            'ad-account:' . $this->adAccountSourceId,
            'ad:' . $this->adSourceId
        ];
    }
}
