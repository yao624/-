<?php

namespace App\Services;

use App\Models\MetaAdCreationTemplate;
use App\Models\MetaAdCreationTemplateShare;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TemplateManageService
{
    /**
     * 获取模板列表（带分页）
     * 包含：当前用户创建的模板 + 被分享给当前用户的模板
     */
    public function getList(array $filters, int $pageSize = 10, int $pageNo = 1): LengthAwarePaginator
    {
        $currentUserId = Auth::id();

        // 获取分享给当前用户的模板ID列表
        $sharedTemplateIds = MetaAdCreationTemplateShare::where('user_id', $currentUserId)
            ->whereNull('deleted_at')
            ->pluck('adtemplate_id')
            ->toArray();

        $query = MetaAdCreationTemplate::with('user:id,name,email')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($currentUserId, $sharedTemplateIds) {
                // 当前用户创建的模板
                $query->where('user_id', $currentUserId)
                // 被分享给当前用户的模板
                ->orWhereIn('id', $sharedTemplateIds);
            });

        // 筛选：模板名称
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // 筛选：模板描述
        if (!empty($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }

        // 筛选：创建者
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('updated_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNo);
    }

    /**
     * 格式化列表数据
     */
    public function formatListItems($items): array
    {
        return collect($items)->map(function ($template) {
            return [
                'id' => $template->id,
                'channel' => 'facebook', // 假数据，后续渠道分表时修改
                'name' => $template->name,
                'templateId' => $template->id,
                'description' => $template->description,
                'creator' => $template->user?->name ?? $template->user?->email ?? 'Unknown',
                'creatorId' => $template->user_id,
                'updatedAt' => $template->updated_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    /**
     * 批量删除模板
     */
    public function batchDelete(array $templateIds, string $userId): int
    {
        return MetaAdCreationTemplate::where('user_id', $userId)
            ->whereIn('id', $templateIds)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * 更新模板名称和描述
     */
    public function update(string $templateId, string $userId, array $data): bool
    {
        $template = MetaAdCreationTemplate::where('id', $templateId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (!$template) {
            return false;
        }

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (empty($updateData)) {
            return true;
        }

        $updateData['updated_at'] = now();

        return $template->update($updateData);
    }
}
