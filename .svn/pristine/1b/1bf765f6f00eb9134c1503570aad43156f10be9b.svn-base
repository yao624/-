<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAd;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Utils\FbUtils;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ActionUpdateFbAdItemStauts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $item_source_id;
    private $status;
    private $item_type;
    /**
     * Create a new job instance.
     */
    public function __construct($item_source_id, $status, $item_type)
    {
        $this->item_source_id = $item_source_id;
        $this->status = $status;
        $this->item_type = $item_type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("--- start update item {$this->item_source_id} to status: {$this->status}");

        try {
            if ($this->item_type === 'adset') {
                $item = FbAdset::query()->firstWhere('source_id', $this->item_source_id);
            } else if ($this->item_type === 'ad') {
                $item = FbAd::query()->firstWhere('source_id', $this->item_source_id);
            } else if ($this->item_type === 'campaign') {
                $item = FbCampaign::query()->firstWhere('source_id', $this->item_source_id);
            }
            if ($item->count() == 0) {
                Log::warning("not find item");
                return;
            }
            if ($item->status == $this->status) {
                Log::info("status is already: {$this->status}, return");
                return;
            }

            $adAccount = $item->fbAdAccount;
            Log::debug($adAccount);

            $token = '';
            $fbAccount = null;
            $apiToken = $adAccount->apiTokens()->where('active', true)->first();
            if ($apiToken) {
                $token = $apiToken['token'];
            } else {
                $fbAccount = $adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
            }
//            $fbAccount = $adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();

            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/{$this->item_source_id}";
            $query = null;
            $body = [
                'status' => $this->status
            ];

            Log::info("update ad item status:");
            Log::debug($body);
            $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $body, 'update_ad_item_status', $token);
            Log::debug("update adset status resp");
            Log::debug($resp);

            Log::debug("--- end update item {$this->item_source_id} to status: {$this->status}");

            // 判断 API 响应是否表示成功
            if (!isset($resp['success']) || $resp['success'] !== true) {
                throw new Exception("Error: API return false: {$this->item_source_id}");
            }

            // 更新状态
            if (isset($resp['success']) && $resp['success'] === true) {
                $item->status = $this->status;
                $item->save();
            }
        } catch (Exception $e) {
            Log::error("Exception: " . $e->getMessage());
            throw $e;
        }


    }

    public function failed(\Throwable $exception)
    {
        Log::error('Action update item status failed: ' . $exception->getMessage());
    }

    public function tags()
    {
        return [
            "Act-Update-{$this->item_type}",
            "{$this->item_source_id}",
            "{$this->status}"
        ];
    }
}
