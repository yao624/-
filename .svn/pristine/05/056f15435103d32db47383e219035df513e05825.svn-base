<?php

namespace App\Jobs;

use App\Enums\BusinessAdAccountRole;
use App\Enums\BusinessUserRelation;
use App\Enums\EnumCatalogRelation;
use App\Enums\EnumCatalogRole;
use App\Enums\EnumCatalogTasks;
use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbApiToken;
use App\Models\FbApp;
use App\Models\FbBm;
use App\Models\FbBusinessUser;
use App\Models\FbCatalog;
use App\Models\FbCatalogProduct;
use App\Models\FbCatalogProductSet;
use App\Models\FbPage;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FacebookSyncApiResource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $token_id;
    /**
     * Create a new job instance.
     */
    public function __construct($token_id)
    {
        $this->token_id = $token_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // app token 也可以获取到 bm, bm 的 pages, 但是无法获取到 page 的 token,所以暂时不用 /me/businesses 的方式关联 多个bm
        $fbBm = null;
        $fb_token = FbApiToken::query()->where('id', $this->token_id)->firstOrFail();
        try {
            if ($fb_token->bm_id) {
                $fbBm = $this->sync_bm($fb_token);
            }
        } catch (\Exception $e) {
            Log::debug("failed to sync bm");
            Telegram::sendMessage("failed to sync bm");
            Log::debug($e->getMessage());
        }

        try {
            $this->sync_pages($fb_token, $fbBm);
        } catch (\Exception $e) {
            Log::debug("failed to sync page");
            Telegram::sendMessage("failed to sync page");
            Log::debug($e->getMessage());
        }
        try {
            $this->sync_ad_accounts($fb_token, $fbBm);
        } catch (\Exception $e) {
            Log::debug("failed to sync ad accounts");
            Telegram::sendMessage("failed to sync ad accounts");
            Log::debug($e->getMessage());
        }

        try {
            $this->sync_pixel($fb_token, $fbBm);
        } catch (\Exception $e) {
            Log::debug("failed to sync bm pixel");
            Telegram::sendMessage("failed to sync bm pixel");
            Log::debug($e->getMessage());
        }

        try {
            $this->sync_users($fb_token, $fbBm);
        } catch (\Exception $e) {
            Log::debug("failed to sync bm users");
            Telegram::sendMessage("failed to sync bm users");
            Log::debug($e->getMessage());
        }

        try {
            $this->sync_catalogs($fb_token, $fbBm);
        } catch (\Exception $e) {
            Log::debug("failed to sync bm catalogs");
            Telegram::sendMessage("failed to sync bm catalogs");
            Log::debug($e->getMessage());
        }

        // 只在 token_type 为 1 的时候同步 apps
        if ($fb_token->token_type === 1) {
            try {
                $this->sync_apps($fb_token, $fbBm);
            } catch (\Exception $e) {
                Log::debug("failed to sync bm apps");
                Telegram::sendMessage("failed to sync bm apps");
                Log::debug($e->getMessage());
            }
        }
    }

    public function tags()
    {
        return [
            'Sync-API-Acc'
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        Log::error('Sync API Acc Job failed: ' . $exception->getMessage());
        try {
            $token = FbApiToken::query()->firstWhere('id', $this->token_id);
            $msg = "Failed to sync api acc resource: {$token->name}";
            Telegram::sendMessage($msg);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::debug("failed to send notification");
        }
    }

    public function sync_bm(FbApiToken $fbApiToken): ?FbBm
    {
        Log::debug("--- start sync bm ---");
        $query = [
            'fields' => 'id,name,created_time,link,timezone_id,primary_page,verification_status',
        ];
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/{$fbApiToken->bm_id}";

        $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $fbApiToken->token);
        if ($resp['success']) {
            $bmData = $resp;
            if (isset($bmData['timezone_id'])) {
                $timezone_id = $bmData['timezone_id'];
            } else {
                $timezone_id = null;
            }
            $fbBM = FbBm::query()->updateOrCreate(
                [
                    'source_id' => $bmData['id']
                ],
                [
                    'name' => $bmData['name'],
                    'timezone_id' => $timezone_id,
                    'verification_status' => $bmData['verification_status'],
                    'created_time' => $bmData['created_time']
                ]
            );
            return $fbBM;
//            $pivotData = [
//                'relation' => BusinessUserRelation::SystemUser->value,
//                'source_id' => $adAccountData['account_id']
//            ];
//            $result = $adAccount->fbBms()->syncWithoutDetaching([
//                $fbBM->id => $pivotData
//            ]);
        } else {
            Log::warning("failed to sync bm");
            Telegram::sendMessage("failed to sync bm");
        }

        return null;

    }

    /**
     * @return void
     * @throws \Exception
     */
    public function sync_ad_accounts(FbApiToken $fb_api_token, FbBm|null $bindBm): void
    {
        Log::info("--- start sync api acc ---");

        $version = FbUtils::$API_Version;

        // bm type 2
        if ($fb_api_token->token_type === 3) {
            Log::debug("bm type 2");
            $query = [
                'fields' => 'id,account_id,account_status,amount_spent,age,balance,business{id,name,created_time,link,timezone_id,primary_page,verification_status},created_time,currency,disable_reason,name,spend_cap,timezone_id,timezone_name,owner,is_prepay_account,tasks,adspixels{id,name,is_unavailable,is_created_by_business,owner_business},funding_source_details',
                'limit' => 10,
            ];
            $endpoint = "https://graph.facebook.com/{$version}/{$fb_api_token->bm_id}/owned_ad_accounts";

        } elseif ($fb_api_token->token_type === 1) {
            // bm token
            $query = [
                'fields' => 'owned_ad_accounts.limit(1){id,account_id,account_status,amount_spent,age,balance,created_time,currency,disable_reason,name,spend_cap,timezone_id,timezone_name,owner,is_prepay_account,adspixels{id,name,is_unavailable,is_created_by_business,owner_business},funding_source_details,permitted_tasks},client_ad_accounts.limit(10){id,account_id,account_status,amount_spent,age,balance,created_time,currency,disable_reason,name,spend_cap,timezone_id,timezone_name,owner,is_prepay_account,adspixels{id,name,is_unavailable,is_created_by_business,owner_business},funding_source_details,permitted_tasks}',
            ];
            $endpoint = "https://graph.facebook.com/{$version}/{$fb_api_token->bm_id}";
        } else {
            $query = [
                'fields' => 'id,account_id,account_status,amount_spent,age,balance,business{id,name,created_time,link,timezone_id,primary_page,verification_status},created_time,currency,disable_reason,name,spend_cap,timezone_id,timezone_name,owner,is_prepay_account,tasks,adspixels{id,name,is_unavailable,is_created_by_business,owner_business},funding_source_details,permitted_tasks',
                'limit' => 10,
            ];
            $endpoint = "https://graph.facebook.com/{$version}/me/adaccounts";
        }


//        $token = FbApiToken::query()->where('id', $this->token_id)->firstOrFail();
        $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $fb_api_token['token']);
//        Log::debug("23333");
//        Log::debug($resp);
        if ($resp['success']) {
            Log::debug('success');

            // Step 1: 获取当前数据库中与该 token 关联的 ad accounts
            $currentAdAccountIds = $fb_api_token->adAccounts()->pluck('source_id')->toArray();

            if ($fb_api_token->token_type === 1) {
                $clientAdAccountResp = collect($resp->get('client_ad_accounts', []));
                $ownedAdAccountResp = collect($resp->get('owned_ad_accounts', []));

                $clientAdAccountIds = $this->save_ad_account_response(collect($clientAdAccountResp), $fb_api_token, $bindBm);
                $ownedAdAccountIds = $this->save_ad_account_response(collect($ownedAdAccountResp), $fb_api_token, $bindBm);

                $incommingAdAccountIds = array_merge($clientAdAccountIds, $ownedAdAccountIds);
            } else {
                $incommingAdAccountIds = $this->save_ad_account_response(collect($resp), $fb_api_token, $bindBm);
            }

//            $incommingAdAccountIds = $this->save_ad_account_response(collect($resp), $fb_api_token, $bindBm);

//            $adAccountsCollection = Collect($resp);
//            $adAccountSourceIDs = $adAccountsCollection->pluck('account_id')->toArray();
            // Step 3: 找到新增和移除的 ad accounts
//            $newAdAccountIds = array_diff($adAccountSourceIDs, $currentAdAccountIds);
//            $removedAdAccountIds = array_diff($currentAdAccountIds, $adAccountSourceIDs);

            Log::debug("incomming ad account source ids: " . json_encode($incommingAdAccountIds));

            $newly_added_ids = array_diff($incommingAdAccountIds, $currentAdAccountIds);
            foreach ($newly_added_ids as $source_id) {
                $msg = "ad account: {$source_id} 关联到 token: {$fb_api_token['name']}";
                Telegram::sendMessage($msg);
            }

            $to_delete_ids = array_diff($currentAdAccountIds, $incommingAdAccountIds);
            foreach ($to_delete_ids as $source_id) {
                Log::debug("detach ad acc {$source_id} from token {$fb_api_token['name']}");

                $target = FbAdAccount::query()->firstWhere('source_id', $source_id);
                if ($target) {
                    $msg = "ad account: {$source_id} 解除关联 token: {$fb_api_token['name']}";
                    Telegram::sendMessage($msg);
                    $fb_api_token->adAccounts()->detach($target);
                }
            }

            Log::debug("new added ad acc ids: " . json_encode($newly_added_ids));
            Log::debug("to delete ad acc ids: " . json_encode($to_delete_ids));

        } else {
            Log::warning("api return failed");
        }
    }

    public function sync_pages(FbApiToken $fb_api_token, FbBm|null $fbBm): void {
        Log::info("--- start sync pages ---");

        $version = FbUtils::$API_Version;

        if (!$fb_api_token->bm_id) {
            return;
        }

        if ($fb_api_token->token_type === 2 || $fb_api_token->token_type === 3) {
            Log::debug("type and 3");
            $query = [
                'fields' => 'id,name,verification_status,is_published,fan_count,has_transitioned_to_new_page_experience,picture',
            ];
            $endpoint = "https://graph.facebook.com/{$version}/{$fb_api_token->bm_id}/client_pages";

        } else {
            $query = [
                'fields' => 'client_pages.limit(10){id,name,verification_status,is_published,fan_count,has_transitioned_to_new_page_experience,picture,access_token,permitted_tasks,business},owned_pages.limit(10){id,name,verification_status,is_published,fan_count,has_transitioned_to_new_page_experience,picture,access_token,permitted_tasks,business}',
            ];
            $endpoint = "https://graph.facebook.com/{$version}/{$fb_api_token->bm_id}";
        }

        $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $fb_api_token['token']);
//        Log::debug($resp->toJson());

        $fb_pages_parent = collect();
        $client_pages_parent = collect();
        $owned_pages = collect();

        if ($resp['success']) {
            Log::debug('success');
            if ($fb_api_token->token_type === 2 || $fb_api_token->token_type === 3) {
                Log::debug("app token or type 3 bm token");
                $fb_pages_parent = collect($resp);
            } elseif ($fb_api_token->token_type === 1) {
                $client_pages_parent = collect($resp['client_pages'] ?? []);
                $owned_pages = collect($resp['owned_pages'] ?? []);
            }

            $page_1 = $this->save_page_response($fb_pages_parent, $fb_api_token, $fbBm);
            $page_2 = $this->save_page_response($client_pages_parent, $fb_api_token, $fbBm);
            $page_3 = $this->save_page_response($owned_pages, $fb_api_token, $fbBm);

            $all_new_page_source_ids = array_merge($page_1, $page_2, $page_3);
            $all_old_page_source_ids = $fb_api_token->fbPages->pluck('source_id')->toArray();
//            Log::debug("all new: " . json_encode($newly_added_ids));

            $to_attached_ids = array_diff($all_new_page_source_ids, $all_old_page_source_ids);
            foreach ($to_attached_ids as $page_source_id) {
                $msg = "page: {$page_source_id} 关联到 token: {$fb_api_token['name']}";
                Log::debug($msg);
                $fbPage = FbPage::query()->where('source_id', $page_source_id)->first();
                // 检查 FbPage 是否存在
                if ($fbPage) {
                    // 上一步已经关了，并有 task 信息
                    //$fb_api_token->fbPages()->syncWithoutDetaching([$fbPage->id]);
                    Telegram::sendMessage($msg);
                } else {
                    // 处理未找到的情况
                    Telegram::sendMessage("未找到 page: {$page_source_id}");
                }
            }

            $to_delete_ids = array_diff($all_old_page_source_ids, $all_new_page_source_ids);
            foreach ($to_delete_ids as $page_source_id) {
                $msg = "page: {$page_source_id} 解除关联 token: {$fb_api_token['name']}";
                Telegram::sendMessage($msg);
                $page = FbPage::query()->where('source_id', $page_source_id)->first();
                if ($page) {
                    $fb_api_token->fbPages()->detach($page);
                }
            }

            Log::debug("new added page ids: " . json_encode($all_new_page_source_ids));
            Log::debug("to delete page ids: " . json_encode($to_delete_ids));

//            Log::debug($to_delete_ids);
        }
    }

    public function save_page_token($ownerId, mixed $tokens, mixed $token, string $ownerType, $fbPage): void
    {
        $existingKey = array_search($ownerId, array_column($tokens, 'owner_id'));
        if ($existingKey !== false) {
            // 如果已存在，则更新对应的 token
            $tokens[$existingKey]['token'] = $token;
        } else {
            // 如果不存在，则插入新的 token
            $tokens[] = [
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'token' => $token,
            ];
        }

        $fbPage->tokens = $tokens;
        $fbPage->save();
    }

    public function sync_pixel(FbApiToken $fb_api_token, ?FbBm $fbBm)
    {
        Log::info("--- start sync pixel ---");

        // https://developers.facebook.com/docs/marketing-api/reference/business/adspixels/
        // https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/business-pixel-sharing

        $version = FbUtils::$API_Version;

        if (!$fbBm) {
            // 如果不是 bm token, 是 app 的 token
        } else if ($fb_api_token->token_type === 1) {
            // 如果是 bm token
            $query = [
                'fields' => 'id,name,is_consolidated_container,is_created_by_business,is_unavailable,owner_business',
                'limit' => 10,
            ];
            $endpoint = "https://graph.facebook.com/$version/{$fb_api_token->bm_id}/adspixels";
//        $token = FbApiToken::query()->where('id', $this->token_id)->firstOrFail();

            $hasNext = true;
            $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $fb_api_token['token']);
            $adpixelsData = collect($resp->get('data', []));
            $paging = collect($resp->get('paging'));

            if ($resp['success']) {
                while ($hasNext) {
                    $adpixelsData->each(function ($adpixel) use ($fbBm) {
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
                        $fbPixel->fbBms()->syncWithoutDetaching([$fbBm->id]);
                    });
                    $hasNext = $paging->has('next');
                    if ($hasNext) {
//                        Log::debug("has next adpixels page");
                        $next = $paging->get('next');
                        $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fb_api_token['token']);
                        if ($resp['success']) {
                            $adpixelsData = collect($resp->get('data', []));
                            $paging = collect($resp->get('paging'));
                        }

                    }
                }
            }

        }
    }

    /**
     * @param Collection $fb_pages_parent
     * @param FbApiToken $fb_api_token
     * @return array
     * @throws \Exception
     */
    public function save_page_response(Collection $fb_pages_parent, FbApiToken $fb_api_token, FbBm|null $fbBm): array
    {
        Log::debug("fb pages parent");
//        Log::debug($fb_pages_parent);

        $fb_pages_data = collect($fb_pages_parent->get('data', []));
        $fb_pages_pagination = collect($fb_pages_parent->get('paging', []));

        $has_next = true;

        $all_new_page_source_ids = [];
        while ($has_next) {
            $fb_pages_data->each(function ($page) use ($fbBm, $fb_api_token, &$all_new_page_source_ids) {
//                Log::debug("page data:", $page);
                $fb_page = FbPage::query()->updateOrCreate(
                    [
                        'source_id' => $page['id']
                    ],
                    [
                        'name' => $page['name'],
                        'fan_count' => $page['fan_count'],
                        'picture' => $page['picture']['data']['url'],
                        'verification_status' => $page['verification_status'],
                        'access_token' => $page['access_token'] ?? ''
                    ]
                );
                $fb_page->save();

                if (isset($page['access_token']) && $page['access_token']) {
                    $tokens = $fbPage->tokens ?? [];
                    $ownerId = $fb_api_token->id;
                    $ownerType = 'bm';
                    $token = $page['access_token'];

                    $this->save_page_token($ownerId, $tokens, $token, $ownerType, $fb_page);
                }
                // 因为 Page 与 ApiToken 是多对多，这里如果 access_token 不为空，不能覆盖
//                    if (isset($page['access_token']) && $page['access_token']) {
//                        $fb_page->access_token = $page['access_token'];
//                        $fb_page->save();
//                    }

                $tasks = $page['permitted_tasks'] ?? [];
                $syncData = [
                    $fb_api_token['id'] => []
                ];
                if (!empty($tasks) && is_array($tasks)) {
                    $syncData[$fb_api_token['id']]['tasks'] = json_encode($tasks);
                }
                $fb_page->fbApiTokens()->syncWithoutDetaching($syncData);

                if ($fbBm) {
                    $syncData = [
                        $fbBm['id'] => []
                    ];
                    $syncData[$fbBm['id']]['is_owner'] = false;
                    $syncData[$fbBm['id']]['tasks'] = json_encode($tasks);
//                    Log::debug($tasks);
                    $permitted_role = FbUtils::getPageRoleFromPermittedTasks($tasks);
                    $syncData[$fbBm['id']]['role'] = $permitted_role;
//                    Log::debug($permitted_role);
                    if (isset($page['business'])) {
                        $business_source_id = $page['business']['id'];
                        if ($fbBm->source_id === $business_source_id) {
                            $syncData[$fbBm['id']]['is_owner'] = true;
                            $syncData[$fbBm['id']]['role'] = 'Admin';
                        }
                    }
                    $fb_page->fbBms()->syncWithoutDetaching($syncData);
                }

                // 不在原来的 source id 中
//                if (!in_array($page['id'], $all_old_page_source_ids)) {
//                    $all_new_page_source_ids[] = $page['id'];
//                }
                $all_new_page_source_ids[] = $page['id'];
            });

            $has_next = $fb_pages_pagination->has('next');
            if ($has_next) {
                Log::debug("has next page");
                $next = $fb_pages_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fb_api_token['token']);
//                    Log::debug($resp->toJson());
                // 这里要注意一下，这里的 parent 不一样了
                $fb_pages_parent = collect($resp);
                $fb_pages_data = collect($fb_pages_parent['data']);
                $fb_pages_pagination = collect($fb_pages_parent['paging']);
            }
        }

//            Log::debug($all_new_page_source_ids);

        return $all_new_page_source_ids;
    }

    public function save_ad_account_response(Collection $collection, FbApiToken $fbApiToken, FbBm|null $bindBm): array
    {
        $adAccountSourceIds = [];
        $adAccountDataCollection = collect($collection->get('data', []));
        $adAccountPaging = collect($collection->get('paging', []));

        Log::debug("save ad acc response");
        Log::debug($adAccountDataCollection);
        $hasNext = true;
        while ($hasNext) {
            $adAccountDataCollection->each(function ($adAccountData) use ($bindBm, $fbApiToken, &$adAccountSourceIds) {

                $original_balance = $adAccountData['balance'];
                $balance = number_format((float)$original_balance, 2, '.', '');;
                if ($original_balance !== '0') {
                    $balance = CurrencyUtils::convert($original_balance, $adAccountData['currency'], 'USD', 2);
                }

                $original_spend_cap = $adAccountData['spend_cap'];
                $spend_cap = $original_spend_cap;
                if ($original_spend_cap !== '0') {
                    $spend_cap = CurrencyUtils::convert($original_spend_cap, $adAccountData['currency'], 'USD', 2);
                }

                $original_amount_spent = $adAccountData['amount_spent'];
                $amount_spent = $original_amount_spent;
                if ($original_amount_spent !== '0') {
                    $amount_spent = CurrencyUtils::convert($original_amount_spent, $adAccountData['currency'], 'USD', 2);
                }

                // 这一步查询可以优化
                $old_ad_account = FbAdAccount::query()->firstWhere('source_id', $adAccountData['account_id']);
                if ($old_ad_account) {
                    $old_status = $old_ad_account->account_status;
                    $new_human_status = FbUtils::$FbAccountStatusMap[$adAccountData['account_status']];
                    if ($old_status != $new_human_status) {
                        $msg = "ad account: {$old_ad_account->name}({$old_ad_account->source_id}) status changed, old: {$old_status}, new: {$new_human_status}";
                        Telegram::sendMessage($msg);
                    }
                }

                $adAccount = FbAdAccount::query()->updateOrCreate(
                    [
                        'source_id' => $adAccountData['account_id']
                    ],
                    [
                        'account_status' => FbUtils::$FbAccountStatusMap[$adAccountData['account_status']] ?? "Unknown",
                        'account_status_code' => $adAccountData['account_status'],
                        'adspaymentcycle' => $adAccountData['adspaymentcycle'] ?? [],
                        'age' => $adAccountData['age'],
                        'total_spent' => $amount_spent,
                        'balance' => $balance,
                        'original_balance' => $original_balance,
                        'amount_spent' => $amount_spent,
                        'original_amount_spent' => $original_amount_spent,
                        'assigned_partners' => $adAccountData['assigned_partners'] ?? [],
                        'business' => $adAccountData['business'] ?? [],
                        'spend_cap' => $spend_cap,
                        'original_spend_cap' => $original_spend_cap,
                        'business_restriction_reason' => $adAccountData['business_restriction_reason'] ?? '',
                        'created_time' => Carbon::parse($adAccountData['created_time']),
                        'currency' => $adAccountData['currency'],
                        'current_unbilled_spend' => $adAccountData['current_unbilled_spend'] ?? [],
                        'disable_reason' => FbUtils::$FbAdAccountDisableReasonMap[$adAccountData['disable_reason']] ?? 'Unknown',
                        'disable_reason_code' => $adAccountData['disable_reason'],
                        'max_billing_threshold' => $adAccountData['max_billing_threshold'] ?? [],
                        'name' => $adAccountData['name'],
                        'owner' => $adAccountData['owner'],
                        'timezone_id' => $adAccountData['timezone_id'],
                        'timezone_name' => $adAccountData['timezone_name'],
                        'is_prepay_account' => $adAccountData['is_prepay_account'],
                    ]
                );
                if (isset($adAccountData['funding_source_details'])) {
                    // 有出现这样的 response
//                    "funding_source_details": {
//                                "coupons": [
//                            {
//                                "amount": 0,
//                                "currency": "RON",
//                                "display_amount": "RON 0.00",
//                                "expiration": "2025-09-19T10:12:01+0000",
//                                "start_date": "2025-03-20T22:12:01+0000",
//                                "coupon_id": "9782215225226029",
//                                "original_amount": 3824,
//                                "original_display_amount": "RON 38.24"
//                            }
//                        ]
//                    }
                    if (isset($adAccountData['funding_source_details']['type'])) {
                        $adAccount['funding_type'] = $adAccountData['funding_source_details']['type'];
                        $adAccount['default_funding'] = $adAccountData['funding_source_details']['display_string'] ?? '';
                        $adAccount->save();
                    }

                }
                Log::debug("add fb ad account id: {$adAccount->id}, source_id: {$adAccountData['account_id']}");

                $adAccountSourceIds[] = $adAccountData['account_id'];

                // 关联到 fb_api_token
                $adAccount->apiTokens()->syncWithoutDetaching([
                    $fbApiToken['id'] => [
                        'tasks' => json_encode($adAccountData['permitted_tasks']?? []),
//                        'id' => Str::ulid(),
//                        'created_at' => $now,
//                        'updated_at' => $now,
                    ]
                ]);

                // 如果有 Business, 设定关联，且是owner
                if (isset($adAccountData['business'])) {
                    $bm = $adAccountData['business'];
                    if (isset($bm['timezone_id'])) {
                        $timezone_id = $bm['timezone_id'];
                    } else {
                        $timezone_id = null;
                    }
                    $fbBM = FbBm::query()->updateOrCreate(
                        [
                            'source_id' => $bm['id']
                        ],
                        [
                            'name' => $bm['name'],
                            'timezone_id' => $timezone_id,
                            'verification_status' => $bm['verification_status']
                        ]
                    );
                    Log::debug("fb bm sync with ad account, bm id: {$fbBM->id}, ad account id: {$adAccount->id}");
                    $relation = $adAccountData['owner'] == $bm['id'] ? BusinessUserRelation::Owener->value : BusinessUserRelation::Partner->value;
                    if ($relation === BusinessUserRelation::Owener->value) {
                        $role = BusinessAdAccountRole::Admin->value;
                    } else {
                        $role = FbUtils::getBmAdAccountRoleByTasks($adAccountData['permitted_tasks'] ?? []);
                    }
                    $pivotData = [
                        'relation' => $relation,
                        'source_id' => $adAccountData['account_id'],
                        'tasks' =>  json_encode($adAccountData['permitted_tasks']?? []),
                        'role' => $role,
                    ];
                    $result = $adAccount->fbBms()->syncWithoutDetaching([
                        $fbBM->id => $pivotData
                    ]);
                    Log::debug("After sync, result: " . json_encode($result));
                }

                if ($bindBm) {
//                    Log::debug($adAccountData['permitted_tasks']);
//                    Log::debug(FbUtils::getBmAdAccountRoleByTasks($adAccountData['permitted_tasks'] ?? []));
                    $relation = $adAccountData['owner'] == $bindBm['source_id'] ? BusinessUserRelation::Owener->value : BusinessUserRelation::Partner->value;
                    if ($relation === BusinessUserRelation::Owener->value) {
                        $role = BusinessAdAccountRole::Admin->value;
                    } else {
                        $role = FbUtils::getBmAdAccountRoleByTasks($adAccountData['permitted_tasks'] ?? []);
                    }
//                    Log::debug("relation: {$relation}, role: {$role}");
                    $pivotData = [
                        'relation' => $relation,
                        'source_id' => $adAccountData['account_id'],
                        'tasks' =>  json_encode($adAccountData['permitted_tasks']?? []),
                        'role' => $role
                    ];
//                    Log::debug("have bind bm: {$bindBm->id}");
//                    Log::debug($pivotData);
                    $adAccount->fbBms()->syncWithoutDetaching([
                        $bindBm->id => $pivotData
                    ]);
                } else {
                    Log::debug("no bm");
                }

                // 关联 pixel
                $adpixels = collect(collect($adAccountData)->get('adspixels'));
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
            });

            $hasNext = $adAccountPaging->has('next');
            if ($hasNext) {
                Log::debug("has next ad account");
                $next = $adAccountPaging->get('next');
//                    Log::debug($next);
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
//                    Log::debug($resp->toJson());
                // 这里要注意一下，这里的 parent 不一样了
                $adAccountsCollection = collect($resp);
                $adAccountDataCollection = collect($adAccountsCollection['data']);
                $adAccountPaging = collect($adAccountsCollection['paging']);
            }

        }
        return $adAccountSourceIds;
    }

    public function sync_users(FbApiToken $fb_api_token, ?FbBm $fbBm)
    {
        Log::info("--- start sync bm users ---");

        if (!$fbBm) {
            return;
        }

        // https://developers.facebook.com/docs/marketing-api/reference/business#edges
        // 获取 business users 和 system users
        // 未认证的 app, 获取到的数据不全，目前无解，但 system user list 是全的

        $version = FbUtils::$API_Version;
        $query = [
            'fields' => 'id,name,business_users.limit(5){email,first_name,last_name,name,id,role,two_fac_status,finance_permission,assigned_ad_accounts.limit(10){id,account_id,tasks}, assigned_pages.limit(10){id,name,tasks}},system_users.limit(2){email,expiry_time,first_name,last_name,name,id,role,two_fac_status,finance_permission,assigned_ad_accounts{id,account_id,tasks}, assigned_pages.limit(10){id,name,tasks}}',
        ];
        $endpoint = "https://graph.facebook.com/$version/{$fb_api_token->bm_id}";

        $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $fb_api_token['token']);
        $business_user_collect = collect();
        $system_user_collect = collect();

        if ($resp['success']) {
            $business_user_collect = collect($resp['business_users'] ?? []);
            $system_user_collect = collect($resp['system_users'] ?? []);

            $current_user_list = $fbBm->fbBusinessUsers()->pluck('fb_business_users.id')->toArray();

            $incoming_business_user_ids = $this->save_bm_user_list($business_user_collect, $fb_api_token, 'business_user', $fbBm);
            $incoming_system_user_ids = $this->save_bm_user_list($system_user_collect, $fb_api_token, 'system_user', $fbBm);
            $incoming_user_ids = array_merge($incoming_business_user_ids, $incoming_system_user_ids);

            $user_to_delete = array_diff($current_user_list, $incoming_user_ids);
            $user_to_add = array_diff($incoming_user_ids, $current_user_list);
            Log::debug("delete: " . json_encode($user_to_delete));
            Log::debug("add: " . json_encode($user_to_add));

            foreach ($user_to_add as $id) {
                $user = FbBusinessUser::query()->firstWhere('id', $id);
                if ($user) {
                    $msg = "User: {$user->name} add to Bm: {$fbBm->source_id}";
                    Log::info($msg);
                    Telegram::sendMessage($msg);
                }
            }

            foreach ($user_to_delete as $id) {
                $user = FbBusinessUser::query()->firstWhere('id', $id);
                $msg = "!!! User: {$user->name} removed from Bm: {$fbBm->source_id}";
                $user->fbBm()->dissociate();
                $user->save();
                Log::warning($msg);
                Telegram::sendMessage($msg);
            }

        } else {
            Log::debug("failed to pull bm user list");
        }
    }

    private function save_bm_user_list(Collection $collection, FbApiToken $fbApiToken, string $user_type, FbBm $fbBm)
    {
//        Log::debug($collection);
        $user_data = collect($collection->get('data', []));
        $user_data_pagination = collect($collection->get('paging', []));

        $incoming_ids = [];

        $has_next = true;
        while ($has_next) {
            $user_data->each(function ($user) use ($fbBm, $user_type, $fbApiToken, &$incoming_ids) {
               $businessUser = FbBusinessUser::query()->updateOrCreate(
                   [
                       'source_id' => $user['id']
                   ],
                   [
                       'fb_bm_id' => FbBm::query()->firstWhere('source_id', $fbApiToken->bm_id)->id,
                       'name' => $user['name'],
                       'first_name' => $user['first_name'] ?? '',
                       'last_name' => $user['last_name'] ?? '',
                       'role' => $user['role'] ?? '',
                       'two_fac_status' => $user['two_fac_status'] ?? '',
                       'email' => $user['email'] ?? '',
                       'finance_permission' => $user['finance_permission'] ?? '',
                       'user_type' => $user_type,
                   ]
               );

               if (isset($user['assigned_ad_accounts'])) {
                   $assignedAdAccCollection = collect($user['assigned_ad_accounts']);
                   $this->save_business_user_ad_account($assignedAdAccCollection, $fbBm, $businessUser, $fbApiToken);
               } else {
                   // 直接检查是否有关联广告账户，全部移除
                   $current_acc_ids = $businessUser->fbAdAccounts()->pluck('fb_ad_accounts.id')->toArray();
                   foreach ($current_acc_ids as $id) {
                       $acc = FbAdAccount::query()->firstWhere('id', $id);
                       $msg = "!!Bm user removed from ad account\r\n bm id: {$fbBm->source_id}\r\n user name: {$businessUser->name}\r\n ad id: {$acc->source_id}";
                       $businessUser->fbAdAccounts()->detach($id);
                       Log::warning($msg);
                       Telegram::sendMessage($msg);
                   }
               }

               if (isset($user['assigned_pages'])) {
                   $assignedPagesCollection = collect($user['assigned_pages']);
                   $this->save_business_user_pages($assignedPagesCollection, $fbBm, $businessUser, $fbApiToken);
               } else {
                   $current_page_ids = $businessUser->fbPages()->pluck('fb_pages.id')->toArray();
                   foreach ($current_page_ids as $id) {
                       $page = FbPage::query()->firstWhere('id', $id);
                       $msg = "!!Bm user removed access to page\r\n bm id: {$fbBm->source_id}\r\nuser name: {$businessUser->name}\r\n page id: {$page->source_id}";
                       $businessUser->fbPages()->detach($id);
                       Log::info($msg);
                       Telegram::sendMessage($msg);
                   }
               }
               $incoming_ids[] = $businessUser['id'];
            });

            $has_next = $user_data_pagination->has('next');
            $next = $user_data_pagination->get('next');
            if ($has_next) {
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
                if ($resp['success']) {
                    $user_data = collect($resp['data']);
                    $user_data_pagination = collect($resp['paging']);
                } else {
                    $has_next = false;
                    Log::warning('failed to get bm user list data');
                }
            }
        }

        return $incoming_ids;
    }

    private function save_business_user_ad_account(Collection $collection, FbBm $fbBm, FbBusinessUser $businessUser, FbApiToken $fbApiToken)
    {
        Log::debug("business user ad account");
        Log::debug($collection);
        $ad_acc_data = collect($collection->get('data', []));
        $ad_acc_data_pagination = collect($collection->get('paging', []));

        $has_next = true;

        $source_ids = $ad_acc_data->pluck('account_id');
        $ad_acc_query = FbAdAccount::query()->whereIn('source_id', $source_ids)->get();

        $current_acc_ids = $businessUser->fbAdAccounts()->pluck('fb_ad_accounts.id')->toArray();
        $incoming_ac_ids = [];

        $success = true;
        while ($has_next) {
            $ad_acc_data->each(function ($data) use ($businessUser, $fbApiToken, $ad_acc_query, &$incoming_ac_ids) {
                $source_id = $data['account_id'];
                $ad_acc = $ad_acc_query->firstWhere('source_id', $source_id);
                if ($ad_acc) {
                    # tasks, https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/ad-accounts/
                    $tasks = $data['tasks'];
                    $role = FbUtils::getBusinessUserAdAccountRole($tasks);
                    $ad_acc->fbBusinessUsers()->syncWithoutDetaching([
                        $businessUser->id => ['role' => $role]
                    ]);
                    $incoming_ac_ids[] = $ad_acc->id;
                }
            });
            $has_next = $ad_acc_data_pagination->has('next');
            if ($has_next) {
                Log::debug("user acc next page");
                $next = $ad_acc_data_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
                if ($resp['success']) {
                    $ad_acc_data_parent = collect($resp);
                    $ad_acc_data = collect($ad_acc_data_parent['data']);
                    $ad_acc_data_pagination = collect($ad_acc_data_parent['paging']);
                } else {
                    $has_next = false;
                    $success = false; // 请求有失败，在后面不对比 id，不做增加和移除的对比
                    Log::warning('user acc req failed');
                    Telegram::sendMessage('user acc req failed');
                }
            }
        }

        if ($success) {
            $acc_to_remove = array_diff($current_acc_ids, $incoming_ac_ids);
            $acc_to_add = array_diff($incoming_ac_ids, $current_acc_ids);
            foreach ($acc_to_add as $id) {
                $acc = FbAdAccount::query()->firstWhere('id', $id);
                if ($acc) {
                    $msg = "Bm user grant access to ad account:\r\n bm id: {$fbBm->source_id}\r\n user name: {$businessUser->name}\r\n ad acc: {$acc->source_id}";
                    Log::info($msg);
                    Telegram::sendMessage($msg);
                }
            }

            foreach ($acc_to_remove as $id) {
                $acc = FbAdAccount::query()->firstWhere('id', $id);
                $msg = "Bm({$fbBm->source_id}) user({$businessUser->name}) remove access to acc: {$acc->source_id}";
                $businessUser->fbAdAccounts()->detach($id);
                Log::warning($msg);
                Telegram::sendMessage($msg);
            }
        }

    }

    private function save_business_user_pages(Collection $collection, FbBm $fbBm, FbBusinessUser $businessUser, FbApiToken $fbApiToken)
    {
        Log::debug("business user pages");

//        https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/pages/

        $page_data = collect($collection->get('data', []));

        $page_data_pagination = collect($collection->get('paging', []));

        $has_next = true;
        $source_ids = $page_data->pluck('id');
        $existed_page = FbPage::query()->whereIn('source_id', $source_ids)->get();

        $current_page_ids = $businessUser->fbPages()->pluck('fb_pages.id')->toArray();
        $incoming_page_ids = [];

        $success = true;
        while ($has_next) {
            $page_data->each(function ($data) use ($businessUser, $existed_page, &$incoming_page_ids) {
                $source_id = $data['id'];
                $page = $existed_page->firstWhere('source_id', $source_id);
                if ($page) {
                    $tasks = $data['tasks'];
                    $role = FbUtils::getUserPageRole($tasks);
                    $page->fbBusinessUsers()->syncWithoutDetaching([
                        $businessUser->id => [
                            'role' => $role,
                            'tasks' => json_encode($tasks)
                        ]
                    ]);
                    $incoming_page_ids[] = $page->id;
                }
            });

            $has_next = $page_data_pagination->has('next');
            if ($has_next) {
                Log::debug('bm user page next page');
                $next = $page_data_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
                if ($resp['success']) {
                    $page_data_parent = collect($resp);
                    $page_data = collect($page_data_parent->get('data'));
                    $page_data_pagination = collect($page_data_parent->get('paging')); // 有可能 resp 为 {data:[]}这样的空
                } else {
                    $has_next = false;
                    $success = false; // 请求失败，后面不再对比，检查增加和删除
                    Log::warning('bm user page next page req failed');
                    Telegram::sendMessage('bm user page next page req failed');
                }
            }
        }

        if ($success) {
            $page_to_remove = array_diff($current_page_ids, $incoming_page_ids);
            $page_to_add = array_diff($incoming_page_ids, $current_page_ids);
            foreach ($page_to_add as $id) {
                $page = FbPage::query()->firstWhere('id', $id);
                if ($page) {
                    $msg = "Bm user assign access to page\r\nbm id: {$fbBm->source_id}\r\nuser name: {$businessUser->name}\r\npage id: {$page->source_id}";
                    Log::info($msg);
                    Telegram::sendMessage($msg);
                }
            }

            foreach ($page_to_remove as $id) {
                $page = FbPage::query()->firstWhere('id', $id);
                $msg = "!!Bm user removed access to page\r\n bm id: {$fbBm->source_id}\r\nuser name: {$businessUser->name}\r\n page id: {$page->source_id}";
                $businessUser->fbPages()->detach($id);
                Log::info($msg);
                Telegram::sendMessage($msg);
            }
        }

    }

    private function sync_catalogs(FbApiToken $fbApiToken, ?FbBm $fbBm) {
        Log::info("--- start sync bm catalogs ---");

        if (!$fbBm) {
            return;
        }
        $currentIds = $fbBm->catalogs()->pluck('fb_catalogs.id')->toArray();

        // https://developers.facebook.com/docs/marketing-api/business-asset-management/guides/catalog/
        $version = FbUtils::$API_Version;
        $query = [
            'fields' => "owned_product_catalogs{id,external_event_sources{id},agencies{id,permitted_tasks},name,business{id},assigned_users.business({$fbBm->source_id}),products{id,currency,name,description,url,image_url,retailer_id,price},product_sets{id,name,filter}},client_product_catalogs{id,external_event_sources{id},agencies{id,permitted_tasks},name,business{id},assigned_users.business({$fbBm->source_id}),products{id,currency,name,description,url,image_url,retailer_id,price},product_sets{id,name,filter}}",
        ];
        $endpoint = "https://graph.facebook.com/{$version}/{$fbBm->source_id}";

        $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $fbApiToken->token);

        if ($resp['success']) {
            $ownedCatalogResp = collect($resp->get('owned_product_catalogs', []));
            $clientCatalogResp = collect($resp->get('client_product_catalogs', []));

            $ownedCatalogIds = $this->save_catalog_response(collect($ownedCatalogResp), $fbApiToken, $fbBm);
            $clientCatalogIds = $this->save_catalog_response(collect($clientCatalogResp), $fbApiToken, $fbBm);

            $incomingIds = array_merge($ownedCatalogIds, $clientCatalogIds);
            $newly_added_ids = array_diff($incomingIds, $currentIds);
            $to_delete_ids = array_diff($currentIds, $incomingIds);

//            Log::debug("incoming catalogs: " . json_encode($incomingIds));
//            Log::debug("current catalogs: " . json_encode($currentIds));
//            Log::debug("new catalogs: " . json_encode($newly_added_ids));
//            Log::debug("deleted catalogs: " . json_encode($to_delete_ids));

            $addedItems = FbCatalog::query()->whereIn('id', $newly_added_ids)->get();

            foreach ($newly_added_ids as $id) {
                $item = $addedItems->firstWhere('id', $id);
                $msg = "catalog 关联到 BM:\r\n BM Name: {$fbBm->name}\r\n BM id: {$fbBm->source_id}\r\n Catalog id: {$item->source_id}\r\n Catalog name: {$item->name}";
                Telegram::sendMessage($msg);
            }

            $deletedItems = FbCatalog::query()->whereIn('id', $to_delete_ids)->get();
            foreach ($to_delete_ids as $id) {
                $item = $deletedItems->firstWhere('id', $id);
                $fbBm->catalogs()->detach($item);
                $msg = "catalog 取消关联 BM:\r\n BM Name: {$fbBm->name}\r\n BM id: {$fbBm->source_id}\r\n Catalog id: {$item->source_id}\r\n Catalog name: {$item->name}";
                Log::info($msg);
                Telegram::sendMessage($msg);
            }

        } else {
            Log::warning("get bm catalog failed");
            Telegram::sendMessage("failed to get catalog");
        }


    }

    private function save_catalog_response(Collection $collection, FbApiToken $fbApiToken, FbBm $fbBm)
    {

        $catalog_data = collect($collection->get('data', []));
        $catalog_data_pagination = collect($collection->get('paging', []));

        $hasNext = true;
        $success = true;
        // catalog 与 FB BM, catalog 与 business user
        $incoming_ids = [];

        while ($hasNext) {
            $catalog_data->each(function ($catalog_data) use ($fbApiToken, $fbBm, &$incoming_ids) {
                $source_id = $catalog_data['id'];
                $catalog = FbCatalog::query()->updateOrCreate(
                    [
                        'source_id' => $source_id
                    ],
                    [
                        'name' => $catalog_data['name']
                    ]
                );
                $incoming_ids[] = $catalog->id;

                $agencies_data = collect($catalog_data['agencies'] ?? []);
                $agencies = $this->save_catalog_agencies($agencies_data, $fbApiToken);

                $owner_business = $catalog_data['business']['id'];
                $is_owner = $owner_business == $fbBm->source_id;
                $relation = $is_owner ? EnumCatalogRelation::Owner->value : EnumCatalogRelation::Partenr->value;
                $role = $is_owner ? EnumCatalogRole::Admin->value : EnumCatalogRole::GeneralUser->value;
                $agency_tasks = $agencies->get($fbBm->source_id);
                if ($agency_tasks) {
                    $role = FbUtils::getCatalogRoleByTasks($agency_tasks);
                }
                if ($role === EnumCatalogRole::Admin->value) {
                    $tasks = EnumCatalogTasks::Admin->tasks();
                } else {
                    $tasks = EnumCatalogTasks::GeneralUser->tasks();
                }
                $catalog->fbBms()->syncWithoutDetaching([
                    $fbBm->id => [
                        'relation' => $relation,
                        'role' => $role,
                        'tasks' => json_encode($tasks)
                    ]
                ]);

                // assigned_users

                $assigned_users_parent = collect($catalog_data['assigned_users'] ?? []);
                $this->save_catalog_users($assigned_users_parent, $fbApiToken, $fbBm, $catalog);

                // products
                $products = collect($catalog_data['products'] ?? []);
                $this->save_catalog_products($products, $fbApiToken, $fbBm, $catalog);
                // product_sets
                $product_sets = collect($catalog_data['product_sets'] ?? []);
                $this->save_catalog_product_sets($product_sets, $fbApiToken, $fbBm, $catalog);

                // pixels
                $pixels_parent = collect($catalog_data['external_event_sources'] ?? []);
                Log::debug($pixels_parent);
                $pixels = collect($pixels_parent->get('data', []));
                $pixel_source_ids = $pixels->pluck('id');
                Log::debug($pixel_source_ids);
                $existing_pixels = FbPixel::query()->whereIn('pixel', $pixel_source_ids)->get();
                $connected_pixel_ids = [];
                foreach ($pixel_source_ids as $pixel_source_id) {
                    $pixel = $existing_pixels->firstWhere('pixel', $pixel_source_id);
                    if ($pixel) {
                        $connected_pixel_ids[] = $pixel->id;
                        Log::debug("pixel: {$pixel->source_id}");
                    }
                }
                $catalog->pixels()->sync($connected_pixel_ids);
            });
            $hasNext = $catalog_data_pagination->has('next');
            if ($hasNext) {
                $next = $catalog_data_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
                if ($resp['success']) {
                    $catalog_data_parent = collect($resp);
                    $catalog_data = collect($catalog_data_parent->get('data'));
                    $catalog_data_pagination = collect($catalog_data_parent->get('paging'));
                } else {
                    $hasNext = false;
                    $success = false;
                    Log::warning('bm catalog next page req failed');
                    Telegram::sendMessage('bm catalog next page req failed');
                }
            }
        }

        if ($success) {

        }



        // product, product  与 catalog

        // product set, 与 product

        return $incoming_ids;

    }

    private function save_catalog_users(Collection $collection, FbApiToken $fbApiToken, FbBm $fbBm, FbCatalog $catalog)
    {
        $users_data = collect($collection->get('data', []));
        $users_data_pagination = collect($collection->get('paging', []));

        $hasNext = true;
        $success = true;

        while ($hasNext) {
            $users_data->each(function ($user) use ($fbBm, $catalog) {
                $source_id = $user['id'];
                $business_user = $fbBm->fbBusinessUsers()->firstWhere('source_id', $source_id);
                if ($business_user) {
                    $tasks = $user['tasks'];
                    $role = FbUtils::getCatalogRoleByTasks($tasks);
                    $catalog->businessUsers()->syncWithoutDetaching([
                        $business_user->id => [
                            'role' => $role,
                            'tasks' => json_encode($tasks)
                        ]
                    ]);
                } else {
                    $msg = "business user not saved in system, source_id: {$source_id}, name: {$user['name']}";
                    Log::warning($msg);
//                    Telegram::sendMessage($msg);
                }
            });

            $hasNext = $users_data_pagination->has('next');
            if ($hasNext) {
                $next = $users_data_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
                if ($resp['success']) {
                    $users_data_parent = collect($resp);
                    $users_data = collect($users_data_parent->get('data'));
                    $users_data_pagination = collect($users_data_parent->get('paging'));
                } else {
                    $hasNext = false;
                    $success = false;
                    Log::warning('bm catalog users next page req failed');
                    Telegram::sendMessage('bm catalog users next page req failed');
                }
            }

        }
    }

    private function save_catalog_products(Collection $collection, FbApiToken $fbApiToken, FbBm $fbBm, FbCatalog $catalog)
    {
        $products_data = collect($collection->get('data', []));
        $products_data_pagination = collect($collection->get('paging', []));

        $hasNext = true;
        $success = true;
        Log::debug("products catalog id: {$catalog->id}");

        while ($hasNext) {
            $products_data->each(function ($product) use ($fbBm, $catalog) {
                $source_id = $product['id'];
//                Log::debug($product);
                FbCatalogProduct::query()->updateOrCreate(
                    [
                        'source_id' => $source_id
                    ],
                    [
                        'fb_catalog_id' => $catalog->id,
                        'name' => $product['name'] ?? 'no-name',
                        'description' => $product['description'] ?? 'no-desc',
                        'url' => $product['url'] ?? 'no-url',
                        'image_url' => $product['image_url'] ?? 'no-image-url',
                        'retailer_id' => $product['retailer_id'],
                        'currency' => $product['currency'] ?? 'USD',
                        'price' => $product['price'] ?? '91'
                    ]
                );
            });

            $hasNext = $products_data_pagination->has('next');
            if ($hasNext) {
                $next = $products_data_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
                if ($resp['success']) {
                    $products_data_parent = collect($resp);
                    $products_data = collect($products_data_parent->get('data'));
                    $products_data_pagination = collect($products_data_parent->get('paging'));
                } else {
                    $hasNext = false;
                    $success = false;
                    Log::warning('bm catalog product next page req failed');
                    Telegram::sendMessage('bm catalog product next page req failed');
                }
            }

        }
    }

    private function save_catalog_product_sets(Collection $collection, FbApiToken $fbApiToken, FbBm $fbBm, FbCatalog $catalog)
    {
        $product_sets_data = collect($collection->get('data', []));
        $product_sets_data_pagination = collect($collection->get('paging', []));

        $hasNext = true;
        $success = true;

        Log::debug("product sets catalog id: {$catalog->id}");


        while ($hasNext) {
            $product_sets_data->each(function ($product_set) use ($fbBm, $catalog) {
                $source_id = $product_set['id'];

                if (isset($product_set['filter'])) {
                    $filter =  json_decode($product_set['filter'], true);
                } else {
                    $filter = [];
                }
                $catalog_product_set = FbCatalogProductSet::query()->updateOrCreate(
                    [
                        'source_id' => $source_id
                    ],
                    [
                        'fb_catalog_id' => $catalog->id,
                        'name' => $product_set['name'],
                        'filter' => $filter,
                    ]
                );

                if ($filter) {
                    $product_query = FbCatalogProduct::query();
                    if (isset($filter['retailer_id'])) {
                        if (isset($filter['retailer_id']['is_any'])) {
                            $retailerIds = $filter['retailer_id']['is_any'];
                            $product_query->whereIn('retailer_id', $retailerIds);
                        } elseif (isset($filter['retailer_id']['eq'])) {
                            $retailerId = $filter['retailer_id']['eq'];
                            $product_query->where('retailer_id', $retailerId);
                        }
                    }

                    if (isset($filter['product_item_id'])) {
                        if (isset($filter['product_item_id']['is_any'])) {
                            $productItemIds = $filter['product_item_id']['is_any'];
                            $product_query->whereIn('source_id', $productItemIds);
                        } elseif (isset($filter['product_item_id']['eq'])) {
                            $productItemId = $filter['product_item_id']['eq'];
                            $product_query->where('source_id', $productItemId);
                        }
                    }

                    $product_ids = $product_query->pluck('id');
//                    Log::debug("pids: " . json_encode($product_ids));

                    $catalog_product_set->products()->sync($product_query->pluck('id'));
                }
            });

            $hasNext = $product_sets_data_pagination->has('next');
            if ($hasNext) {
                $next = $product_sets_data_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
                if ($resp['success']) {
                    $product_sets_data_parent = collect($resp);
                    $product_sets_data = collect($product_sets_data_parent->get('data'));
                    $product_sets_data_pagination = collect($product_sets_data_parent->get('paging'));
                } else {
                    $hasNext = false;
                    $success = false;
                    Log::warning('bm catalog product sets next page req failed');
                    Telegram::sendMessage('bm catalog product sets next page req failed');
                }
            }

        }
    }

    private function save_catalog_agencies(Collection $collection, FbApiToken $fbApiToken)
    {
        $agencies_data = collect($collection->get('data', []));
        $agencies_data_pagination = collect($collection->get('paging', []));

        $hasNext = true;
        $success = true;

        $agencies = collect();

        while ($hasNext) {
            $agencies_data->each(function ($bm) use (&$agencies) {
                $source_id = $bm['id'];
                $agencies = $agencies->put($source_id, $bm['permitted_tasks']);
            });

            $hasNext = $agencies_data_pagination->has('next');
            if ($hasNext) {
                $next = $agencies_data_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, null, 'GET', '', '', $fbApiToken['token']);
                if ($resp['success']) {
                    $agencies_data_parent = collect($resp);
                    $agencies_data = collect($agencies_data_parent->get('data'));
                    $agencies_data_pagination = collect($agencies_data_parent->get('paging'));
                } else {
                    $hasNext = false;
                    $success = false;
                    Log::warning('bm catalog agencies next page req failed');
                    Telegram::sendMessage('bm catalog agencies next page req failed');
                }
            }
        }

        return $agencies;
    }

    /**
     * 同步 Business Manager 的 Apps（只在 token_type 为 1 时调用）
     */
    private function sync_apps(FbApiToken $fbApiToken, ?FbBm $fbBm) {
        Log::info("--- start sync bm apps ---");

        if (!$fbBm) {
            return;
        }

        $currentIds = $fbBm->fbApps()->pluck('fb_apps.id')->toArray();

        $version = FbUtils::$API_Version;
        $query = [
            'fields' => "owned_apps.limit(5){id,name},client_apps.limit(5){id,name,link}",
        ];
        $endpoint = "https://graph.facebook.com/{$version}/{$fbBm->source_id}";

        $resp = FbUtils::makeRequest(null, $endpoint, $query, 'GET', '', '', $fbApiToken->token);

        if ($resp['success']) {
            $ownedAppsResp = collect($resp->get('owned_apps', []));
            $clientAppsResp = collect($resp->get('client_apps', []));

            $ownedAppIds = $this->save_app_response($ownedAppsResp, $fbApiToken, $fbBm, 'owner');
            $clientAppIds = $this->save_app_response($clientAppsResp, $fbApiToken, $fbBm, 'client');

            $incomingIds = array_merge($ownedAppIds, $clientAppIds);
            $newly_added_ids = array_diff($incomingIds, $currentIds);
            $to_delete_ids = array_diff($currentIds, $incomingIds);

            // 通知新增的 Apps
            $addedItems = FbApp::query()->whereIn('id', $newly_added_ids)->get();
            foreach ($newly_added_ids as $id) {
                $item = $addedItems->firstWhere('id', $id);
                $msg = "App 关联到 BM:\r\n BM Name: {$fbBm->name}\r\n BM id: {$fbBm->source_id}\r\n App id: {$item->source_id}\r\n App name: {$item->name}";
                Telegram::sendMessage($msg);
            }

            // 处理删除的 Apps
            $deletedItems = FbApp::query()->whereIn('id', $to_delete_ids)->get();
            foreach ($to_delete_ids as $id) {
                $item = $deletedItems->firstWhere('id', $id);
                $fbBm->fbApps()->detach($item);
                $msg = "App 取消关联 BM:\r\n BM Name: {$fbBm->name}\r\n BM id: {$fbBm->source_id}\r\n App id: {$item->source_id}\r\n App name: {$item->name}";
                Log::info($msg);
                Telegram::sendMessage($msg);
            }

        } else {
            Log::warning("get bm apps failed");
            Telegram::sendMessage("failed to get apps");
        }
    }

    /**
     * 保存 App 响应数据并处理分页
     */
    private function save_app_response(Collection $collection, FbApiToken $fbApiToken, FbBm $fbBm, string $relation)
    {
        $app_data = collect($collection->get('data', []));
        $app_data_pagination = collect($collection->get('paging', []));

        $hasNext = true;
        $incoming_ids = [];

        while ($hasNext) {
            $app_data->each(function ($app_data) use ($fbApiToken, $fbBm, $relation, &$incoming_ids) {
                $source_id = $app_data['id'];
                $app = FbApp::query()->updateOrCreate(
                    [
                        'source_id' => $source_id
                    ],
                    [
                        'name' => $app_data['name'],
                        'created_time' => now(), // Facebook API 可能不返回创建时间
                    ]
                );
                $incoming_ids[] = $app->id;

                // 建立 BM 与 App 的关联关系
                $app->fbBms()->syncWithoutDetaching([
                    $fbBm->id => [
                        'relation' => $relation,
                    ]
                ]);
            });

            $hasNext = $app_data_pagination->has('next');
            if ($hasNext) {
                $next = $app_data_pagination->get('next');
                $resp = FbUtils::makeRequest(null, $next, [], 'GET', '', '', $fbApiToken->token);

                if ($resp['success']) {
                    $app_data_parent = collect($resp);
                    $app_data = collect($app_data_parent->get('data', []));
                    $app_data_pagination = collect($app_data_parent->get('paging', []));
                } else {
                    $hasNext = false;
                    Log::warning('bm apps next page req failed');
                    Telegram::sendMessage('bm apps next page req failed');
                }
            }
        }

        return $incoming_ids;
    }

}
