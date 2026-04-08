<?php

namespace App\Http\Controllers;

use App\Enums\EnumAdsetup;
use App\Enums\OperatorType;
use App\Http\Resources\FbAdResource;
use App\Http\Resources\FbAdsetResource;
use App\Jobs\FacebookCreateAd;
use App\Jobs\FacebookCreateCampaign;
use App\Jobs\FacebookCreateCampaignV2;
use App\Jobs\FacebookUpdateAdCreative;
use App\Models\AdLog;
use App\Models\FbAccount;
use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class FbAdController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->has('tags') ? explode(',', $request->get('tags')) : [];
        Log::debug($tagNames);

        $searchableFields = [
            'name' => $request->get('name'),
            'endpoint' => $request->get('endpoint'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $fbAds = FbAd::searchByTagNames($tagNames)->search($searchableFields)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbAdResource::collection($fbAds->items()),
            'pageSize' => $fbAds->perPage(),
            'pageNo' => $fbAds->currentPage(),
            'totalPage' => $fbAds->lastPage(),
            'totalCount' => $fbAds->total(),
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
    public function show(FbAd $fbAd)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbAd $fbAd)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbAd $fbAd)
    {
        //
    }

    public function quick_launch_ads(Request $request)
    {
        Log::info("quick launch ads");

        # TODO: 检查用户是否有 account，检查 account 是否有 ad account
        # TODO: validate 请求体

        $data = $request->json()->all();
//        Log::debug($data);

        // 遍历数组中的每个对象
        foreach ($data as $item) {
            $fbAccountID = $item['fb_account_id'];
            $fbAdAccountID = $item['fb_ad_account_id'];
            $fbAccount = FbAccount::query()->firstWhere('id', $fbAccountID);
            Log::debug($item);
            if ($fbAccount && $fbAccount->token_valid) {
                FacebookCreateCampaign::dispatch($fbAccountID, $fbAdAccountID, $item)->onQueue('facebook');
//                $test_data = [
//                    'account_id' => '01hsqwvm88danj2066mjmqc683',
//                    'ad_account_id' => '01hsqww1kefjkw99vjqp4z0bh0',
//                    'adset_id' => '120213880026900456',
//                    'item' => [
//                        'page_id' => '01hsqww0q20cdk39ycryq6wksy',
//                        'ad_name_tpl' => 'US CK KT | img',
//                        'material_id' => '01htads26d0ybb8qbxv7bs9vf7',
//                        'copywriting_id' => '01htadytq0h3h4xpqmb1941q9v',
//                        'link_id' => '01htae0y7c6cc9j60kg6k1vjd7'
//                    ]
//                ];
//                FacebookCreateAd::dispatch($test_data['account_id'], $test_data['ad_account_id'], $test_data['adset_id'], $test_data['item']);
            } else {
                Log::warning("accoount: {$fbAccountID} token invalid");
            }
        }

    }

    public function launch_ads_v2(Request $request)
    {
//        return [];
        $cleanData = $request->validate([
            '*.fb_ad_account_id' => 'required|string',
            '*.fb_ad_template_id' => 'required|string',
            '*.operator_type' => 'required|string|in:facebook-user,bm-user',
            '*.operator_id' => 'required|string',
            '*.options' => 'required|array',
            '*.options.launch_mode' => 'required|integer',
            '*.options.pixel_id' => 'string|nullable',
            '*.options.material_id_list' => 'array',
            '*.options.material_id_list.*' => 'string',
            '*.options.page_id' => 'required|string',
            '*.options.link_id' => 'required_without:*.options.post_id_list|string',
            '*.options.copywriting_id' => 'nullable|string',
            '*.options.form_id' => 'nullable|string',
            '*.options.post_id_list' => 'array',
            '*.options.post_id_list.*' => 'string',
            '*.options.product_set_ids' => 'array',
            '*.options.product_set_ids.*' => 'string',
            '*.options.campaign_id' => 'string',
            '*.options.adset_id' => 'string',
        ]);

        Log::debug($cleanData);

        // 首先检查素材个数，如果只有1个，就不检查 launch_mod
        // 如果素材个数大于1，同时 launch_mod = 1, 也就是 N-1-1 的方式，就创建多个 campaign job
        // 创建一个空的批处理
        $batch = Bus::batch([])->onQueue('facebook')->allowFailures()->dispatch();

        $batchJobs = [];
        foreach ($cleanData as $job) {
            Log::debug("campaign job: ", $job);

            $adLog = new AdLog([
                'user_id' => auth()->id(),
            ]);

            $adLog->fb_ad_account_id = $job['fb_ad_account_id'];
            $adLog->fb_ad_template_id = $job['fb_ad_template_id'];
            $adLog->operator_type = $job['operator_type'];
            if ($job['operator_type'] === OperatorType::BMUser->value) {
                $adLog->fb_api_token_id = $job['operator_id'];
            } elseif ($job['operator_type'] === OperatorType::FacebookUser->value) {
                $adLog->fb_account_id = $job['operator_id'];
            }
            $adLog->save();

            $options = $job['options'];
            $materials = collect($options['material_id_list'] ?? []); // 注意 为空的情况
            $posts = collect($options['post_id_list'] ?? []);
            $productSets = collect($options['product_set_ids'] ?? []);
            if ($materials->count() > 0) {
                $adSetup = EnumAdsetup::Material->value;
            } elseif ($posts->count() > 0) {
                $adSetup = EnumAdsetup::Post->value;
            } elseif ($productSets->count() > 0) {
                $adSetup = EnumAdsetup::Catalog->value;
            }

            $launchMode = $options['launch_mode'] ?? 3; // 1-1-N=3
//            $launchMode = 2;
            $adLog->launch_mode = $launchMode;

            if ($posts) {
                $adLog->post_source_id = implode(',', $posts->toArray());
            } else {
                $adLog->fb_pixel_id = $options['pixel_id'] ?? '';
                $adLog->fb_page_id = $options['page_id'] ?? '';
                $adLog->link_id = $options['link_id'] ?? '';
                $adLog->copywriting_id = $options['copywriting_id'] ?? '';
                $adLog->fb_page_form_id = $options['form_id'] ?? '';

                $adLog->materials()->sync($materials);
            }
            $adLog->save();

            // 如果有素材
            if ($adSetup === EnumAdsetup::Material->value) {
                Log::debug("launch campaign from material");
                // 如果有多个素材
                if ($materials->count() > 1) {
                    if ($launchMode === 1) {
                        foreach ($materials as $material) {
                            // 有多素材, launch 为 1, N-1-1
                            $job['options']['material_id'] = $material;
                            $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                                $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                        }
                    } else {
                        $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                            $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                    }
                } else {
                    // 只有一个素材的情况下，直接赋予 material_id 为 list 第一个，这里 launch mode 对广告结构不影响
                    Log::debug("only 1 material, so create 1 campaign");
                    $job['options']['material_id'] = $job['options']['material_id_list'][0];
                    $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                        $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                }
            } elseif ($adSetup === EnumAdsetup::Post->value) {
                Log::debug("launch campaign from old post");
                if ($posts->count() > 1) {
                    if ($launchMode === 1) {
                        Log::debug("multiple old posts, N-1-1");
                        foreach ($posts as $post_id) {
                            // 有多素材, launch 为 1, N-1-1
                            $job['options']['post_id'] = $post_id;
                            $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                                $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                        }
                    } else {
                        $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                            $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                    }
                } else {
                    // 只有一个Post id的情况下，直接赋予 post_id 为 list 第一个，这里 launch mode 对广告结构不影响
                    Log::debug("only 1 post, so create 1 campaign");
                    $job['options']['post_id'] = $posts[0];
                    $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                        $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                }

            } elseif ($adSetup === EnumAdsetup::Catalog->value) {
                Log::debug("launch campaign from catalog");
                if ($productSets->count() > 1) {
                    if ($launchMode === 1) {
                        Log::debug("multiple catalog, N-1-1");
                        foreach ($productSets as $product_set) {
                            // 有多素材, launch 为 1, N-1-1
                            $job['options']['product_set'] = $product_set;
                            $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                                $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                        }
                    } else {
                        $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                            $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                    }
                } else {
                    // 只有一个Post id的情况下，直接赋予 post_id 为 list 第一个，这里 launch mode 对广告结构不影响
                    Log::debug("only 1 product set, so create 1 campaign");
                    $job['options']['product_set'] = $productSets[0];
                    $batchJobs[] = new FacebookCreateCampaignV2($job['fb_ad_account_id'], $job['operator_type'],
                        $job['operator_id'], $job['fb_ad_template_id'], $job['options'], $adLog);
                }

            } else {
                Log::warning("should not be here");
            }
        }

        Bus::batch($batchJobs)->finally(function (Batch $batch) {
            Log::debug("finally, all campaigns batch are finished");
        })->onQueue('facebook')->allowFailures()->dispatch();

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }

    /**
     * 批量更新FB广告
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAds(Request $request)
    {
        Log::info("批量更新FB广告");

        // 验证请求数据
        $validatedData = $request->validate([
            '*.ad_account' => 'required|string',
            '*.id' => 'required|string',
            '*.payload' => 'required|array'
        ]);

        Log::debug('更新广告请求数据:', $validatedData);

        // 按ad_account分组，以便为每个账户的任务添加延迟
        $groupedByAccount = collect($validatedData)->groupBy('ad_account');

        $totalJobs = 0;

        foreach ($groupedByAccount as $adAccountId => $adUpdates) {
            Log::info("处理广告账户: {$adAccountId}, 任务数量: " . count($adUpdates));

            foreach ($adUpdates as $index => $adUpdateRequest) {
                $adAccountSourceId = $adUpdateRequest['ad_account'];
                $adSourceId = $adUpdateRequest['id'];
                $payload = $adUpdateRequest['payload'];

                // 为同一个ad account的每个任务添加30秒递增延迟
                $delaySeconds = $index * 30;

                try {
                    // 分派Job到队列，支持延迟执行
                    \App\Jobs\FacebookUpdateAdCreative::dispatch(
                        $adAccountSourceId,
                        $adSourceId,
                        $payload
                    )->delay(now()->addSeconds($delaySeconds))->onQueue('frontend');

                    $totalJobs++;

                    Log::info("已分派广告更新任务", [
                        'ad_account' => $adAccountSourceId,
                        'ad_id' => $adSourceId,
                        'delay_seconds' => $delaySeconds
                    ]);

                } catch (\Exception $e) {
                    Log::error("分派广告更新任务失败", [
                        'ad_account' => $adAccountSourceId,
                        'ad_id' => $adSourceId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        Log::info("批量更新广告任务分派完成", [
            'total_accounts' => count($groupedByAccount),
            'total_jobs' => $totalJobs
        ]);

        return response()->json([
            'success' => true,
            'message' => '广告更新任务已提交到队列',
            'data' => [
                'total_accounts' => count($groupedByAccount),
                'total_jobs' => $totalJobs,
                'message' => "已为 " . count($groupedByAccount) . " 个广告账户分派 {$totalJobs} 个更新任务，每个账户的任务间隔30秒执行"
            ]
        ], 200);
    }

    /**
     * 获取多语言广告的预览链接
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMultiLangPreview(Request $request)
    {
        Log::info("获取多语言广告预览链接");

        // 验证请求数据
        $validatedData = $request->validate([
            'ad_account' => 'required|string',  // FbAdAccount 的 source_id
            'ad_id' => 'required|string',       // FbAd 的 source_id
            'label_name' => 'required|string'   // 语言标签
        ]);

        Log::debug('预览请求数据:', $validatedData);

        try {
            $adAccountSourceId = $validatedData['ad_account'];
            $adId = $validatedData['ad_id'];
            $labelName = $validatedData['label_name'];

            // 处理特殊的语言标签映射
            if ($labelName === 'afrikaans') {
                $dynamicAssetLabel = 'af_ZA';
            } elseif ($labelName === 'swedish') {
                $dynamicAssetLabel = 'sv_SE';
            } else {
                $dynamicAssetLabel = $labelName;
            }

            // 根据 ad_account 查找 FbAdAccount
            $fbAdAccount = \App\Models\FbAdAccount::where('source_id', $adAccountSourceId)->first();
            if (!$fbAdAccount) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到指定的广告账户'
                ], 404);
            }

            // 查找 FbAd
            $fbAd = FbAd::where('source_id', $adId)->first();
            if (!$fbAd) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到指定的广告'
                ], 404);
            }

            // 检查是否为多语言广告
            $isMultiLanguageAd = false;
            $isCatalogAd = isset($fbAd->creative['product_set_id']) && !empty($fbAd->creative['product_set_id']);

            Log::debug('广告类型检查:', [
                'ad_id' => $adId,
                'is_catalog_ad' => $isCatalogAd,
                'has_product_set_id' => isset($fbAd->creative['product_set_id']),
                'product_set_id' => $fbAd->creative['product_set_id'] ?? null
            ]);

            if ($isCatalogAd) {
                // 目录广告：检查是否有 customization_rules_spec
                $isMultiLanguageAd = isset($fbAd->creative['object_story_spec']['template_data']['customization_rules_spec'])
                    && !empty($fbAd->creative['object_story_spec']['template_data']['customization_rules_spec']);

                Log::debug('目录广告多语言检查:', [
                    'ad_id' => $adId,
                    'has_template_data' => isset($fbAd->creative['object_story_spec']['template_data']),
                    'has_customization_rules_spec' => isset($fbAd->creative['object_story_spec']['template_data']['customization_rules_spec']),
                    'is_multi_language' => $isMultiLanguageAd
                ]);
            } else {
                // 普通广告：检查是否有 asset_feed_spec
                $isMultiLanguageAd = isset($fbAd->creative['asset_feed_spec']) && !empty($fbAd->creative['asset_feed_spec']);

                Log::debug('普通广告多语言检查:', [
                    'ad_id' => $adId,
                    'has_asset_feed_spec' => isset($fbAd->creative['asset_feed_spec']),
                    'is_multi_language' => $isMultiLanguageAd
                ]);
            }

            if (!$isMultiLanguageAd) {
                $adType = $isCatalogAd ? '目录广告' : '普通广告';
                Log::warning('非多语言广告尝试获取预览:', [
                    'ad_id' => $adId,
                    'ad_type' => $adType,
                    'label_name' => $labelName
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "该{$adType}不是多语言广告，无法获取多语言预览"
                ], 400);
            }

            // 获取 access_token，优先使用 token_type 为 1 的，如果没有则使用 token_type 为 3 的
            $apiToken = $fbAdAccount->apiTokens()
                ->where('active', true)
                ->where('token_type', 1)
                ->first();

            if (!$apiToken) {
                $apiToken = $fbAdAccount->apiTokens()
                    ->where('active', true)
                    ->where('token_type', 3)
                    ->first();
            }

            if (!$apiToken) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到有效的API令牌'
                ], 400);
            }

            // 构建 Facebook API 请求参数
            $version = \App\Utils\FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/act_{$adAccountSourceId}/generatepreviews";

            // 构建 creative 数据，区分普通广告和目录广告
            $creativeData = [];

            // 始终包含 object_story_spec
            if (isset($fbAd->creative['object_story_spec'])) {
                $creativeData['object_story_spec'] = $fbAd->creative['object_story_spec'];
            }

            if ($isCatalogAd) {
                // 目录广告：包含 product_set_id 和 template_url_spec
                if (isset($fbAd->creative['product_set_id'])) {
                    $creativeData['product_set_id'] = $fbAd->creative['product_set_id'];
                }

                if (isset($fbAd->creative['template_url_spec'])) {
                    $creativeData['template_url_spec'] = $fbAd->creative['template_url_spec'];
                }

                // 目录广告可能还有其他相关字段
                if (isset($fbAd->creative['call_to_action_type'])) {
                    $creativeData['call_to_action_type'] = $fbAd->creative['call_to_action_type'];
                }

                Log::debug('构建目录广告creative数据:', [
                    'ad_id' => $adId,
                    'has_product_set_id' => isset($creativeData['product_set_id']),
                    'has_template_url_spec' => isset($creativeData['template_url_spec']),
                    'has_call_to_action_type' => isset($creativeData['call_to_action_type'])
                ]);
            } else {
                // 普通广告：包含 asset_feed_spec
                if (isset($fbAd->creative['asset_feed_spec'])) {
                    $assetFeedSpec = $fbAd->creative['asset_feed_spec'];

                    // 处理 videos 字段，移除 thumbnail_hash
                    if (isset($assetFeedSpec['videos']) && is_array($assetFeedSpec['videos'])) {
                        foreach ($assetFeedSpec['videos'] as &$video) {
                            if (isset($video['thumbnail_hash'])) {
                                unset($video['thumbnail_hash']);
                            }
                        }
                    }

                    // 处理 images 字段，移除 image_url (如果是图片广告)
                    if (isset($assetFeedSpec['images']) && is_array($assetFeedSpec['images'])) {
                        foreach ($assetFeedSpec['images'] as &$image) {
                            if (isset($image['image_url'])) {
                                unset($image['image_url']);
                            }
                        }
                    }

                    $creativeData['asset_feed_spec'] = $assetFeedSpec;
                }

                Log::debug('构建普通广告creative数据:', [
                    'ad_id' => $adId,
                    'has_asset_feed_spec' => isset($creativeData['asset_feed_spec'])
                ]);
            }

            $query = [
                'ad_format' => 'DESKTOP_FEED_STANDARD',
                'dynamic_asset_label' => $dynamicAssetLabel,
                'creative' => json_encode($creativeData)
            ];

            Log::debug('Facebook API 请求参数:', $query);

            // 调用 Facebook API (使用 GET 请求)
            $response = \App\Utils\FbUtils::makeRequest(
                null,
                $endpoint,
                $query,
                'GET',
                null,
                '',
                $apiToken->token
            );

            if (!$response['success']) {
                Log::error('Facebook API 调用失败', ['response' => $response]);
                return response()->json([
                    'success' => false,
                    'message' => 'Facebook API 调用失败'
                ], 500);
            }

            // 提取 iframe src
            $responseData = $response->get('data', []);
            if (empty($responseData) || !isset($responseData[0]['body'])) {
                return response()->json([
                    'success' => false,
                    'message' => '未获取到预览数据'
                ], 500);
            }

            $body = $responseData[0]['body'];

            // 使用正则表达式提取 iframe src
            preg_match('/src="([^"]*)"/', $body, $matches);

            if (!isset($matches[1])) {
                return response()->json([
                    'success' => false,
                    'message' => '无法从响应中提取预览链接'
                ], 500);
            }

            $previewUrl = html_entity_decode($matches[1]); // 解码HTML实体

            Log::info("成功获取多语言广告预览链接", [
                'ad_account' => $adAccountSourceId,
                'ad_id' => $adId,
                'label_name' => $labelName,
                'url' => $previewUrl
            ]);

            return response()->json([
                'success' => true,
                'message' => '成功获取预览链接',
                'data' => [
                    'url' => $previewUrl
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("获取多语言广告预览失败", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '获取预览失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量为广告添加多语言
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addLanguagesToAds(Request $request)
    {
        Log::info("批量为广告添加多语言");

        // 验证请求数据
        $validatedData = $request->validate([
            'ad_source_ids' => 'required|array|min:1',
            'ad_source_ids.*' => 'required|string',
            'language_count' => 'required|integer|min:1|max:10', // 限制最多添加10种语言
        ]);

        Log::debug('批量添加语言请求数据:', $validatedData);

        $adSourceIds = $validatedData['ad_source_ids'];
        $languageCount = $validatedData['language_count'];

        try {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            // 按广告账户分组处理
            $groupedAds = [];

            foreach ($adSourceIds as $adSourceId) {
                // 查找广告
                $fbAd = FbAd::where('source_id', $adSourceId)->first();

                if (!$fbAd) {
                    $results[] = [
                        'ad_source_id' => $adSourceId,
                        'success' => false,
                        'message' => '未找到指定的广告'
                    ];
                    $failureCount++;
                    continue;
                }

                // 检查是否是多语言广告
                if (!FbAd::isMultiLanguageAd($fbAd)) {
                    $results[] = [
                        'ad_source_id' => $adSourceId,
                        'success' => false,
                        'message' => '该广告不是多语言广告'
                    ];
                    $failureCount++;
                    continue;
                }

                // 获取广告账户信息
                $adAccount = $fbAd->fbAdAccountV2;
                if (!$adAccount) {
                    $results[] = [
                        'ad_source_id' => $adSourceId,
                        'success' => false,
                        'message' => '无法获取广告账户信息'
                    ];
                    $failureCount++;
                    continue;
                }

                // 检查广告账户是否有有效的API Token
                if (!FbAd::hasValidApiToken($adAccount)) {
                    $results[] = [
                        'ad_source_id' => $adSourceId,
                        'success' => false,
                        'message' => '广告账户不满足条件'
                    ];
                    $failureCount++;
                    continue;
                }

                // 按广告账户分组
                $adAccountSourceId = $adAccount->source_id;
                if (!isset($groupedAds[$adAccountSourceId])) {
                    $groupedAds[$adAccountSourceId] = [];
                }
                $groupedAds[$adAccountSourceId][] = $fbAd;
            }

            // 为每个广告账户的广告添加语言
            foreach ($groupedAds as $adAccountSourceId => $ads) {
                $delaySeconds = 0;

                foreach ($ads as $fbAd) {
                    try {
                        // 为广告添加指定数量的语言
                        $newPayload = FbAd::addLanguageToAd($fbAd, $languageCount);

                        if (!$newPayload) {
                            $results[] = [
                                'ad_source_id' => $fbAd->source_id,
                                'success' => false,
                                'message' => '无法为广告添加新语言（可能是没有可用的新语言）'
                            ];
                            $failureCount++;
                            continue;
                        }

                        // 分派更新Creative的Job，为同一账户添加延迟
                        \App\Jobs\FacebookUpdateAdCreative::dispatch(
                            $adAccountSourceId,
                            $fbAd->source_id,
                            $newPayload
                        )->delay(now()->addSeconds($delaySeconds))->onQueue('facebook');

                        $results[] = [
                            'ad_source_id' => $fbAd->source_id,
                            'success' => true,
                            'message' => "已成功提交添加 {$languageCount} 种语言的任务" . ($delaySeconds > 0 ? "（延迟 {$delaySeconds} 秒执行）" : ''),
                            'delay_seconds' => $delaySeconds
                        ];
                        $successCount++;

                        // 为同一账户的下一个任务增加30秒延迟
                        $delaySeconds += 30;

                        Log::info("已为广告分派添加语言任务", [
                            'ad_id' => $fbAd->source_id,
                            'ad_account_id' => $adAccountSourceId,
                            'language_count' => $languageCount,
                            'delay_seconds' => $delaySeconds - 30
                        ]);

                    } catch (\Exception $e) {
                        $results[] = [
                            'ad_source_id' => $fbAd->source_id,
                            'success' => false,
                            'message' => '处理失败: ' . $e->getMessage()
                        ];
                        $failureCount++;

                        Log::error("为广告添加语言失败", [
                            'ad_id' => $fbAd->source_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info("批量添加语言任务完成", [
                'total_ads' => count($adSourceIds),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'language_count' => $languageCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "批量添加语言任务已提交",
                'data' => [
                    'total_ads' => count($adSourceIds),
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'language_count' => $languageCount,
                    'results' => $results
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("批量添加语言失败", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '批量添加语言失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量设置广告的自动添加多语言功能
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAutoAddLanguages(Request $request)
    {
        Log::info("批量设置广告的自动添加多语言功能");

        // 验证请求数据
        $validatedData = $request->validate([
            'ad_source_ids' => 'required|array|min:1',
            'ad_source_ids.*' => 'required|string',
            'auto_add_languages' => 'required|boolean', // true=开启，false=关闭
        ]);

        Log::debug('批量设置自动添加多语言请求数据:', $validatedData);

        $adSourceIds = $validatedData['ad_source_ids'];
        $autoAddLanguages = $validatedData['auto_add_languages'];

        try {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($adSourceIds as $adSourceId) {
                // 查找广告
                $fbAd = FbAd::where('source_id', $adSourceId)->first();

                if (!$fbAd) {
                    $results[] = [
                        'ad_source_id' => $adSourceId,
                        'success' => false,
                        'message' => '未找到指定的广告'
                    ];
                    $failureCount++;
                    continue;
                }

                // 如果要开启自动添加多语言，需要检查前置条件
                if ($autoAddLanguages) {
                    // 检查是否是多语言广告
                    if (!FbAd::isMultiLanguageAd($fbAd)) {
                        $results[] = [
                            'ad_source_id' => $adSourceId,
                            'success' => false,
                            'message' => '该广告不是多语言广告，无法开启自动添加多语言功能'
                        ];
                        $failureCount++;
                        continue;
                    }

                    // 检查广告账户是否有有效的API Token
                    $adAccount = $fbAd->fbAdAccountV2;
                    if (!$adAccount) {
                        $results[] = [
                            'ad_source_id' => $adSourceId,
                            'success' => false,
                            'message' => '无法获取广告账户信息'
                        ];
                        $failureCount++;
                        continue;
                    }

                    if (!FbAd::hasValidApiToken($adAccount)) {
                        $results[] = [
                            'ad_source_id' => $adSourceId,
                            'success' => false,
                            'message' => '广告账户不满足条件，无法开启自动添加多语言功能'
                        ];
                        $failureCount++;
                        continue;
                    }
                }

                try {
                    // 更新广告的auto_add_languages字段
                    $fbAd->auto_add_languages = $autoAddLanguages;
                    $fbAd->save();

                    $statusText = $autoAddLanguages ? '开启' : '关闭';
                    $results[] = [
                        'ad_source_id' => $adSourceId,
                        'success' => true,
                        'message' => "成功{$statusText}自动添加多语言功能",
                        'auto_add_languages' => $autoAddLanguages
                    ];
                    $successCount++;

                    Log::info("成功设置广告自动添加多语言功能", [
                        'ad_id' => $adSourceId,
                        'auto_add_languages' => $autoAddLanguages
                    ]);

                } catch (\Exception $e) {
                    $results[] = [
                        'ad_source_id' => $adSourceId,
                        'success' => false,
                        'message' => '设置失败: ' . $e->getMessage()
                    ];
                    $failureCount++;

                    Log::error("设置广告自动添加多语言功能失败", [
                        'ad_id' => $adSourceId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info("批量设置自动添加多语言功能完成", [
                'total_ads' => count($adSourceIds),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'auto_add_languages' => $autoAddLanguages
            ]);

            $statusText = $autoAddLanguages ? '开启' : '关闭';
            return response()->json([
                'success' => true,
                'message' => "批量{$statusText}自动添加多语言功能已完成",
                'data' => [
                    'total_ads' => count($adSourceIds),
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'auto_add_languages' => $autoAddLanguages,
                    'results' => $results
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("批量设置自动添加多语言功能失败", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '批量设置失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 批量拷贝Facebook广告
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function copyAds(Request $request)
    {
        Log::info("批量拷贝Facebook广告");

        // 验证请求数据
        $validatedData = $request->validate([
            'ads' => 'required|array',
            'ads.*.ad_id' => 'required|string',      // FbAd的source_id
            'ads.*.count' => 'required|integer|min:1|max:20', // 拷贝数量，限制最多20份
            'mode' => 'nullable|integer|in:1,2,3', // 拷贝模式：1=N-1-1，2=1-N-1，3=1-1-N，默认为1
        ]);

        Log::debug('批量拷贝广告请求数据:', $validatedData);

        try {
            $results = [];
            $totalJobs = 0;

            // 获取拷贝模式，默认为1（N-1-1）
            $mode = $request->input('mode', 1);

            // 按广告账户分组，以便为每个账户的任务添加延迟
            $groupedByAccount = [];

            // 先验证所有广告是否存在，并按广告账户分组
            foreach ($validatedData['ads'] as $copyRequest) {
                $adSourceId = $copyRequest['ad_id'];
                $count = $copyRequest['count'];

                // 查找广告
                $fbAd = FbAd::where('source_id', $adSourceId)->first();
                if (!$fbAd) {
                    $results[] = [
                        'ad_id' => $adSourceId,
                        'count' => $count,
                        'success' => false,
                        'message' => '未找到指定的广告'
                    ];
                    continue;
                }

                // 获取广告账户信息
                $adAccount = $fbAd->fbAdAccountV2;
                if (!$adAccount) {
                    $results[] = [
                        'ad_id' => $adSourceId,
                        'count' => $count,
                        'success' => false,
                        'message' => '无法获取广告账户信息'
                    ];
                    continue;
                }

                // 检查广告账户是否有有效的API Token
                $apiToken = $adAccount->apiTokens()
                    ->where('active', true)
                    ->where('token_type', 1)
                    ->first();

                if (!$apiToken) {
                    $results[] = [
                        'ad_id' => $adSourceId,
                        'count' => $count,
                        'success' => false,
                        'message' => '广告账户没有有效的API Token'
                    ];
                    continue;
                }

                // 按广告账户分组
                $adAccountSourceId = $adAccount->source_id;
                if (!isset($groupedByAccount[$adAccountSourceId])) {
                    $groupedByAccount[$adAccountSourceId] = [];
                }

                $groupedByAccount[$adAccountSourceId][] = [
                    'ad_source_id' => $adSourceId,
                    'count' => $count,
                    'fb_ad' => $fbAd
                ];
            }

            // 为每个广告账户的拷贝任务分派Job
            foreach ($groupedByAccount as $adAccountSourceId => $copyTasks) {
                $delaySeconds = 0;

                foreach ($copyTasks as $task) {
                    $adSourceId = $task['ad_source_id'];
                    $count = $task['count'];

                    try {
                        // 为每份拷贝创建一个Job，每个Job之间间隔20秒
                        for ($i = 0; $i < $count; $i++) {
                            \App\Jobs\ActionCopyFbAd::dispatch($adSourceId, 1, $mode, $i + 1)
                                ->delay(now()->addSeconds($delaySeconds))
                                ->onQueue('frontend');

                            $delaySeconds += 20; // 每个拷贝任务间隔20秒
                            $totalJobs++;
                        }

                        $results[] = [
                            'ad_id' => $adSourceId,
                            'count' => $count,
                            'success' => true,
                            'message' => "已成功提交 {$count} 个拷贝任务，每个任务间隔20秒执行",
                            'total_delay_seconds' => ($count - 1) * 20
                        ];

                        Log::info("已为广告分派拷贝任务", [
                            'ad_id' => $adSourceId,
                            'count' => $count,
                            'ad_account_id' => $adAccountSourceId,
                            'total_jobs' => $count
                        ]);

                    } catch (\Exception $e) {
                        $results[] = [
                            'ad_id' => $adSourceId,
                            'count' => $count,
                            'success' => false,
                            'message' => '提交拷贝任务失败: ' . $e->getMessage()
                        ];

                        Log::error("分派广告拷贝任务失败", [
                            'ad_id' => $adSourceId,
                            'count' => $count,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info("批量拷贝广告任务分派完成", [
                'total_accounts' => count($groupedByAccount),
                'total_jobs' => $totalJobs
            ]);

            return response()->json([
                'success' => true,
                'message' => '广告拷贝任务已提交到队列',
                'data' => [
                    'total_accounts' => count($groupedByAccount),
                    'total_jobs' => $totalJobs,
                    'results' => $results,
                    'note' => '每个广告账户的拷贝任务将按20秒间隔依次执行'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("批量拷贝广告失败", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '批量拷贝广告失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 将广告复制到多个指定的广告组
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function copyAdToAdsets(Request $request)
    {
        Log::info("将广告复制到多个广告组");

        // 验证请求数据
        $validatedData = $request->validate([
            'ad_source_id' => 'required|string',        // 要复制的广告ID
            'adset_source_ids' => 'required|array|min:1',  // 目标广告组ID数组
            'adset_source_ids.*' => 'required|string',   // 每个广告组ID必须是字符串
        ]);

        Log::debug('复制广告到广告组请求数据:', $validatedData);

        try {
            $adSourceId = $validatedData['ad_source_id'];
            $adsetSourceIds = $validatedData['adset_source_ids'];

            $results = [];
            $totalJobs = 0;

            // 查找原始广告
            $originalAd = FbAd::where('source_id', $adSourceId)->first();
            if (!$originalAd) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到指定的广告: ' . $adSourceId
                ], 404);
            }

            // 获取广告账户信息
            $adAccount = $originalAd->fbAdAccountV2;
            if (!$adAccount) {
                return response()->json([
                    'success' => false,
                    'message' => '无法获取广告账户信息'
                ], 400);
            }

            // 检查广告账户是否有有效的API Token
            $apiToken = $adAccount->apiTokens()
                ->where('active', true)
                ->where('token_type', 1)
                ->first();

            if (!$apiToken) {
                return response()->json([
                    'success' => false,
                    'message' => '广告账户没有有效的API Token'
                ], 400);
            }

            // 验证所有目标广告组是否存在
            $validAdsets = [];
            foreach ($adsetSourceIds as $adsetSourceId) {
                $adset = \App\Models\FbAdset::where('source_id', $adsetSourceId)->first();
                if (!$adset) {
                    $results[] = [
                        'adset_source_id' => $adsetSourceId,
                        'success' => false,
                        'message' => '未找到指定的广告组'
                    ];
                    continue;
                }

                // 检查广告组所属的广告账户
                $adsetAdAccount = $adset->fbAdAccount;
                if (!$adsetAdAccount || $adsetAdAccount->source_id !== $adAccount->source_id) {
                    $results[] = [
                        'adset_source_id' => $adsetSourceId,
                        'success' => false,
                        'message' => '广告组不属于同一个广告账户'
                    ];
                    continue;
                }

                $validAdsets[] = $adsetSourceId;
            }

            if (empty($validAdsets)) {
                return response()->json([
                    'success' => false,
                    'message' => '没有有效的目标广告组',
                    'data' => ['results' => $results]
                ], 400);
            }

            // 为每个有效的广告组创建拷贝任务
            $delaySeconds = 0;
            foreach ($validAdsets as $index => $adsetSourceId) {
                try {
                    \App\Jobs\ActionCopyAdToAdset::dispatch($adSourceId, $adsetSourceId, $index + 1)
                        ->delay(now()->addSeconds($delaySeconds))
                        ->onQueue('frontend');

                    $delaySeconds += 20; // 每个任务间隔20秒
                    $totalJobs++;

                    $results[] = [
                        'adset_source_id' => $adsetSourceId,
                        'success' => true,
                        'message' => '拷贝任务已提交',
                        'delay_seconds' => $delaySeconds - 20
                    ];

                    Log::info("已分派广告复制到广告组任务", [
                        'ad_source_id' => $adSourceId,
                        'adset_source_id' => $adsetSourceId,
                        'delay_seconds' => $delaySeconds - 20
                    ]);

                } catch (\Exception $e) {
                    $results[] = [
                        'adset_source_id' => $adsetSourceId,
                        'success' => false,
                        'message' => '任务提交失败: ' . $e->getMessage()
                    ];

                    Log::error("分派广告复制到广告组任务失败", [
                        'ad_source_id' => $adSourceId,
                        'adset_source_id' => $adsetSourceId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info("广告复制到广告组任务分派完成", [
                'ad_source_id' => $adSourceId,
                'total_valid_adsets' => count($validAdsets),
                'total_jobs' => $totalJobs
            ]);

            return response()->json([
                'success' => true,
                'message' => '广告复制到广告组任务已提交到队列',
                'data' => [
                    'ad_source_id' => $adSourceId,
                    'total_valid_adsets' => count($validAdsets),
                    'total_jobs' => $totalJobs,
                    'results' => $results,
                    'note' => '每个复制任务将按20秒间隔依次执行'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("广告复制到广告组失败", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '广告复制到广告组失败: ' . $e->getMessage()
            ], 500);
        }
    }

}
