<?php

namespace App\Http\Controllers;

use App\Models\MetaAdCreationCreativeGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetaAdCreationCreativeGroupController extends Controller
{
    private function toResponseItem(MetaAdCreationCreativeGroup $g): array
    {
        return [
            'id' => $g->id,
            'name' => $g->name,
            'creativeType' => $g->creative_type ?? 'create',
            'materialIds' => $g->material_ids ?? [],
            'materials' => $g->materials ?? [],
            'postIds' => $g->post_ids ?? [],
            'format' => $g->format ?? 'single',
            'settingMode' => $g->setting_mode ?? 'by_group',
            'deepLink' => $g->deep_link ?? '',
            'body' => $g->body ?? '',
            'title' => $g->title ?? '',
            'cta' => $g->cta ?? null,
            'tags' => $g->tags ?? null,
            'videoOptimization' => $g->video_optimization ?? null,
            'imageOptimization' => $g->image_optimization ?? null,
            'multilang' => (bool) ($g->multilang ?? false),
            'createdAt' => $g->created_at?->toIso8601String(),
            'createdAtText' => $g->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 列表：当前用户的创意组
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $list = MetaAdCreationCreativeGroup::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($g) => $this->toResponseItem($g));

        return response()->json([
            'success' => true,
            'data' => $list,
        ]);
    }

    /**
     * 新建创意组
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'creative_type' => 'nullable|string|in:create,post',
            'material_ids' => 'nullable|array',
            'material_ids.*' => 'nullable|string',
            'materials' => 'nullable|array',
            'post_ids' => 'nullable|array',
            'post_ids.*' => 'nullable|string',
            'format' => 'nullable|string|in:flexible,single,carousel',
            'setting_mode' => 'nullable|string|in:by_group,by_material',
            'deep_link' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'title' => 'nullable|string|max:500',
            'cta' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'video_optimization' => 'nullable|string|max:50',
            'image_optimization' => 'nullable|string|max:50',
            'multilang' => 'nullable|boolean',
        ]);

        $g = MetaAdCreationCreativeGroup::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'creative_type' => $request->creative_type ?? 'create',
            'material_ids' => $request->material_ids ?? [],
            'materials' => $request->materials ?? [],
            'post_ids' => $request->post_ids ?? [],
            'format' => $request->format ?? 'single',
            'setting_mode' => $request->setting_mode ?? 'by_group',
            'deep_link' => $request->deep_link ?? '',
            'body' => $request->body ?? '',
            'title' => $request->title ?? '',
            'cta' => $request->cta ?? null,
            'tags' => $request->tags ?? null,
            'video_optimization' => $request->video_optimization ?? null,
            'image_optimization' => $request->image_optimization ?? null,
            'multilang' => (bool) ($request->multilang ?? false),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->toResponseItem($g),
            'message' => __('创意组已保存'),
        ]);
    }

    /**
     * 单条详情
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $userId = Auth::id();
        $g = MetaAdCreationCreativeGroup::where('user_id', $userId)->where('id', $id)->first();

        if (!$g) {
            return response()->json(['success' => false, 'message' => __('创意组不存在')], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->toResponseItem($g),
        ]);
    }

    /**
     * 更新创意组
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $userId = Auth::id();
        $g = MetaAdCreationCreativeGroup::where('user_id', $userId)->where('id', $id)->first();

        if (!$g) {
            return response()->json(['success' => false, 'message' => __('创意组不存在')], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'creative_type' => 'nullable|string|in:create,post',
            'material_ids' => 'nullable|array',
            'material_ids.*' => 'nullable|string',
            'materials' => 'nullable|array',
            'post_ids' => 'nullable|array',
            'post_ids.*' => 'nullable|string',
            'format' => 'nullable|string|in:flexible,single,carousel',
            'setting_mode' => 'nullable|string|in:by_group,by_material',
            'deep_link' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'title' => 'nullable|string|max:500',
            'cta' => 'nullable|string|max:50',
            'tags' => 'nullable|array',
            'video_optimization' => 'nullable|string|max:50',
            'image_optimization' => 'nullable|string|max:50',
            'multilang' => 'nullable|boolean',
        ]);

        $g->update([
            'name' => $request->name,
            'creative_type' => $request->creative_type ?? $g->creative_type,
            'material_ids' => $request->material_ids ?? $g->material_ids,
            'materials' => $request->materials ?? $g->materials,
            'post_ids' => $request->post_ids ?? $g->post_ids,
            'format' => $request->format ?? $g->format,
            'setting_mode' => $request->setting_mode ?? $g->setting_mode,
            'deep_link' => $request->deep_link ?? $g->deep_link,
            'body' => $request->body ?? $g->body,
            'title' => $request->title ?? $g->title,
            'cta' => $request->cta ?? $g->cta,
            'tags' => $request->tags ?? $g->tags,
            'video_optimization' => $request->video_optimization ?? $g->video_optimization,
            'image_optimization' => $request->image_optimization ?? $g->image_optimization,
            'multilang' => isset($request->multilang) ? (bool) $request->multilang : $g->multilang,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->toResponseItem($g),
            'message' => __('创意组已更新'),
        ]);
    }

    /**
     * 删除创意组
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $userId = Auth::id();
        $g = MetaAdCreationCreativeGroup::where('user_id', $userId)->where('id', $id)->first();

        if (!$g) {
            return response()->json(['success' => false, 'message' => __('创意组不存在')], 404);
        }

        $g->delete();
        return response()->json(['success' => true, 'message' => __('已删除')]);
    }
}
