<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Utils\FbUtils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookCreateCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $item;
    private $fbAccountID;
    private $fbAdAccountID;

    /**
     * Create a new job instance.
     */
    public function __construct($fbAccountID, $fbAdAccountID, $item)
    {
        $this->item = $item;
        $this->fbAccountID = $fbAccountID;
        $this->fbAdAccountID = $fbAdAccountID;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("ad account id: {$this->fbAdAccountID}");
        Log::debug("acc id: {$this->fbAccountID}");

        $fbAdAccount = FbAdAccount::query()->findOrFail($this->fbAdAccountID);
        $fbAccount = FbAccount::query()->findOrFail($this->fbAccountID);

        Log::info("Create Campaign, account: {$fbAccount->name}, ad account: {$fbAdAccount->name}");

        /**
        let params = {
        'name': camp_name,
        'objective': 'OUTCOME_SALES',
        'status': 'PAUSED',
        'special_ad_categories': ['NONE'],
        'buying_type': 'AUCTION'
        }
        if (cbo) {
        params['daily_budget'] = '2000'
        params['bid_strategy'] = 'LOWEST_COST_WITHOUT_CAP'
        }
         */
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/campaigns";
        $query = null;

        # https://github.com/facebook/facebook-php-business-sdk/blob/main/src/FacebookAds/Object/Values/CampaignObjectiveValues.php
        $objective_map = [
            'Sales' => 'OUTCOME_SALES',
            'App Promotion' => 'OUTCOME_APP_PROMOTION',
            'Leads' => 'OUTCOME_LEADS',
            'Engagement' => 'OUTCOME_ENGAGEMENT',
            'Traffic' => 'OUTCOME_TRAFFIC'
        ];
        $objective = $objective_map[$this->item['campaign_objective']];

        $payload = [
            'name' => $this->processCampaignName($this->item['campaign_name_tpl']),
            'objective' => $objective,
            'status' => 'PAUSED',
            'special_ad_categories' => json_encode([]),
            'buying_type' => 'AUCTION'
        ];

        # TODO: budget period 分为 daily 和 lifetime, 暂不处理
        $budget_period = $this->item['budget_period'];
        $budget_type = $this->item['budget_type'];
        $budget = $this->item['budget'];
        $budget_fixed = strval(intval(round($budget, 2) * 100));
        if ($budget_type == 'CBO') {
            $payload['daily_budget'] = $budget_fixed;
            $payload['bid_strategy'] = 'LOWEST_COST_WITHOUT_CAP';
        }

        Log::info("create campaign payload:");
        Log::debug($payload);
        $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $payload);
        Log::debug("create campaign resp");
        Log::debug($resp);
        if (collect($resp)->has('id')) {
            $campaign_id = $resp['id'];
            $this->item['campaign_id'] = $campaign_id;
            // 检查 multiple_adset，是否创建多个adset
            // 如果 multiple_adset 是 true, 则，根据 creatives，创建多个 create adset 的 job,
            $multipleAdset = $this->item['multiple_adset'];
            if ($multipleAdset) {
                $creatives = $this->item['creatives'];
                foreach ($creatives as $index => $creative) {
                    $newItem = $this->item;
                    $newItem['adset_name_tpl'] = $newItem['adset_name_tpl'] . " #" . ($index + 1);
                    $newItem['creatives'] = [$creative];
                    FacebookCreateAdset::dispatch($this->fbAccountID, $this->fbAdAccountID, $campaign_id, $newItem)->onQueue('facebook');
                }
            } else {
                FacebookCreateAdset::dispatch($this->fbAccountID, $this->fbAdAccountID, $campaign_id, $this->item)->onQueue('facebook');
            }
        }
    }

    function processCampaignName($name) {
        // 替换 {{datetime}} 宏
        $name = str_replace('{{date}}', date('Y-m-d'), $name);

        // 替换 {{random}} 宏
        $randomString = substr(str_shuffle(md5(time())), 0, 4);
        $name = str_replace('{{random}}', $randomString, $name);

        return $name;
    }

    public function tags(): array
    {
        return [
            'FB-Create',
            'FB-Create-Camp',
            "{$this->fbAdAccountID}",
        ];
    }
}
