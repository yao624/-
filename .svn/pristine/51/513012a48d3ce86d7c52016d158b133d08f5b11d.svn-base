<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMetaAutoRuleRequest;
use App\Models\MetaAutoRule;
use App\Services\MetaAutoRuleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetaAutoRuleController extends Controller
{
    use ApiResponse;

    protected MetaAutoRuleService $service;

    public function __construct(MetaAutoRuleService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取规则列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $filters = [
            'keyword' => $request->input('keyword'),
            'monitoring_object' => $request->input('monitoring_object'),
            'status' => $request->input('status'),
            'template_id' => $request->input('template_id'),
            'user_id' => Auth::id(),
        ];

        $rules = $this->service->getList($filters);

        return $this->success($rules);
    }

    /**
     * 创建规则
     *
     * @param StoreMetaAutoRuleRequest $request
     * @return JsonResponse
     */
    public function create(StoreMetaAutoRuleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id() ?? 0;
            $rule = $this->service->create($data);

            return $this->success($rule);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 更新规则
     *
     * @param StoreMetaAutoRuleRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(StoreMetaAutoRuleRequest $request, int $id): JsonResponse
    {
        $rule = MetaAutoRule::find((int)$id);

        if (!$rule) {
            return $this->fail('Rule not found', 404);
        }

        try {
            $data = $request->validated();
            $updated = $this->service->update($rule, $data);

            return $this->success($updated, 'Rule updated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 删除规则
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');

        try {
            MetaAutoRule::whereIn('id', $ids)->update(['is_del' => 1]);

            return $this->success(null, 'Rules deleted successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 获取规则详情
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $rule = MetaAutoRule::find((int)$id);

        if (!$rule) {
            return $this->fail('Rule not found', 404);
        }

        return $this->success($rule);
    }

    /**
     * 获取规则创建者列表
     *
     * @return JsonResponse
     */
    public function creators(): JsonResponse
    {
        $creators = MetaAutoRule::join('users', 'meta_auto_rules.user_id', '=', 'users.id')
            ->where('meta_auto_rules.is_del', 0)
            ->select('users.id', 'users.name')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(fn($item) => ['id' => (string)$item->id, 'name' => $item->name])
            ->toArray();

        return $this->success($creators);
    }

    /**
     * 获取规则统计摘要
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $userId = auth()->id() ?? 0;

        $rules = MetaAutoRule::where('user_id', $userId)
            ->where('is_del', 0)
            ->where('status', 1)
            ->get();

        $sendNotification = 0;
        $sendNotificationHours = 0;
        $toggleAds = 0;
        $toggleAdsHours = 0;
        $modifyBid = 0;
        $modifyBidHours = 0;
        $modifyBudget = 0;
        $modifyBudgetHours = 0;

        foreach ($rules as $rule) {
            $actions = $rule->actions ?? [];
            $actionList = $actions['actions'] ?? [];

            foreach ($actionList as $action) {
                if (!($action['enabled'] ?? false)) {
                    continue;
                }

                $type = $action['type'] ?? '';
                $interval = $rule->execution_interval ?? '24h';
                $hours = (int) filter_var($interval, FILTER_SANITIZE_NUMBER_INT) ?: 24;

                // 发送通知类：send-email, send-tg-alert, send_email, send_tg_alert, notification, send-dingtalk
                if (in_array($type, ['send-email', 'send-tg-alert', 'send_email', 'send_tg_alert', 'notification', 'send-dingtalk'])) {
                    $sendNotification++;
                    $sendNotificationHours += $hours;
                // 开关广告类：turn-off, turn-on, turn_off, turn_on
                } elseif (in_array($type, ['turn-off', 'turn-on', 'turn_off', 'turn_on'])) {
                    $toggleAds++;
                    $toggleAdsHours += $hours;
                // 修改出价类：adjust-bid, increase-bid, decrease-bid, adjust_bid, increase_bid, decrease_bid
                } elseif (in_array($type, ['adjust-bid', 'increase-bid', 'decrease-bid', 'adjust_bid', 'increase_bid', 'decrease_bid'])) {
                    $modifyBid++;
                    $modifyBidHours += $hours;
                // 修改预算类：adjust-budget, increase-budget, decrease-budget, adjust_budget, increase_budget, decrease_budget
                } elseif (in_array($type, ['adjust-budget', 'increase-budget', 'decrease-budget', 'adjust_budget', 'increase_budget', 'decrease_budget'])) {
                    $modifyBudget++;
                    $modifyBudgetHours += $hours;
                }
            }
        }

        $allActions = $sendNotification + $toggleAds + $modifyBid + $modifyBudget;
        $allActionsHours = $sendNotificationHours + $toggleAdsHours + $modifyBidHours + $modifyBudgetHours;

        $data = [
            'sendNotification' => $sendNotification,
            'sendNotificationHours' => $sendNotificationHours,
            'toggleAds' => $toggleAds,
            'toggleAdsHours' => $toggleAdsHours,
            'modifyBid' => $modifyBid,
            'modifyBidHours' => $modifyBidHours,
            'modifyBudget' => $modifyBudget,
            'modifyBudgetHours' => $modifyBudgetHours,
            'allActions' => $allActions,
            'allActionsHours' => $allActionsHours,
        ];

        return $this->success($data);
    }

    /**
     * 批量启用规则
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchActive(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');
        MetaAutoRule::whereIn('id', $ids)->update(['status' => 1]);

        return $this->success(null, 'Rules activated successfully');
    }

    /**
     * 批量停用规则
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchInactive(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');
        MetaAutoRule::whereIn('id', $ids)->update(['status' => 0]);

        return $this->success(null, 'Rules deactivated successfully');
    }

    /**
     * 审核规则（仅超管可操作）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function audit(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user || !$user->is_super) {
            return $this->fail('无访问权限，仅超管可操作', 403);
        }

        $request->validate([
            'id' => 'required|integer',
            'audit_status' => 'required|integer|in:1,2',
            'audit_reason' => 'nullable|string|max:500',
        ]);

        $id = $request->input('id');
        $auditStatus = $request->input('audit_status');
        $auditReason = $request->input('audit_reason');

        $rule = MetaAutoRule::find((int)$id);

        if (!$rule) {
            return $this->fail('Rule not found', 404);
        }

        $rule->update([
            'audit_status' => $auditStatus,
            'audit_reason' => $auditReason,
        ]);

        return $this->success(null, 'Rule audited successfully');
    }
}
