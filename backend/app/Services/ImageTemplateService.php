<?php

namespace App\Services;

use App\Models\ImageTemplate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ImageTemplateService
{
    /**
     * 获取模板列表（分页）
     */
    public function getList(array $filters, int $pageSize = 10, int $pageNo = 1): LengthAwarePaginator
    {
        $currentUserId = Auth::id();

        $query = ImageTemplate::with('user:id,name,email')
            ->where('user_id', $currentUserId);

        // 筛选条件
        if (!empty($filters['template_name'])) {
            $query->where('template_name', 'like', '%' . $filters['template_name'] . '%');
        }

        if (!empty($filters['canvas_width']) && !empty($filters['canvas_height'])) {
            $query->where('canvas_width', $filters['canvas_width'])
                  ->where('canvas_height', $filters['canvas_height']);
        }

        return $query->orderBy('updated_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNo);
    }

    /**
     * 获取模板详情
     */
    public function getDetail(int $id): ImageTemplate
    {
        $currentUserId = Auth::id();

        return ImageTemplate::where('id', $id)
            ->where('user_id', $currentUserId)
            ->firstOrFail();
    }

    /**
     * 创建模板
     */
    public function create(array $data): ImageTemplate
    {
        $data['user_id'] = Auth::id();

        return ImageTemplate::create($data);
    }

    /**
     * 更新模板
     */
    public function update(int $id, array $data): ImageTemplate
    {
        $template = $this->getDetail($id);
        $template->update($data);

        return $template->fresh();
    }

    /**
     * 删除模板（软删除）
     */
    public function delete(int $id): bool
    {
        $template = $this->getDetail($id);
        $template->delete();

        return true;
    }

    /**
     * 批量删除模板
     */
    public function batchDelete(array $ids): int
    {
        $currentUserId = Auth::id();

        return ImageTemplate::where('user_id', $currentUserId)
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * 复制模板
     */
    public function copy(int $id): ImageTemplate
    {
        $template = $this->getDetail($id);
        $currentUserId = Auth::id();

        $newData = $template->toArray();
        unset($newData['id'], $newData['created_at'], $newData['updated_at'], $newData['deleted_at']);

        // 复制时名称加上副本标识
        $newData['template_name'] = $this->generateCopyName($template->template_name);
        $newData['user_id'] = $currentUserId;

        return ImageTemplate::create($newData);
    }

    /**
     * 生成复制后的名称
     */
    private function generateCopyName(string $originalName): string
    {
        // 检查是否已有副本标识
        if (preg_match('/^(.*?)副本(\d+)$/', $originalName, $matches)) {
            $baseName = $matches[1];
            $copyNum = (int)$matches[2] + 1;
            return $baseName . '副本' . $copyNum;
        }

        // 检查是否已有"副本"后缀
        if (str_ends_with($originalName, '副本')) {
            return $originalName . '2';
        }

        return $originalName . '副本';
    }

    /**
     * 格式化列表项
     */
    public function formatListItems($items): array
    {
        return collect($items)->map(function ($template) {
            return [
                'id' => (string) $template->id,
                'name' => $template->template_name,
                'width' => $template->canvas_width,
                'height' => $template->canvas_height,
                'json' => $template->canvas_json,
                'dynamicVariables' => $template->dynamic_variables ?? [],
                'previewImage' => $template->preview_image,
                'variableCount' => $template->variable_count ?? 0,
                'description' => $template->description,
                'updatedAt' => $template->updated_at?->format('Y-m-d H:i:s'),
                'createdAt' => $template->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }
}
