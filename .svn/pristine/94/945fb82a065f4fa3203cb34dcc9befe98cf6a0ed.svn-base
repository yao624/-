<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbAdTemplateResource;
use App\Models\FbAdAccount;
use App\Models\FbAdTemplate;
use App\Models\FbApiToken;
use App\Models\FbGeoLocationCache;
use App\Models\User;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

    class FbAdTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $userId = Auth::id();

        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'name' => $request->get('name'),
            'notes' => $request->get('notes'),
        ];

        $fbAdTemplate = FbAdTemplate::search($searchableFields);

        $fbAdTemplate = $fbAdTemplate->with('sharedWith')
            ->where(function ($query) {
                $query->where('user_id', auth()->id()) // 当前用户创建的 Material
                ->orWhereHas('sharedWith', function ($subQuery) {
                    $subQuery->where('user_id', auth()->id()); // 被分享给当前用户的 Material
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbAdTemplateResource::collection($fbAdTemplate->items()),
            'pageSize' => $fbAdTemplate->perPage(),
            'pageNo' => $fbAdTemplate->currentPage(),
            'totalPage' => $fbAdTemplate->lastPage(),
            'totalCount' => $fbAdTemplate->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store fb ad template');
        $cleanData = $request->validate([
            'name' => 'required|string',
            'adset_name' => 'string',
            'campaign_name' => 'string',
            'ad_name' => 'string',
            'notes' => 'nullable',
            'bid_strategy' => 'string|in:LOWEST_COST_WITHOUT_CAP,LOWEST_COST_WITH_BID_CAP,COST_CAP',
            'bid_amount' => '',
            'budget_level' => 'string|in:campaign,adset',
            'budget_type' => 'string|in:daily,lifetime',
            'budget' => 'string',
            'accelerated' => 'boolean|nullable',
            'objective' => 'string|in:OUTCOME_APP_PROMOTION,OUTCOME_AWARENESS,OUTCOME_ENGAGEMENT,OUTCOME_LEADS,OUTCOME_SALES,OUTCOME_TRAFFIC',
            'conversion_location' => 'string|in:APP,WEBSITE,INSTANT_FORMS',
            'optimization_goal' => 'string|in:APP_INSTALLS,LINK_CLICKS,OFFSITE_CONVERSIONS,LEAD_GENERATION,QUALITY_LEAD,REACH,POST_ENGAGEMENT,VALUE',
            'pixel_event' => ['string', 'nullable', Rule::in(config('fb_template_validator_conf.pixel_event'))],
            'advantage_plus_audience' => 'nullable|boolean',
            'genders' => 'numeric|in:0,1,2',
            'age_min' => 'numeric',
            'age_max' => 'required|numeric|lte:65|gte:age_min', // age_max 最大为 65，并且大于等于 age_min
            'primary_text' => 'nullable|string',
            'headline_text' => 'nullable|string',
            'description_text' => 'nullable|string',
            'countries_included' => 'nullable|array',
            'countries_included.*' => 'array',
            'countries_excluded' => 'nullable|array',
            'countries_excluded.*' => 'array',
            'regions_included' => 'nullable|array',
            'regions_included.*' => 'array',
            'regions_excluded' => 'nullable|array',
            'regions_excluded.*' => 'array',
            'cities_included' => 'nullable|array',
            'cities_included.*' => 'array',
            'cities_excluded' => 'nullable|array',
            'cities_excluded.*' => 'array',
            'locales' => 'nullable|array',
            'locales.*' => 'array',
            'interests' => 'nullable|array',
            'interests.*' => 'array',
            'publisher_platforms' => 'nullable|array',
            'publisher_platforms.*' => 'string|in:facebook,instagram,messenger,audience_network',
            'placement_mode' => 'nullable|string|in:advanced,manual',
            'facebook_positions' => 'nullable|array',
            'facebook_positions.*' => 'string',
            'instagram_positions' => 'nullable|array',
            'instagram_positions.*' => 'string',
            'messenger_positions' => 'nullable|array',
            'messenger_positions.*' => 'string',
            'audience_network_positions' => 'nullable|array',
            'audience_network_positions.*' => 'string',
            'device_platforms' => 'nullable|array',
            'device_platforms.*' => 'string|in:mobile,desktop',
            'user_os' => 'nullable|array',
            'user_os.*' => 'string',
            'wireless_carrier' => 'nullable|boolean',
            'call_to_action' => 'string',
            'url_params' => 'nullable|string'
        ]);

        $user = auth()->user();

        // 暂时不改前端，后端适应
        if (isset($cleanData['accelerated'])) {
            if ($cleanData['accelerated']) {
                $cleanData['accelerated'] = true;
            } else {
                $cleanData['accelerated'] = false;
            }
        } else {
            $cleanData['accelerated'] = false;
        }

        $fbAdTemplate = FbAdTemplate::create($cleanData);

        $fbAdTemplate->user()->associate($user);
        $fbAdTemplate->save();

        return new FbAdTemplateResource($fbAdTemplate);
    }

    /**
     * Display the specified resource.
     */
    public function show(FbAdTemplate $fbAdTemplate)
    {
        // 获取当前用户的 ID
        $currentUserId = auth()->id();

        // 检查当前用户是否是材料的创建者或被分享的用户
        if ($fbAdTemplate->user_id !== $currentUserId && !$fbAdTemplate->isSharedWith($currentUserId)) {
            // 如果用户没有权限，返回403 Forbidden
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return new FbAdTemplateResource($fbAdTemplate);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbAdTemplate $fbAdTemplate)
    {

        if ($fbAdTemplate->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 验证请求数据
//        $validatedData = $request->validate([
//            'name' => 'nullable',
//            'notes' => 'string',
//        ]);
        $validatedData = $request->validate([
            'name' => 'required|string',
            'adset_name' => 'string',
            'campaign_name' => 'string',
            'ad_name' => 'string',
            'notes' => 'nullable',
            'bid_strategy' => 'string|in:LOWEST_COST_WITHOUT_CAP,LOWEST_COST_WITH_BID_CAP,COST_CAP',
            'bid_amount' => '',
            'budget_level' => 'string|in:campaign,adset',
            'budget_type' => 'string|in:daily,lifetime',
            'budget' => 'string',
            'accelerated' => 'boolean',
            'objective' => 'string|in:OUTCOME_APP_PROMOTION,OUTCOME_AWARENESS,OUTCOME_ENGAGEMENT,OUTCOME_LEADS,OUTCOME_SALES,OUTCOME_TRAFFIC',
            'conversion_location' => 'string|in:APP,WEBSITE,INSTANT_FORMS',
            'optimization_goal' => 'string|in:APP_INSTALLS,LINK_CLICKS,OFFSITE_CONVERSIONS,LEAD_GENERATION,QUALITY_LEAD,REACH,POST_ENGAGEMENT,VALUE',
            'pixel_event' => ['string', 'nullable', Rule::in(config('fb_template_validator_conf.pixel_event'))],
            'advantage_plus' => 'nullable|boolean',
            'genders' => 'numeric|in:0,1,2',
            'age_min' => 'numeric',
            'age_max' => 'required|numeric|lte:65|gte:age_min', // age_max 最大为 65，并且大于等于 age_min
            'primary_text' => 'nullable|string',
            'headline_text' => 'nullable|string',
            'description_text' => 'nullable|string',
            'countries_included' => 'nullable|array',
            'countries_included.*' => 'array',
            'countries_excluded' => 'nullable|array',
            'countries_excluded.*' => 'array',
            'regions_included' => 'nullable|array',
            'regions_included.*' => 'array',
            'regions_excluded' => 'nullable|array',
            'regions_excluded.*' => 'array',
            'cities_included' => 'nullable|array',
            'cities_included.*' => 'array',
            'cities_excluded' => 'nullable|array',
            'cities_excluded.*' => 'array',
            'locales' => 'nullable|array',
            'locales.*' => 'array',
            'interests' => 'nullable|array',
            'interests.*' => 'array',
            'publisher_platforms' => 'nullable|array',
            'publisher_platforms.*' => 'string|in:facebook,instagram,messenger,audience_network',
            'placement_mode' => 'nullable|string|in:advanced,manual',
            'facebook_positions' => 'nullable|array',
            'facebook_positions.*' => 'string',
            'instagram_positions' => 'nullable|array',
            'instagram_positions.*' => 'string',
            'messenger_positions' => 'nullable|array',
            'messenger_positions.*' => 'string',
            'audience_network_positions' => 'nullable|array',
            'audience_network_positions.*' => 'string',
            'device_platforms' => 'nullable|array',
            'device_platforms.*' => 'string|in:mobile,desktop',
            'user_os' => 'nullable|array',
            'user_os.*' => 'string',
            'wireless_carrier' => 'nullable|boolean',
            'call_to_action' => 'string',
            'url_params' => 'nullable|string'
        ]);

        // 更新模型实例
        $fbAdTemplate->update($validatedData);

        // 返回资源
        return new FbAdTemplateResource($fbAdTemplate);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbAdTemplate $fbAdTemplate)
    {
        if ($fbAdTemplate->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $fbAdTemplate->delete();
        return response()->json(null, 204);
    }

    public function target_search(Request $request)
    {
        $cleanData = $request->validate([
            'q' => 'string|nullable',
            'location_types' => 'string|in:country,region,city',
        ]);

        $keyword = trim((string)($cleanData['q'] ?? ''));
        $locationType = (string)$cleanData['location_types'];

        // 按需求：先回源 Meta，再写库，再从数据库返回，不走“缓存命中即返回”。

        // Meta Targeting Search 要求 q 非空；空关键词不请求 Graph
        if ($keyword === '') {
            return response()->json([
                'data' => [],
                'cached' => false,
            ]);
        }

        // 与 Meta 广告创建 / BM Graph 使用同一套 Graph Token（MetaAdCreationBmGraphController）
        $accessToken = MetaAdCreationBmGraphController::defaultGraphAccessToken();
        $version = FbUtils::$API_Version;

        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36';
        $headers = [
            'User-Agent' => $user_agent,
        ];
        $baseHttp = Http::withHeaders($headers)->timeout(300)->connectTimeout(300);

        // location_types 须为 JSON 数组字符串，例如 ["country"]，见 Targeting Search / adgeolocation
        $query = [
            'q' => $keyword,
            'type' => 'adgeolocation',
            'location_types' => json_encode([$locationType]),
            'limit' => 50,
            'access_token' => $accessToken,
        ];
        Log::debug('fb_ad_templates targeting-search', [
            'location_type' => $locationType,
            'q_len' => strlen($keyword),
            'graph_version' => $version,
        ]);

        $endpoint = "https://graph.facebook.com/{$version}/search";
        $resp = $baseHttp->get($endpoint, $query);

        if ($resp->successful()) {
            // 2) 回源成功：写入/刷新缓存（TTL 30 天）
            $data = $resp->json('data', []);
            $normalizedRows = [];

            if (is_array($data) && count($data) > 0) {
                $now = Carbon::now();
                $expiresAt = $now->copy()->addDays(30);
                $upsertRows = [];

                foreach ($data as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $geoKey = $item['key'] ?? $item['id'] ?? null;
                    if ($geoKey === null) {
                        continue;
                    }
                    $geoKey = trim((string) $geoKey);
                    if ($geoKey === '') {
                        continue;
                    }
                    $name = $item['name'] ?? $geoKey;
                    $name = trim((string) $name);
                    if ($name === '') {
                        $name = $geoKey;
                    }

                    // 统一前端 normalizeGeoList 可识别的字段：key/id/name（兼容 id/code）
                    $normalizedRows[] = array_merge($item, [
                        'key' => $geoKey,
                        'id' => (isset($item['id']) && $item['id'] !== null && (string) $item['id'] !== '' ? (string) $item['id'] : $geoKey),
                        'name' => $name,
                    ]);

                    // upsert 走 Query Builder，不会应用模型的 json cast，必须显式编码，否则 MySQL 绑定报 Array to string conversion
                    try {
                        $rawJson = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    } catch (\JsonException $e) {
                        $rawJson = json_encode(['key' => $geoKey, 'name' => $name], JSON_UNESCAPED_UNICODE) ?: '{}';
                    }

                    $upsertRows[] = [
                        'location_type' => $locationType,
                        'geo_key' => $geoKey,
                        'name' => $name,
                        'raw' => $rawJson,
                        'last_fetched_at' => $now,
                        'expires_at' => $expiresAt,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ];
                }

                if (count($upsertRows) > 0) {
                    try {
                        FbGeoLocationCache::query()->upsert(
                            $upsertRows,
                            ['location_type', 'geo_key'],
                            ['name', 'raw', 'last_fetched_at', 'expires_at', 'updated_at']
                        );
                    } catch (\Throwable $e) {
                        Log::warning('targeting_search: geo cache upsert skipped', [
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            }

            try {
                $now = Carbon::now();
                $limit = 50;
                $rows = FbGeoLocationCache::query()
                    ->where('location_type', $locationType)
                    ->where('expires_at', '>', $now)
                    ->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('geo_key', 'like', '%' . $keyword . '%');
                    })
                    ->orderByRaw('CASE WHEN geo_key = ? THEN 0 ELSE 1 END', [$keyword])
                    ->orderBy('name')
                    ->limit($limit)
                    ->get()
                    ->map(function (FbGeoLocationCache $c) {
                        $raw = is_array($c->raw) ? $c->raw : [];
                        $raw['key'] = $raw['key'] ?? $c->geo_key;
                        $raw['id'] = $raw['id'] ?? $c->geo_key;
                        $raw['name'] = $raw['name'] ?? $c->name;
                        return $raw;
                    })
                    ->values()
                    ->all();

                return response()->json([
                    'data' => $rows,
                    'cached' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('targeting_search: geo cache read-after-write skipped', [
                    'message' => $e->getMessage(),
                ]);

                // 表/模型未就绪时兜底直接返回回源数据，避免前端无结果。
                return response()->json([
                    'data' => $normalizedRows !== [] ? $normalizedRows : (is_array($data) ? $data : []),
                    'cached' => false,
                ]);
            }
        }

        $graph = $resp->json();
        Log::error('target_search_graph_failed', ['graph' => $graph]);

        return response()->json([
            'data' => [],
            'message' => 'target_search_graph_failed',
            'graph' => $graph,
        ]);

    }

    public function locale_search(Request $request)
    {
        $cleanData = $request->validate([
            'q' => 'string|nullable',
        ]);

        $fbApiToken = FbApiToken::query()->firstWhere('active', true);
        if (!$fbApiToken) {
            Telegram::sendMessage('locale search failed, no token active');
            return response()->json([
                'data' => []
            ]);
        }

        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36';
        $headers = [
            'User-Agent' => $user_agent,
        ];
        $baseHttp = Http::withHeaders($headers)->timeout(300)->connectTimeout(300);

        $query = [
            'q' => $cleanData['q'],
            'type' => 'adlocale',
            'access_token' => $fbApiToken['token']
        ];

        $endpoint = 'https://graph.facebook.com/v21.0/search';
        $resp = $baseHttp->get($endpoint, $query);

        if ($resp->successful()) {
            return $resp->json('data');
        } else {
            Log::error($resp->json());
            return response()->json([], 500);
        }
    }

    public function interests_search(Request $request)
    {
        $cleanData = $request->validate([
            'q' => 'string',
        ]);

        $fbApiToken = FbApiToken::query()->firstWhere('active', true);
        if (!$fbApiToken) {
            Telegram::sendMessage('interests search failed, no token active');
            return response()->json([
                'data' => []
            ]);
        }

        $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36';
        $headers = [
            'User-Agent' => $user_agent,
        ];
        $baseHttp = Http::withHeaders($headers)->timeout(300)->connectTimeout(300);

        $query = [
            'q' => $cleanData['q'],
            'type' => 'adinterest',
            'access_token' => $fbApiToken['token']
        ];

        $endpoint = 'https://graph.facebook.com/v21.0/search';
        $resp = $baseHttp->get($endpoint, $query);

        if ($resp->successful()) {
            return $resp->json('data');
        } else {
            Log::error($resp->json());
            return response()->json([], 500);
        }
    }

    /**
     * Meta Marketing API：GET /{ad-account-id}/targetingbrowse
     * 用于「浏览」人口统计 / 行为 / 兴趣分类树（扁平列表 + parent_key 下钻）。
     *
     * @see https://developers.facebook.com/docs/marketing-api/reference/ad-account/targetingbrowse/
     */
    public function targeting_browse(Request $request): JsonResponse
    {
        $clean = $request->validate([
            'targeting_category' => 'required|string|in:interests,behaviors,demographics',
            'parent_key' => 'nullable|string|max:512',
            'locale' => 'nullable|string|max:16',
            'fb_ad_account_id' => 'nullable|string|max:64',
            'act_ad_account_id' => 'nullable|string|max:64',
        ]);

        $actId = $this->resolveActAdAccountIdForTargeting($request);
        if ($actId === null) {
            return response()->json([
                'success' => false,
                'message' => __('请先选择广告账户，或传入 fb_ad_account_id / act_ad_account_id'),
                'data' => [],
            ], 422);
        }

        $token = MetaAdCreationBmGraphController::defaultGraphAccessToken();
        $version = FbUtils::$API_Version;

        $query = [
            'targeting_category' => $clean['targeting_category'],
            'access_token' => $token,
            'locale' => $clean['locale'] ?? 'zh_CN',
        ];
        if (! empty($clean['parent_key'])) {
            $query['parent_key'] = $clean['parent_key'];
        }

        $endpoint = "https://graph.facebook.com/{$version}/{$actId}/targetingbrowse";
        $resp = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; MetaAdCreation/1.0)',
        ])->timeout(120)->connectTimeout(30)->get($endpoint, $query);

        if ($resp->successful()) {
            $json = $resp->json();

            return response()->json([
                'success' => true,
                'data' => $json['data'] ?? [],
                'paging' => $json['paging'] ?? null,
            ]);
        }

        Log::warning('targeting_browse failed', [
            'status' => $resp->status(),
            'body' => $resp->json(),
            'act_id' => $actId,
        ]);

        return response()->json([
            'success' => false,
            'message' => $resp->json('error.message') ?? 'targeting_browse_failed',
            'data' => [],
            'graph' => $resp->json(),
        ], 400);
    }

    /**
     * Meta Marketing API：GET /{ad-account-id}/targetingsearch
     * 详细定位关键词搜索（可传 limit_type 限定兴趣/行为/人口统计等）。
     *
     * @see https://developers.facebook.com/docs/marketing-api/reference/ad-account/targetingsearch/
     */
    public function targeting_search_detailed(Request $request): JsonResponse
    {
        $clean = $request->validate([
            'q' => 'required|string|max:500',
            'limit_type' => 'nullable|string|max:64',
            'limit' => 'nullable|integer|min:1|max:500',
            'locale' => 'nullable|string|max:16',
            'fb_ad_account_id' => 'nullable|string|max:64',
            'act_ad_account_id' => 'nullable|string|max:64',
        ]);

        $actId = $this->resolveActAdAccountIdForTargeting($request);
        if ($actId === null) {
            return response()->json([
                'success' => false,
                'message' => __('请先选择广告账户，或传入 fb_ad_account_id / act_ad_account_id'),
                'data' => [],
            ], 422);
        }

        $token = MetaAdCreationBmGraphController::defaultGraphAccessToken();
        $version = FbUtils::$API_Version;

        $query = [
            'q' => $clean['q'],
            'access_token' => $token,
            'limit' => $clean['limit'] ?? 50,
            'locale' => $clean['locale'] ?? 'zh_CN',
        ];
        if (! empty($clean['limit_type'])) {
            $query['limit_type'] = $clean['limit_type'];
        }

        $endpoint = "https://graph.facebook.com/{$version}/{$actId}/targetingsearch";
        $resp = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; MetaAdCreation/1.0)',
        ])->timeout(120)->connectTimeout(30)->get($endpoint, $query);

        if ($resp->successful()) {
            $json = $resp->json();

            return response()->json([
                'success' => true,
                'data' => $json['data'] ?? [],
                'paging' => $json['paging'] ?? null,
            ]);
        }

        Log::warning('targeting_search_detailed failed', [
            'status' => $resp->status(),
            'body' => $resp->json(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $resp->json('error.message') ?? 'targeting_search_detailed_failed',
            'data' => [],
            'graph' => $resp->json(),
        ], 400);
    }

    /**
     * 解析 act_XXXX：优先 query act_ad_account_id，否则用租户库 fb_ad_accounts.source_id。
     */
    private function resolveActAdAccountIdForTargeting(Request $request): ?string
    {
        $act = $request->query('act_ad_account_id');
        if (is_string($act) && $act !== '') {
            return str_starts_with($act, 'act_') ? $act : 'act_'.ltrim($act, 'act_');
        }
        $fid = $request->query('fb_ad_account_id');
        if (is_string($fid) && $fid !== '') {
            $row = FbAdAccount::query()->find($fid);
            if ($row && $row->source_id) {
                $sid = (string) $row->source_id;

                return str_starts_with($sid, 'act_') ? $sid : 'act_'.$sid;
            }
        }

        return null;
    }

    public function share(Request $request)
    {
        $request->validate([
            'user_emails' => 'required|array',
            'user_emails.*' => 'exists:users,email', // 确保每个用户ID存在
            'resource_ids' => 'required|array',
            'resource_ids.*' => 'exists:fb_ad_templates,id', // 确保每个材料ID存在
        ]);

        $userIds = User::whereIn('email', $request->user_emails)->pluck('id');

        foreach ($request->resource_ids as $resourceId) {
            $resource = FbAdTemplate::findOrFail($resourceId);

            // 检查当前用户是否为该材料的拥有者
            if ($resource->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $resource->sharedWith()->syncWithoutDetaching($userIds);
        }

        return response()->json(['message' => 'Resource shared successfully.']);
    }

    public function unshare(Request $request)
    {
        $request->validate([
            'user_emails' => 'required|array',
            'user_emails.*' => 'exists:users,email', // 确保每个用户ID存在
            'resource_ids' => 'required|array',
            'resource_ids.*' => 'exists:fb_ad_templates,id', // 确保每个材料ID存在
        ]);

        $userIds = User::whereIn('email', $request->user_emails)->pluck('id');

        foreach ($request->resource_ids as $resourceId) {
            $resource = FbAdTemplate::findOrFail($resourceId);

            // 检查当前用户是否为该材料的拥有者
            if ($resource->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $resource->sharedWith()->detach($userIds);
        }

        return response()->json(['message' => 'Resource unshared successfully.']);
    }
}
