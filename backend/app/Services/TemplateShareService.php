<?php

namespace App\Services;

use App\Models\MetaAdCreationTemplate;
use App\Models\MetaAdCreationTemplateShare;
use Illuminate\Support\Str;

class TemplateShareService
{
    /**
     * 获取模板列表（包括被分享的）
     */
    public function getListWithShared(string $userId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = MetaAdCreationTemplate::with('user:id,name,email')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sharedWith', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            });

        // 可选：按广告账户筛选
        if (!empty($filters['fb_ad_account_id'])) {
            $query->where('fb_ad_account_id', $filters['fb_ad_account_id']);
        }

        // 排序参数
        $sortField = $filters['sortField'] ?? 'created_at';
        $sortDirection = $filters['sortDirection'] ?? 'desc';
        $allowedSortFields = ['created_at', 'updated_at', 'name'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'created_at';

        return $query->orderBy($sortField, $sortDirection)
            ->paginate($filters['pageSize'] ?? 10, ['*'], 'page', $filters['pageNo'] ?? 1);
    }

    /**
     * 格式化列表数据（带分享标识）
     */
    public function formatListWithSharedItems($items, string $currentUserId): array
    {
        return collect($items)->map(function ($template) use ($currentUserId) {
            return [
                'id' => $template->id,
                'fbAdAccountId' => $template->fb_ad_account_id,
                'name' => $template->name,
                'formData' => $template->form_data ?? [],
                'metaCounts' => $template->meta_counts ?? [],
                'createdAt' => $template->created_at?->toIso8601String(),
                'createdAtText' => $template->created_at?->format('Y-m-d H:i:s'),
                'isShared' => $template->user_id !== $currentUserId,
            ];
        })->toArray();
    }

    /**
     * 分享模板给指定用户（支持批量）
     */
    public function shareTemplates(array $templateIds, array $targetUserIds, string $ownerId): int
    {
        // 检查模板所有权
        $templates = MetaAdCreationTemplate::where('user_id', $ownerId)
            ->whereIn('id', $templateIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        if (empty($templates)) {
            return 0;
        }

        $sharedCount = 0;
        $now = now();

        foreach ($templates as $templateId) {
            foreach ($targetUserIds as $targetUserId) {
                $exists = MetaAdCreationTemplateShare::where('adtemplate_id', $templateId)
                    ->where('user_id', $targetUserId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    MetaAdCreationTemplateShare::create([
                        'id' => (string) Str::ulid(),
                        'adtemplate_id' => $templateId,
                        'user_id' => $targetUserId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $sharedCount++;
                }
            }
        }

        return $sharedCount;
    }

    /**
     * 取消分享
     */
    public function unshareTemplates(array $templateIds, array $targetUserIds, string $ownerId): int
    {
        // 检查模板所有权
        $templates = MetaAdCreationTemplate::where('user_id', $ownerId)
            ->whereIn('id', $templateIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        if (empty($templates)) {
            return 0;
        }

        return MetaAdCreationTemplateShare::whereIn('adtemplate_id', $templates)
            ->whereIn('user_id', $targetUserIds)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
