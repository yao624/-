<?php

namespace App\Http\Controllers;

use App\Models\MetaAdCreationDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class MetaAdCreationDraftController extends Controller
{
    /**
     * 列表：当前用户草稿，支持按标签筛选（同标签便于快速选择）
     */
    public function index(Request $request): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForDrafts();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '草稿表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $userId = Auth::id();
        $query = MetaAdCreationDraft::where('user_id', $userId);
        if ($request->filled('tag')) {
            $query->where('tag', $request->tag);
        }
        if ($request->filled('fb_ad_account_id')) {
            $query->where('fb_ad_account_id', $request->fb_ad_account_id);
        }
        $query->orderBy('updated_at', 'desc');

        $list = $query->get()->map(fn ($d) => [
            'id' => $d->id,
            'fbAdAccountId' => $d->fb_ad_account_id,
            'tag' => $d->tag ?? '',
            'name' => $d->name,
            'formData' => $d->form_data ?? [],
            'metaCounts' => $d->meta_counts ?? [
                'regionGroup' => 1,
                'targeting' => 1,
                'bidBudget' => 1,
                'creativeGroup' => 1,
            ],
            'currentStep' => (int) ($d->current_step ?? 0),
            'updatedAt' => $d->updated_at?->toIso8601String(),
            'updatedAtText' => $d->updated_at?->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $list,
        ]);
    }

    /**
     * 获取用户所有标签（去重），便于筛选
     */
    public function tags(Request $request): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForDrafts();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '草稿表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $userId = Auth::id();
        $tags = MetaAdCreationDraft::where('user_id', $userId)
            ->whereNotNull('tag')
            ->where('tag', '!=', '')
            ->distinct()
            ->pluck('tag')
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * 单条草稿详情
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForDrafts();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '草稿表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $userId = Auth::id();
        $draft = MetaAdCreationDraft::where('user_id', $userId)->where('id', $id)->first();

        if (!$draft) {
            return response()->json(['success' => false, 'message' => __('草稿不存在')], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $draft->id,
                'tag' => $draft->tag ?? '',
                'name' => $draft->name,
                'formData' => $draft->form_data ?? [],
                'metaCounts' => $draft->meta_counts ?? [
                    'regionGroup' => 1,
                    'targeting' => 1,
                    'bidBudget' => 1,
                    'creativeGroup' => 1,
                ],
                'currentStep' => (int) ($draft->current_step ?? 0),
                'updatedAt' => $draft->updated_at?->toIso8601String(),
                'updatedAtText' => $draft->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * 新建草稿（多条之一）
     */
    public function store(Request $request): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForDrafts();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '草稿表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $request->validate([
            'tag' => 'required|string|max:100',
            'name' => 'nullable|string|max:255',
            'form_data' => 'required|array',
            'meta_counts' => 'required|array',
            'meta_counts.regionGroup' => 'nullable|integer|min:1|max:30',
            'meta_counts.targeting' => 'nullable|integer|min:1|max:30',
            'meta_counts.bidBudget' => 'nullable|integer|min:1|max:30',
            'meta_counts.creativeGroup' => 'nullable|integer|min:1|max:30',
            'current_step' => 'nullable|integer|min:0|max:8',
        ]);

        $formData = self::normalizeMetaAdCreationFormData($request->form_data);
        $adAccountId = self::metaAdCreationGetAdAccountId($formData);

        try {
            $draft = MetaAdCreationDraft::create([
                'user_id' => Auth::id(),
                'fb_ad_account_id' => $adAccountId,
                'tag' => $request->tag,
                'name' => $request->name,
                'form_data' => $formData,
                'meta_counts' => $request->meta_counts,
                'current_step' => (int) $request->input('current_step', 0),
            ]);
        } catch (QueryException $e) {
            Log::error('Meta draft create failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('草稿保存失败：') . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $draft->id,
                'tag' => $draft->tag,
                'name' => $draft->name,
                'updatedAt' => $draft->updated_at?->toIso8601String(),
                'updatedAtText' => $draft->updated_at?->format('Y-m-d H:i:s'),
            ],
            'message' => __('草稿已保存'),
        ]);
    }

    /**
     * 更新草稿（覆盖原草稿，便于少量修改后保存）
     */
    public function update(Request $request, string $id): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForDrafts();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '草稿表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $userId = Auth::id();
        $draft = MetaAdCreationDraft::where('user_id', $userId)->where('id', $id)->first();

        if (!$draft) {
            return response()->json(['success' => false, 'message' => __('草稿不存在')], 404);
        }

        $request->validate([
            'tag' => 'sometimes|string|max:100',
            'name' => 'nullable|string|max:255',
            'form_data' => 'required|array',
            'meta_counts' => 'required|array',
            'current_step' => 'nullable|integer|min:0|max:8',
        ]);

        $formData = self::normalizeMetaAdCreationFormData($request->form_data);
        $draft->form_data = $formData;
        $draft->meta_counts = $request->meta_counts;
        $draft->current_step = (int) $request->input('current_step', $draft->current_step);
        $adAccountId = self::metaAdCreationGetAdAccountId($formData);
        $draft->fb_ad_account_id = $adAccountId ?? $draft->fb_ad_account_id;
        if ($request->has('tag')) {
            $draft->tag = $request->tag;
        }
        if ($request->has('name')) {
            $draft->name = $request->name;
        }
        try {
            $draft->save();
        } catch (QueryException $e) {
            Log::error('Meta draft update failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => __('草稿更新失败：') . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $draft->id,
                'updatedAtText' => $draft->updated_at?->format('Y-m-d H:i:s'),
            ],
            'message' => __('草稿已更新'),
        ]);
    }

    /**
     * 删除一条草稿
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! config('meta_ad_creation.skip_schema_guard', true)) {
            $missing = \App\Services\MetaAdCreationSchemaGuard::missingForDrafts();
            if ($missing !== []) {
                return response()->json(['success' => false, 'message' => '草稿表结构不完整：' . implode(', ', $missing)], 500);
            }
        }

        $userId = Auth::id();
        $draft = MetaAdCreationDraft::where('user_id', $userId)->where('id', $id)->first();

        if (!$draft) {
            return response()->json(['success' => false, 'message' => __('草稿不存在')], 404);
        }

        $draft->delete();
        return response()->json(['success' => true, 'message' => __('草稿已删除')]);
    }
}
