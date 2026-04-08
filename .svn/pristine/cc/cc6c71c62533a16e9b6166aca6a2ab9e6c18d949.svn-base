<?php

namespace App\Jobs;

use App\Enums\BidStrategy;
use App\Enums\BudgetLevel;
use App\Enums\BudgetType;
use App\Enums\ConversionLocationType;
use App\Enums\EnumAdsetup;
use App\Enums\ObjectiveType;
use App\Enums\OperatorType;
use App\Models\AdLog;
use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbAdTemplate;
use App\Models\FbApp;
use App\Models\FbApiToken;
use App\Models\FbCampaign;
use App\Models\FbPage;
use App\Models\FbPixel;
use App\Support\MetaAdCreationSplitRules;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class FacebookCreateAdsetV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $fbAdAccountID;
    private string $operatorType;
    private $operatorID;
    private $fbAdTemplateID;
    private $campaignID;
    private $options;
    private AdLog $adLog;
    private ?string $adset_source_id = null;
    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $operatorType, $operatorID, $campaignID, $adTemplateID, $options,
                                AdLog $adLog)
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->operatorType = $operatorType;
        $this->operatorID = $operatorID;
        $this->fbAdTemplateID = $adTemplateID;
        $this->campaignID = $campaignID;
        $this->options = $options;
        $this->adLog = $adLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        # 参考文档，创建 adset 的参数
        # https://developers.facebook.com/docs/marketing-api/reference/ad-campaign/#example-2
//        $fbAdTemplate = [
//            "name" => "tpl-01-cbo-daily-normal-sale-web-purchase-video",
//            "campaign_name" => "us-leads-{{date}}-{{random}}",
//            "adset_name" => "us-web-leads-{{date}}-{{random}}",
//            "ad_name" => "us-video",
//            "bid_strategy" => "LOWEST_COST_WITHOUT_CAP",
//            "bid_amount" => "",
//            "budget_level" => "campaign",
//            "budget_type" => "daily",
//            "budget" => "200",
//            "objective" => "OUTCOME_SALES",
//            "accelerated" => false,
//            "conversion_location" => "WEBSITE",
//            "optimization_goal" => "OFFSITE_CONVERSIONS",
//            "pixel_event" => "PURCHASE",
//            "advantage_plus_audience" => false,
//            "genders" => "1",
//            "age_min" => "35",
//            "age_max" => "55",
//            "cities_included" => [
//                [
//                    "key" => "2420379",
//                    "name" => "Los Angeles",
//                    "radius" => 25,
//                    "country_name" => "United States",
//                    "distance_unit" => "mile",
//                ],
//            ],
//            "cities_excluded" => [
//                [
//                    "key" => "2418100",
//                    "name" => "Bostonia",
//                    "radius" => 25,
//                    "country_name" => "United States",
//                    "distance_unit" => "mile",
//                ],
//            ],
//            "locales" => [
//                ["key" => 6, "name" => "English (US)"],
//                ["key" => 24, "name" => "English (UK)"],
//            ],
//            "interests" => [
//                ["id" => 6003139266461, "name" => "Movies"],
//                ["id" => 6003317542854, "name" => "Stock"],
//            ],
//            'publisher_platforms' => ['facebook'],
//            'device_platforms' => ['mobile'],
//            'wireless_carrier' => false
//        ];

        $fbAdTemplate = FbAdTemplate::query()->firstWhere('id', $this->fbAdTemplateID);
        $fbAdAccount = FbAdAccount::query()->firstWhere('id', $this->fbAdAccountID);

//        $this->options['pixel'] = '1666216184140143';
//        $this->options['end_time'] = Carbon::now()->addDays(2)->format('Y-m-d\TH:i:sO');
        if (isset($this->options['pixel_id']) && $this->options['pixel_id']) {
            $pixel = FbPixel::query()->where('id', $this->options['pixel_id'])->firstOrFail();
        } else {
            $pixel = null;
        }
//        $this->options['page_id'] = '173455875839968';

        $existed_campaign = null;
        if (isset($this->options['campaign_id'])) {
            $existed_campaign = FbCampaign::query()->firstWhere('id', $this->options['campaign_id']);
        }

        $existed_adset = null;
        $adset_source_id = null;
        if (isset($this->options['adset_id'])) {
            $existed_adset = FbAdset::query()->where('id', $this->options['adset_id'])->firstOrFail();
        }

        $adsetStatus = strtoupper((string) ($this->options['adset_status'] ?? 'PAUSED'));
        if (!in_array($adsetStatus, ['ACTIVE', 'PAUSED'], true)) {
            $adsetStatus = 'PAUSED';
        }

        $payload = [
            'name' => $this->processName($fbAdTemplate['adset_name'], $fbAdAccount->source_id),
            'status' => $adsetStatus,
            'campaign_id' => $this->campaignID,
        ];

        # event type 与 objective 的限制 https://developers.facebook.com/docs/marketing-api/reference/ad-campaign/#odax
        # https://developers.facebook.com/docs/marketing-api/reference/ad-campaign-group#odax-mapping

        # optimization_goal 对应界面上的 Conversion Location
        # optimization_goal 目前针对objective的两种情况而不同
        #   Sale （OUTCOME_SALES） 的 值就 只有 OFFSITE_CONVERSIONS
        #   Leads (OUTCOME_LEADS) 的有 OFFSITE_CONVERSIONS 和 LEAD_GENERATION
        #   promoted_object 因 optimization_goal 的不同而不同
        #   OFFSITE_CONVERSIONS 的需要 custom_event_type 和 pixel id。同时 custom_event_type 也因 objective的不同而有不同
        #   LEAD_GENERATION 的需要 page id

        $payload['optimization_goal'] = $fbAdTemplate['optimization_goal'];

        $fbApp = null;
        if (!empty($this->options['fb_app_id'])) {
            $fbApp = FbApp::query()->where('id', $this->options['fb_app_id'])->firstOrFail();
        }

        $cl = $fbAdTemplate['conversion_location'];
        $objective = $fbAdTemplate['objective'];

        if ($cl === ConversionLocationType::App->value) {
            if (!$fbApp) {
                throw new \Exception('App conversion requires fb_app_id in launch options');
            }
            $applicationId = $fbApp->source_id;
            $payload['billing_event'] = 'IMPRESSIONS';

            if ($objective === ObjectiveType::AppPromotion->value) {
                $payload['optimization_goal'] = 'APP_INSTALLS';
                $payload['promoted_object'] = json_encode([
                    'application_id' => $applicationId,
                ]);
            } elseif ($objective === 'OUTCOME_TRAFFIC') {
                $payload['optimization_goal'] = 'LINK_CLICKS';
                $payload['promoted_object'] = json_encode([
                    'application_id' => $applicationId,
                ]);
            } elseif ($objective === ObjectiveType::Sales->value) {
                $payload['optimization_goal'] = 'OFFSITE_CONVERSIONS';
                $evt = trim((string) ($fbAdTemplate['pixel_event'] ?? ''));
                if ($evt === '') {
                    $evt = 'PURCHASE';
                }
                $payload['promoted_object'] = json_encode([
                    'application_id' => $applicationId,
                    'custom_event_type' => $evt,
                ]);
            } elseif ($objective === ObjectiveType::Leads->value) {
                $payload['optimization_goal'] = 'OFFSITE_CONVERSIONS';
                $evt = trim((string) ($fbAdTemplate['pixel_event'] ?? ''));
                if ($evt === '') {
                    $evt = 'LEAD';
                }
                $payload['promoted_object'] = json_encode([
                    'application_id' => $applicationId,
                    'custom_event_type' => $evt,
                ]);
            } else {
                $payload['promoted_object'] = json_encode([
                    'application_id' => $applicationId,
                ]);
            }
        } elseif ($objective === ObjectiveType::Sales->value) {
            # promoted_object
            # https://developers.facebook.com/docs/marketing-api/reference/ad-promoted-object/#Creating
            if ($cl === ConversionLocationType::Website->value && $pixel) {
                $payload['promoted_object'] = json_encode([
                    'custom_event_type' => $fbAdTemplate['pixel_event'],
                    'pixel_id' => $pixel['pixel'],
                ]);
            }

            $payload['billing_event'] = 'IMPRESSIONS';
        } elseif ($objective === ObjectiveType::Leads->value) {
            if ($cl === ConversionLocationType::Website->value) {
                if ($fbAdTemplate['optimization_goal'] === 'OFFSITE_CONVERSIONS' && $pixel) {
                    $payload['promoted_object'] = json_encode([
                        'custom_event_type' => $fbAdTemplate['pixel_event'],
                        'pixel_id' => $pixel['pixel'],
                    ]);
                }
            } elseif ($cl === ConversionLocationType::InstantForms->value) {
                $page = FbPage::query()->where('id', $this->options['page_id'])->firstOrFail();
                $payload['promoted_object'] = json_encode([
                    'page_id' => $page->source_id,
                ]);
                $payload['destination_type'] = 'ON_AD';
            }
            $payload['billing_event'] = 'IMPRESSIONS';
        } elseif ($objective === ObjectiveType::Traffic->value && $cl === ConversionLocationType::Website->value) {
            // 网站「流量」：链接点击优化，一般无需 promoted_object（与模板 LINK_CLICKS 一致）
            $payload['optimization_goal'] = 'LINK_CLICKS';
            $payload['billing_event'] = 'IMPRESSIONS';
        } elseif ($objective === ObjectiveType::Awareness->value && $cl === ConversionLocationType::Website->value) {
            // 知名度：按触达优化，不依赖 pixel_event
            $payload['optimization_goal'] = 'REACH';
            $payload['billing_event'] = 'IMPRESSIONS';
        } elseif ($objective === ObjectiveType::Engagement->value && $cl === ConversionLocationType::Website->value) {
            // 互动：按帖文互动优化，需要 page_id 作为 promoted_object
            $page = FbPage::query()->where('id', $this->options['page_id'])->firstOrFail();
            $payload['optimization_goal'] = 'POST_ENGAGEMENT';
            $payload['billing_event'] = 'IMPRESSIONS';
            $payload['promoted_object'] = json_encode([
                'page_id' => $page->source_id,
            ]);
            $payload['destination_type'] = 'ON_PAGE';
        } elseif ($objective === ObjectiveType::AppPromotion->value) {
            throw new \Exception(__('「应用推广」须将转化发生位置设为「应用」，并在投放内容中选择应用。'));
        }

        // 9 步创建：模板已按 step-bid-budget 写入 optimization_goal / pixel_event
        if (str_contains((string) ($fbAdTemplate['notes'] ?? ''), 'From 9-step meta ad creation')) {
            $og = trim((string) ($fbAdTemplate['optimization_goal'] ?? ''));
            if ($og !== '') {
                $payload['optimization_goal'] = $og;
            }
        }

        # budget
        $convert_budget = CurrencyUtils::convertAndFormat($fbAdTemplate['budget'],'USD', $fbAdAccount->currency );
        $currency_offset = CurrencyUtils::$currencyConfig[$fbAdAccount->currency]['offset'];
        $convert_budget = intval($currency_offset * $convert_budget);

        if ($existed_campaign) {
            // 如果是在已经存在的 campaign 中创建 adset
            if (!$existed_campaign->daily_budget && !$existed_campaign->lifetime_budget) {
                // 如果 campaign 层级没有设置预算,那就要检查模板中是否有设置预算
                // TODO: 如果模板也没有设置预算，要处理一下
                $payload = $this->getPayload($fbAdTemplate, $convert_budget, $payload, $fbAdAccount);
            }
        } else {
            // 如果不是在已经存在的 campaign 中创建 adset
            $payload = $this->getPayload($fbAdTemplate, $convert_budget, $payload, $fbAdAccount);
        }




        # targeting https://developers.facebook.com/docs/marketing-api/audiences/reference/advanced-targeting
        # basic targeting: https://developers.facebook.com/docs/marketing-api/audiences/reference/basic-targeting#basic-targeting

        # advantage audience
        $targeting = [];
        if (isset($fbAdTemplate['advantage_plus_audience']) && $fbAdTemplate['advantage_plus_audience']) {
            $targeting['targeting_automation'] = [
                'advantage_audience' => 1
            ];
            $targeting['age_max'] = 65;
            $targeting['age_min'] = 20;
            $targeting['age_range'] = [
                $targeting['age_min'],
                $targeting['age_max']
            ];
        } else {
            $targeting['age_max'] = $fbAdTemplate['age_max'];
            $targeting['age_min'] = $fbAdTemplate['age_min'];
            $targeting['targeting_automation'] = [
                'advantage_audience' => 0
            ];
        }

        # gender , default 是全部
        if ($fbAdTemplate['genders'] !== 0) {
            $targeting['genders'] = [$fbAdTemplate['genders']];
        }

        # geo
        if (isset($fbAdTemplate['countries_included']) && $fbAdTemplate['countries_included']) {
            $targeting['geo_locations']['countries'] = array_column($fbAdTemplate['countries_included'], 'key');
        }
        if (isset($fbAdTemplate['countries_excluded']) && $fbAdTemplate['countries_excluded']) {
            $targeting['excluded_geo_locations']['countries'] = array_column($fbAdTemplate['countries_excluded'], 'key');
        }

        if (isset($fbAdTemplate['regions_included']) && $fbAdTemplate['regions_included']) {
            $targeting['geo_locations']['regions'] = collect($fbAdTemplate['regions_included'])->map(function ($item) {
                return ['key' => $item['key']];
            })->toArray();
        }
        if (isset($fbAdTemplate['regions_excluded']) && $fbAdTemplate['regions_excluded']) {
            $targeting['excluded_geo_locations']['regions'] = collect($fbAdTemplate['regions_excluded'])->map(function ($item) {
                return ['key' => $item['key']];
            })->toArray();
        }

        if (isset($fbAdTemplate['cities_included']) && $fbAdTemplate['cities_included']) {
            $targeting['geo_locations']['cities'] = collect($fbAdTemplate['cities_included'])->map(function ($item) {
                return [
                    'key' => $item['key'],
                    'radius' => $item['radius'] ?? 25,
                    'distance_unit' => $item['distance_unit'] ?? 'mile',
                ];
            })->toArray();
        }
        if (isset($fbAdTemplate['cities_excluded']) && $fbAdTemplate['cities_excluded']) {
            $targeting['excluded_geo_locations']['cities'] = collect($fbAdTemplate['cities_excluded'])->map(function ($item) {
                return [
                    'key' => $item['key'],
                    'radius' => $item['radius'],
                    'distance_unit' => $item['distance_unit'] ?? 'mile',
                ];
            })->toArray();
        }

        # local targeting https://developers.facebook.com/docs/marketing-api/audiences/reference/advanced-targeting#additional
        # api search: https://developers.facebook.com/docs/marketing-api/audiences/reference/targeting-search#locale
        if (isset($fbAdTemplate['locales']) && $fbAdTemplate['locales']) {
            $targeting['locales'] = array_column($fbAdTemplate['locales'], 'key');
        }

        # interests: https://developers.facebook.com/docs/marketing-api/audiences/reference/targeting-search#interests
        # api search: https://developers.facebook.com/docs/marketing-api/audiences/reference/advanced-targeting#examples
        if (isset($fbAdTemplate['interests']) && $fbAdTemplate['interests']) {
            $targeting['interests'] = collect($fbAdTemplate['interests'])->map(function ($item) {
                if (is_array($item) && isset($item['id'])) {
                    return ['id' => (int) $item['id']];
                }

                return $item;
            })->values()->all();
        }

        #placement https://developers.facebook.com/docs/marketing-api/audiences/reference/placement-targeting

        # publisher platform: facebook, instagram, messenger, audience_network
        $placementMode = (string) ($fbAdTemplate['placement_mode'] ?? 'manual');
        if ($placementMode === 'manual' && isset($fbAdTemplate['publisher_platforms']) && $fbAdTemplate['publisher_platforms']) {
            $targeting['publisher_platforms'] = $fbAdTemplate['publisher_platforms'];
            if (in_array('instagram', $fbAdTemplate['publisher_platforms'])) {
                $include_ig_placement = true;
            } else {
                $include_ig_placement = false;
            }
            if (!empty($fbAdTemplate['facebook_positions'])) {
                $targeting['facebook_positions'] = array_values($fbAdTemplate['facebook_positions']);
            }
            if (!empty($fbAdTemplate['instagram_positions'])) {
                $targeting['instagram_positions'] = array_values($fbAdTemplate['instagram_positions']);
            }
            if (!empty($fbAdTemplate['messenger_positions'])) {
                $targeting['messenger_positions'] = array_values($fbAdTemplate['messenger_positions']);
            }
            if (!empty($fbAdTemplate['audience_network_positions'])) {
                $targeting['audience_network_positions'] = array_values($fbAdTemplate['audience_network_positions']);
            }
        } else {
            // 进阶赋能型版位：不限制平台和具体位置，交由 Meta 自动分配
            $include_ig_placement = true;
        }

        # device platform: All, mobile, desktop
        if (isset($fbAdTemplate['device_platforms']) && $fbAdTemplate['device_platforms']) {
            $targeting['device_platforms'] = array_values($fbAdTemplate['device_platforms']);
        }

        # user_os: https://developers.facebook.com/docs/marketing-api/audiences/reference/advanced-targeting
        if (isset($fbAdTemplate['user_os']) && is_array($fbAdTemplate['user_os']) && count($fbAdTemplate['user_os']) > 0) {
            $targeting['user_os'] = array_values($fbAdTemplate['user_os']);
        }

        # wifi: https://developers.facebook.com/docs/marketing-api/audiences/reference/advanced-targeting
        if (isset($fbAdTemplate['wireless_carrier']) && $fbAdTemplate['wireless_carrier']) {
            $targeting['wireless_carrier'] = ['wifi'];
        }

        $payload['targeting'] = json_encode($targeting);

        $bbMeta = $this->parseBidBudgetMetaFromNotes($fbAdTemplate['notes'] ?? '');
        if (is_array($bbMeta)) {
            if (! empty($bbMeta['billing_event']) && in_array($bbMeta['billing_event'], ['IMPRESSIONS', 'LINK_CLICKS'], true)) {
                $payload['billing_event'] = $bbMeta['billing_event'];
            }
            $attrSpec = $this->mapAttributionKeyToSpec($bbMeta['attribution'] ?? null);
            if ($attrSpec !== null) {
                $payload['attribution_spec'] = json_encode($attrSpec);
            }
        }

//        $payload['targeting'] = json_encode([
//            'facebook_positions' => ['feed'],
//            'geo_locations' => [
//                'countries' => [
//                    'US'
//                ],
//                'regions' => [
//                    [
//                        'key' => '4081'
//                    ]
//                ],
//                'cities' => [
//                    [
//                        'key' => '777934',
//                        'radius' => 10,
//                        'distance_unit' => 'mile'
//                    ]
//                ]
//            ],
//            'genders' => [1],
//            'age_max' => 65,
//            'age_min' => 20,
//            'age_range' => [
//                30,45
//            ],
//            'publisher_platforms' => [
//                'facebook',
//                'audience_network'
//            ],
//            'device_platforms' => [
//                'mobile'
//            ],
//            'targeting_automation' => [
//                'advantage_audience' => 1
//            ],
//            'targeting_optimization' => 'expansion_all'
//        ]);

        # dsa_beneficiary
        $payload['dsa_beneficiary'] = 'Lauren Jimmy Hughes';
        $payload['dsa_payor'] = 'Lauren Jimmy Hughes';

        # 对于 campaign budget 的 lifetime budget 的广告，在这个层级设置
        if ($fbAdTemplate['budget_type'] === BudgetType::LifeTime->value &&
            $fbAdTemplate['budget_level'] === BudgetLevel::Campaign->value) {
            $payload['end_time'] = $this->options['end_time'];
        }

        # 9 步创建：分时段（日期范围 + 每日时段）
        $ms = $this->options['meta_adset_schedule'] ?? null;
        if (is_array($ms)) {
            $startDate = !empty($ms['scheduleStartDate']) ? (string) $ms['scheduleStartDate'] : null;
            $endDate = !empty($ms['scheduleEndDate']) ? (string) $ms['scheduleEndDate'] : null;
            if ($startDate) {
                $payload['start_time'] = strpos($startDate, 'T') !== false ? substr($startDate, 0, 19) : ($startDate . 'T00:00:00');
            }
            if ($endDate) {
                $payload['end_time'] = strpos($endDate, 'T') !== false ? substr($endDate, 0, 19) : ($endDate . 'T23:59:59');
            }
            $startM = $this->minutesFromHHmm(isset($ms['dailyScheduleStart']) ? (string) $ms['dailyScheduleStart'] : null);
            $endM = $this->minutesFromHHmm(isset($ms['dailyScheduleEnd']) ? (string) $ms['dailyScheduleEnd'] : null);
            if ($startM !== null && $endM !== null && $endM > $startM) {
                $payload['adset_schedule'] = json_encode([
                    ['days' => [1, 2, 3, 4, 5, 6, 7], 'start_minute' => $startM, 'end_minute' => $endM],
                ]);
            }
        }

        if (is_array($bbMeta) && (($bbMeta['schedule'] ?? '') === 'custom') && ! empty($bbMeta['startDate']) && ! empty($bbMeta['endDate'])) {
            $sd = (string) $bbMeta['startDate'];
            $st = (string) ($bbMeta['startTime'] ?? '00:00');
            $ed = (string) $bbMeta['endDate'];
            $et = (string) ($bbMeta['endTime'] ?? '23:59');
            $payload['start_time'] = strpos($sd, 'T') !== false ? substr($sd, 0, 19) : ($sd.'T'.$st.':00');
            $payload['end_time'] = strpos($ed, 'T') !== false ? substr($ed, 0, 19) : ($ed.'T'.$et.':59');
        }

        # api create adset
        Log::info("create adset payload:");
        Log::debug($payload);

        $version = FbUtils::$API_Version;

        $fbAdAccount = FbAdAccount::query()->firstWhere('id', $this->fbAdAccountID);
        Log::debug($fbAdAccount);
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/adsets";
        $query = null;

        if (!$existed_adset) {
            if ($this->operatorType == OperatorType::FacebookUser->value) {
                $fbAccount = FbAccount::query()->firstWhere('id', $this->operatorID)
                    ->where('token_valid', true)->firstOrFail();
                $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'POST', $payload);
            } else if ($this->operatorType == OperatorType::BMUser->value) {
                $fbApiToken = FbApiToken::query()->firstWhere('id', $this->operatorID);

                $resp = FbUtils::makeRequest(null, $endpoint, $query, 'POST', $payload, '', $fbApiToken['token']);
            }
            Log::debug("create adset resp");
            Log::debug($resp);
            if (isset($resp['id'])) {
                $adset_source_id = $resp['id'];
            }
        } else {
            $adset_source_id = $existed_adset->source_id;
        }

        if ($adset_source_id) {
            // 有 ID 表示创建成功
            Log::debug("adset id: {$adset_source_id}");
//            $adsetId = $resp['id'];

            $this->adset_source_id = $adset_source_id;
            $this->adLog->adsets()->syncWithoutDetaching([
                $this->adset_source_id => [ 'adset_created' => true ]
            ]);
            $this->adLog->save();

            // 同步adset
//            $today = Carbon::now()->format('Y-m-d');
//            FacebookFetchAdset::dispatch($this->fbAdAccountID, $today,
//                $today, null, false, false, 1, [
//                    'field' => 'id',
//                    'operator' => 'IN',
//                    'value' => [$adsetId]
//                ])->onQueue('facebook');
            FacebookFetchAdsetV2::dispatch($this->fbAdAccountID, null, null, null, false, false, false,[
                'field' => 'id',
                'operator' => 'IN',
                'value' => [$adset_source_id]
            ])->onQueue('frontend');

            $launchMode = $this->options['launch_mode'];
//            $launchMode = 3;

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

            if ($include_ig_placement && $adSetup === EnumAdsetup::Catalog->value) {
                $require_pbia_id = true;
            } else {
                $require_pbia_id = false;
            }

            // 如果是 launch mode 是 1-1-N
            if ($launchMode === 3) {

                // 如果是通过素材创建广告
                if ($adSetup === EnumAdsetup::Material->value) {
                    $slotPayloads = $this->materialJobsFromCreativeSlotsOrList();
                    if (count($slotPayloads) > 1) {
                        $batchJobs = [];
                        foreach ($slotPayloads as $index => $options) {
                            $batchJobs[] = (new FacebookCreateAdV2($this->fbAdAccountID, $this->operatorType, $this->operatorID,
                                $this->campaignID, $adset_source_id, $this->fbAdTemplateID, $options, $this->adLog))->onQueue('facebook')
                                ->delay(MetaAdCreationSplitRules::staggerDelaySecondsForAdJob($index, $this->options['split_rules'] ?? []));
                        }

                        Bus::batch($batchJobs)->finally(function (Batch $batch) {
                            Log::debug("finally, all ads batch are finished");
                        })->onQueue('facebook')->allowFailures()->dispatch();

                    } else {
                        $opts = count($slotPayloads) === 1 ? $slotPayloads[0] : $this->options;
                        FacebookCreateAdV2::dispatch($this->fbAdAccountID, $this->operatorType, $this->operatorID,
                            $this->campaignID, $adset_source_id, $this->fbAdTemplateID, $opts, $this->adLog)->onQueue('facebook');
                    }
                } elseif ($adSetup === EnumAdsetup::Post->value) {
                    // 如果是通过 post 创建广告

                    // 如果有多个 post
                    if ($posts->count() > 1) {
                        $batchJobs = [];
                        $options = $this->options;
                        foreach ($posts as $index => $post_id) {
                            $options['post_id'] = $post_id;
                            $batchJobs[] = (new FacebookCreateAdV2($this->fbAdAccountID, $this->operatorType, $this->operatorID,
                                $this->campaignID, $adset_source_id, $this->fbAdTemplateID, $options, $this->adLog))->onQueue('facebook')
                                ->delay(MetaAdCreationSplitRules::staggerDelaySecondsForAdJob($index, $this->options['split_rules'] ?? []));
                        }

                        Bus::batch($batchJobs)->finally(function (Batch $batch) {
                            Log::debug("finally, all ads batch are finished");
                        })->onQueue('facebook')->allowFailures()->dispatch();
                    } else {
                        FacebookCreateAdV2::dispatch($this->fbAdAccountID, $this->operatorType, $this->operatorID,
                            $this->campaignID, $adset_source_id, $this->fbAdTemplateID, $this->options, $this->adLog)->onQueue('facebook');
                    }
                } elseif ($adSetup === EnumAdsetup::Catalog->value) {
                    // 如果是通过 product set 创建广告

                    // 如果有多个 product set
                    if ($productSets->count() > 1) {
                        $batchJobs = [];
                        $options = $this->options;
                        foreach ($productSets as $index => $product_set) {
                            $options['product_set'] = $product_set;
                            $batchJobs[] = (new FacebookCreateAdV2($this->fbAdAccountID, $this->operatorType, $this->operatorID,
                                $this->campaignID, $adset_source_id, $this->fbAdTemplateID, $options, $this->adLog, $require_pbia_id))
                                ->onQueue('facebook')
                                ->delay(MetaAdCreationSplitRules::staggerDelaySecondsForAdJob($index, $this->options['split_rules'] ?? []));
                        }

                        Bus::batch($batchJobs)->finally(function (Batch $batch) {
                            Log::debug("finally, all ads batch are finished");
                        })->onQueue('facebook')->allowFailures()->dispatch();
                    } else {
                        FacebookCreateAdV2::dispatch($this->fbAdAccountID, $this->operatorType, $this->operatorID,
                            $this->campaignID, $adset_source_id, $this->fbAdTemplateID, $this->options, $this->adLog, $require_pbia_id)->onQueue('facebook');
                    }
                }

            } else {
                Log::debug("after create adset, not 1-1-N");
                FacebookCreateAdV2::dispatch($this->fbAdAccountID, $this->operatorType, $this->operatorID,
                    $this->campaignID, $adset_source_id, $this->fbAdTemplateID, $this->options, $this->adLog)->onQueue('facebook');
            }

//            FacebookCreateAdV2::dispatch($this->fbAdAccountID, $this->operatorType, $this->operatorID,
//                $this->campaignID, $adsetId, $this->fbAdTemplateID, $this->options);
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
            'FB-Create-Adset',
            "FB-Campaign-{$this->campaignID}",
            "{$this->fbAdAccountID} - {$this->operatorType} - {$this->operatorID} - {$this->fbAdTemplateID}",
        ];
    }

    function processName($name, $adAccountSourceId) {
        // 替换 {{datetime}} 宏
//        $name = str_replace('{{date}}', date('Y-m-d'), $name);
        // 获取当前时间并转换为 UTC+8 时区
        $currentDateTime = \Carbon\Carbon::now('UTC')->addHours(8);
        // 格式化日期时间为指定的格式
        $formattedDateTime = $currentDateTime->format('m/d-H:i:s');
        $name = str_replace('{{date}}', "($formattedDateTime)", $name);

        // 替换 {{random}} 宏
        if (strpos($name, '{{random}}') !== false ) {
            if (isset($this->options['random']) && $this->options['random']) {
                $name = str_replace('{{random}}', $this->options['random'], $name);
            } else {
                $randomString = substr(str_shuffle(md5(time())), 0, 6);
                $name = str_replace('{{random}}', $randomString, $name);
                $this->options['random'] = $randomString;
            }
        }

        $adAccountSourceIdSub = substr($adAccountSourceId, -4);
        $name = str_replace('{{acc.id}}', $adAccountSourceIdSub, $name);

        return $name;
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        $msg = "Failed to create adset: {$this->operatorType} : {$this->operatorID}, camp id: {$this->campaignID}, log id: {$this->adLog->id}";

        if ($this->adset_source_id) {
            $this->adLog->adsets()->syncWithoutDetaching([
                $this->adset_source_id => [
                    'adset_created' => false,
                    'adset_failed_reason' => $exception->getMessage()
                ]
            ]);
        } else {
            $this->adLog->is_success = false;
            $this->adLog->failed_reason = $exception->getMessage();
        }
        $this->adLog->save();

        Log::error('Failed to create adset: ' . $exception->getMessage());
        Telegram::sendMessage($msg);
    }

    /**
     * @param Model|Builder|null $fbAdTemplate
     * @param int $convert_budget
     * @param array $payload
     * @return array
     */
    public function getPayload(Model|Builder|null $fbAdTemplate, int $convert_budget, array $payload, FbAdAccount $fbAdAccount): array
    {
        if ($fbAdTemplate['budget_level'] === BudgetLevel::Adset->value) {
            $bs = $fbAdTemplate['bid_strategy'] ?? '';

            if ($fbAdTemplate['budget_type'] === 'daily') {
                $payload['daily_budget'] = $convert_budget;
                $payload['bid_strategy'] = $fbAdTemplate['bid_strategy'];
            } elseif ($fbAdTemplate['budget_type'] === BudgetType::LifeTime->value) {
                $payload['lifetime_budget'] = $convert_budget;
                $payload['bid_strategy'] = $fbAdTemplate['bid_strategy'];
            }

            if (!empty($fbAdTemplate['accelerated'])) {
                $payload['pacing_type'] = ['no_pacing'];
            } elseif ($bs === BidStrategy::BidCap->value) {
                $payload['pacing_type'] = ['standard'];
            }

            $rawBid = trim((string) ($fbAdTemplate['bid_amount'] ?? ''));
            if ($rawBid !== '') {
                if ($bs === BidStrategy::MinRoas->value) {
                    $floor = (float) $rawBid;
                    if ($floor > 0) {
                        $payload['bid_constraints'] = json_encode(['roas_average_floor' => $floor]);
                    }
                } elseif (in_array($bs, [BidStrategy::BidCap->value, BidStrategy::CostPerResultGoal->value], true)) {
                    $convert_bid = CurrencyUtils::convertAndFormat($fbAdTemplate['bid_amount'], 'USD', $fbAdAccount->currency);
                    $currency_offset = CurrencyUtils::$currencyConfig[$fbAdAccount->currency]['offset'];
                    $payload['bid_amount'] = (int) round($currency_offset * (float) $convert_bid);
                }
            }
        }
        return $payload;
    }

    /**
     * step-bid-budget 归因键 → Meta attribution_spec（JSON 数组）。
     *
     * @return list<array{event_type: string, window_days: int}>|null
     */
    private function mapAttributionKeyToSpec(?string $key): ?array
    {
        if ($key === null || $key === '') {
            return null;
        }

        return match ($key) {
            'click_1d' => [['event_type' => 'click', 'window_days' => 1]],
            'click_7d' => [['event_type' => 'click', 'window_days' => 7]],
            'click_7d_view_1d' => [['event_type' => 'click', 'window_days' => 7], ['event_type' => 'view', 'window_days' => 1]],
            'click_1d_view_1d' => [['event_type' => 'click', 'window_days' => 1], ['event_type' => 'view', 'window_days' => 1]],
            default => null,
        };
    }

    /**
     * 从 FbAdTemplate.notes 解析 step-bid-budget 写入的 bb_meta（base64 JSON）。
     *
     * @return array<string, mixed>|null
     */
    private function parseBidBudgetMetaFromNotes(?string $notes): ?array
    {
        if ($notes === null || $notes === '') {
            return null;
        }
        if (! preg_match('/\|bb_meta:([A-Za-z0-9+/=]+)/', $notes, $m)) {
            return null;
        }
        $json = base64_decode($m[1], true);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    /** HH:mm → 0–1439 的分钟数（用于 adset_schedule） */
    private function minutesFromHHmm(?string $hm): ?int
    {
        if ($hm === null || $hm === '') {
            return null;
        }
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($hm), $m)) {
            return null;
        }
        $h = (int) $m[1];
        $min = (int) $m[2];
        if ($h < 0 || $h > 23 || $min < 0 || $min > 59) {
            return null;
        }

        return $h * 60 + $min;
    }
}
