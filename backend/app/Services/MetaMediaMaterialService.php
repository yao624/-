<?php

namespace App\Services;

use App\Models\MetaMediaMaterial;
use Illuminate\Database\Eloquent\Builder;

class MetaMediaMaterialService
{
    /**
     * 获取媒体素材列表
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
                ->orderByDesc('create_time')
                ->offset(($pageNo - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            return [
                'data' => $this->formatData($rows),
                'pageSize' => $pageSize,
                'pageNo' => $pageNo,
                'totalPage' => $totalPage,
                'totalCount' => $totalCount,
            ];
        } else {
            $rows = $query->orderByDesc('create_time')->limit(200)->get();
            $totalCount = $rows->count();

            return [
                'data' => $this->formatData($rows),
                'totalCount' => $totalCount,
            ];
        }
    }

    /**
     * 构建查询
     *
     * @param array $filters
     * @return Builder
     */
    private function buildQuery(array $filters): Builder
    {
        $query = MetaMediaMaterial::with(['useAccount', 'belongAccount']);

        // 按名称筛选
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // 按素材ID筛选
        if (!empty($filters['materialId'])) {
            $query->where('material_id', $filters['materialId']);
        }

        // 按渠道筛选
        if (!empty($filters['channel'])) {
            $query->where('channel', 'like', '%' . $filters['channel'] . '%');
        }

        // 按使用账户筛选
        if (!empty($filters['useAccount'])) {
            $query->where('use_account', 'like', '%' . $filters['useAccount'] . '%');
        }

        // 按所属账户筛选
        if (!empty($filters['belongAccount'])) {
            $query->where('belong_account', 'like', '%' . $filters['belongAccount'] . '%');
        }

        // 按尺寸筛选
        if (!empty($filters['size'])) {
            $query->where('size', 'like', '%' . $filters['size'] . '%');
        }

        // 按时长筛选
        if (!empty($filters['duration'])) {
            $query->where('duration', 'like', '%' . $filters['duration'] . '%');
        }

        // 按形状筛选
        if (!empty($filters['shape'])) {
            $query->where('shape', 'like', '%' . $filters['shape'] . '%');
        }

        // 按格式筛选
        if (!empty($filters['format'])) {
            $query->where('format', 'like', '%' . $filters['format'] . '%');
        }

        // 按来源筛选
        if (!empty($filters['source'])) {
            $query->where('source', 'like', '%' . $filters['source'] . '%');
        }

        // 按素材备注筛选
        if (!empty($filters['materialNote'])) {
            $query->where('material_note', 'like', '%' . $filters['materialNote'] . '%');
        }

        // 按拒审信息筛选
        if (!empty($filters['rejectInfo'])) {
            $query->where('reject_info', 'like', '%' . $filters['rejectInfo'] . '%');
        }

        // 按创建时间起始筛选
        if (!empty($filters['createTimeStart'])) {
            $query->where('create_time', '>=', $filters['createTimeStart']);
        }

        // 按创建时间结束筛选
        if (!empty($filters['createTimeEnd'])) {
            $query->where('create_time', '<=', $filters['createTimeEnd']);
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
     * 格式化数据
     *
     * @param mixed $rows
     * @return array
     */
    private function formatData($rows): array
    {
        return $rows->map(function ($item) {
            return [
                'id' => (string) $item->id,
                'materialId' => (string) $item->material_id,
                'name' => $item->name,
                'channel' => $item->channel,
                'useAccount' => $item->use_account,
                'useAccountName' => $item->useAccount?->name ?? null,
                'belongAccount' => $item->belong_account,
                'belongAccountName' => $item->belongAccount?->name ?? null,
                'size' => $item->size,
                'duration' => $item->duration,
                'shape' => $item->shape,
                'format' => $item->format,
                'source' => $item->source,
                'rejectInfo' => $item->reject_info,
                'materialNote' => $item->material_note,
                'createTime' => $item->create_time?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }
}
