<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbApiToken;
use App\Models\FbCampaign;
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

class FacebookFetchCampaignV2 implements ShouldQueue
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

        Log::info("--- Fetch FB Campaign Data, Ad Account: {$this->fbAdAccountSourceID}");

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$this->fbAdAccountSourceID}/campaigns";
        $fields = 'id,account_id, bid_strategy,budget_remaining, configured_status,created_time, daily_budget, effective_status,lifetime_budget,name, objective, source_campaign_id, spend_cap, start_time, status, updated_time';
        $page_limit = 50;

        $query = [
            'fields' => $fields,
            'limit' => $page_limit,
            'filtering' => [
                [
                    'field' => 'campaign.effective_status',
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
                // 检查scope是否包含campaign
                if (!isset($accountFilter['scope']) || !in_array('campaign', $accountFilter['scope'])) {
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
            Log::info("--- Fetch FB Campaign Data, Ad Account: {$this->fbAdAccountSourceID} next page");
            $next = $paging->get('next');
            $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, '', $token);
            $this->processResponse($resp);
            $paging = collect($resp->get('paging'));
        }

        if ($this->continue_pull_next_level) {
            FacebookFetchAdsetV2::dispatch($this->fbAdAccountID, $this->fbAccountID, $this->date_start, $this->date_stop,
                $this->continue_pull_next_level, $this->pull_insights, $this->continue_pull_insights, $this->filtering)
                ->onQueue('facebook');
        }
        if ($this->pull_insights) {
            FacebookFetchCampaignInsights::dispatch($this->fbAdAccountID, $this->date_start, $this->date_stop,
                $this->fbAccountID, false)->onQueue('facebook');
        }
    }

    /*
     * @param Collection $resp
     * @return void
     */
    public function processResponse(Collection $resp): Collection
    {
        $source_ids = collect();
//        Log::debug(json_encode($resp));

        $fbCampaignCollection = collect($resp->get('data', []));
        $default_start_time = Carbon::createFromTimestamp(0);
        $camp_count = $fbCampaignCollection->count();
        Log::debug("fb campaign count: {$camp_count} ");
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
                $life_budget_string = $fbCampaignData['lifetime_budget'];
                $currency_offset = CurrencyUtils::$currencyConfig[$this->currency]['offset'];

                if (is_numeric($life_budget_string)) {
                    $lifetime_budget = intval($life_budget_string) / $currency_offset;
                    $lifetime_budget = CurrencyUtils::convertAndFormat($lifetime_budget, $this->currency, 'USD');
                } else {
                    $lifetime_budget = "-1";
                }
            }

            Log::debug("campaign: {$fbCampaignData['name']}");
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
        });

        return $source_ids;
    }

    public function tags(): array
    {
        return [
            'FB-Pull-Camp',
            "{$this->fbAdAccountSourceID}",
            "{$this->fbAccountID}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookFetchCampaign Job failed: ' . $exception->getMessage());
    }

}
