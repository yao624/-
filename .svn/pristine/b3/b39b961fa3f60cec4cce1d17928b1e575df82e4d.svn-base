<?php

namespace App\Models;

use App\Utils\Telegram;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FbAd extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'notes',
        'fb_campaign_id',
        'fb_adset_id',
        'fb_page_id',
        'adset_id',
        'campaign_id',
        'configured_status',
        'created_time',
        'creative',
        'effective_status',
        'source_id',
        'name',
        'preview_shareable_link',
        'source_ad_id',
        'status',
        'post_url',
        'updated_time',
        'is_deleted_on_fb',
        'auto_add_languages',
    ];

    protected $casts = [
        'creative' => 'array',
        'is_deleted_on_fb' => 'boolean',
        'auto_add_languages' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        // 监听模型更新事件
        static::updating(function ($fbAd) {
            // 检查 effective_status 是否有变化
            if ($fbAd->isDirty('effective_status')) {
                $original = $fbAd->getOriginal('effective_status');
                $changed = $fbAd->effective_status;

                // 获取关联的 FbAdAccount 信息
                $adAccount = $fbAd->fbAdAccount;
                $adAccountId = $adAccount->source_id;
                $adAccountName = Str::limit($adAccount->name);
                $adName = $fbAd->name;
                $adID = $fbAd->source_id;
                // 记录日志

                $adAccountName = preg_replace('/[[:^print:]]/', '', $adAccountName);
                Log::info("Effective status changed from {$original} to {$changed} for Ad Account ID: {$adAccountId}, Name: {$adAccountName}");
                $message = "Ad status changed\r\n\t\tAd Account Name: {$adAccountName}\r\n\t\tAd Account ID: {$adAccountId}\r\n\t\tAd Name: {$adName}\r\n\t\tAd ID: {$adID}\r\n\t\tStatus from {$original} to {$changed}";
                Log::info($message);
                Telegram::sendMessage($message);

                // 检查是否变为 DISAPPROVED 且是多语言广告
                if ($changed === 'DISAPPROVED' && $original !== 'DISAPPROVED') {
                    static::handleDisapprovedAd($fbAd);
                }
            }

            // 防盗刷检测 - 检查creative是否有变化
            if ($fbAd->isDirty('creative')) {
                static::performFraudDetection($fbAd);
            }
        });
    }

    /**
     * 执行防盗刷检测
     */
    private static function performFraudDetection($fbAd)
    {
        try {
            // 预检查：如果没有配置白名单，直接跳过检测
            if (!\App\Services\FraudDetectionService::shouldPerformScan()) {
                Log::debug("没有配置防盗刷白名单，跳过检测", ['ad_id' => $fbAd->source_id]);
                return;
            }

            Log::info("执行防盗刷检测", ['ad_id' => $fbAd->source_id]);

            $fraudDetectionService = app(\App\Services\FraudDetectionService::class);
            $fraudActionsService = app(\App\Services\FraudActionsService::class);

            $detectionResult = $fraudDetectionService->checkAd($fbAd);
            $fraudActionsService->executeActions($fbAd, $detectionResult);

        } catch (\Exception $e) {
            Log::error("防盗刷检测失败", [
                'ad_id' => $fbAd->source_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Telegram::sendMessage("anti-fraud detect scan failed");
        }
    }

    /**
     * 处理被拒的广告
     */
    private static function handleDisapprovedAd($fbAd)
    {
        try {
            Log::info("处理被拒的广告", [
                'ad_id' => $fbAd->source_id,
                'ad_name' => $fbAd->name
            ]);

            // 检查是否开启了自动添加多语言功能
            if (!$fbAd->auto_add_languages) {
                Log::info("广告未开启自动添加多语言功能，跳过", [
                    'ad_id' => $fbAd->source_id
                ]);
                return;
            }

            // 检查是否是多语言广告
            if (!static::isMultiLanguageAd($fbAd)) {
                Log::info("广告不是多语言广告，跳过自动添加语言", [
                    'ad_id' => $fbAd->source_id
                ]);
                return;
            }

            // 获取广告账户信息
            $adAccount = $fbAd->fbAdAccountV2;
            if (!$adAccount) {
                Log::warning("无法获取广告账户信息", [
                    'ad_id' => $fbAd->source_id
                ]);
                return;
            }

            // 检查广告账户是否有有效的API Token和ACTIVE状态
            if (!static::hasValidApiToken($adAccount)) {
                Log::info("广告账户不满足条件，跳过自动添加语言", [
                    'ad_id' => $fbAd->source_id,
                    'ad_account_id' => $adAccount->source_id,
                    'account_status' => $adAccount->account_status,
                    'reason' => '需要account_status=ACTIVE且有token_type=1且active=true的API Token'
                ]);
                return;
            }

            // 自动添加一种语言
            $newPayload = static::addLanguageToAd($fbAd, 1);
            if (!$newPayload) {
                Log::warning("无法为广告添加新语言", [
                    'ad_id' => $fbAd->source_id
                ]);
                return;
            }

            // 分派更新Creative的Job
            \App\Jobs\FacebookUpdateAdCreative::dispatch(
                $adAccount->source_id,
                $fbAd->source_id,
                $newPayload
            )->delay(now()->addSeconds(30))->onQueue('facebook');

            Log::info("已为被拒广告自动分派添加语言任务", [
                'ad_id' => $fbAd->source_id,
                'ad_account_id' => $adAccount->source_id
            ]);

            // 发送Telegram通知
            $message = "Auto add language to disapproved ad\r\n\t\tAd ID: {$fbAd->source_id}\r\n\t\tAd Name: {$fbAd->name}\r\n\t\tAd Account: {$adAccount->name}";
            Telegram::sendMessage($message);

        } catch (\Exception $e) {
            Log::error("处理被拒广告失败", [
                'ad_id' => $fbAd->source_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 检查广告账户是否有有效的API Token和ACTIVE状态
     */
    public static function hasValidApiToken($adAccount)
    {
        // 检查广告账户状态是否为 ACTIVE
        if ($adAccount->account_status !== 'ACTIVE') {
            Log::info("广告账户状态不是ACTIVE", [
                'ad_account_id' => $adAccount->source_id,
                'account_status' => $adAccount->account_status
            ]);
            return false;
        }

        // 检查是否有有效的API Token
        $validToken = $adAccount->apiTokens()
            ->where('active', true)
            ->where('token_type', 1)
            ->first();

        if (!$validToken) {
            Log::info("广告账户没有有效的API Token", [
                'ad_account_id' => $adAccount->source_id
            ]);
            return false;
        }

        return true;
    }

    /**
     * 检查是否是目录广告
     */
    public static function isCatalogAd($fbAd)
    {
        return isset($fbAd->creative['product_set_id']) && !empty($fbAd->creative['product_set_id']);
    }

    /**
     * 检查是否是多语言广告
     */
    public static function isMultiLanguageAd($fbAd)
    {
        // 检查是否是目录广告
        if (static::isCatalogAd($fbAd)) {
            // 目录广告：检查 object_story_spec.template_data.customization_rules_spec
            return isset($fbAd->creative['object_story_spec']['template_data']['customization_rules_spec'])
                && !empty($fbAd->creative['object_story_spec']['template_data']['customization_rules_spec']);
        }

        // 普通广告：检查 asset_feed_spec
        return isset($fbAd->creative['asset_feed_spec']) && !empty($fbAd->creative['asset_feed_spec']);
    }

    /**
     * 为广告添加指定数量的语言
     *
     * @param FbAd $fbAd 广告对象
     * @param int $languageCount 要添加的语言数量
     * @return array|null 新的payload或null
     */
    public static function addLanguageToAd($fbAd, $languageCount = 1)
    {
        try {
            $creative = $fbAd->creative;

            // 检查是否是目录广告
            $isCatalogAd = static::isCatalogAd($fbAd);

            // 检查广告类型并验证多语言支持
            if ($isCatalogAd) {
                if (!isset($creative['object_story_spec']['template_data']['customization_rules_spec'])) {
                    Log::warning("目录广告不是多语言广告，无法添加语言", [
                        'ad_id' => $fbAd->source_id
                    ]);
                    return null;
                }
            } else {
                if (!isset($creative['asset_feed_spec'])) {
                    Log::warning("广告不是多语言广告，无法添加语言", [
                        'ad_id' => $fbAd->source_id
                    ]);
                    return null;
                }
            }

            // 获取当前的语言列表
            $currentLanguages = static::getCurrentLanguages($creative);
            Log::info("当前广告的语言", [
                'ad_id' => $fbAd->source_id,
                'languages' => $currentLanguages
            ]);

            // 从addition.json获取可添加的语言
            $additionLanguages = static::getAdditionLanguages();

            // 过滤掉已经存在的语言
            $availableLanguages = array_filter($additionLanguages, function($lang) use ($currentLanguages) {
                return !in_array($lang['label_name'], $currentLanguages);
            });

            if (empty($availableLanguages)) {
                Log::warning("没有可添加的新语言", [
                    'ad_id' => $fbAd->source_id
                ]);
                return null;
            }

            // 随机选择要添加的语言
            $availableLanguagesArray = array_values($availableLanguages);
            $totalAvailable = count($availableLanguagesArray);

            // 确保不选择超过可用语言数量的语言
            $actualLanguageCount = min($languageCount, $totalAvailable);

            if ($actualLanguageCount === 1) {
                // 只选择一种语言时，直接随机选择一个
                $randomIndex = array_rand($availableLanguagesArray);
                $selectedLanguages = [$availableLanguagesArray[$randomIndex]];
            } else {
                // 选择多种语言时，随机打乱数组后取前N个
                shuffle($availableLanguagesArray);
                $selectedLanguages = array_slice($availableLanguagesArray, 0, $actualLanguageCount);
            }

            Log::info("随机选择的新语言", [
                'ad_id' => $fbAd->source_id,
                'total_available' => $totalAvailable,
                'requested_count' => $languageCount,
                'actual_count' => $actualLanguageCount,
                'selected_languages' => array_column($selectedLanguages, 'label_name')
            ]);

            // 构建新的payload
            $newPayload = static::buildNewPayload($creative, $selectedLanguages, $isCatalogAd);

            return $newPayload;

        } catch (\Exception $e) {
            Log::error("添加语言失败", [
                'ad_id' => $fbAd->source_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 获取当前广告的语言列表
     */
    private static function getCurrentLanguages($creative)
    {
        $languages = [];

        // 检查是否是目录广告
        if (isset($creative['product_set_id'])) {
            // 目录广告：从 customization_rules_spec 获取语言
            if (isset($creative['object_story_spec']['template_data']['customization_rules_spec'])) {
                foreach ($creative['object_story_spec']['template_data']['customization_rules_spec'] as $rule) {
                    if (isset($rule['customization_spec']['language'])) {
                        $languages[] = $rule['customization_spec']['language'];
                    }
                }
            }
        } else {
            // 普通广告：从 asset_feed_spec 获取语言
            if (isset($creative['asset_feed_spec']['asset_customization_rules'])) {
                foreach ($creative['asset_feed_spec']['asset_customization_rules'] as $rule) {
                    if (isset($rule['body_label']['name'])) {
                        $languages[] = $rule['body_label']['name'];
                    }
                }
            }
        }

        return array_unique($languages);
    }

    /**
     * 获取可添加的语言列表
     */
    private static function getAdditionLanguages()
    {
        $configPath = config_path('addition.json');
        if (!file_exists($configPath)) {
            Log::error("addition.json 配置文件不存在");
            return [];
        }

        $jsonContent = file_get_contents($configPath);
        $languages = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("addition.json 配置文件解析失败: " . json_last_error_msg());
            return [];
        }

        return $languages;
    }

    /**
     * 构建新的payload
     */
    private static function buildNewPayload($creative, $selectedLanguages, $isCatalogAd = false)
    {
        // 构建新的payload，只包含必要的字段
        $newPayload = [];

        // 1. 添加 object_story_spec
        if (isset($creative['object_story_spec'])) {
            $newPayload['object_story_spec'] = $creative['object_story_spec'];
        }

        // 2. 添加 url_tags（如果有的话，否则使用默认值）
        if (isset($creative['url_tags'])) {
            $newPayload['url_tags'] = $creative['url_tags'];
        } else {
            $newPayload['url_tags'] = 'fbclid={fbclid}&utm_campaign={{campaign.name}}&utm_source={{site_source_name}}&utm_placement={{placement}}&campaign_id={{campaign.id}}&adset_id={{adset.id}}&ad_id={{ad.id}}&adset_name={{adset.name}}&pixel={{pixel}}&ad_name={{ad.name}}';
        }

        // 3. 根据广告类型构建不同的结构
        if ($isCatalogAd) {
            // 目录广告：构建 customization_rules_spec 和 product_set_id
            return static::buildCatalogAdPayload($newPayload, $creative, $selectedLanguages);
        } else {
            // 普通广告：构建 asset_feed_spec
            return static::buildRegularAdPayload($newPayload, $creative, $selectedLanguages);
        }
    }

    /**
     * 构建目录广告的payload
     */
    private static function buildCatalogAdPayload($newPayload, $creative, $selectedLanguages)
    {
        // 添加 product_set_id
        if (isset($creative['product_set_id'])) {
            $newPayload['product_set_id'] = $creative['product_set_id'];
        }

        // 获取现有的 customization_rules_spec
        $originalRulesSpec = $creative['object_story_spec']['template_data']['customization_rules_spec'] ?? [];

        // 找到默认语言的规则（只有 customization_spec 字段的）
        $defaultRule = null;
        foreach ($originalRulesSpec as $rule) {
            if (isset($rule['customization_spec']) && count($rule) === 1) {
                $defaultRule = $rule;
                break;
            }
        }

        if (!$defaultRule) {
            Log::error("找不到目录广告的默认语言规则");
            return null;
        }

        // 获取默认的 template_data 中的字段
        $templateData = $creative['object_story_spec']['template_data'];
        $defaultLink = $templateData['link'] ?? '';
        $defaultName = $templateData['name'] ?? '';
        $defaultMessage = $templateData['message'] ?? '';
        $defaultDescription = $templateData['description'] ?? '';

        // 获取 template_url_spec
        $templateUrlSpec = $creative['template_url_spec'] ?? null;

        // 为每种新语言添加规则
        foreach ($selectedLanguages as $language) {
            $newLanguageCode = $language['label_name'];

            // 构建新的规则
            $newRule = [
                'link' => $defaultLink,
                'name' => $defaultName,
                'message' => $defaultMessage,
                'description' => $defaultDescription,
                'customization_spec' => [
                    'language' => $newLanguageCode
                ]
            ];

            // 添加 template_url_spec
            if ($templateUrlSpec) {
                $newRule['template_url_spec'] = $templateUrlSpec;
            }

            $newPayload['object_story_spec']['template_data']['customization_rules_spec'][] = $newRule;
        }

        Log::info("构建的目录广告payload字段", [
            'payload_keys' => array_keys($newPayload),
            'added_languages' => array_column($selectedLanguages, 'label_name')
        ]);

        return $newPayload;
    }

    /**
     * 构建普通广告的payload
     */
    private static function buildRegularAdPayload($newPayload, $creative, $selectedLanguages)
    {
        // 3. 构建新的 asset_feed_spec，只包含必要的字段
        $originalAssetFeedSpec = $creative['asset_feed_spec'];
        $newAssetFeedSpec = [];

        // 复制固定字段（不包含adlabels的字段）
        $fixedFields = ['call_to_action_types', 'ad_formats'];
        foreach ($fixedFields as $field) {
            if (isset($originalAssetFeedSpec[$field])) {
                $newAssetFeedSpec[$field] = $originalAssetFeedSpec[$field];
            }
        }

        // 复制并清理包含adlabels的字段
        $adlabelFields = ['bodies', 'titles', 'descriptions', 'link_urls', 'videos', 'images'];
        foreach ($adlabelFields as $field) {
            if (isset($originalAssetFeedSpec[$field])) {
                $newAssetFeedSpec[$field] = static::cleanAdlabels($originalAssetFeedSpec[$field]);
            }
        }

        // 复制并清理asset_customization_rules
        if (isset($originalAssetFeedSpec['asset_customization_rules'])) {
            $newAssetFeedSpec['asset_customization_rules'] = static::cleanAssetCustomizationRules($originalAssetFeedSpec['asset_customization_rules']);
        }

        // 获取默认语言的数据（is_default = true）
        $defaultRule = null;
        if (isset($newAssetFeedSpec['asset_customization_rules'])) {
            foreach ($newAssetFeedSpec['asset_customization_rules'] as $rule) {
                if (isset($rule['is_default']) && $rule['is_default'] === true) {
                    $defaultRule = $rule;
                    break;
                }
            }
        }

        if (!$defaultRule) {
            Log::error("找不到默认语言规则");
            return null;
        }

        $defaultLabelName = $defaultRule['body_label']['name'];

        // 为每种新语言添加数据
        foreach ($selectedLanguages as $language) {
            $newLabelName = $language['label_name'];

            // 添加到asset_customization_rules
            $newRule = [
                'body_label' => ['name' => $newLabelName],
                'title_label' => ['name' => $newLabelName],
                'description_label' => ['name' => $newLabelName],
                'link_url_label' => ['name' => $newLabelName],
                'is_default' => false
            ];

            // 根据广告类型添加相应的label
            if (isset($defaultRule['video_label'])) {
                $newRule['video_label'] = ['name' => $defaultLabelName]; // 使用默认语言的视频
            }
            if (isset($defaultRule['image_label'])) {
                $newRule['image_label'] = ['name' => $defaultLabelName]; // 使用默认语言的图片
            }

            // 添加customization_spec，只包含locales字段，值来自addition.json
            if (isset($language['locales'])) {
                $newRule['customization_spec'] = [
                    'locales' => $language['locales']
                ];
            }

            $newAssetFeedSpec['asset_customization_rules'][] = $newRule;

            // 添加到bodies, titles, descriptions, link_urls
            $defaultBodyText = static::findDefaultText($originalAssetFeedSpec['bodies'] ?? [], $defaultLabelName);
            $defaultTitleText = static::findDefaultText($originalAssetFeedSpec['titles'] ?? [], $defaultLabelName);
            $defaultDescriptionText = static::findDefaultText($originalAssetFeedSpec['descriptions'] ?? [], $defaultLabelName);
            $defaultLinkUrl = static::findDefaultLinkUrl($originalAssetFeedSpec['link_urls'] ?? [], $defaultLabelName);

            // 添加body
            if ($defaultBodyText !== null) {
                $newAssetFeedSpec['bodies'][] = [
                    'text' => $defaultBodyText,
                    'adlabels' => [['name' => $newLabelName]]
                ];
            }

            // 添加title
            if ($defaultTitleText !== null) {
                $newAssetFeedSpec['titles'][] = [
                    'text' => $defaultTitleText,
                    'adlabels' => [['name' => $newLabelName]]
                ];
            }

            // 添加description
            if ($defaultDescriptionText !== null) {
                $newAssetFeedSpec['descriptions'][] = [
                    'text' => $defaultDescriptionText,
                    'adlabels' => [['name' => $newLabelName]]
                ];
            }

            // 添加link_url
            if ($defaultLinkUrl !== null) {
                $newAssetFeedSpec['link_urls'][] = [
                    'website_url' => $defaultLinkUrl,
                    'adlabels' => [['name' => $newLabelName]]
                ];
            }
        }

        // 将构建的asset_feed_spec添加到payload中
        $newPayload['asset_feed_spec'] = $newAssetFeedSpec;

        Log::info("构建的普通广告payload字段", [
            'payload_keys' => array_keys($newPayload),
            'asset_feed_spec_keys' => array_keys($newAssetFeedSpec)
        ]);

        return $newPayload;
    }

    /**
     * 清理adlabels中的id字段
     */
    private static function cleanAdlabels($items)
    {
        if (!is_array($items)) {
            return $items;
        }

        $cleanedItems = [];
        foreach ($items as $item) {
            $cleanedItem = [];

            // 根据不同类型的字段保留不同的属性
            if (isset($item['text'])) {
                // bodies, titles, descriptions 类型
                $cleanedItem['text'] = $item['text'];
            } elseif (isset($item['website_url'])) {
                // link_urls 类型，只保留website_url，移除display_url等字段
                $cleanedItem['website_url'] = $item['website_url'];
            } elseif (isset($item['hash'])) {
                // images 类型
                $cleanedItem['hash'] = $item['hash'];
            } elseif (isset($item['video_id'])) {
                // videos 类型
                $cleanedItem['video_id'] = $item['video_id'];
                if (isset($item['thumbnail_url'])) {
                    $cleanedItem['thumbnail_url'] = $item['thumbnail_url'];
                }
            }

            // 清理adlabels，只保留name字段
            if (isset($item['adlabels']) && is_array($item['adlabels'])) {
                $cleanedAdlabels = [];
                foreach ($item['adlabels'] as $adlabel) {
                    // 只保留name字段，去掉id字段
                    if (isset($adlabel['name'])) {
                        $cleanedAdlabels[] = ['name' => $adlabel['name']];
                    }
                }
                $cleanedItem['adlabels'] = $cleanedAdlabels;
            }

            $cleanedItems[] = $cleanedItem;
        }

        return $cleanedItems;
    }

    /**
     * 清理asset_customization_rules中的id字段
     */
    private static function cleanAssetCustomizationRules($rules)
    {
        if (!is_array($rules)) {
            return $rules;
        }

        $cleanedRules = [];
        foreach ($rules as $rule) {
            $cleanedRule = [];
            foreach ($rule as $key => $value) {
                if (in_array($key, ['body_label', 'title_label', 'description_label', 'link_url_label', 'video_label', 'image_label'])) {
                    // 清理label字段，只保留name
                    if (isset($value['name'])) {
                        $cleanedRule[$key] = ['name' => $value['name']];
                    }
                } elseif ($key === 'customization_spec') {
                    // 清理customization_spec，只保留locales字段
                    if (isset($value['locales'])) {
                        $cleanedRule[$key] = ['locales' => $value['locales']];
                    }
                } else {
                    // 其他字段直接复制（如is_default）
                    $cleanedRule[$key] = $value;
                }
            }
            $cleanedRules[] = $cleanedRule;
        }

        return $cleanedRules;
    }

    /**
     * 查找默认语言的文本
     */
    private static function findDefaultText($items, $defaultLabelName)
    {
        foreach ($items as $item) {
            if (isset($item['adlabels'])) {
                foreach ($item['adlabels'] as $label) {
                    if ($label['name'] === $defaultLabelName) {
                        return $item['text'] ?? '';
                    }
                }
            }
        }
        return null;
    }

    /**
     * 查找默认语言的链接URL
     */
    private static function findDefaultLinkUrl($items, $defaultLabelName)
    {
        foreach ($items as $item) {
            if (isset($item['adlabels'])) {
                foreach ($item['adlabels'] as $label) {
                    if ($label['name'] === $defaultLabelName) {
                        return $item['website_url'] ?? '';
                    }
                }
            }
        }
        return null;
    }

    public function tags() {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function fbCampaign()
    {
        return $this->belongsTo(FbCampaign::class);
    }

    public function fbAdset()
    {
        return $this->belongsTo(FbAdset::class);
    }

    public function fbPages()
    {
        return $this->belongsToMany(FbPage::class,'fb_ad_fb_page')
            ->using(CustomPivot::class)
            ->withPivot('fb_page_source_id')
            ->withTimestamps();
    }

    public function fbAdAccount()
    {
        return $this->fbCampaign->fbAdAccount();
    }

    public function fbAdAccountV2()
    {
        return $this->hasOneThrough(
            FbAdAccount::class,     // 目标模型
            FbCampaign::class,      // 中间模型
            'id',                   // 中间模型的外键在目标模型中的引用（FbCampaign的id）
            'id',                   // 目标模型的外键（FbAdAccount的id）
            'fb_campaign_id',      // 当前模型（FbAd）的外键
            'fb_ad_account_id'                   // 中间模型（FbCampaign）的本地键
        );
    }

    public function offerClicks()
    {
        return $this->hasMany(Click::class, 'fb_ad_source_id', 'source_id');
    }

    public function offerConversions()
    {
        return $this->hasMany(Conversion::class, 'fb_ad_source_id', 'source_id');
    }

    public function insights()
    {
        return $this->hasMany(FbAdInsight::class, 'ad_id', 'source_id');
    }

    public function rules()
    {
        return $this->morphToMany(Rule::class, 'ruleable');
    }

    public function get_metrics($startDate, $endDate, $timezone)
    {
        Log::debug("fb campaign");
        Log::info($this->fbCampaign->fbAdAccount);
        $insights = $this->insights()->whereBetween('date_start', [$startDate, $endDate])->get();

        // 转换日期到 AdAccount 时区
        $startDateInTimeZone = Carbon::createFromFormat('Y-m-d', $startDate, 'UTC')
            ->setTimezone($timezone)
            ->startOfDay();

        $endDateInTimeZone = Carbon::createFromFormat('Y-m-d', $endDate, 'UTC')
            ->setTimezone($timezone)
            ->endOfDay();

        $offerClicksCount = $this->offerClicks()
            ->whereBetween('clicks.click_datetime', [$startDateInTimeZone, $endDateInTimeZone])
            ->count();

        $offerConversionEvent = $this->offerConversions()
            ->whereBetween('conversions.conversion_datetime', [$startDateInTimeZone, $endDateInTimeZone]);
        $offerConversionsCount = (clone $offerConversionEvent)->where('price', '>', 0)->count();
        $offerConversionsValue = (clone $offerConversionEvent)->where('price', '>', 0)->sum('price');
        $offerLeads = (clone $offerConversionEvent)->where('price', '=', 0)->count();

        $total_spend = $insights->sum('spend');

        if ($offerLeads==0) {
            $taken_rate = 0;
        } else {
            $taken_rate = $offerConversionsCount / $offerLeads * 100;
            $taken_rate = round($taken_rate, 2);
        }
        if ($offerClicksCount > 0) {
            $offer_cpc = round($total_spend / $offerClicksCount, 2);
            $offer_epc = round($offerConversionsValue / $offerClicksCount, 2);
        } else {
            $offer_cpc = $total_spend;
            $offer_epc = null;
        }


        $aggregated = [
            'ad_account_id' => $this->fbAdAccount->source_id,
            'ad_account_name' => $this->fbAdAccount->name,
            'account_status' => $this->fbAdAccount->account_status,
            'disable_reason' => $this->fbAdAccount->disable_reason,
            'adtrust_dsl' => $this->fbAdAccount->adtrust_dsl,
            'currency' => $this->fbAdAccount->currency,
            'timezone' => $this->fbAdAccount->timezone_name,
            'campaign_id' => $this->fbCampaign->source_id,
            'campaign_name' => $this->fbCampaign->name,
            'adset_id' => $this->fbAdset->source_id,
            'adset_name' => $this->fbAdset->name,
            'ad_id' => $this->source_id,
            'ad_name' => $this->name,
            'impressions' => $insights->sum('impressions'),
            'daily_budget' => $this->daily_budget,
            'reach' => $insights->sum('reach'),
            'spend' => round($total_spend, 2),
            'purchase_roas' => round($insights->avg('purchase_roas_value'), 2),
            'frequency' => round($insights->avg('frequency'), 2),
            'clicks' => $insights->sum('clicks'),
            'link_clicks' => $insights->sum('inline_link_clicks'),
            'cpm' => round($insights->avg('cpm'), 2),
            'cpc' => round($insights->avg('cpc'), 2),
            'ctr' => round($insights->avg('ctr'), 2),
            'link_ctr' => round($insights->avg('inline_link_click_ctr'), 2),
            'link_cpc' => round($insights->avg('cost_per_inline_link_click'), 2),
            'add_to_cart' => $insights->sum('add_to_cart'),
            'purchase' => $insights->sum('purchase'),
            'lead' => $insights->sum('lead'),
            'comment' => $insights->sum('comment'),
            'cost_per_purchase' => round($insights->avg('cost_per_purchase'), 2),
            'cost_per_lead' => round($insights->avg('cost_per_lead'), 2),
            'cost_to_add_to_cart' => round($insights->avg('cost_to_add_to_cart'), 2),
            'offer_clicks' => $offerClicksCount,
            'offer_leads' => $offerLeads,
            'offer_conversions' => $offerConversionsCount,
            'offer_conversions_value' => $offerConversionsValue,
            'taken_rate' => $taken_rate,
            'offer_cpc' => $offer_cpc,
            'offer_epc' => $offer_epc,
            'enable_rule' => $this->fbAdAccount->enable_rule
        ];

        return $aggregated;
    }

    public function post()
    {
        return $this->belongsTo(FbPagePost::class, 'source_id', 'ad_source_id');
    }

}
