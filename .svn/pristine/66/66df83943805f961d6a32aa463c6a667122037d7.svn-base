<?php

namespace App\Http\Controllers;

use App\Models\MetaAdCreationRecord;
use App\Services\MetaAdCreationLaunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MetaAdCreationRecordController extends Controller
{
    /**
     * 历史记录列表（用于复制历史任务与回溯）
     */
    public function index(Request $request): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForRecords();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '创建记录表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $userId = Auth::id();
        $query = MetaAdCreationRecord::where('user_id', $userId);

        if ($request->filled('fb_ad_account_id')) {
            $query->where('fb_ad_account_id', $request->fb_ad_account_id);
        }

        $list = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'fbAdAccountId' => $record->fb_ad_account_id,
                'draftId' => $record->draft_id,
                'templateId' => $record->template_id,
                'regionGroupId' => $record->region_group_id,
                'creativeGroupId' => $record->creative_group_id,
                'adLogId' => $record->ad_log_id,
                'fbCampaignId' => $record->fb_campaign_id,
                'fbAdsetIds' => $record->fb_adset_ids ?? [],
                'fbAdIds' => $record->fb_ad_ids ?? [],
                'formDataSnapshot' => $record->form_data_snapshot ?? [],
                'createdAt' => $record->created_at?->toIso8601String(),
                'createdAtText' => $record->created_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'success' => true,
            'data' => $list,
        ]);
    }

    /**
     * 提交 9 步创建：落库创建记录，并对接现有 Meta API 链路（FbAdTemplate + FacebookCreateCampaignV2）。
     * 若 form_data 中含素材/帖子/商品集且已选主页，则调度 FacebookCreateCampaignV2 创建 Campaign → AdSet → Ad；
     * 否则仅落库，fb_campaign_id 可后续由 AdLog 回写。
     */
    public function store(Request $request): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForRecords();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '创建记录表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $request->validate([
            'form_data' => 'required|array',
            'meta_counts' => 'nullable|array',
            'draft_id' => 'nullable|string|size:26',
            'template_id' => 'nullable|string|size:26',
        ]);

        // #region agent log
        $rawFormData = is_array($request->form_data) ? $request->form_data : [];
        Log::debug('meta_ad_creation: raw form_data before normalize', [
            'hypothesisId' => 'H1_splitRules_preserved',
            'runId' => 'verify-raw',
            'topLevelKeys' => array_keys($rawFormData),
            'has_splitRules' => array_key_exists('splitRules', $rawFormData),
            'splitRules_type' => isset($rawFormData['splitRules']) ? gettype($rawFormData['splitRules']) : null,
            'has_stepOne' => array_key_exists('stepOne', $rawFormData),
            'has_stepTwo' => array_key_exists('stepTwo', $rawFormData),
            'has_stepDelivery' => array_key_exists('stepDelivery', $rawFormData),
            'has_stepRegion' => array_key_exists('stepRegion', $rawFormData),
            'has_stepPlacement' => array_key_exists('stepPlacement', $rawFormData),
            'has_stepTargeting' => array_key_exists('stepTargeting', $rawFormData),
            'has_stepBidBudget' => array_key_exists('stepBidBudget', $rawFormData),
            'has_stepCreativeSettings' => array_key_exists('stepCreativeSettings', $rawFormData),
            'has_stepCreativeGroup' => array_key_exists('stepCreativeGroup', $rawFormData),
        ]);
        // #endregion

        $formData = self::normalizeMetaAdCreationFormData($request->form_data);
        // #region agent log
        Log::debug('meta_ad_creation: form_data after normalize', [
            'hypothesisId' => 'H1_splitRules_preserved',
            'runId' => 'verify-normalize',
            'topLevelKeys' => array_keys($formData),
            'has_splitRules' => array_key_exists('splitRules', $formData),
            'stepTwo_campaignName' => $formData['stepTwo']['campaignName'] ?? null,
            'stepOne_adAccount' => $formData['stepOne']['adAccount'] ?? null,
        ]);
        // #endregion
        $adAccountId = self::metaAdCreationGetAdAccountId($formData);
        $regionGroupId = self::metaAdCreationGetRegionGroupId($formData);
        $creativeGroupId = self::metaAdCreationGetCreativeGroupId($formData);

        // 基础必填校验（与前端 step 0、1、2 一致）
        if (empty($adAccountId)) {
            return response()->json(['success' => false, 'message' => __('请选择广告账户')], 422);
        }
        if (empty($formData['stepOne']['operator'] ?? null)) {
            return response()->json(['success' => false, 'message' => __('请选择操作员（FB 个人号）')], 422);
        }
        if (empty(trim($formData['stepTwo']['campaignName'] ?? ''))) {
            return response()->json(['success' => false, 'message' => __('请输入广告系列名称')], 422);
        }

        // objective + conversionLocation 白名单校验，避免绕过前端导致生成非法 Meta payload
        $stepOne = $formData['stepOne'] ?? [];
        $objective = $stepOne['objective'] ?? null;
        $conversionLocation = $stepOne['conversionLocation'] ?? null; // 前端值：app / website / form

        $allowedObjectives = [
            'OUTCOME_APP_PROMOTION',
            'OUTCOME_AWARENESS',
            'OUTCOME_ENGAGEMENT',
            'OUTCOME_LEADS',
            'OUTCOME_SALES',
            'OUTCOME_TRAFFIC',
        ];
        $allowedLocations = ['app', 'website', 'form'];

        if (empty($objective) || !in_array($objective, $allowedObjectives, true)) {
            return response()->json(['success' => false, 'message' => __('广告目标不支持')], 422);
        }
        if (empty($conversionLocation) || !in_array($conversionLocation, $allowedLocations, true)) {
            return response()->json(['success' => false, 'message' => __('转化发生位置不支持')], 422);
        }

        $validLocations = match ($objective) {
            'OUTCOME_APP_PROMOTION' => ['app'],
            'OUTCOME_AWARENESS', 'OUTCOME_ENGAGEMENT' => ['website'],
            'OUTCOME_LEADS' => ['website', 'app', 'form'],
            'OUTCOME_SALES', 'OUTCOME_TRAFFIC' => ['website', 'app'],
            default => [],
        };

        if (!in_array($conversionLocation, $validLocations, true)) {
            return response()->json(['success' => false, 'message' => __('目标与转化发生位置组合不合法')], 422);
        }

        // leads + 即时表单：必须选择表单
        if ($objective === 'OUTCOME_LEADS' && $conversionLocation === 'form' && empty($stepOne['form'] ?? null)) {
            return response()->json(['success' => false, 'message' => __('请在「基础设置」中选择表单')], 422);
        }

        // website 场景：按当前实现像素要求
        if ($conversionLocation === 'website') {
            $pixelObjectiveAllowWithoutPixel = ['OUTCOME_TRAFFIC', 'OUTCOME_AWARENESS', 'OUTCOME_ENGAGEMENT'];
            if (!in_array($objective, $pixelObjectiveAllowWithoutPixel, true) && empty($stepOne['pixel'] ?? null)) {
                return response()->json(['success' => false, 'message' => __('网站转化请在基础设置中选择像素')], 422);
            }
        }

        $record = MetaAdCreationRecord::create([
            'user_id' => Auth::id(),
            'fb_ad_account_id' => $adAccountId,
            'draft_id' => $request->input('draft_id'),
            'template_id' => $request->input('template_id'),
            'region_group_id' => $regionGroupId,
            'creative_group_id' => $creativeGroupId,
            'form_data_snapshot' => $formData,
            'fb_campaign_id' => null,
            'fb_adset_ids' => null,
            'fb_ad_ids' => null,
        ]);

        // #region agent log
        try {
            $fresh = $record->fresh();
            $stored = $fresh?->form_data_snapshot ?? [];
            Log::debug('meta_ad_creation: form_data_snapshot stored', [
                'hypothesisId' => 'H1_splitRules_preserved',
                'runId' => 'verify-snapshot-store',
                'has_splitRules' => is_array($stored) && array_key_exists('splitRules', $stored),
            ]);
        } catch (\Throwable $e) {
            Log::debug('meta_ad_creation: form_data_snapshot stored (skip fresh)', [
                'hypothesisId' => 'H1_splitRules_preserved',
                'runId' => 'verify-snapshot-store',
                'error' => $e->getMessage(),
            ]);
        }
        // #endregion

        try {
            $launchService = app(MetaAdCreationLaunchService::class);
            $result = $launchService->submitAndLaunch($record, $formData, $request->input('meta_counts', []));
        } catch (\Throwable $e) {
            Log::error('Meta record submit failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '提交失败：' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'fbAdAccountId' => $record->fb_ad_account_id,
                'createdAt' => $record->created_at?->toIso8601String(),
                'launched' => $result['launched'],
                'adLogId' => $result['ad_log_id'] ?? null,
            ],
            'message' => $result['message'],
        ]);
    }

    /**
     * 单条记录详情
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForRecords();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '创建记录表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $record = MetaAdCreationRecord::where('user_id', Auth::id())->where('id', $id)->first();
        if (!$record) {
            return response()->json(['success' => false, 'message' => __('记录不存在')], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'fbAdAccountId' => $record->fb_ad_account_id,
                'draftId' => $record->draft_id,
                'templateId' => $record->template_id,
                'regionGroupId' => $record->region_group_id,
                'creativeGroupId' => $record->creative_group_id,
                'adLogId' => $record->ad_log_id,
                'fbCampaignId' => $record->fb_campaign_id,
                'fbAdsetIds' => $record->fb_adset_ids ?? [],
                'fbAdIds' => $record->fb_ad_ids ?? [],
                'formDataSnapshot' => $record->form_data_snapshot ?? [],
                'createdAt' => $record->created_at?->toIso8601String(),
                'createdAtText' => $record->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * 从关联的 AdLog 回写 fb_campaign_id、fb_adset_ids 到本记录（可选，用于按创建记录查成效）。
     */
    public function syncFromAdLog(Request $request, string $id): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missingRecord = \App\Services\MetaAdCreationSchemaGuard::missingForRecords();
            if ($missingRecord !== []) {
                return response()->json(['success' => false, 'message' => '创建记录表结构不完整：' . implode(', ', $missingRecord)], 500);
            }
            $missingMapping = \App\Services\MetaAdCreationSchemaGuard::missingForMappings();
            if ($missingMapping !== []) {
                return response()->json(['success' => false, 'message' => '映射表结构不完整：' . implode(', ', $missingMapping)], 500);
            }
        }

        $record = MetaAdCreationRecord::where('user_id', Auth::id())->where('id', $id)->first();
        if (!$record) {
            return response()->json(['success' => false, 'message' => __('记录不存在')], 404);
        }
        try {
            $launchService = app(MetaAdCreationLaunchService::class);
            $updated = $launchService->syncRecordFromAdLog($record);
        } catch (\Throwable $e) {
            Log::error('Meta record sync failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '同步失败：' . $e->getMessage(),
            ], 500);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'fbCampaignId' => $record->fb_campaign_id,
                'fbAdsetIds' => $record->fb_adset_ids,
                'updated' => $updated,
            ],
            'message' => $updated ? __('已从投放日志回写广告系列与广告组') : __('暂无投放日志或尚未创建完成'),
        ]);
    }

    /**
     * 删除历史记录
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $record = MetaAdCreationRecord::where('user_id', Auth::id())->where('id', $id)->first();
        if (!$record) {
            return response()->json(['success' => false, 'message' => __('记录不存在')], 404);
        }
        $record->delete();
        return response()->json(['success' => true, 'message' => __('已删除')]);
    }
}
