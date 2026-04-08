<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class XmpMaterialsController extends Controller
{
    private function authUserId(): ?int
    {
        $id = Auth::guard('sanctum')->id();
        if ($id !== null) return (int) $id;
        $fallback = Auth::id();
        return $fallback !== null ? (int) $fallback : null;
    }

    /**
     * Normalize query string values sent by the front-end.
     * - Treat "undefined"/"null" (case-insensitive) as empty.
     * - Trim whitespace.
     */
    private function normalizeQueryString($value): string
    {
        if (is_array($value)) return '';
        $s = trim((string) $value);
        if ($s === '') return '';
        $lower = strtolower($s);
        if ($lower === 'undefined' || $lower === 'null') return '';
        return $s;
    }

    /**
     * Normalize multi rating scores (e.g. [5,4.5] or "5,4.5") into numeric list.
     *
     * @return float[]
     */
    private function normalizeRatingScores($value): array
    {
        $arr = [];
        if (is_array($value)) {
            $arr = $value;
        } else {
            $s = trim((string) $value);
            if ($s !== '') {
                $arr = explode(',', $s);
            }
        }

        $out = [];
        foreach ($arr as $v) {
            if ($v === null) continue;
            $s = trim((string) $v);
            if ($s === '' || strtolower($s) === 'undefined' || strtolower($s) === 'null') continue;
            if (!is_numeric($s)) continue;
            $f = round((float) $s, 1);
            if ($f < 0.5 || $f > 5.0) continue;
            $out[] = $f;
        }

        // unique
        $out = array_values(array_unique($out));
        rsort($out);
        return $out;
    }

    /**
     * Normalize meta_tag ids (system tags) from query/body.
     *
     * @return int[]
     */
    private function normalizeMetaTagIds($value): array
    {
        $arr = [];
        if (is_array($value)) {
            $arr = $value;
        } else {
            $s = trim((string) $value);
            if ($s !== '') {
                $arr = explode(',', $s);
            }
        }

        $out = [];
        foreach ($arr as $v) {
            if ($v === null) continue;
            $s = trim((string) $v);
            if ($s === '' || strtolower($s) === 'undefined' || strtolower($s) === 'null') continue;
            if (!is_numeric($s)) continue;
            $out[] = (int) $s;
        }
        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    /**
     * Normalize integer list from query/body (array or comma string).
     *
     * @return int[]
     */
    private function normalizeIntList($value): array
    {
        $arr = [];
        if (is_array($value)) {
            $arr = $value;
        } else {
            $s = trim((string) $value);
            if ($s !== '') {
                $arr = explode(',', $s);
            }
        }

        $out = [];
        foreach ($arr as $v) {
            if ($v === null) continue;
            $s = trim((string) $v);
            if ($s === '' || strtolower($s) === 'undefined' || strtolower($s) === 'null') continue;
            if (!is_numeric($s)) continue;
            $out[] = (int) $s;
        }
        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    /**
     * material_type: regular/playable/0/1 or list.
     *
     * @return int[] values in [0,1]
     */
    private function normalizeMaterialTypes($value): array
    {
        $arr = [];
        if (is_array($value)) {
            $arr = $value;
        } else {
            $s = trim((string) $value);
            if ($s !== '') {
                $arr = explode(',', $s);
            }
        }

        $out = [];
        foreach ($arr as $v) {
            $s = strtolower(trim((string) $v));
            if ($s === '' || $s === 'undefined' || $s === 'null') continue;
            if ($s === 'regular' || $s === '0') $out[] = 0;
            if ($s === 'playable' || $s === '1') $out[] = 1;
        }
        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    /**
     * size_level: small/medium/large or list.
     *
     * @return string[]
     */
    private function normalizeSizeLevels($value): array
    {
        $arr = [];
        if (is_array($value)) {
            $arr = $value;
        } else {
            $s = trim((string) $value);
            if ($s !== '') {
                $arr = explode(',', $s);
            }
        }

        $allowed = ['small', 'medium', 'large'];
        $out = [];
        foreach ($arr as $v) {
            $s = strtolower(trim((string) $v));
            if (in_array($s, $allowed, true)) $out[] = $s;
        }
        $out = array_values(array_unique($out));
        return $out;
    }

    /**
     * Apply legacy meta_tag include/exclude filter to query.
     */
    private function applyLegacyMetaTagFilter($query, array $metaTagIds, string $mode)
    {
        if (empty($metaTagIds)) return $query;
        $mode = strtolower(trim($mode));
        if ($mode === 'exclude' || $mode === 'not' || $mode === 'not_in') {
            $query->where(function ($q) use ($metaTagIds) {
                $q->whereNull('m.meta_tag')->orWhereNotIn('m.meta_tag', $metaTagIds);
            });
        } else {
            // default include
            $query->whereIn('m.meta_tag', $metaTagIds);
        }
        return $query;
    }

    /**
     * Apply system_tag include/exclude filter to query using relation table.
     * system_tag_ids are `meta_system_tags.id` values.
     */
    private function applySystemTagFilter($query, array $systemTagIds, string $mode)
    {
        if (empty($systemTagIds)) return $query;
        $mode = strtolower(trim($mode));

        if ($mode === 'exclude' || $mode === 'not' || $mode === 'not_in') {
            $query->whereNotExists(function ($sub) use ($systemTagIds) {
                $sub->select(DB::raw(1))
                    ->from('meta_material_system_tags as mmst')
                    ->whereColumn('mmst.material_id', 'm.id')
                    ->whereIn('mmst.system_tag_id', $systemTagIds);
            });
        } else {
            // default include
            $query->whereExists(function ($sub) use ($systemTagIds) {
                $sub->select(DB::raw(1))
                    ->from('meta_material_system_tags as mmst')
                    ->whereColumn('mmst.material_id', 'm.id')
                    ->whereIn('mmst.system_tag_id', $systemTagIds);
            });
        }

        return $query;
    }

    private function applyRejectReasonOptionFilter($query, array $optionIds)
    {
        if (empty($optionIds)) return $query;

        $rows = collect();
        try {
            $hasTable = DB::getSchemaBuilder()->hasTable('meta_reject_reason_options');
            if ($hasTable) {
                $rows = DB::table('meta_reject_reason_options')
                    ->select(['id', 'channel_scope', 'reject_state'])
                    ->where('is_active', 1)
                    ->whereIn('id', $optionIds)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }
        } catch (\Throwable $e) {
            $rows = collect();
        }
        if ($rows->isEmpty()) return $query;

        $sourceLike = [
            'meta' => ['meta', 'facebook'],
            'google' => ['google'],
            'tiktok' => ['tiktok'],
            'mintegral' => ['mintegral'],
            'unity' => ['unity'],
        ];

        $query->where(function ($outer) use ($rows, $sourceLike) {
            foreach ($rows as $row) {
                $outer->orWhere(function ($q) use ($row, $sourceLike) {
                    $channelScope = strtolower(trim((string) ($row->channel_scope ?? 'all')));
                    $rejectState = (int) ($row->reject_state ?? 0); // 0=未拒审 1=有拒审记录

                    if ($channelScope !== 'all') {
                        $keywords = $sourceLike[$channelScope] ?? [$channelScope];
                        $q->where(function ($sq) use ($keywords) {
                            foreach ($keywords as $idx => $kw) {
                                if ($idx === 0) $sq->whereRaw('LOWER(COALESCE(m.source, "")) like ?', ['%' . strtolower($kw) . '%']);
                                else $sq->orWhereRaw('LOWER(COALESCE(m.source, "")) like ?', ['%' . strtolower($kw) . '%']);
                            }
                        });
                    }

                    if ($rejectState === 1) {
                        $q->whereNotNull('m.reject_reason')->where('m.reject_reason', '!=', '');
                    } else {
                        $q->where(function ($rq) {
                            $rq->whereNull('m.reject_reason')->orWhere('m.reject_reason', '');
                        });
                    }
                });
            }
        });

        return $query;
    }

    /**
     * 非视频资源：从已保存的本地文件读取像素尺寸（jpeg/png/gif/webp 等 PHP 可识别格式）。
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function readImageDimensionsFromPath(string $absolutePath): array
    {
        if ($absolutePath === '' || !is_readable($absolutePath)) {
            return [null, null];
        }
        $info = @getimagesize($absolutePath);
        if ($info === false || !isset($info[0], $info[1])) {
            return [null, null];
        }

        return [(int) $info[0], (int) $info[1]];
    }

    /**
     * 列表：支持 folder_id + include_subfolders（对应“显示子文件夹素材”）
     */
    public function index(Request $request)
    {
        $ownerId = $request->query('owner_id', $this->authUserId());
        $folderId = $request->query('folder_id');
        $includeSubfolders = (bool) $request->boolean('include_subfolders', false);
        $withStatistics = (bool) $request->boolean('with_statistics', false);
        $statisticsStartDate = $this->normalizeQueryString($request->query('statistics_start_date', ''));
        $statisticsEndDate = $this->normalizeQueryString($request->query('statistics_end_date', ''));
        if ($withStatistics) {
            if ($statisticsStartDate === '' || $statisticsEndDate === '') {
                // MVP：默认取最近 7 天（含当天）
                $statisticsEndDate = now()->toDateString();
                $statisticsStartDate = now()->subDays(6)->toDateString();
            }
        }

        $designerIds = $this->normalizeIntList($request->query('designer_id', []));
        $creatorIds = $this->normalizeIntList($request->query('creator_id', []));

        $globalSearch = $this->normalizeQueryString($request->query('global_search', ''));

        $materialTypes = $this->normalizeMaterialTypes($request->query('material_type', []));

        $source = $this->normalizeQueryString($request->query('source', ''));
        $materialGroupId = $this->normalizeQueryString($request->query('material_group_id', ''));
        $sizeLevels = $this->normalizeSizeLevels($request->query('size_level', []));
        $createStartDate = $this->normalizeQueryString($request->query('create_start_date', ''));
        $createEndDate = $this->normalizeQueryString($request->query('create_end_date', ''));
        $ratingScores = $this->normalizeRatingScores($request->query('rating_scores', $request->query('rating', [])));
        $systemTagIds = $this->normalizeMetaTagIds($request->query('system_tag_ids', $request->query('system_tag_id', [])));
        $systemTagMode = $this->normalizeQueryString($request->query('system_tag_mode', 'include'));
        // legacy fallback (old protocol): meta_tag_ids/meta_tag_mode
        $legacyMetaTagIds = $this->normalizeMetaTagIds($request->query('meta_tag_ids', $request->query('meta_tag_id', [])));
        $legacyMetaTagMode = $this->normalizeQueryString($request->query('meta_tag_mode', 'include'));
        $auditStatusRaw = $this->normalizeQueryString($request->query('audit_status', ''));
        $auditStatus = null;
        if ($auditStatusRaw !== null && $auditStatusRaw !== '') {
            if (is_numeric($auditStatusRaw)) {
                $auditStatus = (int) $auditStatusRaw;
            } else {
                $auditStatusStr = strtolower((string) $auditStatusRaw);
                // 允许用文字做兼容
                if (in_array($auditStatusStr, ['pending', 'rejected', 'approved'], true)) {
                    $auditStatus = $auditStatusStr === 'pending' ? 0 : ($auditStatusStr === 'approved' ? 1 : 2);
                }
            }
        }

        $rejectReason = $this->normalizeQueryString($request->query('reject_reason', ''));
        $rejectReasonOptionIds = $this->normalizeIntList(
            $request->query('reject_reason_option_ids', $request->query('reject_reason_options', []))
        );

        $tagIdsRaw = $request->query('tag_ids', $request->query('tag_id'));
        $tagIds = [];
        if (is_string($tagIdsRaw)) {
            $tagIdsRaw = $this->normalizeQueryString($tagIdsRaw);
        } elseif (is_array($tagIdsRaw)) {
            $tagIds = $tagIdsRaw;
        }

        if (is_string($tagIdsRaw) && $tagIdsRaw !== '') {
            $tagIds = array_values(array_filter(explode(',', $tagIdsRaw)));
        }

        $pageNo = (int) $request->query('pageNo', $request->query('page', 1));
        $pageSize = (int) $request->query('pageSize', 20);
        if ($pageNo < 1) {
            $pageNo = 1;
        }
        if ($pageSize < 1) {
            $pageSize = 20;
        }

        // 排序：仅允许少量字段以避免 SQL 注入/不可排序字段
        $sortField = (string) $request->query('sortField', 'create_time');
        $sortOrder = strtolower((string) $request->query('sortOrder', 'desc'));
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';
        $allowedSortFields = ['create_time', 'material_name', 'local_id', 'file_format', 'material_type'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'create_time';
        }

        if ($folderId === null || $folderId === '') {
            return response()->json(['data' => [], 'totalCount' => 0]);
        }

        // favorites 虚拟入口：前端传 folder_id='favorites'
        if ((string) $folderId === 'favorites') {
            return $this->favorites($request);
        }

        // 列表策略（与前端预期对齐）：
        // - 默认：展示“当前文件夹的直接子文件夹 + 当前文件夹下的素材”
        // - include_subfolders=1：展示“所有子孙文件夹 + 所有子孙素材”
        // - 分页只作用于“素材”；文件夹仅在第 1 页返回（避免文件夹数量挤占分页导致整页无素材）

        $baseQuery = DB::table('meta_materials as m')
            ->whereNull('m.deleted_at');

        // 权限/归属（通过文件夹 owner_id 约束，确保同租户内可见）
        if ($ownerId !== null && $ownerId !== '') {
            $baseQuery->join('meta_folders as f', 'f.id', '=', 'm.folder_id')
                ->whereNull('f.deleted_at')
                ->where('f.owner_id', $ownerId);
        }

        // 先计算“需要展示的 folder 范围”和“需要展示的 material 范围”
        $folderTotalCount = 0;
        $folderRows = collect();

        // include_subfolders=1 时：文件夹展示“所有子孙文件夹”；否则仅展示“直接子文件夹”
        $folderIdInt = (int) $folderId;
        $descendantFolderIds = collect();
        $hasDescendantScope = false;
        if ($includeSubfolders) {
            $selectedPath = DB::table('meta_folders')
                ->whereNull('deleted_at')
                ->where('id', $folderId)
                ->value('folder_path');

            if ($selectedPath) {
                $hasDescendantScope = true;
                $descendantFolderIds = DB::table('meta_folders')
                    ->whereNull('deleted_at')
                    ->when($ownerId !== null && $ownerId !== '', function ($q) use ($ownerId) {
                        $q->where('owner_id', $ownerId);
                    })
                    ->where(function ($q) use ($selectedPath) {
                        $q->where('folder_path', $selectedPath)
                            ->orWhere('folder_path', 'like', $selectedPath . '/%');
                    })
                    ->pluck('id');
            }
        }

        if ($pageNo === 1) {
            $folderBaseQuery = DB::table('meta_folders')
                ->whereNull('deleted_at');

            if ($includeSubfolders && $hasDescendantScope) {
                // 展示所有子孙（排除自己）
                $ids = $descendantFolderIds->filter(function ($x) use ($folderIdInt) {
                    return (int) $x !== (int) $folderIdInt;
                });
                $folderBaseQuery->whereIn('id', $ids->all());
            } else {
                // 仅展示直接 children
                $folderBaseQuery->where('parent_id', $folderIdInt);
            }

            if ($ownerId !== null && $ownerId !== '') {
                $folderBaseQuery->where('owner_id', $ownerId);
            }

            // 全局搜索：子文件夹行也按文件夹名/路径过滤（与占位文案「文件夹」一致）
            if ($globalSearch !== '') {
                $like = '%' . $globalSearch . '%';
                $folderBaseQuery->where(function ($q) use ($like) {
                    $q->where('folder_name', 'like', $like)
                        ->orWhere('folder_path', 'like', $like);
                });
            }

            $folderTotalCount = (clone $folderBaseQuery)->count();
            // 文件夹不分页（只第1页返回全量，避免素材分页被挤占）
            $folderRows = (clone $folderBaseQuery)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'asc')
                ->get([
                    'id',
                    'folder_name',
                    'parent_id',
                    'create_time',
                ]);
        }

        // 素材分页（始终按 pageNo/pageSize）
        $materialsSkip = max(0, ($pageNo - 1) * $pageSize);
        $materialsTake = $pageSize;

        // material folder 过滤
        if ($includeSubfolders) {
            if ($hasDescendantScope && !$descendantFolderIds->isEmpty()) {
                $baseQuery->whereIn('m.folder_id', $descendantFolderIds->all());
            } else {
                $baseQuery->where('m.folder_id', $folderId);
            }
        } else {
            $baseQuery->where('m.folder_id', $folderId);
        }

        if (!empty($designerIds)) {
            $baseQuery->whereIn('m.designer_id', $designerIds);
        }

        if (!empty($creatorIds)) {
            $baseQuery->whereIn('m.creator_id', $creatorIds);
        }

        if (!empty($materialTypes)) {
            $baseQuery->whereIn('m.material_type', $materialTypes);
        }
        if ($materialGroupId !== '') {
            $baseQuery->where('m.material_group_id', (int) $materialGroupId);
        }
        if (!empty($sizeLevels)) {
            $baseQuery->where(function ($q) use ($sizeLevels) {
                foreach ($sizeLevels as $level) {
                    if ($level === 'small') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) <= 720');
                        });
                    } elseif ($level === 'medium') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) > 720')
                                ->whereRaw('GREATEST(m.width, m.height) <= 1920');
                        });
                    } elseif ($level === 'large') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) > 1920');
                        });
                    }
                }
            });
        }

        if ($source !== '') {
            $baseQuery->where('m.source', 'like', '%' . $source . '%');
        }

        if ($auditStatus !== null) {
            if (in_array($auditStatus, [0, 1, 2], true)) {
                $baseQuery->where('m.audit_status', $auditStatus);
            }
        }

        if (!empty($rejectReasonOptionIds)) {
            $this->applyRejectReasonOptionFilter($baseQuery, $rejectReasonOptionIds);
        } elseif ($rejectReason !== '') {
            if ($rejectReason === '__HAS_VALUE__') {
                $baseQuery->whereNotNull('m.reject_reason')->where('m.reject_reason', '!=', '');
            } elseif ($rejectReason === '__EMPTY__') {
                $baseQuery->where(function ($q) {
                    $q->whereNull('m.reject_reason')->orWhere('m.reject_reason', '');
                });
            } else {
                $baseQuery->where('m.reject_reason', 'like', '%' . $rejectReason . '%');
            }
        }
        if ($createStartDate !== '') {
            $baseQuery->whereDate('m.create_time', '>=', $createStartDate);
        }
        if ($createEndDate !== '') {
            $baseQuery->whereDate('m.create_time', '<=', $createEndDate);
        }

        if (!empty($ratingScores)) {
            $baseQuery->whereIn('m.rating', $ratingScores);
        }

        if (!empty($systemTagIds)) {
            $this->applySystemTagFilter($baseQuery, $systemTagIds, $systemTagMode);
        } else {
            $this->applyLegacyMetaTagFilter($baseQuery, $legacyMetaTagIds, $legacyMetaTagMode);
        }

        if (!empty($tagIds)) {
            $baseQuery->whereIn('m.id', function ($q) use ($tagIds) {
                $q->select('material_id')
                    ->from('meta_material_tags')
                    ->whereIn('tag_id', $tagIds);
            });
        }

        if ($globalSearch !== '') {
            $like = '%' . $globalSearch . '%';
            // 名称 / Local ID / 备注 / 所属文件夹名与路径（meta_materials 无 name 列，勿用 m.name）
            $baseQuery->where(function ($q) use ($like, $ownerId) {
                $q->where('m.material_name', 'like', $like)
                    ->orWhere('m.local_id', 'like', $like)
                    ->orWhere('m.remarks', 'like', $like)
                    ->orWhereExists(function ($sub) use ($like, $ownerId) {
                        $sub->select(DB::raw(1))
                            ->from('meta_folders as sf')
                            ->whereColumn('sf.id', 'm.folder_id')
                            ->whereNull('sf.deleted_at')
                            ->where(function ($inner) use ($like) {
                                $inner->where('sf.folder_name', 'like', $like)
                                    ->orWhere('sf.folder_path', 'like', $like);
                            });
                        if ($ownerId !== null && $ownerId !== '') {
                            $sub->where('sf.owner_id', $ownerId);
                        }
                    });
            });
        }

        $baseQuery->select([
            'm.id',
            'm.local_id',
            'm.material_name',
            'm.file_url',
            'm.thumbnail_url',
            'm.file_format',
            'm.width',
            'm.height',
            'm.rating',
            'm.material_type',
            'm.designer_id',
            'm.creator_id',
            'm.source',
            'm.audit_status',
            'm.reject_reason',
            'm.folder_id',
            'm.remarks',
            'm.meta_tag',
            'm.material_group_id',
            'm.mindworks_locked',
            'm.create_time',
        ]);

        // totalCount：用于前端分页（只统计素材，避免“文件夹数量”影响分页）
        $materialTotalCount = (clone $baseQuery)->count();
        $totalCount = $materialTotalCount;

        $rows = collect();
        if ($materialsTake > 0) {
            $rows = $baseQuery
                ->orderBy('m.' . $sortField, $sortOrder)
                ->orderBy('m.id', 'desc')
                ->skip($materialsSkip)
                ->take($materialsTake)
                ->get();
        }

        $materialIds = $rows->pluck('id');

        $tagsMap = [];
        if (!$materialIds->isEmpty()) {
            $tagRows = DB::table('meta_material_tags as mt')
                ->join('meta_tags as t', 't.id', '=', 'mt.tag_id')
                ->whereIn('mt.material_id', $materialIds->all())
                ->select(['mt.material_id', 't.name as tag_name'])
                ->get();

            foreach ($tagRows as $tr) {
                $mid = (string) $tr->material_id;
                if (!isset($tagsMap[$mid])) {
                    $tagsMap[$mid] = [];
                }
                $tagsMap[$mid][] = $tr->tag_name;
            }
        }

        // 投放数据统计（MVP：按统计起止日期聚合）
        $statsMap = [];
        if ($withStatistics && !$materialIds->isEmpty()) {
            $statRows = DB::table('meta_material_statistics as st')
                ->whereIn('st.material_id', $materialIds->all())
                ->where('st.statistics_date', '<=', $statisticsEndDate)
                ->where(function ($q) use ($statisticsStartDate) {
                    $q->whereNull('st.statistics_end_date')
                        ->orWhere('st.statistics_end_date', '>=', $statisticsStartDate);
                })
                ->select([
                    'st.material_id',
                    DB::raw('SUM(COALESCE(st.production_cost,0)) as production_cost_sum'),
                    DB::raw('SUM(COALESCE(st.spend,0)) as spend_sum'),
                    DB::raw('SUM(COALESCE(st.impressions,0)) as impressions_sum'),
                    DB::raw('SUM(COALESCE(st.clicks,0)) as clicks_sum'),
                    DB::raw('SUM(COALESCE(st.conversions,0)) as conversions_sum'),
                ])
                ->groupBy('st.material_id')
                ->get();

            foreach ($statRows as $sr) {
                $statsMap[(string) $sr->material_id] = [
                    'productionCost' => (float) $sr->production_cost_sum,
                    'spend' => (float) $sr->spend_sum,
                    'impressions' => (int) $sr->impressions_sum,
                    'clicks' => (int) $sr->clicks_sum,
                    'conversions' => (int) $sr->conversions_sum,
                ];
            }
        }

        $data = $rows->map(function ($m) use ($tagsMap, $withStatistics, $statsMap) {
            $format = strtolower((string) ($m->file_format ?? ''));
            $type = in_array($format, ['mp4', 'webm', 'mov', 'qt', 'ogg'], true) ? 'video' : 'image';

            $stat = $withStatistics ? ($statsMap[(string) $m->id] ?? null) : null;
            $productionCost = $stat['productionCost'] ?? 0.0;
            $spend = $stat['spend'] ?? 0.0;
            $impressions = $stat['impressions'] ?? 0;
            $clicks = $stat['clicks'] ?? 0;
            $conversions = $stat['conversions'] ?? 0;
            $cpm = $impressions > 0 ? $spend / ($impressions / 1000) : 0;
            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            $clickCost = $clicks > 0 ? $spend / $clicks : 0;
            $conversionCost = $conversions > 0 ? $spend / $conversions : 0;

            return [
                'id' => (string) $m->id,
                'type' => $type,
                'name' => $m->material_name,
                'localId' => $m->local_id,
                'tags' => $tagsMap[(string) $m->id] ?? [],
                'thumbnail' => $m->thumbnail_url ?: $m->file_url,
                'width' => $m->width !== null && $m->width !== '' ? (int) $m->width : null,
                'height' => $m->height !== null && $m->height !== '' ? (int) $m->height : null,
                // 统计字段：with_statistics 时回填聚合结果
                // 兼容旧字段：cost 表示花费(spend)
                'cost' => $withStatistics ? $spend : '-',
                'productionCost' => $withStatistics ? $productionCost : '-',
                'spend' => $withStatistics ? $spend : '-',
                'impressions' => $withStatistics ? $impressions : '-',
                'cpm' => $withStatistics ? $cpm : '-',
                'clicks' => $withStatistics ? $clicks : '-',
                'clickCost' => $withStatistics ? $clickCost : '-',
                'ctr' => $withStatistics ? $ctr : '-',
                'conversions' => $withStatistics ? $conversions : '-',
                'conversionCost' => $withStatistics ? $conversionCost : '-',
                'materialCount' => 0,
                'designerId' => $m->designer_id,
                'creatorId' => $m->creator_id,
                'folderId' => $m->folder_id,
                'createTime' => $m->create_time,
                'auditStatus' => $m->audit_status,
                'rejectReason' => $m->reject_reason,
                'source' => $m->source,
                'remarks' => $m->remarks,
                'xmpTag' => $m->meta_tag,
                'materialGroupId' => $m->material_group_id,
                'mindworksLocked' => (int) ($m->mindworks_locked ?? 0),
            ];
        });

        // 文件夹行统计：子文件夹数量、直接素材数量（用于表格“素材”列展示）
        $folderSubfolderCountById = [];
        $folderMaterialCountById = [];
        try {
            $folderIds = $folderRows->pluck('id')
                ->filter()
                ->map(function ($x) {
                    return (int) $x;
                })
                ->values()
                ->all();

            if (!empty($folderIds)) {
                $subfolderCounts = DB::table('meta_folders')
                    ->whereNull('deleted_at')
                    ->whereIn('parent_id', $folderIds)
                    ->when($ownerId !== null && $ownerId !== '', function ($q) use ($ownerId) {
                        $q->where('owner_id', $ownerId);
                    })
                    ->groupBy('parent_id')
                    ->selectRaw('parent_id, COUNT(*) as cnt')
                    ->get();
                foreach ($subfolderCounts as $row) {
                    $folderSubfolderCountById[(string) $row->parent_id] = (int) $row->cnt;
                }

                $materialCounts = DB::table('meta_materials')
                    ->whereNull('deleted_at')
                    ->whereIn('folder_id', $folderIds)
                    ->groupBy('folder_id')
                    ->selectRaw('folder_id, COUNT(*) as cnt')
                    ->get();
                foreach ($materialCounts as $row) {
                    $folderMaterialCountById[(string) $row->folder_id] = (int) $row->cnt;
                }
            }
        } catch (\Throwable $e) {
            // count 失败不影响列表主流程
        }

        // 文件夹行（用于表格“素材/文件夹”混合展示）
        $folderData = $folderRows->map(function ($f) use ($folderSubfolderCountById, $folderMaterialCountById) {
            $fid = (string) $f->id;
            return [
                // 与素材表的 id 可能重叠，避免表格 row-key 冲突
                'id' => 'folder-' . (string) $f->id,
                'type' => 'folder',
                'name' => $f->folder_name,
                'localId' => null,
                'tags' => [],
                'thumbnail' => null,
                'width' => null,
                'height' => null,
                'subfolderCount' => (int) ($folderSubfolderCountById[$fid] ?? 0),
                'folderMaterialCount' => (int) ($folderMaterialCountById[$fid] ?? 0),
                'cost' => '-',
                'productionCost' => '-',
                'spend' => '-',
                'impressions' => '-',
                'cpm' => '-',
                'clicks' => '-',
                'clickCost' => '-',
                'ctr' => '-',
                'conversions' => '-',
                'conversionCost' => '-',
                'materialCount' => 0,
                'designerId' => null,
                'creatorId' => null,
                'folderId' => (int) $f->parent_id,
                'folderSelfId' => (string) $f->id,
                'createTime' => $f->create_time,
                'auditStatus' => null,
                'rejectReason' => null,
                'source' => null,
                'remarks' => null,
                'xmpTag' => null,
                'materialGroupId' => null,
                'mindworksLocked' => 0,
            ];
        });

        $combinedData = $folderData->concat($data);

        return response()->json([
            'data' => $combinedData->values(),
            'totalCount' => $totalCount,
            'folderCount' => $folderTotalCount,
        ]);
    }

    /**
     * favorites 列表：仅返回当前用户收藏素材
     */
    public function favorites(Request $request)
    {
        $ownerId = $request->query('owner_id', $this->authUserId());
        $withStatistics = (bool) $request->boolean('with_statistics', false);
        $statisticsStartDate = $this->normalizeQueryString($request->query('statistics_start_date', ''));
        $statisticsEndDate = $this->normalizeQueryString($request->query('statistics_end_date', ''));
        if ($withStatistics) {
            if ($statisticsStartDate === '' || $statisticsEndDate === '') {
                $statisticsEndDate = now()->toDateString();
                $statisticsStartDate = now()->subDays(6)->toDateString();
            }
        }

        $designerIds = $this->normalizeIntList($request->query('designer_id', []));
        $creatorIds = $this->normalizeIntList($request->query('creator_id', []));
        $globalSearch = $this->normalizeQueryString($request->query('global_search', ''));

        $materialTypes = $this->normalizeMaterialTypes($request->query('material_type', []));

        $source = $this->normalizeQueryString($request->query('source', ''));
        $materialGroupId = $this->normalizeQueryString($request->query('material_group_id', ''));
        $sizeLevels = $this->normalizeSizeLevels($request->query('size_level', []));
        $createStartDate = $this->normalizeQueryString($request->query('create_start_date', ''));
        $createEndDate = $this->normalizeQueryString($request->query('create_end_date', ''));
        $ratingScores = $this->normalizeRatingScores($request->query('rating_scores', $request->query('rating', [])));
        $systemTagIds = $this->normalizeMetaTagIds($request->query('system_tag_ids', $request->query('system_tag_id', [])));
        $systemTagMode = $this->normalizeQueryString($request->query('system_tag_mode', 'include'));
        $legacyMetaTagIds = $this->normalizeMetaTagIds($request->query('meta_tag_ids', $request->query('meta_tag_id', [])));
        $legacyMetaTagMode = $this->normalizeQueryString($request->query('meta_tag_mode', 'include'));
        $auditStatusRaw = $this->normalizeQueryString($request->query('audit_status', ''));
        $auditStatus = null;
        if ($auditStatusRaw !== null && $auditStatusRaw !== '') {
            if (is_numeric($auditStatusRaw)) {
                $auditStatus = (int) $auditStatusRaw;
            } else {
                $auditStatusStr = strtolower((string) $auditStatusRaw);
                if (in_array($auditStatusStr, ['pending', 'rejected', 'approved'], true)) {
                    $auditStatus = $auditStatusStr === 'pending' ? 0 : ($auditStatusStr === 'approved' ? 1 : 2);
                }
            }
        }

        $rejectReason = $this->normalizeQueryString($request->query('reject_reason', ''));
        $rejectReasonOptionIds = $this->normalizeIntList(
            $request->query('reject_reason_option_ids', $request->query('reject_reason_options', []))
        );

        $tagIdsRaw = $request->query('tag_ids', $request->query('tag_id'));
        $tagIds = [];
        if (is_string($tagIdsRaw)) {
            $tagIdsRaw = $this->normalizeQueryString($tagIdsRaw);
        } elseif (is_array($tagIdsRaw)) {
            $tagIds = $tagIdsRaw;
        }

        if (is_string($tagIdsRaw) && $tagIdsRaw !== '') {
            $tagIds = array_values(array_filter(explode(',', $tagIdsRaw)));
        }

        $pageNo = (int) $request->query('pageNo', $request->query('page', 1));
        $pageSize = (int) $request->query('pageSize', 20);
        if ($pageNo < 1) {
            $pageNo = 1;
        }
        if ($pageSize < 1) {
            $pageSize = 20;
        }

        $baseQuery = DB::table('meta_material_favorites as fav')
            ->join('meta_materials as m', 'm.id', '=', 'fav.material_id')
            ->join('meta_folders as f', 'f.id', '=', 'm.folder_id')
            ->whereNull('m.deleted_at')
            ->whereNull('f.deleted_at')
            ->where('fav.owner_id', $ownerId)
            ->select([
                'm.id',
                'm.local_id',
                'm.material_name',
                'm.file_url',
                'm.thumbnail_url',
                'm.file_format',
                'm.width',
                'm.height',
                'm.rating',
                'm.material_type',
                'm.designer_id',
                'm.creator_id',
                'm.folder_id',
                'm.source',
                'm.remarks',
                'm.meta_tag',
                'm.material_group_id',
                'm.mindworks_locked',
                'm.audit_status',
                'm.reject_reason',
                'm.create_time',
            ]);

        if (!empty($designerIds)) {
            $baseQuery->whereIn('m.designer_id', $designerIds);
        }

        if (!empty($creatorIds)) {
            $baseQuery->whereIn('m.creator_id', $creatorIds);
        }

        if (!empty($materialTypes)) {
            $baseQuery->whereIn('m.material_type', $materialTypes);
        }
        if ($materialGroupId !== '') {
            $baseQuery->where('m.material_group_id', (int) $materialGroupId);
        }
        if (!empty($sizeLevels)) {
            $baseQuery->where(function ($q) use ($sizeLevels) {
                foreach ($sizeLevels as $level) {
                    if ($level === 'small') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) <= 720');
                        });
                    } elseif ($level === 'medium') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) > 720')
                                ->whereRaw('GREATEST(m.width, m.height) <= 1920');
                        });
                    } elseif ($level === 'large') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) > 1920');
                        });
                    }
                }
            });
        }

        if ($source !== '') {
            $baseQuery->where('m.source', 'like', '%' . $source . '%');
        }

        if ($auditStatus !== null) {
            if (in_array($auditStatus, [0, 1, 2], true)) {
                $baseQuery->where('m.audit_status', $auditStatus);
            }
        }

        if (!empty($rejectReasonOptionIds)) {
            $this->applyRejectReasonOptionFilter($baseQuery, $rejectReasonOptionIds);
        } elseif ($rejectReason !== '') {
            if ($rejectReason === '__HAS_VALUE__') {
                $baseQuery->whereNotNull('m.reject_reason')->where('m.reject_reason', '!=', '');
            } elseif ($rejectReason === '__EMPTY__') {
                $baseQuery->where(function ($q) {
                    $q->whereNull('m.reject_reason')->orWhere('m.reject_reason', '');
                });
            } else {
                $baseQuery->where('m.reject_reason', 'like', '%' . $rejectReason . '%');
            }
        }
        if ($createStartDate !== '') {
            $baseQuery->whereDate('m.create_time', '>=', $createStartDate);
        }
        if ($createEndDate !== '') {
            $baseQuery->whereDate('m.create_time', '<=', $createEndDate);
        }

        if (!empty($ratingScores)) {
            $baseQuery->whereIn('m.rating', $ratingScores);
        }

        if (!empty($systemTagIds)) {
            $this->applySystemTagFilter($baseQuery, $systemTagIds, $systemTagMode);
        } else {
            $this->applyLegacyMetaTagFilter($baseQuery, $legacyMetaTagIds, $legacyMetaTagMode);
        }

        if (!empty($tagIds)) {
            $baseQuery->whereIn('m.id', function ($q) use ($tagIds) {
                $q->select('material_id')
                    ->from('meta_material_tags')
                    ->whereIn('tag_id', $tagIds);
            });
        }

        if ($globalSearch !== '') {
            $like = '%' . $globalSearch . '%';
            $baseQuery->where(function ($q) use ($like) {
                $q->where('m.material_name', 'like', $like)
                    ->orWhere('m.local_id', 'like', $like)
                    ->orWhere('m.remarks', 'like', $like)
                    ->orWhere('f.folder_name', 'like', $like)
                    ->orWhere('f.folder_path', 'like', $like);
            });
        }

        $totalCount = (clone $baseQuery)->count();

        $rows = $baseQuery
            ->orderBy('m.create_time', 'desc')
            ->skip(($pageNo - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        $materialIds = $rows->pluck('id');
        $tagsMap = [];
        if (!$materialIds->isEmpty()) {
            $tagRows = DB::table('meta_material_tags as mt')
                ->join('meta_tags as t', 't.id', '=', 'mt.tag_id')
                ->whereIn('mt.material_id', $materialIds->all())
                ->select(['mt.material_id', 't.name as tag_name'])
                ->get();

            foreach ($tagRows as $tr) {
                $mid = (string) $tr->material_id;
                if (!isset($tagsMap[$mid])) {
                    $tagsMap[$mid] = [];
                }
                $tagsMap[$mid][] = $tr->tag_name;
            }
        }

        // 投放数据统计（MVP：按统计起止日期聚合）
        $statsMap = [];
        if ($withStatistics && !$materialIds->isEmpty()) {
            $statRows = DB::table('meta_material_statistics as st')
                ->whereIn('st.material_id', $materialIds->all())
                ->where('st.statistics_date', '<=', $statisticsEndDate)
                ->where(function ($q) use ($statisticsStartDate) {
                    $q->whereNull('st.statistics_end_date')
                        ->orWhere('st.statistics_end_date', '>=', $statisticsStartDate);
                })
                ->select([
                    'st.material_id',
                    DB::raw('SUM(COALESCE(st.production_cost,0)) as production_cost_sum'),
                    DB::raw('SUM(COALESCE(st.spend,0)) as spend_sum'),
                    DB::raw('SUM(COALESCE(st.impressions,0)) as impressions_sum'),
                    DB::raw('SUM(COALESCE(st.clicks,0)) as clicks_sum'),
                    DB::raw('SUM(COALESCE(st.conversions,0)) as conversions_sum'),
                ])
                ->groupBy('st.material_id')
                ->get();

            foreach ($statRows as $sr) {
                $statsMap[(string) $sr->material_id] = [
                    'productionCost' => (float) $sr->production_cost_sum,
                    'spend' => (float) $sr->spend_sum,
                    'impressions' => (int) $sr->impressions_sum,
                    'clicks' => (int) $sr->clicks_sum,
                    'conversions' => (int) $sr->conversions_sum,
                ];
            }
        }

        $data = $rows->map(function ($m) use ($tagsMap, $withStatistics, $statsMap) {
            $format = strtolower((string) ($m->file_format ?? ''));
            $type = in_array($format, ['mp4', 'webm', 'mov', 'qt', 'ogg'], true) ? 'video' : 'image';

            $stat = $withStatistics ? ($statsMap[(string) $m->id] ?? null) : null;
            $productionCost = $stat['productionCost'] ?? 0.0;
            $spend = $stat['spend'] ?? 0.0;
            $impressions = $stat['impressions'] ?? 0;
            $clicks = $stat['clicks'] ?? 0;
            $conversions = $stat['conversions'] ?? 0;
            $cpm = $impressions > 0 ? $spend / ($impressions / 1000) : 0;
            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            $clickCost = $clicks > 0 ? $spend / $clicks : 0;
            $conversionCost = $conversions > 0 ? $spend / $conversions : 0;

            return [
                'id' => (string) $m->id,
                'type' => $type,
                'name' => $m->material_name,
                'localId' => $m->local_id,
                'tags' => $tagsMap[(string) $m->id] ?? [],
                'thumbnail' => $m->thumbnail_url ?: $m->file_url,
                'width' => $m->width !== null && $m->width !== '' ? (int) $m->width : null,
                'height' => $m->height !== null && $m->height !== '' ? (int) $m->height : null,
                // 兼容旧字段：cost 表示花费(spend)
                'cost' => $withStatistics ? $spend : '-',
                'productionCost' => $withStatistics ? $productionCost : '-',
                'spend' => $withStatistics ? $spend : '-',
                'impressions' => $withStatistics ? $impressions : '-',
                'cpm' => $withStatistics ? $cpm : '-',
                'clicks' => $withStatistics ? $clicks : '-',
                'clickCost' => $withStatistics ? $clickCost : '-',
                'ctr' => $withStatistics ? $ctr : '-',
                'conversions' => $withStatistics ? $conversions : '-',
                'conversionCost' => $withStatistics ? $conversionCost : '-',
                'materialCount' => 0,
                'designerId' => $m->designer_id,
                'creatorId' => $m->creator_id,
                'folderId' => $m->folder_id,
                'createTime' => $m->create_time,
                'auditStatus' => $m->audit_status,
                'rejectReason' => $m->reject_reason,
                'source' => $m->source,
                'remarks' => $m->remarks,
                'xmpTag' => $m->meta_tag,
                'materialGroupId' => $m->material_group_id,
                'mindworksLocked' => (int) ($m->mindworks_locked ?? 0),
            ];
        });

        return response()->json([
            'data' => $data,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * 上传素材（MVP：多文件上传 + 统一落库元信息 + 可选标签/设计师/创意人）
     *
     * 路径：POST /material-library/materials/upload
     *
     * 支持字段（multipart/form-data）：
     * - folder_id（必填）
     * - material_type：regular|playable 或 0|1
     * - tag_mode：unified|smart（MVP：smart 先忽略）
     * - tag_ids[]（标签 ID；可空）
     * - designer_mode：unified|smart（MVP：smart 先忽略）
     * - designer_id
     * - creator_mode：unified|smart（MVP：smart 先忽略）
     * - creator_id
     * - batch_prefix（可选：用于生成 local_id / batch_code）
     * - material_group_id（可选）
     * - filter_duplicate（可选：MVP 不做哈希去重，仅保留字段）
     * - files[]（上传文件，多文件）
     *
     * 文件落盘：使用 public 磁盘，实际路径为 storage/app/public/xmp_materials/...（不是 public/xmp_materials）。
     * 浏览器通过 /storage/... 访问需执行：php artisan storage:link（public/storage -> storage/app/public）。
     */
    public function upload(Request $request)
    {
        $folderId = $request->input('folder_id');
        if ($folderId === null || $folderId === '' || (string)$folderId === 'favorites') {
            return response()->json(['message' => 'folder_id is required'], 422);
        }

        $materialTypeRaw = $request->input('material_type', 'regular');
        $materialType = 0;
        if ($materialTypeRaw === 'playable' || (string) $materialTypeRaw === '1') {
            $materialType = 1;
        }

        $tagMode = $request->input('tag_mode', 'unified');
        $designerMode = $request->input('designer_mode', 'unified');
        $creatorMode = $request->input('creator_mode', 'unified');

        $tagIds = $request->input('tag_ids', []);
        if (!is_array($tagIds)) {
            $tagIds = $tagIds === null || $tagIds === '' ? [] : [$tagIds];
        }

        $tagIds = ($tagMode === 'unified') ? $tagIds : [];
        $designerId = ($designerMode === 'unified') ? $request->input('designer_id') : null;
        $creatorId = ($creatorMode === 'unified') ? $request->input('creator_id') : null;

        $materialGroupId = $request->input('material_group_id');
        $materialGroupIds = $request->input('material_group_ids', []);
        if (!is_array($materialGroupIds)) {
            $materialGroupIds = $materialGroupIds === null || $materialGroupIds === '' ? [] : [$materialGroupIds];
        }
        if (($materialGroupId === null || $materialGroupId === '') && !empty($materialGroupIds)) {
            $materialGroupId = $materialGroupIds[0];
        }
        $batchPrefix = trim((string) $request->input('batch_prefix', ''));
        $filterDuplicate = (bool) $request->boolean('filter_duplicate', false);
        // MVP：目前不做真正去重（缺少 hash 列）。参数只用于后续扩展。
        unset($filterDuplicate);

        /** @var \Illuminate\Http\UploadedFile[]|null $files */
        // 前端使用 multipart 字段名 files[]，Laravel 中应通过 file('files') 读取
        $files = $request->file('files');
        $files = is_array($files) ? array_values(array_filter($files)) : [];
        if (empty($files)) {
            return response()->json(['message' => 'files is required'], 422);
        }

        $uploadMode = $request->input('upload_mode', 'file');
        $relativePaths = $request->input('relative_paths', []);
        if (!is_array($relativePaths)) {
            $relativePaths = [];
        }

        // 单次点击上传限制：最多 100 条素材，其中视频最多 30 条
        $totalCount = count($files);
        if ($totalCount > 100) {
            return response()->json(['message' => 'single upload allows at most 100 files'], 422);
        }
        $videoExts = ['mp4', 'mov', 'webm', 'm4v', 'avi', 'mkv'];
        $videoCount = 0;
        foreach ($files as $f) {
            if (!$f) continue;
            $ext = strtolower((string) $f->getClientOriginalExtension());
            if (in_array($ext, $videoExts, true)) {
                $videoCount++;
            }
        }
        if ($videoCount > 30) {
            return response()->json(['message' => 'single upload allows at most 30 video files'], 422);
        }

        $now = now();

        // 生成 batch_code（批次唯一），并用于 upload_batch_id 关联
        $dateStr = $now->format('Ymd');
        $timeStr = $now->format('His');
        $batchToken = $now->format('YmdHisv') . strtoupper(bin2hex(random_bytes(3)));
        $batchCodeTemplate = $batchPrefix !== ''
            ? str_replace(
                ['{date}', '{time}', '{batch}'],
                [$dateStr, $timeStr, $batchToken],
                $batchPrefix
            )
            : ('batch_' . $batchToken);

        $sanitizeCode = static function ($value) {
            $value = preg_replace('/\\s+/', '', (string) $value);
            return mb_substr((string) $value, 0, 50);
        };

        $batchCodeBase = $sanitizeCode($batchCodeTemplate);
        $batchCode = $batchCodeBase;
        $retry = 0;
        while (
            DB::table('meta_upload_batches')->where('batch_code', $batchCode)->exists()
            && $retry < 5
        ) {
            $retry++;
            $suffix = strtoupper(bin2hex(random_bytes(2)));
            $batchCode = $sanitizeCode($batchCodeBase . '_' . $suffix);
        }
        if (DB::table('meta_upload_batches')->where('batch_code', $batchCode)->exists()) {
            $batchCode = $sanitizeCode('batch_' . $batchToken . '_' . strtoupper(bin2hex(random_bytes(2))));
        }

        $uploadBatchId = DB::table('meta_upload_batches')->insertGetId([
            'batch_code' => $batchCode,
            'upload_type' => 0,
            'total_count' => count($files),
            'success_count' => 0,
            'fail_count' => 0,
            'create_time' => $now,
        ]);

        $createdIds = [];
        $successCount = 0;
        $failCount = 0;

        $folderCache = []; // ['parentId_folderName' => folderId]

        DB::beginTransaction();
        try {
            foreach ($files as $idx => $file) {
                if (!$file) {
                    $failCount++;
                    continue;
                }

                $leafFolderId = (int)$folderId;
                if ($uploadMode === 'folder') {
                    $relPath = $relativePaths[$idx] ?? '';
                    if ($relPath !== '') {
                        $dirname = dirname(str_replace('\\', '/', $relPath));
                        if ($dirname !== '.' && $dirname !== '') {
                            $parts = explode('/', trim($dirname, '/'));
                            $currentParentId = $leafFolderId;

                            foreach ($parts as $part) {
                                $part = trim($part);
                                if ($part === '') continue;

                                $cacheKey = $currentParentId . '_' . $part;
                                if (isset($folderCache[$cacheKey])) {
                                    $currentParentId = $folderCache[$cacheKey];
                                } else {
                                    $child = DB::table('meta_folders')
                                        ->where('parent_id', $currentParentId)
                                        ->where('folder_name', $part)
                                        ->whereNull('deleted_at')
                                        ->first();

                                    if ($child) {
                                        $currentParentId = $child->id;
                                        $folderCache[$cacheKey] = $currentParentId;
                                    } else {
                                        $parentItem = DB::table('meta_folders')->where('id', $currentParentId)->first();
                                        if ($parentItem) {
                                            $newPath = rtrim((string)$parentItem->folder_path, '/') . '/' . $part;
                                            $newLevel = $parentItem->level !== null
                                                ? ((int)$parentItem->level + 1)
                                                : (substr_count($newPath, '/') + 1);

                                            $maxSortOrder = DB::table('meta_folders')
                                                ->where('parent_id', $currentParentId)
                                                ->where('owner_id', $parentItem->owner_id)
                                                ->where('library_type', $parentItem->library_type)
                                                ->whereNull('deleted_at')
                                                ->max('sort_order');

                                            $newId = DB::table('meta_folders')->insertGetId([
                                                'folder_name' => $part,
                                                'parent_id' => $currentParentId,
                                                'folder_path' => $newPath,
                                                'library_type' => $parentItem->library_type,
                                                'owner_id' => $parentItem->owner_id,
                                                'level' => $newLevel,
                                                'sort_order' => (int)$maxSortOrder + 1,
                                                'create_time' => $now,
                                                'deleted_at' => null,
                                            ]);

                                            $currentParentId = $newId;
                                            $folderCache[$cacheKey] = $currentParentId;
                                        }
                                    }
                                }
                            }
                            $leafFolderId = $currentParentId;
                        }
                    }
                }

                $originalName = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension() ?: '');
                $fileFormat = $ext !== '' ? $ext : null;

                $materialNameBase = pathinfo($originalName, PATHINFO_FILENAME);
                $materialName = $materialNameBase !== '' ? $materialNameBase : ('material_' . ($idx + 1));

                // local_id 批次内唯一：支持单次上传多文件（图/视频）稳定落库
                $localId = $sanitizeCode($batchCode . '_' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT));

                $diskDir = 'xmp_materials/' . $now->format('Y-m-d');
                $hashName = $file->hashName();

                /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
                $publicDisk = Storage::disk('public');
                // 确保目录存在（部分环境下 storeAs 在父目录未创建时会失败）
                if (!$publicDisk->exists($diskDir)) {
                    $publicDisk->makeDirectory($diskDir);
                }

                $path = $file->storeAs($diskDir, $hashName, 'public');
                if ($path === false || $path === '') {
                    throw new \RuntimeException('Failed to store uploaded file to public disk');
                }
                $absoluteStoredPath = $publicDisk->path($path);
                if (!is_file($absoluteStoredPath)) {
                    throw new \RuntimeException(
                        'Uploaded file missing after store. Expected at: ' . $absoluteStoredPath
                    );
                }

                $fileUrl = $publicDisk->url($path);

                $thumbnailUrl = null;
                // MVP：图片直接复用主文件地址，避免生成缩略图的额外成本。
                if (in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                    $thumbnailUrl = $fileUrl;
                }

                $isVideo = in_array($ext, $videoExts, true);
                $width = null;
                $height = null;
                if (!$isVideo) {
                    [$width, $height] = $this->readImageDimensionsFromPath($absoluteStoredPath);
                }

                $metaTag = null; // MVP：生命周期枚举先不强制

                $id = DB::table('meta_materials')->insertGetId([
                    'local_id' => $localId,
                    'material_name' => $materialName,
                    'file_url' => $fileUrl,
                    'thumbnail_url' => $thumbnailUrl,
                    'file_format' => $fileFormat,
                    'width' => $width,
                    'height' => $height,
                    'file_size' => $file->getSize(),
                    'material_type' => $materialType,
                    'folder_id' => $leafFolderId,
                    'designer_id' => $designerId !== null && $designerId !== '' ? (int) $designerId : null,
                    'creator_id' => $creatorId !== null && $creatorId !== '' ? (int) $creatorId : null,
                    'meta_tag' => $metaTag,
                    'upload_status' => 1,
                    'audit_status' => 0,
                    'source' => $request->input('source', null),
                    'reject_reason' => null,
                    'material_group_id' => $materialGroupId !== null && $materialGroupId !== '' ? (int) $materialGroupId : null,
                    'remarks' => null,
                    'upload_batch_id' => $uploadBatchId,
                    'mindworks_locked' => 0,
                    'create_time' => $now,
                    'deleted_at' => null,
                ]);

                if (!empty($tagIds)) {
                    $rows = array_map(static function ($tagId) use ($id, $now) {
                        return [
                            'material_id' => (string) $id,
                            'tag_id' => (int) $tagId,
                            'create_time' => $now,
                        ];
                    }, array_values($tagIds));

                    DB::table('meta_material_tags')->insert($rows);
                }

                $createdIds[] = (string) $id;
                $successCount++;
            }

            DB::table('meta_upload_batches')->where('id', $uploadBatchId)->update([
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'upload_batch_id' => (string) $uploadBatchId,
                'created_material_ids' => $createdIds,
            ],
        ]);
    }

    /**
     * 上传素材：初始化“临时分片上传会话”
     * POST /material-library/materials/upload-temp/session
     */
    public function uploadTempSessionInit(Request $request)
    {
        $ownerId = $this->authUserId();

        $folderId = $request->input('folder_id');
        if ($folderId === null || $folderId === '' || (string) $folderId === 'favorites') {
            return response()->json(['message' => 'folder_id is required'], 422);
        }

        $uploadModeRaw = $request->input('upload_mode', 'file');
        $uploadMode = ((string) $uploadModeRaw === 'folder') ? 1 : 0;

        $materialTypeRaw = $request->input('material_type', 'regular');
        $materialType = ((string) $materialTypeRaw === 'playable' || (string) $materialTypeRaw === '1') ? 1 : 0;

        $tagModeRaw = $request->input('tag_mode', 'unified');
        $tagMode = (strtolower((string) $tagModeRaw) === 'smart') ? 1 : 0;

        $designerModeRaw = $request->input('designer_mode', 'unified');
        $designerMode = (strtolower((string) $designerModeRaw) === 'smart') ? 1 : 0;

        $creatorModeRaw = $request->input('creator_mode', 'unified');
        $creatorMode = (strtolower((string) $creatorModeRaw) === 'smart') ? 1 : 0;

        $designerId = $designerMode === 0 ? $request->input('designer_id') : null;
        $creatorId = $creatorMode === 0 ? $request->input('creator_id') : null;

        $materialGroupId = $request->input('material_group_id');
        $materialGroupIds = $request->input('material_group_ids', []);
        if (($materialGroupId === null || $materialGroupId === '') && is_array($materialGroupIds) && !empty($materialGroupIds)) {
            $materialGroupId = $materialGroupIds[0];
        }

        $batchPrefix = trim((string) $request->input('batch_prefix', ''));
        $tagIds = $request->input('tag_ids', []);
        if (!is_array($tagIds)) $tagIds = [$tagIds];
        $tagIds = array_values(array_filter(array_map(static function ($v) {
            $s = trim((string) $v);
            return $s === '' ? null : $s;
        }, $tagIds), static fn($v) => $v !== null));
        if ($tagMode !== 0) $tagIds = [];

        $filesManifest = $request->input('files_manifest', []);
        if (!is_array($filesManifest) || empty($filesManifest)) {
            return response()->json(['message' => 'files_manifest is required'], 422);
        }

        $now = now();
        $sessionToken = $now->format('YmdHisv') . strtoupper(bin2hex(random_bytes(3)));
        $sanitizeCode = static function ($value) {
            $value = preg_replace('/\\s+/', '', (string) $value);
            return mb_substr((string) $value, 0, 50);
        };

        $base = $sanitizeCode('sess_' . $sessionToken);
        $sessionCode = $base;
        $retry = 0;
        while (DB::table('meta_upload_sessions')->where('session_code', $sessionCode)->exists() && $retry < 5) {
            $retry++;
            $suffix = strtoupper(bin2hex(random_bytes(2)));
            $sessionCode = $sanitizeCode($base . '_' . $suffix);
        }

        $sessionId = DB::table('meta_upload_sessions')->insertGetId([
            'session_code' => $sessionCode,
            'owner_id' => $ownerId,
            'folder_id' => (int) $folderId,
            'upload_mode' => (int) $uploadMode,
            'material_type' => (int) $materialType,
            'tag_mode' => (int) $tagMode,
            'tag_ids_json' => json_encode(array_values($tagIds), JSON_UNESCAPED_UNICODE),
            'designer_mode' => (int) $designerMode,
            'designer_id' => ($designerId === '' || $designerId === null) ? null : (int) $designerId,
            'creator_mode' => (int) $creatorMode,
            'creator_id' => ($creatorId === '' || $creatorId === null) ? null : (int) $creatorId,
            'material_group_id' => ($materialGroupId === '' || $materialGroupId === null) ? null : (int) $materialGroupId,
            'batch_prefix' => $batchPrefix === '' ? null : $batchPrefix,
            'status' => 0,
            'expires_at' => $now->copy()->addHours(24),
            'create_time' => $now,
            'update_time' => $now,
        ]);

        $fileInserts = [];
        foreach ($filesManifest as $i => $m) {
            if (!is_array($m)) continue;
            $fileKey = trim((string) ($m['file_key'] ?? ''));
            if ($fileKey === '') continue;

            $fileIndex = (int) ($m['file_index'] ?? $i);
            $fileName = trim((string) ($m['file_name'] ?? ''));
            if ($fileName === '') $fileName = 'material_' . ($fileIndex + 1);

            $fileSize = $m['file_size'] ?? null;
            $chunkTotal = (int) ($m['chunk_total'] ?? 0);
            if ($chunkTotal <= 0) continue;

            $relativePath = $m['relative_path'] ?? null;
            $relativePath = ($relativePath === '' || $relativePath === null) ? null : (string) $relativePath;

            $fileInserts[] = [
                'session_id' => (int) $sessionId,
                'file_key' => $fileKey,
                'file_index' => $fileIndex,
                'file_name' => $fileName,
                'file_size' => $fileSize === '' || $fileSize === null ? null : (int) $fileSize,
                'chunk_total' => $chunkTotal,
                'received_chunks_count' => 0,
                'upload_status' => 0,
                'error_message' => null,
                'relative_path' => $relativePath,
                'local_id' => null,
                'material_id' => null,
                'create_time' => $now,
                'update_time' => $now,
            ];
        }

        if (empty($fileInserts)) {
            DB::table('meta_upload_sessions')->where('id', $sessionId)->delete();
            return response()->json(['message' => 'invalid files_manifest'], 422);
        }

        DB::table('meta_upload_session_files')->insert($fileInserts);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => (string) $sessionId,
                'session_code' => (string) $sessionCode,
            ],
        ]);
    }

    /**
     * 上传素材：上传临时分片（chunk）
     * PUT /material-library/materials/upload-temp/session/{sessionId}/chunk
     *
     * multipart/form-data:
     * - file_key
     * - chunk_index (int)
     * - chunk_total (int, 可选，用于校验)
     * - chunk (file)
     */
    public function uploadTempSessionChunk(Request $request, $sessionId)
    {
        $sessionIdInt = (int) $sessionId;
        $ownerId = $this->authUserId();

        $session = DB::table('meta_upload_sessions')
            ->where('id', $sessionIdInt)
            ->where('status', 0)
            ->first();

        if (!$session) {
            return response()->json(['message' => 'upload session not found or already committed'], 404);
        }

        if (!empty($session->expires_at) && \Carbon\Carbon::parse($session->expires_at)->isPast()) {
            // 过期会话：拒绝继续写，前端可自动回退重新 init
            return response()->json(['message' => 'upload session expired'], 404);
        }

        if ($session->owner_id !== null && $ownerId !== null && (int) $session->owner_id !== (int) $ownerId) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $fileKey = trim((string) $request->input('file_key', ''));
        if ($fileKey === '') return response()->json(['message' => 'file_key is required'], 422);

        $chunkIndexRaw = $request->input('chunk_index', '');
        if ($chunkIndexRaw === '' || $chunkIndexRaw === null || !is_numeric($chunkIndexRaw)) {
            return response()->json(['message' => 'chunk_index must be numeric'], 422);
        }
        $chunkIndex = (int) $chunkIndexRaw;

        $chunkTotal = null;
        $chunkTotalRaw = $request->input('chunk_total', null);
        if ($chunkTotalRaw !== null && $chunkTotalRaw !== '' && is_numeric($chunkTotalRaw)) {
            $chunkTotal = (int) $chunkTotalRaw;
        }

        /** @var \Illuminate\Http\UploadedFile|null $chunkFile */
        $chunkFile = $request->file('chunk');
        if (!$chunkFile) return response()->json(['message' => 'chunk file is required'], 422);

        $sessionFile = DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->where('file_key', $fileKey)
            ->first();

        if (!$sessionFile) return response()->json(['message' => 'session file not found'], 404);

        $expectedChunkTotal = (int) $sessionFile->chunk_total;
        if ($chunkIndex < 0 || $chunkIndex >= $expectedChunkTotal) {
            return response()->json(['message' => 'chunk_index out of range'], 422);
        }
        if ($chunkTotal !== null && $chunkTotal !== $expectedChunkTotal) {
            return response()->json(['message' => 'chunk_total mismatch'], 422);
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $fileHash = sha1($fileKey);
        $tempDir = 'xmp_upload_temp/' . (string) $session->session_code . '/' . $fileHash;
        if (!$publicDisk->exists($tempDir)) {
            $publicDisk->makeDirectory($tempDir);
        }

        $chunkName = 'chunk_' . $chunkIndex;
        // 幂等：覆盖写同一 chunk_name
        try {
            $stored = $chunkFile->storeAs($tempDir, $chunkName, 'public');
            if ($stored === false || $stored === '') {
                return response()->json(['message' => 'failed to store chunk'], 500);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'failed to store chunk', 'error' => $e->getMessage()], 500);
        }

        $inserted = DB::table('meta_upload_session_chunks')->insertOrIgnore([
            'session_file_id' => (int) $sessionFile->id,
            'chunk_index' => (int) $chunkIndex,
            'chunk_size' => (int) $chunkFile->getSize(),
            'create_time' => now(),
        ]);

        // 更新 received_chunks_count：通过是否新增 chunk 行来增量更新
        if ((int) $inserted > 0) {
            DB::table('meta_upload_session_files')
                ->where('id', (int) $sessionFile->id)
                ->increment('received_chunks_count', 1);
        }

        $receivedCount = DB::table('meta_upload_session_chunks')
            ->where('session_file_id', (int) $sessionFile->id)
            ->count();

        $newStatus = $receivedCount >= $expectedChunkTotal ? 1 : 0;
        DB::table('meta_upload_session_files')
            ->where('id', (int) $sessionFile->id)
            ->update([
                'upload_status' => $newStatus,
                'received_chunks_count' => (int) $receivedCount,
                'update_time' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'file_key' => $fileKey,
                'chunk_index' => (int) $chunkIndex,
                'received_chunks_count' => (int) $receivedCount,
                'chunk_total' => (int) $expectedChunkTotal,
            ],
        ]);
    }

    /**
     * 上传素材：查询上传会话文件/分片状态（用于断点续传）
     * GET /material-library/materials/upload-temp/session/{sessionId}/files/status
     */
    public function uploadTempSessionFilesStatus(Request $request, $sessionId)
    {
        $sessionIdInt = (int) $sessionId;
        $ownerId = $this->authUserId();

        $session = DB::table('meta_upload_sessions')
            ->where('id', $sessionIdInt)
            ->whereIn('status', [0, 1])
            ->first();

        if (!$session) return response()->json(['message' => 'upload session not found'], 404);
        if (!empty($session->expires_at) && \Carbon\Carbon::parse($session->expires_at)->isPast()) {
            return response()->json(['message' => 'upload session expired'], 404);
        }
        if ($session->owner_id !== null && $ownerId !== null && (int) $session->owner_id !== (int) $ownerId) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $files = DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->orderBy('file_index')
            ->get();

        $fileIds = [];
        foreach ($files as $f) $fileIds[] = (int) $f->id;

        $chunksMap = [];
        if (!empty($fileIds)) {
            $chunkRows = DB::table('meta_upload_session_chunks')
                ->whereIn('session_file_id', $fileIds)
                ->orderBy('chunk_index')
                ->get(['session_file_id', 'chunk_index']);
            foreach ($chunkRows as $row) {
                $sid = (int) $row->session_file_id;
                if (!isset($chunksMap[$sid])) $chunksMap[$sid] = [];
                $chunksMap[$sid][] = (int) $row->chunk_index;
            }
        }

        $respFiles = [];
        foreach ($files as $f) {
            $respFiles[] = [
                'file_key' => (string) $f->file_key,
                'file_index' => (int) $f->file_index,
                'file_name' => (string) $f->file_name,
                'chunk_total' => (int) $f->chunk_total,
                'received_chunks_count' => (int) $f->received_chunks_count,
                'upload_status' => (int) $f->upload_status,
                'received_chunk_indices' => $chunksMap[(int) $f->id] ?? [],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => (string) $sessionIdInt,
                'status' => (int) $session->status,
                'files' => $respFiles,
            ],
        ]);
    }

    /**
     * 上传素材：commit 会话，合并分片并写入最终文件+落库
     * POST /material-library/materials/upload-temp/session/{sessionId}/commit
     */
    public function uploadTempSessionCommit(Request $request, $sessionId)
    {
        $sessionIdInt = (int) $sessionId;
        $ownerId = $this->authUserId();

        $session = DB::table('meta_upload_sessions')
            ->where('id', $sessionIdInt)
            ->where('status', 0)
            ->first();

        if (!$session) return response()->json(['message' => 'upload session not found'], 404);
        if (!empty($session->expires_at) && \Carbon\Carbon::parse($session->expires_at)->isPast()) {
            return response()->json(['message' => 'upload session expired'], 404);
        }
        if ($session->owner_id !== null && $ownerId !== null && (int) $session->owner_id !== (int) $ownerId) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $tagIds = [];
        if (!empty($session->tag_ids_json)) {
            $decoded = json_decode((string) $session->tag_ids_json, true);
            if (is_array($decoded)) $tagIds = $decoded;
        }
        $tagIds = array_values(array_filter(array_map(static fn($v) => is_numeric($v) ? (int) $v : null, $tagIds), static fn($v) => $v !== null));

        $publicDisk = Storage::disk('public');
        $diskDirDate = now()->format('Y-m-d');
        $finalDiskDir = 'xmp_materials/' . $diskDirDate;
        if (!$publicDisk->exists($finalDiskDir)) {
            $publicDisk->makeDirectory($finalDiskDir);
        }

        $sessionFiles = DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->orderBy('file_index')
            ->get();

        if (empty($sessionFiles)) return response()->json(['message' => 'no session files'], 422);

        // 校验：必须每个文件 chunk_total 都已全部接收
        $incomplete = [];
        foreach ($sessionFiles as $sf) {
            $ct = (int) $sf->chunk_total;
            $rc = (int) $sf->received_chunks_count;
            if ((int) $sf->upload_status !== 1 || $rc < $ct) {
                $incomplete[] = [
                    'file_key' => (string) $sf->file_key,
                    'received' => $rc,
                    'total' => $ct,
                ];
            }
        }
        if (!empty($incomplete)) {
            return response()->json(['message' => 'incomplete chunks', 'incomplete' => $incomplete], 422);
        }

        $now = now();
        $batchPrefix = trim((string) ($session->batch_prefix ?? ''));
        $batchToken = (string) $session->session_code;
        $dateStr = $now->format('Ymd');
        $timeStr = $now->format('His');

        $rawBatchCode = $batchPrefix !== ''
            ? str_replace(['{date}', '{time}', '{batch}'], [$dateStr, $timeStr, $batchToken], $batchPrefix)
            : ('batch_' . $batchToken);

        $batchCode = preg_replace('/\\s+/', '', (string) $rawBatchCode);
        $batchCode = mb_substr((string) $batchCode, 0, 50);

        // 避免极少数 batch_code 冲突（数据库层面不强制唯一）
        $retry = 0;
        while (DB::table('meta_upload_batches')->where('batch_code', $batchCode)->exists() && $retry < 5) {
            $retry++;
            $suffix = strtoupper(bin2hex(random_bytes(2)));
            $batchCode = mb_substr($batchCode . '_' . $suffix, 0, 50);
        }
        $uploadBatchId = DB::table('meta_upload_batches')->insertGetId([
            'batch_code' => $batchCode,
            'upload_type' => (int) $session->upload_mode,
            'total_count' => count($sessionFiles),
            'success_count' => 0,
            'fail_count' => 0,
            'create_time' => $now,
        ]);

        $createdIds = [];
        $successCount = 0;
        $failCount = 0;

        $folderCache = []; // 同 upload()：用于 folder 模式下缓存子文件夹 id

        $sanitizeCode = static function ($value) {
            $value = preg_replace('/\\s+/', '', (string) $value);
            return mb_substr((string) $value, 0, 50);
        };

        // 复用 upload() 中的文件夹创建逻辑
        foreach ($sessionFiles as $sf) {
            try {
                // 已经 commit 成功过的文件：避免重复落库（幂等）
                if ((int) $sf->upload_status === 1 && $sf->material_id !== null) {
                    $createdIds[] = (string) $sf->material_id;
                    continue;
                }

                $fileKey = (string) $sf->file_key;
                $fileIndex = (int) $sf->file_index;
                $fileName = (string) $sf->file_name;
                $relativePath = $sf->relative_path !== null ? (string) $sf->relative_path : '';

                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $ext = $ext !== '' ? $ext : null;
                $fileFormat = $ext !== null ? $ext : null;

                $materialNameBase = pathinfo($fileName, PATHINFO_FILENAME);
                $materialName = $materialNameBase !== '' ? $materialNameBase : ('material_' . ((int) $fileIndex + 1));

                $localId = $sanitizeCode($batchCode . '_' . str_pad((string) ($fileIndex + 1), 3, '0', STR_PAD_LEFT));

                $isVideo = in_array($ext, ['mp4', 'mov', 'webm', 'm4v', 'avi', 'mkv'], true);

                $leafFolderId = (int) $session->folder_id;
                if ((int) $session->upload_mode === 1 && $relativePath !== '') {
                    $dirname = dirname(str_replace('\\', '/', $relativePath));
                    if ($dirname !== '.' && $dirname !== '') {
                        $parts = explode('/', trim($dirname, '/'));
                        $currentParentId = $leafFolderId;
                        foreach ($parts as $part) {
                            $part = trim($part);
                            if ($part === '') continue;
                            $cacheKey = $currentParentId . '_' . $part;
                            if (isset($folderCache[$cacheKey])) {
                                $currentParentId = $folderCache[$cacheKey];
                                continue;
                            }
                            $child = DB::table('meta_folders')
                                ->where('parent_id', $currentParentId)
                                ->where('folder_name', $part)
                                ->whereNull('deleted_at')
                                ->first();
                            if ($child) {
                                $currentParentId = (int) $child->id;
                                $folderCache[$cacheKey] = $currentParentId;
                            } else {
                                $parentItem = DB::table('meta_folders')->where('id', $currentParentId)->first();
                                if (!$parentItem) throw new \RuntimeException('parent folder missing for folder mode');

                                $newPath = rtrim((string) $parentItem->folder_path, '/') . '/' . $part;
                                $newLevel = $parentItem->level !== null
                                    ? ((int) $parentItem->level + 1)
                                    : (substr_count($newPath, '/') + 1);

                                $maxSortOrder = DB::table('meta_folders')
                                    ->where('parent_id', $currentParentId)
                                    ->where('owner_id', $parentItem->owner_id)
                                    ->where('library_type', $parentItem->library_type)
                                    ->whereNull('deleted_at')
                                    ->max('sort_order');

                                $newId = DB::table('meta_folders')->insertGetId([
                                    'folder_name' => $part,
                                    'parent_id' => $currentParentId,
                                    'folder_path' => $newPath,
                                    'library_type' => $parentItem->library_type,
                                    'owner_id' => $parentItem->owner_id,
                                    'level' => $newLevel,
                                    'sort_order' => (int) $maxSortOrder + 1,
                                    'create_time' => $now,
                                    'deleted_at' => null,
                                ]);
                                $currentParentId = (int) $newId;
                                $folderCache[$cacheKey] = $currentParentId;
                            }
                        }
                        $leafFolderId = $currentParentId;
                    }
                }

                // 合并 chunks -> 最终文件（流式合并，避免内存爆）
                $fileHash = sha1($fileKey);
                $tempDir = 'xmp_upload_temp/' . (string) $session->session_code . '/' . $fileHash;
                $chunkTotal = (int) $sf->chunk_total;

                $outName = $fileHash . ($ext ? ('.' . $ext) : '');
                $outRelPath = $finalDiskDir . '/' . $outName;
                $outAbsPath = $publicDisk->path($outRelPath);

                // 确保不会残留旧文件
                if (is_file($outAbsPath)) {
                    @unlink($outAbsPath);
                }

                $outHandle = fopen($outAbsPath, 'wb');
                if (!$outHandle) throw new \RuntimeException('failed to open output file');

                for ($i = 0; $i < $chunkTotal; $i++) {
                    $chunkAbs = $publicDisk->path($tempDir . '/chunk_' . (int) $i);
                    if (!is_file($chunkAbs)) {
                        fclose($outHandle);
                        throw new \RuntimeException('missing chunk file: ' . $chunkAbs);
                    }
                    $inHandle = fopen($chunkAbs, 'rb');
                    if (!$inHandle) {
                        fclose($outHandle);
                        throw new \RuntimeException('failed to open chunk file');
                    }
                    stream_copy_to_stream($inHandle, $outHandle);
                    fclose($inHandle);
                }
                fclose($outHandle);

                if (!is_file($outAbsPath)) {
                    throw new \RuntimeException('merged file missing after merge');
                }

                /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
                $fileUrl = $publicDisk->url($outRelPath);
                $thumbnailUrl = null;
                if ($ext !== null && in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                    $thumbnailUrl = $fileUrl;
                }

                $width = null;
                $height = null;
                if (!$isVideo && $outAbsPath !== '') {
                    [$width, $height] = $this->readImageDimensionsFromPath($outAbsPath);
                }

                $designerId = ((int) $session->designer_mode === 0 && $session->designer_id !== null) ? (int) $session->designer_id : null;
                $creatorId = ((int) $session->creator_mode === 0 && $session->creator_id !== null) ? (int) $session->creator_id : null;

                $materialId = DB::table('meta_materials')->insertGetId([
                    'local_id' => $localId,
                    'material_name' => $materialName,
                    'file_url' => $fileUrl,
                    'thumbnail_url' => $thumbnailUrl,
                    'file_format' => $fileFormat,
                    'width' => $width,
                    'height' => $height,
                    'file_size' => (int) ($sf->file_size ?? 0),
                    'material_type' => (int) $session->material_type,
                    'folder_id' => $leafFolderId,
                    'designer_id' => $designerId,
                    'creator_id' => $creatorId,
                    'meta_tag' => null,
                    'upload_status' => 1,
                    'audit_status' => 0,
                    'source' => null,
                    'reject_reason' => null,
                    'material_group_id' => $session->material_group_id !== null ? (int) $session->material_group_id : null,
                    'remarks' => null,
                    'upload_batch_id' => $uploadBatchId,
                    'mindworks_locked' => 0,
                    'create_time' => $now,
                    'deleted_at' => null,
                ]);

                if (!empty($tagIds)) {
                    $rows = array_map(static function ($tagId) use ($materialId, $now) {
                        return [
                            'material_id' => (string) $materialId,
                            'tag_id' => (int) $tagId,
                            'create_time' => $now,
                        ];
                    }, $tagIds);
                    if (!empty($rows)) {
                        DB::table('meta_material_tags')->insert($rows);
                    }
                }

                DB::table('meta_upload_session_files')
                    ->where('id', (int) $sf->id)
                    ->update([
                        'upload_status' => 1,
                        'local_id' => $localId,
                        'material_id' => (int) $materialId,
                        'update_time' => $now,
                    ]);

                $createdIds[] = (string) $materialId;
                $successCount++;
            } catch (\Throwable $e) {
                $failCount++;
                DB::table('meta_upload_session_files')
                    ->where('id', (int) $sf->id)
                    ->update([
                        'upload_status' => 2,
                        'error_message' => $e->getMessage(),
                        'update_time' => $now,
                    ]);
            }
        }

        // commit 结束后，以最终落库结果为准统计成功/失败
        $successCount = DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->where('upload_status', 1)
            ->count();
        $failCount = DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->where('upload_status', 2)
            ->count();

        DB::table('meta_upload_batches')->where('id', $uploadBatchId)->update([
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ]);

        DB::table('meta_upload_sessions')
            ->where('id', $sessionIdInt)
            ->update([
                'status' => 1,
                'update_time' => $now,
            ]);

        // 清理临时分片文件与 chunk 元数据（commit 完后不再需要）
        $tempRoot = 'xmp_upload_temp/' . (string) $session->session_code;
        try {
            $publicDisk->deleteDirectory($tempRoot);
        } catch (\Throwable $e) {
            // ignore
        }
        $sessionFileIds = DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->pluck('id')
            ->map(static fn($v) => (int) $v)
            ->toArray();

        if (!empty($sessionFileIds)) {
            DB::table('meta_upload_session_chunks')
                ->whereIn('session_file_id', $sessionFileIds)
                ->delete();
        }

        DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->delete();

        DB::table('meta_upload_sessions')
            ->where('id', $sessionIdInt)
            ->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'upload_batch_id' => (string) $uploadBatchId,
                'created_material_ids' => $createdIds,
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ],
        ]);
    }

    /**
     * 删除未提交的临时上传会话
     * DELETE /material-library/materials/upload-temp/session/{sessionId}
     */
    public function uploadTempSessionDelete(Request $request, $sessionId)
    {
        $sessionIdInt = (int) $sessionId;
        $ownerId = $this->authUserId();

        $session = DB::table('meta_upload_sessions')
            ->where('id', $sessionIdInt)
            ->first();
        if (!$session) return response()->json(['message' => 'upload session not found'], 404);
        if ($session->owner_id !== null && $ownerId !== null && (int) $session->owner_id !== (int) $ownerId) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        if ((int) $session->status !== 0) {
            return response()->json(['message' => 'only uploading session can be deleted'], 422);
        }

        $sessionCode = (string) $session->session_code;
        $publicDisk = Storage::disk('public');
        $tempRoot = 'xmp_upload_temp/' . $sessionCode;

        try {
            $publicDisk->deleteDirectory($tempRoot);
        } catch (\Throwable $e) {
            // ignore
        }

        $sessionFileIds = DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->pluck('id')
            ->map(static fn($v) => (int) $v)
            ->toArray();

        if (!empty($sessionFileIds)) {
            DB::table('meta_upload_session_chunks')
                ->whereIn('session_file_id', $sessionFileIds)
                ->delete();
        }

        DB::table('meta_upload_session_files')
            ->where('session_id', $sessionIdInt)
            ->delete();

        DB::table('meta_upload_sessions')
            ->where('id', $sessionIdInt)
            ->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => (string) $sessionIdInt,
                'deleted' => true,
            ],
        ]);
    }

    /**
     * 编辑素材（软字段更新）
     * PUT /material-library/materials/{id}
     */
    public function update(Request $request, $id)
    {
        $material = DB::table('meta_materials')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$material) {
            return response()->json(['message' => 'material not found'], 404);
        }

        $updateData = [];

        if ($request->has('material_name')) {
            $materialName = trim((string) $request->input('material_name'));
            if ($materialName === '') {
                return response()->json(['message' => 'material_name is required'], 422);
            }
            $updateData['material_name'] = $materialName;
        }

        if ($request->has('folder_id')) {
            $folderId = $request->input('folder_id');
            if ($folderId === '' || $folderId === null) {
                $updateData['folder_id'] = null;
            } else {
                $folder = DB::table('meta_folders')
                    ->where('id', $folderId)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$folder) {
                    return response()->json(['message' => 'folder not found'], 404);
                }

                $updateData['folder_id'] = (int) $folderId;
            }
        }

        if ($request->has('designer_id')) {
            $designerId = $request->input('designer_id');
            $updateData['designer_id'] = ($designerId === '' || $designerId === null) ? null : (int) $designerId;
        }

        if ($request->has('creator_id')) {
            $creatorId = $request->input('creator_id');
            $updateData['creator_id'] = ($creatorId === '' || $creatorId === null) ? null : (int) $creatorId;
        }

        if ($request->has('material_group_id')) {
            $groupId = $request->input('material_group_id');
            $updateData['material_group_id'] = ($groupId === '' || $groupId === null) ? null : (int) $groupId;
        }

        if ($request->has('remarks')) {
            $remarks = (string) $request->input('remarks');
            $updateData['remarks'] = $remarks === '' ? null : $remarks;
        }

        // 文档字段：xmp_tag；表字段：meta_tag
        if ($request->has('xmp_tag') || $request->has('meta_tag')) {
            $xmpTag = $request->has('xmp_tag') ? $request->input('xmp_tag') : $request->input('meta_tag');
            $updateData['meta_tag'] = ($xmpTag === '' || $xmpTag === null) ? null : (int) $xmpTag;
        }

        if ($request->has('mindworks_locked')) {
            $updateData['mindworks_locked'] = (int) ($request->boolean('mindworks_locked') ? 1 : 0);
        }

        if (empty($updateData)) {
            return response()->json([
                'success' => true,
                'data' => ['updated' => 0],
            ]);
        }

        DB::table('meta_materials')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update($updateData);

        return response()->json([
            'success' => true,
            'data' => ['material_id' => (string) $id],
        ]);
    }

    /**
     * 更新素材制作费用（写入统计表）
     * PUT /material-library/materials/{id}/production-cost
     */
    public function updateProductionCost(Request $request, $id)
    {
        $material = DB::table('meta_materials')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$material) {
            return response()->json(['message' => 'material not found'], 404);
        }

        if (!$request->has('production_cost')) {
            return response()->json(['message' => 'production_cost is required'], 422);
        }

        $rawProductionCost = $request->input('production_cost');
        $productionCost = null;

        if ($rawProductionCost !== null && $rawProductionCost !== '') {
            if (!is_numeric($rawProductionCost)) {
                return response()->json(['message' => 'production_cost must be numeric'], 422);
            }
            $productionCost = (float) $rawProductionCost;
            if ($productionCost < 0) {
                return response()->json(['message' => 'production_cost must be greater than or equal to 0'], 422);
            }
            $productionCost = round($productionCost, 2);
        }

        $today = now()->toDateString();

        DB::transaction(function () use ($id, $today, $productionCost) {
            $latestStat = DB::table('meta_material_statistics')
                ->where('material_id', $id)
                ->orderByDesc('statistics_date')
                ->orderByDesc('id')
                ->first();

            if ($latestStat) {
                DB::table('meta_material_statistics')
                    ->where('id', $latestStat->id)
                    ->update([
                        'production_cost' => $productionCost,
                    ]);
                return;
            }

            DB::table('meta_material_statistics')->insert([
                'material_id' => (int) $id,
                'production_cost' => $productionCost,
                'spend' => null,
                'impressions' => null,
                'cpm' => null,
                'clicks' => null,
                'cpc' => null,
                'ctr' => null,
                'conversions' => null,
                'cpa' => null,
                'associated_creative_count' => 0,
                'statistics_date' => $today,
                'statistics_end_date' => null,
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'material_id' => (string) $id,
                'production_cost' => $productionCost,
            ],
        ]);
    }

    /**
     * 删除素材（软删）
     * DELETE /material-library/materials/{id}
     */
    public function destroy(Request $request, $id)
    {
        $now = now();
        $affected = DB::table('meta_materials')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        if ((int) $affected === 0) {
            return response()->json(['message' => 'material not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'material_id' => (string) $id,
                'deleted' => (int) $affected,
            ],
        ]);
    }

    /**
     * 批量操作（MVP：SET_TAGS/CLEAR_TAGS/MOVE/DELETE）
     * POST /material-library/materials/batch-actions
     */
    public function batchActions(Request $request)
    {
        $actionTypeRaw = trim((string) $request->input('action_type', ''));
        $actionType = strtoupper($actionTypeRaw);

        $resourceIds = $request->input('resource_ids', []);
        if (!is_array($resourceIds)) {
            $resourceIds = $resourceIds === null ? [] : [$resourceIds];
        }
        $resourceIds = array_values(array_filter($resourceIds, static function ($v) {
            return $v !== null && $v !== '';
        }));
        $resourceIds = array_values(array_map(static function ($v) {
            return (int) $v;
        }, $resourceIds));

        if (empty($resourceIds)) {
            return response()->json(['message' => 'resource_ids is required'], 422);
        }

        $payload = $request->input('payload', []);
        if (!is_array($payload)) {
            $payload = [];
        }

        $validMaterialIds = DB::table('meta_materials')
            ->whereIn('id', $resourceIds)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(static function ($v) {
                return (int) $v;
            })
            ->all();

        $validSet = array_flip($validMaterialIds);
        $now = now();

        $resolveTagIds = function (array $tags) use ($now) {
            $tagIds = [];
            $tagNames = [];

            foreach ($tags as $t) {
                if ($t === null || $t === '') continue;

                if (is_numeric($t)) {
                    $tagIds[] = (int) $t;
                    continue;
                }

                if (is_string($t)) {
                    $name = trim($t);
                    if ($name !== '') $tagNames[] = $name;
                    continue;
                }

                if (is_array($t)) {
                    if (isset($t['id']) && is_numeric($t['id'])) {
                        $tagIds[] = (int) $t['id'];
                        continue;
                    }
                    if (isset($t['tag_id']) && is_numeric($t['tag_id'])) {
                        $tagIds[] = (int) $t['tag_id'];
                        continue;
                    }
                    if (isset($t['name']) && is_string($t['name'])) {
                        $tagNames[] = trim($t['name']);
                        continue;
                    }
                }

                if (is_object($t)) {
                    // eslint-disable-next-line no-prototype-builtins
                    if (isset($t->id) && is_numeric($t->id)) {
                        $tagIds[] = (int) $t->id;
                        continue;
                    }
                    if (isset($t->tag_id) && is_numeric($t->tag_id)) {
                        $tagIds[] = (int) $t->tag_id;
                        continue;
                    }
                    if (isset($t->name) && is_string($t->name)) {
                        $tagNames[] = trim($t->name);
                        continue;
                    }
                }
            }

            $tagIds = array_values(array_unique(array_filter($tagIds, static fn($v) => $v !== null)));
            $tagNames = array_values(array_unique(array_filter($tagNames, static fn($v) => $v !== null && $v !== '')));

            if (!empty($tagNames)) {
                $existing = DB::table('meta_tags')
                    ->whereIn('name', $tagNames)
                    ->select(['id', 'name as tag_name'])
                    ->get();

                $existingByName = [];
                foreach ($existing as $row) {
                    $existingByName[(string) $row->tag_name] = (int) $row->id;
                }

                $missing = array_values(array_diff($tagNames, array_keys($existingByName)));
                if (!empty($missing)) {
                    $insertRows = array_map(static function ($name) use ($now) {
                        return [
                            'name' => (string) $name,
                            'remark' => null,
                            'folder_id' => null,
                            'tag_object' => null,
                            'tag_object_level1' => null,
                            'sort' => 0,
                            'create_time' => $now,
                        ];
                    }, $missing);

                    // 避免并发导致的唯一键冲突
                    DB::table('meta_tags')->insertOrIgnore($insertRows);
                }

                $existing = DB::table('meta_tags')
                    ->whereIn('name', $tagNames)
                    ->select(['id', 'name as tag_name'])
                    ->get();

                foreach ($existing as $row) {
                    $tagIds[] = (int) $row->id;
                }
            }

            return array_values(array_unique($tagIds));
        };

        // 统一返回结构：每条素材一项结果
        $results = array_map(static function ($rid) use ($validSet) {
            return [
                'material_id' => (string) $rid,
                'status' => isset($validSet[$rid]) ? 'ok' : 'skipped',
            ];
        }, $resourceIds);

        if (empty($validMaterialIds)) {
            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        }

        DB::beginTransaction();
        try {
            if ($actionType === 'DELETE') {
                DB::table('meta_materials')
                    ->whereIn('id', $validMaterialIds)
                    ->update(['deleted_at' => $now]);
            } elseif ($actionType === 'MOVE') {
                $targetFolderId = $payload['target_folder_id'] ?? $payload['targetFolderId'] ?? null;
                if ($targetFolderId === null || $targetFolderId === '') {
                    throw new \RuntimeException('target_folder_id is required', 422);
                }

                $targetFolder = DB::table('meta_folders')
                    ->where('id', $targetFolderId)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$targetFolder) {
                    throw new \RuntimeException('target folder not found', 404);
                }

                DB::table('meta_materials')
                    ->whereIn('id', $validMaterialIds)
                    ->update(['folder_id' => (int) $targetFolderId]);
            } elseif ($actionType === 'CLEAR_TAGS') {
                DB::table('meta_material_tags')
                    ->whereIn('material_id', $validMaterialIds)
                    ->delete();
            } elseif ($actionType === 'SET_TAGS' || $actionType === 'EDIT_TAGS') {
                $tagIdsCommon = null;
                $materialsTagsMap = null;

                if (isset($payload['materials_tags']) || isset($payload['materialsTags'])) {
                    $materialsTagsMap = $payload['materials_tags'] ?? $payload['materialsTags'];
                    if (!is_array($materialsTagsMap)) $materialsTagsMap = null;
                }

                if ($materialsTagsMap === null) {
                    $commonTags = $payload['tags'] ?? [];
                    $tagIdsCommon = $resolveTagIds(is_array($commonTags) ? $commonTags : []);
                }

                // 先清空，再写入（确保“清空+设置”不留下旧标签）
                DB::table('meta_material_tags')
                    ->whereIn('material_id', $validMaterialIds)
                    ->delete();

                $insertRows = [];
                if ($materialsTagsMap !== null) {
                    foreach ($materialsTagsMap as $row) {
                        if (!is_array($row)) continue;
                        $mid = $row['material_id'] ?? $row['id'] ?? null;
                        if ($mid === null || $mid === '') continue;
                        $mid = (int) $mid;
                        if (!isset($validSet[$mid])) continue;

                        $rowTags = $row['tags'] ?? [];
                        $tagIds = $resolveTagIds(is_array($rowTags) ? $rowTags : []);
                        foreach ($tagIds as $tagId) {
                            $insertRows[] = [
                                'material_id' => (string) $mid,
                                'tag_id' => (int) $tagId,
                                'create_time' => $now,
                            ];
                        }
                    }
                } else {
                    $tagIds = $tagIdsCommon ?? [];
                    foreach ($validMaterialIds as $mid) {
                        foreach ($tagIds as $tagId) {
                            $insertRows[] = [
                                'material_id' => (string) $mid,
                                'tag_id' => (int) $tagId,
                                'create_time' => $now,
                            ];
                        }
                    }
                }

                if (!empty($insertRows)) {
                    DB::table('meta_material_tags')->insert($insertRows);
                }
            } else {
                throw new \RuntimeException('unsupported action_type', 422);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $code = (int) $e->getCode();
            $status = ($code >= 400 && $code <= 599) ? $code : 500;

            return response()->json([
                'message' => $e->getMessage() ?: 'batch action failed',
            ], $status);
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * 7.9 审核工作流
     * POST /material-library/materials/{id}/audit
     * body: { status: 1|2, reject_reason?: string }
     */
    public function audit(Request $request, $id)
    {
        $statusRaw = $request->input('status');
        $status = is_numeric($statusRaw) ? (int) $statusRaw : -1;
        if (!in_array($status, [1, 2], true)) {
            return response()->json(['message' => 'status must be 1|2'], 422);
        }

        $rejectReason = trim((string) $request->input('reject_reason', ''));
        if ($status === 2 && $rejectReason === '') {
            return response()->json(['message' => 'reject_reason is required when rejecting'], 422);
        }

        /** @var \\App\\Models\\User|null $authUser */
        $authUser = Auth::user();
        $isAdmin = $authUser ? (bool) $authUser->hasRole('admin') : false;

        $material = DB::table('meta_materials as m')
            ->join('meta_folders as f', 'f.id', '=', 'm.folder_id')
            ->where('m.id', $id)
            ->whereNull('m.deleted_at')
            ->whereNull('f.deleted_at')
            ->select(['m.id', 'm.folder_id', 'f.owner_id'])
            ->first();

        if (!$material) {
            return response()->json(['message' => 'material not found'], 404);
        }

        $authUserId = $this->authUserId();
        if (!$isAdmin && $authUserId !== null && (int) $material->owner_id !== (int) $authUserId) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        $now = now();
        DB::table('meta_materials')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'audit_status' => $status,
                'reject_reason' => $status === 2 ? $rejectReason : null,
                // 审核后通常不影响 upload_status 等；MVP 只更新 audit 字段
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'material_id' => (string) $id,
                'audit_status' => $status,
            ],
        ]);
    }

    /**
     * 7.10 数据导出（MVP：导出 CSV）
     * POST /material-library/materials/export
     * body 支持：{ format, folder_id, include_subfolders, designer_id, creator_id, tag_ids, material_group_id, material_type, size_level, source, audit_status, reject_reason, create_start_date, create_end_date, global_search, resource_ids }
     */
    public function export(Request $request)
    {
        $formatRaw = strtolower((string) $request->input('format', 'csv'));
        $format = in_array($formatRaw, ['csv', 'xlsx'], true) ? $formatRaw : 'csv';
        // MVP 只支持 csv，但允许前端传 xlsx
        $format = 'csv';

        $resourceIds = $request->input('resource_ids', null);
        if (is_string($resourceIds) && $resourceIds !== '') {
            $resourceIds = [$resourceIds];
        }
        if (!is_array($resourceIds)) {
            $resourceIds = [];
        }
        $resourceIds = array_values(array_filter($resourceIds, static function ($v) {
            return $v !== null && $v !== '';
        }));
        $resourceIds = array_values(array_map(static function ($v) {
            return (int) $v;
        }, $resourceIds));

        $limit = (int) $request->input('limit', 1000);
        if ($limit < 1) $limit = 1000;

        $designerIds = $this->normalizeIntList($request->input('designer_id', []));
        $creatorIds = $this->normalizeIntList($request->input('creator_id', []));
        $tagIdsRaw = $request->input('tag_ids', $request->input('tag_id', []));
        $tagIds = is_string($tagIdsRaw)
            ? array_values(array_filter(explode(',', $tagIdsRaw)))
            : (is_array($tagIdsRaw) ? $tagIdsRaw : []);
        $tagIds = array_values(array_map(static fn($v) => (int) $v, $tagIds));

        $globalSearch = trim((string) $request->input('global_search', ''));

        // 类型（支持 frontend 传 regular/playable 或数字）
        $materialTypes = $this->normalizeMaterialTypes($request->input('material_type', []));

        $source = $this->normalizeQueryString($request->input('source', ''));

        // 审核状态（支持数字或 pending/rejected/approved）
        $auditStatusRaw = $this->normalizeQueryString($request->input('audit_status', ''));
        $auditStatus = null;
        if ($auditStatusRaw !== '') {
            if (is_numeric($auditStatusRaw)) {
                $auditStatus = (int) $auditStatusRaw;
            } else {
                $auditStatusStr = strtolower((string) $auditStatusRaw);
                $auditStatus = $auditStatusStr === 'pending' ? 0 : ($auditStatusStr === 'approved' ? 1 : ($auditStatusStr === 'rejected' ? 2 : null));
            }
        }

        // 拒审信息（支持 __HAS_VALUE__/__EMPTY__ 或关键字）
        $rejectReason = $this->normalizeQueryString($request->input('reject_reason', ''));
        $rejectReasonOptionIds = $this->normalizeIntList(
            $request->input('reject_reason_option_ids', $request->input('reject_reason_options', []))
        );

        $materialGroupId = $this->normalizeQueryString($request->input('material_group_id', ''));
        $sizeLevels = $this->normalizeSizeLevels($request->input('size_level', []));
        $createStartDate = $this->normalizeQueryString($request->input('create_start_date', ''));
        $createEndDate = $this->normalizeQueryString($request->input('create_end_date', ''));
        $ratingScores = $this->normalizeRatingScores($request->input('rating_scores', $request->input('rating', [])));
        $systemTagIds = $this->normalizeMetaTagIds($request->input('system_tag_ids', $request->input('system_tag_id', [])));
        $systemTagMode = $this->normalizeQueryString($request->input('system_tag_mode', 'include'));
        $legacyMetaTagIds = $this->normalizeMetaTagIds($request->input('meta_tag_ids', $request->input('meta_tag_id', [])));
        $legacyMetaTagMode = $this->normalizeQueryString($request->input('meta_tag_mode', 'include'));

        $folderId = $request->input('folder_id');
        $includeSubfolders = (bool) $request->boolean('include_subfolders', false);

        /** @var \\App\\Models\\User|null $authUser */
        $authUser = Auth::user();
        $isAdmin = $authUser ? (bool) $authUser->hasRole('admin') : false;
        $ownerId = $request->input('owner_id', $this->authUserId());

        // 禁止导出 Mindworks 锁定素材
        $baseQuery = DB::table('meta_materials as m')
            ->whereNull('m.deleted_at')
            ->where('m.mindworks_locked', 0);

        // favorites 虚拟视图（通过 owner_id 控制）
        if (!empty($resourceIds)) {
            $baseQuery->whereIn('m.id', $resourceIds);
            // 非管理员：限制 owner，避免越权导出
            if (!$isAdmin && $ownerId !== null && $ownerId !== '') {
                $baseQuery
                    ->join('meta_folders as f', 'f.id', '=', 'm.folder_id')
                    ->whereNull('f.deleted_at')
                    ->where('f.owner_id', $ownerId);
            }
        } else {
            if ($folderId === null || $folderId === '') {
                return response()->json(['message' => 'folder_id is required when resource_ids is empty'], 422);
            }

            if ((string) $folderId === 'favorites') {
                $baseQuery
                    ->join('meta_material_favorites as fav', 'fav.material_id', '=', 'm.id')
                    ->where('fav.owner_id', $ownerId)
                    ->join('meta_folders as f', 'f.id', '=', 'm.folder_id')
                    ->whereNull('f.deleted_at');
            } else {
                // folder 过滤（含子目录）
                if ($includeSubfolders) {
                    $selectedPath = DB::table('meta_folders')
                        ->whereNull('deleted_at')
                        ->where('id', $folderId)
                        ->value('folder_path');

                    if ($selectedPath) {
                        $descendantIds = DB::table('meta_folders')
                            ->whereNull('deleted_at')
                            ->where(function ($q) use ($ownerId) {
                                if ($ownerId !== null && $ownerId !== '') {
                                    $q->where('owner_id', $ownerId);
                                }
                            })
                            ->where(function ($q) use ($selectedPath) {
                                $q->where('folder_path', $selectedPath)
                                    ->orWhere('folder_path', 'like', $selectedPath . '/%');
                            })
                            ->pluck('id');

                        if ($descendantIds->isEmpty()) {
                            return response()->json([
                                'success' => true,
                                'data' => [
                                    'filename' => 'xmp-materials.csv',
                                    'csv' => '',
                                ],
                            ]);
                        }

                        $baseQuery->whereIn('m.folder_id', $descendantIds->all());
                    } else {
                        $baseQuery->where('m.folder_id', $folderId);
                    }
                } else {
                    $baseQuery->where('m.folder_id', $folderId);
                }

                // 限制 owner（非管理员时）
                if (!$isAdmin && $ownerId !== null && $ownerId !== '') {
                    $baseQuery
                        ->join('meta_folders as f', 'f.id', '=', 'm.folder_id')
                        ->whereNull('f.deleted_at')
                        ->where('f.owner_id', $ownerId);
                }
            }
        }

        if (!empty($designerIds)) $baseQuery->whereIn('m.designer_id', $designerIds);
        if (!empty($creatorIds)) $baseQuery->whereIn('m.creator_id', $creatorIds);

        if (!empty($materialTypes)) $baseQuery->whereIn('m.material_type', $materialTypes);
        if ($materialGroupId !== '') $baseQuery->where('m.material_group_id', (int) $materialGroupId);
        if (!empty($sizeLevels)) {
            $baseQuery->where(function ($q) use ($sizeLevels) {
                foreach ($sizeLevels as $level) {
                    if ($level === 'small') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) <= 720');
                        });
                    } elseif ($level === 'medium') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) > 720')
                                ->whereRaw('GREATEST(m.width, m.height) <= 1920');
                        });
                    } elseif ($level === 'large') {
                        $q->orWhere(function ($sq) {
                            $sq->whereNotNull('m.width')
                                ->whereNotNull('m.height')
                                ->whereRaw('GREATEST(m.width, m.height) > 1920');
                        });
                    }
                }
            });
        }

        if ($source !== '') $baseQuery->where('m.source', 'like', '%' . $source . '%');
        if ($auditStatus !== null) $baseQuery->where('m.audit_status', $auditStatus);

        if (!empty($rejectReasonOptionIds)) {
            $this->applyRejectReasonOptionFilter($baseQuery, $rejectReasonOptionIds);
        } elseif ($rejectReason !== '') {
            if ($rejectReason === '__HAS_VALUE__') {
                $baseQuery->whereNotNull('m.reject_reason')->where('m.reject_reason', '!=', '');
            } elseif ($rejectReason === '__EMPTY__') {
                $baseQuery->where(function ($q) {
                    $q->whereNull('m.reject_reason')->orWhere('m.reject_reason', '');
                });
            } else {
                $baseQuery->where('m.reject_reason', 'like', '%' . $rejectReason . '%');
            }
        }

        if ($createStartDate !== '') {
            $baseQuery->whereDate('m.create_time', '>=', $createStartDate);
        }
        if ($createEndDate !== '') {
            $baseQuery->whereDate('m.create_time', '<=', $createEndDate);
        }

        if (!empty($ratingScores)) {
            $baseQuery->whereIn('m.rating', $ratingScores);
        }

        if (!empty($systemTagIds)) {
            $this->applySystemTagFilter($baseQuery, $systemTagIds, $systemTagMode);
        } else {
            $this->applyLegacyMetaTagFilter($baseQuery, $legacyMetaTagIds, $legacyMetaTagMode);
        }

        if (!empty($tagIds)) {
            $baseQuery->whereIn('m.id', function ($q) use ($tagIds) {
                $q->select('material_id')
                    ->from('meta_material_tags')
                    ->whereIn('tag_id', $tagIds);
            });
        }

        if ($globalSearch !== '') {
            $like = '%' . $globalSearch . '%';
            $baseQuery->where(function ($q) use ($like) {
                $q->where('m.material_name', 'like', $like)
                    ->orWhere('m.local_id', 'like', $like)
                    ->orWhere('m.remarks', 'like', $like)
                    ->orWhereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))
                            ->from('meta_folders as sf')
                            ->whereColumn('sf.id', 'm.folder_id')
                            ->whereNull('sf.deleted_at')
                            ->where(function ($inner) use ($like) {
                                $inner->where('sf.folder_name', 'like', $like)
                                    ->orWhere('sf.folder_path', 'like', $like);
                            });
                    });
            });
        }

        $materials = $baseQuery
            ->select([
                'm.id',
                'm.local_id',
                'm.material_name',
                'm.file_format',
                'm.rating',
                'm.material_type',
                'm.designer_id',
                'm.creator_id',
                'm.folder_id',
                'm.source',
                'm.audit_status',
                'm.reject_reason',
                'm.meta_tag',
                'm.mindworks_locked',
            ])
            ->orderBy('m.create_time', 'desc')
            ->take($limit)
            ->get();

        $materialIds = $materials->pluck('id')->all();
        $tagsMap = [];
        if (!empty($materialIds)) {
            $tagRows = DB::table('meta_material_tags as mt')
                ->join('meta_tags as t', 't.id', '=', 'mt.tag_id')
                ->whereIn('mt.material_id', $materialIds)
                ->select(['mt.material_id', 't.name as tag_name'])
                ->get();

            foreach ($tagRows as $tr) {
                $mid = (string) $tr->material_id;
                if (!isset($tagsMap[$mid])) $tagsMap[$mid] = [];
                $tagsMap[$mid][] = $tr->tag_name;
            }
        }

        // 生成 CSV
        $fp = fopen('php://temp', 'r+');
        $headers = [
            'id',
            'local_id',
            'material_name',
            'tags',
            'material_type',
            'audit_status',
            'reject_reason',
            'designer_id',
            'creator_id',
            'folder_id',
            'source',
            'xmp_tag',
            'mindworks_locked',
        ];
        fputcsv($fp, $headers);

        foreach ($materials as $m) {
            $mid = (string) $m->id;
            fputcsv($fp, [
                $m->id,
                $m->local_id,
                $m->material_name,
                implode(';', $tagsMap[$mid] ?? []),
                $m->material_type,
                $m->audit_status,
                $m->reject_reason,
                $m->designer_id,
                $m->creator_id,
                $m->folder_id,
                $m->source,
                $m->meta_tag,
                (int) ($m->mindworks_locked ?? 0),
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp) ?: '';
        fclose($fp);

        $filename = 'xmp-materials-' . now()->format('Ymd_His') . '.csv';

        return response()->json([
            'success' => true,
            'data' => [
                'filename' => $filename,
                'csv' => $csv,
            ],
        ]);
    }

    /**
     * 7.11 投放数据/统计
     * GET /material-library/materials/statistics
     */
    public function statistics(Request $request)
    {
        $materialIdsRaw = $request->query('material_ids', []);
        if (is_string($materialIdsRaw) && $materialIdsRaw !== '') {
            $materialIdsRaw = [$materialIdsRaw];
        }
        if (!is_array($materialIdsRaw)) $materialIdsRaw = [];
        $materialIds = array_values(array_map(static fn($v) => (int) $v, $materialIdsRaw));

        $startDate = trim((string) $request->query('start_date', ''));
        $endDate = trim((string) $request->query('end_date', ''));
        if ($startDate === '' || $endDate === '') {
            $endDate = now()->toDateString();
            $startDate = now()->subDays(6)->toDateString();
        }

        if (empty($materialIds)) {
            return response()->json([
                'data' => [],
                'totalCount' => 0,
            ]);
        }

        $rows = DB::table('meta_material_statistics as st')
            ->whereIn('st.material_id', $materialIds)
            ->where('st.statistics_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('st.statistics_end_date')
                    ->orWhere('st.statistics_end_date', '>=', $startDate);
            })
            ->select([
                'st.material_id',
                DB::raw('SUM(COALESCE(st.spend,0)) as spend_sum'),
                DB::raw('SUM(COALESCE(st.impressions,0)) as impressions_sum'),
                DB::raw('SUM(COALESCE(st.clicks,0)) as clicks_sum'),
                DB::raw('SUM(COALESCE(st.conversions,0)) as conversions_sum'),
            ])
            ->groupBy('st.material_id')
            ->get();

        $data = $rows->map(function ($r) {
            $spend = (float) $r->spend_sum;
            $impressions = (int) $r->impressions_sum;
            $clicks = (int) $r->clicks_sum;
            $conversions = (int) $r->conversions_sum;
            $cpm = $impressions > 0 ? $spend / ($impressions / 1000) : 0;
            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
            $cpc = $clicks > 0 ? $spend / $clicks : 0;
            $cpa = $conversions > 0 ? $spend / $conversions : 0;

            return [
                'material_id' => (string) $r->material_id,
                'cost' => $spend,
                'spend' => $spend,
                'impressions' => $impressions,
                'cpm' => $cpm,
                'clicks' => $clicks,
                'ctr' => $ctr,
                'conversions' => $conversions,
                'cpc' => $cpc,
                'cpa' => $cpa,
            ];
        });

        return response()->json([
            'data' => $data,
            'totalCount' => $data->count(),
        ]);
    }

    /**
     * 7.12 XMP 标签自动更新（MVP：基于 spend/impressions 简化判定）
     * POST /material-library/materials/auto-update-xmp-tags
     */
    public function autoUpdateXmpTags(Request $request)
    {
        $startDate = trim((string) $request->input('start_date', ''));
        $endDate = trim((string) $request->input('end_date', ''));
        if ($startDate === '' || $endDate === '') {
            $endDate = now()->toDateString();
            $startDate = now()->subDays(6)->toDateString();
        }

        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();
        $days = (int) $start->diffInDays($end) + 1;

        $prevStart = (clone $start)->subDays($days);
        $prevEnd = (clone $end)->subDays($days);

        $prevStartDate = $prevStart->toDateString();
        $prevEndDate = $prevEnd->toDateString();

        // 当前范围内有统计的素材
        $statsMaterialIds = DB::table('meta_material_statistics')
            ->where('statistics_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('statistics_end_date')
                    ->orWhere('statistics_end_date', '>=', $startDate);
            })
            ->pluck('material_id')
            ->unique()
            ->values()
            ->all();

        // 当前范围内新建素材（即使没统计也要打“近7天新素材”）
        $createdMaterialIds = DB::table('meta_materials')
            ->whereNull('deleted_at')
            ->whereDate('create_time', '>=', $startDate)
            ->whereDate('create_time', '<=', $endDate)
            ->pluck('id')
            ->values()
            ->all();

        $materialIds = array_values(array_unique(array_merge($statsMaterialIds, $createdMaterialIds)));
        if (empty($materialIds)) {
            return response()->json([
                'success' => true,
                'data' => ['updated_count' => 0],
            ]);
        }

        $currStats = DB::table('meta_material_statistics as st')
            ->whereIn('st.material_id', $materialIds)
            ->where('st.statistics_date', '<=', $endDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('st.statistics_end_date')
                    ->orWhere('st.statistics_end_date', '>=', $startDate);
            })
            ->select([
                'st.material_id',
                DB::raw('SUM(COALESCE(st.spend,0)) as spend_sum'),
                DB::raw('SUM(COALESCE(st.impressions,0)) as impressions_sum'),
                DB::raw('SUM(COALESCE(st.clicks,0)) as clicks_sum'),
                DB::raw('SUM(COALESCE(st.conversions,0)) as conversions_sum'),
            ])
            ->groupBy('st.material_id')
            ->get();

        $prevStats = DB::table('meta_material_statistics as st')
            ->whereIn('st.material_id', $materialIds)
            ->where('st.statistics_date', '<=', $prevEndDate)
            ->where(function ($q) use ($prevStartDate) {
                $q->whereNull('st.statistics_end_date')
                    ->orWhere('st.statistics_end_date', '>=', $prevStartDate);
            })
            ->select([
                'st.material_id',
                DB::raw('SUM(COALESCE(st.spend,0)) as spend_sum'),
                DB::raw('SUM(COALESCE(st.impressions,0)) as impressions_sum'),
                DB::raw('SUM(COALESCE(st.clicks,0)) as clicks_sum'),
                DB::raw('SUM(COALESCE(st.conversions,0)) as conversions_sum'),
            ])
            ->groupBy('st.material_id')
            ->get();

        $currMap = [];
        foreach ($currStats as $r) {
            $currMap[(int) $r->material_id] = [
                'spend' => (float) $r->spend_sum,
                'impressions' => (int) $r->impressions_sum,
                'clicks' => (int) $r->clicks_sum,
                'conversions' => (int) $r->conversions_sum,
            ];
        }

        $prevMap = [];
        foreach ($prevStats as $r) {
            $prevMap[(int) $r->material_id] = [
                'spend' => (float) $r->spend_sum,
                'impressions' => (int) $r->impressions_sum,
                'clicks' => (int) $r->clicks_sum,
                'conversions' => (int) $r->conversions_sum,
            ];
        }

        $createdSet = array_flip(array_map(static fn($v) => (int) $v, $createdMaterialIds));

        $materials = DB::table('meta_materials')
            ->whereIn('id', $materialIds)
            ->whereNull('deleted_at')
            ->select(['id', 'source', 'create_time'])
            ->get();

        $updated = 0;
        DB::beginTransaction();
        try {
            foreach ($materials as $m) {
                $mid = (int) $m->id;
                $curr = $currMap[$mid] ?? ['spend' => 0.0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0];
                $prev = $prevMap[$mid] ?? ['spend' => 0.0, 'impressions' => 0, 'clicks' => 0, 'conversions' => 0];

                $source = strtolower((string) ($m->source ?? ''));
                $isYoutube = strpos($source, 'youtube') !== false;
                $isNew = isset($createdSet[$mid]);

                // YouTube 覆盖
                if ($isYoutube) {
                    $tag = 6;
                } elseif ($isNew && (float) $curr['spend'] <= 0.0 && (int) $curr['impressions'] <= 0) {
                    $tag = 7;
                } else {
                    // 是否有消耗
                    if ((float) $curr['spend'] <= 0.0) {
                        $tag = 4; // 无消耗
                    } else {
                        // 成长/衰退：对比上一段 impressions
                        if ((int) $curr['impressions'] >= (int) $prev['impressions']) {
                            $tag = 1; // 成长期（简化）
                        } else {
                            $tag = 2; // 衰退期（简化）
                        }
                    }
                }

                DB::table('meta_materials')
                    ->where('id', $mid)
                    ->whereNull('deleted_at')
                    ->update(['meta_tag' => $tag]);

                $updated++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'auto update failed',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }

    /**
     * 7.13 媒体素材同步
     * POST /material-library/media-materials/sync
     */
    public function syncMediaMaterials(Request $request)
    {
        $accountId = $request->input('account_id');
        $channel = trim((string) $request->input('channel', ''));
        $folderId = $request->input('materials_folder_id', $request->input('folder_id', null));

        if ($accountId === null || $accountId === '' || $channel === '') {
            return response()->json(['message' => 'account_id and channel are required'], 422);
        }

        $now = now();
        $accountIdInt = (int) $accountId;

        $materialsQuery = DB::table('meta_materials')
            ->whereNull('deleted_at')
            ->where('mindworks_locked', 0);

        if ($folderId !== null && $folderId !== '' && (string) $folderId !== 'favorites') {
            $materialsQuery->where('folder_id', (int) $folderId);
        }

        $materials = $materialsQuery
            ->select(['id', 'material_name', 'file_format', 'source'])
            ->take(5000)
            ->get();

        $materialIds = $materials->pluck('id')->all();
        $totalCount = count($materialIds);

        $syncId = DB::table('meta_media_material_sync')->insertGetId([
            'account_id' => $accountIdInt,
            'channel' => $channel,
            'sync_status' => 1,
            'total_count' => $totalCount,
            'success_count' => 0,
            'fail_count' => 0,
            'sync_time' => $now,
            'error_message' => null,
        ]);

        DB::beginTransaction();
        try {
            // 清理旧数据，避免重复
            DB::table('meta_media_materials')
                ->whereIn('material_id', $materialIds)
                ->where('channel', $channel)
                ->where('belong_account', (string) $accountIdInt)
                ->delete();

            $insertRows = [];
            foreach ($materials as $m) {
                $insertRows[] = [
                    'material_id' => (int) $m->id,
                    'name' => (string) $m->material_name,
                    'channel' => $channel,
                    'use_account' => (string) $accountIdInt,
                    'belong_account' => (string) $accountIdInt,
                    'format' => $m->file_format,
                    'source' => $m->source,
                    'create_time' => $now,
                ];
            }

            $success = 0;
            if (!empty($insertRows)) {
                DB::table('meta_media_materials')->insert($insertRows);
                $success = count($insertRows);
            }

            DB::table('meta_media_material_sync')
                ->where('id', $syncId)
                ->update([
                    'sync_status' => 2,
                    'success_count' => $success,
                    'fail_count' => 0,
                    'error_message' => null,
                ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            DB::table('meta_media_material_sync')
                ->where('id', $syncId)
                ->update([
                    'sync_status' => 3,
                    'success_count' => 0,
                    'fail_count' => $totalCount,
                    'error_message' => $e->getMessage(),
                ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sync_id' => (string) $syncId,
            ],
        ]);
    }

    /**
     * GET /material-library/media-materials/syncs/{syncId}
     */
    public function getMediaMaterialsSyncStatus($syncId)
    {
        $row = DB::table('meta_media_material_sync')
            ->where('id', $syncId)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'sync not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sync_id' => (string) $row->id,
                'account_id' => (int) $row->account_id,
                'channel' => $row->channel,
                'sync_status' => (int) $row->sync_status,
                'total_count' => (int) $row->total_count,
                'success_count' => (int) $row->success_count,
                'fail_count' => (int) $row->fail_count,
                'sync_time' => $row->sync_time,
                'error_message' => $row->error_message,
            ],
        ]);
    }

    /**
     * GET /material-library/media-materials?channel=...&account_id=...
     */
    public function listMediaMaterials(Request $request)
    {
        $channel = trim((string) $request->query('channel', ''));
        $accountId = $request->query('account_id', '');
        $accountIdStr = (string) $accountId;

        $query = DB::table('meta_media_materials as mm')
            ->join('meta_materials as m', 'm.id', '=', 'mm.material_id')
            ->whereNull('m.deleted_at')
            ->select([
                'mm.id',
                'mm.material_id',
                'mm.name',
                'mm.channel',
                'mm.use_account',
                'mm.belong_account',
                'mm.size',
                'mm.duration',
                'mm.shape',
                'mm.format',
                'mm.source',
                'mm.reject_info',
                'mm.material_note',
                'mm.create_time',
            ]);

        if ($channel !== '') {
            $query->where('mm.channel', $channel);
        }
        if ($accountIdStr !== '') {
            $query->where('mm.belong_account', $accountIdStr);
        }

        if ($channel === '' && $accountIdStr === '') {
            return response()->json(['data' => [], 'totalCount' => 0]);
        }

        $totalCount = (clone $query)->count();
        $rows = $query->orderBy('mm.create_time', 'desc')->limit(200)->get();

        $data = $rows->map(function ($r) {
            return [
                'id' => (string) $r->id,
                'materialId' => (string) $r->material_id,
                'name' => $r->name,
                'channel' => $r->channel,
                'useAccount' => $r->use_account,
                'belongAccount' => $r->belong_account,
                'size' => $r->size,
                'duration' => $r->duration,
                'shape' => $r->shape,
                'format' => $r->format,
                'source' => $r->source,
                'rejectInfo' => $r->reject_info,
                'materialNote' => $r->material_note,
                'createTime' => $r->create_time,
            ];
        });

        return response()->json([
            'data' => $data,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * 7.14 使用记录/引用关系
     * GET /material-library/materials/{id}/usages
     */
    public function usages(Request $request, $id)
    {
        $rows = DB::table('meta_material_usages')
            ->where('material_id', (int) $id)
            ->orderBy('used_at', 'desc')
            ->limit(200)
            ->get();

        $data = $rows->map(function ($r) {
            return [
                'usageType' => $r->usage_type,
                'refType' => $r->ref_type,
                'refId' => $r->ref_id,
                'usedAt' => $r->used_at,
                'operatorId' => $r->operator_id,
                'metadata' => $r->metadata,
            ];
        });

        return response()->json([
            'data' => $data,
            'totalCount' => count($data),
        ]);
    }
}
