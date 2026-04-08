<?php

namespace App\Services;

use App\Enums\BidStrategy;
use App\Enums\OperatorType;
use App\Models\AdLog;
use App\Models\AdLogPivotAd;
use App\Models\FbAdTemplate;
use App\Models\FbAd;
use App\Models\FbAdset;
use App\Models\MetaAdCreationRecord;
use App\Models\MetaAdCreationRecordAd;
use App\Models\MetaAdCreationRecordAdset;
use App\Jobs\FacebookCreateCampaignV2;
use App\Support\MetaAdCreationSplitRules;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * 将 9 步 form_data 转为 FbAdTemplate + launch options，并调度现有 FacebookCreateCampaignV2 链路对接 Meta API。
 */
class MetaAdCreationLaunchService
{
    /**
     * 从 form_data 构建 FbAdTemplate 属性并创建模板行（用于调度现有 Job）。
     *
     * @param  array<string, mixed>|null  $targetingPackage  单条定向包数据；为 null 时从 stepTargeting.packages[0] 或旧版扁平结构读取。
     * @param  int|null  $packageIndex  与 stepTargeting.packages / stepBidBudget.packages 同一下标，用于合并每条广告的出价排期。
     */
    public function createTemplateFromFormData(array $formData, string $userId, ?array $targetingPackage = null, ?int $packageIndex = null): FbAdTemplate
    {
        $stepOne = $formData['stepOne'] ?? [];
        $stepTwo = $formData['stepTwo'] ?? [];
        $stepDelivery = $formData['stepDelivery'] ?? [];
        $idx = $packageIndex ?? 0;
        $stepTargeting = $targetingPackage;
        if ($stepTargeting === null) {
            $raw = $formData['stepTargeting'] ?? [];
            if (! empty($raw['packages']) && is_array($raw['packages'])) {
                $stepTargeting = $raw['packages'][0] ?? [];
            } else {
                $stepTargeting = is_array($raw) ? $raw : [];
            }
        }
        // 每条定向包可带独立 stepRegion（与 Meta 每条 Ad Set 的 targeting.geo_locations 一致）
        $globalStepRegion = $formData['stepRegion'] ?? [];
        $stepRegion = $this->resolveEffectiveStepRegionForPackage($globalStepRegion, $stepTargeting);
        $stepBidBudget = $this->mergeStepBidBudgetForIndex($formData, $idx);
        $stepPlacement = $formData['stepPlacement'] ?? [];
        $stepCreativeSettings = $formData['stepCreativeSettings'] ?? [];
        $stepCreativeGroup = $formData['stepCreativeGroup'] ?? [];

        $conversionLocation = $this->mapConversionLocation($stepOne['conversionLocation'] ?? 'website');
        $budgetType = ($stepTwo['budgetType'] ?? 'daily') === 'lifetime' ? 'lifetime' : 'daily';
        $budget = isset($stepTwo['budget']) ? (float) $stepTwo['budget'] : 100;
        if ($budget <= 0) {
            $budget = 100;
        }

        $bidMapped = $this->mapStepTwoBidToTemplate($stepTwo, $stepBidBudget);

        $objective = $stepOne['objective'] ?? 'OUTCOME_SALES';
        $appEvent = $stepBidBudget['appEvent'] ?? null;

        [$optimizationGoal, $pixelEvent] = $this->resolveOptimizationAndConversionEvent(
            $objective,
            $conversionLocation,
            $stepCreativeSettings,
            $stepBidBudget,
            $appEvent
        );
        [$optimizationGoal, $pixelEvent] = $this->applyBidBudgetGoalOverrides(
            $objective,
            $conversionLocation,
            $stepBidBudget,
            $optimizationGoal,
            $pixelEvent
        );

        $notes = 'From 9-step meta ad creation';
        $promoteUrl = trim((string) ($stepDelivery['promoteWebsiteUrl'] ?? ''));
        if ($conversionLocation === 'WEBSITE' && $promoteUrl !== '') {
            $notes .= ' | promote_url:' . $promoteUrl;
        }
        $store = $stepDelivery['store'] ?? null;
        if ($conversionLocation === 'APP' && $store !== null && $store !== '') {
            $notes .= ' | app_store:' . (string) $store;
        }
        if (!empty($stepTargeting['targetInstalled'])) {
            $notes .= ' | prefer_app_install_users:1';
        }

        $pkgName = trim((string) ($stepTargeting['name'] ?? ''));
        if ($pkgName !== '') {
            $notes .= ' | targeting_pkg:' . $pkgName;
        }

        [$ageMin, $ageMax] = $this->mapTargetingAge($stepTargeting, $stepRegion);

        $deliveryAdset = trim((string) ($stepDelivery['name'] ?? ''));
        $adsetPattern = $pkgName !== ''
            ? $pkgName . '-{{date}}-{{random}}'
            : ($deliveryAdset !== '' ? $deliveryAdset . '-{{date}}-{{random}}' : 'adset-{{date}}-{{random}}');

        $adNameCreative = trim((string) ($stepCreativeSettings['adName'] ?? ''));
        if ($adNameCreative !== '') {
            $adNamePattern = str_contains($adNameCreative, '{{date}}') || str_contains($adNameCreative, '{{random}}')
                ? $adNameCreative
                : $adNameCreative . '-{{date}}-{{random}}';
        } else {
            $cgLabel = $this->resolveCreativeGroupLabel($stepCreativeGroup);
            $adNamePattern = $cgLabel !== ''
                ? $cgLabel . '-{{date}}-{{random}}'
                : 'ad-{{date}}-{{random}}';
        }

        if (! empty($stepCreativeSettings['usePageAsIdentity'])) {
            $notes .= ' | use_page_identity:1';
        }
        if (! empty($stepCreativeSettings['multiAdvertiser'])) {
            $notes .= ' | multi_advertiser:1';
        }

        $attrs = [
            'name' => 'meta-9step-' . substr(uniqid(), -6),
            'notes' => $notes,
            'campaign_name' => !empty($stepTwo['campaignName']) ? $stepTwo['campaignName'] . '-{{date}}-{{random}}' : 'camp-{{date}}-{{random}}',
            'adset_name' => $adsetPattern,
            'ad_name' => $adNamePattern,
            'bid_strategy' => $bidMapped['bid_strategy'],
            'bid_amount' => $bidMapped['bid_amount'],
            'budget_level' => 'campaign',
            'budget_type' => $budgetType,
            'budget' => (string) $budget,
            'objective' => $objective,
            'accelerated' => (($stepBidBudget['deliveryType'] ?? $stepTwo['deliveryType'] ?? 'standard') === 'accelerated'),
            'conversion_location' => $conversionLocation,
            'optimization_goal' => $optimizationGoal,
            'pixel_event' => $pixelEvent,
            'advantage_plus_audience' => (bool) ($stepTargeting['advancedAudience'] ?? false),
            'genders' => $this->mapGenderToMeta($stepTargeting['gender'] ?? $stepTargeting['genders'] ?? 'all'),
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'user_id' => $userId,
        ];

        // 地区与定向（与 FacebookCreateAdsetV2 一致的结构）
        if (!empty($stepRegion['countries_included'])) {
            $attrs['countries_included'] = is_array($stepRegion['countries_included']) ? $stepRegion['countries_included'] : [];
        }
        if (!empty($stepRegion['countries_excluded'])) {
            $attrs['countries_excluded'] = is_array($stepRegion['countries_excluded']) ? $stepRegion['countries_excluded'] : [];
        }
        if (!empty($stepRegion['regions_included'])) {
            $attrs['regions_included'] = is_array($stepRegion['regions_included']) ? $stepRegion['regions_included'] : [];
        }
        if (!empty($stepRegion['regions_excluded'])) {
            $attrs['regions_excluded'] = is_array($stepRegion['regions_excluded']) ? $stepRegion['regions_excluded'] : [];
        }
        if (!empty($stepRegion['cities_included'])) {
            $attrs['cities_included'] = is_array($stepRegion['cities_included']) ? $stepRegion['cities_included'] : [];
        }
        if (!empty($stepRegion['cities_excluded'])) {
            $attrs['cities_excluded'] = is_array($stepRegion['cities_excluded']) ? $stepRegion['cities_excluded'] : [];
        }
        // 旧数据：定向对象上直接带地理字段；若已启用「每广告组独立地区」则不再用此处覆盖
        if (! $this->isUseCustomRegion($stepTargeting)) {
            if (!empty($stepTargeting['countries_included'])) {
                $attrs['countries_included'] = is_array($stepTargeting['countries_included']) ? $stepTargeting['countries_included'] : ($attrs['countries_included'] ?? []);
            }
            if (!empty($stepTargeting['cities_included'])) {
                $attrs['cities_included'] = is_array($stepTargeting['cities_included']) ? $stepTargeting['cities_included'] : [];
            }
            if (!empty($stepTargeting['cities_excluded'])) {
                $attrs['cities_excluded'] = is_array($stepTargeting['cities_excluded']) ? $stepTargeting['cities_excluded'] : [];
            }
            if (!empty($stepTargeting['regions_included'])) {
                $attrs['regions_included'] = is_array($stepTargeting['regions_included']) ? $stepTargeting['regions_included'] : [];
            }
            if (!empty($stepTargeting['regions_excluded'])) {
                $attrs['regions_excluded'] = is_array($stepTargeting['regions_excluded']) ? $stepTargeting['regions_excluded'] : [];
            }
        }
        if (!empty($stepTargeting['locales'])) {
            $attrs['locales'] = is_array($stepTargeting['locales']) ? $stepTargeting['locales'] : [];
        } elseif (!empty($stepTargeting['language'])) {
            $attrs['locales'] = $this->mapLanguageToLocales($stepTargeting['language']);
        }
        if (!empty($stepTargeting['interests'])) {
            $attrs['interests'] = is_array($stepTargeting['interests']) ? $stepTargeting['interests'] : [];
        }
        if (($stepTargeting['detailedTargeting'] ?? 'unlimited') === 'unlimited') {
            $attrs['interests'] = [];
        }
        $placement = $this->mapPlacementToTemplateFields($stepPlacement);
        foreach ($placement as $k => $v) {
            $attrs[$k] = $v;
        }
        // 兼容旧数据：若 stepPlacement 没数据，仍允许从 stepRegion 兜底平台
        if (empty($attrs['publisher_platforms']) && !empty($stepRegion['publisher_platforms'])) {
            $attrs['publisher_platforms'] = is_array($stepRegion['publisher_platforms']) ? $stepRegion['publisher_platforms'] : [];
        }
        if (!empty($stepTargeting['devices']) && is_array($stepTargeting['devices'])) {
            $allowed = ['mobile', 'desktop'];
            $attrs['device_platforms'] = array_values(array_intersect($allowed, array_map('strval', $stepTargeting['devices'])));
        } elseif (!empty($stepRegion['device_platforms'])) {
            $attrs['device_platforms'] = is_array($stepRegion['device_platforms']) ? $stepRegion['device_platforms'] : ['mobile'];
        }

        if (!empty($stepTargeting['wifiOnly']) || !empty($stepTargeting['wifi_only'])) {
            $attrs['wireless_carrier'] = true;
        }

        $userOs = [];
        if (!empty($stepTargeting['osVersionMin'])) {
            $userOs[] = (string) $stepTargeting['osVersionMin'];
        }
        if (!empty($stepTargeting['osVersionMax'])) {
            $userOs[] = (string) $stepTargeting['osVersionMax'];
        }
        if ($userOs !== []) {
            $attrs['user_os'] = array_values(array_unique($userOs));
        }

        $bbMeta = $this->buildBidBudgetMetaForNotes($stepBidBudget);
        if ($bbMeta !== []) {
            $attrs['notes'] = ($attrs['notes'] ?? '').' |bb_meta:'.base64_encode(json_encode($bbMeta, JSON_UNESCAPED_UNICODE));
        }

        return FbAdTemplate::create($attrs);
    }

    /**
     * stepBidBudget.packages[$index] 覆盖同名字段（与定向包同索引对应一条 Ad Set）。
     *
     * @return array<string, mixed>
     */
    private function mergeStepBidBudgetForIndex(array $formData, int $index): array
    {
        $bb = $formData['stepBidBudget'] ?? [];
        if (! is_array($bb)) {
            return [];
        }
        $pkg = $bb['packages'][$index] ?? null;
        if (is_array($pkg) && $pkg !== []) {
            return array_merge($bb, $pkg);
        }

        return $bb;
    }

    /**
     * 前端 step-bid-budget 成效目标 → 覆盖 resolveOptimization 结果（须与系列 objective 可共存）。
     *
     * @return array{0: string, 1: string}
     */
    private function applyBidBudgetGoalOverrides(
        string $objective,
        string $conversionLocation,
        array $stepBidBudget,
        string $optimizationGoal,
        string $pixelEvent
    ): array {
        $goal = $stepBidBudget['goal'] ?? null;
        if ($goal === null || $goal === '') {
            return [$optimizationGoal, $pixelEvent];
        }
        if ($goal === 'link_clicks') {
            return ['LINK_CLICKS', ''];
        }
        if ($goal === 'reach') {
            return ['REACH', ''];
        }
        if ($goal === 'app_installs') {
            return ['APP_INSTALLS', ''];
        }
        if ($goal === 'offsite_conversions') {
            $evt = $stepBidBudget['websitePixelEvent'] ?? $pixelEvent;

            return ['OFFSITE_CONVERSIONS', (string) (($evt !== null && $evt !== '') ? $evt : 'PURCHASE')];
        }
        if ($goal === 'conversion_value') {
            $evt = $conversionLocation === 'APP'
                ? ($stepBidBudget['appEvent'] ?? $pixelEvent)
                : ($stepBidBudget['websitePixelEvent'] ?? $pixelEvent);

            return ['VALUE', (string) (($evt !== null && $evt !== '') ? $evt : 'PURCHASE')];
        }
        if ($goal === 'app_events') {
            $evt = $stepBidBudget['appEvent'] ?? $pixelEvent;
            if ($conversionLocation === 'APP' && $evt !== null && $evt !== '') {
                return ['OFFSITE_CONVERSIONS', (string) $evt];
            }

            return [$optimizationGoal, $pixelEvent];
        }

        return [$optimizationGoal, $pixelEvent];
    }

    /**
     * 供 FacebookCreateAdsetV2 解析：billing_event、排期、花费限额等（见 Meta Ad Set 字段）。
     *
     * @return array<string, mixed>
     */
    private function buildBidBudgetMetaForNotes(array $stepBidBudget): array
    {
        $billing = $stepBidBudget['billing'] ?? 'impressions';
        $billingEvent = $billing === 'cpc' ? 'LINK_CLICKS' : 'IMPRESSIONS';
        $meta = [
            'billing_event' => $billingEvent,
            'schedule' => $stepBidBudget['schedule'] ?? 'now',
            'startDate' => $stepBidBudget['startDate'] ?? null,
            'startTime' => $stepBidBudget['startTime'] ?? null,
            'endDate' => $stepBidBudget['endDate'] ?? null,
            'endTime' => $stepBidBudget['endTime'] ?? null,
            'spendMin' => $stepBidBudget['spendMin'] ?? null,
            'spendMax' => $stepBidBudget['spendMax'] ?? null,
            'attribution' => $stepBidBudget['attribution'] ?? null,
            'bidStrategy' => $stepBidBudget['bidStrategy'] ?? null,
            'deliveryType' => $stepBidBudget['deliveryType'] ?? null,
            'adSetBudgetType' => $stepBidBudget['adSetBudgetType'] ?? null,
            'adSetBudget' => $stepBidBudget['adSetBudget'] ?? null,
            'budgetByRegion' => $stepBidBudget['budgetByRegion'] ?? false,
            'regionBudgets' => $stepBidBudget['regionBudgets'] ?? [],
            'bidByRegion' => $stepBidBudget['bidByRegion'] ?? false,
            'regionBids' => $stepBidBudget['regionBids'] ?? [],
            'websitePixelEvent' => $stepBidBudget['websitePixelEvent'] ?? null,
            'goal' => $stepBidBudget['goal'] ?? null,
            'pixelAssignMode' => $stepBidBudget['pixelAssignMode'] ?? null,
            'pixelId' => $stepBidBudget['pixelId'] ?? null,
            'pixelEvent' => $stepBidBudget['pixelEvent'] ?? null,
        ];

        return $meta;
    }

    /**
     * 从 form_data 解析多条定向包（与前端 stepTargeting.packages 一致；旧数据为单条扁平对象）。
     *
     * @return array<int, array<string, mixed>>
     */
    public function extractTargetingPackages(array $formData): array
    {
        $st = $formData['stepTargeting'] ?? [];
        if (! is_array($st)) {
            return [[]];
        }
        if (! empty($st['packages']) && is_array($st['packages'])) {
            $out = [];
            foreach ($st['packages'] as $p) {
                if (is_array($p)) {
                    $out[] = $p;
                }
            }

            return $out !== [] ? $out : [[]];
        }
        $flat = $st;
        unset($flat['packages']);

        return $flat !== [] ? [$flat] : [[]];
    }

    /**
     * 构建 launch_ads_v2 所需的 options（与 FbAdController::launch_ads_v2 入参一致）。
     */
    public function buildLaunchOptions(array $formData): array
    {
        $stepOne = $formData['stepOne'] ?? [];
        $stepCreativeSettings = $formData['stepCreativeSettings'] ?? [];
        $stepCreativeGroup = $formData['stepCreativeGroup'] ?? [];
        [$mergedMaterialIds, $mergedPostIds] = $this->mergeCreativeGroupMaterialPostIds(is_array($stepCreativeGroup) ? $stepCreativeGroup : []);
        $pageFromCreative = trim((string) ($stepCreativeSettings['fbPage'] ?? ''));
        $effectivePageId = $pageFromCreative !== '' ? $pageFromCreative : (string) ($stepOne['page'] ?? '');
        $options = [
            'launch_mode' => (int) ($formData['options']['launch_mode'] ?? 3),
            'page_id' => $effectivePageId,
            'pixel_id' => $stepOne['pixel'] ?? null,
            'link_id' => $stepOne['link_id'] ?? $stepOne['linkId'] ?? '',
            'copywriting_id' => $stepOne['copywriting_id'] ?? $stepOne['copywritingId'] ?? null,
            'form_id' => $stepOne['form'] ?? $stepOne['form_id'] ?? null,
            'material_id_list' => $formData['options']['material_id_list'] ?? $mergedMaterialIds,
            /** 按素材：有序列表，与 Marketing API asset_feed_spec 中多图/多视频 + bodies/titles/link_urls 的语义对齐；当前投放 Job 按「每槽位一条广告」拆分并传入 creative_asset_slot */
            'creative_asset_slots' => $this->buildCreativeAssetSlotsFromFormData($formData),
            /** 创意组绑定规则：按账户 / 按定向包（地区组），供多账户或按包拆分创意时使用 */
            'creative_binding' => $this->buildCreativeBindingFromFormData($formData),
            'post_id_list' => $formData['options']['post_id_list'] ?? $mergedPostIds,
            'product_set_ids' => $formData['options']['product_set_ids'] ?? [],
            /** 创意设置：Ad status、网站事件追踪、扩展标记（CreateAd / notes） */
            'ad_status' => ! empty($stepCreativeSettings['adStatus']) ? 'ACTIVE' : 'PAUSED',
            'website_tracking_event' => $stepCreativeSettings['websiteEvent'] ?? null,
            'use_page_as_identity' => ! empty($stepCreativeSettings['usePageAsIdentity']),
            'multi_advertiser_ads' => ! empty($stepCreativeSettings['multiAdvertiser']),
        ];
        $dsa = $this->resolveDsaFromFormData($formData);
        if ($dsa) {
            $options['dsa_beneficiary'] = $dsa['beneficiary'];
            $options['dsa_payor'] = $dsa['payor'];
        }
        $stepTwo = $formData['stepTwo'] ?? [];
        if (!empty($stepTwo['specialCategory'])) {
            $options['special_ad_categories'] = is_array($stepTwo['specialCategory'])
                ? $stepTwo['specialCategory']
                : [$stepTwo['specialCategory']];
        }
        $stepBidBudget = $formData['stepBidBudget'] ?? [];
        if (!empty($stepBidBudget['endDate'])) {
            $endDate = is_string($stepBidBudget['endDate']) ? $stepBidBudget['endDate'] : ($stepBidBudget['endDate']['$d'] ?? null);
            $endTime = $stepBidBudget['endTime'] ?? '23:59:59';
            if (is_array($endTime) && isset($endTime['$d'])) {
                $endTime = date('H:i:s', strtotime($endTime['$d']));
            } elseif (!is_string($endTime)) {
                $endTime = '23:59:59';
            }
            if ($endDate) {
                $options['end_time'] = (strpos($endDate, 'T') !== false ? substr($endDate, 0, 19) : $endDate . 'T' . $endTime);
            }
        }
        if (!empty($formData['options']['end_time'])) {
            $options['end_time'] = $formData['options']['end_time'];
        }

        $stepDelivery = $formData['stepDelivery'] ?? [];
        $mappedLoc = $this->mapConversionLocation($stepOne['conversionLocation'] ?? 'website');
        if ($mappedLoc === 'APP') {
            $options['fb_app_id'] = $stepDelivery['app'] ?? null;
            if (!empty($stepDelivery['store'])) {
                $options['delivery_app_store'] = (string) $stepDelivery['store'];
            }
        }
        if ($mappedLoc === 'WEBSITE') {
            $wUrl = trim((string) ($stepDelivery['promoteWebsiteUrl'] ?? ''));
            if ($wUrl !== '') {
                $options['promote_website_url'] = $wUrl;
            }
        }

        // 投放内容：广告组创建后的初始状态（ACTIVE / PAUSED）
        $options['adset_status'] = !empty($stepDelivery['status']) ? 'ACTIVE' : 'PAUSED';

        // 分时段投放 → 由 FacebookCreateAdsetV2 映射为 start_time / end_time / adset_schedule
        if (($stepTwo['schedule'] ?? '') === 'time_slot') {
            $options['meta_adset_schedule'] = [
                'scheduleStartDate' => $stepTwo['scheduleStartDate'] ?? null,
                'scheduleEndDate' => $stepTwo['scheduleEndDate'] ?? null,
                'dailyScheduleStart' => $stepTwo['dailyScheduleStart'] ?? null,
                'dailyScheduleEnd' => $stepTwo['dailyScheduleEnd'] ?? null,
            ];
        }

        // 自定义广告系列花费限额（USD 主币值），FacebookCreateCampaignV2 转为账户币种下最小单位写入 spend_cap
        if (($stepTwo['spendLimit'] ?? '') === 'custom' && isset($stepTwo['customSpendCap'])) {
            $cap = (float) $stepTwo['customSpendCap'];
            if ($cap > 0) {
                $options['campaign_spend_cap_usd'] = $cap;
            }
        }

        /** 9 步「编辑拆分规则」：供 Job 错峰与后续多层级拆单扩展 */
        $options['split_rules'] = MetaAdCreationSplitRules::normalize($formData['splitRules'] ?? null);

        return $options;
    }

    /**
     * 从 stepCreativeGroup.groups 读取绑定规则（按账户 / 按地区组），写入 options.creative_binding。
     */
    private function buildCreativeBindingFromFormData(array $formData): array
    {
        $cg = $formData['stepCreativeGroup'] ?? [];
        $out = [];
        if (empty($cg['groups']) || ! is_array($cg['groups'])) {
            return $out;
        }
        foreach ($cg['groups'] as $g) {
            if (! is_array($g)) {
                continue;
            }
            $rule = (($g['bindingRule'] ?? '') === 'by_region_group') ? 'by_region_group' : 'by_account';
            $mode = (($g['bindingAdAccountMode'] ?? '') === 'selected') ? 'selected' : 'all';
            $accIds = [];
            if (! empty($g['bindingAdAccountIds']) && is_array($g['bindingAdAccountIds'])) {
                $accIds = array_values(array_filter($g['bindingAdAccountIds'], fn ($v) => $v !== null && $v !== ''));
            }
            $rgIds = [];
            if (! empty($g['bindingRegionGroupIds']) && is_array($g['bindingRegionGroupIds'])) {
                $rgIds = array_values(array_filter($g['bindingRegionGroupIds'], fn ($v) => $v !== null && $v !== ''));
            }
            $out[] = [
                'group_id' => (string) ($g['id'] ?? ''),
                'binding_rule' => $rule,
                'binding_ad_account_mode' => $mode,
                'binding_ad_account_ids' => $accIds,
                'binding_region_group_ids' => $rgIds,
            ];
        }

        return $out;
    }

    /**
     * 从 stepCreativeGroup 中 settingMode=by_material 的 materialSlots 生成投放 options.creative_asset_slots。
     * 参见 Ad Creative：asset_feed_spec（images/videos、bodies、titles、link_urls 等）。
     */
    private function buildCreativeAssetSlotsFromFormData(array $formData): array
    {
        $cg = $formData['stepCreativeGroup'] ?? [];
        $slots = [];
        if (empty($cg['groups']) || ! is_array($cg['groups'])) {
            return $slots;
        }
        $deliveryUrl = trim((string) (($formData['stepDelivery']['promoteWebsiteUrl'] ?? '') ?: ''));
        foreach ($cg['groups'] as $g) {
            if (! is_array($g)) {
                continue;
            }
            if (($g['settingMode'] ?? '') !== 'by_material') {
                continue;
            }
            $materialSlots = $g['materialSlots'] ?? [];
            if (! is_array($materialSlots)) {
                continue;
            }
            $groupBody = trim((string) ($g['body'] ?? ''));
            $groupTitle = trim((string) ($g['title'] ?? ''));
            $groupDesc = trim((string) ($g['description'] ?? ''));
            $groupLink = trim((string) ($g['linkUrl'] ?? ''));
            if ($groupLink === '' && $deliveryUrl !== '') {
                $groupLink = $deliveryUrl;
            }
            $groupCta = trim((string) ($g['cta'] ?? ''));
            if ($groupCta === '') {
                $groupCta = 'LEARN_MORE';
            }
            foreach ($materialSlots as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $mid = trim((string) ($row['materialId'] ?? $row['material_id'] ?? ''));
                if ($mid === '') {
                    continue;
                }
                $rowBody = trim((string) ($row['body'] ?? ''));
                $rowHeadline = trim((string) ($row['headline'] ?? ''));
                $rowDesc = trim((string) ($row['description'] ?? ''));
                $rowLink = trim((string) ($row['link'] ?? ''));
                $atRaw = strtolower((string) ($row['assetType'] ?? $row['asset_type'] ?? 'image'));
                $assetType = in_array($atRaw, ['video', 'carousel'], true) ? $atRaw : 'image';
                $slots[] = [
                    'group_id' => (string) ($g['id'] ?? ''),
                    'slot_id' => (string) ($row['slotId'] ?? $row['slot_id'] ?? ''),
                    'asset_type' => $assetType,
                    'material_id' => $mid,
                    'body' => $rowBody !== '' ? $rowBody : $groupBody,
                    'headline' => $rowHeadline !== '' ? $rowHeadline : $groupTitle,
                    'description' => $rowDesc !== '' ? $rowDesc : $groupDesc,
                    'link' => $rowLink !== '' ? $rowLink : $groupLink,
                    'cta' => trim((string) ($row['cta'] ?? '')) !== '' ? trim((string) ($row['cta'] ?? '')) : $groupCta,
                    'deferred_deep_link' => trim((string) ($row['deferredDeepLink'] ?? $row['deferred_deep_link'] ?? '')),
                    'custom_product_page_id' => trim((string) ($row['customProductPageId'] ?? $row['custom_product_page_id'] ?? '')),
                ];
            }
        }

        return $slots;
    }

    /**
     * 是否有至少一个创意来源（素材/帖子/商品集），用于决定是否调度 Meta 创建任务。
     */
    public function hasCreativeSource(array $options): bool
    {
        $materials = $options['material_id_list'] ?? [];
        $posts = $options['post_id_list'] ?? [];
        $productSets = $options['product_set_ids'] ?? [];
        if (is_array($materials)) {
            $materials = array_filter($materials);
        }
        if (is_array($posts)) {
            $posts = array_filter($posts);
        }
        if (is_array($productSets)) {
            $productSets = array_filter($productSets);
        }
        return count($materials) > 0 || count($posts) > 0 || count($productSets) > 0;
    }

    /**
     * 提交后：创建记录、可选创建模板+AdLog 并调度 FacebookCreateCampaignV2（与现有 launch 链路一致）。
     * 若 form_data 中无素材/帖子/商品集，则仅落库记录不调度 Meta，返回 launched=false。
     */
    public function submitAndLaunch(MetaAdCreationRecord $record, array $formData, array $metaCounts = []): array
    {
        Log::debug('meta_ad_creation: submitAndLaunch incoming formData', [
            'hypothesisId' => 'H2_splitRules_survives_to_launch',
            'runId' => 'verify-launch',
            'topLevelKeys' => array_keys($formData),
            'has_splitRules' => array_key_exists('splitRules', $formData),
            'splitRules_type' => isset($formData['splitRules']) ? gettype($formData['splitRules']) : null,
        ]);
        $options = $this->buildLaunchOptions($formData);
        Log::info('Meta ad creation split_rules', [
            'record_id' => $record->id,
            'summary' => MetaAdCreationSplitRules::describe($options['split_rules'] ?? []),
        ]);
        $stepOne = $formData['stepOne'] ?? [];
        $fbAdAccountId = $record->fb_ad_account_id;
        $operatorId = $stepOne['operator'] ?? null;
        $operatorType = $stepOne['operator_type'] ?? OperatorType::FacebookUser->value;

        if (!$fbAdAccountId || !$operatorId) {
            return ['launched' => false, 'message' => __('缺少广告账户或操作员')];
        }

        if (!$this->hasCreativeSource($options)) {
            return [
                'launched' => false,
                'message' => __('创建记录已保存；请补充素材或帖子后重新提交，或使用创建广告 v2 选择素材后投放'),
            ];
        }

        // 与 launch_ads_v2 一致：需要 page_id
        if (empty($options['page_id'])) {
            return ['launched' => false, 'message' => __('请选择 Facebook 主页')];
        }

        $mappedLocation = $this->mapConversionLocation($stepOne['conversionLocation'] ?? 'website');
        // Leads + 即时表单：需要 lead gen form_id
        if ($stepOne['objective'] === 'OUTCOME_LEADS' && $mappedLocation === 'INSTANT_FORMS' && empty($options['form_id'])) {
            return ['launched' => false, 'message' => __('请在「基础设置」中选择表单')];
        }
        if ($mappedLocation === 'APP' && empty($options['fb_app_id'])) {
            return ['launched' => false, 'message' => __('转化发生位置为「应用」时，请在「投放内容」中选择已关联到广告账户的应用')];
        }
        $objective = $stepOne['objective'] ?? '';
        if ($objective === 'OUTCOME_APP_PROMOTION' && $mappedLocation !== 'APP') {
            return ['launched' => false, 'message' => __('「应用推广」须将转化发生位置设为「应用」，并在「投放内容」中选择应用')];
        }
        if (($objective === 'OUTCOME_AWARENESS' || $objective === 'OUTCOME_ENGAGEMENT') && $mappedLocation !== 'WEBSITE') {
            return ['launched' => false, 'message' => __('「知名度/互动」当前仅支持「网站」转化发生位置')];
        }
        // 网站 + 流量：LINK_CLICKS 可不绑 Pixel；销量/线索网站转化仍要求 Pixel
        if ($mappedLocation === 'WEBSITE' && empty($options['pixel_id'])) {
            if (! in_array($objective, ['OUTCOME_TRAFFIC', 'OUTCOME_AWARENESS', 'OUTCOME_ENGAGEMENT'], true)) {
                return ['launched' => false, 'message' => __('网站转化请在基础设置中选择像素')];
            }
        }

        $stepDelivery = $formData['stepDelivery'] ?? [];
        $adsetName = trim((string) ($stepDelivery['name'] ?? ''));
        if ($adsetName === '') {
            return ['launched' => false, 'message' => __('请在「投放内容」中填写广告组名称')];
        }

        // 前端 stepTargeting.packages：每条定向包 → 一条 FbAdTemplate（受众/命名等差异）。
        // 官方结构：一个 Campaign 下可挂多个 Ad Set（同一 objective；预算可在系列或组层级）。
        // 见：https://developers.facebook.com/docs/marketing-api/buying-api/
        $targetingPackages = $this->extractTargetingPackages($formData);
        $templates = [];
        foreach ($targetingPackages as $idx => $pkg) {
            $templates[] = $this->createTemplateFromFormData($formData, $record->user_id, $pkg, (int) $idx);
        }
        $template = $templates[0];
        // 传入 FacebookCreateCampaignV2：在 launch_mode≠2 时对每个模板各建一个 Ad Set，共用同一 campaign_id。
        $options['targeting_template_ids'] = array_map(static fn ($t) => $t->id, $templates);

        $adLog = new AdLog([
            'user_id' => $record->user_id,
            'fb_ad_account_id' => $fbAdAccountId,
            'fb_ad_template_id' => $template->id,
            'operator_type' => $operatorType,
            'launch_mode' => $options['launch_mode'],
        ]);
        if ($operatorType === OperatorType::BMUser->value) {
            $adLog->fb_api_token_id = $operatorId;
        } else {
            $adLog->fb_account_id = $operatorId;
        }
        $adLog->fb_pixel_id = $options['pixel_id'] ?? '';
        $adLog->fb_page_id = $options['page_id'] ?? '';
        $adLog->link_id = $options['link_id'] ?? '';
        $adLog->copywriting_id = $options['copywriting_id'] ?? '';
        $adLog->fb_page_form_id = $options['form_id'] ?? '';
        $adLog->materials()->sync($options['material_id_list'] ?? []);
        $adLog->save();

        $record->ad_log_id = $adLog->id;
        $record->save();

        $job = new FacebookCreateCampaignV2(
            $fbAdAccountId,
            $operatorType,
            $operatorId,
            $template->id,
            $options,
            $adLog
        );
        Bus::dispatch($job->onQueue('facebook'));

        Log::info('Meta ad creation launched from 9-step', [
            'record_id' => $record->id,
            'ad_log_id' => $adLog->id,
            'template_id' => $template->id,
        ]);

        return [
            'launched' => true,
            'message' => __('已提交到 Meta 创建广告系列与广告组，请稍后在广告管理中查看'),
            'ad_log_id' => $adLog->id,
        ];
    }

    /** 版位：进阶赋能型版位（advanced）或手动版位（manual + 平台/位置）。 */
    private function mapPlacementToTemplateFields(array $stepPlacement): array
    {
        $mode = (string) ($stepPlacement['placementMode'] ?? $stepPlacement['placement_mode'] ?? 'manual');
        if ($mode !== 'advanced' && $mode !== 'manual') {
            $mode = 'manual';
        }

        $attrs = [
            'placement_mode' => $mode,
            'publisher_platforms' => [],
            'facebook_positions' => [],
            'instagram_positions' => [],
            'messenger_positions' => [],
            'audience_network_positions' => [],
        ];

        if ($mode === 'advanced') {
            return $attrs;
        }

        $platforms = $stepPlacement['publisher_platforms'] ?? $stepPlacement['publisherPlatforms'] ?? null;
        if (!is_array($platforms) || empty($platforms)) {
            $platform = (string) ($stepPlacement['platform'] ?? 'facebook');
            $map = [
                'facebook' => ['facebook'],
                'instagram' => ['instagram'],
                'messenger' => ['messenger'],
                'audience' => ['audience_network'],
                'audience_network' => ['audience_network'],
            ];
            $platforms = $map[$platform] ?? ['facebook'];
        }

        $attrs['publisher_platforms'] = array_values(array_unique(array_filter($platforms)));
        $attrs['facebook_positions'] = is_array($stepPlacement['facebook_positions'] ?? $stepPlacement['facebookPositions'] ?? null)
            ? array_values(array_unique($stepPlacement['facebook_positions'] ?? $stepPlacement['facebookPositions']))
            : [];
        $attrs['instagram_positions'] = is_array($stepPlacement['instagram_positions'] ?? $stepPlacement['instagramPositions'] ?? null)
            ? array_values(array_unique($stepPlacement['instagram_positions'] ?? $stepPlacement['instagramPositions']))
            : [];
        $attrs['messenger_positions'] = is_array($stepPlacement['messenger_positions'] ?? $stepPlacement['messengerPositions'] ?? null)
            ? array_values(array_unique($stepPlacement['messenger_positions'] ?? $stepPlacement['messengerPositions']))
            : [];
        $attrs['audience_network_positions'] = is_array($stepPlacement['audience_network_positions'] ?? $stepPlacement['audienceNetworkPositions'] ?? null)
            ? array_values(array_unique($stepPlacement['audience_network_positions'] ?? $stepPlacement['audienceNetworkPositions']))
            : [];

        // 平台未勾选时，强制清空对应 positions，避免“手动看似无效”
        $selectedPlatformSet = array_flip($attrs['publisher_platforms']);
        if (!isset($selectedPlatformSet['facebook'])) {
            $attrs['facebook_positions'] = [];
        }
        if (!isset($selectedPlatformSet['instagram'])) {
            $attrs['instagram_positions'] = [];
        }
        if (!isset($selectedPlatformSet['messenger'])) {
            $attrs['messenger_positions'] = [];
        }
        if (!isset($selectedPlatformSet['audience_network'])) {
            $attrs['audience_network_positions'] = [];
        }

        return $attrs;
    }

    /** UI gender (all/male/female) -> Meta genders: 0=all, 1=male, 2=female */
    private function mapGenderToMeta($v): int
    {
        if (is_numeric($v)) {
            return (int) $v;
        }
        $map = ['all' => 0, 'male' => 1, 'female' => 2];
        return $map[strtolower((string) $v)] ?? 0;
    }

    /** UI language (id or string) -> Meta locales format [{key, name}] */
    private function mapLanguageToLocales($v): array
    {
        if (empty($v)) {
            return [];
        }
        if (is_array($v)) {
            return $v;
        }
        $common = [
            'en' => ['key' => 6, 'name' => 'English (US)'],
            'zh' => ['key' => 24, 'name' => 'Chinese (Simplified)'],
            'zh-TW' => ['key' => 28, 'name' => 'Chinese (Traditional)'],
            'ja' => ['key' => 26, 'name' => 'Japanese'],
            'ko' => ['key' => 19, 'name' => 'Korean'],
        ];
        $key = is_string($v) ? strtolower($v) : (string) $v;
        if (isset($common[$key])) {
            return [$common[$key]];
        }
        if (is_numeric($v)) {
            return [['key' => (int) $v, 'name' => 'Locale ' . $v]];
        }
        return [];
    }

    /**
     * 合并「地区」步骤与单条定向包上的 stepRegion（当 useCustomRegion 为真时）。
     * 与 Meta Ad Set 的 targeting.geo_locations / excluded_geo_locations 一一对应。
     *
     * @param  array<string, mixed>  $globalStepRegion  form_data.stepRegion
     * @param  array<string, mixed>  $targetingPackage    单条定向包（含 useCustomRegion、stepRegion）
     * @return array<string, mixed>
     */
    private function resolveEffectiveStepRegionForPackage(array $globalStepRegion, array $targetingPackage): array
    {
        if (! $this->isUseCustomRegion($targetingPackage)) {
            return $globalStepRegion;
        }
        $local = $targetingPackage['stepRegion'] ?? null;
        if (! is_array($local)) {
            return $globalStepRegion;
        }

        return $this->mergeStepRegionLayer($globalStepRegion, $local);
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function mergeStepRegionLayer(array $base, array $override): array
    {
        $keys = [
            'countries_included', 'countries_excluded',
            'regions_included', 'regions_excluded',
            'cities_included', 'cities_excluded',
            'publisher_platforms', 'device_platforms',
            'regionGroupName', 'useExisting', 'regionGroupId', 'regionSearch', 'tab',
            'financialRegion',
            'benefitTw', 'sponsorTw', 'benefitAu', 'sponsorAu', 'benefitSg', 'sponsorSg',
        ];
        $out = $base;
        foreach ($keys as $k) {
            if (array_key_exists($k, $override)) {
                $out[$k] = $override[$k];
            }
        }

        return $out;
    }

    /** @param  array<string, mixed>  $targetingPackage */
    private function isUseCustomRegion(array $targetingPackage): bool
    {
        $v = $targetingPackage['useCustomRegion'] ?? false;

        return $v === true || $v === 1 || $v === '1' || $v === 'true';
    }

    /**
     * 从全局地区 + 各定向包独立地区中解析 DSA（launch options 仍为单次；优先全局已填完整项）。
     *
     * @param  array<string, mixed>  $formData
     */
    private function resolveDsaFromFormData(array $formData): ?array
    {
        $global = $formData['stepRegion'] ?? [];
        $dsa = $this->resolveDsaFromRegion(is_array($global) ? $global : []);
        if ($dsa !== null) {
            return $dsa;
        }
        foreach ($this->extractTargetingPackages($formData) as $pkg) {
            if (! $this->isUseCustomRegion($pkg)) {
                continue;
            }
            $eff = $this->resolveEffectiveStepRegionForPackage(is_array($global) ? $global : [], $pkg);
            $dsa = $this->resolveDsaFromRegion($eff);
            if ($dsa !== null) {
                return $dsa;
            }
        }

        return null;
    }

    /** 从 stepRegion 解析 DSA 受益方/赞助方（定向欧盟时 Meta 要求） */
    private function resolveDsaFromRegion(array $stepRegion): ?array
    {
        $pairs = [
            [$stepRegion['benefitTw'] ?? null, $stepRegion['sponsorTw'] ?? null],
            [$stepRegion['benefitAu'] ?? null, $stepRegion['sponsorAu'] ?? null],
            [$stepRegion['benefitSg'] ?? null, $stepRegion['sponsorSg'] ?? null],
        ];
        foreach ($pairs as [$b, $p]) {
            $bs = $b !== null && $b !== '' ? trim((string) $b) : '';
            $ps = $p !== null && $p !== '' ? trim((string) $p) : '';
            if ($bs !== '' && $ps !== '') {
                return ['beneficiary' => $bs, 'payor' => $ps];
            }
        }
        foreach ($pairs as [$b, $p]) {
            $bs = $b !== null && $b !== '' ? trim((string) $b) : '';
            if ($bs !== '') {
                return ['beneficiary' => $bs, 'payor' => $bs];
            }
        }

        return null;
    }

    private function mapConversionLocation(?string $v): string
    {
        if ($v === 'app' || $v === 'APP') {
            return 'APP';
        }
        if (strtoupper((string) $v) === 'INSTANT_FORMS' || $v === 'form') {
            return 'INSTANT_FORMS';
        }
        return 'WEBSITE';
    }

    /**
     * 优化目标 + 转化事件（写入模板 optimization_goal / pixel_event；应用侧 pixel_event 表示 App 标准事件名）。
     *
     * @return array{0: string, 1: string}
     */
    private function resolveOptimizationAndConversionEvent(
        string $objective,
        string $conversionLocation,
        array $stepCreativeSettings,
        array $stepBidBudget,
        $appEvent
    ): array {
        if ($conversionLocation === 'APP') {
            if ($objective === 'OUTCOME_APP_PROMOTION') {
                return ['APP_INSTALLS', ''];
            }
            if ($objective === 'OUTCOME_TRAFFIC') {
                return ['LINK_CLICKS', ''];
            }
            if ($objective === 'OUTCOME_AWARENESS') {
                return ['REACH', ''];
            }
            if ($objective === 'OUTCOME_ENGAGEMENT') {
                return ['POST_ENGAGEMENT', ''];
            }
            if ($objective === 'OUTCOME_LEADS') {
                $evt = ($appEvent !== null && $appEvent !== '') ? (string) $appEvent : 'LEAD';

                return ['OFFSITE_CONVERSIONS', $evt];
            }
            $evt = ($appEvent !== null && $appEvent !== '') ? (string) $appEvent : 'PURCHASE';

            return ['OFFSITE_CONVERSIONS', $evt];
        }

        $optimizationGoal = 'OFFSITE_CONVERSIONS';
        if ($objective === 'OUTCOME_LEADS' && $conversionLocation === 'INSTANT_FORMS') {
            $optimizationGoal = 'LEAD_GENERATION';
        } elseif ($objective === 'OUTCOME_TRAFFIC') {
            // 网站/应用「流量」：广告组以链接点击优化为主，不使用 Pixel 标准事件作转化名
            $optimizationGoal = 'LINK_CLICKS';
        } elseif ($objective === 'OUTCOME_AWARENESS') {
            $optimizationGoal = 'REACH';
        } elseif ($objective === 'OUTCOME_ENGAGEMENT') {
            $optimizationGoal = 'POST_ENGAGEMENT';
        }

        $websiteEvent = $stepCreativeSettings['websiteEvent'] ?? null;
        if (in_array($optimizationGoal, ['LINK_CLICKS', 'REACH', 'POST_ENGAGEMENT'], true) && $conversionLocation === 'WEBSITE') {
            return [$optimizationGoal, ''];
        }

        $pixelEvent = ($conversionLocation === 'WEBSITE' && !empty($websiteEvent))
            ? (string) $websiteEvent
            : (string) ($stepBidBudget['pixelEvent'] ?? 'PURCHASE');

        return [$optimizationGoal, $pixelEvent];
    }

    /**
     * 最低年龄与年龄建议：age_min = max(ageFrom, minAge)，age_max = max(age_min, ageTo)，与 Meta 文档一致。
     */
    private function mapTargetingAge(array $stepTargeting, array $stepRegion): array
    {
        $ageFrom = (int) ($stepTargeting['ageFrom'] ?? $stepTargeting['age_from'] ?? 18);
        $minAge = (int) ($stepTargeting['minAge'] ?? $stepTargeting['min_age'] ?? $stepRegion['ageMin'] ?? 18);
        $ageTo = (int) ($stepTargeting['ageTo'] ?? $stepTargeting['age_to'] ?? $stepRegion['ageMax'] ?? 65);

        $ageMin = max($ageFrom, $minAge);
        if (isset($stepTargeting['age_min']) && is_numeric($stepTargeting['age_min'])) {
            $ageMin = max($ageMin, (int) $stepTargeting['age_min']);
        }
        $ageMax = max($ageMin, $ageTo);
        if (isset($stepTargeting['age_max']) && is_numeric($stepTargeting['age_max'])) {
            $ageMax = max($ageMax, (int) $stepTargeting['age_max']);
        }
        if ($ageMax > 65) {
            $ageMax = 65;
        }
        if ($ageMin < 13) {
            $ageMin = 13;
        }

        return [$ageMin, $ageMax];
    }

    /**
     * 前端 stepTwo 竞价策略 → Meta bid_strategy + bid_amount（金额类为 USD 字符串，与模板/Job 中 CurrencyUtils 一致）。
     */
    private function mapStepTwoBidToTemplate(array $stepTwo, array $stepBidBudget): array
    {
        $strategy = $stepBidBudget['bidStrategy'] ?? $stepTwo['bidStrategy'] ?? 'HIGHEST_VOLUME';
        $fallbackBid = (string) ($stepBidBudget['bidAmount'] ?? '');

        switch ($strategy) {
            case 'COST_PER_RESULT':
                $target = $stepBidBudget['costPerResultTarget'] ?? $stepTwo['costPerResultTarget'] ?? null;
                return [
                    'bid_strategy' => BidStrategy::CostPerResultGoal->value,
                    'bid_amount' => ($target !== null && $target !== '') ? (string) $target : $fallbackBid,
                ];
            case 'BID_CAP':
                $cap = $stepBidBudget['bidCapAmount'] ?? $stepTwo['bidCapAmount'] ?? null;
                return [
                    'bid_strategy' => BidStrategy::BidCap->value,
                    'bid_amount' => ($cap !== null && $cap !== '') ? (string) $cap : $fallbackBid,
                ];
            case 'ROAS':
                $roas = $stepBidBudget['roasTarget'] ?? $stepTwo['roasTarget'] ?? null;
                return [
                    'bid_strategy' => BidStrategy::MinRoas->value,
                    'bid_amount' => ($roas !== null && $roas !== '') ? (string) $roas : '',
                ];
            case 'HIGHEST_VOLUME':
            default:
                return [
                    'bid_strategy' => BidStrategy::HighestVolume->value,
                    'bid_amount' => '',
                ];
        }
    }

    /**
     * 合并多创意组中的素材 ID、帖子 ID（与前端 stepCreativeGroup.groups 一致）。
     *
     * @return array{0: array<int, mixed>, 1: array<int, mixed>}
     */
    private function mergeCreativeGroupMaterialPostIds(array $cg): array
    {
        $materialIds = [];
        $postIds = [];
        if (! empty($cg['groups']) && is_array($cg['groups'])) {
            foreach ($cg['groups'] as $g) {
                if (! is_array($g)) {
                    continue;
                }
                foreach (['materialIds', 'material_id_list', 'videoMaterialIds', 'imageMaterialIds'] as $k) {
                    if (empty($g[$k]) || ! is_array($g[$k])) {
                        continue;
                    }
                    foreach ($g[$k] as $mid) {
                        if ($mid !== null && $mid !== '') {
                            $materialIds[] = $mid;
                        }
                    }
                }
                if (! empty($g['postIds']) && is_array($g['postIds'])) {
                    foreach ($g['postIds'] as $pid) {
                        if ($pid !== null && $pid !== '') {
                            $postIds[] = $pid;
                        }
                    }
                }
                if (! empty($g['post_id_list']) && is_array($g['post_id_list'])) {
                    foreach ($g['post_id_list'] as $pid) {
                        if ($pid !== null && $pid !== '') {
                            $postIds[] = $pid;
                        }
                    }
                }
                if (($g['settingMode'] ?? '') === 'by_material' && ! empty($g['materialSlots']) && is_array($g['materialSlots'])) {
                    foreach ($g['materialSlots'] as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $mid = $row['materialId'] ?? $row['material_id'] ?? null;
                        if ($mid !== null && $mid !== '') {
                            $materialIds[] = $mid;
                        }
                    }
                }
            }
        }
        if ($materialIds === []) {
            foreach (['materialIds', 'material_id_list'] as $k) {
                if (! empty($cg[$k]) && is_array($cg[$k])) {
                    $materialIds = array_merge($materialIds, $cg[$k]);
                }
            }
        }
        if ($postIds === []) {
            foreach (['postIds', 'post_id_list'] as $k) {
                if (! empty($cg[$k]) && is_array($cg[$k])) {
                    $postIds = array_merge($postIds, $cg[$k]);
                }
            }
        }
        $materialIds = array_values(array_unique(array_filter($materialIds, fn ($v) => $v !== null && $v !== '')));
        $postIds = array_values(array_unique(array_filter($postIds, fn ($v) => $v !== null && $v !== '')));

        return [$materialIds, $postIds];
    }

    /**
     * 当前/首个创意组名称，用于 ad_name 模板兜底。
     */
    private function resolveCreativeGroupLabel(array $cg): string
    {
        $label = '';
        if (! empty($cg['groups']) && is_array($cg['groups'])) {
            $activeId = $cg['activeGroupId'] ?? null;
            foreach ($cg['groups'] as $g) {
                if (! is_array($g)) {
                    continue;
                }
                if ($activeId !== null && ($g['id'] ?? null) === $activeId) {
                    $label = trim((string) ($g['creativeGroupName'] ?? $g['name'] ?? ''));
                    break;
                }
            }
            if ($label === '' && isset($cg['groups'][0]) && is_array($cg['groups'][0])) {
                $g0 = $cg['groups'][0];
                $label = trim((string) ($g0['creativeGroupName'] ?? $g0['name'] ?? ''));
            }
        }
        if ($label === '') {
            $label = trim((string) ($cg['creativeGroupName'] ?? $cg['groupName'] ?? ''));
        }

        return $label;
    }

    /**
     * 从关联的 AdLog 回写 fb_campaign_id、fb_adset_ids 到创建记录（可选，用于「按某次创建」查成效）。
     */
    public function syncRecordFromAdLog(MetaAdCreationRecord $record): bool
    {
        if (!$record->ad_log_id) {
            return false;
        }
        $adLog = AdLog::find($record->ad_log_id);
        if (!$adLog) {
            return false;
        }
        $campaign = $adLog->campaigns()->first();
        if ($campaign) {
            $record->fb_campaign_id = $campaign->id;
            $adsets = $adLog->adsets()->orderBy('fb_adsets.created_at', 'asc')->get();
            $adsetIds = $adsets->pluck('id')->values()->toArray();
            $record->fb_adset_ids = $adsetIds;
            // adlog_ad 通过 ad_source_id 关联 fb_ads.source_id，不能直接用 $adLog->ads()（该关系历史上定义不准确）。
            $adSourceIds = AdLogPivotAd::query()
                ->where('adlog_id', $adLog->id)
                ->where('ad_created', true)
                ->pluck('ad_source_id')
                ->values()
                ->toArray();
            $ads = $adSourceIds !== []
                ? FbAd::query()
                    ->whereIn('source_id', $adSourceIds)
                    ->orderBy('created_at', 'asc')
                    ->get()
                : collect();
            $adIds = $ads->pluck('id')->values()->toArray();
            $record->fb_ad_ids = $adIds;
            $record->save();

            $this->syncMappingTablesFromRecord($record, $adsets->all(), $ads->all());
            return true;
        }
        return false;
    }

    /**
     * 将记录回写结果同步到映射明细表。
     *
     * @param  array<int, FbAdset>  $adsets
     * @param  array<int, FbAd>  $ads
     */
    private function syncMappingTablesFromRecord(MetaAdCreationRecord $record, array $adsets, array $ads): void
    {
        if (!Schema::hasTable('meta_ad_creation_record_adsets') || !Schema::hasTable('meta_ad_creation_record_ads')) {
            throw new \RuntimeException('映射表不存在，请先执行 upgrade_meta_ad_creation_add_mapping_tables.sql');
        }

        $snapshot = is_array($record->form_data_snapshot) ? $record->form_data_snapshot : [];
        $targetingPackages = $this->extractTargetingPackages($snapshot);
        $globalRegion = is_array($snapshot['stepRegion'] ?? null) ? $snapshot['stepRegion'] : [];
        $stepDelivery = is_array($snapshot['stepDelivery'] ?? null) ? $snapshot['stepDelivery'] : [];
        $creativeGroup = is_array($snapshot['stepCreativeGroup'] ?? null) ? $snapshot['stepCreativeGroup'] : [];

        $bindingRule = $this->resolveCreativeBindingRule($creativeGroup);
        $adsetMapRows = [];
        foreach ($adsets as $idx => $adset) {
            $pkg = (is_array($targetingPackages[$idx] ?? null) ? $targetingPackages[$idx] : []);
            $effectiveRegion = $this->resolveEffectiveStepRegionForPackage($globalRegion, $pkg);
            $regionSnapshot = $this->extractRegionSnapshot($effectiveRegion);
            $payload = [
                'record_id' => $record->id,
                'fb_campaign_id' => $record->fb_campaign_id,
                'fb_adset_id' => $adset->id,
                'targeting_package_index' => $idx,
                'targeting_package_name' => (string) ($pkg['name'] ?? ''),
                'region_group_id' => $this->resolveRegionGroupIdForPackage($record, $effectiveRegion),
                'region_snapshot' => $regionSnapshot,
                'creative_group_id' => $record->creative_group_id,
                'creative_binding_rule' => $bindingRule,
                'adset_name_snapshot' => $adset->name ?: (string) ($stepDelivery['name'] ?? ''),
                'adset_status_snapshot' => (string) ($adset->status ?? ''),
            ];
            $row = MetaAdCreationRecordAdset::query()->updateOrCreate(
                ['fb_adset_id' => $adset->id],
                $payload
            );
            $adsetMapRows[$adset->id] = $row;
        }

        $slotMap = $this->buildSlotMapByMaterialId($creativeGroup);
        foreach ($ads as $ad) {
            $materialId = $this->extractMaterialIdFromAd($ad);
            $postId = $this->extractPostIdFromAd($ad);
            $slot = $materialId !== '' ? ($slotMap[$materialId] ?? null) : null;
            $adsetMapId = null;
            if (!empty($ad->fb_adset_id) && isset($adsetMapRows[$ad->fb_adset_id])) {
                $adsetMapId = $adsetMapRows[$ad->fb_adset_id]->id;
            }
            MetaAdCreationRecordAd::query()->updateOrCreate(
                ['fb_ad_id' => $ad->id],
                [
                    'record_id' => $record->id,
                    'record_adset_id' => $adsetMapId,
                    'creative_slot_id' => $slot['creative_slot_id'] ?? '',
                    'material_id' => $materialId !== '' ? $materialId : null,
                    'post_id' => $postId !== '' ? $postId : null,
                    'creative_snapshot' => [
                        'slot' => $slot,
                        'ad_name' => $ad->name,
                    ],
                ]
            );
        }
    }

    private function resolveCreativeBindingRule(array $stepCreativeGroup): string
    {
        $groups = is_array($stepCreativeGroup['groups'] ?? null) ? $stepCreativeGroup['groups'] : [];
        if ($groups === []) {
            return 'unknown';
        }
        $rules = [];
        foreach ($groups as $g) {
            if (!is_array($g)) {
                continue;
            }
            $r = (($g['bindingRule'] ?? '') === 'by_region_group') ? 'by_region_group' : 'by_account';
            $rules[$r] = true;
        }
        if (count($rules) === 1) {
            return (string) array_key_first($rules);
        }
        return count($rules) > 1 ? 'mixed' : 'unknown';
    }

    private function extractRegionSnapshot(array $region): array
    {
        return [
            'countries_included' => is_array($region['countries_included'] ?? null) ? $region['countries_included'] : [],
            'countries_excluded' => is_array($region['countries_excluded'] ?? null) ? $region['countries_excluded'] : [],
            'regions_included' => is_array($region['regions_included'] ?? null) ? $region['regions_included'] : [],
            'regions_excluded' => is_array($region['regions_excluded'] ?? null) ? $region['regions_excluded'] : [],
            'cities_included' => is_array($region['cities_included'] ?? null) ? $region['cities_included'] : [],
            'cities_excluded' => is_array($region['cities_excluded'] ?? null) ? $region['cities_excluded'] : [],
        ];
    }

    private function resolveRegionGroupIdForPackage(MetaAdCreationRecord $record, array $effectiveRegion): ?string
    {
        $regionGroupId = $effectiveRegion['regionGroupId'] ?? null;
        if (is_string($regionGroupId) && strlen($regionGroupId) === 26) {
            return $regionGroupId;
        }
        return $record->region_group_id;
    }

    private function buildSlotMapByMaterialId(array $stepCreativeGroup): array
    {
        $groups = is_array($stepCreativeGroup['groups'] ?? null) ? $stepCreativeGroup['groups'] : [];
        $map = [];
        foreach ($groups as $g) {
            if (!is_array($g) || !is_array($g['materialSlots'] ?? null)) {
                continue;
            }
            foreach ($g['materialSlots'] as $slot) {
                if (!is_array($slot)) {
                    continue;
                }
                $materialId = trim((string) ($slot['materialId'] ?? $slot['material_id'] ?? ''));
                if ($materialId === '') {
                    continue;
                }
                $map[$materialId] = [
                    'creative_slot_id' => (string) ($slot['slotId'] ?? $slot['slot_id'] ?? ''),
                    'group_id' => (string) ($g['id'] ?? ''),
                    'body' => (string) ($slot['body'] ?? ''),
                    'headline' => (string) ($slot['headline'] ?? ''),
                    'description' => (string) ($slot['description'] ?? ''),
                    'link' => (string) ($slot['link'] ?? ''),
                    'cta' => (string) ($slot['cta'] ?? ''),
                ];
            }
        }
        return $map;
    }

    private function extractMaterialIdFromAd(FbAd $ad): string
    {
        $creative = is_array($ad->creative) ? $ad->creative : [];
        $materialId = trim((string) (
            $creative['material_id']
            ?? $creative['materialId']
            ?? $creative['asset_id']
            ?? $creative['assetId']
            ?? ''
        ));
        return $materialId;
    }

    private function extractPostIdFromAd(FbAd $ad): string
    {
        $creative = is_array($ad->creative) ? $ad->creative : [];
        return trim((string) (
            $creative['post_id']
            ?? $creative['postId']
            ?? $creative['object_story_id']
            ?? ''
        ));
    }
}
