<?php

namespace App\Jobs;

use App\Http\Controllers\FbHelper;
use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\FbBm;
use App\Models\FbBusinessUser;
use App\Models\FbPage;
use App\Models\FbPixel;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Uid\Ulid;

class FacebookSyncResources implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private $fbSourceID;
    private $fbAccountID;

    private $fbAccount;
    private mixed $fbAdAccountID;
    private mixed $date_start;
    private mixed $date_stop;
    private mixed $next;

    /**
     * Create a new job instance.
     * 除了第一个参数，后面几个参数是给自动化job任务用的
     */
    public function __construct($fbAccountID, $fbAdAccountID=null, $date_start=null, $date_stop=null,$next=false)
    {
        $this->fbAccountID = $fbAccountID;
        $this->fbAccount = FbAccount::query()->where('id', $fbAccountID)->first();
        $this->fbSourceID = $this->fbAccount->source_id;
        $this->fbAdAccountID = $fbAdAccountID;
        $this->date_start = $date_start;
        $this->date_stop = $date_stop;
        $this->next = $next;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("sync res: {$this->fbAccountID}");

        $endpoint = "https://graph.facebook.com/v23.0/me";
        $fields = 'id,name,business_users{id, name, expiry_time, business{id,name, created_time,timezone_id,two_factor_type,verification_status,client_ad_accounts{account_id,name,id,adtrust_dsl,account_status,disable_reason,balance,amount_spent,business_restriction_reason,average_daily_campaign_budget,created_time,is_new_advertiser,timezone_name,timezone_id,currency,is_tier_restricted,age,max_billing_threshold,current_unbilled_spend,adspaymentcycle,spend_cap,owner,is_prepay_account,funding_source_details},business_users{email,expiry_time,first_name,last_name,name,id,role,two_fac_status,finance_permission,assigned_ad_accounts},is_disabled_for_integrity_reasons}, role,two_fac_status, email, finance_permission, first_name, last_name},first_name, last_name, gender, email,picture,accounts.limit(100){id,name,verification_status,is_published,ad_campaign,is_promotable,is_restricted,parent_page,promotion_eligible,promotion_ineligible_reason,fan_count,has_transitioned_to_new_page_experience,picture,roles},adaccounts.limit(100){name,id,adtrust_dsl,account_status,account_id,disable_reason,balance,amount_spent,business{id,name, created_time,timezone_id,two_factor_type,verification_status},assigned_partners{id,name, created_time,timezone_id,two_factor_type,verification_status},business_restriction_reason,average_daily_campaign_budget,created_time,is_new_advertiser,is_tier_restricted,timezone_name,timezone_id,currency,self_resolve_uri,age,max_billing_threshold,current_unbilled_spend,adspaymentcycle,spend_cap,owner,is_prepay_account,funding_source_details}';
        $query = [
            'fields' => $fields
        ];
        $data = FbUtils::makeRequest($this->fbAccount,$endpoint, $query);
        if (!$data['success']) {
            Log::warning("failed to sync {$this->fbAccountID}");
            return;
        }

        // FbAccount 数据更新
        $this->fbAccount->source_id = $data->get('id');
        $this->fbAccount->name = $data->get('name');
        $this->fbAccount->first_name = $data->get('first_name');
        $this->fbAccount->last_name = $data->get('last_name');
        $this->fbAccount->gender = $data->get('gender');
        $this->fbAccount->picture = $data->get('picture.data.url');
        $this->fbAccount->save();

        $this->syncFbPage($data);

        $fbBmsData = $data->get('business_users');
        $fbBmsCollection = collect($fbBmsData['data'] ?? []);

        $this->syncFbBM($fbBmsCollection);

        $this->syncAdAccount($data);

        $fields = 'id,adaccounts.limit(100){id,account_id,adspixels{id,name,creation_time,is_unavailable,is_created_by_business,creator,owner_ad_account,owner_business}}';

        # TODO: 分页要处理
        $query = [
            'fields' => $fields
        ];
        $data = FbUtils::makeRequest($this->fbAccount, $endpoint, $query);
        Log::debug($data);

        $this->syncFbPixel($data);

        if ($this->next) {
            FacebookFetchCampaign::dispatch($this->fbAdAccountID, $this->date_start, $this->date_stop, $this->fbAccountID, $this->next)->onQueue('facebook');
        }
    }



    private function syncFbBM(Collection $dataCollection)
    {
        Log::debug('start sync fb bm');

        $dataCollection->each(function ($fbBmUser) {

            // 当前 FbAccount 的 BusinessUser ID
            $currentBusinessUserID = $fbBmUser['id'];

            $business = $fbBmUser['business'];

            $businessSourceID = $business['id'];
            $oldBm = FbBm::query()->firstWhere('source_id', $businessSourceID);
            if ($oldBm) {
                $oldBmDisabledFlag = $oldBm['is_disabled_for_integrity_reasons'];
            }
            $fbBm = FbBm::query()->updateOrCreate(
                [
                    'source_id' => $business['id']
                ],
                [
                    'name' => $business['name'],
                    'created_time' => Carbon::parse($business['created_time']),
                    'timezone_id' => (string)$business['timezone_id'],
                    'verification_status' => $business['verification_status'],
                    'is_disabled_for_integrity_reasons' => $business['is_disabled_for_integrity_reasons'],
                    'two_factor_type' => $business['two_factor_type'] ?? '',
                ]
            );
            if ($oldBm) {
                if (!$oldBmDisabledFlag) {
                    if ($fbBm['is_disabled_for_integrity_reasons']) {
                        $msg = "BM: {$fbBm['name']} ({$fbBm['source_id']}) 广告功能受限";
                        Log::warning($msg);
                        Telegram::sendMessage($msg);
                    }
                }
            }

            Log::debug("bm id: {$fbBm->id}");

            $businessUsersCollection = collect($business['business_users']['data']);
            $businessUsersCollection->each(function ($fbBusinessUserData) use ($currentBusinessUserID, $fbBm) {
                $fbBusinessUserSourceID = $fbBusinessUserData['id'];
                Log::debug("fb business id: {$fbBusinessUserSourceID}");
                $fbBusinessUser = FbBusinessUser::query()->updateOrCreate(
                    [
                        'source_id' => $fbBusinessUserSourceID
                    ],
                    [
                        'email' => $fbBusinessUserData['email'] ?? '',
                        'finance_permission' => $fbBusinessUserData['finance_permission'] ?? '',
                        'name' => $fbBusinessUserData['name'],
                        'first_name' => $fbBusinessUserData['first_name'],
                        'last_name' => $fbBusinessUserData['last_name'],
                        'role' => $fbBusinessUserData['role'],
                        'two_fac_status' => $fbBusinessUserData['two_fac_status'] ?? '',
                        'expiry_time' => (string)$fbBusinessUserData['expiry_time'],
                        'fb_bm_id' => $fbBm->id
                    ]
                );
                // 如果 fb business user id 与 当前的 id 相同，就把当前 business user 与 当前的 fb account 关联起来
                // 这样, fb account 通过 fb business account 就可以知道有哪些 bm 了
                if ($fbBusinessUserSourceID == $currentBusinessUserID) {
                    $fbBusinessUser->fb_account_id = $this->fbAccountID;
                    $fbBusinessUser->save();
                }

            });

        });

        $fbAccCurrentBmSourceID = $this->fbAccount->fbBms()->pluck('fb_bms.source_id')->toArray();
        $bmSourceIDToKeep = $dataCollection->pluck('business.id')->all();
        $bmSourceIDToRemove = array_diff($fbAccCurrentBmSourceID, $bmSourceIDToKeep);
        // 移除过时的关联并记录操作
        foreach ($bmSourceIDToRemove as $SourceIDToRemove) {
            $fbBM = FbBm::query()->firstWhere('source_id', $SourceIDToRemove);
            $msg = "FB账号: {$this->fbAccount->name} 已经不再关联BM: {$fbBM->name}({$SourceIDToRemove})";
            Log::info($msg);
            Telegram::sendMessage($msg);
            //把 bm 与 facebook 个人号关联移除
            // 从 facebook 个号中找出 business user, business user 中的 fb_bm_id 为这个bm 的 id
            // 再把这个 business_user 的 fb_account_id 设置为 null
            $fbBusinessUser = $this->fbAccount->fbBusinessUsers()->firstWhere('fb_bm_id', $fbBM->id);
            $fbBusinessUser->fb_account_id = null;
            $fbBusinessUser->save();
        }
        Log::debug('end sync fb bm');

    }

    public function tags(): array
    {
        return [
            'FB',
            'SyncRes',
            "{$this->fbSourceID} "
        ];
    }

    public function failed(\Throwable $exception)
    {
        // Log failure
        $msg = "sync resource failed please check ASAP: \r\n fb acc id: {$this->fbSourceID}";
        Telegram::sendMessage($msg);
        Log::error('Job failed: ' . $exception->getMessage());
    }

    /**
     * @param Collection $data
     * @return void
     */
    public function syncFbPage(Collection $data): void
    {
        Log::debug('start sync fb page');

        // 保存 Fb Page
        $fbPagesData = $data->get('accounts');
        $fbPagesCollection = collect($fbPagesData['data'] ?? []);

        // 遍历集合中的每个 Page 对象
        $fbPagesCollection->each(function ($page) {
            // 访问对象的属性
            if (isset($page['roles'])) {
                $old_obj = FbPage::query()->firstWhere('source_id', $page['id']);
                if ($old_obj) {
                    $old_status = $old_obj['is_promotable'];
                } else {
                    $old_status = null;
                }
                $fbPage = FbPage::query()->updateOrCreate(
                    [
                        'source_id' => $page['id']
                    ],
                    [
                        'name' => $page['name'],
                        'promotion_eligible' => $page['promotion_eligible'],
                        'fan_count' => $page['fan_count'],
                        'picture' => $page['picture']['data']['url'],
                        'verification_status' => $page['verification_status'],
                        'roles' => $page['roles']['data']
                    ]
                );
                if ($old_status != $fbPage['promotion_eligible'] && $fbPage['promotion_eligible'] == false) {
                    $msg = "Page: {$fbPage['name']} ({$fbPage['source_id']}) is restricted for ads promotion";
                    Telegram::sendMessage($msg);
                }
                Log::debug("page: {$fbPage->id}");
                $rolesCollection = collect($page['roles']['data']);

                $rolesCollection->each(function ($role) use ($fbPage) {
                    $fbAccount = FbAccount::where('source_id', $role['id'])->first();
                    $pivotData = [
                        'source_id' => $role['id'],
                        'name' => $role['name'],
                        'tasks' => json_encode($role['tasks']),
                        'role_human' => 'default', // 请根据实际情况修改
                        'is_active' => $role['is_active']
                    ];
                    if ($fbAccount) {
                        // 如果 fbAccount 存在于系统中，关联它们，并且添加额外的 Pivot 表数据
                        if (!$fbPage->fbAccounts->contains($fbAccount->id)) {
                            $fbPage->fbAccounts()->syncWithoutDetaching([
                                $fbAccount->id => $pivotData
                            ]);
                        }
                    } else {
                        // 如果 fbAccount 不存在于你的系统中，你可以使用 Pivot 表在中间表中存储数据
                        DB::table('fb_account_page')->insert(array_merge([
                            'id' => (string)Ulid::generate(),
                            'fb_page_id' => $fbPage->id
                        ], $pivotData));
                    }
                });
            } else {
                $name = $page['name'];
                Log::warning("page ${name} is pending approval");
            }

        });
        Log::debug('end sync fb page');

    }

    public function syncAdAccount(Collection $data)
    {
        Log::debug('start sync personal ad account');
        // 个人号有权限的 ad account
        $adAccounts = $data->get('adaccounts');
        $adAccountsCollection = Collect($adAccounts['data'] ?? []);
        $adAccountsCollection->each(function ($adAccountData) {

            $adtrust_dsl = $adAccountData['adtrust_dsl'];
            $original_adtrust_dsl = $adAccountData['adtrust_dsl'];
            if ($original_adtrust_dsl != -1) {
                $adtrust_dsl = CurrencyUtils::convertToFloat($original_adtrust_dsl, $adAccountData['currency']);
            }

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
                    'adtrust_dsl' => $adtrust_dsl,
                    'original_adtrust_dsl' => $original_adtrust_dsl,
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
                    'business_restriction_reason' => $adAccountData['business_restriction_reason'],
                    'created_time' => Carbon::parse($adAccountData['created_time']),
                    'currency' => $adAccountData['currency'],
                    'current_unbilled_spend' => $adAccountData['current_unbilled_spend'],
                    'disable_reason' => FbUtils::$FbAdAccountDisableReasonMap[$adAccountData['disable_reason']] ?? 'Unknown',
                    'disable_reason_code' => $adAccountData['disable_reason'],
                    'max_billing_threshold' => $adAccountData['max_billing_threshold'],
                    'name' => $adAccountData['name'],
                    'owner' => $adAccountData['owner'],
                    'timezone_id' => $adAccountData['timezone_id'],
                    'timezone_name' => $adAccountData['timezone_name'],
                    'is_prepay_account' => $adAccountData['is_prepay_account']
                ]
            );

            if (isset($adAccountData['funding_source_details'])) {
                $adAccount['funding_type'] = $adAccountData['funding_source_details']['type'];
                $adAccount['default_funding'] = $adAccountData['funding_source_details']['display_string'];
                $adAccount->save();
            }

            Log::debug("fb ad account id: {$adAccount->id}, source_id: {$adAccountData['account_id']}");

            // Ad account 与 FbAccount 关联同步，因为是在 AdAccount 的 Data 里面，中间表里面的 FbAdAccount ID 肯定是有的
            // 根据 owner id 来确定 relation, 如果 Owner ID 与 FbAccount 的 source id 相同，则是 Owner

            $pivotData = [
                'source_id' => $adAccountData['account_id'],
                'relation' => $adAccountData['owner'] == $this->fbSourceID ? "Owner" : "Partner"
            ];

            $adAccount->fbAccounts()->syncWithoutDetaching([
                $this->fbAccountID => $pivotData
            ]);

            // 设定是否是自己的号，只有个号才处理，否则为 Null
            if (isset($adAccountData['business']) || isset($adAccountData['assigned_partners'])) {
                $adAccount->is_original = null;
            } else {
                // $adAccount 没有 'business' 和 'assigned_partners' 这两个键
                if ($adAccountData['owner'] == $this->fbSourceID) {
                    $adAccount->is_original = true;
                } else {
                    $adAccount->is_original = false;
                }
            }

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
                $pivotData = [
                    'relation' => $adAccountData['owner'] == $bm['id'] ? 'Owner' : 'Partner',
                    'source_id' => $adAccountData['account_id']
                ];
                $result = $adAccount->fbBms()->syncWithoutDetaching([
                    $fbBM->id => $pivotData
                ]);
                Log::debug("After sync, result: " . json_encode($result));
            }
        });
        // 个号取消与 Ad Account 的关联

        $currentFbAccAdAccountSourceIDs = $this->fbAccount->fbAdAccounts()->pluck('fb_ad_accounts.source_id')->toArray();
        $adAccountSourceIDsToKeep = $adAccountsCollection->pluck('account_id')->all();
        $adAccountSourceIDsToRemove = array_diff($currentFbAccAdAccountSourceIDs, $adAccountSourceIDsToKeep);

        // 移除过时的关联并记录操作
        foreach ($adAccountSourceIDsToRemove as $SourceIDToRemove) {
            $fbAdAccount = FbAdAccount::query()->firstWhere('source_id', $SourceIDToRemove);
            $this->fbAccount->fbAdAccounts()->detach($fbAdAccount->id);
            Log::info("FB账号: {$this->fbAccount->name} 已经不再关联广告账户: {$fbAdAccount->name}({$SourceIDToRemove})");
            $msg = "FB账号: {$this->fbAccount->name} 已经不再关联广告账户: {$fbAdAccount->name}({$SourceIDToRemove})";
            Telegram::sendMessage($msg);
        }

        Log::debug('end sync personal ad account');
        Log::debug('start sync bm ad account');


        $businessUsersCollection = collect($data['business_users'] ?? []);

        if ($businessUsersCollection->isNotEmpty()) {
            Log::debug('not empty, start ...');
            $businessUsersDataCollection = collect($businessUsersCollection->get('data', []));

            # 在这里面把 ad account 与 bm 关联
            $businessUsersDataCollection->each(function ($businessUser) {
                # 每一个都是BM
                Log::debug('111');
                $businessCollection = collect($businessUser['business']);

                $bm = FbBm::query()->firstWhere('source_id', $businessCollection->get('id'));

                // BM 里面的广告账户
                $clientAdAccountsCollection = collect($businessCollection->get('client_ad_accounts', []));
                $clientAdAccountsDataCollection = collect($clientAdAccountsCollection->get('data', []));

                # 遍历 ad accounts, 只处理 ad account 与 bm 的关联
                $clientAdAccountsDataCollection->each(function ($adAccountData) use ($bm, $businessCollection) {

                    $adtrust_dsl = $adAccountData['adtrust_dsl'];
                    $original_adtrust_dsl = $adAccountData['adtrust_dsl'];
                    if ($original_adtrust_dsl != -1) {
                        $adtrust_dsl = CurrencyUtils::convertToFloat($original_adtrust_dsl, $adAccountData['currency']);
                    }

                    $original_balance = $adAccountData['balance'];
                    $balance = $original_balance;
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

                    $adAccount = FbAdAccount::query()->updateOrCreate(
                        [
                            'source_id' => $adAccountData['account_id']
                        ],
                        [
                            'adtrust_dsl' => $adtrust_dsl,
                            'original_adtrust_dsl' => $original_adtrust_dsl,
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
                            'business_restriction_reason' => $adAccountData['business_restriction_reason'],
                            'created_time' => Carbon::parse($adAccountData['created_time']),
                            'currency' => $adAccountData['currency'],
                            'current_unbilled_spend' => $adAccountData['current_unbilled_spend'],
                            'disable_reason' => FbUtils::$FbAdAccountDisableReasonMap[$adAccountData['disable_reason']] ?? 'Unknown',
                            'disable_reason_code' => $adAccountData['disable_reason'],
                            'max_billing_threshold' => $adAccountData['max_billing_threshold'],
                            'name' => $adAccountData['name'],
                            'owner' => $adAccountData['owner'],
                            'timezone_id' => $adAccountData['timezone_id'],
                            'timezone_name' => $adAccountData['timezone_name'],
                            'is_prepay_account' => $adAccountData['is_prepay_account']
                        ]
                    );
                    Log::debug("ad account id: {$adAccount->id}, source_id: {$adAccountData['account_id']}");

                    if (isset($adAccountData['funding_source_details'])) {
                        $adAccount['funding_type'] = $adAccountData['funding_source_details']['type'];
                        $adAccount['default_funding'] = $adAccountData['funding_source_details']['display_string'];
                        $adAccount->save();
                    }

                    $pivotData = [
                        'relation' => $adAccountData['owner'] == $businessCollection->get('id') ? 'Owner' : 'Partner',
                        'source_id' => $adAccountData['account_id']
                    ];

                    $result = $adAccount->fbBms()->syncWithoutDetaching([
                        $bm->id => $pivotData
                    ]);
                    Log::debug("After sync, result: " . json_encode($result));

                });

                // 把 FbAdAccount 与 FbBusinessUser 关联起来
                $businessUsers = collect($businessCollection->get('business_users'), []);
                $businessUsersDataCollection = collect($businessUsers->get('data'), []);
//                Log::debug($businessUsers);
                Log::debug('222');
                $businessUsersDataCollection->each(function ($businessUserData) {
                    Log::debug('333');
                    $businessUser = FbBusinessUser::query()->updateOrCreate(
                       [
                           'source_id' => $businessUserData['id']
                       ],
                       [
                           'email' => $businessUserData['email'] ?? '',
                           'expiry_time' => $businessUserData['expiry_time'],
                           'name' => $businessUserData['name'],
                           'first_name' => $businessUserData['first_name'],
                           'last_name' => $businessUserData['last_name'],
                           'role' => $businessUserData['role'],
                           'two_fac_status' => $businessUserData['two_fac_status'] ?? '',
                       ]
                    );
                    $adAccountIds = [];

                    $assignedAdAccounts = collect($businessUserData)->get('assigned_ad_accounts', []);
                    $assignedAdAccountsData = collect($assignedAdAccounts)->get('data', []);
                    Log::debug('-------');
                    foreach ($assignedAdAccountsData as $adAccountData) {
                        Log::debug('444');
                        $fbAdAccount = FbAdAccount::query()->firstWhere('source_id', $adAccountData['account_id']);
//                        Log::debug("fb ad account: {$fbAdAccount->source_id}");
                        if ($fbAdAccount) {
                            if (isset($adAccountData['role'])) {
                                $adAccountIds[$fbAdAccount->id] = ['role' => $adAccountData['role']];
                                $result = $businessUser->fbAdAccounts()->syncWithoutDetaching([
                                    $fbAdAccount->id => ['role' => $adAccountData['role']]
                                ]);
                                Log::debug("After sync, result: " . json_encode($result));
                            }

                        } else {
                            Log::warning("{$adAccountData['account_id']}, {$adAccountData['role']} not in db");
                        }
                    }
//                    $businessUser->fbAdAccounts()->sync($adAccountIds);
                });
            });


        } else {
            Log::debug('empty stop...');
        }
        Log::debug('end sync bm ad account');

    }

    public function syncFbPixel(Collection $data)
    {
        Log::debug('------ Start sync Pixel --------');
        $adAccounts = collect($data->get('adaccounts'));
        $adAccountsData = collect($adAccounts->get('data'));
        Log::debug("ad account number: {$adAccountsData->count()}");
        $adAccountsData->each(function ($adAccount) {
            $adpixels = collect(collect($adAccount)->get('adspixels'));
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
                        'creator' => $adpixel['creator'] ?? [],
                    ]
                );

                // 与 AdAccount 关联
                $adAccountSourceID = $adAccount['account_id'];
                $fbAdAccount = FbAdAccount::query()->firstWhere('source_id', $adAccountSourceID);
                if ($fbAdAccount) {
                    $fbPixel->fbAdAccounts()->syncWithoutDetaching([$fbAdAccount->id]);
                }

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
        Log::debug('------ End of sync Pixel --------');

    }
}
