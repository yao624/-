<?php

namespace App\Jobs;

use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Models\FbPage;
use App\Models\FbPixel;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use DateTime;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\Translation\t;

class FacebookFetchCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;
    private $timeout = 2600;

    private $fbAdAccountID;
    private $adAccount;
    private $fbAdAccountSoruceID;

    private mixed $date_stop;
    private mixed $date_start;
    private $next;
    private mixed $fbAccountID;
    private $currency;
    private $fbAccount;
    private $token;
    private $all;
    private $lastDays; // 多少天前创建, 只有 $all 为true时才有效
    private $filtering;

    /**
     * 这个时间参数无意义，只是为了方便给下一步获取insight数据传参数需要
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $date_start=null, $date_stop=null, $fbAccountID=null,
                                $next=true, $all=false, $lastDays=1, $filtering=[])
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->adAccount = FbAdAccount::query()->findOrFail($fbAdAccountID);
        $this->currency = $this->adAccount['currency'];
        $this->fbAdAccountSoruceID = $this->adAccount->source_id;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
        $this->next = $next;
        $this->fbAccountID = $fbAccountID;
        $this->all = $all;
        $this->lastDays = $lastDays;
        $this->filtering = $filtering;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = '';
        if ($this->fbAccountID == null) {
            Log::debug("fb account id is null");
            $apiToken = $this->adAccount->apiTokens()->firstWhere('active', true);
            if ($apiToken) {
                $token = $apiToken['token'];
                Log::info("use api token");
                $this->token = $token;
            } else {
                $query = $this->adAccount->fbAccounts()->where('token_valid', true);
                if ($query->count() == 0) {
                    Log::warning("no api token, nor fb account");
                    $msg = "{$this->fbAdAccountSoruceID} no api token or fb account available";
                    Telegram::sendMessage($msg);
                    throw new \Exception("no api token, nor fb account");
                } else {
                    $this->fbAccount = $query->first();
                    $this->fbAccountID = $this->fbAccount->id;
                }
            }
        } else {
            # 查找到一个 token valid 是有效的fb account

            $this->fbAccount = $this->adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
            $this->fbAccountID = $this->fbAccount->id;
        }

        Log::info("--- Fetch FB Campaign Data, Ad Account: {$this->fbAdAccountSoruceID}");

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$this->fbAdAccountSoruceID}/campaigns";
        $special_acc_id = ['565434670881480'];
        if (in_array($this->fbAdAccountSoruceID, $special_acc_id)) {
            $fields = 'id,account_id, bid_strategy,budget_remaining, buying_type, configured_status,created_time, daily_budget, effective_status,lifetime_budget,name, objective, source_campaign_id, spend_cap, start_time, status, stop_time, updated_time,adsets.limit(4){id, account_id, adset_schedule, attribution_spec, bid_amount, bid_constraints, bid_info, bid_strategy,billing_event, budget_remaining,created_time, campaign_id,configured_status,daily_budget,daily_spend_cap,dsa_beneficiary, dsa_payor,effective_status,instagram_actor_id,is_dynamic_creative,lifetime_budget,lifetime_spend_cap,name,optimization_goal,promoted_object{custom_event_type,pixel_id,page_id},source_adset_id,start_time,status,targeting,ads.limit(4){id,account_id, ad_active_time,adset_id,campaign_id, configured_status,created_time,creative{body, title, id,actor_id,call_to_action_type, effective_instagram_story_id,effective_object_story_id,instagram_permalink_url,instagram_story_id,link_url,object_story_id,object_id,object_story_spec,object_url,status,thumbnail_url, object_type,link_destination_display_url, object_store_url,url_tags},effective_status,name,preview_shareable_link,source_ad_id,status, updated_time}}';
            $page_limit = 4;
        } else {
            $fields = 'id,account_id, bid_strategy,budget_remaining, buying_type, configured_status,created_time, daily_budget, effective_status,lifetime_budget,name, objective, source_campaign_id, spend_cap, start_time, status, stop_time, updated_time,adsets.limit(10){id, account_id, adset_schedule, attribution_spec, bid_amount, bid_constraints, bid_info, bid_strategy,billing_event, budget_remaining,created_time, campaign_id,configured_status,daily_budget,daily_spend_cap,dsa_beneficiary, dsa_payor,effective_status,instagram_actor_id,is_dynamic_creative,lifetime_budget,lifetime_spend_cap,name,optimization_goal,promoted_object{custom_event_type,pixel_id,page_id},source_adset_id,start_time,status,targeting,ads.limit(20){id,account_id, ad_active_time,adset_id,campaign_id, configured_status,created_time,creative{body, title, id,actor_id,call_to_action_type, effective_instagram_story_id,effective_object_story_id,instagram_permalink_url,instagram_story_id,link_url,object_story_id,object_id,object_story_spec,object_url,status,thumbnail_url, object_type,link_destination_display_url, object_store_url,url_tags},effective_status,name,preview_shareable_link,source_ad_id,status, updated_time}}';
            $page_limit = 2;
        }

        // 默认查询 30天内的创建的 Campaign
        $lastyear =  Carbon::now()->subDays(30)->timestamp;
        $query = [
            'fields' => $fields,
            'filtering' => [
                [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => $lastyear,
                ],
            ],
            'limit' => $page_limit,
        ];

//        if (!$this->date_start && !$this->date_stop) {
//            Log::debug("fetch all campaign including archived");
//            $query['filtering'][] = [
//                'field' => 'campaign.effective_status',
//                'operator' => 'IN',
//                'value' => [
//                    'ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'IN_PROCESS', 'WITH_ISSUES', 'PENDING_REVIEW'
//                ],
//            ];
//        }
        $query['filtering'][] = [
            'field' => 'campaign.effective_status',
            'operator' => 'IN',
            'value' => [
                'ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'IN_PROCESS', 'WITH_ISSUES',
                'PENDING_REVIEW', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED',
            ],
        ];

        if ($this->all) {
            // 如果同步所有为 true, 创建时间这一步要检查 date start, 如果有时间范围，就在时间范围内获取
            // 如果没有，则在时间通过 lastDays 来获取 create_time 的时间
            if ($this->date_start) {
                $query['filtering'][0]['value'] = Carbon::parse($this->date_start)->timestamp;
                if ($this->date_stop) {
                    $query['filtering'][] = [
                        'field' => 'created_time',
                        'operator' => 'LESS_THAN',
                        'value' => Carbon::parse($this->date_stop)->timestamp,
                    ];
                }
            } else {
                $lastDaysTimestamp =  Carbon::now()->subDays($this->lastDays)->timestamp;
                $query['filtering'][0]['value'] = $lastDaysTimestamp;
            }
        } else {
            if ($this->date_start) {
                $query['filtering'][0]['value'] = Carbon::parse($this->date_start)->timestamp;
                if ($this->date_stop) {
                    $query['filtering'][] = [
                        'field' => 'created_time',
                        'operator' => 'LESS_THAN',
                        'value' => Carbon::parse($this->date_stop)->timestamp,
                    ];
                }
            }
        }

        if (!empty($this->filtering)) {
            $query['filtering'][] = $this->filtering;
        }
        Log::debug($query);

        $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', null, '', $token);
//        Log::debug("resp json: {$resp->toJson()}");

        $paging = collect($resp->get('paging'));
        $campaign_ids = collect();
        $ids = $this->processResponse($resp);
        $campaign_ids = $campaign_ids->concat($ids);
        while ($paging->has('next')) {
            Log::info("--- Fetch FB Campaign Data, Ad Account: {$this->fbAdAccountSoruceID} new page");
            $next = $paging->get('next');
            $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, '', $token);
//            Log::debug("response");
//            Log::debug($resp);
            $ids = $this->processResponse($resp);
            $campaign_ids = $campaign_ids->concat($ids);
            $paging = collect($resp->get('paging'));
        }
        // 把 AdAccount 里面的campaign id找出来，不在这个列表里面的，全部更新 is_deleted_on_fb 为 true
//        $updatedCount = $this->adAccount->fbCampaigns()
//            ->whereNotIn('source_id', $campaign_ids)
//            ->update(['is_deleted_on_fb' => true]);
//        Log::info("Updated campaigns: $updatedCount, mark as deleted on fb");
//
//        if ($updatedCount > 0) {
//            $updatedAdsetIds = $this->adAccount->fbCampaigns()
//                ->where('is_deleted_on_fb', true)
//                ->with('fbAdsets')
//                ->get()
//                ->pluck('fbAdsets.*.id')
//                ->collapse();
//            // 批量更新 FbAdsets
//            $updatedAdsetsCount = FbAdset::whereIn('id', $updatedAdsetIds)->update(['is_deleted_on_fb' => true]);
//            // 获取需要更新的 FbAds 的 IDs
//            $updatedAdIds = FbAdset::whereIn('id', $updatedAdsetIds)
//                ->with('fbAds')
//                ->get()
//                ->pluck('fbAds.*.id')
//                ->collapse();
//            // 批量更新 FbAds
//            $updatedAdsCount = FbAd::whereIn('id', $updatedAdIds)->update(['is_deleted_on_fb' => true]);
//
//            Log::info("Updated FbAdsets: $updatedAdsetsCount, FbAds: $updatedAdsCount");
//        }

        if ($this->next) {
            FacebookFetchAdset::dispatch($this->fbAdAccountID, $this->date_start, $this->date_stop, $this->fbAccountID, $this->next,
                $this->all, $this->lastDays)->onQueue('facebook');
        }

        if ($this->next) {
            FacebookFetchAdAccountInsights::dispatch($this->fbAdAccountID, $this->date_start, $this->date_stop, $this->fbAccountID, $this->next)
                ->onQueue('facebook')->delay(now()->addSeconds(3));
        }

    }

    public function tags(): array
    {
        return [
            'FB-Camp-Sts',
            "{$this->fbAdAccountSoruceID}",
            "{$this->fbAccountID}"
        ];
    }

    /**
     * @param Collection $resp
     * @return void
     */
    public function processResponse(Collection $resp): Collection
    {
        $source_ids = collect();
        $fbCampaignCollection = collect($resp->get('data', []));
        $default_start_time = Carbon::createFromTimestamp(0);
        $camp_count = $fbCampaignCollection->count();
        Log::debug("fb campaign length: {$camp_count} ");
        $fbCampaignCollection->each(function ($fbCampaignData) use ($default_start_time, &$source_ids) {
            $daily_budget = null;
            $lifetime_budget = null;
            if (isset($fbCampaignData['daily_budget'])) {
                $daily_budget_string = $fbCampaignData['daily_budget'];
                $currency_offset = CurrencyUtils::$currencyConfig[$this->currency]['offset'];
                if (is_numeric($daily_budget_string)) {
                    $daily_budget = intval($daily_budget_string) / $currency_offset;
                    $daily_budget = CurrencyUtils::convertAndFormat($daily_budget, $this->currency, 'USD');
                } else {
                    $daily_budget = "-1";
                }
            }
            if (isset($fbCampaignData['lifetime_budget'])) {
//                $lifetime_budget = CurrencyUtils::convertAndFormat($fbCampaignData['lifetime_budget'], $this->currency, 'USD');
                $life_budget_string = $fbCampaignData['lifetime_budget'];
                $currency_offset = CurrencyUtils::$currencyConfig[$this->currency]['offset'];

                if (is_numeric($life_budget_string)) {
                    $lifetime_budget = intval($life_budget_string) / $currency_offset;
                    $lifetime_budget = CurrencyUtils::convertAndFormat($lifetime_budget, $this->currency, 'USD');
                } else {
                    $lifetime_budget = "-1";
                }
            }
            Log::debug("campaign data:");
            Log::debug($fbCampaignData['name']);
            $source_ids->push($fbCampaignData['id']);
            $fbCampaign = FbCampaign::query()->updateOrCreate(
                [
                    'source_id' => $fbCampaignData['id']
                ],
                [
                    'fb_ad_account_id' => $this->fbAdAccountID,
                    'account_id' => $fbCampaignData['account_id'],
                    'bid_strategy' => $fbCampaignData['bid_strategy'] ?? null,
                    'budget_remaining' => $fbCampaignData['budget_remaining'] ?? null,
                    'configured_status' => $fbCampaignData['configured_status'],
                    'created_time' => $fbCampaignData['created_time'] ? Carbon::parse($fbCampaignData['created_time']) : '',
                    'daily_budget' => $daily_budget,
                    'lifetime_budget' => $lifetime_budget,
                    'effective_status' => $fbCampaignData['effective_status'],
                    'source_id' => $fbCampaignData['id'],
                    'name' => $fbCampaignData['name'],
                    'objective' => $fbCampaignData['objective'],
                    'source_campaign_id' => $fbCampaignData['source_campaign_id'],
                    'start_time' => $fbCampaignData['start_time']
                        ? (Carbon::instance(new DateTime($fbCampaignData['start_time']))->gt($default_start_time)
                            ? Carbon::instance(new DateTime($fbCampaignData['start_time']))
                            : null)
                        : null,
                    'status' => $fbCampaignData['status'],
                    'updated_time' => $fbCampaignData['updated_time'] ? Carbon::parse($fbCampaignData['updated_time']) : '',
                    'original_daily_budget' => $fbCampaignData['daily_budget'] ?? null,
                    'original_lifetime_budget' => $fbCampaignData['lifetime_budget'] ?? null,
                ]
            );

            $adsetsParent = collect($fbCampaignData)->get('adsets', []);
            $adsetsData = collect(collect($adsetsParent)->get('data', []));
            $adsetsPagination = collect(collect($adsetsParent)->get('paging'));

            $adset_source_ids = collect();
            $hasNext = true;
            while ($hasNext) {

                $adsetsData->each(function ($adsetData) use ($default_start_time, $fbCampaign, &$adset_source_ids) {

                    $fbPixelID = null;
                    if (isset($adsetData['promoted_object']['pixel_id'])) {
                        $fbPixel = FbPixel::query()->firstWhere('pixel', $adsetData['promoted_object']['pixel_id']);
                        if ($fbPixel) {
                            $fbPixelID = $fbPixel->id;
                        }
                    }
                    $daily_budget = null;
                    $lifetime_budget = null;
                    if (isset($adsetData['daily_budget'])) {
//                        $daily_budget = CurrencyUtils::convert($adsetData['daily_budget'], $this->currency, 'USD', 2);
                        $daily_budget_string = $adsetData['daily_budget'];
                        $currency_offset = CurrencyUtils::$currencyConfig[$this->currency]['offset'];

                        if (is_numeric($daily_budget_string)) {
                            $daily_budget = intval($daily_budget_string) / $currency_offset;
                            $daily_budget = CurrencyUtils::convertAndFormat($daily_budget, $this->currency, 'USD');
                        } else {
                            $daily_budget = "-1";
                        }
                    }
                    if (isset($adsetData['lifetime_budget'])) {
//                        $lifetime_budget = CurrencyUtils::convert($adsetData['lifetime_budget'], $this->currency, 'USD', 2);
                        $life_budget_string = $adsetData['lifetime_budget'];
                        $currency_offset = CurrencyUtils::$currencyConfig[$this->currency]['offset'];

                        if (is_numeric($life_budget_string)) {
                            $lifetime_budget = intval($life_budget_string) / $currency_offset;
                            $lifetime_budget = CurrencyUtils::convertAndFormat($lifetime_budget, $this->currency, 'USD');
                        } else {
                            $lifetime_budget = "-1";
                        }
                    }

                    $adset_source_ids->push($adsetData['id']);
                    $fbAdset = FbAdset::query()->updateOrCreate(
                        [
                            'source_id' => $adsetData['id']
                        ],
                        [
                            'fb_campaign_id' => $fbCampaign->id,
                            'pixel_id' => $fbPixelID,
                            'account_id' => $adsetData['account_id'],
                            'billing_event' => $adsetData['billing_event'],
                            'budget_remaining' => $adsetData['budget_remaining'] ?? '',
                            'campaign_id' => $adsetData['campaign_id'],
                            'configured_status' => $adsetData['configured_status'],
                            'created_time' => $adsetData['created_time'] ? Carbon::parse($adsetData['created_time']) : '',
                            'daily_budget' => $daily_budget,
                            'lifetime_budget' => $lifetime_budget,
                            'effective_status' => $adsetData['effective_status'],
                            'is_dynamic_creative' => $adsetData['is_dynamic_creative'],
                            'name' => $adsetData['name'],
                            'optimization_goal' => $adsetData['optimization_goal'],
                            'promoted_object' => $adsetData['promoted_object'] ?? [],
                            'source_adset_id' => $adsetData['source_adset_id'],
                            'start_time' => $adsetData['start_time']
                                ? (Carbon::instance(new DateTime($adsetData['start_time']))->gt($default_start_time)
                                    ? Carbon::instance(new DateTime($adsetData['start_time']))
                                    : null)
                                : null,
                            'status' => $adsetData['status'],
                            'targeting' => $adsetData['targeting'],
                            'original_daily_budget' => $adData['daily_budget'] ?? null,
                            'original_lifetime_budget' => $adData['lifetime_budget'] ?? null,
                        ]
                    );

                    $adsParent = collect($adsetData)->get('ads', []);
                    $adsData = collect(collect($adsParent)->get('data', []));
                    $adsPagination = collect(collect($adsParent)->get('paging'));

                    $ad_source_ids = collect();
                    $hasNextAd = true;

                    while ($hasNextAd) {
                        $adsData->each(function ($adData) use ($fbAdset, $fbCampaign, &$ad_source_ids) {
                            $ad_source_ids->push($adData['id']);
                            $fbAd = FbAd::query()->updateOrCreate(
                                [
                                    'source_id' => $adData['id']
                                ],
                                [
                                    'fb_campaign_id' => $fbCampaign->id,
                                    'fb_adset_id' => $fbAdset->id,
                                    'adset_id' => $adData['adset_id'],
                                    'campaign_id' => $adData['campaign_id'],
                                    'configured_status' => $adData['configured_status'],
                                    'created_time' => $adData['created_time'] ? Carbon::parse($adData['created_time']) : null,
                                    'creative' => $adData['creative'] ?? [],
                                    'effective_status' => $adData['effective_status'],
                                    'name' => $adData['name'],
                                    'preview_shareable_link' => $adData['preview_shareable_link'] ?? '',
                                    'source_ad_id' => $adData['source_ad_id'],
                                    'status' => $adData['status'],
                                    'post_url' => '',
                                    'updated_time' => $adData['updated_time'] ? Carbon::parse($adData['updated_time']) : null,
                                ]
                            );

                            if (isset($adData['creative']) && isset($adData['creative']['object_story_spec']) && isset($adData['creative']['object_story_spec']['page_id'])) {
                                $fb_page_source_id = $adData['creative']['object_story_spec']['page_id'];
                                $fbPage = FbPage::where('source_id', $fb_page_source_id)->first();
                                if ($fbPage) {
                                    $fbAd->fbPages()->syncWithoutDetaching([
                                        $fbPage->id => ['fb_page_source_id' => $fb_page_source_id]
                                    ]);
                                }
                            }
                        });

                        $hasNextAd = $adsPagination->has('next');
                        if ($hasNextAd) {
                            Log::info("ads has next");
                            $next = $adsPagination->get('next');
                            $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, '', $this->token);
                            $adsData = collect(collect($resp)->get('data', []));
                            $adsPagination = collect(collect($resp)->get('paging'));
                        }
                    }

//                    $updatedCount = $fbAdset->fbAds()->whereNotIn('source_id', $ad_source_ids)
//                        ->update(['is_deleted_on_fb' => true]);
//                    Log::info("Updated ads: $updatedCount, mark as deleted on fb");
                });

                $hasNext = $adsetsPagination->has('next');
                if ($hasNext) {
                    Log::info("adset has next");
                    $next = $adsetsPagination->get('next');
                    $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, '', $this->token);
                    $adsetsData = collect(collect($resp)->get('data', []));
                    $adsetsPagination = collect(collect($resp)->get('paging'));
                }
            }

//            $updatedCount = $fbCampaign->fbAdsets()
//                ->whereNotIn('source_id', $adset_source_ids)
//                ->update(['is_deleted_on_fb' => true]);
//            Log::info("Updated adsets: $updatedCount, mark as deleted on fb");
//            if ($updatedCount > 0) {
//                $updatedAdIds = $fbCampaign->fbAdsets()->whereIn('id', $adset_source_ids)
//                    ->with('fbAds')
//                    ->get()
//                    ->pluck('fbAds.*.id')
//                    ->collapse();
//                // 批量更新 FbAds
//                $updatedAdsCount = FbAd::whereIn('id', $updatedAdIds)->update(['is_deleted_on_fb' => true]);
//                Log::info("Updated FbAds: $updatedAdsCount");
//            }
//            $adsetsData = collect($adsetsParent)
        });

        return $source_ids;
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookFetchCampaign Job failed: ' . $exception->getMessage());
    }
}
