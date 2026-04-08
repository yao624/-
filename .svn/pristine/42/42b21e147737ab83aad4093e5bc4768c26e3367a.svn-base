<?php

namespace App\Jobs;

use App\Enums\BidStrategy;
use App\Enums\BudgetType;
use App\Enums\ConversionLocationType;
use App\Enums\EnumAdsetup;
use App\Enums\ObjectiveType;
use App\Enums\OperatorType;
use App\Models\AdLog;
use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbAdTemplate;
use App\Models\FbApiToken;
use App\Models\FbCampaign;
use App\Support\MetaAdCreationSplitRules;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class FacebookCreateCampaignV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $fbAdAccountID;
    private $operatorID;
    private string $operatorType;
    private $fbAdTemplateID;
    private $options;
    private AdLog $adLog;
    private $campaign_source_id;
    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, string $operatorType, $operatorID, $adTemplateID, $options, AdLog $adLog)
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->operatorType = $operatorType;
        $this->operatorID = $operatorID;
        $this->fbAdTemplateID = $adTemplateID;
        $this->options = $options;
        $this->adLog = $adLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        # 参考文档
        # https://developers.facebook.com/docs/marketing-api/reference/ad-campaign-group/#example-2
        $fbAdTemplate = FbAdTemplate::query()->firstWhere('id', $this->fbAdTemplateID);

        $fbAdAccount = FbAdAccount::query()->firstWhere('id', $this->fbAdAccountID);
        Log::debug($fbAdAccount);

        // 检查 Ad account 是否有 pixel 的权限
        if (isset($this->options['pixel_id']) && $this->options['pixel_id']) {
            $isRelated = $fbAdAccount->fbPixels()->where('fb_pixels.id', $this->options['pixel_id'])->exists();
            if (!$isRelated) {
                throw new \Exception("Ad Account {$fbAdAccount->source_id} don't have pixel({$this->options['pixel_id']}) permission");
            }
        }

        $existed_campaign = null;
        $campaign_source_id = null;
        if (isset($this->options['campaign_id'])) {
            $existed_campaign = FbCampaign::query()->where('id', $this->options['campaign_id'])->firstOrFail();
        }

//        $fbAdTemplate = [
//            "name" => "tpl-01-cbo-daily-normal-sale-web-purchase-video",
//            "campaign_name" => "us-sales-{{date}}-{{random}}",
//            "adset_name" => "us-web-sales-{{date}}-{{random}}",
//            "ad_name" => "us-video",
//            "bid_strategy" => "LOWEST_COST_WITHOUT_CAP",
//            "bid_amount" => "",
//            "budget_level" => "campaign",
//            "budget_type" => "daily",
//            "budget" => "200000",
//            "objective" => ObjectiveType::Sales->value,
//            "accelerated" => false,
//            "conversion_location" => ConversionLocationType::Website->value,
//            "optimization_goal" => "OFFSITE_CONVERSIONS",
//            "pixel_event" => "PURCHASE",
//            "advantage_plus_audience" => false,
//            "genders" => "1",
//            "age_min" => "35",
//            "age_max" => "65"
//        ];

        if (!$existed_campaign) {
            Log::debug("create new campaign");
            // 如果不是通过旧的 campaign 创建
            $payload = [
                'name' => $this->processCampaignName($fbAdTemplate['campaign_name'], $fbAdAccount->source_id),
                'status' => 'PAUSED',
                'special_ad_categories' => json_encode([]),
                'buying_type' => 'AUCTION'
            ];

            # objective, see https://developers.facebook.com/docs/marketing-api/reference/ad-campaign-group/#odax
            # Objective is invalid. Use one of:
            #      APP_INSTALLS, BRAND_AWARENESS, EVENT_RESPONSES, LEAD_GENERATION, LINK_CLICKS, LOCAL_AWARENESS, MESSAGES,
            #       OFFER_CLAIMS, PAGE_LIKES, POST_ENGAGEMENT, PRODUCT_CATALOG_SALES, REACH, STORE_VISITS, VIDEO_VIEWS,
            #      OUTCOME_AWARENESS, OUTCOME_ENGAGEMENT, OUTCOME_LEADS, OUTCOME_SALES, OUTCOME_TRAFFIC,
            #         OUTCOME_APP_PROMOTION, CONVERSIONS
//        $objective_map = [
//            'Sales' => 'OUTCOME_SALES',
//            'App Promotion' => 'OUTCOME_APP_PROMOTION',
//            'Leads' => 'OUTCOME_LEADS',
//            'Engagement' => 'OUTCOME_ENGAGEMENT',
//            'Traffic' => 'OUTCOME_TRAFFIC'
//        ];
//        $objective = $objective_map[$fbAdTemplate['objective']];

            $payload['objective'] = $fbAdTemplate['objective'];

            # budget level and budget
            $convert_budget = CurrencyUtils::convertAndFormat($fbAdTemplate['budget'],'USD', $fbAdAccount->currency );
            $currency_offset = CurrencyUtils::$currencyConfig[$fbAdAccount->currency]['offset'];
            $convert_budget = intval($currency_offset * $convert_budget);

            if ($fbAdTemplate['budget_level'] === 'campaign') {
                if ($fbAdTemplate['budget_type'] === 'daily') {
                    $payload['daily_budget'] = $convert_budget;
                    $payload['bid_strategy'] = $fbAdTemplate['bid_strategy'];
                } elseif ($fbAdTemplate['budget_type'] === BudgetType::LifeTime->value) {
                    $payload['lifetime_budget'] = $convert_budget;
                    $payload['bid_strategy'] = $fbAdTemplate['bid_strategy'];
                }

                $bs = $fbAdTemplate['bid_strategy'] ?? '';

                # 系列层级：COST_CAP / BID_CAP 的 bid_amount；ROAS → bid_constraints（与 9 步创建映射一致）
                $rawBid = trim((string) ($fbAdTemplate['bid_amount'] ?? ''));
                if ($rawBid !== '') {
                    if ($bs === BidStrategy::MinRoas->value) {
                        $floor = (float) $rawBid;
                        if ($floor > 0) {
                            $payload['bid_constraints'] = json_encode(['roas_average_floor' => $floor]);
                        }
                    } elseif (in_array($bs, [BidStrategy::BidCap->value, BidStrategy::CostPerResultGoal->value], true)) {
                        $convert_bid = CurrencyUtils::convertAndFormat($rawBid, 'USD', $fbAdAccount->currency);
                        $curOff = CurrencyUtils::$currencyConfig[$fbAdAccount->currency]['offset'];
                        $payload['bid_amount'] = (int) round($curOff * (float) $convert_bid);
                    }
                }

                # pacing：https://developers.facebook.com/docs/marketing-api/bidding/guides/advantage-campaign-budget/
                if (!empty($fbAdTemplate['accelerated'])) {
                    $payload['pacing_type'] = ['no_pacing'];
                } elseif ($bs === BidStrategy::BidCap->value) {
                    $payload['pacing_type'] = ['standard'];
                }

                # 自定义广告系列花费限额（options.campaign_spend_cap_usd 为 USD）
                if (!empty($this->options['campaign_spend_cap_usd'])) {
                    $capUsd = (float) $this->options['campaign_spend_cap_usd'];
                    if ($capUsd > 0) {
                        $convert_cap = CurrencyUtils::convertAndFormat((string) $capUsd, 'USD', $fbAdAccount->currency);
                        $curOff = CurrencyUtils::$currencyConfig[$fbAdAccount->currency]['offset'];
                        $payload['spend_cap'] = (int) round($curOff * (float) $convert_cap);
                    }
                }
            }

            # api create campaign
            Log::info("create campaign payload:");
            Log::debug($payload);

            $version = FbUtils::$API_Version;


            $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/campaigns";
            $query = null;

            if ($this->operatorType == OperatorType::FacebookUser->value) {
                $fbAccount = FbAccount::query()->firstWhere('id', $this->operatorID)
                    ->where('token_valid', true)->firstOrFail();
                $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $payload);
            } else if ($this->operatorType == OperatorType::BMUser->value) {
                $fbApiToken = FbApiToken::query()->firstWhere('id', $this->operatorID);

                $resp = FbUtils::makeRequest(null, $endpoint, $query, 'POST', $payload, '', $fbApiToken['token']);
            }
            Log::debug("create campaign resp");
            Log::debug($resp);
            if (isset($resp['id'])) {
                $campaign_source_id = $resp['id'];
            }
        } else {
            Log::info("use existed campaign");
            $campaign_source_id = $existed_campaign->source_id;
        }

        if ($campaign_source_id) {
            // 有 ID 表示创建成功
            Log::debug("campaign id: {$campaign_source_id}");
//            $campaignId = $resp['id'];
            $this->campaign_source_id = $campaign_source_id;
//            $this->adLog->campaign_id = $campaignId;
//            $this->adLog->campaign_created = true;
            $this->adLog->campaigns()->syncWithoutDetaching([
                $campaign_source_id => [ 'campaign_created' => true ]
            ]);
            $this->adLog->save();

            // 同步 campaign
//            $today = Carbon::now()->format('Y-m-d');
//            FacebookFetchCampaign::dispatch($this->fbAdAccountID, $today,
//                $today, null, false, false, 1, [
//                    'field' => 'id',
//                    'operator' => 'IN',
//                    'value' => [$campaignId]
//                ]);
            FacebookFetchCampaignV2::dispatch($this->fbAdAccountID, null, null, null, false, false, false,[
                'field' => 'id',
                'operator' => 'IN',
                'value' => [$campaign_source_id]
            ]);

            $launchMode = $this->options['launch_mode'] ?? 3;
//            $launchMode = 3;
            Log::debug("prepare to launch adset, launch mode: {$launchMode}");

            $multiTargetingIds = $this->options['targeting_template_ids'] ?? null;
            if ($launchMode === 2 && is_array($multiTargetingIds) && count($multiTargetingIds) > 1) {
                Log::warning('Meta 9-step: launch_mode=2 且多素材/多帖子拆分时，多个定向包仅使用首个模板受众（与素材数笛卡尔积未实现）');
            }

            $materials = collect($this->options['material_id_list'] ?? []);
            $posts = collect($this->options['post_id_list'] ?? []);
            $productSets = collect($this->options['product_set_ids'] ?? []);
            if ($materials->count() > 0) {
                $adSetup = EnumAdsetup::Material->value;
            } elseif ($posts->count() > 0) {
                $adSetup = EnumAdsetup::Post->value;
            } elseif ($productSets->count() > 0) {
                $adSetup = EnumAdsetup::Catalog->value;
            }

            // 如果是 launch mode 是 1-N-1=2
            if ($launchMode === 2) {
                // 如果是通过素材创建广告
                if ($adSetup === EnumAdsetup::Material->value) {
                    $slotPayloads = $this->materialJobsFromCreativeSlotsOrList();
                    if (count($slotPayloads) > 1) {
                        $batchJobs = [];
                        foreach ($slotPayloads as $index => $options) {
                            $batchJobs[] = (new FacebookCreateAdsetV2($this->fbAdAccountID, $this->operatorType,
                                $this->operatorID, $campaign_source_id, $this->fbAdTemplateID, $options, $this->adLog))
                                ->delay(MetaAdCreationSplitRules::staggerDelaySecondsForAdsetJob($index, $this->options['split_rules'] ?? []));
                        }

                        Bus::batch($batchJobs)->finally(function (Batch $batch) {
                            Log::debug("finally, all adset batch are finished");
                        })->onQueue('facebook')->allowFailures()->dispatch();

                    } else {
                        $opts = count($slotPayloads) === 1 ? $slotPayloads[0] : $this->options;
                        FacebookCreateAdsetV2::dispatch($this->fbAdAccountID, $this->operatorType,
                            $this->operatorID, $campaign_source_id, $this->fbAdTemplateID, $opts, $this->adLog);
                    }
                } elseif ($adSetup === EnumAdsetup::Post->value) {
                    // 如果是通过 post 创建广告

                    // 如果 post 数量大于 1
                    if ($posts->count() > 1) {
                        $batchJobs = [];
                        $options = $this->options;
                        foreach ($posts as $index => $post_id) {
                            $options['post_id'] = $post_id;
                            $batchJobs[] = (new FacebookCreateAdsetV2($this->fbAdAccountID, $this->operatorType,
                                $this->operatorID, $campaign_source_id, $this->fbAdTemplateID, $options, $this->adLog))
                                ->delay(MetaAdCreationSplitRules::staggerDelaySecondsForAdsetJob($index, $this->options['split_rules'] ?? []));
                        }

                        Bus::batch($batchJobs)->finally(function (Batch $batch) {
                            Log::debug("finally, all adset batch are finished");
                        })->onQueue('facebook')->allowFailures()->dispatch();
                    } else {
                        // 如果只有一个 post
                        FacebookCreateAdsetV2::dispatch($this->fbAdAccountID, $this->operatorType,
                            $this->operatorID, $campaign_source_id, $this->fbAdTemplateID, $this->options, $this->adLog);
                    }
                } elseif ($adSetup === EnumAdsetup::Catalog->value) {
                    // 如果是通过 product set 创建广告

                    // 如果 product sets 数量大于 1
                    if ($productSets->count() > 1) {
                        $batchJobs = [];
                        $options = $this->options;
                        foreach ($productSets as $index => $product_set) {
                            $options['product_set'] = $product_set;
                            $batchJobs[] = (new FacebookCreateAdsetV2($this->fbAdAccountID, $this->operatorType,
                                $this->operatorID, $campaign_source_id, $this->fbAdTemplateID, $options, $this->adLog))
                                ->delay(MetaAdCreationSplitRules::staggerDelaySecondsForAdsetJob($index, $this->options['split_rules'] ?? []));
                        }

                        Bus::batch($batchJobs)->finally(function (Batch $batch) {
                            Log::debug("finally, all adset batch are finished");
                        })->onQueue('facebook')->allowFailures()->dispatch();
                    } else {
                        // 如果只有一个 product set
                        FacebookCreateAdsetV2::dispatch($this->fbAdAccountID, $this->operatorType,
                            $this->operatorID, $campaign_source_id, $this->fbAdTemplateID, $this->options, $this->adLog);
                    }
                }

            } else {
                // launch_mode=3（默认 1-1-N）：系列已创建后，在同一 campaign_id 下创建多个广告组。
                // Meta：POST /act_{ad_account_id}/adsets，参数含 campaign_id（归属系列）与 targeting 等。
                // 文档：https://developers.facebook.com/docs/marketing-api/reference/ad-campaign/
                //      https://developers.facebook.com/docs/marketing-api/reference/ad-set/
                Log::debug("after create campaign, not 1-N-1");
                $templateIds = $this->options['targeting_template_ids'] ?? [$this->fbAdTemplateID];
                if (! is_array($templateIds)) {
                    $templateIds = [$this->fbAdTemplateID];
                }
                $templateIds = array_values(array_filter($templateIds));
                if (count($templateIds) === 0) {
                    $templateIds = [$this->fbAdTemplateID];
                }
                foreach ($templateIds as $index => $tplId) {
                    FacebookCreateAdsetV2::dispatch($this->fbAdAccountID, $this->operatorType,
                        $this->operatorID, $campaign_source_id, $tplId, $this->options, $this->adLog)
                        ->delay(MetaAdCreationSplitRules::staggerDelaySecondsForAdsetJob($index, $this->options['split_rules'] ?? []));
                }
            }
        }

    }

    /**
     * 按素材（creative_asset_slots）优先：每槽位一条任务；否则按 material_id_list。
     *
     * @return array<int, array<string, mixed>>
     */
    private function materialJobsFromCreativeSlotsOrList(): array
    {
        $base = $this->options;
        $slots = $base['creative_asset_slots'] ?? [];
        $out = [];
        if (is_array($slots)) {
            foreach ($slots as $slot) {
                if (! is_array($slot)) {
                    continue;
                }
                $mid = trim((string) ($slot['material_id'] ?? ''));
                if ($mid === '') {
                    continue;
                }
                $o = $base;
                $o['material_id'] = $mid;
                $o['creative_asset_slot'] = $slot;
                $out[] = $o;
            }
        }
        if (count($out) > 0) {
            return $out;
        }
        $materials = collect($base['material_id_list'] ?? []);
        foreach ($materials as $mid) {
            if ($mid === null || $mid === '') {
                continue;
            }
            $o = $base;
            $o['material_id'] = $mid;
            $out[] = $o;
        }

        return $out;
    }

    public function tags(): array
    {
        return [
            'FB-Create',
            'FB-Create-Camp',
            "{$this->fbAdAccountID} - {$this->operatorType} - {$this->operatorID} - {$this->fbAdTemplateID}",
        ];
    }

    function processCampaignName($name, $adAccountSourceId) {
        // 替换 {{datetime}} 宏
//        $name = str_replace('{{date}}', date('Y-m-d'), $name);

        // 获取当前时间并转换为 UTC+8 时区
        $currentDateTime = Carbon::now('UTC')->addHours(8);
        // 格式化日期时间为指定的格式
        $formattedDateTime = $currentDateTime->format('m/d-H:i:s');
        $name = str_replace('{{date}}', "($formattedDateTime)", $name);


        // 替换 {{random}} 宏
        if (strpos($name, '{{random}}') !== false ) {
            $randomString = substr(str_shuffle(md5(time())), 0, 6);
            $name = str_replace('{{random}}', $randomString, $name);
            $this->options['random'] = $randomString;
        }

        $adAccountSourceIdSub = substr($adAccountSourceId, -4);
        $name = str_replace('{{acc.id}}', $adAccountSourceIdSub, $name);


        return $name;
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        $msg = "Failed to create campaign: {$this->operatorType} : {$this->operatorID}, log id: {$this->adLog->id}";

        if ($this->campaign_source_id) {
            $this->adLog->campaigns()->syncWithoutDetaching([
                $this->campaign_source_id => [
                    'campaign_created' => false,
                    'campaign_failed_reason' => $exception->getMessage()
                ]
            ]);
        } else {
            $this->adLog->is_success = false;
            $this->adLog->failed_reason = $exception->getMessage();
        }
        $this->adLog->save();

        Log::error('Failed to create campaign: ' . $exception->getMessage());
        Telegram::sendMessage($msg);
    }
}
