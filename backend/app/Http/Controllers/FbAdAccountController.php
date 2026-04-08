<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbAdAccountResource;
use App\Http\Resources\FbAdAccountResourceMode2;
use App\Http\Resources\FbAdAccountWithCampaignResource;
use App\Jobs\FacebookSetAccountSpendCap;
use App\Jobs\FacebookSyncApiResource;
use App\Jobs\TriggerFacebookFetchApiResource;
use App\Models\FbAdAccount;
use App\Models\FbApiToken;
use App\Models\FbApp;
use App\Models\FbBm;
use App\Models\FbPixel;
use App\Models\Tag;
use App\Services\FbAdAccountAssignedUsersFromGraphService;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FbAdAccountController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'account_ids' => 'array',
            'account_names' => 'array',
            'ad_account_ids' => 'array',
            'ad_account_names' => 'array',
            'bm_ids' => 'array',
            'bm_names' => 'array',
            'enable_rule' => 'in:true,false',
            'is_archived' => 'in:true,false',
            'bm_system_users' => 'nullable|array',
            'bm_system_users.*' => 'string|exists:fb_api_tokens,id',
            'with-campaign' => 'nullable',
            'child_source_ids' => 'array',
            'cards' => 'array'
        ]);

        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);
//        $tagNames = $request->query('tags', []);

        $fbAdAccountTags = $request->query('ad_account_tags', []);
//        $fbAdAccountTags = $tagNames;

        $fbAccountTags = $request->query('fb_account_tags', []);

        $systemUserIds = $request->query('user_ids', []);

        $campaignNames = $request->query('campaign_names', []);
        $campaignTags = $request->query('campaign_tags', []);
        $others = collect($request->query('others', []));

        $searchableFields = [
            'name' => $request->get('name'),
            'account_status' => $request->get('account_status'),
        ];

        $fbBusinessUserName = $request->get('business_user_name');
        $fbBusinessUserEmail = $request->get('business_user_email');

        $apiTokenIds = $request->get('bm_system_users');
        $withCampaign = filter_var($request->get('with-campaign'), FILTER_VALIDATE_BOOLEAN);

//        $fbAdAccounts = FbAdAccount::searchByTagNames($tagNames)
//            ->search($searchableFields);
        $fbAdAccounts = FbAdAccount::search($searchableFields);


        $admin = auth()->user()->hasRole('admin');
        $userId = auth()->id();

        if (!$admin) {
            // 必须用闭包包住 OR，否则后续 whereHas（如 bm_ids）会与 OR 错误结合，导致列表恒为空
            $fbAdAccounts = $fbAdAccounts->where(function ($q) use ($userId) {
                $q->whereHas('fbAccounts', function ($query) use ($userId) {
                    $query->where('fb_accounts.user_id', $userId);
                })->orWhereHas('users', function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                });
            })->with(['tags' => function ($query) use ($userId) {
                $query->where('tags.user_id', $userId);
            }]);
        }
//        $fbAdAccounts = $fbAdAccounts->whereHas('fbAccounts', function ($query) use ($userId) {
//            $query->where('user_id', $userId);
//        });

        if ($request->get('account_status')) {
            $fbAdAccounts = $fbAdAccounts->whereIn('account_status', $request->get('account_status'));
        }

        if ($apiTokenIds) {
            $fbAdAccounts = $fbAdAccounts->whereHas('apiTokens', function ($query) use ($apiTokenIds) {
                $query->whereIn('fb_api_tokens.id', $apiTokenIds);
            });
        }

        // 这里是在 fb ads 的界面调用时候，传的参数，与 tags 其实一样
        if ($fbAdAccountTags) {
//            $fbAdAccounts = $fbAdAccounts->searchByTagNames($fbAdAccountTags);
            if (!$admin) {
                $fbAdAccounts = $fbAdAccounts->whereHas('tags', function ($query) use ($userId, $fbAdAccountTags) {
                    $query->where('tags.user_id', $userId)->whereIn('tags.name', $fbAdAccountTags);
                });
            } else {
                $fbAdAccounts = $fbAdAccounts->whereHas('tags', function ($query) use ($userId, $fbAdAccountTags) {
                    $query->whereIn('tags.name', $fbAdAccountTags);
                });
            }

        }

        if ($fbAccountTags) {
            $fbAdAccounts = $fbAdAccounts->whereHas('fbAccounts.tags', function ($query) use ($fbAccountTags, $userId) {
                $query->where('tags.user_id', $userId)->whereIn('tags.name', $fbAccountTags);
            });
        }

        if ($systemUserIds) {
            $fbAdAccounts = $fbAdAccounts->where(function ($q) use ($systemUserIds) {
                $q->whereHas('fbAccounts', function ($query) use ($systemUserIds) {
                    $query->whereIn('user_id', $systemUserIds);
                })->orWhereHas('users', function ($query) use ($systemUserIds) {
                    $query->whereIn('users.id', $systemUserIds);
                });
            });
        }

        if ($request->get('enable_rule')) {
            $fbAdAccounts = $fbAdAccounts->where('enable_rule', filter_var($request->get('enable_rule'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->get('is_archived')) {
            $fbAdAccounts = $fbAdAccounts->where('is_archived', '=', filter_var($request->get('is_archived'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->get('auto_sync')) {
            $fbAdAccounts = $fbAdAccounts->where('auto_sync', '=', filter_var($request->get('auto_sync'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->get('keywords')) {
            $keywords = $request->get('keywords');
            $fbAdAccounts = $fbAdAccounts->when($keywords, function ($q) use ($keywords) {
                // 在闭包内部，$q 代表当前的查询构建器实例

                // 为了确保 OR 条件只应用于 name 和 id，并且不影响其他可能存在的 WHERE 条件，
                // 我们将 name 和 id 的 OR 查询包裹在一个闭包 where 中。
                return $q->where(function ($subQuery) use ($keywords) {
                    // 准备好 SQL 的 LIKE 模糊匹配模式
                    $searchTerm = '%' . $keywords . '%';

                    // 添加 name 字段的模糊匹配查询
                    $subQuery->where('name', 'LIKE', $searchTerm)
                        // 添加 id 字段的模糊匹配查询 (使用 orWhere)
                        ->orWhere('source_id', 'LIKE', $searchTerm);

                    // 注意: 对 id (通常是数字类型) 使用 LIKE 可能效率不高，
                    // 并且行为可能因数据库类型而异 (例如 MySQL 会隐式转换类型)。
                    // 如果你的 id 总是数字，并且你只想匹配完整的 id，
                    // 可以考虑改为 ->orWhere('id', $keywords) 如果 is_numeric($keywords)
                    // 但根据你的要求“id模糊匹配”，这里保留 LIKE
                });
            });
        }

        if ($fbBusinessUserName || $fbBusinessUserEmail) {
            $fbAdAccounts = $fbAdAccounts->whereHas('fbBusinessUsers', function ($query) use ($fbBusinessUserName, $fbBusinessUserEmail) {
                $query->where('fb_business_users.name', 'LIKE', "%{$fbBusinessUserName}%")
                    ->where('fb_business_users.email', 'LIKE', "%{$fbBusinessUserEmail}%");
            });
        }

        if ($request->get('ad_account_ids')) {
            $fbAdAccounts = $fbAdAccounts->whereIn('source_id', $request->get('ad_account_ids'));
        }

        $ad_account_names = $request->get('ad_account_names');
        if ($ad_account_names) {
            $fbAdAccounts = $fbAdAccounts->where(function ($query) use ($ad_account_names) {
                foreach ($ad_account_names as $ad_account_name) {
                    $query->orWhere('name', 'LIKE', '%' . $ad_account_name . '%');
                }
            });
        }

        $fbAccountIds = $request->get('account_ids');
        if ($fbAccountIds) {
            $fbAdAccounts = $fbAdAccounts->whereHas('fbAccounts', function ($query) use ($fbAccountIds) {
                $query->whereIn('fb_accounts.source_id', $fbAccountIds);
            });
        }

        $fbAccountNames = $request->get('account_names');
        if ($fbAccountNames) {
            $fbAdAccounts = $fbAdAccounts->whereHas('fbAccounts', function ($query) use ($fbAccountNames) {
                $query->where(function ($innerQuery) use ($fbAccountNames) {
                    foreach ($fbAccountNames as $name) {
                        $innerQuery->orWhere('fb_accounts.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        $bmIds = $request->get('bm_ids');
        if ($bmIds) {
            $fbAdAccounts = $fbAdAccounts->whereHas('fbBms', function ($query) use ($bmIds) {
                $query->whereIn('fb_bms.source_id', $bmIds);
            });
        }

        $fbBmNames = $request->get('bm_names');
        if ($fbBmNames) {
            $fbAdAccounts = $fbAdAccounts->whereHas('fbBms', function ($query) use ($fbBmNames) {
                $query->where(function ($innerQuery) use ($fbBmNames) {
                    foreach ($fbBmNames as $name) {
                        $innerQuery->orWhere('fb_bms.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

//        if ($campaignNames || $campaignTags || $others->contains('exclude_archived_campaign')) {

        if ($campaignNames || $campaignTags) {

            $fbAdAccounts = $fbAdAccounts->whereHas('fbCampaigns', function ($query) use ($userId, $campaignTags, $others, $campaignNames) {
                $query->where(function ($innerQuery) use ($campaignNames, $campaignTags, $userId, $others) {
                    if ($campaignNames) {
                        Log::debug("filter campaign names:");
                        Log::debug($campaignNames);
                        foreach ($campaignNames as $name) {
                            $innerQuery->orWhere('name', 'LIKE', '%' . $name . '%');
                        }
                    }
//                    if ($others->contains('exclude_archived_campaign')) {
//                        Log::debug('fb ad account: exclude archived campaign');
//                        $innerQuery->where('is_archived', false);
//                    }

                    if ($campaignTags) {
                        $innerQuery->whereHas('tags', function ($query) use ($campaignTags, $userId) {
                            $query->where('tags.user_id', $userId)->whereIn('name', $campaignTags);
                        });
                    }
                });
            });
        }

        // 根据子对象的 source_id 查询对应的 FbAdAccount
        $childSourceIds = $request->get('child_source_ids');
        if ($childSourceIds) {
            $fbAdAccounts = $fbAdAccounts->where(function ($query) use ($childSourceIds) {
                // 查询包含指定 Campaign source_id 的 FbAdAccount
                $query->whereHas('fbCampaigns', function ($subQuery) use ($childSourceIds) {
                    $subQuery->whereIn('fb_campaigns.source_id', $childSourceIds);
                })
                // 或者查询包含指定 Adset source_id 的 FbAdAccount
                ->orWhereHas('fbCampaigns.fbAdsets', function ($subQuery) use ($childSourceIds) {
                    $subQuery->whereIn('fb_adsets.source_id', $childSourceIds);
                })
                // 或者查询包含指定 Ad source_id 的 FbAdAccount
                ->orWhereHas('fbAds', function ($subQuery) use ($childSourceIds) {
                    $subQuery->whereIn('fb_ads.source_id', $childSourceIds);
                });
            });
        }

        // 根据 cards 参数查询 default_funding 包含指定字符串的 FbAdAccount
        $cards = $request->get('cards');
        if ($cards) {
            $fbAdAccounts = $fbAdAccounts->where(function ($query) use ($cards) {
                foreach ($cards as $card) {
                    $query->orWhere('default_funding', 'LIKE', '%' . $card . '%');
                }
            });
        }

//        if ($campaignTags) {
//            $fbAdAccounts = $fbAdAccounts->whereHas('fbCampaigns', function ($query) use ($userId, $campaignTags) {
//                $query->whereHas('tags', function ($query) use ($campaignTags, $userId) {
//                    $query->where('tags.user_id', $userId)->whereIn('tags.name', $campaignTags);
//                });
//            });
//        }

//        if ($others->contains('exclude_archived_campaign')) {
//            Log::debug('fb ad account: exclude archived campaign');
//            $fbAdAccounts = $fbAdAccounts->whereDoesntHave('fbCampaigns', function ($query) {
//                $query->where('is_archived', true);
//            });
//        }

        $fbAdAccounts = $fbAdAccounts->with(['fbAccounts', 'fbBms', 'fbBusinessUsers', 'fbPixels', 'fbBms.fbAdAccounts'])
            ->orderBy($sortField, $sortDirection)->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        if ($request->query('mode') && $request->query('mode') > 1) {
            $resourceCollection = FbAdAccountResourceMode2::collection($fbAdAccounts->items());
        } else {
            $resourceCollection = FbAdAccountResource::collection($fbAdAccounts->items());
        }

        if ($withCampaign) {
            $resourceCollection = FbAdAccountWithCampaignResource::collection($fbAdAccounts->items());
        }

//        $resourceCollection = $request->query('mode') && $request->query('mode') > 1
//            ? FbAdAccountResourceMode2::collection($fbAdAccounts->items())
//            : FbAdAccountResource::collection($fbAdAccounts->items());

        return [
            'data' => $resourceCollection,
            'pageSize' => $fbAdAccounts->perPage(),
            'pageNo' => $fbAdAccounts->currentPage(),
            'totalPage' => $fbAdAccounts->lastPage(),
            'totalCount' => $fbAdAccounts->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, FbAdAccount $fbAdAccount)
    {
        $withCampaign = $request->boolean('with-campaign');
        $withApps = $request->boolean('with-apps');

        Log::info('fb_ad_accounts: show request', [
            'fb_ad_account_id' => $fbAdAccount->id,
            'with_campaign' => $withCampaign,
            'with_apps' => $withApps,
        ]);

        if ($withCampaign) {
            Log::info('fb_ad_accounts: show branch=WithCampaignResource（未 load fbAccounts，个号字段可能为空）', [
                'fb_ad_account_id' => $fbAdAccount->id,
            ]);

            return new FbAdAccountWithCampaignResource($fbAdAccount);
        }

        $with = ['fbAccounts', 'fbBms', 'fbBusinessUsers', 'fbPixels'];
        if ($withApps) {
            $with[] = 'subscribedApps';
        }

        $fbAdAccount->load($with);

        if ($fbAdAccount->fbAccounts->isEmpty()) {
            Log::info('fb_ad_accounts: show fb_accounts empty, syncing assigned_users from Graph API', [
                'fb_ad_account_id' => $fbAdAccount->id,
                'source_id' => $fbAdAccount->source_id,
            ]);
            try {
                app(FbAdAccountAssignedUsersFromGraphService::class)->sync(
                    FbUtils::$API_Version,
                    $fbAdAccount,
                    MetaAdCreationBmGraphController::defaultGraphAccessToken()
                );
                $fbAdAccount->load('fbAccounts');
            } catch (\Throwable $e) {
                Log::warning('fb_ad_accounts: show Graph assigned_users sync failed', [
                    'fb_ad_account_id' => $fbAdAccount->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::info('fb_ad_accounts: show branch=FbAdAccountResource', [
            'fb_ad_account_id' => $fbAdAccount->id,
            'loaded' => $with,
            'fb_accounts_count' => $fbAdAccount->fbAccounts->count(),
            'fb_pixels_count' => $fbAdAccount->fbPixels->count(),
            'fb_bms_count' => $fbAdAccount->fbBms->count(),
            'fb_business_users_count' => $fbAdAccount->fbBusinessUsers->count(),
        ]);

        return new FbAdAccountResource($fbAdAccount);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbAdAccount $fbAdAccount)
    {
        $userID = auth()->user()->id;

        // 验证请求数据
        $validatedData = $request->validate([
            'notes' => 'string|nullable',
            'tags' => 'array|distinct|nullable'
        ]);

        // 更新模型实例
        $fbAdAccount->update($validatedData);
//        // 创建新的 Tag
//        $newTags = collect($request->get('new_tags'))->map(function ($name) {
//            return Tag::query()->firstOrCreate(['name' => $name])->id;
//        });
//
//        // 使用已经有的 tags
//        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
//        $fbAdAccount->tags()->sync($tagIds);

        // 如果 tag 已经存在，就直接attach, 如果 tag 不存在，先要创建 tag, 再attach
        $allTags = $fbAdAccount->tags()->where('tags.user_id', $userID)->pluck('name');
        $tagNames = collect($request->get('tags', []))->unique();
        $existingTags = Tag::query()->where('tags.user_id', $userID)->whereHas('fbAdAccounts')->whereIn('name', $tagNames)->pluck('name');
        $newTags = $tagNames->diff($existingTags);
        $toDeletedTags = $allTags->diff($tagNames);

        foreach ($existingTags as $tagName) {
            $tag = Tag::query()->where('tags.user_id', $userID)->whereHas('fbAdAccounts')->where('name', '=' , $tagName)->first();
            if (!$fbAdAccount->tags->contains($tag->id)) {
                $fbAdAccount->tags()->attach($tag->id, ['user_id' => $userID]);
            }
        }

        foreach ($newTags as $tagName) {
            $tag = Tag::query()->firstOrCreate(['name' => $tagName, 'user_id' => $userID]);
            $fbAdAccount->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        foreach ($toDeletedTags as $tagName) {
            $tag = Tag::query()->where('user_id', $userID)->whereHas('fbAdAccounts')->where('name', '=' , $tagName)->first();
            $fbAdAccount->tags()->detach($tag->id);
        }

        // 返回资源
        return new FbAdAccountResource($fbAdAccount->load(['fbAccounts', 'fbBms', 'fbBusinessUsers', 'tags']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbAdAccount $fbAdAccount)
    {
        //
    }

    public function archive(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        $user = $request->user();

        foreach ($ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }
        FbAdAccount::whereIn('id', $ids)->update(['is_archived' => true]); // 更新数据库
        return response()->json(['message' => 'Accounts archived successfully.']);
    }

    public function unarchive(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        $user = $request->user();

        foreach ($ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }
        FbAdAccount::whereIn('id', $ids)->update(['is_archived' => false]); // 更新数据库
        return response()->json(['message' => 'Accounts unarchived successfully.']);
    }

    public function enableRule(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
        ]);

        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        $user = $request->user();

        foreach ($ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }
        FbAdAccount::whereIn('id', $ids)->update(['enable_rule' => true]); // 更新数据库
        # TODO: i18n
        return response()->json(['message' => 'Operation successfully.']);
    }

    public function disableRule(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        $user = $request->user();

        foreach ($ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }
        FbAdAccount::whereIn('id', $ids)->update(['enable_rule' => false]); // 更新数据库
        # TODO: i18n
        return response()->json(['message' => 'Operation successfully.']);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 只有 admin 能调用
     * 根据 api token 同步 api 所具有的资产
     */
    public function sync_api_resource(Request $request)
    {
        $clenData = $request->validate([
            'ids' => 'array',
            'ids.*' => 'string|exists:fb_api_tokens,id'
        ]);

        TriggerFacebookFetchApiResource::dispatch($clenData['ids'])->onQueue('frontend');

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function assign_users(Request $request)
    {
        // 验证传入的数据格式
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'string|exists:users,id',
            'ad_account_ids' => 'required|array',
            'ad_account_ids.*' => 'string|exists:fb_ad_accounts,id',
        ]);

        $userIds = $validated['user_ids'];
        $adAccountIds = $validated['ad_account_ids'];

        foreach ($adAccountIds as $adAccountId) {
            $adAccount = FbAdAccount::find($adAccountId);

            if ($adAccount) {
                // 使用syncWithoutDetaching来避免覆盖已有关系
                $adAccount->users()->syncWithoutDetaching($userIds);
            }
        }
        return response()->json([
            'message' => 'success',
            'success' => true
        ]);
    }

    public function remove_users(Request $request)
    {
        // 验证传入的数据格式
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'string|exists:users,id',
            'ad_account_ids' => 'required|array',
            'ad_account_ids.*' => 'string|exists:fb_ad_accounts,id',
        ]);

        $userIds = $validated['user_ids'];
        $adAccountIds = $validated['ad_account_ids'];

        foreach ($adAccountIds as $adAccountId) {
            $adAccount = FbAdAccount::find($adAccountId);

            if ($adAccount) {
                // 使用detach来移除指定的用户
                $adAccount->users()->detach($userIds);
            }
        }

        return response()->json([
            'message' => 'success',
            'success' => true
        ]);
    }

    public function auto_sync(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
            'on' => 'required|boolean'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        $user = $request->user();

        foreach ($ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }
        FbAdAccount::whereIn('id', $ids)->update(['auto_sync' => $validatedData['on']]); // 更新数据库
        return response()->json(['message' => 'Operation successfully.']);
    }

    public function set_account_spend_cap(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
            'cap_type' => 'required|string|in:reset,remove,amount',
            'cap_value' => 'numeric'
        ]);
        $ids = $request->get('ids'); // 从请求中获取要归档的账户ID
        $cap_type = $validatedData['cap_type'];
        $cap_value = $request->get('cap_value', 0);
        $user = $request->user();

        foreach ($ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        $accs = FbAdAccount::query()->whereIn('id', $ids)->get();
        foreach ($accs as $index => $acc) {
            FacebookSetAccountSpendCap::dispatch($acc->source_id, $cap_type, $cap_value)->delay($index * 5)
                ->onQueue('frontend');
        }

        return response()->json(['message' => 'Task submitted']);
    }

    /**
     * 批量更新/添加广告账户的filters
     */
    public function updateFilters(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
            'filters' => 'required|array',
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string',
            'filters.*.value' => 'required',
            'filters.*.scope' => 'required|array',
            'filters.*.scope.*' => 'required|string|in:campaign,adset,ad'
        ]);

        $ids = $request->get('ids');
        $filters = $request->get('filters');
        $user = $request->user();

        foreach ($ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        FbAdAccount::whereIn('id', $ids)->update(['filters' => $filters]);

        return response()->json([
            'message' => '过滤条件更新成功',
            'success' => true
        ]);
    }

    /**
     * 批量清空广告账户的filters
     */
    public function clearFilters(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id'
        ]);

        $ids = $request->get('ids');
        $user = $request->user();

        foreach ($ids as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json(['message' => 'Unauthorized action.'], 403);
            }
        }

        FbAdAccount::whereIn('id', $ids)->update(['filters' => null]);

        return response()->json([
            'message' => '过滤条件清空成功',
            'success' => true
        ]);
    }

    /**
     * Toggle is_topup field for FB Ad Accounts
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle_topup(Request $request)
    {
        $request->validate([
            'source_ids' => 'required|array|min:1',
            'source_ids.*' => 'required|string',
            'value' => 'required|boolean'
        ]);

        $sourceIds = $request->input('source_ids');
        $value = $request->input('value');
        $user = auth()->user();

        // 查找所有要更新的广告账户
        $fbAdAccounts = FbAdAccount::whereIn('source_id', $sourceIds)->get();

        // 检查是否所有source_id都存在
        if ($fbAdAccounts->count() !== count($sourceIds)) {
            $foundSourceIds = $fbAdAccounts->pluck('source_id')->toArray();
            $notFoundSourceIds = array_diff($sourceIds, $foundSourceIds);

            return response()->json([
                'message' => '部分广告账户未找到',
                'success' => false,
                'not_found_source_ids' => $notFoundSourceIds
            ], 404);
        }

        // 检查用户权限
        foreach ($fbAdAccounts as $fbAdAccount) {
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json([
                    'message' => "没有权限操作广告账户: {$fbAdAccount->source_id}",
                    'success' => false
                ], 403);
            }
        }

        // 批量更新 is_topup 字段
        $updatedCount = FbAdAccount::whereIn('source_id', $sourceIds)->update(['is_topup' => $value]);

        // 返回结果
        return response()->json([
            'message' => 'is_topup 字段更新成功',
            'success' => true,
            'data' => [
                'updated_count' => $updatedCount,
                'value' => $value,
                'source_ids' => $sourceIds
            ]
        ]);
    }

    /**
     * Sync single ad account from Facebook API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync_ad_account(Request $request)
    {
        $request->validate([
            'source_id' => 'required|string'
        ]);

        $sourceId = $request->input('source_id');
        $user = auth()->user();

        // 查找广告账户
        $fbAdAccount = FbAdAccount::where('source_id', $sourceId)->first();

        if (!$fbAdAccount) {
            return response()->json([
                'message' => '广告账户未找到',
                'success' => false
            ], 404);
        }

        // 检查用户权限
        if (!$user->can('operate', $fbAdAccount)) {
            return response()->json([
                'message' => '没有权限操作该广告账户',
                'success' => false
            ], 403);
        }

        try {
            // 同步执行广告账户同步（参考 FacebookSyncAdAccount Job 的逻辑）
            $this->syncAdAccountFromFacebook($fbAdAccount);

            // 重新加载数据以获取最新信息
            $fbAdAccount->refresh();

            return response()->json([
                'message' => '广告账户同步成功',
                'success' => true,
                'data' => new FbAdAccountResource($fbAdAccount)
            ]);

        } catch (\Exception $e) {
            Log::error('Sync ad account failed: ' . $e->getMessage());

            return response()->json([
                'message' => '同步广告账户失败: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * 同步广告账户数据（从 FacebookSyncAdAccount Job 改写的同步版本）
     *
     * @param FbAdAccount $fbAdAccount
     * @throws \Exception
     */
    private function syncAdAccountFromFacebook(FbAdAccount $fbAdAccount)
    {
        Log::info("--- Sync Fb Ad Account info: {$fbAdAccount->source_id} ---");

        // 获取 token
        $token = '';
        $fbAccount = null;

        // 优先使用 API Token
        $apiToken = $fbAdAccount->apiTokens()->where('active', true)->first();
        if ($apiToken) {
            $token = $apiToken->token;
        } else {
            // 查找 token 有效的 fb account
            $fbAccount = $fbAdAccount->fbAccounts()->where('token_valid', true)->first();
            if (!$fbAccount) {
                throw new \Exception("No available API token or FB account for ad account: {$fbAdAccount->source_id}");
            }
        }

        $currency = $fbAdAccount->currency;

        // 构建请求参数
        if ($token) {
            $query = [
                'fields' => 'name,id,account_status,disable_reason,balance,amount_spent,timezone_name,timezone_id,currency,age,spend_cap,is_prepay_account,funding_source_details,owner,adspixels{id,name,is_unavailable,is_created_by_business,owner_business}',
            ];
        } else {
            $query = [
                'fields' => 'name,id,adtrust_dsl,account_status,disable_reason,balance,amount_spent,business_restriction_reason,timezone_name,timezone_id,currency,age,max_billing_threshold,current_unbilled_spend,spend_cap,is_prepay_account,owner,adspixels{id,name,is_unavailable,is_created_by_business,owner_business}',
            ];
        }

        // 调用 Facebook API
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}";

        $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'GET', '', '', $token);

        // 检查状态变化
        $old_status = $fbAdAccount->account_status;
        $new_human_status = FbUtils::$FbAccountStatusMap[$resp['account_status']];
        if ($old_status != $new_human_status) {
            Log::info("Ad account status changed - Account: {$fbAdAccount->name}({$fbAdAccount->source_id}), Old: {$old_status}, New: {$new_human_status}");
        }

        // 货币转换
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

        // 更新广告账户基本信息
        $fbAdAccount->update([
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
        ]);

        // 更新可选字段
        if (isset($resp['adspaymentcycle'])) {
            $fbAdAccount['adspaymentcycle'] = $resp['adspaymentcycle'] ?? [];
        }
        if (isset($resp['current_unbilled_spend'])) {
            $fbAdAccount['current_unbilled_spend'] = $resp['current_unbilled_spend'];
        }
        if (isset($resp['max_billing_threshold'])) {
            $fbAdAccount['max_billing_threshold'] = $resp['max_billing_threshold'];
        }
        if (isset($resp['adtrust_dsl'])) {
            $adtrust_dsl = $resp['adtrust_dsl'];
            $original_adtrust_dsl = $resp['adtrust_dsl'];
            if ($original_adtrust_dsl != -1) {
                $adtrust_dsl = CurrencyUtils::convert($original_adtrust_dsl, $currency, 'USD', 2);
            }
            $fbAdAccount['adtrust_dsl'] = $adtrust_dsl;
            $fbAdAccount['original_adtrust_dsl'] = $original_adtrust_dsl;
        }
        if (isset($resp['business_restriction_reason'])) {
            $fbAdAccount['business_restriction_reason'] = $resp['business_restriction_reason'];
        }

        // 更新资金来源信息
        if (isset($resp['funding_source_details'])) {
            $fbAdAccount['funding_type'] = $resp['funding_source_details']['type'];
            $fbAdAccount['default_funding'] = $resp['funding_source_details']['display_string'];
        }

        $fbAdAccount->save();

        // 同步 Pixel 信息
        if (isset($resp['adspixels']) && isset($resp['adspixels']['data'])) {
            $this->syncAdAccountPixels($fbAdAccount, $resp['adspixels']['data']);
        }

        Log::info("--- Sync Fb Ad Account completed: {$fbAdAccount->source_id} ---");
    }

    /**
     * 同步广告账户的 Pixel 信息
     *
     * @param FbAdAccount $fbAdAccount
     * @param array $pixelsData
     */
    private function syncAdAccountPixels(FbAdAccount $fbAdAccount, array $pixelsData)
    {
        foreach ($pixelsData as $adpixel) {
            Log::debug("Syncing pixel id: {$adpixel['id']}");

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
            $fbPixel->fbAdAccounts()->syncWithoutDetaching([$fbAdAccount->id]);

            // 与 BM 关联
            if (isset($adpixel['owner_business'])) {
                $bmSourceID = $adpixel['owner_business']['id'];
                $fbBm = FbBm::query()->firstWhere('source_id', $bmSourceID);
                if ($fbBm) {
                    $fbPixel->fbBms()->syncWithoutDetaching([$fbBm->id]);
                }
            }
        }
    }

    /**
     * Sync account spend info only (lightweight version)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync_account_spend_info(Request $request)
    {
        $request->validate([
            'source_id' => 'required|string'
        ]);

        $sourceId = $request->input('source_id');
        $user = auth()->user();

        // 查找广告账户
        $fbAdAccount = FbAdAccount::where('source_id', $sourceId)->first();

        if (!$fbAdAccount) {
            return response()->json([
                'message' => '广告账户未找到',
                'success' => false
            ], 404);
        }

        // 检查用户权限
        if (!$user->can('operate', $fbAdAccount)) {
            return response()->json([
                'message' => '没有权限操作该广告账户',
                'success' => false
            ], 403);
        }

        try {
            // 同步执行广告账户消费信息同步（轻量级版本）
            $this->syncAccountSpendInfoFromFacebook($fbAdAccount);

            // 重新加载数据以获取最新信息
            $fbAdAccount->refresh();

            return response()->json([
                'message' => '广告账户消费信息同步成功',
                'success' => true,
                'data' => new FbAdAccountResource($fbAdAccount)
            ]);

        } catch (\Exception $e) {
            Log::error('Sync account spend info failed: ' . $e->getMessage());

            return response()->json([
                'message' => '同步广告账户消费信息失败: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * 同步广告账户消费信息（轻量级版本，只同步指定字段）
     *
     * @param FbAdAccount $fbAdAccount
     * @throws \Exception
     */
    private function syncAccountSpendInfoFromFacebook(FbAdAccount $fbAdAccount)
    {
        Log::info("--- Sync Fb Ad Account spend info: {$fbAdAccount->source_id} ---");

        // 获取 token
        $token = '';
        $fbAccount = null;

        // 优先使用 API Token
        $apiToken = $fbAdAccount->apiTokens()->where('active', true)->first();
        if ($apiToken) {
            $token = $apiToken->token;
        } else {
            // 查找 token 有效的 fb account
            $fbAccount = $fbAdAccount->fbAccounts()->where('token_valid', true)->first();
            if (!$fbAccount) {
                throw new \Exception("No available API token or FB account for ad account: {$fbAdAccount->source_id}");
            }
        }

        $currency = $fbAdAccount->currency;

        // 构建请求参数 - 只请求消费相关字段
        $query = [
            'fields' => 'name,account_status,spend_cap,amount_spent,balance,currency',
        ];

        // 调用 Facebook API
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}";

        $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'GET', '', '', $token);

        // 检查状态变化
        $old_status = $fbAdAccount->account_status;
        $new_human_status = FbUtils::$FbAccountStatusMap[$resp['account_status']];
        if ($old_status != $new_human_status) {
            Log::info("Ad account status changed - Account: {$fbAdAccount->name}({$fbAdAccount->source_id}), Old: {$old_status}, New: {$new_human_status}");
        }

        // 货币转换
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

        // 更新广告账户消费相关信息
        $fbAdAccount->update([
            'name' => $resp['name'],
            'account_status' => FbUtils::$FbAccountStatusMap[$resp['account_status']] ?? "Unknown",
            'spend_cap' => $spend_cap,
            'amount_spent' => $amount_spent,
            'original_amount_spent' => $original_amount_spent,
            'total_spent' => $amount_spent, // total_spent 使用与 amount_spent 相同的值
            'balance' => $balance,
            'currency' => $resp['currency']
        ]);

        $fbAdAccount->save();

        Log::info("--- Sync Fb Ad Account spend info completed: {$fbAdAccount->source_id} ---");
    }

    /**
     * 批量让广告账户订阅App
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribe_app(Request $request)
    {
        $request->validate([
            'ad_account_source_ids' => 'required|array|min:1',
            'ad_account_source_ids.*' => 'string',
            'app_source_id' => 'required|string',
            'fb_api_token_id' => 'required|string|exists:fb_api_tokens,id'
        ]);

        $adAccountSourceIds = $request->input('ad_account_source_ids');
        $appSourceId = $request->input('app_source_id');
        $fbApiTokenId = $request->input('fb_api_token_id');

        $user = auth()->user();

        // 获取 API Token
        $fbApiToken = FbApiToken::find($fbApiTokenId);
        if (!$fbApiToken || !$fbApiToken->active) {
            return response()->json([
                'message' => 'API Token 不存在或未激活',
                'success' => false
            ], 404);
        }

        // 获取 App
        $fbApp = FbApp::where('source_id', $appSourceId)->first();
        if (!$fbApp) {
            return response()->json([
                'message' => 'App 不存在',
                'success' => false
            ], 404);
        }

        // 获取所有广告账户
        $fbAdAccounts = FbAdAccount::whereIn('source_id', $adAccountSourceIds)->get();

        if ($fbAdAccounts->count() !== count($adAccountSourceIds)) {
            return response()->json([
                'message' => '部分广告账户不存在',
                'success' => false
            ], 404);
        }

        // 检查用户对所有广告账户的权限
        foreach ($fbAdAccounts as $fbAdAccount) {
            if (!$user->can('operate', $fbAdAccount)) {
                return response()->json([
                    'message' => "没有权限操作广告账户: {$fbAdAccount->source_id}",
                    'success' => false
                ], 403);
            }
        }

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        // 循环处理每个广告账户
        foreach ($fbAdAccounts as $fbAdAccount) {
            try {
                // 调用 Facebook API 进行订阅
                $success = $this->subscribeAdAccountToApp($fbAdAccount, $fbApp, $fbApiToken);

                if ($success) {
                    // 成功时在数据库中建立关联关系
                    $fbAdAccount->subscribedApps()->syncWithoutDetaching([$fbApp->id]);
                    $successCount++;

                    $results[] = [
                        'ad_account_source_id' => $fbAdAccount->source_id,
                        'success' => true,
                        'message' => '订阅成功'
                    ];
                } else {
                    $failedCount++;
                    $results[] = [
                        'ad_account_source_id' => $fbAdAccount->source_id,
                        'success' => false,
                        'message' => 'Facebook API 调用失败'
                    ];
                }

            } catch (\Exception $e) {
                $failedCount++;
                Log::error("Subscribe app failed for ad account {$fbAdAccount->source_id}: " . $e->getMessage());

                $results[] = [
                    'ad_account_source_id' => $fbAdAccount->source_id,
                    'success' => false,
                    'message' => '订阅失败: ' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => "处理完成，成功: {$successCount}，失败: {$failedCount}",
            'success' => $successCount > 0,
            'data' => [
                'app_source_id' => $appSourceId,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'results' => $results
            ]
        ]);
    }

    /**
     * 调用 Facebook API 让广告账户订阅App
     *
     * @param FbAdAccount $fbAdAccount
     * @param FbApp $fbApp
     * @param FbApiToken $fbApiToken
     * @return bool
     * @throws \Exception
     */
    private function subscribeAdAccountToApp(FbAdAccount $fbAdAccount, FbApp $fbApp, FbApiToken $fbApiToken): bool
    {
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/subscribed_apps";

        $body = [
            'app_id' => $fbApp->source_id,
        ];

        Log::info("Subscribing ad account {$fbAdAccount->source_id} to app {$fbApp->source_id}");

        Log::info(['endpoint' => $endpoint, 'token' => $fbApiToken->token, 'body' => $body]);
        $resp = FbUtils::makeRequest(null, $endpoint, null, 'POST', $body, '', $fbApiToken->token);

        if ($resp['success'] && isset($resp['success']) && $resp['success'] === true) {
            Log::info("Successfully subscribed ad account {$fbAdAccount->source_id} to app {$fbApp->source_id}");
            return true;
        } else {
            Log::warning("Failed to subscribe ad account {$fbAdAccount->source_id} to app {$fbApp->source_id}");
            Log::warning("Response: " . json_encode($resp));
            return false;
        }
    }
}
