<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMetaUserMenuRequest;
use App\Services\MetaUserMenuService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaUserMenuController extends Controller
{
    use ApiResponse;

    protected MetaUserMenuService $service;

    public function __construct(MetaUserMenuService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取用户菜单权限
     *
     * @param int $id
     * @return JsonResponse
     */
    public function index(int $id): JsonResponse
    {
        $menus = $this->service->getUserMenus((int) $id);

        return $this->success($menus);
    }

    /**
     * 分配用户菜单权限
     *
     * @param StoreMetaUserMenuRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function store(StoreMetaUserMenuRequest $request, int $id): JsonResponse
    {
        try {
            $menuIds = $request->input('menu_ids', []);
            $this->service->assignMenus((int) $id, $menuIds);

            return $this->success(null, 'Menus assigned successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 移除用户菜单权限
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->removeMenu((int) $id);

            return $this->success(null, 'Menu removed successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}
