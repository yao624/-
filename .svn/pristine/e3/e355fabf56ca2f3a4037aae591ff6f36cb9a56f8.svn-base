<?php

namespace App\Http\Controllers\AdTemplate;

use App\Http\Controllers\Controller;
use App\Services\TemplateManageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateManageController extends Controller
{
    use ApiResponse;

    private TemplateManageService $service;

    public function __construct(TemplateManageService $service)
    {
        $this->service = $service;
    }

    /**
     * 模板列表（支持筛选和分页）
     */
    public function list(Request $request): JsonResponse
    {
        $filters = [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'user_id' => $request->input('user_id'),
        ];


        $channel = (int) $request->input('channel', 'facebook');
        $pageSize = (int) $request->input('pageSize', 10);
        $pageNo = (int) $request->input('pageNo', 1);

        $paginator = $this->service->getList($filters, $pageSize, $pageNo);

        return $this->success([
            'data' => $this->service->formatListItems($paginator->items()),
            'pageSize' => $paginator->perPage(),
            'pageNo' => $paginator->currentPage(),
            'totalPage' => $paginator->lastPage(),
            'totalCount' => $paginator->total(),
        ]);
    }

    /**
     * 更新模板名称和描述
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:meta_ad_creation_templates,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $updated = $this->service->update(
            $request->input('template_id'),
            Auth::id(),
            $request->only(['name', 'description'])
        );

        if (!$updated) {
            return $this->fail('模板不存在或无权修改', 404);
        }

        return $this->success(null, '模板已更新');
    }

    /**
     * 分享/批量分享模板给指定用户
     */
    public function share(Request $request): JsonResponse
    {
        $request->validate([
            'template_ids' => 'required|array|min:1',
            'template_ids.*' => 'exists:meta_ad_creation_templates,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userId = Auth::id();
        $templateIds = $request->input('template_ids');
        $targetUserIds = $request->input('user_ids');

        $sharedCount = app(\App\Services\TemplateShareService::class)
            ->shareTemplates($templateIds, $targetUserIds, $userId);

        if ($sharedCount === 0) {
            return $this->fail('没有可分享的模板', 404);
        }

        return $this->success(
            ['sharedCount' => $sharedCount],
            __('已分享 :count 个模板', ['count' => $sharedCount])
        );
    }

    /**
     * 删除模板（软删除）
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'template_ids' => 'required|array|min:1',
            'template_ids.*' => 'exists:meta_ad_creation_templates,id',
        ]);

        $deleted = $this->service->batchDelete(
            $request->input('template_ids'),
            Auth::id()
        );

        if ($deleted === 0) {
            return $this->fail('没有可删除的模板', 404);
        }

        return $this->success(
            ['deletedCount' => $deleted],
            __('已删除 :count 个模板', ['count' => $deleted])
        );
    }
}
