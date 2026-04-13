<?php

namespace App\Services;

use App\Models\MetaPermission;
use App\Models\MetaUserMenu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaUserMenuService
{
    /**
     * 获取用户菜单权限（带checked标记）
     *
     * @param int $userId
     * @return array
     */
    public function getUserMenus(int $userId): array
    {
        // 获取用户已分配的菜单ID
        $assignedMenuIds = MetaUserMenu::where('user_id', $userId)->pluck('menu_id')->toArray();

        // 获取菜单树（顶级）
        $menus = MetaPermission::where('pid', 0)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return $this->buildMenuTree($menus, $assignedMenuIds);
    }

    /**
     * 递归构建菜单树
     *
     * @param $menus
     * @param array $assignedMenuIds
     * @return array
     */
    private function buildMenuTree($menus, array $assignedMenuIds): array
    {
        $result = [];
        foreach ($menus as $menu) {
            if ($menu->status !== 1) {
                continue;
            }
            $node = [
                'id' => $menu->id,
                'name' => $menu->name,
                'checked' => in_array($menu->id, $assignedMenuIds),
                'children' => [],
            ];

            $children = $menu->children()->where('status', 1)->get();
            if ($children->isNotEmpty()) {
                $node['children'] = $this->buildMenuTree($children, $assignedMenuIds);
            }

            $result[] = $node;
        }
        return $result;
    }

    /**
     * 分配用户菜单权限
     *
     * @param int $userId
     * @param array $menuIds
     * @return bool
     * @throws \Exception
     */
    public function assignMenus(int $userId, array $menuIds): bool
    {
        try {
            DB::beginTransaction();

            // 删除现有权限
            MetaUserMenu::where('user_id', $userId)->delete();

            // 添加新权限
            foreach ($menuIds as $menuId) {
                MetaUserMenu::create([
                    'user_id' => $userId,
                    'menu_id' => $menuId,
                ]);
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign menus', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 移除用户菜单权限
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function removeMenu(int $id): bool
    {
        try {
            $userMenu = MetaUserMenu::find($id);

            if (!$userMenu) {
                throw new \Exception('Menu permission not found');
            }

            $userMenu->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove menu', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
