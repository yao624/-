<?php

namespace App\Jobs;

use App\Models\FbAd;
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

class ActionUpdateFbAdItemBudget implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $item_source_id;
    private $budget;
    private $budget_type;

    private $object_type;
    public function __construct(string $item_source_id, $object_type, $budget_type, string $budget)
    {
        $this->item_source_id = $item_source_id;
        $this->object_type = $object_type;
        $this->budget = $budget;
        $this->budget_type = $budget_type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("--- start update item {$this->object_type}: {$this->item_source_id} {$this->budget_type}: {$this->budget}");

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
            $endpoint = "https://graph.facebook.com/{$version}/{$this->item_source_id}";
            $query = null;
            $body = [];

            $convert_budget = CurrencyUtils::convertAndFormat($this->budget,'USD', $adAccount->currency );
            $currency_offset = CurrencyUtils::$currencyConfig[$adAccount->currency]['offset'];
            $convert_budget = $currency_offset * $convert_budget;

            Log::debug("converted budget: {$convert_budget}");

            if ($this->budget_type === 'daily_budget') {
                $body['daily_budget'] = $convert_budget;
            } else {
                $body['lifetime_budget'] = $convert_budget;
            }
            Log::info("update ad item budget:");
            Log::debug($body);

            $token = '';
            $fbAccount = null;
            $apiToken = $adAccount->apiTokens()->where('active', true)->first();
            if ($apiToken) {
                $token = $apiToken['token'];
            } else {
                $fbAccount = $adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
            }
//            $fbAccount = $adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();

            $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $body, 'update_ad_item_status', $token);
            Log::debug("update budget resp");
            Log::debug($resp);

            Log::debug("--- start update item {$this->object_type}: {$this->item_source_id} {$this->budget_type}: {$this->budget}, converted: {$convert_budget}");

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
        Log::error('Action update item budget failed: ' . $exception->getMessage());
    }

    public function tags()
    {
        return [
            "Act-Update-{$this->budget_type}",
            "{$this->object_type}",
            "{$this->item_source_id}",
            "{$this->budget}"
        ];
    }
}
