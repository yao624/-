<?php

namespace App\Jobs;

use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ActionCopyFbObject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $item_source_id;
    private $object_type;
    private $count;
    public function __construct(string $item_source_id, $object_type, $count)
    {
        $this->item_source_id = $item_source_id;
        $this->object_type = $object_type;
        $this->count = $count;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("--- start copy item {$this->object_type}: {$this->item_source_id}, count: {$this->count} ");

        try {
            if ($this->object_type === 'adset') {
                $item = FbAdset::query()->firstWhere('source_id', $this->item_source_id);
            } else if ($this->object_type === 'campaign') {
                $item = FbCampaign::query()->firstWhere('source_id', $this->item_source_id);
            }
            if ($item->count() == 0) {
                Log::warning("not find item");
                return;
            }

            $adAccount = $item->fbAdAccount;
            Log::debug($adAccount);
            if ($adAccount->account_status != 'ACTIVE') {
                Log::error("Ad Account: {$adAccount->source_id} status is not ACTIVE");
                return;
            }

            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}";
            $query = null;
            $body = [];

            $asyncBatch = [];

            for ($i = 1; $i <= $this->count; $i++) {
                $asyncBatch[] = [
                    'method' => 'POST',
                    'relative_url' => "{$this->item_source_id}/copies",
                    'name' => "async_copy{$i}",
                    'body' => "name=copy_{$i}&deep_copy=true",
//                    'body' => "deep_copy=true",
                ];
            }
            $body['asyncbatch'] = json_encode($asyncBatch);
//            $body['name'] = 'async-copy';

            Log::info("copy ad item:");
            Log::debug($body);

            $token = '';
            $fbAccount = null;
            $apiToken = $adAccount->apiTokens()->where('active', true)->first();
            if ($apiToken) {
                $token = $apiToken['token'];
            } else {
                $fbAccount = $adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
            }

            $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $body, 'copy_fb_items', $token);
            Log::debug("copy item resp");
            Log::debug($resp);

            Log::debug("--- start copy item {$this->object_type}: {$this->item_source_id} ");

            // 判断 API 响应是否表示成功
            if (!isset($resp['success']) || $resp['success'] !== true) {
                throw new Exception("Error: API return false: {$this->item_source_id}");
            }

        } catch (Exception $e) {
            Log::error("Exception: " . $e->getMessage());
            throw $e;
        }


    }

    public function failed(\Throwable $exception)
    {
        Log::error('Action copy item failed: ' . $exception->getMessage());
    }

    public function tags()
    {
        return [
            "Act-Copy-{$this->object_type}",
            "{$this->item_source_id}",
            "{$this->count}",
        ];
    }
}
