<?php

namespace App\Services;

use App\Models\MetaMaterialEditorTask;
use App\Models\MetaMaterialEditItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MetaMaterialEditorTaskService
{
    /**
     * 创建编辑任务
     *
     * @param array $data
     * @return MetaMaterialEditorTask
     */
    public function createTask(array $data): MetaMaterialEditorTask
    {
        return DB::transaction(function () use ($data) {
            $task = MetaMaterialEditorTask::create([
                'task_name' => $data['task_name'] ?? '',
                'status' => 'pending',
                'material_type' => $data['material_type'] ?? null,
                'folder_option' => $data['folder_option'] ?? 'original',
                'folder_id' => $data['folder_id'] ?? null,
                'designer_id' => $data['designer_id'] ?? null,
                'creator_id' => $data['creator_id'] ?? null,
                'tags' => $data['tags'] ?? null,
                'total_count' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'pending_count' => 0,
                'created_by' => $data['created_by'],
            ]);

            // 如果有素材列表，创建编辑项
            if (!empty($data['materials']) && is_array($data['materials'])) {
                $this->createEditItems($task, $data['materials']);
                $task->update(['total_count' => count($data['materials']), 'pending_count' => count($data['materials'])]);
            }

            return $task->fresh();
        });
    }

    /**
     * 创建编辑项
     *
     * @param MetaMaterialEditorTask $task
     * @param array $materials
     * @return void
     */
    private function createEditItems(MetaMaterialEditorTask $task, array $materials): void
    {
        $items = [];
        foreach ($materials as $material) {
            $items[] = [
                'task_id' => $task->id,
                'material_id' => $material['material_id'],
                'material_name' => $material['material_name'] ?? '',
                'preview_url' => $material['preview_url'] ?? null,
                'original_preview_url' => $material['original_preview_url'] ?? null,
                'status' => 'pending',
                'edit_content' => $material['edit_content'] ?? null,
                'reason' => $material['reason'] ?? null,
                'created_at' => now(),
            ];
        }
        MetaMaterialEditItem::insert($items);
    }

    /**
     * 获取任务列表
     *
     * @param array $filters
     * @param int|null $pageNo
     * @param int|null $pageSize
     * @return array
     */
    public function getList(array $filters = [], ?int $pageNo = null, ?int $pageSize = null): array
    {
        $query = $this->buildQuery($filters);

        $hasPagination = $pageNo !== null || $pageSize !== null;

        if ($hasPagination) {
            $pageNo = max($pageNo ?? 1, 1);
            $pageSize = $this->normalizePageSize($pageSize ?? 20);

            $totalCount = $query->count();
            $totalPage = (int) ceil($totalCount / $pageSize);
            $rows = $query
                ->orderByDesc('created_at')
                ->offset(($pageNo - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            return [
                'data' => $this->formatListData($rows),
                'pageSize' => $pageSize,
                'pageNo' => $pageNo,
                'totalPage' => $totalPage,
                'totalCount' => $totalCount,
            ];
        } else {
            $rows = $query->orderByDesc('created_at')->limit(200)->get();
            $totalCount = $rows->count();

            return [
                'data' => $this->formatListData($rows),
                'totalCount' => $totalCount,
            ];
        }
    }

    /**
     * 获取任务详情
     *
     * @param int $taskId
     * @return array
     */
    public function getDetail(int $taskId): array
    {
        $task = MetaMaterialEditorTask::with(['editItems', 'creator', 'designer', 'creatorUser'])
            ->findOrFail($taskId);

        return [
            'id' => $task->id,
            'taskName' => $task->task_name,
            'status' => $task->status,
            'materialType' => $task->material_type,
            'folderOption' => $task->folder_option,
            'folderId' => $task->folder_id,
            'designerId' => $task->designer_id,
            'designerName' => $task->designer?->name ?? null,
            'creatorId' => $task->creator_id,
            'creatorName' => $task->creatorUser?->name ?? null,
            'tags' => $task->tags,
            'totalCount' => $task->total_count,
            'successCount' => $task->success_count,
            'failedCount' => $task->failed_count,
            'pendingCount' => $task->pending_count,
            'errorMessage' => $task->error_message,
            'createdBy' => $task->created_by,
            'creator' => $task->creator?->name ?? null,
            'createdAt' => $task->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $task->updated_at?->format('Y-m-d H:i:s'),
            'completedAt' => $task->completed_at?->format('Y-m-d H:i:s'),
            'items' => $this->formatEditItems($task->editItems),
        ];
    }

    /**
     * 格式化编辑项数据
     *
     * @param mixed $items
     * @return array
     */
    private function formatEditItems($items): array
    {
        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'materialId' => $item->material_id,
                'materialName' => $item->material_name,
                'previewUrl' => $item->preview_url,
                'originalPreviewUrl' => $item->original_preview_url,
                'originalMaterialName' => $item->material_name, // 原素材名称与素材名称相同
                'status' => $item->status,
                'editContent' => $item->edit_content,
                'reason' => $item->reason,
                'createdAt' => $item->created_at?->format('Y-m-d H:i:s'),
                'updatedAt' => $item->updated_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * 构建查询
     *
     * @param array $filters
     * @return Builder
     */
    private function buildQuery(array $filters): Builder
    {
        $query = MetaMaterialEditorTask::with(['creator']);

        // 按任务名称筛选
        if (!empty($filters['task_name'])) {
            $query->where('task_name', 'like', '%' . $filters['task_name'] . '%');
        }

        // 按状态筛选
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 按素材类型筛选
        if (!empty($filters['material_type'])) {
            $query->where('material_type', $filters['material_type']);
        }

        // 按创建人筛选
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        // 按设计师筛选
        if (!empty($filters['designer_id'])) {
            $query->where('designer_id', $filters['designer_id']);
        }

        // 按创意人筛选
        if (!empty($filters['creator_id'])) {
            $query->where('creator_id', $filters['creator_id']);
        }

        // 按创建时间起始筛选
        if (!empty($filters['create_time_start'])) {
            $query->where('created_at', '>=', $filters['create_time_start']);
        }

        // 按创建时间结束筛选
        if (!empty($filters['create_time_end'])) {
            $query->where('created_at', '<=', $filters['create_time_end']);
        }

        return $query;
    }

    /**
     * 规范化每页条数
     *
     * @param int $pageSize
     * @return int
     */
    private function normalizePageSize(int $pageSize): int
    {
        if ($pageSize < 1) {
            return 20;
        }
        if ($pageSize > 200) {
            return 200;
        }
        return $pageSize;
    }

    /**
     * 格式化列表数据
     *
     * @param mixed $rows
     * @return array
     */
    private function formatListData($rows): array
    {
        return $rows->map(function ($item) {
            return [
                'id' => $item->id,
                'status' => $item->status,
                'createdAt' => $item->created_at?->format('Y-m-d H:i:s'),
                'creator' => $item->creator?->name ?? null,
            ];
        })->toArray();
    }
}
