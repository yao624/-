<?php

namespace App\Http\Controllers\AdTemplate;

use App\Http\Controllers\Controller;
use App\Services\TemplateShareService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateShareController extends Controller
{
    use ApiResponse;

    private TemplateShareService $service;

    public function __construct(TemplateShareService $service)
    {
        $this->service = $service;
    }

    /**
     * 列表：当前用户的 Meta 广告创建模板（包括被分享的）
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'fb_ad_account_id' => $request->input('fb_ad_account_id'),
            'sortField' => $request->input('sortField', 'created_at'),
            'sortDirection' => $request->input('sortOrder', 'desc'),
            'pageSize' => (int) $request->input('pageSize', 10),
            'pageNo' => (int) $request->input('pageNo', 1),
        ];

        $paginator = $this->service->getListWithShared(Auth::id(), $filters);

        return $this->success([
            'data' => $this->service->formatListWithSharedItems($paginator->items(), Auth::id()),
            'pageSize' => $paginator->perPage(),
            'pageNo' => $paginator->currentPage(),
            'totalPage' => $paginator->lastPage(),
            'totalCount' => $paginator->total(),
        ]);
    }

    /**
     * 分享模板给指定用户（支持批量）
     */
    public function share(Request $request): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'template_ids' => 'required|array|min:1',
            'template_ids.*' => 'exists:meta_ad_creation_templates,id',
        ]);

        $userId = Auth::id();
        $templateIds = $request->input('template_ids');
        $targetUserIds = $request->input('user_ids');

        $sharedCount = $this->service->shareTemplates($templateIds, $targetUserIds, $userId);

        return $this->success(
            ['sharedCount' => $sharedCount],
            __('已分享 :count 个模板', ['count' => $sharedCount])
        );
    }

    /**
     * 批量分享（与 share 相同，保留命名一致性）
     */
    public function batchShare(Request $request): JsonResponse
    {
        return $this->share($request);
    }

    /**
     * 取消分享
     */
    public function unshare(Request $request): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'template_ids' => 'required|array|min:1',
            'template_ids.*' => 'exists:meta_ad_creation_templates,id',
        ]);

        $userId = Auth::id();
        $templateIds = $request->input('template_ids');
        $targetUserIds = $request->input('user_ids');

        $unsharedCount = $this->service->unshareTemplates($templateIds, $targetUserIds, $userId);

        return $this->success(
            ['unsharedCount' => $unsharedCount],
            __('已取消分享 :count 个模板', ['count' => $unsharedCount])
        );
    }
}
