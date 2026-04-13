<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMetaOrganizationRequest;
use App\Http\Requests\UpdateMetaOrganizationRequest;
use App\Models\MetaOrganization;
use App\Services\MetaOrganizationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaOrganizationController extends Controller
{
    use ApiResponse;

    protected MetaOrganizationService $service;

    public function __construct(MetaOrganizationService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取组织树（含员工）
     *
     * @return JsonResponse
     */
    public function tree(): JsonResponse
    {
        $tree = $this->service->getTree();

        return $this->success($tree);
    }

    /**
     * 获取组织列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'keyword' => $request->input('keyword'),
            'parent_id' => $request->input('parent_id'),
        ];

        $organizations = $this->service->getList($filters);

        return $this->success($organizations);
    }

    /**
     * 创建组织
     *
     * @param StoreMetaOrganizationRequest $request
     * @return JsonResponse
     */
    public function create(StoreMetaOrganizationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $org = $this->service->create($data);

            return $this->success($org);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 更新组织
     *
     * @param UpdateMetaOrganizationRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateMetaOrganizationRequest $request, int $id): JsonResponse
    {
        $org = MetaOrganization::find((int) $id);

        if (!$org) {
            return $this->fail('Organization not found', 404);
        }

        try {
            $data = $request->validated();
            $updated = $this->service->update($org, $data);

            return $this->success($updated, 'Organization updated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 删除组织
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $org = MetaOrganization::find((int) $id);

        if (!$org) {
            return $this->fail('Organization not found', 404);
        }

        try {
            $this->service->delete($org);

            return $this->success(null, 'Organization deleted successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * 获取组织详情
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $org = MetaOrganization::find((int) $id);

        if (!$org) {
            return $this->fail('Organization not found', 404);
        }

        return $this->success($org);
    }
}
