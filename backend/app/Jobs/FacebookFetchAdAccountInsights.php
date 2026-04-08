<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbAdAccountInsight;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacebookFetchAdAccountInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $fbAdAccountID;
    private $adAccount;
    private $fbAccount;
    private $fbAccountID;
    private $dateStart;
    private $dateStop;
    private mixed $next;
    private $souceID;
    private $currency;

    /**
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $date_start=null, $date_stop=null, $fbAccountID=null, $next=false)
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->adAccount = FbAdAccount::query()->findOrFail($fbAdAccountID);
        $this->souceID = $this->adAccount->source_id;
        $this->currency = $this->adAccount['currency'];
        $this->fbAccountID = $fbAccountID;
        $this->dateStart = $date_start;
        $this->dateStop = $date_stop;
        $this->next = $next;
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
                    $msg = "{$this->souceID} no api token or fb account available";
                    Telegram::sendMessage($msg);
                    throw new \Exception("no api token, nor fb account");
                } else {
                    $this->fbAccount = $query->first();
                    $this->fbAccountID = $this->fbAccount->id;
                }
            }
        } else {
            # TODO: 如果 当前fb account token失效了，可以检查其它 fb account
            $this->fbAccount = FbAccount::query()->where('token_valid', true)
                ->where('id', $this->fbAccountID)->firstOrFail();
            $this->fbAccountID = $this->fbAccount->id;
        }

        # 查找到一个 token valid 是有效的fb account
//        if (!$this->fbAccountID) {
//            $fbAccount = $this->adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
//        } else {
//            $fbAccount = FbAccount::query()->where('token_valid', true)
//                ->where('id', $this->fbAccountID)->firstOrFail();
//        }

        Log::info("--- fetch account insight, ad account id: {$this->adAccount->source_id}---");

        if (!$this->dateStart && !$this->dateStop) {
            // 首先获取哪些时间段有数据
            Log::debug("no date_start and date_stop, continue: {$this->next}");
            $fields = 'impressions';
            $query = [
                'fields' => $fields,
                'level' => 'account',
                'date_preset' => 'maximum',
                'time_increment' => 1
            ];
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/act_{$this->adAccount->source_id}/insights";

            $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', null, null, $token);

//            Log::debug($resp);
            $period = $this->checkDatePeriod($resp);
            $paging = collect($resp->get('paging'));
            while ($paging->has('next')) {
                $next = $paging->get('next');
                $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, null, $token);
                $paging = collect($resp->get('paging'));
                $nextPeriod = $this->checkDatePeriod($resp);
                $period = $period->merge($nextPeriod);
            }
            $period = $period->unique();

            // 根据时间，批量获取数据
            $period->map(function ($date_str, $index) {
                FacebookFetchAdAccountInsights::dispatch($this->fbAdAccountID, $date_str, $date_str, $this->fbAccountID, true)
                    ->onQueue('facebook')->delay(now()->addSeconds(($index + 1) * 10));
            });
            // 再根据时间，你依次获取每天的 account insights
        } else if($this->dateStart && $this->dateStop) {
            Log::info("params: {$this->dateStart} to {$this->dateStop}, next: {$this->next}");

            $dateRange = FbUtils::getRangeDate($this->dateStart, $this->dateStop);
            foreach ($dateRange as $currentDate) {
                Log::debug($currentDate);
                $query = [
                    'fields' => 'actions,account_currency,account_id,account_name,action_values,clicks,conversion_values,cost_per_action_type,cost_per_inline_link_click,cpc,cpm,ctr,date_start,date_stop,frequency,impressions,inline_link_click_ctr,inline_link_clicks,objective,purchase_roas,reach,quality_ranking,spend',
                    'level' => 'account',
                    'time_range[since]' => $currentDate,
                    'time_range[until]' => $currentDate
                ];
                $version = FbUtils::$API_Version;
                $endpoint = "https://graph.facebook.com/{$version}/act_{$this->adAccount->source_id}/insights";

                $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', null, null, $token);
                $this->saveFbAdAccountInsights($resp);
                $paging = collect($resp->get('paging'));
                while ($paging->has('next')) {
                    $next = $paging->get('next');
                    $resp = FbUtils::makeRequest($this->fbAccount, $next, null, 'GET', null, null, $token);
                    $this->saveFbAdAccountInsights($resp);
                    $paging = collect($paging->get('paging'));
                }
            }

            if ($this->next) {
                FacebookFetchCampaignInsights::dispatch($this->fbAdAccountID, $this->dateStart, $this->dateStop,
                    $this->fbAccountID, $this->next)
                    ->onQueue('facebook')->delay(now()->addSeconds(5));
            }
        }
    }

    public function tags(): array
    {
        return [
            'FB-Adac-Ins',
            "{$this->souceID}",
            "{$this->dateStart}:{$this->dateStop}",
            "{$this->adAccount->id}",
            "{$this->fbAccountID}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookFetchAccountInsights Job failed: ' . $exception->getMessage());
        $msg = "failed to fetch ad account insights: {$this->souceID}";
        Telegram::sendMessage($msg);
    }

    private function checkDatePeriod(Collection $resp)
    {
        $dataCollection = collect($resp['data']);

        return $dataCollection->map(function ($item) {
            return $item['date_start'];
        });
    }

    private function saveFbAdAccountInsights(Collection $resp)
    {
        $data = collect($resp['data']);

        $data->each(function ($insightData) {
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
            FbAdAccountInsight::query()->updateOrCreate(
                [
                    'account_id' => $insightData['account_id'],
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

        // 更新 FbAdAccount 的 update_at 字段
        if ($data->isNotEmpty()) {
            $this->adAccount->touch();
        }
    }
}
