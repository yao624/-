<?php

namespace App\Services;

use App\Models\MetaAutoRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaAutoRuleService
{
    /**
     * 获取规则列表
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getList(array $filters = [])
    {
        $query = MetaAutoRule::query()->where('is_del', 0);

        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $query->where('name', 'like', "%{$filters['keyword']}%");
        }

        if (isset($filters['monitoring_object']) && $filters['monitoring_object'] !== '') {
            $query->where('monitoring_object', $filters['monitoring_object']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['template_id']) && $filters['template_id'] !== '') {
            $query->where('template_id', $filters['template_id']);
        }

        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('id', 'desc')->get();
    }

    /**
     * 创建规则
     *
     * @param array $data
     * @return MetaAutoRule
     * @throws \Exception
     */
    public function create(array $data): MetaAutoRule
    {
        $userId = $data['user_id'] ?? 0;
        $limitNum = config('app.rule_limit_num', 15);
        $currentCount = MetaAutoRule::where('user_id', $userId)->where('is_del', 0)->count();

        if ($currentCount >= $limitNum) {
            throw new \Exception("规则数量已达上限，最多可创建 {$limitNum} 条规则");
        }

        try {
            DB::beginTransaction();

            $rule = MetaAutoRule::create($data);

            DB::commit();

            return $rule;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新规则
     *
     * @param MetaAutoRule $rule
     * @param array $data
     * @return MetaAutoRule
     * @throws \Exception
     */
    public function update(MetaAutoRule $rule, array $data): MetaAutoRule
    {
        try {
            DB::beginTransaction();

            $rule->update($data);

            DB::commit();

            return $rule;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update rule', ['rule_id' => $rule->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除规则（软删除）
     *
     * @param MetaAutoRule $rule
     * @return bool
     * @throws \Exception
     */
    public function delete(MetaAutoRule $rule): bool
    {
        try {
            $rule->update(['is_del' => 1]);

            Log::info('Rule deleted', ['rule_id' => $rule->id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete rule', ['rule_id' => $rule->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
