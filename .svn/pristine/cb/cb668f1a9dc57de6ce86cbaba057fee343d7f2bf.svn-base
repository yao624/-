<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbPixel;
use App\Utils\FbUtils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookCreateAdset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $fbAccountID;
    private $fbAdAccountID;
    private $campaignID;
    private $item;

    /**
     * Create a new job instance.
     */
    public function __construct($fbAccountID, $fbAdAccountID, $campaignID, $item)
    {
        $this->fbAccountID = $fbAccountID;
        $this->fbAdAccountID = $fbAdAccountID;
        $this->campaignID = $campaignID;
        $this->item = $item;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $fbAdAccount = FbAdAccount::query()->findOrFail($this->fbAdAccountID);
        $fbAccount = FbAccount::query()->findOrFail($this->fbAccountID);
        $pixel = FbPixel::query()->findOrFail($this->item['pixel_id']);

        Log::info("Create Adset, account: {$fbAccount->name}, ad account: {$fbAdAccount->name}, camp id: {$this->campaignID}");

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/adsets";
        $query = null;

        # https://developers.facebook.com/docs/marketing-api/reference/ad-promoted-object/
        $payload = [
            'name' => $this->processName($this->item['adset_name_tpl']),
            'billing_event' => 'IMPRESSIONS',
            'campaign_id' => $this->campaignID,
            'targeting' => json_encode([
                "age_max" => 65,
                "age_min" => 21,
                "genders" => [$this->item['genders']],
                "age_range" => [$this->item['age_range'][0], $this->item['age_range'][1]],
                "geo_locations" => [
                    "countries" => $this->item['geo'],
                    "location_types" => [
                        "home",
                        "recent"
                    ]
                ],
                "targeting_automation" => [
                    "advantage_audience" => 1
                ],
                "targeting_optimization" => "expansion_all",
                "brand_safety_content_filter_levels" => [
                    "FACEBOOK_STANDARD",
                    "AN_STANDARD"
                ]
            ]),
            'optimization_goal' => 'OFFSITE_CONVERSIONS',
            'promoted_object' => json_encode([
                "custom_event_type" => "PURCHASE",
                "pixel_id" => $pixel['pixel']
            ]),
            'status' => 'PAUSED'
        ];

        $budget_period = $this->item['budget_period'];
        $budget_type = $this->item['budget_type'];
        $budget = $this->item['budget'];
        $budget_fixed = strval(intval(round($budget, 2) * 100));
        if ($budget_type == 'ABO' && $budget_period == 'daily') {
            $payload['daily_budget'] = $budget_fixed;
            $payload['bid_strategy'] = 'LOWEST_COST_WITHOUT_CAP';
        }
        Log::info("create adset payload:");
        Log::debug($payload);
        $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $payload);
        Log::debug("create adset resp");
        Log::debug($resp);
        if (collect($resp)->has('id')) {
            Log::info("adset created");
            $adsetID = $resp['id'];
            // 创建 Ad, 遍历 ad creative 来创建 ad
            $creatives = $this->item['creatives'];
            foreach ($creatives as $index => $creative) {
                $creative['ad_name_tpl'] = $creative['ad_name_tpl'] . " #" . ($index + 1);
                FacebookCreateAd::dispatch($this->fbAccountID, $this->fbAdAccountID, $adsetID, $creative)->onQueue('facebook');
            }
        } else {
            Log::warning("failed to create adset");
            Log::warning($resp);
        }

    }

    function processName($name) {
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
            'FB-Create-Adset',
            "{$this->fbAdAccountID}",
            "C-{$this->campaignID}"
        ];
    }
}
