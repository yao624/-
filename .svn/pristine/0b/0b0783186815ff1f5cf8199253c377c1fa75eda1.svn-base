<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbCampaign;
use App\Models\FbCampaignInsight;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FacebookFetchCampaignInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $fbAdAccountID;
    private $adAccount;
    private $fbAccount;
    private mixed $fbAccountID;
    private mixed $dateStart;
    private mixed $dateStop;
    private mixed $next;
    private $sourceID;
    private $currency;
    private $filtering;

    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $date_start=null, $date_stop=null, $fbAccountID=null, $next=false, $filtering=[])
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->adAccount = FbAdAccount::query()->findOrFail($fbAdAccountID);
        $this->fbAccountID = $fbAccountID;
        $this->currency = $this->adAccount['currency'];
        $this->sourceID = $this->adAccount->source_id;
        $this->dateStart = $date_start;
        $this->dateStop = $date_stop;
        $this->next = $next;
        $this->filtering = $filtering;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $token = '';
        if ($this->fbAccountID == null) {
            $apiToken = $this->adAccount->apiTokens()->firstWhere('active', true);
            if ($apiToken) {
                $token = $apiToken['token'];
            } else {
                $query = $this->adAccount->fbAccounts()->where('token_valid', true);
                if ($query->count() == 0) {
                    Log::warning("no api token, nor fb account");
                    $msg = "{$this->sourceID} no api token or fb account available";
                    Telegram::sendMessage($msg);
                    throw new \Exception("no api token, nor fb account");
                } else {
                    $this->fbAccount = $query->first();
                    $this->fbAccountID = $this->fbAccount->id;
                }
            }
        } else {
            $this->fbAccount = FbAccount::query()->where('token_valid', true)
                ->where('id', $this->fbAccountID)->firstOrFail();
            $this->fbAccountID = $this->fbAccount->id;
        }

//        if (!$this->fbAccountID) {
//            $fbAccount = $this->adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
//        } else {
//            $fbAccount = FbAccount::query()->where('token_valid', true)
//                ->where('id', $this->fbAccountID)->firstOrFail();
//            $this->fbAccountID = $fbAccount->id;
//        }

        Log::info("--- fetch campaign insight, ad account id: {$this->adAccount->source_id}---");

        $dateRange = FbUtils::getRangeDate($this->dateStart, $this->dateStop);
        foreach ($dateRange as $currentDate) {
            $query = [
                'fields' => 'actions,account_currency,account_id,account_name,action_values,campaign_id,campaign_name,clicks,conversion_values,cost_per_action_type,cost_per_inline_link_click,cpc,cpm,ctr,date_start,date_stop,frequency,impressions,inline_link_click_ctr,inline_link_clicks,objective,purchase_roas,reach,quality_ranking,spend',
                'level' => 'campaign',
                'time_range[since]' => $currentDate,
                'time_range[until]' => $currentDate,
                'filtering' => [
                    [
                        'field' => 'campaign.effective_status',
                        'operator' => 'IN',
                        'value' => [
                            'ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'IN_PROCESS', 'WITH_ISSUES',
                            'PENDING_REVIEW', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED',
                        ],
                    ],
                    [
                        'field' => 'ad.impressions',
                        'operator' => 'GREATER_THAN',
                        'value' => 0
                    ]
                ]
            ];
            if (!empty($this->filtering)) {
                $query['filtering'][] = $this->filtering;
            }
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/act_{$this->adAccount->source_id}/insights";

            $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', null, null, $token);
            $this->saveCampaignInsights($resp);
            $paging = collect($resp->get('paging'));

            while ($paging->has('next')) {
                $next = $paging->get('next');
                Log::debug("has next, request next");
                $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, null, $token);
                $this->saveCampaignInsights($resp);
                $paging = collect($resp->get('paging'));
            }

            if ($this->next) {
                FacebookFetchAdsetInsights::dispatch($this->fbAdAccountID, $this->dateStart, $this->dateStop, $this->fbAccountID, $this->next)
                    ->onQueue('facebook')->delay(now()->addSeconds(5));
            }
        }

    }

    public function tags(): array
    {
        return [
            'FB-Camp-Ins',
            "{$this->sourceID}",
            "{$this->dateStart}:{$this->dateStop}",
            "{$this->adAccount->id}",
            "{$this->fbAccountID}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookFetchCampaignInsights Job failed: ' . $exception->getMessage());
        $msg = "faied to fetch campaign insight, ad account: {$this->sourceID}";
        Telegram::sendMessage($msg);
    }

    private function saveCampaignInsights(Collection $resp)
    {
        $data = collect($resp['data']);

        $campaignIds = collect();

        $data->each(function ($insightData) use ($campaignIds) {
            $add_to_cart = null;
            $purchase = null;
            $lead = null;
            $comment = null;
            $cost_per_purchase = null;
            $cost_per_lead = null;
            $cost_to_add_to_cart = null;

            $original_cost_per_purchase = null;
            $original_cost_per_lead = null;
            $original_cost_to_add_to_cart = null;

            if (isset($insightData['actions'])) {
                foreach ($insightData['actions'] as $action) {
                    if ($action['action_type'] == 'add_to_cart') {
                        $add_to_cart = intval($action['value']);
                    }
                    if ($action['action_type'] == 'purchase') {
                        $purchase = intval($action['value']);
                    }
                    if ($action['action_type'] == 'lead') {
                        $lead = intval($action['value']);
                    }
                    if ($action['action_type'] == 'comment') {
                        $comment = intval($action['value']);
                    }
                }
            }
            if (isset($insightData['cost_per_action_type'])) {
                foreach ($insightData['cost_per_action_type'] as $action) {
                    if ($action['action_type'] == 'purchase') {
                        $original_cost_per_purchase = floatval($action['value']);
                        $cost_per_purchase = floatval(CurrencyUtils::convert($action['value'], $this->currency));
                    }
                    if ($action['action_type'] == 'lead') {
                        $original_cost_per_lead = floatval($action['value']);
                        $cost_per_lead = floatval(CurrencyUtils::convert($action['value'], $this->currency));
                    }
                    if ($action['action_type'] == 'add_to_cart') {
                        $original_cost_to_add_to_cart = floatval($action['value']);
                        $cost_to_add_to_cart = floatval(CurrencyUtils::convert($action['value'], $this->currency));
                    }
                }
            }

            $campaignIds->push($insightData['campaign_id']);

            FbCampaignInsight::query()->updateOrCreate(
                [
                    'account_id' => $insightData['account_id'],
                    'campaign_id' => $insightData['campaign_id'],
                    'date_start' => Carbon::parse($insightData['date_start']),
                    'date_stop' => Carbon::parse($insightData['date_stop']),
                ],
                [
                    'account_currency' => $insightData['account_currency'],
                    'account_name' => $insightData['account_name'],
                    'actions' => $insightData['actions'] ?? [],
                    'action_values' => $insightData['action_values'] ?? [],
                    'clicks' => $insightData['clicks'] ?? null,
                    'cost_per_action_type' => $insightData['cost_per_action_type'] ?? [],
                    'cost_per_inline_link_click' => CurrencyUtils::convertAndFormat($insightData['cost_per_inline_link_click'] ?? null, $this->currency),
                    'cpc' => CurrencyUtils::convertAndFormat($insightData['cpc'] ?? null, $this->currency),
                    'cpm' => CurrencyUtils::convertAndFormat($insightData['cpm'] ?? null, $this->currency),
                    'ctr' => $insightData['ctr'] ?? null,
                    'frequency' => $insightData['frequency'] ?? null,
                    'impressions' => $insightData['impressions'] ?? null,
                    'inline_link_click_ctr' => $insightData['inline_link_click_ctr'] ?? null,
                    'inline_link_clicks' => $insightData['inline_link_clicks'] ?? null,
                    'objective' => $insightData['objective'] ?? '',
                    'purchase_roas' => $insightData['purchase_roas'] ?? [],
                    'purchase_roas_value' => isset($insightData['purchase_roas']) ? $insightData['purchase_roas'][0]['value'] : null,
                    'quality_ranking' => $insightData['quality_ranking'] ?? '',
                    'reach' => $insightData['reach'] ?? null,
                    'spend' => CurrencyUtils::convertAndFormat($insightData['spend'] ?? null, $this->currency),
                    'original_spend' => $insightData['spend'] ?? null,
                    'campaign_name' => $insightData['campaign_name'],
                    'add_to_cart' => $add_to_cart,
                    'purchase' => $purchase,
                    'lead' => $lead,
                    'comment' => $comment,
                    'cost_per_purchase' => $cost_per_purchase,
                    'cost_per_lead' => $cost_per_lead,
                    'cost_to_add_to_cart' => $cost_to_add_to_cart,
                    'original_cost_per_purchase' => $original_cost_per_purchase,
                    'original_cost_per_lead' => $original_cost_per_lead,
                    'original_cost_to_add_to_cart' => $original_cost_to_add_to_cart,
                ]
            );
        });

        // 更新 FbCampaign 的 update_at 字段
        if ($campaignIds->isNotEmpty()) {
            FbCampaign::whereIn('source_id', $campaignIds->unique())->update(['updated_at' => now()]);
        }
    }
}
