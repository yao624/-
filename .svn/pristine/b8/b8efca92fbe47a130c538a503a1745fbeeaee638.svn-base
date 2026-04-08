<?php

namespace App\Jobs;

use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
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

class FacebookFetchAdset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $timeout = 600;

    private $fbAdAccountID;
    private $adAccount;
    private $fbAdAccountSoruceID;

    private mixed $date_stop;
    private mixed $date_start;
    private $next;
    private mixed $fbAccountID;
    private $fbAccount;
    private $token;
    private $all;
    private $currency;
    private $lastDays;
    private $filtering;
    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $date_start=null, $date_stop=null, $fbAccountID=null, $next=true,
                                $all=false, $lastDays=1, $filtering=[])
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->adAccount = FbAdAccount::query()->findOrFail($fbAdAccountID);
        $this->fbAdAccountSoruceID = $this->adAccount->source_id;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
        $this->currency = $this->adAccount['currency'];
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

        Log::info("--- Fetch FB Adset Data, Ad Account: {$this->fbAdAccountSoruceID}");

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$this->fbAdAccountSoruceID}/adsets";
        $fields = 'id, account_id, adset_schedule, attribution_spec, bid_amount, bid_constraints, bid_info, bid_strategy,billing_event, budget_remaining,created_time, campaign_id,configured_status,daily_budget,daily_spend_cap,dsa_beneficiary, dsa_payor,effective_status,instagram_actor_id,is_dynamic_creative,lifetime_budget,lifetime_spend_cap,name,optimization_goal,promoted_object{custom_event_type,pixel_id,page_id},source_adset_id,start_time,status,targeting,ads.limit(50){id,account_id, ad_active_time,adset_id,campaign_id, configured_status,created_time,creative{body, title, id,actor_id,call_to_action_type, effective_instagram_story_id,effective_object_story_id,instagram_permalink_url,instagram_story_id,link_url,object_story_id,object_id,object_story_spec,object_url,status,thumbnail_url, object_type,link_destination_display_url, object_store_url},effective_status,name,preview_shareable_link,source_ad_id,status, updated_time}';
        $page_limit = 20;
        $query = [
            'fields' => $fields,
            'limit' => $page_limit,
            'filtering' => [
                [
                    'field' => 'adset.effective_status',
                    'operator' => 'IN',
                    'value' => ['ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'IN_PROCESS', 'WITH_ISSUES',
                        'PENDING_REVIEW', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED'],
                ],
            ],
        ];

        if ($this->all) {
            // 如果同步所有为 true, 创建时间这一步要检查 date start, 如果有时间范围，就在时间范围内获取
            // 如果没有，则在时间通过 lastDays 来获取 create_time 的时间
            if ($this->date_start) {
                $query['filtering'][] = [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => Carbon::parse($this->date_start)->timestamp,
                ];
                if ($this->date_stop) {
                    $query['filtering'][] = [
                        'field' => 'created_time',
                        'operator' => 'LESS_THAN',
                        'value' => Carbon::parse($this->date_stop)->timestamp,
                    ];
                }
            } else {
                $timestamp = Carbon::now()->subDays($this->lastDays)->timestamp;
                $query['filtering'][] = [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => $timestamp,
                ];
            }

        } else {
            // 如果all不是 true, 则也根据是否有 date_start 和 date_stop 判断，默认是 30天
            if ($this->date_start) {
                $query['filtering'][] = [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => Carbon::parse($this->date_start)->timestamp,
                ];
                if ($this->date_stop) {
                    $query['filtering'][] = [
                        'field' => 'created_time',
                        'operator' => 'LESS_THAN',
                        'value' => Carbon::parse($this->date_stop)->timestamp,
                    ];
                }
            } else {
                $last30days =  Carbon::now()->subDays(30)->timestamp;
                $query['filtering'][] = [
                    'field' => 'created_time',
                    'operator' => 'GREATER_THAN',
                    'value' => $last30days,
                ];
            }
        }

        if (!empty($this->filtering)) {
            $query['filtering'][] = $this->filtering;
        }
//        Log::debug($query);

        $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', null, '', $token);
//        Log::debug("resp json: {$resp->toJson()}");

        $paging = collect($resp->get('paging'));
        $adset_ids = collect();
        $ids = $this->processResponse($resp);
        $adset_ids = $adset_ids->concat($ids);
        while ($paging->has('next')) {
            Log::info("--- Fetch FB Adset Data, Ad Account: {$this->fbAdAccountSoruceID} new page");
            $next = $paging->get('next');
            $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, '', $token);
//            Log::debug("response");
//            Log::debug($resp);
            $ids = $this->processResponse($resp);
            $adset_ids = $adset_ids->concat($ids);
            $paging = collect($resp->get('paging'));
        }

        if ($this->next) {
            FacebookFetchAd::dispatch($this->fbAdAccountID, $this->date_start, $this->date_stop, $this->fbAccountID, false, true, $this->lastDays);
        }
    }

    private function processResponse(Collection $resp)
    {
        $default_start_time = Carbon::createFromTimestamp(0);
        $adset_source_ids = collect();
        $adsetsData = collect($resp->get('data',[]));

        $adsetsData->each(function ($adsetData) use ($default_start_time, &$adset_source_ids) {
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
//                $daily_budget = CurrencyUtils::convertAndFormat($adsetData['daily_budget'], $this->currency, 'USD');
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
//                $lifetime_budget = CurrencyUtils::convertAndFormat($adsetData['lifetime_budget'], $this->currency, 'USD');
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

            $campaign_source_id = $adsetData['campaign_id'];
            $fbCampaign = FbCampaign::query()->firstWhere('source_id',$campaign_source_id);
            if (!$fbCampaign) {
                Log::warning("campaign not in system: {$campaign_source_id}");
                return;
            }

            FbAdset::query()->updateOrCreate(
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
        });

        return $adset_source_ids;
    }
}
