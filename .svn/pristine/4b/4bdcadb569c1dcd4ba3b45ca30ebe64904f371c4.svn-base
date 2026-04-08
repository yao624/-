<?php

namespace App\Jobs;

use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbBm;
use App\Models\FbPixel;
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
use Illuminate\Support\Facades\Log;

class FacebookSyncAdAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $fbAdAccountID;
    private mixed $fbAccountID;
    private mixed $dateStart;
    private mixed $dateStop;
    private mixed $next;
    private mixed $fbAdAccountSourceId;
    private FbAdAccount|null $fbAdAccount;
    private mixed $token;
    /**
     * @var mixed|null
     */
    private FbAccount|null $fbAccount;
    private $fetchObjectFiltering;

    /**
     * $fbAccountID 可以为空
     * Create a new job instance.
     */
    public function __construct($fbAdAccountID, $date_start=null, $date_stop=null, $fbAccountID=null, $next=false, $fetchObjectFiltering = [])
    {
        $this->fbAdAccountID = $fbAdAccountID;
        $this->fbAccountID = $fbAccountID;
        $this->dateStart = $date_start;
        $this->dateStop = $date_stop;
        $this->next = $next;
        $this->fbAdAccount = FbAdAccount::query()->findOrFail($fbAdAccountID);
        $this->fbAdAccountSourceId = $this->fbAdAccount->source_id;
        $this->fbAccount = null;
        $this->fetchObjectFiltering = $fetchObjectFiltering;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info(" --- Sync Fb Ad Account info : {$this->fbAdAccountSourceId}---");

        $token = '';
        if ($this->fbAccountID == null) {
            Log::debug("fb account id is null");
            $this->fbAccount = null;
            $apiToken = $this->fbAdAccount->apiTokens()->firstWhere('active', true);
            if ($apiToken) {
                $token = $apiToken->token;
                $this->token = $token;
            } else {
                // 重新查找 token 有效的 fb account
                $fbAccount = $this->fbAdAccount->fbAccounts()->where('token_valid', true)->first();
                if ($fbAccount) {
                    $this->fbAccount = $fbAccount;
                    $this->fbAccountID = $this->fbAccount->id;
                } else {
                    Log::warning("no api token, nor fb account");
                    $msg = "{$this->fbAdAccount->source_id} no api token or fb account available";
                    Telegram::sendMessage($msg);
                    throw new \Exception("no api token, nor fb account");
                }
            }
        } else {
            $this->fbAccount = $this->fbAdAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
            $this->fbAccountID = $this->fbAccount->id;
        }

        $adAccount = $this->fbAdAccount;
        $currency = $adAccount->currency;

//        if (!$this->fbAccountID) {
//            $fbAccount = $adAccount->fbAccounts()->where('token_valid', true)->firstOrFail();
//        } else {
//            $fbAccount = FbAccount::query()->where('token_valid', true)
//                ->where('id', $this->fbAccountID)->firstOrFail();
//        }

        Log::info("---ad account id: {$this->fbAdAccountSourceId}---");
        if ($token) {
            // current_unbilled_spend, adspaymentcycle, max_billing_threshold, adtrust_dsl,business_restriction_reason
            $query = [
                'fields' => 'name,id,account_status,disable_reason,balance,amount_spent,timezone_name,timezone_id,currency,age,spend_cap,is_prepay_account,funding_source_details,owner,adspixels{id,name,is_unavailable,is_created_by_business,owner_business}',
            ];
        } else {
            $query = [
                'fields' => 'name,id,adtrust_dsl,account_status,disable_reason,balance,amount_spent,business_restriction_reason,timezone_name,timezone_id,currency,age,max_billing_threshold,current_unbilled_spend,spend_cap,is_prepay_account,owner,adspixels{id,name,is_unavailable,is_created_by_business,owner_business}',
            ];
        }

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$this->fbAdAccountSourceId}";

        $resp = FbUtils::makeRequest($this->fbAccount, $endpoint, $query, 'GET', '', '',$token);

        $old_status = $adAccount->account_status;
        $new_human_status = FbUtils::$FbAccountStatusMap[$resp['account_status']];
        if ($old_status != $new_human_status) {
            $msg = "ad account: {$adAccount->name}({$adAccount->source_id}) status changed, old: {$old_status}, new: {$new_human_status}";
            Telegram::sendMessage($msg);
        }



        $original_balance = $resp['balance'];
        $balance = $original_balance;
        if ($original_balance !== '0') {
            $balance = CurrencyUtils::convert($original_balance, $currency, 'USD', 2);
        }

        $original_spend_cap = $resp['spend_cap'];
        $spend_cap = $original_spend_cap;
        if ($original_spend_cap !== '0') {
            $spend_cap = CurrencyUtils::convert($original_spend_cap, $currency, 'USD', 2);
        }

        $original_amount_spent = $resp['amount_spent'];
        $amount_spent = $original_amount_spent;
        if ($original_amount_spent !== '0') {
            $amount_spent = CurrencyUtils::convert($original_amount_spent, $currency, 'USD', 2);
        }

        $adAccount->update(
            [
                'account_status' => FbUtils::$FbAccountStatusMap[$resp['account_status']] ?? "Unknown",
                'account_status_code' => $resp['account_status'],
                'age' => $resp['age'],
                'total_spent' => $amount_spent,
                'balance' => $balance,
                'original_balance' => $original_balance,
                'amount_spent' => $amount_spent,
                'original_amount_spent' => $original_amount_spent,
                'spend_cap' => $spend_cap,
                'original_spend_cap' => $original_spend_cap,
                'currency' => $resp['currency'],
                'disable_reason' => FbUtils::$FbAdAccountDisableReasonMap[$resp['disable_reason']] ?? 'Unknown',
                'disable_reason_code' => $resp['disable_reason'],
                'name' => $resp['name'],
                'owner' => $resp['owner'],
                'timezone_id' => $resp['timezone_id'],
                'timezone_name' => $resp['timezone_name'],
                'is_prepay_account' => $resp['is_prepay_account']
            ]
        );
        if (isset($resp['adspaymentcycle'])) {
            $adAccount['adspaymentcycle'] = $resp['adspaymentcycle'] ?? [];
        }
        if (isset($resp['current_unbilled_spend'])) {
            $adAccount['current_unbilled_spend'] = $resp['current_unbilled_spend'];
        }
        if (isset($resp['max_billing_threshold'])) {
            $adAccount['max_billing_threshold'] = $resp['max_billing_threshold'];
        }
        if (isset($resp['adtrust_dsl'])) {
            $adtrust_dsl = $resp['adtrust_dsl'];
            $original_adtrust_dsl = $resp['adtrust_dsl'];
            if ($original_adtrust_dsl != -1) {
                $adtrust_dsl = CurrencyUtils::convertToFloat($original_adtrust_dsl, $currency);
            }
            $adAccount['adtrust_dsl'] = $adtrust_dsl;
            $adAccount['original_adtrust_dsl'] = $original_adtrust_dsl;

        }
        if (isset($resp['business_restriction_reason'])) {
            $adAccount['business_restriction_reason'] = $resp['business_restriction_reason'];
        }

        if (isset($resp['funding_source_details'])) {
            $adAccount['funding_type'] = $resp['funding_source_details']['type'];
            $adAccount['default_funding'] = $resp['funding_source_details']['display_string'];
        }

        $adAccount->save();

        // 关联 pixel
        $adpixels = collect(collect($resp)->get('adspixels'));
        $adpixelsData = collect($adpixels->get('data'));
        $adpixelsData->each(function ($adpixel) use ($adAccount) {
            Log::debug("pixel id: {$adpixel['id']}");
            $fbPixel = FbPixel::query()->updateOrCreate(
                [
                    'pixel' => $adpixel['id']
                ],
                [
                    'name' => $adpixel['name'],
                    'is_created_by_business' => $adpixel['is_created_by_business'],
                    'is_unavailable' => $adpixel['is_unavailable'],
                    'owner_business' => $adpixel['owner_business'] ?? [],
                    'is_dataset' => $adpixel['is_consolidated_container'] ?? false
                ]
            );

            // 与 AdAccount 关联
            $fbPixel->fbAdAccounts()->syncWithoutDetaching([$adAccount->id]);

            // 与 BM 关联
            if (isset($adpixel['owner_business'])) {
                $bmSourceID = $adpixel['owner_business']['id'];
                $fbBm = FbBm::query()->firstWhere('source_id', $bmSourceID);
                if ($fbBm) {
                    $fbPixel->fbBms()->syncWithoutDetaching([$fbBm->id]);
                }
            }
        });

        if ($this->next) {
            // 如果 next 为 true, 1，拉取广告结构。2 拉取对应时间范围内的花费
//            FacebookFetchCampaign::dispatch($this->fbAdAccountID, $this->dateStart, $this->dateStop, $this->fbAccountID,
//                $this->next, true, 3)->onQueue('facebook');
            FacebookFetchCampaignV2::dispatch($this->fbAdAccountID, $this->fbAccountID, $this->dateStart,
                $this->dateStop, true, true, true, $this->fetchObjectFiltering)->onQueue('facebook');

            if ($this->dateStart && $this->dateStop) {
                // 因为 FetchInsights 如果不传 date_start 和 date_stop, 默认是获取最大范围内的花费的，所以这里需要检查
                // 获取所有历史的消耗，使用单独的方法
                // 这里的 next 为 false, 因为 campaign/adset/ad 层级，在获取 Campaign 后再拉取
                FacebookFetchAdAccountInsights::dispatch($this->fbAdAccountID, $this->dateStart,
                    $this->dateStop, $this->fbAccountID, false)->onQueue('facebook');
            }
        }
    }

    public function tags(): array
    {
        return [
            'FB-Sync-Ad-Account',
            "{$this->fbAdAccountSourceId}"
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('FacebookSyncAdAccount Job failed: ' . $exception->getMessage());

        // 检查是否是权限丢失错误
        if (strpos($exception->getMessage(), 'Ad account owner has NOT grant ads_management or ads_read permission') !== false) {
            Log::warning("检测到广告账户权限丢失", [
                'ad_account_id' => $this->fbAdAccount->source_id,
                'error' => $exception->getMessage()
            ]);

            try {
                $fraudActionsService = app(\App\Services\FraudActionsService::class);
                $fraudActionsService->handleAccountPermissionLoss($this->fbAdAccount);
            } catch (\Exception $e) {
                Log::error("处理广告账户权限丢失失败", [
                    'ad_account_id' => $this->fbAdAccount->source_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
