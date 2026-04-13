<?php

namespace App\Services;

use App\Models\MetaOrganization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaOrganizationService
{
    /**
     * 获取组织树（含员工）
     *
     * @return array
     */
    public function getTree(): array
    {
        $organizations = MetaOrganization::with(['children', 'users'])
            ->where('parent_id', 0)
            ->where('is_del', 0)
            ->orderBy('sort')
            ->orderBy('id', 'desc')
            ->get();

        return $this->buildTree($organizations);
    }

    /**
     * 递归构建树
     *
     * @param $organizations
     * @return array
     */
    private function buildTree($organizations): array
    {
        return $organizations->map(function ($org) {
            $node = [
                'id' => $org->id,
                'parent_id' => $org->parent_id,
                'name' => $org->name,
                'code' => $org->code,
                'type' => 'org',
            ];

            // 添加子组织
            if ($org->children->isNotEmpty()) {
                $node['children'] = $this->buildTree($org->children);
            } else {
                $node['children'] = [];
            }

            // 添加员工
            $node['users'] = $org->users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super' => $user->is_super ?? 0,
            ])->toArray();

            return $node;
        })->toArray();
    }

    /**
     * 获取组织列表
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getList(array $filters = [])
    {
        $query = MetaOrganization::query()->where('is_del', 0);

        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $query->where('name', 'like', "%{$filters['keyword']}%");
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        return $query->orderBy('sort')->orderBy('id', 'desc')->get();
    }

    /**
     * 创建组织
     *
     * @param array $data
     * @return MetaOrganization
     * @throws \Exception
     */
    public function create(array $data): MetaOrganization
    {
        try {
            DB::beginTransaction();

            // 检查层级深度，最大10级
            $level = 1;
            if (!empty($data['parent_id'])) {
                $level = $this->getOrgLevel((int)$data['parent_id']) + 1;
                if ($level > 10) {
                    throw new \Exception('组织最多支持10级');
                }
            }

            $org = MetaOrganization::create($data);

            DB::commit();

            return $org;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create organization', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取组织的层级深度
     *
     * @param int $orgId
     * @return int
     */
    public function getOrgLevel(int $orgId): int
    {
        $org = MetaOrganization::find($orgId);
        if (!$org || $org->parent_id == 0) {
            return 1;
        }
        return 1 + $this->getOrgLevel($org->parent_id);
    }

    /**
     * 更新组织
     *
     * @param MetaOrganization $org
     * @param array $data
     * @return MetaOrganization
     * @throws \Exception
     */
    public function update(MetaOrganization $org, array $data): MetaOrganization
    {
        try {
            DB::beginTransaction();

            $org->update($data);

            DB::commit();

            return $org;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update organization', ['org_id' => $org->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除组织
     *
     * @param MetaOrganization $org
     * @return bool
     * @throws \Exception
     */
    public function delete(MetaOrganization $org): bool
    {
        try {
            DB::beginTransaction();

            // 递归删除子组织
            $this->deleteChildren($org->id);

            // 删除组织本身
            $org->update(['is_del' => 1]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete organization', ['org_id' => $org->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 递归删除子组织
     *
     * @param int $parentId
     * @return void
     */
    private function deleteChildren(int $parentId): void
    {
        $children = MetaOrganization::where('parent_id', $parentId)->where('is_del', 0)->get();

        foreach ($children as $child) {
            $this->deleteChildren($child->id);
            $child->update(['is_del' => 1]);
        }
    }
}
