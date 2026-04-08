<?php

namespace App\Http\Controllers;

use App\Http\Resources\LinkResource;
use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->query('tags', []);

        $searchableFields = [
            'link' => $request->get('link'),
            'notes' => $request->get('notes'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];
        if ($request->get('name')) {
            $searchableFields['notes'] = $request->get('name');
        }

//        $admin = auth()->user()->hasRole('admin');
//
//        if ($admin) {
//            // 管理员的话，返回所有的 tag, 非管理员，只返回它自己有权限的 tag
//            $links = Link::searchByTagNames($tagNames)->search($searchableFields)->with('tags');
//        } else {
//            $links = Link::searchByTagNames($tagNames)->search($searchableFields)
//                ->where('user_id', auth()->id())->with(['tags' => function ($query) {
//                $query->wherePivot('user_id', auth()->id());
//            }]);
//        }
//        $links = $links->orderBy($sortField, $sortDirection)
//            ->with('sharedWith')
//            ->where(function ($query) {
//                $query->where('user_id', auth()->id()) // 当前用户创建的 Material
//                ->orWhereHas('sharedWith', function ($subQuery) {
//                    $subQuery->where('user_id', auth()->id()); // 被分享给当前用户的 Material
//                });
//            })
//            ->orderBy('id', $sortDirection)
//            ->paginate($pageSize, ['*'], 'page', $pageNo);

        $admin = auth()->user()->hasRole('admin');

        $linksQuery = Link::searchByTagNames($tagNames)->search($searchableFields);

//        // 如果用户是管理员，则返回所有 Link；否则，返回自己创建的和分享给自己的 Link
//        if (!$admin) {
//            // 查询自己创建的 Link
//            $linksQuery->where('user_id', auth()->id());
//        }
        $linksQuery->where('user_id', auth()->id());

        // 现在添加条件以包括分享给该用户的链接
        $linksQuery->orWhereHas('sharedWith', function ($subQuery) {
            $subQuery->where('user_id', auth()->id());
        });

        // 继续链式调用以获取链接的标签并排序
        $links = $linksQuery->with(['tags' => function ($query) {
            $query->wherePivot('user_id', auth()->id());
        }])
            ->with('sharedWith')
            ->orderBy($sortField, $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => LinkResource::collection($links->items()),
            'pageSize' => $links->perPage(),
            'pageNo' => $links->currentPage(),
            'totalPage' => $links->lastPage(),
            'totalCount' => $links->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store link');

        $userID = auth()->user()->id;
        $request->validate([
            'link' => [
                'required',
                Rule::unique('links')->where(function ($query) use ($userID) {
                    return $query->where('user_id', $userID)
                        ->whereNull('deleted_at');
                }),
            ],
            'new_tags' => [
                'sometimes', // 只有当 new_tags 存在时才应用后续的规则
                'array',     // new_tags 必须是一个数组
            ],
            'new_tags.*' => [
                'string',    // new_tags 数组中的每个元素必须是字符串
                'distinct',  // new_tags 数组中的每个元素必须是唯一的
                Rule::notIn(Tag::pluck('name')->toArray()), // new_tags 数组中的每个元素不能在 Tag 模型的 name 字段值中
            ],
            'notes' => 'string|nullable',
            'tags' => 'array|distinct|nullable'
        ]);

        $link = Link::create([
            'link' => $request->input('link'),
            'notes' => $request->input('notes', ''),
            'user_id' => auth()->id(),
        ]);

        // 如果 tag 已经存在，就直接attach, 如果 tag 不存在，先要创建 tag, 再attach
        $tagNames = collect($request->get('tags', []))->unique();
        $existingTags = Tag::query()->where('user_id', $userID)->whereHas('links')->whereIn('name', $tagNames)->pluck('name');
        $newTags = $tagNames->diff($existingTags);

        foreach ($existingTags as $tagName) {
            $tag = Tag::query()->where('user_id', $userID)->whereHas('links')->where('name', '=' , $tagName)->first();
            $link->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        foreach ($newTags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName, 'user_id' => $userID]);
            $link->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        $link->load('tags');

        return new LinkResource($link);
    }

    /**
     * Display the specified resource.
     */
    public function show(Link $link)
    {
        // 获取当前用户的 ID
        $currentUserId = auth()->id();

        // 检查当前用户是否是材料的创建者或被分享的用户
        if ($link->user_id !== $currentUserId && !$link->isSharedWith($currentUserId)) {
            // 如果用户没有权限，返回403 Forbidden
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $link->load('tags');
        return new LinkResource($link);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Link $link)
    {
        $userID = auth()->user()->id;

        // 检查当前用户是否为材料的创建者
        if ($link->user_id !== auth()->id()) {
            // 如果用户没有权限，返回403 Forbidden
            return response()->json(['message' => 'Forbidden'], 403);
        }
        // 验证请求数据
        $validatedData = $request->validate([
            'link' => [
                'required',
                Rule::unique('links')->ignore($link->id)->where(function ($query) use ($userID) {
                    return $query->where('user_id', $userID)
                        ->whereNull('deleted_at');
                })
            ],
            'notes' => 'nullable|string',
//            'tag_ids' => 'nullable|string',
//            'new_tags' => 'nullable'
            'tags' => 'array|distinct|nullable'
        ]);

        // 更新模型实例
        $link->update($validatedData);

        // 如果 tag 已经存在，就直接attach, 如果 tag 不存在，先要创建 tag, 再attach
        $allTags = $link->tags()->where('tags.user_id', $userID)->pluck('name');
        $tagNames = collect($request->get('tags', []))->unique();
        $existingTags = Tag::query()->where('user_id', $userID)->whereHas('links')->whereIn('name', $tagNames)->pluck('name');
        $newTags = $tagNames->diff($existingTags);
        $toDeletedTags = $allTags->diff($tagNames);

        foreach ($existingTags as $tagName) {
            $tag = Tag::query()->where('user_id', $userID)->whereHas('links')->where('name', '=' , $tagName)->first();
            if (!$link->tags->contains($tag->id)) {
                $link->tags()->attach($tag->id, ['user_id' => $userID]);
            }
        }

        foreach ($newTags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => $tagName, 'user_id' => $userID]);
            $link->tags()->attach($tag->id, ['user_id' => $userID]);
        }

        foreach ($toDeletedTags as $tagName) {
            Log::debug("delete tag: {$tagName}");
            $tag = Tag::query()->where('user_id', $userID)->whereHas('links')->where('name', '=' , $tagName)->first();
            if ($tag) {
                $link->tags()->detach($tag->id);
            }
        }

        $link->load('tags');

        // 返回资源
        return new LinkResource($link);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Link $link)
    {
        if ($link->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $link->delete();
        return response()->json(null, 204);
    }

    public function share(Request $request)
    {
        $request->validate([
            'user_emails' => 'required|array',
            'user_emails.*' => 'exists:users,email', // 确保每个用户ID存在
            'resource_ids' => 'required|array',
            'resource_ids.*' => 'exists:links,id', // 确保每个材料ID存在
        ]);

        $userIds = User::whereIn('email', $request->user_emails)->pluck('id');

        foreach ($request->resource_ids as $resourceId) {
            $resource = Link::findOrFail($resourceId);

            // 检查当前用户是否为该材料的拥有者
            if ($resource->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $resource->sharedWith()->syncWithoutDetaching($userIds);
        }

        return response()->json(['message' => 'Resource shared successfully.']);
    }

    public function unshare(Request $request)
    {
        $request->validate([
            'user_emails' => 'required|array',
            'user_emails.*' => 'exists:users,email', // 确保每个用户ID存在
            'resource_ids' => 'required|array',
            'resource_ids.*' => 'exists:links,id', // 确保每个材料ID存在
        ]);

        $userIds = User::whereIn('email', $request->user_emails)->pluck('id');

        foreach ($request->resource_ids as $resourceId) {
            $resource = Link::findOrFail($resourceId);

            // 检查当前用户是否为该材料的拥有者
            if ($resource->user_id !== $request->user()->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $resource->sharedWith()->detach($userIds);
        }

        return response()->json(['message' => 'Resource unshared successfully.']);
    }

}
