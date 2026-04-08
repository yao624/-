<?php

namespace App\Http\Controllers;

use App\Http\Resources\NetworkResource;
use App\Http\Resources\ProxyResource;
use App\Models\Network;
use App\Models\Proxy;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProxyController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->has('tags') ? explode(',', $request->get('tags')) : [];

        $searchableFields = [
            'host' => $request->get('host'),
            'protocol' => $request->get('protocol'),
            'port' => $request->get('port'),
            'username' => $request->get('username'),
            'password' => $request->get('password'),
            'notes' => $request->get('notes'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $proxy = Proxy::searchByTagNames($tagNames)->search($searchableFields)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => ProxyResource::collection($proxy->items()),
            'pageSize' => $proxy->perPage(),
            'pageNo' => $proxy->currentPage(),
            'totalPage' => $proxy->lastPage(),
            'totalCount' => $proxy->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store proxy');
        $request->validate([
            'protocol' => 'required|in:http,https,socks5',
            'host' => 'required|string',
            'port' => 'required|integer|between:100,65535',
            'username' => 'string|nullable',
            'password' => 'string|nullable',
            'new_tags' => [
                'sometimes', // 只有当 new_tags 存在时才应用后续的规则
                'array',     // new_tags 必须是一个数组
            ],
            'new_tags.*' => [
                'string',    // new_tags 数组中的每个元素必须是字符串
                'distinct',  // new_tags 数组中的每个元素必须是唯一的
                Rule::notIn(Tag::pluck('name')->toArray()), // new_tags 数组中的每个元素不能在 Tag 模型的 name 字段值中
            ],
        ]);

        // 获取当前登录的用户
        $user = Auth::user();

        $proxy = Proxy::create([
            'user_id' => $user->id,
            'protocol' => $request->input('protocol'),
            'host' => $request->input('host'),
            'port' => $request->input('port'),
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'notes' => $request->input('notes', ''),
        ]);

        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $proxy->tags()->sync($tagIds);

        // 预加载 tags
        $proxy->load('tags');

        return new ProxyResource($proxy);
    }

    /**
     * Display the specified resource.
     */
    public function show(Proxy $proxy)
    {
        return new ProxyResource($proxy);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proxy $proxy)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'protocol' => 'required|in:http,https,socks5',
            'host' => 'required|string',
            'port' => 'required|integer|between:100,65535',
            'username' => 'string|nullable',
            'password' => 'string|nullable',
            'notes' => 'nullable',
            'new_tags' => [
                'sometimes', // 只有当 new_tags 存在时才应用后续的规则
                'array',     // new_tags 必须是一个数组
            ],
            'new_tags.*' => [
                'string',    // new_tags 数组中的每个元素必须是字符串
                'distinct',  // new_tags 数组中的每个元素必须是唯一的
                Rule::notIn(Tag::pluck('name')->toArray()), // new_tags 数组中的每个元素不能在 Tag 模型的 name 字段值中
            ],
        ]);

        // 更新模型实例
        $proxy->update($validatedData);
        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $proxy->tags()->sync($tagIds);

        // 返回资源
        return new ProxyResource($proxy);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proxy $proxy)
    {
        $proxy->delete();
        return response()->json(null, 204);
    }
}
