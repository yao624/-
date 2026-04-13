<?php

namespace App\Http\Controllers;

use App\Services\ImageTemplateService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImageTemplateController extends Controller
{
    use ApiResponse;

    private ImageTemplateService $service;

    public function __construct(ImageTemplateService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取模板列表
     */
    public function list(Request $request): JsonResponse
    {
        $filters = [
            'template_name' => $request->input('template_name'),
            'canvas_width' => $request->input('canvas_width'),
            'canvas_height' => $request->input('canvas_height'),
        ];

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
     * 获取模板详情
     */
    public function detail(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        $id = (int) $request->input('id');

        $template = $this->service->getDetail($id);

        return $this->success([
            'id' => (string) $template->id,
            'name' => $template->template_name,
            'width' => $template->canvas_width,
            'height' => $template->canvas_height,
            'json' => $template->canvas_json,
            'dynamicVariables' => $template->dynamic_variables,
            'previewImage' => $template->preview_image,
            'variableCount' => $template->variable_count ?? 0,
            'description' => $template->description,
            'createdAt' => $template->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $template->updated_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 创建模板
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'template_name' => 'required|string|max:200',
            'canvas_width' => 'required|integer|min:1',
            'canvas_height' => 'required|integer|min:1',
            'canvas_json' => 'required',
            'dynamic_variables' => 'nullable',
            'description' => 'nullable|string',
            'preview_image' => 'nullable|string|max:500',
        ]);

        $template = $this->service->create($request->all());

        return $this->success([
            'id' => (string) $template->id,
        ], '模板创建成功');
    }

    /**
     * 更新模板
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|min:1',
            'template_name' => 'nullable|string|max:200',
            'canvas_width' => 'nullable|integer|min:1',
            'canvas_height' => 'nullable|integer|min:1',
            'canvas_json' => 'nullable',
            'dynamic_variables' => 'nullable',
            'description' => 'nullable|string',
            'preview_image' => 'nullable|string|max:500',
        ]);

        $template = $this->service->update(
            (int) $request->input('id'),
            $request->except('id')
        );

        return $this->success([
            'id' => (string) $template->id,
        ], '模板更新成功');
    }

    /**
     * 删除模板
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        $this->service->delete((int) $request->input('id'));

        return $this->success(null, '模板删除成功');
    }

    /**
     * 批量删除模板
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|min:1',
        ]);

        $deletedCount = $this->service->batchDelete($request->input('ids'));

        return $this->success([
            'deletedCount' => $deletedCount,
        ], "已删除 {$deletedCount} 个模板");
    }

    /**
     * 复制模板
     */
    public function copy(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|min:1',
        ]);

        $template = $this->service->copy((int) $request->input('id'));

        return $this->success([
            'id' => (string) $template->id,
            'name' => $template->template_name,
        ], '模板复制成功');
    }
}
