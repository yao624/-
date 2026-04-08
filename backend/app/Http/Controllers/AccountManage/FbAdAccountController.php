<?php

namespace App\Http\Controllers\AccountManage;

use App\Http\Controllers\Controller;
use App\Http\Resources\FbAdAccountResource;
use App\Http\Resources\FbAdAccountResourceMode2;
use App\Http\Resources\FbAdAccountWithCampaignResource;
use App\Jobs\FacebookSetAccountSpendCap;
use App\Jobs\TriggerFacebookFetchApiResource;
use App\Models\FbAdAccount;
use App\Models\FbApiToken;
use App\Models\FbApp;
use App\Models\FbBm;
use App\Models\FbPixel;
use App\Models\Tag;
use App\Traits\ApiResponse;
use App\Utils\CurrencyUtils;
use App\Utils\FbUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FbAdAccountController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
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

        $fbAdAccountTags = $request->query('ad_account_tags', []);
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

        $fbAdAccounts = FbAdAccount::search($searchableFields);
        $admin = auth()->user()->hasRole('admin');
        $userId = auth()->id();

        if (!$admin) {
            $fbAdAccounts = $fbAdAccounts
                ->whereHas('fbAccounts', fn($q) => $q->where('fb_accounts.user_id', $userId))
                ->orWhereHas('users', fn($q) => $q->where('users.id', $userId))
                ->with(['tags' => fn($q) => $q->where('tags.user_id', $userId)]);
        }

        $this->applyIndexFilters($fbAdAccounts, $request, [
            'fbAdAccountTags' => $fbAdAccountTags,
            'fbAccountTags' => $fbAccountTags,
            'systemUserIds' => $systemUserIds,
            'campaignNames' => $campaignNames,
            'campaignTags' => $campaignTags,
            'others' => $others,
            'userId' => $userId,
            'admin' => $admin,
            'apiTokenIds' => $apiTokenIds,
            'fbBusinessUserName' => $fbBusinessUserName,
            'fbBusinessUserEmail' => $fbBusinessUserEmail,
        ]);

        $fbAdAccounts = $fbAdAccounts
            ->with(['fbAccounts', 'fbBms', 'fbBusinessUsers', 'fbPixels', 'fbBms.fbAdAccounts'])
            ->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        $resourceCollection = match (true) {
            $withCampaign => FbAdAccountWithCampaignResource::collection($fbAdAccounts->items()),
            $request->query('mode') && $request->query('mode') > 1 => FbAdAccountResourceMode2::collection($fbAdAccounts->items()),
            default => FbAdAccountResource::collection($fbAdAccounts->items()),
        };

        return $this->success([
            'data' => $resourceCollection,
            'pageSize' => $fbAdAccounts->perPage(),
            'pageNo' => $fbAdAccounts->currentPage(),
            'totalPage' => $fbAdAccounts->lastPage(),
            'totalCount' => $fbAdAccounts->total(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, FbAdAccount $fbAdAccount): JsonResponse
    {
        if ($request->query('with-campaign')) {
            return $this->success(new FbAdAccountWithCampaignResource($fbAdAccount));
        }

        $with = ['fbAccounts', 'fbBms', 'fbBusinessUsers', 'fbPixels'];
        if ($request->boolean('with-apps')) {
            $with[] = 'subscribedApps';
        }

        return $this->success(new FbAdAccountResource($fbAdAccount->load($with)));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbAdAccount $fbAdAccount): JsonResponse
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'notes' => 'string|nullable',
            'tags' => 'array|distinct|nullable'
        ]);

        $fbAdAccount->update($validated);
        $this->syncTags($fbAdAccount, $validated['tags'] ?? [], $userId, true);

        return $this->success(
            new FbAdAccountResource($fbAdAccount->load(['fbAccounts', 'fbBms', 'fbBusinessUsers', 'tags'])),
            '更新成功'
        );
    }

    /**
     * Archive ad accounts.
     */
    public function archive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id'
        ]);

        $user = $request->user();

        foreach ($validated['ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail('Unauthorized action.', 403);
            }
        }

        FbAdAccount::whereIn('id', $validated['ids'])->update(['is_archived' => true]);

        return $this->success(null, 'Accounts archived successfully.');
    }

    /**
     * Unarchive ad accounts.
     */
    public function unarchive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id'
        ]);

        $user = $request->user();

        foreach ($validated['ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail('Unauthorized action.', 403);
            }
        }

        FbAdAccount::whereIn('id', $validated['ids'])->update(['is_archived' => false]);

        return $this->success(null, 'Accounts unarchived successfully.');
    }

    /**
     * Enable rule for ad accounts.
     */
    public function enableRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
        ]);

        $user = $request->user();

        foreach ($validated['ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail('Unauthorized action.', 403);
            }
        }

        FbAdAccount::whereIn('id', $validated['ids'])->update(['enable_rule' => true]);

        return $this->success(null, 'Operation successfully.');
    }

    /**
     * Disable rule for ad accounts.
     */
    public function disableRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id'
        ]);

        $user = $request->user();

        foreach ($validated['ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail('Unauthorized action.', 403);
            }
        }

        FbAdAccount::whereIn('id', $validated['ids'])->update(['enable_rule' => false]);

        return $this->success(null, 'Operation successfully.');
    }

    /**
     * Sync API resources (admin only).
     */
    public function syncApiResource(Request $request): JsonResponse
    {
        $cleanData = $request->validate([
            'ids' => 'array',
            'ids.*' => 'string|exists:fb_api_tokens,id'
        ]);

        TriggerFacebookFetchApiResource::dispatch($cleanData['ids'])->onQueue('frontend');

        return $this->success(null, trans('message.task_submitted', [], $this->language));
    }

    /**
     * Assign users to ad accounts.
     */
    public function assignUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'string|exists:users,id',
            'ad_account_ids' => 'required|array',
            'ad_account_ids.*' => 'string|exists:fb_ad_accounts,id',
        ]);

        foreach ($validated['ad_account_ids'] as $adAccountId) {
            $adAccount = FbAdAccount::find($adAccountId);
            if ($adAccount) {
                $adAccount->users()->syncWithoutDetaching($validated['user_ids']);
            }
        }

        return $this->success(null, 'success');
    }

    /**
     * Remove users from ad accounts.
     */
    public function removeUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'string|exists:users,id',
            'ad_account_ids' => 'required|array',
            'ad_account_ids.*' => 'string|exists:fb_ad_accounts,id',
        ]);

        foreach ($validated['ad_account_ids'] as $adAccountId) {
            $adAccount = FbAdAccount::find($adAccountId);
            if ($adAccount) {
                $adAccount->users()->detach($validated['user_ids']);
            }
        }

        return $this->success(null, 'success');
    }

    /**
     * Toggle auto sync for ad accounts.
     */
    public function autoSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
            'on' => 'required|boolean'
        ]);

        $user = $request->user();

        foreach ($validated['ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail('Unauthorized action.', 403);
            }
        }

        FbAdAccount::whereIn('id', $validated['ids'])->update(['auto_sync' => $validated['on']]);

        return $this->success(null, 'Operation successfully.');
    }

    /**
     * Set account spend cap.
     */
    public function setAccountSpendCap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
            'cap_type' => 'required|string|in:reset,remove,amount',
            'cap_value' => 'numeric'
        ]);

        $capType = $validated['cap_type'];
        $capValue = $request->get('cap_value', 0);
        $user = $request->user();

        foreach ($validated['ids'] as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail('Unauthorized action.', 403);
            }
        }

        $accs = FbAdAccount::query()->whereIn('id', $validated['ids'])->get();
        foreach ($accs as $index => $acc) {
            FacebookSetAccountSpendCap::dispatch($acc->source_id, $capType, $capValue)
                ->delay($index * 5)
                ->onQueue('frontend');
        }

        return $this->success(null, 'Task submitted');
    }

    /**
     * Update filters for ad accounts.
     */
    public function updateFilters(Request $request): JsonResponse
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

        $user = $request->user();

        foreach ($request->get('ids') as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail('Unauthorized action.', 403);
            }
        }

        FbAdAccount::whereIn('id', $request->get('ids'))->update(['filters' => $request->get('filters')]);

        return $this->success(null, '过滤条件更新成功');
    }

    /**
     * Clear filters for ad accounts.
     */
    public function clearFilters(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id'
        ]);

        $user = $request->user();

        foreach ($request->get('ids') as $id) {
            $fbAdAccount = FbAdAccount::findOrFail($id);
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail('Unauthorized action.', 403);
            }
        }

        FbAdAccount::whereIn('id', $request->get('ids'))->update(['filters' => null]);

        return $this->success(null, '过滤条件清空成功');
    }

    /**
     * Toggle is_topup field for FB Ad Accounts.
     */
    public function toggleTopup(Request $request): JsonResponse
    {
        $request->validate([
            'source_ids' => 'required|array|min:1',
            'source_ids.*' => 'required|string',
            'value' => 'required|boolean'
        ]);

        $sourceIds = $request->input('source_ids');
        $value = $request->input('value');
        $user = auth()->user();

        $fbAdAccounts = FbAdAccount::whereIn('source_id', $sourceIds)->get();

        if ($fbAdAccounts->count() !== count($sourceIds)) {
            $foundSourceIds = $fbAdAccounts->pluck('source_id')->toArray();
            $notFoundSourceIds = array_diff($sourceIds, $foundSourceIds);

            return $this->fail('部分广告账户未找到', 404, [
                'not_found_source_ids' => $notFoundSourceIds
            ]);
        }

        foreach ($fbAdAccounts as $fbAdAccount) {
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail("没有权限操作广告账户: {$fbAdAccount->source_id}", 403);
            }
        }

        $updatedCount = FbAdAccount::whereIn('source_id', $sourceIds)->update(['is_topup' => $value]);

        return $this->success([
            'updated_count' => $updatedCount,
            'value' => $value,
            'source_ids' => $sourceIds
        ], 'is_topup 字段更新成功');
    }

    /**
     * Sync single ad account from Facebook API.
     */
    public function syncAdAccount(Request $request): JsonResponse
    {
        $request->validate([
            'source_id' => 'required|string'
        ]);

        $sourceId = $request->input('source_id');
        $user = auth()->user();

        $fbAdAccount = FbAdAccount::where('source_id', $sourceId)->first();

        if (!$fbAdAccount) {
            return $this->fail('广告账户未找到', 404);
        }

        if (!$user->can('operate', $fbAdAccount)) {
            return $this->fail('没有权限操作该广告账户', 403);
        }

        try {
            $this->syncAdAccountFromFacebook($fbAdAccount);
            $fbAdAccount->refresh();

            return $this->success([
                'data' => new FbAdAccountResource($fbAdAccount)
            ], '广告账户同步成功');

        } catch (\Exception $e) {
            Log::error('Sync ad account failed: ' . $e->getMessage());
            return $this->fail('同步广告账户失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Sync account spend info only (lightweight version).
     */
    public function syncAccountSpendInfo(Request $request): JsonResponse
    {
        $request->validate([
            'source_id' => 'required|string'
        ]);

        $sourceId = $request->input('source_id');
        $user = auth()->user();

        $fbAdAccount = FbAdAccount::where('source_id', $sourceId)->first();

        if (!$fbAdAccount) {
            return $this->fail('广告账户未找到', 404);
        }

        if (!$user->can('operate', $fbAdAccount)) {
            return $this->fail('没有权限操作该广告账户', 403);
        }

        try {
            $this->syncAccountSpendInfoFromFacebook($fbAdAccount);
            $fbAdAccount->refresh();

            return $this->success([
                'data' => new FbAdAccountResource($fbAdAccount)
            ], '广告账户消费信息同步成功');

        } catch (\Exception $e) {
            Log::error('Sync account spend info failed: ' . $e->getMessage());
            return $this->fail('同步广告账户消费信息失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Subscribe ad accounts to an app.
     */
    public function subscribeApp(Request $request): JsonResponse
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

        $fbApiToken = FbApiToken::find($fbApiTokenId);
        if (!$fbApiToken || !$fbApiToken->active) {
            return $this->fail('API Token 不存在或未激活', 404);
        }

        $fbApp = FbApp::where('source_id', $appSourceId)->first();
        if (!$fbApp) {
            return $this->fail('App 不存在', 404);
        }

        $fbAdAccounts = FbAdAccount::whereIn('source_id', $adAccountSourceIds)->get();

        if ($fbAdAccounts->count() !== count($adAccountSourceIds)) {
            return $this->fail('部分广告账户不存在', 404);
        }

        foreach ($fbAdAccounts as $fbAdAccount) {
            if (!$user->can('operate', $fbAdAccount)) {
                return $this->fail("没有权限操作广告账户: {$fbAdAccount->source_id}", 403);
            }
        }

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($fbAdAccounts as $fbAdAccount) {
            try {
                $success = $this->subscribeAdAccountToApp($fbAdAccount, $fbApp, $fbApiToken);

                if ($success) {
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

        return $this->success([
            'app_source_id' => $appSourceId,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'results' => $results
        ], "处理完成，成功: {$successCount}，失败: {$failedCount}");
    }

    /**
     * Apply filters for index method.
     */
    private function applyIndexFilters($query, Request $request, array $options): void
    {
        extract($options);

        if ($request->get('account_status')) {
            $query->whereIn('account_status', $request->get('account_status'));
        }

        if ($apiTokenIds) {
            $query->whereHas('apiTokens', fn($q) => $q->whereIn('fb_api_tokens.id', $apiTokenIds));
        }

        if ($fbAdAccountTags) {
            if (!$admin) {
                $query->whereHas('tags', fn($q) => $q
                    ->where('tags.user_id', $userId)
                    ->whereIn('tags.name', $fbAdAccountTags));
            } else {
                $query->whereHas('tags', fn($q) => $q->whereIn('tags.name', $fbAdAccountTags));
            }
        }

        if ($fbAccountTags) {
            $query->whereHas('fbAccounts.tags', fn($q) => $q
                ->where('tags.user_id', $userId)
                ->whereIn('tags.name', $fbAccountTags));
        }

        if ($systemUserIds) {
            $query->whereHas('fbAccounts', fn($q) => $q->whereIn('user_id', $systemUserIds))
                ->orWhereHas('users', fn($q) => $q->whereIn('users.id', $systemUserIds));
        }

        if ($request->get('enable_rule')) {
            $query->where('enable_rule', filter_var($request->get('enable_rule'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->get('is_archived')) {
            $query->where('is_archived', filter_var($request->get('is_archived'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->get('auto_sync')) {
            $query->where('auto_sync', filter_var($request->get('auto_sync'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($keywords = $request->get('keywords')) {
            $query->where(function ($q) use ($keywords) {
                $searchTerm = '%' . $keywords . '%';
                $q->where('name', 'LIKE', $searchTerm)
                    ->orWhere('source_id', 'LIKE', $searchTerm);
            });
        }

        if ($fbBusinessUserName || $fbBusinessUserEmail) {
            $query->whereHas('fbBusinessUsers', function ($q) use ($fbBusinessUserName, $fbBusinessUserEmail) {
                $q->where('fb_business_users.name', 'LIKE', "%{$fbBusinessUserName}%")
                    ->where('fb_business_users.email', 'LIKE', "%{$fbBusinessUserEmail}%");
            });
        }

        if ($request->get('ad_account_ids')) {
            $query->whereIn('source_id', $request->get('ad_account_ids'));
        }

        if ($adAccountNames = $request->get('ad_account_names')) {
            $query->where(function ($q) use ($adAccountNames) {
                foreach ($adAccountNames as $name) {
                    $q->orWhere('name', 'LIKE', '%' . $name . '%');
                }
            });
        }

        if ($fbAccountIds = $request->get('account_ids')) {
            $query->whereHas('fbAccounts', fn($q) => $q->whereIn('fb_accounts.source_id', $fbAccountIds));
        }

        if ($fbAccountNames = $request->get('account_names')) {
            $query->whereHas('fbAccounts', function ($q) use ($fbAccountNames) {
                $q->where(function ($innerQ) use ($fbAccountNames) {
                    foreach ($fbAccountNames as $name) {
                        $innerQ->orWhere('fb_accounts.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        if ($bmIds = $request->get('bm_ids')) {
            $query->whereHas('fbBms', fn($q) => $q->whereIn('fb_bms.source_id', $bmIds));
        }

        if ($fbBmNames = $request->get('bm_names')) {
            $query->whereHas('fbBms', function ($q) use ($fbBmNames) {
                $q->where(function ($innerQ) use ($fbBmNames) {
                    foreach ($fbBmNames as $name) {
                        $innerQ->orWhere('fb_bms.name', 'LIKE', '%' . $name . '%');
                    }
                });
            });
        }

        if ($campaignNames || $campaignTags) {
            $query->whereHas('fbCampaigns', function ($q) use ($campaignNames, $campaignTags, $userId) {
                $q->where(function ($innerQ) use ($campaignNames, $campaignTags, $userId) {
                    if ($campaignNames) {
                        Log::debug("filter campaign names:");
                        Log::debug($campaignNames);
                        foreach ($campaignNames as $name) {
                            $innerQ->orWhere('name', 'LIKE', '%' . $name . '%');
                        }
                    }

                    if ($campaignTags) {
                        $innerQ->whereHas('tags', fn($tq) => $tq
                            ->where('tags.user_id', $userId)
                            ->whereIn('name', $campaignTags));
                    }
                });
            });
        }

        if ($childSourceIds = $request->get('child_source_ids')) {
            $query->where(function ($q) use ($childSourceIds) {
                $q->whereHas('fbCampaigns', fn($sq) => $sq->whereIn('fb_campaigns.source_id', $childSourceIds))
                    ->orWhereHas('fbCampaigns.fbAdsets', fn($sq) => $sq->whereIn('fb_adsets.source_id', $childSourceIds))
                    ->orWhereHas('fbAds', fn($sq) => $sq->whereIn('fb_ads.source_id', $childSourceIds));
            });
        }

        if ($cards = $request->get('cards')) {
            $query->where(function ($q) use ($cards) {
                foreach ($cards as $card) {
                    $q->orWhere('default_funding', 'LIKE', '%' . $card . '%');
                }
            });
        }
    }

    /**
     * Sync tags for the ad account.
     */
    private function syncTags(FbAdAccount $fbAdAccount, array $tagNames, int $userId, bool $isUpdate = false): void
    {
        $tagNames = collect($tagNames)->unique();

        if ($isUpdate) {
            $allTags = $fbAdAccount->tags()->where('tags.user_id', $userId)->pluck('name');
            $existingTags = Tag::query()->where('tags.user_id', $userId)->whereHas('fbAdAccounts')->whereIn('name', $tagNames)->pluck('name');
            $newTags = $tagNames->diff($existingTags);
            $toDeletedTags = $allTags->diff($tagNames);

            foreach ($existingTags as $tagName) {
                $tag = Tag::query()->where('tags.user_id', $userId)->whereHas('fbAdAccounts')->where('name', $tagName)->first();
                if ($tag && !$fbAdAccount->tags->contains($tag->id)) {
                    $fbAdAccount->tags()->attach($tag->id, ['user_id' => $userId]);
                }
            }

            foreach ($newTags as $tagName) {
                $tag = Tag::query()->firstOrCreate(['name' => $tagName, 'user_id' => $userId]);
                $fbAdAccount->tags()->attach($tag->id, ['user_id' => $userId]);
            }

            foreach ($toDeletedTags as $tagName) {
                $tag = Tag::query()->where('user_id', $userId)->whereHas('fbAdAccounts')->where('name', $tagName)->first();
                if ($tag) {
                    $fbAdAccount->tags()->detach($tag->id);
                }
            }
        } else {
            $existingTags = Tag::query()->where('user_id', $userId)->whereHas('fbAdAccounts')->whereIn('name', $tagNames)->pluck('name');
            $newTags = $tagNames->diff($existingTags);

            foreach ($existingTags as $tagName) {
                $tag = Tag::query()->where('user_id', $userId)->whereHas('fbAdAccounts')->where('name', $tagName)->first();
                $fbAdAccount->tags()->attach($tag->id, ['user_id' => $userId]);
            }

            foreach ($newTags as $tagName) {
                $tag = Tag::query()->firstOrCreate(['name' => $tagName, 'user_id' => $userId]);
                $fbAdAccount->tags()->attach($tag->id, ['user_id' => $userId]);
            }
        }
    }

    /**
     * Sync ad account data from Facebook.
     */
    private function syncAdAccountFromFacebook(FbAdAccount $fbAdAccount): void
    {
        Log::info("--- Sync Fb Ad Account info: {$fbAdAccount->source_id} ---");

        $token = '';
        $fbAccount = null;

        $apiToken = $fbAdAccount->apiTokens()->where('active', true)->first();
        if ($apiToken) {
            $token = $apiToken->token;
        } else {
            $fbAccount = $fbAdAccount->fbAccounts()->where('token_valid', true)->first();
            if (!$fbAccount) {
                throw new \Exception("No available API token or FB account for ad account: {$fbAdAccount->source_id}");
            }
        }

        $currency = $fbAdAccount->currency;

        $query = $token
            ? ['fields' => 'name,id,account_status,disable_reason,balance,amount_spent,timezone_name,timezone_id,currency,age,spend_cap,is_prepay_account,funding_source_details,owner,adspixels{id,name,is_unavailable,is_created_by_business,owner_business}']
            : ['fields' => 'name,id,adtrust_dsl,account_status,disable_reason,balance,amount_spent,business_restriction_reason,timezone_name,timezone_id,currency,age,max_billing_threshold,current_unbilled_spend,spend_cap,is_prepay_account,owner,adspixels{id,name,is_unavailable,is_created_by_business,owner_business}'];

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}";

        $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'GET', '', '', $token);

        $oldStatus = $fbAdAccount->account_status;
        $newHumanStatus = FbUtils::$FbAccountStatusMap[$resp['account_status']];
        if ($oldStatus != $newHumanStatus) {
            Log::info("Ad account status changed - Account: {$fbAdAccount->name}({$fbAdAccount->source_id}), Old: {$oldStatus}, New: {$newHumanStatus}");
        }

        $originalBalance = $resp['balance'];
        $balance = $originalBalance !== '0' ? CurrencyUtils::convert($originalBalance, $currency, 'USD', 2) : $originalBalance;

        $originalSpendCap = $resp['spend_cap'];
        $spendCap = $originalSpendCap !== '0' ? CurrencyUtils::convert($originalSpendCap, $currency, 'USD', 2) : $originalSpendCap;

        $originalAmountSpent = $resp['amount_spent'];
        $amountSpent = $originalAmountSpent !== '0' ? CurrencyUtils::convert($originalAmountSpent, $currency, 'USD', 2) : $originalAmountSpent;

        $fbAdAccount->update([
            'account_status' => FbUtils::$FbAccountStatusMap[$resp['account_status']] ?? "Unknown",
            'account_status_code' => $resp['account_status'],
            'age' => $resp['age'],
            'total_spent' => $amountSpent,
            'balance' => $balance,
            'original_balance' => $originalBalance,
            'amount_spent' => $amountSpent,
            'original_amount_spent' => $originalAmountSpent,
            'spend_cap' => $spendCap,
            'original_spend_cap' => $originalSpendCap,
            'currency' => $resp['currency'],
            'disable_reason' => FbUtils::$FbAdAccountDisableReasonMap[$resp['disable_reason']] ?? 'Unknown',
            'disable_reason_code' => $resp['disable_reason'],
            'name' => $resp['name'],
            'owner' => $resp['owner'],
            'timezone_id' => $resp['timezone_id'],
            'timezone_name' => $resp['timezone_name'],
            'is_prepay_account' => $resp['is_prepay_account']
        ]);

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
            $adtrustDsl = $resp['adtrust_dsl'];
            $originalAdtrustDsl = $resp['adtrust_dsl'];
            if ($originalAdtrustDsl != -1) {
                $adtrustDsl = CurrencyUtils::convert($originalAdtrustDsl, $currency, 'USD', 2);
            }
            $fbAdAccount['adtrust_dsl'] = $adtrustDsl;
            $fbAdAccount['original_adtrust_dsl'] = $originalAdtrustDsl;
        }
        if (isset($resp['business_restriction_reason'])) {
            $fbAdAccount['business_restriction_reason'] = $resp['business_restriction_reason'];
        }

        if (isset($resp['funding_source_details'])) {
            $fbAdAccount['funding_type'] = $resp['funding_source_details']['type'];
            $fbAdAccount['default_funding'] = $resp['funding_source_details']['display_string'];
        }

        $fbAdAccount->save();

        if (isset($resp['adspixels']) && isset($resp['adspixels']['data'])) {
            $this->syncAdAccountPixels($fbAdAccount, $resp['adspixels']['data']);
        }

        Log::info("--- Sync Fb Ad Account completed: {$fbAdAccount->source_id} ---");
    }

    /**
     * Sync ad account spend info from Facebook (lightweight version).
     */
    private function syncAccountSpendInfoFromFacebook(FbAdAccount $fbAdAccount): void
    {
        Log::info("--- Sync Fb Ad Account spend info: {$fbAdAccount->source_id} ---");

        $token = '';
        $fbAccount = null;

        $apiToken = $fbAdAccount->apiTokens()->where('active', true)->first();
        if ($apiToken) {
            $token = $apiToken->token;
        } else {
            $fbAccount = $fbAdAccount->fbAccounts()->where('token_valid', true)->first();
            if (!$fbAccount) {
                throw new \Exception("No available API token or FB account for ad account: {$fbAdAccount->source_id}");
            }
        }

        $currency = $fbAdAccount->currency;

        $query = ['fields' => 'name,account_status,spend_cap,amount_spent,balance,currency'];

        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}";

        $resp = FbUtils::makeRequest($fbAccount, $endpoint, $query, 'GET', '', '', $token);

        $oldStatus = $fbAdAccount->account_status;
        $newHumanStatus = FbUtils::$FbAccountStatusMap[$resp['account_status']];
        if ($oldStatus != $newHumanStatus) {
            Log::info("Ad account status changed - Account: {$fbAdAccount->name}({$fbAdAccount->source_id}), Old: {$oldStatus}, New: {$newHumanStatus}");
        }

        $originalBalance = $resp['balance'];
        $balance = $originalBalance !== '0' ? CurrencyUtils::convert($originalBalance, $currency, 'USD', 2) : $originalBalance;

        $originalSpendCap = $resp['spend_cap'];
        $spendCap = $originalSpendCap !== '0' ? CurrencyUtils::convert($originalSpendCap, $currency, 'USD', 2) : $originalSpendCap;

        $originalAmountSpent = $resp['amount_spent'];
        $amountSpent = $originalAmountSpent !== '0' ? CurrencyUtils::convert($originalAmountSpent, $currency, 'USD', 2) : $originalAmountSpent;

        $fbAdAccount->update([
            'name' => $resp['name'],
            'account_status' => FbUtils::$FbAccountStatusMap[$resp['account_status']] ?? "Unknown",
            'spend_cap' => $spendCap,
            'amount_spent' => $amountSpent,
            'original_amount_spent' => $originalAmountSpent,
            'total_spent' => $amountSpent,
            'balance' => $balance,
            'currency' => $resp['currency']
        ]);

        $fbAdAccount->save();

        Log::info("--- Sync Fb Ad Account spend info completed: {$fbAdAccount->source_id} ---");
    }

    /**
     * Sync ad account pixels.
     */
    private function syncAdAccountPixels(FbAdAccount $fbAdAccount, array $pixelsData): void
    {
        foreach ($pixelsData as $adpixel) {
            Log::debug("Syncing pixel id: {$adpixel['id']}");

            $fbPixel = FbPixel::query()->updateOrCreate(
                ['pixel' => $adpixel['id']],
                [
                    'name' => $adpixel['name'],
                    'is_created_by_business' => $adpixel['is_created_by_business'],
                    'is_unavailable' => $adpixel['is_unavailable'],
                    'owner_business' => $adpixel['owner_business'] ?? [],
                    'is_dataset' => $adpixel['is_consolidated_container'] ?? false
                ]
            );

            $fbPixel->fbAdAccounts()->syncWithoutDetaching([$fbAdAccount->id]);

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
     * Subscribe ad account to app via Facebook API.
     */
    private function subscribeAdAccountToApp(FbAdAccount $fbAdAccount, FbApp $fbApp, FbApiToken $fbApiToken): bool
    {
        $version = FbUtils::$API_Version;
        $endpoint = "https://graph.facebook.com/{$version}/act_{$fbAdAccount->source_id}/subscribed_apps";

        $body = ['app_id' => $fbApp->source_id];

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
