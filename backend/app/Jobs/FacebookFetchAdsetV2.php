<?php

namespace App\Jobs;

use App\Models\FbAccount;
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

class FacebookFetchAdsetV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $timeout = 2600;

    private FbAdAccount|null $adAccount;
    private FbAccount|null $fbAccount;
    private string $fbAdAccountID;
    private string $fbAdAccountSourceID;
    private string|null $fbAccountID;

    private mixed $date_stop;
    private mixed $date_start;
    private string $currency;
    private string $token;
    private bool $continue_pull_next_level;
    private bool $pull_insights;
    private bool $continue_pull_insights;
    private array $filtering;
    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $fbAccountID=null, $date_start=null, $date_stop=null,
                                $continue_pull_next_level=false, $pull_insights=false, $continue_pull_insights=false,
                                $filtering=[])
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->adAccount = FbAdAccount::query()->findOrFail($fbAdAccountID);
        $this->fbAdAccountSourceID = $this->adAccount->source_id;
        $this->currency = $this->adAccount->currency;

        $this->fbAccountID = $fbAccountID;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;

        $this->continue_pull_next_level = $continue_pull_next_level;
        $this->pull_insights = $pull_insights;
        $this->continue_pull_insights = $continue_pull_insights;

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
            $this->fbAccount = null;
            $apiToken = $this->adAccount->apiTokens()->firstWhere('active', true);
            if ($apiToken) {
                $token = $apiToken->token;
                $this->token = $token;
            } else {
                // 重新查找 token 有效的 fb account
                $fbAccount = $this->adAccount->fbAccounts()->where('token_valid', true)->first();
                if ($fbAccount) {
                    $this->fbAccount = $fbAccount;
                    $this->fbAccountID = $this->fbAccount->id;
                } else {
                    Log::warning("no api token, nor fb account");
                    $msg = "{$this->adAccount->source_id} no api token or fb account available";
                    Telegram::sendMessage($msg);
                    throw new \Exception("no api token, nor fb account");
                }
            }
        } else {
            $this->fbAccount = $this->adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
            $this->fbAccountID = $this->fbAccount->id;
        }

        Log::info("--- Fetch FB Adset Data, Ad Account: {$this->fbAdAccountSourceID}");

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$this->fbAdAccountSourceID}/adsets";
        $fields = 'id, account_id, adset_schedule, attribution_spec, bid_amount, bid_info, bid_strategy,billing_event, budget_remaining,created_time, campaign_id,configured_status,daily_budget,daily_spend_cap,dsa_beneficiary, dsa_payor,effective_status,instagram_actor_id,is_dynamic_creative,lifetime_budget,lifetime_spend_cap,name,optimization_goal,promoted_object{custom_event_type,pixel_id,page_id},source_adset_id,start_time,status,targeting';
        $page_limit = 20;

        $query = [
            'fields' => $fields,
            'limit' => $page_limit,
            'filtering' => [
                [
                    'field' => 'adset.effective_status',
                    'operator' => 'IN',
                    'value' => [
                        'ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'IN_PROCESS', 'WITH_ISSUES',
                        'PENDING_REVIEW', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED',
                    ],
                ]
            ]
        ];

                        // 添加FbAdAccount的filters（优先级高于代码中的filtering）
        if ($this->adAccount->filters) {
            $accountFilters = $this->adAccount->filters;
            foreach ($accountFilters as $accountFilter) {
                // 检查scope是否包含adset
                if (!isset($accountFilter['scope']) || !in_array('adset', $accountFilter['scope'])) {
                    continue;
                }

                $filterForFb = [
                    'field' => $accountFilter['field'],
                    'operator' => $accountFilter['operator'],
                    'value' => $accountFilter['value']
                ];

                // 检查是否与现有filtering重复，如果重复则替换
                $replaced = false;
                foreach ($query['filtering'] as $index => $existingFilter) {
                    if ($existingFilter['field'] === $accountFilter['field']) {
                        $query['filtering'][$index] = $filterForFb; // 替换为ad account的filter
                        $replaced = true;
                        break;
                    }
                }

                // 如果没有重复，则添加
                if (!$replaced) {
                    $query['filtering'][] = $filterForFb;
                }
            }
        }

        if (!empty($this->filtering)) {
            $query['filtering'][] = $this->filtering;
        }

        $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', null, '', $token);
        $paging = collect($resp->get('paging'));
        $this->processResponse($resp);

        while ($paging->has('next')) {
            Log::info("--- Fetch FB Adset Data, Ad Account: {$this->fbAdAccountSourceID} next page");
            $next = $paging->get('next');
            $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, '', $token);
            $this->processResponse($resp);
            $paging = collect($resp->get('paging'));
        }

        if ($this->continue_pull_next_level) {
            FacebookFetchAdV2::dispatch($this->fbAdAccountID, $this->fbAccountID, $this->date_start, $this->date_stop,
                $this->pull_insights, $this->filtering)->onQueue('facebook');
        }

        if ($this->pull_insights) {
            FacebookFetchAdsetInsights::dispatch($this->fbAdAccountID, $this->date_start, $this->date_stop,
                $this->fbAccountID, false)->onQueue('facebook');
        }
    }

    private function processResponse(array|Collection $resp): Collection
    {
        $source_ids = collect();
//        Log::debug(json_encode($resp));

        $default_start_time = Carbon::createFromTimestamp(0);
        $adset_source_ids = collect();
        $adsetsData = collect($resp->get('data',[]));

        $fbCampaignSourceIds = $adsetsData->pluck('campaign_id');
        $existingCampaign = FbCampaign::query()->whereIn('source_id', $fbCampaignSourceIds)->get();

        $adsetsData->each(function ($adsetData) use ($existingCampaign, $default_start_time, &$adset_source_ids) {

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
                $life_budget_string = $adsetData['lifetime_budget'];
                $currency_offset = CurrencyUtils::$currencyConfig[$this->currency]['offset'];

                if (is_numeric($life_budget_string)) {
                    $lifetime_budget = intval($life_budget_string) / $currency_offset;
                    $lifetime_budget = CurrencyUtils::convertAndFormat($lifetime_budget, $this->currency, 'USD');
                } else {
                    $lifetime_budget = "-1";
                }
            }

            if (isset($adsetData['bid_amount'])) {
                $bid_amount = $adsetData['bid_amount'];
                $currency_offset = CurrencyUtils::$currencyConfig[$this->currency]['offset'];

                if (is_numeric($bid_amount)) {
                    $bid_amount = intval($bid_amount) / $currency_offset;
                    $bid_amount = CurrencyUtils::convertAndFormat($bid_amount, $this->currency, 'USD');
                } else {
                    $bid_amount = null;
                }
            } else {
                $bid_amount = null;
            }

            $adset_source_ids->push($adsetData['id']);
            $campaign_source_id = $adsetData['campaign_id'];
            $fbCampaign = $existingCampaign->firstWhere('source_id',$campaign_source_id);
            if (!$fbCampaign) {
                Log::warning("campaign not in system: {$campaign_source_id}");
                Telegram::sendMessage("campaign not in system: {$campaign_source_id}");
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
                    'bid_strategy' => $adsetData['bid_strategy'] ?? null,
                    'billing_event' => $adsetData['billing_event'],
                    'bid_amount' => $bid_amount,
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
                    'targeting' => $adsetData['targeting'] ?? [],
                    'original_daily_budget' => $adsetData['daily_budget'] ?? null,
                    'original_lifetime_budget' => $adsetData['lifetime_budget'] ?? null,
                    'original_bid_amount' => $adsetData['bid_amount'] ?? null,
                ]
            );

        });

        return $source_ids;
    }

    public function tags(): array
    {
        return [
            'FB-Pull-Adset',
            "{$this->fbAdAccountSourceID}",
            "{$this->fbAccountID}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookFetchAdset Job failed: ' . $exception->getMessage());
    }


}
