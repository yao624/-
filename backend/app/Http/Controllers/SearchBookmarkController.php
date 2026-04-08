<?php

namespace App\Http\Controllers;

use App\Http\Resources\SearchBookmarkResource;
use App\Models\SearchBookmark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SearchBookmarkController extends BaseController
{
    /**
     * 获取当前用户的搜索书签列表
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $bookmarks = SearchBookmark::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'success' => true,
            'data' => SearchBookmarkResource::collection($bookmarks),
            'message' => '获取书签成功'
        ];
    }

    /**
     * 创建新的搜索书签
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'search_conditions' => 'required|array',
            'description' => 'nullable|string|max:1000'
        ]);

        $user = $request->user();

        // 检查用户是否已经有相同名称的书签
        $existingBookmark = SearchBookmark::where('user_id', $user->id)
            ->where('name', $request->name)
            ->first();

        if ($existingBookmark) {
            return response()->json([
                'success' => false,
                'message' => '书签名称已存在'
            ], 422);
        }

        $bookmark = SearchBookmark::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'search_conditions' => $request->search_conditions,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'data' => new SearchBookmarkResource($bookmark),
            'message' => '书签创建成功'
        ]);
    }

    /**
     * 显示指定的搜索书签
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $bookmark = SearchBookmark::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$bookmark) {
            return response()->json([
                'success' => false,
                'message' => '书签不存在'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SearchBookmarkResource($bookmark),
            'message' => '获取书签成功'
        ]);
    }

    /**
     * 删除指定的搜索书签
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $bookmark = SearchBookmark::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$bookmark) {
            return response()->json([
                'success' => false,
                'message' => '书签不存在'
            ], 404);
        }

        $bookmark->delete();

        return response()->json([
            'success' => true,
            'message' => '书签删除成功'
        ]);
    }
}
