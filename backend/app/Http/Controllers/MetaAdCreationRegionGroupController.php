<?php

namespace App\Http\Controllers;

use App\Models\MetaAdCreationRegionGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MetaAdCreationRegionGroupController extends Controller
{
    /**
     * 列表：当前用户的地区组
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if (! $this->hasRegionGroupTable()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $userId = Auth::id();
            $query = MetaAdCreationRegionGroup::where('user_id', $userId);

            $q = trim((string) $request->get('q', ''));
            if ($q !== '') {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where('name', 'like', $like);
            }

            $tag = trim((string) $request->get('tag', ''));
            if ($tag !== '' && $this->hasTagsColumn()) {
                $query->whereJsonContains('tags', $tag);
            }

            $list = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'tags' => $this->hasTagsColumn() ? ($g->tags ?? []) : [],
                    'fbAdAccountIds' => $this->hasFbAdAccountIdsColumn() ? ($g->fb_ad_account_ids ?? []) : [],
                    // 兼容历史字段命名（snake_case）与当前字段命名（camelCase）
                    'countriesIncluded' => $g->countries_included ?? [],
                    'countriesExcluded' => $g->countries_excluded ?? [],
                    'regionsIncluded' => $g->regions_included ?? [],
                    'regionsExcluded' => $g->regions_excluded ?? [],
                    'citiesIncluded' => $g->cities_included ?? [],
                    'citiesExcluded' => $g->cities_excluded ?? [],
                    'countries_included' => $g->countries_included ?? [],
                    'countries_excluded' => $g->countries_excluded ?? [],
                    'regions_included' => $g->regions_included ?? [],
                    'regions_excluded' => $g->regions_excluded ?? [],
                    'cities_included' => $g->cities_included ?? [],
                    'cities_excluded' => $g->cities_excluded ?? [],
                    'createdAt' => $g->created_at?->toIso8601String(),
                    'createdAtText' => $g->created_at?->format('Y-m-d H:i:s'),
                    'created_at' => $g->created_at?->format('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'data' => $list,
            ]);
        } catch (Throwable $e) {
            Log::error('meta-ad-creation-region-groups index failed', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => __('加载地区组列表失败'),
            ]);
        }
    }
    private function hasRegionGroupTable(): bool
    {
        try {
            $model = new MetaAdCreationRegionGroup;
            $table = $model->getTable();
            $name = $model->getConnection()->getName();

            return Schema::connection($name)->hasTable($table);
        } catch (Throwable $e) {
            Log::warning('meta-ad-creation-region-groups table check failed', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }


    /**
     * 将前端或历史数据中的地区项统一为 [{ key, name }, ...]，兼容 code / id 等别名。
     *
     * @param  mixed  $items
     * @return array<int, array{key: string, name: string}>
     */
    private function normalizeGeoItems($items): array
    {
        if (! is_array($items)) {
            return [];
        }
        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $key = $item['key'] ?? $item['code'] ?? $item['id'] ?? null;
            if ($key === null || $key === '') {
                continue;
            }
            $key = (string) $key;
            $name = $item['name'] ?? $item['label'] ?? '';
            $name = $name !== '' ? (string) $name : $key;
            $out[] = ['key' => $key, 'name' => $name];
        }

        return $out;
    }

    /**
     * 租户库若未执行 regions_* 迁移，表中无此列，写入会导致 500；仅当列存在时再持久化大区字段。
     */
    private function hasGeoRegionColumns(): bool
    {
        if (! $this->hasRegionGroupTable()) {
            return false;
        }
        $model = new MetaAdCreationRegionGroup;
        $table = $model->getTable();
        $name = $model->getConnection()->getName();

        return Schema::connection($name)->hasColumn($table, 'regions_included')
            && Schema::connection($name)->hasColumn($table, 'regions_excluded');
    }

    private function hasGeoCityColumns(): bool
    {
        if (! $this->hasRegionGroupTable()) {
            return false;
        }
        $model = new MetaAdCreationRegionGroup;
        $table = $model->getTable();
        $name = $model->getConnection()->getName();

        return Schema::connection($name)->hasColumn($table, 'cities_included')
            && Schema::connection($name)->hasColumn($table, 'cities_excluded');
    }

    private function hasFbAdAccountIdsColumn(): bool
    {
        if (! $this->hasRegionGroupTable()) {
            return false;
        }
        $model = new MetaAdCreationRegionGroup;
        $table = $model->getTable();
        $name = $model->getConnection()->getName();

        return Schema::connection($name)->hasColumn($table, 'fb_ad_account_ids');
    }

    private function hasTagsColumn(): bool
    {
        if (! $this->hasRegionGroupTable()) {
            return false;
        }
        $model = new MetaAdCreationRegionGroup;
        $table = $model->getTable();
        $name = $model->getConnection()->getName();

        return Schema::connection($name)->hasColumn($table, 'tags');
    }

    /**
     * @param  mixed  $input
     * @return array<int, string>
     */
    private function normalizeTags($input): array
    {
        if (! is_array($input)) {
            return [];
        }
        $out = [];
        foreach ($input as $item) {
            if (! is_string($item) && ! is_numeric($item)) {
                continue;
            }
            $s = trim((string) $item);
            if ($s === '') {
                continue;
            }
            if (mb_strlen($s) > 64) {
                $s = mb_substr($s, 0, 64);
            }
            $out[$s] = true;
        }

        return array_slice(array_keys($out), 0, 20);
    }

    /**
     * @param  mixed  $ids
     * @return array<int, string>
     */
    private function normalizeFbAdAccountIds($ids): array
    {
        if (! is_array($ids)) {
            return [];
        }
        $out = [];
        foreach ($ids as $id) {
            if (! is_string($id) && ! is_numeric($id)) {
                continue;
            }
            $s = trim((string) $id);
            if ($s === '') {
                continue;
            }
            $out[$s] = true;
        }

        return array_keys($out);
    }

    /**
     * 新建地区组
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'countries_included' => $this->normalizeGeoItems($request->input('countries_included')),
            'countries_excluded' => $this->normalizeGeoItems($request->input('countries_excluded')),
            'regions_included' => $this->normalizeGeoItems($request->input('regions_included')),
            'regions_excluded' => $this->normalizeGeoItems($request->input('regions_excluded')),
            'cities_included' => $this->normalizeGeoItems($request->input('cities_included')),
            'cities_excluded' => $this->normalizeGeoItems($request->input('cities_excluded')),
            'fb_ad_account_ids' => $this->normalizeFbAdAccountIds($request->input('fb_ad_account_ids')),
            'tags' => $this->normalizeTags($request->input('tags')),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:64',
            'fb_ad_account_ids' => 'nullable|array',
            'fb_ad_account_ids.*' => 'string|max:64',
            'countries_included' => 'nullable|array',
            'countries_included.*.key' => 'required|string',
            'countries_included.*.name' => 'nullable|string',
            'countries_excluded' => 'nullable|array',
            'countries_excluded.*.key' => 'required|string',
            'countries_excluded.*.name' => 'nullable|string',
            'regions_included' => 'nullable|array',
            'regions_included.*.key' => 'required|string',
            'regions_included.*.name' => 'nullable|string',
            'regions_excluded' => 'nullable|array',
            'regions_excluded.*.key' => 'required|string',
            'regions_excluded.*.name' => 'nullable|string',
            'cities_included' => 'nullable|array',
            'cities_included.*.key' => 'required|string',
            'cities_included.*.name' => 'nullable|string',
            'cities_excluded' => 'nullable|array',
            'cities_excluded.*.key' => 'required|string',
            'cities_excluded.*.name' => 'nullable|string',
        ]);

        $payload = [
            'user_id' => Auth::id(),
            'name' => $request->name,
            'countries_included' => $request->countries_included ?? [],
            'countries_excluded' => $request->countries_excluded ?? [],
        ];
        if ($this->hasFbAdAccountIdsColumn()) {
            $payload['fb_ad_account_ids'] = $request->input('fb_ad_account_ids', []);
        }
        if ($this->hasGeoRegionColumns()) {
            $payload['regions_included'] = $request->regions_included ?? [];
            $payload['regions_excluded'] = $request->regions_excluded ?? [];
        }
        if ($this->hasGeoCityColumns()) {
            $payload['cities_included'] = $request->cities_included ?? [];
            $payload['cities_excluded'] = $request->cities_excluded ?? [];
        }
        if ($this->hasTagsColumn()) {
            $payload['tags'] = $request->input('tags', []);
        }

        $g = MetaAdCreationRegionGroup::create($payload);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $g->id,
                'name' => $g->name,
                'tags' => $this->hasTagsColumn() ? ($g->tags ?? []) : [],
                'fbAdAccountIds' => $this->hasFbAdAccountIdsColumn() ? ($g->fb_ad_account_ids ?? []) : [],
                'countriesIncluded' => $g->countries_included ?? [],
                'countriesExcluded' => $g->countries_excluded ?? [],
                'regionsIncluded' => $g->regions_included ?? [],
                'regionsExcluded' => $g->regions_excluded ?? [],
                'citiesIncluded' => $g->cities_included ?? [],
                'citiesExcluded' => $g->cities_excluded ?? [],
                'createdAt' => $g->created_at?->toIso8601String(),
                'createdAtText' => $g->created_at?->format('Y-m-d H:i:s'),
            ],
            'message' => __('地区组已保存'),
        ]);
    }

    /**
     * 单条详情
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $userId = Auth::id();
        $g = MetaAdCreationRegionGroup::where('user_id', $userId)->where('id', $id)->first();

        if (!$g) {
            return response()->json(['success' => false, 'message' => __('地区组不存在')], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $g->id,
                'name' => $g->name,
                'tags' => $this->hasTagsColumn() ? ($g->tags ?? []) : [],
                'fbAdAccountIds' => $this->hasFbAdAccountIdsColumn() ? ($g->fb_ad_account_ids ?? []) : [],
                'countriesIncluded' => $g->countries_included ?? [],
                'countriesExcluded' => $g->countries_excluded ?? [],
                'regionsIncluded' => $g->regions_included ?? [],
                'regionsExcluded' => $g->regions_excluded ?? [],
                'citiesIncluded' => $g->cities_included ?? [],
                'citiesExcluded' => $g->cities_excluded ?? [],
                'createdAt' => $g->created_at?->toIso8601String(),
                'createdAtText' => $g->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * 更新地区组
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $userId = Auth::id();
        $g = MetaAdCreationRegionGroup::where('user_id', $userId)->where('id', $id)->first();

        if (!$g) {
            return response()->json(['success' => false, 'message' => __('地区组不存在')], 404);
        }

        $request->merge([
            'countries_included' => $this->normalizeGeoItems($request->input('countries_included')),
            'countries_excluded' => $this->normalizeGeoItems($request->input('countries_excluded')),
            'regions_included' => $this->normalizeGeoItems($request->input('regions_included')),
            'regions_excluded' => $this->normalizeGeoItems($request->input('regions_excluded')),
            'cities_included' => $this->normalizeGeoItems($request->input('cities_included')),
            'cities_excluded' => $this->normalizeGeoItems($request->input('cities_excluded')),
            'fb_ad_account_ids' => $this->normalizeFbAdAccountIds($request->input('fb_ad_account_ids')),
            'tags' => $this->normalizeTags($request->input('tags')),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:64',
            'fb_ad_account_ids' => 'nullable|array',
            'fb_ad_account_ids.*' => 'string|max:64',
            'countries_included' => 'nullable|array',
            'countries_included.*.key' => 'required|string',
            'countries_included.*.name' => 'nullable|string',
            'countries_excluded' => 'nullable|array',
            'countries_excluded.*.key' => 'required|string',
            'countries_excluded.*.name' => 'nullable|string',
            'regions_included' => 'nullable|array',
            'regions_included.*.key' => 'required|string',
            'regions_included.*.name' => 'nullable|string',
            'regions_excluded' => 'nullable|array',
            'regions_excluded.*.key' => 'required|string',
            'regions_excluded.*.name' => 'nullable|string',
            'cities_included' => 'nullable|array',
            'cities_included.*.key' => 'required|string',
            'cities_included.*.name' => 'nullable|string',
            'cities_excluded' => 'nullable|array',
            'cities_excluded.*.key' => 'required|string',
            'cities_excluded.*.name' => 'nullable|string',
        ]);

        $payload = [
            'name' => $request->name,
            'countries_included' => $request->countries_included ?? [],
            'countries_excluded' => $request->countries_excluded ?? [],
        ];
        if ($this->hasFbAdAccountIdsColumn()) {
            $payload['fb_ad_account_ids'] = $request->input('fb_ad_account_ids', []);
        }
        if ($this->hasGeoRegionColumns()) {
            $payload['regions_included'] = $request->regions_included ?? [];
            $payload['regions_excluded'] = $request->regions_excluded ?? [];
        }
        if ($this->hasGeoCityColumns()) {
            $payload['cities_included'] = $request->cities_included ?? [];
            $payload['cities_excluded'] = $request->cities_excluded ?? [];
        }
        if ($this->hasTagsColumn()) {
            $payload['tags'] = $request->input('tags', []);
        }

        $g->update($payload);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $g->id,
                'name' => $g->name,
                'tags' => $this->hasTagsColumn() ? ($g->tags ?? []) : [],
                'fbAdAccountIds' => $this->hasFbAdAccountIdsColumn() ? ($g->fb_ad_account_ids ?? []) : [],
                'countriesIncluded' => $g->countries_included ?? [],
                'countriesExcluded' => $g->countries_excluded ?? [],
                'regionsIncluded' => $g->regions_included ?? [],
                'regionsExcluded' => $g->regions_excluded ?? [],
                'citiesIncluded' => $g->cities_included ?? [],
                'citiesExcluded' => $g->cities_excluded ?? [],
                'createdAt' => $g->created_at?->toIso8601String(),
                'createdAtText' => $g->created_at?->format('Y-m-d H:i:s'),
            ],
            'message' => __('地区组已更新'),
        ]);
    }

    /**
     * 删除地区组
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $userId = Auth::id();
        $g = MetaAdCreationRegionGroup::where('user_id', $userId)->where('id', $id)->first();

        if (!$g) {
            return response()->json(['success' => false, 'message' => __('地区组不存在')], 404);
        }

        $g->delete();
        return response()->json(['success' => true, 'message' => __('已删除')]);
    }
}
