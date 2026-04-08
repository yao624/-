<?php

namespace App\Http\Controllers;

use App\Services\MetaMaterialEditorTaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class MetaMaterialEditorTaskController extends Controller
{
    use ApiResponse;

    private MetaMaterialEditorTaskService $service;

    public function __construct(MetaMaterialEditorTaskService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /material-editor/tasks
     * 创建素材编辑任务
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_name' => 'required|string|max:255',
            'material_type' => 'nullable|in:image,video',
            'folder_option' => 'required|in:original,new',
            'folder_id' => 'nullable|integer',
            'designer_id' => 'nullable|integer',
            'creator_id' => 'nullable|integer',
            'tags' => 'nullable|array',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required_with:materials|integer',
            'materials.*.material_name' => 'nullable|string|max:255',
            'materials.*.preview_url' => 'nullable|string|max:500',
            'materials.*.original_preview_url' => 'nullable|string|max:500',
            'materials.*.edit_content' => 'nullable|string',
            'materials.*.reason' => 'nullable|string|max:500',
            'created_by' => 'required|integer',
        ]);

        $task = $this->service->createTask($validated);

        return $this->success([
            'id' => $task->id,
            'taskName' => $task->task_name,
            'status' => $task->status,
        ], '任务创建成功');
    }

    /**
     * GET /material-editor/tasks
     * 获取任务列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'task_name' => $request->query('task_name'),
            'status' => $request->query('status'),
            'material_type' => $request->query('material_type'),
            'created_by' => $request->query('created_by'),
            'designer_id' => $request->query('designer_id'),
            'creator_id' => $request->query('creator_id'),
            'create_time_start' => $request->query('create_time_start'),
            'create_time_end' => $request->query('create_time_end'),
        ];

        $pageNo = $request->has('pageNo') ? (int) $request->query('pageNo') : null;
        $pageSize = $request->has('pageSize') ? (int) $request->query('pageSize') : null;

        $result = $this->service->getList($filters, $pageNo, $pageSize);

        return $this->success($result);
    }

    /**
     * GET /material-editor/tasks/{id}
     * 获取任务详情
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $detail = $this->service->getDetail($id);

        return $this->success($detail);
    }
}
