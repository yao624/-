<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * 模板 CRUD 使用 DB 查询，避免生产未部署 MetaAdCreationTemplate 模型时报错。
 */
class MetaAdCreationTemplateController extends Controller
{
    private const TABLE = 'meta_ad_creation_templates';

    /**
     * @return array<string, mixed>
     */
    private static function jsonColumnToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 列表：当前用户的 Meta 广告创建模板
     */
    public function index(Request $request): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForTemplates();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '模板表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $userId = Auth::id();
        $query = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNull('deleted_at');
        if ($request->filled('fb_ad_account_id')) {
            $query->where('fb_ad_account_id', $request->fb_ad_account_id);
        }
        $list = $query->orderByDesc('created_at')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'fbAdAccountId' => $t->fb_ad_account_id,
                'name' => $t->name,
                'formData' => self::jsonColumnToArray($t->form_data),
                'metaCounts' => self::jsonColumnToArray($t->meta_counts),
                'createdAt' => $t->created_at ? Carbon::parse($t->created_at)->toIso8601String() : null,
                'createdAtText' => $t->created_at ? Carbon::parse($t->created_at)->format('Y-m-d H:i:s') : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $list,
        ]);
    }

    /**
     * 保存模板
     */
    public function store(Request $request): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForTemplates();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '模板表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'form_data' => 'required|array',
            'meta_counts' => 'required|array',
            'meta_counts.regionGroup' => 'nullable|integer|min:1|max:30',
            'meta_counts.targeting' => 'nullable|integer|min:1|max:30',
            'meta_counts.bidBudget' => 'nullable|integer|min:1|max:30',
            'meta_counts.creativeGroup' => 'nullable|integer|min:1|max:30',
        ]);

        $formData = self::normalizeMetaAdCreationFormData($request->form_data);
        $adAccountId = self::metaAdCreationGetAdAccountId($formData);

        $id = (string) Str::ulid();
        $now = now();

        try {
            DB::table(self::TABLE)->insert([
                'id' => $id,
                'user_id' => Auth::id(),
                'fb_ad_account_id' => $adAccountId,
                'name' => $request->name,
                'form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'meta_counts' => json_encode($request->meta_counts, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        } catch (QueryException $e) {
            Log::error('Meta template save failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('模板保存失败：') . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $id,
                'name' => $request->name,
                'formData' => $formData,
                'metaCounts' => $request->meta_counts,
                'createdAt' => $now->toIso8601String(),
                'createdAtText' => $now->format('Y-m-d H:i:s'),
            ],
            'message' => __('模板已保存'),
        ]);
    }

    /**
     * 单条详情（用于「使用」模板时拉取）
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForTemplates();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '模板表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $userId = Auth::id();
        $t = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (! $t) {
            return response()->json(['success' => false, 'message' => __('模板不存在')], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $t->id,
                'name' => $t->name,
                'formData' => self::jsonColumnToArray($t->form_data),
                'metaCounts' => self::jsonColumnToArray($t->meta_counts),
                'createdAt' => $t->created_at ? Carbon::parse($t->created_at)->toIso8601String() : null,
                'createdAtText' => $t->created_at ? Carbon::parse($t->created_at)->format('Y-m-d H:i:s') : null,
            ],
        ]);
    }

    /**
     * 删除模板
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $userId = Auth::id();
        $affected = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            return response()->json(['success' => false, 'message' => __('模板不存在')], 404);
        }

        return response()->json(['success' => true, 'message' => __('已删除')]);
    }
}
