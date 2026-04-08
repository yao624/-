<?php

namespace App\Jobs;

use App\Models\FbAd;
use App\Models\FbAdAccount;
use App\Models\FbApiToken;
use App\Models\FbCatalogProductSet;
use App\Models\FbPage;
use App\Utils\FbUtils;
use App\Utils\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookUpdateAdCreative implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 1;

    protected $adAccountSourceId;
    protected $adSourceId;
    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct($adAccountSourceId, $adSourceId, $payload)
    {
        $this->adAccountSourceId = $adAccountSourceId;
        $this->adSourceId = $adSourceId;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("开始处理广告Creative更新", [
            'ad_account' => $this->adAccountSourceId,
            'ad_id' => $this->adSourceId
        ]);

        try {
            // 1. 获取FbAdAccount
            $fbAdAccount = FbAdAccount::where('source_id', $this->adAccountSourceId)->first();
            if (!$fbAdAccount) {
                throw new \Exception("找不到广告账户: {$this->adAccountSourceId}");
            }

            // 2. 获取FbAd
            $fbAd = FbAd::where('source_id', $this->adSourceId)->first();
            if (!$fbAd) {
                throw new \Exception("找不到广告: {$this->adSourceId}");
            }

            // 3. 获取API Token
            $fbApiToken = $this->getFbApiToken($fbAdAccount);
            if (!$fbApiToken) {
                throw new \Exception("找不到可用的API Token");
            }

            // 4. 检查creative是否需要更新
           $needsUpdate = $this->checkCreativeNeedsUpdate($fbAd, $this->payload);
//            $needsUpdate = true;

            if (!$needsUpdate) {
                Log::info("广告Creative无需更新", ['ad_id' => $this->adSourceId]);
                return;
            }

            Log::info("检测到Creative需要更新，开始创建新Creative");

            // 5. 创建新的Creative
            $newCreativeId = $this->createNewCreative($fbAdAccount, $this->payload, $fbApiToken->token);

            if (!$newCreativeId) {
                throw new \Exception("创建新Creative失败");
            }

            // 6. 更新Ad的Creative
            $updateResult = $this->updateAdCreative($this->adSourceId, $newCreativeId, $fbApiToken->token);

            if (!$updateResult) {
                throw new \Exception("更新广告Creative失败");
            }

            Log::info("广告Creative更新成功", [
                'ad_id' => $this->adSourceId,
                'new_creative_id' => $newCreativeId
            ]);

        } catch (\Exception $e) {
            Log::error("广告Creative更新失败", [
                'ad_account' => $this->adAccountSourceId,
                'ad_id' => $this->adSourceId,
                'error' => $e->getMessage()
            ]);

            // 调度重试任务（20分钟后执行）
            try {
                FacebookUpdateAdCreativeRetry::dispatch(
                    $this->adAccountSourceId,
                    $this->adSourceId,
                    1 // 第一次重试
                )->onQueue('frontend')->delay(now()->addMinutes(20));

                Log::info("已调度重试任务", [
                    'ad_account' => $this->adAccountSourceId,
                    'ad_id' => $this->adSourceId,
                    'retry_delay' => '20分钟'
                ]);
            } catch (\Exception $retryException) {
                Log::error("调度重试任务失败", [
                    'ad_account' => $this->adAccountSourceId,
                    'ad_id' => $this->adSourceId,
                    'error' => $retryException->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * 获取可用的API Token
     */
    private function getFbApiToken($fbAdAccount)
    {
        return $fbAdAccount->apiTokens()
            ->where('token_type', 1)
            ->where('active', true)
            ->first();
    }

    /**
     * 检查Creative是否需要更新
     */
    private function checkCreativeNeedsUpdate($fbAd, $payload)
    {
        $currentCreative = $fbAd->creative;

        if (!$currentCreative) {
            Log::info("当前广告没有Creative数据，需要更新");
            return true;
        }

        // 判断新payload的Creative类型
        $newCreativeType = $this->detectCreativeType($payload);

        // 判断当前Creative的类型
        $currentCreativeType = $this->detectCreativeType($currentCreative);

        Log::info("Creative类型检测", [
            'current_type' => $currentCreativeType,
            'new_type' => $newCreativeType
        ]);

        // 如果检测到unknown类型，记录警告并需要更新
        if ($currentCreativeType === 'unknown' || $newCreativeType === 'unknown') {
            Log::warning("检测到unknown类型的Creative，强制更新", [
                'current_type' => $currentCreativeType,
                'new_type' => $newCreativeType
            ]);
            return true;
        }

        // 如果Creative类型发生变化，直接需要更新
        if ($currentCreativeType !== $newCreativeType) {
            Log::info("Creative类型发生变化，需要更新", [
                'from' => $currentCreativeType,
                'to' => $newCreativeType
            ]);
            return true;
        }

        // 类型相同，进行详细字段比较
        switch ($newCreativeType) {
            case 'single_image':
                return $this->checkSingleImageCreative($currentCreative, $payload);
            case 'single_video':
                return $this->checkSingleVideoCreative($currentCreative, $payload);
            case 'multi_image':
                return $this->checkMultiImageCreative($currentCreative, $payload);
            case 'multi_video':
                return $this->checkMultiVideoCreative($currentCreative, $payload);
            case 'catalog_single_image':
                return $this->checkCatalogSingleImageCreative($currentCreative, $payload);
            case 'catalog_single_video':
                return $this->checkCatalogSingleVideoCreative($currentCreative, $payload);
            case 'catalog_multi_image':
                return $this->checkCatalogMultiImageCreative($currentCreative, $payload);
            case 'catalog_multi_video':
                return $this->checkCatalogMultiVideoCreative($currentCreative, $payload);
            default:
                Log::warning("未知的Creative类型，强制更新: {$newCreativeType}");
                return true;
        }
    }

    /**
     * 检测Creative类型
     */
    private function detectCreativeType($data)
    {
        if (!is_array($data)) {
            Log::warning("Creative数据不是数组格式", ['data' => $data]);
            return 'unknown';
        }

        $hasAssetFeedSpec = isset($data['asset_feed_spec']) && !empty($data['asset_feed_spec']);
        $hasProductSetId = isset($data['product_set_id']) && !empty($data['product_set_id']);

        Log::debug("Creative类型检测详情", [
            'has_asset_feed_spec' => $hasAssetFeedSpec,
            'has_object_story_spec' => isset($data['object_story_spec']),
            'has_product_set_id' => $hasProductSetId,
            'product_set_id' => $data['product_set_id'] ?? null,
            'object_story_spec_keys' => isset($data['object_story_spec']) ? array_keys($data['object_story_spec']) : []
        ]);

        // 如果有product_set_id，则是目录广告
        if ($hasProductSetId) {
            return $this->detectCatalogCreativeType($data);
        }

        if (!$hasAssetFeedSpec) {
            // 单语言Creative
            if (isset($data['object_story_spec']['video_data'])) {
                return 'single_video';
            } elseif (isset($data['object_story_spec']['link_data'])) {
                return 'single_image';
            } else {
                // 如果没有video_data也没有link_data，默认认为是单语言图片
                Log::warning("单语言Creative缺少video_data和link_data，默认为single_image");
                return 'single_image';
            }
        } else {
            // 多语言Creative
            if (isset($data['asset_feed_spec']['videos']) && !empty($data['asset_feed_spec']['videos'])) {
                return 'multi_video';
            } elseif (isset($data['asset_feed_spec']['images']) && !empty($data['asset_feed_spec']['images'])) {
                return 'multi_image';
            } else {
                // 如果asset_feed_spec存在但既没有videos也没有images，默认认为是多语言图片
                Log::warning("多语言Creative缺少videos和images，默认为multi_image");
                return 'multi_image';
            }
        }
    }

    /**
     * 检测目录广告Creative类型
     */
    private function detectCatalogCreativeType($data)
    {
        $productSetId = $data['product_set_id'];

        // 判断是否为多语言：检查 object_story_spec.template_data 是否有 customization_rules_spec
        $hasCustomizationRules = isset($data['object_story_spec']['template_data']['customization_rules_spec'])
            && !empty($data['object_story_spec']['template_data']['customization_rules_spec']);

        // 判断是否为视频广告：查询 product set 的第一个 product 的 video_url 字段
        $isVideo = $this->isCatalogVideoAd($productSetId);

        Log::debug("目录广告类型检测", [
            'product_set_id' => $productSetId,
            'has_customization_rules' => $hasCustomizationRules,
            'is_video' => $isVideo
        ]);

        if ($hasCustomizationRules) {
            // 多语言目录广告
            return $isVideo ? 'catalog_multi_video' : 'catalog_multi_image';
        } else {
            // 单语言目录广告
            return $isVideo ? 'catalog_single_video' : 'catalog_single_image';
        }
    }

    /**
     * 判断目录广告是否为视频广告
     */
    private function isCatalogVideoAd($productSetId)
    {
        try {
            $productSet = FbCatalogProductSet::where('source_id', $productSetId)->first();

            if (!$productSet) {
                Log::warning("找不到Product Set", ['product_set_id' => $productSetId]);
                return false;
            }

            // 获取第一个product
            $firstProduct = $productSet->products()->first();

            if (!$firstProduct) {
                Log::warning("Product Set中没有产品", ['product_set_id' => $productSetId]);
                return false;
            }

            $hasVideoUrl = !empty($firstProduct->video_url);

            Log::debug("Product视频URL检查", [
                'product_set_id' => $productSetId,
                'product_id' => $firstProduct->source_id,
                'video_url' => $firstProduct->video_url,
                'has_video_url' => $hasVideoUrl
            ]);

            return $hasVideoUrl;

        } catch (\Exception $e) {
            Log::error("检查目录视频广告类型失败", [
                'product_set_id' => $productSetId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 检查单语言图片Creative
     */
    private function checkSingleImageCreative($currentCreative, $payload)
    {
        $objectStorySpec = $payload['object_story_spec'] ?? [];
        $linkData = $objectStorySpec['link_data'] ?? [];

        $currentSpec = $currentCreative['object_story_spec'] ?? [];
        $currentLinkData = $currentSpec['link_data'] ?? [];

        // 检查需要对比的字段
        $fieldsToCheck = [
            'page_id' => $objectStorySpec['page_id'] ?? null,
            'link' => $linkData['link'] ?? null,
            'name' => $linkData['name'] ?? null,
            'message' => $linkData['message'] ?? null,
            'image_hash' => $linkData['image_hash'] ?? null,
            'description' => $linkData['description'] ?? null,
            'call_to_action_type' => $linkData['call_to_action']['type'] ?? null,
        ];

        $currentFields = [
            'page_id' => $currentSpec['page_id'] ?? null,
            'link' => $currentLinkData['link'] ?? null,
            'name' => $currentLinkData['name'] ?? null,
            'message' => $currentLinkData['message'] ?? null,
            'image_hash' => $currentLinkData['image_hash'] ?? null,
            'description' => $currentLinkData['description'] ?? null,
            'call_to_action_type' => $currentLinkData['call_to_action']['type'] ?? null,
        ];

        foreach ($fieldsToCheck as $field => $value) {
            if (($currentFields[$field] ?? null) !== $value) {
                Log::info("单语言图片Creative字段不同: {$field}", [
                    'current' => $currentFields[$field] ?? null,
                    'new' => $value
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * 检查单语言视频Creative
     */
    private function checkSingleVideoCreative($currentCreative, $payload)
    {
        $objectStorySpec = $payload['object_story_spec'] ?? [];
        $videoData = $objectStorySpec['video_data'] ?? [];

        $currentSpec = $currentCreative['object_story_spec'] ?? [];
        $currentVideoData = $currentSpec['video_data'] ?? [];

        // 检查需要对比的字段
        $fieldsToCheck = [
            'page_id' => $objectStorySpec['page_id'] ?? null,
            'title' => $videoData['title'] ?? null,
            'message' => $videoData['message'] ?? null,
            'video_id' => $videoData['video_id'] ?? null,
            'link_description' => $videoData['link_description'] ?? null,
            'image_url' => $this->removeQueryParams($videoData['image_url'] ?? ''),
            'call_to_action_type' => $videoData['call_to_action']['type'] ?? null,
            'call_to_action_link' => $videoData['call_to_action']['value']['link'] ?? null,
        ];

        $currentFields = [
            'page_id' => $currentSpec['page_id'] ?? null,
            'title' => $currentVideoData['title'] ?? null,
            'message' => $currentVideoData['message'] ?? null,
            'video_id' => $currentVideoData['video_id'] ?? null,
            'link_description' => $currentVideoData['link_description'] ?? null,
            'image_url' => $this->removeQueryParams($currentVideoData['image_url'] ?? ''),
            'call_to_action_type' => $currentVideoData['call_to_action']['type'] ?? null,
            'call_to_action_link' => $currentVideoData['call_to_action']['value']['link'] ?? null,
        ];

        foreach ($fieldsToCheck as $field => $value) {
            if (($currentFields[$field] ?? null) !== $value) {
                Log::info("单语言视频Creative字段不同: {$field}", [
                    'current' => $currentFields[$field] ?? null,
                    'new' => $value
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * 检查多语言图片Creative
     */
    private function checkMultiImageCreative($currentCreative, $payload)
    {
        $assetFeedSpec = $payload['asset_feed_spec'] ?? [];
        $currentAssetFeedSpec = $currentCreative['asset_feed_spec'] ?? [];

        // 检查需要对比的字段数组
        $arrayFieldsToCheck = ['bodies', 'titles', 'descriptions', 'link_urls', 'images', 'call_to_action_types'];

        foreach ($arrayFieldsToCheck as $field) {
            if (!$this->compareArrayField($currentAssetFeedSpec[$field] ?? [], $assetFeedSpec[$field] ?? [])) {
                Log::info("多语言图片Creative字段不同: {$field}");
                return true;
            }
        }

        // 检查asset_customization_rules
        if (!$this->compareAssetCustomizationRules($currentAssetFeedSpec['asset_customization_rules'] ?? [], $assetFeedSpec['asset_customization_rules'] ?? [])) {
            Log::info("多语言图片Creative asset_customization_rules不同");
            return true;
        }

        return false;
    }

    /**
     * 检查多语言视频Creative
     */
    private function checkMultiVideoCreative($currentCreative, $payload)
    {
        $assetFeedSpec = $payload['asset_feed_spec'] ?? [];
        $currentAssetFeedSpec = $currentCreative['asset_feed_spec'] ?? [];

        // 检查需要对比的字段数组
        $arrayFieldsToCheck = ['bodies', 'titles', 'descriptions', 'link_urls', 'videos', 'call_to_action_types'];

        foreach ($arrayFieldsToCheck as $field) {
            if (!$this->compareArrayField($currentAssetFeedSpec[$field] ?? [], $assetFeedSpec[$field] ?? [])) {
                Log::info("多语言视频Creative字段不同: {$field}");
                return true;
            }
        }

        // 检查asset_customization_rules
        if (!$this->compareAssetCustomizationRules($currentAssetFeedSpec['asset_customization_rules'] ?? [], $assetFeedSpec['asset_customization_rules'] ?? [])) {
            Log::info("多语言视频Creative asset_customization_rules不同");
            return true;
        }

        return false;
    }

    /**
     * 比较数组字段
     */
    private function compareArrayField($current, $new)
    {
        // 简单的数组比较，可以根据需要优化
        return json_encode($current) === json_encode($new);
    }

    /**
     * 比较asset_customization_rules（顺序可能不同）
     */
    private function compareAssetCustomizationRules($current, $new)
    {
        if (count($current) !== count($new)) {
            return false;
        }

        // 按某个字段排序后比较
        usort($current, function($a, $b) {
            return strcmp($a['body_label']['name'] ?? '', $b['body_label']['name'] ?? '');
        });

        usort($new, function($a, $b) {
            return strcmp($a['body_label']['name'] ?? '', $b['body_label']['name'] ?? '');
        });

        return json_encode($current) === json_encode($new);
    }

    /**
     * 移除URL中的查询参数
     */
    private function removeQueryParams($url)
    {
        if (empty($url)) {
            return '';
        }

        $parsed = parse_url($url);
        if (!$parsed) {
            return $url;
        }

        $cleanUrl = '';
        if (isset($parsed['scheme'])) $cleanUrl .= $parsed['scheme'] . '://';
        if (isset($parsed['host'])) $cleanUrl .= $parsed['host'];
        if (isset($parsed['port'])) $cleanUrl .= ':' . $parsed['port'];
        if (isset($parsed['path'])) $cleanUrl .= $parsed['path'];

        return $cleanUrl;
    }

    /**
     * 清理payload中的null值，将text字段的null转换为空字符串
     */
    private function cleanPayloadNullValues($payload)
    {
        if (!is_array($payload)) {
            return $payload === null ? '' : $payload;
        }

        $cleanedPayload = [];
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $cleanedPayload[$key] = $this->cleanPayloadNullValues($value);
            } else {
                // 特别处理text字段，将null转换为空字符串
                if ($key === 'text' && $value === null) {
                    $cleanedPayload[$key] = '';
                    Log::debug("将text字段的null值转换为空字符串");
                } else {
                    $cleanedPayload[$key] = $value;
                }
            }
        }
        return $cleanedPayload;
    }

    /**
     * 创建新的Creative
     */
    private function createNewCreative($fbAdAccount, $payload, $apiToken)
    {
        $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/act_{$fbAdAccount->source_id}/adcreatives";

        // 清理payload中的null值，将text字段的null转换为空字符串
        $cleanedPayload = $this->cleanPayloadNullValues($payload);
        Log::debug("clean payload: ", [$cleanedPayload]);

        // 处理 pbia 逻辑：如果 object_story_spec 有 page_id 但没有 instagram_user_id，
        // 则检查 page 的 pbia 字段，如果不为空就设置到 instagram_user_id
        if (isset($cleanedPayload['object_story_spec']['page_id'])) {
            $pageId = $cleanedPayload['object_story_spec']['page_id'];

            // 如果已经有 instagram_user_id，则不处理
            if (!isset($cleanedPayload['object_story_spec']['instagram_user_id'])) {
                Log::debug("检查页面的pbia设置", ['page_id' => $pageId]);

                // 查找对应的 FbPage
                $fbPage = FbPage::where('source_id', $pageId)->first();

                if ($fbPage && !empty($fbPage->pbia)) {
                    Log::info("为页面设置Instagram用户ID", [
                        'page_id' => $pageId,
                        'pbia' => $fbPage->pbia
                    ]);

                    // 设置 instagram_user_id 为 pbia 的值
                    $cleanedPayload['object_story_spec']['instagram_user_id'] = $fbPage->pbia;
                } else {
                    Log::debug("页面未设置pbia或页面不存在", [
                        'page_id' => $pageId,
                        'page_found' => $fbPage ? 'yes' : 'no',
                        'pbia_empty' => $fbPage ? empty($fbPage->pbia) : 'unknown'
                    ]);

                    // 如果页面存在但没有 pbia，尝试创建 pbia
                    if ($fbPage && empty($fbPage->pbia)) {
                        Log::info("页面没有pbia，尝试自动创建", ['page_id' => $pageId]);

                                                $createdPbiaId = $this->getOrCreatePbiaId($fbPage);
                        if ($createdPbiaId) {
                            Log::info("成功获取/创建pbia并设置Instagram用户ID", [
                                'page_id' => $pageId,
                                'pbia_id' => $createdPbiaId
                            ]);

                            // 设置 instagram_user_id 为获取的 pbia 值
                            $cleanedPayload['object_story_spec']['instagram_user_id'] = $createdPbiaId;

                            // 注意：pbia 已经在 getOrCreatePbiaId 方法中保存到数据库了
                        } else {
                            Log::warning("创建pbia失败", ['page_id' => $pageId]);
                        }
                    }
                }
            } else {
                Log::debug("页面已有instagram_user_id，跳过pbia处理", [
                    'page_id' => $pageId,
                    'existing_instagram_user_id' => $cleanedPayload['object_story_spec']['instagram_user_id']
                ]);
            }
        }

        $postData = [];

        if (isset($cleanedPayload['object_story_spec'])) {
            $postData['object_story_spec'] = json_encode($cleanedPayload['object_story_spec']);
        }

        if (isset($cleanedPayload['asset_feed_spec'])) {
            $postData['asset_feed_spec'] = json_encode($cleanedPayload['asset_feed_spec']);
        }

        if (isset($cleanedPayload['url_tags'])) {
            $postData['url_tags'] = $cleanedPayload['url_tags'];
        }

        if (isset($cleanedPayload['product_set_id'])) {
            $postData['product_set_id'] = $cleanedPayload['product_set_id'];
        }

        if (isset($cleanedPayload['template_url_spec'])) {
            $postData['template_url_spec'] = json_encode($cleanedPayload['template_url_spec']);
        }

        Log::info("创建Creative请求数据", $postData);

        try {
            $response = FbUtils::makeRequest(
                null,
                $endpoint,
                null,
                'POST',
                $postData,
                'create_adcreatives',
                $apiToken
            );

            if ($response['success'] && isset($response['id'])) {
                Log::info("Creative创建成功", ['creative_id' => $response['id']]);
                return $response['id'];
            } else {
                Log::error("Creative创建失败", ['response' => $response]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error("创建Creative时发生异常", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 更新Ad的Creative
     */
    private function updateAdCreative($adSourceId, $newCreativeId, $apiToken)
    {
        $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/{$adSourceId}/";

        $postData = [
            'creative' => json_encode(['creative_id' => $newCreativeId])
        ];

        Log::info("更新Ad Creative请求数据", $postData);

        try {
            $response = FbUtils::makeRequest(
                null,
                $endpoint,
                null,
                'POST',
                $postData,
                'update_adset_status',
                $apiToken
            );

            if ($response['success']) {
                Log::info("Ad Creative更新成功", [
                    'ad_id' => $adSourceId,
                    'creative_id' => $newCreativeId
                ]);
                return true;
            } else {
                Log::error("Ad Creative更新失败", ['response' => $response]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("更新Ad Creative时发生异常", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 检查目录单语言图片Creative
     */
    private function checkCatalogSingleImageCreative($currentCreative, $payload)
    {
        // 检查 product_set_id
        $currentProductSetId = $currentCreative['product_set_id'] ?? null;
        $newProductSetId = $payload['product_set_id'] ?? null;
        if ($currentProductSetId !== $newProductSetId) {
            Log::info("目录单语言图片Creative product_set_id不同", [
                'current' => $currentProductSetId,
                'new' => $newProductSetId
            ]);
            return true;
        }

        // 检查 object_story_spec.page_id
        $currentPageId = $currentCreative['object_story_spec']['page_id'] ?? null;
        $newPageId = $payload['object_story_spec']['page_id'] ?? null;
        if ($currentPageId !== $newPageId) {
            Log::info("目录单语言图片Creative page_id不同", [
                'current' => $currentPageId,
                'new' => $newPageId
            ]);
            return true;
        }

        // 检查 template_data 中的字段
        $currentTemplateData = $currentCreative['object_story_spec']['template_data'] ?? [];
        $newTemplateData = $payload['object_story_spec']['template_data'] ?? [];

        $fieldsToCheck = ['link', 'name', 'message', 'description'];
        foreach ($fieldsToCheck as $field) {
            $currentValue = $currentTemplateData[$field] ?? null;
            $newValue = $newTemplateData[$field] ?? null;
            if ($currentValue !== $newValue) {
                Log::info("目录单语言图片Creative template_data.{$field}不同", [
                    'current' => $currentValue,
                    'new' => $newValue
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * 检查目录单语言视频Creative
     */
    private function checkCatalogSingleVideoCreative($currentCreative, $payload)
    {
        // 检查 product_set_id
        $currentProductSetId = $currentCreative['product_set_id'] ?? null;
        $newProductSetId = $payload['product_set_id'] ?? null;
        if ($currentProductSetId !== $newProductSetId) {
            Log::info("目录单语言视频Creative product_set_id不同", [
                'current' => $currentProductSetId,
                'new' => $newProductSetId
            ]);
            return true;
        }

        // 检查 object_story_spec.page_id
        $currentPageId = $currentCreative['object_story_spec']['page_id'] ?? null;
        $newPageId = $payload['object_story_spec']['page_id'] ?? null;
        if ($currentPageId !== $newPageId) {
            Log::info("目录单语言视频Creative page_id不同", [
                'current' => $currentPageId,
                'new' => $newPageId
            ]);
            return true;
        }

        // 检查 template_data 中的字段
        $currentTemplateData = $currentCreative['object_story_spec']['template_data'] ?? [];
        $newTemplateData = $payload['object_story_spec']['template_data'] ?? [];

        $fieldsToCheck = ['link', 'name', 'message', 'description'];
        foreach ($fieldsToCheck as $field) {
            $currentValue = $currentTemplateData[$field] ?? null;
            $newValue = $newTemplateData[$field] ?? null;
            if ($currentValue !== $newValue) {
                Log::info("目录单语言视频Creative template_data.{$field}不同", [
                    'current' => $currentValue,
                    'new' => $newValue
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * 检查目录多语言图片Creative
     */
    private function checkCatalogMultiImageCreative($currentCreative, $payload)
    {
        // 检查 product_set_id
        $currentProductSetId = $currentCreative['product_set_id'] ?? null;
        $newProductSetId = $payload['product_set_id'] ?? null;
        if ($currentProductSetId !== $newProductSetId) {
            Log::info("目录多语言图片Creative product_set_id不同", [
                'current' => $currentProductSetId,
                'new' => $newProductSetId
            ]);
            return true;
        }

        // 检查 object_story_spec.page_id
        $currentPageId = $currentCreative['object_story_spec']['page_id'] ?? null;
        $newPageId = $payload['object_story_spec']['page_id'] ?? null;
        if ($currentPageId !== $newPageId) {
            Log::info("目录多语言图片Creative page_id不同", [
                'current' => $currentPageId,
                'new' => $newPageId
            ]);
            return true;
        }

        // 检查 template_data 中的基础字段
        $currentTemplateData = $currentCreative['object_story_spec']['template_data'] ?? [];
        $newTemplateData = $payload['object_story_spec']['template_data'] ?? [];

        $fieldsToCheck = ['link', 'name', 'message', 'description'];
        foreach ($fieldsToCheck as $field) {
            $currentValue = $currentTemplateData[$field] ?? null;
            $newValue = $newTemplateData[$field] ?? null;
            if ($currentValue !== $newValue) {
                Log::info("目录多语言图片Creative template_data.{$field}不同", [
                    'current' => $currentValue,
                    'new' => $newValue
                ]);
                return true;
            }
        }

        // 检查 customization_rules_spec
        $currentCustomizationRules = $currentTemplateData['customization_rules_spec'] ?? [];
        $newCustomizationRules = $newTemplateData['customization_rules_spec'] ?? [];

        if (!$this->compareCatalogCustomizationRules($currentCustomizationRules, $newCustomizationRules)) {
            Log::info("目录多语言图片Creative customization_rules_spec不同");
            return true;
        }

        return false;
    }

    /**
     * 检查目录多语言视频Creative
     */
    private function checkCatalogMultiVideoCreative($currentCreative, $payload)
    {
        // 检查 product_set_id
        $currentProductSetId = $currentCreative['product_set_id'] ?? null;
        $newProductSetId = $payload['product_set_id'] ?? null;
        if ($currentProductSetId !== $newProductSetId) {
            Log::info("目录多语言视频Creative product_set_id不同", [
                'current' => $currentProductSetId,
                'new' => $newProductSetId
            ]);
            return true;
        }

        // 检查 object_story_spec.page_id
        $currentPageId = $currentCreative['object_story_spec']['page_id'] ?? null;
        $newPageId = $payload['object_story_spec']['page_id'] ?? null;
        if ($currentPageId !== $newPageId) {
            Log::info("目录多语言视频Creative page_id不同", [
                'current' => $currentPageId,
                'new' => $newPageId
            ]);
            return true;
        }

        // 检查 template_data 中的基础字段
        $currentTemplateData = $currentCreative['object_story_spec']['template_data'] ?? [];
        $newTemplateData = $payload['object_story_spec']['template_data'] ?? [];

        $fieldsToCheck = ['link', 'name', 'message', 'description'];
        foreach ($fieldsToCheck as $field) {
            $currentValue = $currentTemplateData[$field] ?? null;
            $newValue = $newTemplateData[$field] ?? null;
            if ($currentValue !== $newValue) {
                Log::info("目录多语言视频Creative template_data.{$field}不同", [
                    'current' => $currentValue,
                    'new' => $newValue
                ]);
                return true;
            }
        }

        // 检查 customization_rules_spec
        $currentCustomizationRules = $currentTemplateData['customization_rules_spec'] ?? [];
        $newCustomizationRules = $newTemplateData['customization_rules_spec'] ?? [];

        if (!$this->compareCatalogCustomizationRules($currentCustomizationRules, $newCustomizationRules)) {
            Log::info("目录多语言视频Creative customization_rules_spec不同");
            return true;
        }

        return false;
    }

    /**
     * 比较目录广告的 customization_rules_spec（顺序可能不同）
     */
    private function compareCatalogCustomizationRules($current, $new)
    {
        if (count($current) !== count($new)) {
            return false;
        }

        // 按 customization_spec.language 排序后比较
        usort($current, function($a, $b) {
            $aLang = $a['customization_spec']['language'] ?? '';
            $bLang = $b['customization_spec']['language'] ?? '';
            return strcmp($aLang, $bLang);
        });

        usort($new, function($a, $b) {
            $aLang = $a['customization_spec']['language'] ?? '';
            $bLang = $b['customization_spec']['language'] ?? '';
            return strcmp($aLang, $bLang);
        });

        return json_encode($current) === json_encode($new);
    }

    /**
     * Job标签，用于队列监控和分类
     */
    public function tags(): array
    {
        return [
            "FB-Update-Ad-Creative",
            "ad-account-{$this->adAccountSourceId}",
            "ad-{$this->adSourceId}",
            "creative-update"
        ];
    }

    /**
     * 获取或创建页面的 PBIA ID
     */
    private function getOrCreatePbiaId($fbPage)
    {
        try {
            // 获取页面的 page token
            $pageToken = $this->getPageToken($fbPage);
            if (!$pageToken) {
                Log::error("无法获取页面token", ['page_id' => $fbPage->source_id]);
                return null;
            }

            $pageSourceId = $fbPage->source_id;
            $version = FbUtils::$API_Version;
            $endpoint = "https://graph.facebook.com/{$version}/{$pageSourceId}/page_backed_instagram_accounts";

            // 首先尝试获取现有的 pbia
            Log::debug("尝试获取现有的pbia", ['page_id' => $pageSourceId]);
            $resp = FbUtils::makeRequest(null, $endpoint, null, 'GET', null, 'pbia', $pageToken);

            if ($resp['success']) {
                $data = collect($resp['data'] ?? []);
                if ($data->isNotEmpty()) {
                    $firstItem = $data->first();
                    $pbiaId = data_get($firstItem, 'id');

                    Log::info("找到现有的pbia，保存到数据库", [
                        'page_id' => $pageSourceId,
                        'pbia_id' => $pbiaId
                    ]);

                    // 保存 pbia 到数据库，避免下次重复调用 API
                    $fbPage->update(['pbia' => $pbiaId]);

                    return $pbiaId;
                } else {
                    // 没有 pbia，需要创建
                    Log::debug('没有找到pbia，开始创建', ['page_id' => $pageSourceId]);
                    $createResp = FbUtils::makeRequest(null, $endpoint, null, 'POST', null, '', $pageToken);

                    if ($createResp['success']) {
                        Log::info('成功创建pbia', ['page_id' => $pageSourceId]);
                        $createdPbiaId = data_get($createResp, 'id');
                        return $createdPbiaId;
                    } else {
                        Log::error('创建pbia失败', [
                            'page_id' => $pageSourceId,
                            'response' => $createResp
                        ]);
                        return null;
                    }
                }
            } else {
                Log::error('获取pbia失败', [
                    'page_id' => $pageSourceId,
                    'response' => $resp
                ]);
                Telegram::sendMessage("获取pbia失败，page_id: {$pageSourceId}, response: " . json_encode($resp));
                return null;
            }
        } catch (\Exception $e) {
            Log::error('获取或创建pbia时发生异常', [
                'page_id' => $fbPage->source_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 获取页面的 Page Token
     */
    private function getPageToken($fbPage)
    {
        try {
            if (!$fbPage->tokens) {
                Log::debug("页面没有tokens数据", ['page_id' => $fbPage->source_id]);
                return null;
            }

            $tokens = collect($fbPage->tokens);
            Log::debug("页面tokens数据", [
                'page_id' => $fbPage->source_id,
                'tokens_count' => $tokens->count()
            ]);

            // 找出所有 owner_type 为 'bm' 的 tokens
            $bmTokens = $tokens->where('owner_type', 'bm');

            if ($bmTokens->isEmpty()) {
                Log::debug("没有找到owner_type为bm的tokens", ['page_id' => $fbPage->source_id]);
                return null;
            }

            // 遍历所有 bm tokens，找到可用的 API token
            foreach ($bmTokens as $tokenData) {
                $ownerId = $tokenData['owner_id'] ?? null;
                if (!$ownerId) {
                    continue;
                }

                // 查找对应的 FbApiToken
                $fbApiToken = FbApiToken::where('id', $ownerId)
                    ->where('token_type', 1)
                    ->where('active', true)
                    ->first();

                if ($fbApiToken) {
                    Log::debug("找到匹配的API token", [
                        'page_id' => $fbPage->source_id,
                        'owner_id' => $ownerId,
                        'token_available' => !empty($tokenData['token'])
                    ]);

                    return $tokenData['token'] ?? null;
                }
            }

            Log::debug("没有找到匹配的有效API token", ['page_id' => $fbPage->source_id]);
            return null;
        } catch (\Exception $e) {
            Log::error('获取页面token时发生异常', [
                'page_id' => $fbPage->source_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Job失败时的处理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('更新广告Creative失败: ' . $exception->getMessage(), [
            'ad_account' => $this->adAccountSourceId,
            'ad_id' => $this->adSourceId,
            'exception' => $exception
        ]);

        // 发送Telegram通知
        $message = "FB广告Creative更新失败\r\n";
        $message .= "广告账户: {$this->adAccountSourceId}\r\n";
        $message .= "广告ID: {$this->adSourceId}\r\n";
        $message .= "错误信息: " . $exception->getMessage();

        try {
            Telegram::sendMessage($message);
        } catch (\Exception $e) {
            Log::error('发送Telegram通知失败: ' . $e->getMessage());
        }
    }
}
