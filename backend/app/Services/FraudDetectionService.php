<?php

namespace App\Services;

use App\Models\FbAd;
use App\Models\FbPage;
use App\Models\FbPagePost;
use App\Models\FbCatalogProductSet;
use App\Models\FbCatalogProduct;
use App\Models\FraudConfig;
use App\Enums\FraudConfigType;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    /**
     * 检查是否需要进行防盗刷扫描
     * 如果没有配置任何白名单，则不需要扫描
     */
    public static function shouldPerformScan(): bool
    {
        // 获取所有激活的FraudConfig
        $fraudConfigs = FraudConfig::where('active', true)->get();

        if ($fraudConfigs->isEmpty()) {
            Log::info("没有激活的FraudConfig，跳过防盗刷扫描");
            return false;
        }

        // 检查是否配置了任何白名单
        $hasAnyWhitelist = false;
        foreach ($fraudConfigs as $config) {
            if (($config->type === FraudConfigType::DomainWhitelist->value ||
                 $config->type === FraudConfigType::UrlWhitelist->value) &&
                $config->value && !empty($config->value)) {
                $hasAnyWhitelist = true;
                break;
            }
        }

        if (!$hasAnyWhitelist) {
            Log::info("没有配置任何白名单，跳过防盗刷扫描");
            return false;
        }

        Log::info("检测到防盗刷白名单配置，需要进行扫描");
        return true;
    }

    /**
     * 检查广告是否在排除列表中
     */
    private function isAdExcluded(string $adSourceId): bool
    {
        // 获取所有激活的FraudConfig
        $fraudConfigs = FraudConfig::where('active', true)->get();

        if ($fraudConfigs->isEmpty()) {
            return false;
        }

        // 合并所有排除的广告列表
        $excludedAds = [];
        foreach ($fraudConfigs as $config) {
            if ($config->excluded_ads && is_array($config->excluded_ads)) {
                $excludedAds = array_merge($excludedAds, $config->excluded_ads);
            }
        }

        $excludedAds = array_unique($excludedAds);

        $isExcluded = in_array($adSourceId, $excludedAds);

        if ($isExcluded) {
            Log::info("广告在排除列表中", [
                'ad_id' => $adSourceId,
                'excluded_ads_count' => count($excludedAds)
            ]);
        }

        return $isExcluded;
    }

    /**
     * 检查广告是否存在盗刷风险
     */
    public function checkAd(FbAd $ad): array
    {
        Log::info("开始检查广告盗刷风险", ['ad_id' => $ad->source_id]);

        // 检查广告是否在排除列表中
        if ($this->isAdExcluded($ad->source_id)) {
            Log::info("广告在排除列表中，跳过检测", ['ad_id' => $ad->source_id]);
            return [
                'is_fraud' => false,
                'reason' => '广告在排除列表中，跳过检测',
                'urls' => [],
                'ad_type' => 'excluded'
            ];
        }

        $creative = $ad->creative;
        if (!$creative) {
            Log::warning("广告没有creative数据", ['ad_id' => $ad->source_id]);
            return [
                'is_fraud' => true,
                'reason' => '广告没有creative数据',
                'urls' => []
            ];
        }

        // 获取广告类型和需要检查的URL
        $adType = $this->getAdType($creative);
        Log::info("广告类型识别", ['ad_id' => $ad->source_id, 'type' => $adType]);

        $result = $this->extractUrlsFromCreative($creative, $adType);

        if ($result['is_fraud']) {
            Log::warning("广告检测为异常", [
                'ad_id' => $ad->source_id,
                'reason' => $result['reason']
            ]);
            return $result;
        }

        // 检查URL是否在白名单中
        $fraudCheckResult = $this->checkUrlsAgainstWhitelist($result['urls']);

        if ($fraudCheckResult['is_fraud']) {
            Log::warning("广告URL不在白名单中", [
                'ad_id' => $ad->source_id,
                'urls' => $result['urls'],
                'reason' => $fraudCheckResult['reason']
            ]);
        }

        return [
            'is_fraud' => $fraudCheckResult['is_fraud'],
            'reason' => $fraudCheckResult['reason'],
            'urls' => $result['urls'],
            'ad_type' => $adType
        ];
    }

    /**
     * 识别广告类型
     */
    private function getAdType($creative): string
    {
        // 检查是否为目录广告
        if (isset($creative['product_set_id'])) {
            if (isset($creative['object_story_spec']['template_data']['customization_rules_spec'])) {
                return 'catalog_multi_lang';
            } else {
                return 'catalog_single_lang';
            }
        }

        // 检查是否为多语言广告
        if (isset($creative['asset_feed_spec'])) {
            if (isset($creative['asset_feed_spec']['images'])) {
                return 'multi_lang_image';
            } elseif (isset($creative['asset_feed_spec']['videos'])) {
                return 'multi_lang_video';
            }
        }

        // 检查是否为跑原帖广告
        // 原帖广告的唯一特征：没有 object_story_spec 的 creative
        if (!isset($creative['object_story_spec'])) {
            return 'post';
        }

        // 检查普通广告类型
        if (isset($creative['object_story_spec']['link_data']['link'])) {
            return 'normal_image';
        }

        if (isset($creative['object_story_spec']['video_data']['call_to_action']['value']['link'])) {
            return 'normal_video';
        }

        return 'unknown';
    }

    /**
     * 从creative中提取需要检查的URL
     */
    private function extractUrlsFromCreative($creative, string $adType): array
    {
        $urls = [];

        switch ($adType) {
            case 'normal_image':
                $urls[] = $creative['object_story_spec']['link_data']['link'];
                break;

            case 'normal_video':
                $urls[] = $creative['object_story_spec']['video_data']['call_to_action']['value']['link'];
                break;

            case 'post':
                return $this->handlePostAd($creative);

            case 'catalog_single_lang':
                return $this->handleCatalogSingleLang($creative);

            case 'catalog_multi_lang':
                return $this->handleCatalogMultiLang($creative);

            case 'multi_lang_image':
            case 'multi_lang_video':
                return $this->handleMultiLangAd($creative);

            default:
                return [
                    'is_fraud' => true,
                    'reason' => '未知的广告类型',
                    'urls' => []
                ];
        }

        return [
            'is_fraud' => false,
            'reason' => '',
            'urls' => $urls
        ];
    }

    /**
     * 处理跑原帖广告
     */
    private function handlePostAd($creative): array
    {
        // 检查是否有 actor_id
        if (!isset($creative['actor_id'])) {
            return [
                'is_fraud' => true,
                'reason' => "跑原帖广告缺少actor_id",
                'urls' => []
            ];
        }

        $actorId = $creative['actor_id'];

        // 检查主页是否存在
        $page = FbPage::where('source_id', $actorId)->first();
        if (!$page) {
            return [
                'is_fraud' => true,
                'reason' => "主页不存在: {$actorId}",
                'urls' => []
            ];
        }

        // 检查是否有 effective_object_story_id
        if (!isset($creative['effective_object_story_id'])) {
            return [
                'is_fraud' => true,
                'reason' => "跑原帖广告缺少effective_object_story_id",
                'urls' => []
            ];
        }

        $effectiveObjectStoryId = $creative['effective_object_story_id'];

        // 解析post id
        $parts = explode('_', $effectiveObjectStoryId);
        if (count($parts) < 2) {
            return [
                'is_fraud' => true,
                'reason' => "无效的effective_object_story_id格式: {$effectiveObjectStoryId}",
                'urls' => []
            ];
        }

        $postId = $parts[1];
        $pagePost = FbPagePost::where('source_id', $postId)->first();
        if (!$pagePost) {
            return [
                'is_fraud' => true,
                'reason' => "主页帖子在系统里面不存在, Post ID: {$postId}",
                'urls' => []
            ];
        }

        return [
            'is_fraud' => false,
            'reason' => '',
            'urls' => [$pagePost->url]
        ];
    }

    /**
     * 处理单语言目录广告
     */
    private function handleCatalogSingleLang($creative): array
    {
        $productSetId = $creative['product_set_id'];

        // 检查product set是否存在
        $productSet = FbCatalogProductSet::where('source_id', $productSetId)->first();
        if (!$productSet) {
            return [
                'is_fraud' => true,
                'reason' => "Product set不存在: {$productSetId}",
                'urls' => []
            ];
        }

        $urls = [];

        // 检查template_data中的link
        if (isset($creative['object_story_spec']['template_data']['link'])) {
            $link = $creative['object_story_spec']['template_data']['link'];
            if ($link !== '{{product.url}}' && filter_var($link, FILTER_VALIDATE_URL)) {
                $urls[] = $link;
            } elseif ($link !== '{{product.url}}' && !filter_var($link, FILTER_VALIDATE_URL)) {
                return [
                    'is_fraud' => true,
                    'reason' => "无效的链接: {$link}",
                    'urls' => []
                ];
            }
        }

        // 检查product set中的产品URL
        $productUrls = $this->getProductUrlsFromFilter($productSet->filter);
        if ($productUrls['is_fraud']) {
            return $productUrls;
        }

        $urls = array_merge($urls, $productUrls['urls']);

        return [
            'is_fraud' => false,
            'reason' => '',
            'urls' => $urls
        ];
    }

    /**
     * 处理多语言目录广告
     */
    private function handleCatalogMultiLang($creative): array
    {
        $productSetId = $creative['product_set_id'];

        // 检查product set是否存在
        $productSet = FbCatalogProductSet::where('source_id', $productSetId)->first();
        if (!$productSet) {
            return [
                'is_fraud' => true,
                'reason' => "Product set不存在: {$productSetId}",
                'urls' => []
            ];
        }

        $urls = [];

        // 检查template_data中的link
        if (isset($creative['object_story_spec']['template_data']['link'])) {
            $link = $creative['object_story_spec']['template_data']['link'];
            if ($link !== '{{product.url}}' && filter_var($link, FILTER_VALIDATE_URL)) {
                $urls[] = $link;
            } elseif ($link !== '{{product.url}}' && !filter_var($link, FILTER_VALIDATE_URL)) {
                return [
                    'is_fraud' => true,
                    'reason' => "无效的链接: {$link}",
                    'urls' => []
                ];
            }
        }

        // 检查customization_rules_spec中的链接
        if (isset($creative['object_story_spec']['template_data']['customization_rules_spec'])) {
            foreach ($creative['object_story_spec']['template_data']['customization_rules_spec'] as $rule) {
                if (isset($rule['link'])) {
                    $link = $rule['link'];
                    if ($link !== '{{product.url}}' && filter_var($link, FILTER_VALIDATE_URL)) {
                        $urls[] = $link;
                    } elseif ($link !== '{{product.url}}' && !filter_var($link, FILTER_VALIDATE_URL)) {
                        return [
                            'is_fraud' => true,
                            'reason' => "无效的链接: {$link}",
                            'urls' => []
                        ];
                    }
                }

                if (isset($rule['template_url_spec']['web']['url'])) {
                    $url = $rule['template_url_spec']['web']['url'];
                    if ($url !== '{{product.url}}' && filter_var($url, FILTER_VALIDATE_URL)) {
                        $urls[] = $url;
                    } elseif ($url !== '{{product.url}}' && !filter_var($url, FILTER_VALIDATE_URL)) {
                        return [
                            'is_fraud' => true,
                            'reason' => "无效的URL: {$url}",
                            'urls' => []
                        ];
                    }
                }
            }
        }

        // 检查product set中的产品URL
        $productUrls = $this->getProductUrlsFromFilter($productSet->filter);
        if ($productUrls['is_fraud']) {
            return $productUrls;
        }

        $urls = array_merge($urls, $productUrls['urls']);

        return [
            'is_fraud' => false,
            'reason' => '',
            'urls' => array_unique($urls)
        ];
    }

    /**
     * 处理多语言图片/视频广告
     */
    private function handleMultiLangAd($creative): array
    {
        $urls = [];

        if (isset($creative['asset_feed_spec']['link_urls'])) {
            foreach ($creative['asset_feed_spec']['link_urls'] as $linkUrl) {
                if (isset($linkUrl['website_url'])) {
                    $urls[] = $linkUrl['website_url'];
                }
            }
        }

        return [
            'is_fraud' => false,
            'reason' => '',
            'urls' => $urls
        ];
    }

    /**
     * 从product set的filter中获取产品URL
     */
    private function getProductUrlsFromFilter($filter): array
    {
        if (!$filter) {
            return [
                'is_fraud' => true,
                'reason' => 'Product set没有filter',
                'urls' => []
            ];
        }

        $urls = [];

        // 处理不同的filter格式
        if (isset($filter['product_item_id']['eq'])) {
            // 格式 4.2.1
            $productId = $filter['product_item_id']['eq'];
            $product = FbCatalogProduct::where('source_id', $productId)->first();
            if (!$product) {
                return [
                    'is_fraud' => true,
                    'reason' => "产品不存在: {$productId}",
                    'urls' => []
                ];
            }
            $urls[] = $product->url;
        } elseif (isset($filter['retailer_id']['is_any'])) {
            // 格式 4.2.2
            $retailerIds = $filter['retailer_id']['is_any'];
            foreach ($retailerIds as $retailerId) {
                $product = FbCatalogProduct::where('retailer_id', $retailerId)->first();
                if (!$product) {
                    return [
                        'is_fraud' => true,
                        'reason' => "产品不存在 (retailer_id): {$retailerId}",
                        'urls' => []
                    ];
                }
                $urls[] = $product->url;
            }
        } elseif (isset($filter['retailer_id']['eq'])) {
            // 格式 4.2.3
            $retailerId = $filter['retailer_id']['eq'];
            $product = FbCatalogProduct::where('retailer_id', $retailerId)->first();
            if (!$product) {
                return [
                    'is_fraud' => true,
                    'reason' => "产品不存在 (retailer_id): {$retailerId}",
                    'urls' => []
                ];
            }
            $urls[] = $product->url;
        } elseif (isset($filter['or'])) {
            // 格式 4.2.4
            foreach ($filter['or'] as $orCondition) {
                if (isset($orCondition['product_item_id']['eq'])) {
                    $productId = $orCondition['product_item_id']['eq'];
                    $product = FbCatalogProduct::where('source_id', $productId)->first();
                    if (!$product) {
                        return [
                            'is_fraud' => true,
                            'reason' => "产品不存在: {$productId}",
                            'urls' => []
                        ];
                    }
                    $urls[] = $product->url;
                }
            }
        } elseif (isset($filter['product_item_id']['is_any'])) {
            // 格式 4.2.5
            $productIds = $filter['product_item_id']['is_any'];
            foreach ($productIds as $productId) {
                $product = FbCatalogProduct::where('source_id', $productId)->first();
                if (!$product) {
                    return [
                        'is_fraud' => true,
                        'reason' => "产品不存在: {$productId}",
                        'urls' => []
                    ];
                }
                $urls[] = $product->url;
            }
        } else {
            return [
                'is_fraud' => true,
                'reason' => '不支持的filter格式',
                'urls' => []
            ];
        }

        return [
            'is_fraud' => false,
            'reason' => '',
            'urls' => array_filter($urls) // 过滤空URL
        ];
    }

    /**
     * 检查URL是否在白名单中
     */
    private function checkUrlsAgainstWhitelist(array $urls): array
    {
        if (empty($urls)) {
            return [
                'is_fraud' => false,
                'reason' => '没有需要检查的URL'
            ];
        }

        // 获取所有激活的FraudConfig
        $fraudConfigs = FraudConfig::where('active', true)->get();

        if ($fraudConfigs->isEmpty()) {
            Log::info("没有激活的FraudConfig，跳过检查");
            return [
                'is_fraud' => false,
                'reason' => '没有激活的防盗刷配置'
            ];
        }

        // 合并所有白名单
        $domainWhitelist = [];
        $urlWhitelist = [];
        $hasDomainWhitelist = false;
        $hasUrlWhitelist = false;

        foreach ($fraudConfigs as $config) {
            if ($config->type === FraudConfigType::DomainWhitelist->value && $config->value) {
                $domainWhitelist = array_merge($domainWhitelist, $config->value);
                $hasDomainWhitelist = true;
            } elseif ($config->type === FraudConfigType::UrlWhitelist->value && $config->value) {
                $urlWhitelist = array_merge($urlWhitelist, $config->value);
                $hasUrlWhitelist = true;
            }
        }

        $domainWhitelist = array_unique($domainWhitelist);
        $urlWhitelist = array_unique($urlWhitelist);

        Log::info("防盗刷白名单检查", [
            'has_domain_whitelist' => $hasDomainWhitelist,
            'domain_count' => count($domainWhitelist),
            'has_url_whitelist' => $hasUrlWhitelist,
            'url_count' => count($urlWhitelist),
            'urls_to_check' => $urls
        ]);

        // 如果没有配置任何白名单，跳过检查
        if (!$hasDomainWhitelist && !$hasUrlWhitelist) {
            Log::info("没有配置任何白名单，跳过检查");
            return [
                'is_fraud' => false,
                'reason' => '没有配置任何白名单，允许所有URL'
            ];
        }

        // 收集所有异常信息
        $invalidUrls = [];
        $hasAnyFraud = false;

        // 检查每个URL
        foreach ($urls as $url) {
            if (!$url) continue;

            $parsedUrl = parse_url($url);
            if (!$parsedUrl) {
                $invalidUrls[] = "无效的URL格式: {$url}";
                $hasAnyFraud = true;
                continue;
            }

            $domain = $parsedUrl['host'] ?? '';

            // 移除查询参数的URL用于URL白名单检查
            $urlWithoutQuery = ($parsedUrl['scheme'] ?? 'https') . '://' . $domain . ($parsedUrl['path'] ?? '');

            $isUrlValid = false;

            // 检查域名白名单（如果配置了）
            if ($hasDomainWhitelist && in_array($domain, $domainWhitelist)) {
                $isUrlValid = true;
                Log::info("域名通过白名单检查", [
                    'domain' => $domain,
                    'url' => $url
                ]);
            }

            // 检查URL白名单（如果配置了且域名检查未通过）
            if (!$isUrlValid && $hasUrlWhitelist && in_array($urlWithoutQuery, $urlWhitelist)) {
                $isUrlValid = true;
                Log::info("URL通过白名单检查", [
                    'url_without_query' => $urlWithoutQuery,
                    'original_url' => $url
                ]);
            }

            // 如果配置了白名单但URL都不匹配，则为异常
            if (($hasDomainWhitelist || $hasUrlWhitelist) && !$isUrlValid) {
                $invalidUrls[] = $url;
                $hasAnyFraud = true;
                Log::warning("URL不在任何白名单中", [
                    'domain' => $domain,
                    'url_without_query' => $urlWithoutQuery,
                    'original_url' => $url,
                    'domain_whitelist' => $domainWhitelist,
                    'url_whitelist' => $urlWhitelist
                ]);
            }
        }

        // 如果发现任何异常，返回详细的异常信息
        if ($hasAnyFraud) {
            return [
                'is_fraud' => true,
                'reason' => "以下URL不在任何白名单中: " . implode(', ', array_unique($invalidUrls))
            ];
        }

        return [
            'is_fraud' => false,
            'reason' => '所有URL都通过了白名单检查'
        ];
    }
}
