<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgentResource;
use App\Http\Resources\NetworkResource;
use App\Models\Agent;
use App\Models\Network;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AgentController extends Controller
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
            'name' => $request->get('name'),
            'ip' => $request->get('ip'),
            'port' => $request->get('port'),
            'domain' => $request->get('domain'),
            'notes' => $request->get('notes'),
            'token' => $request->get('token'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $agent = Agent::searchByTagNames($tagNames)->search($searchableFields)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        $agent->load('tags');

        return [
            'data' => AgentResource::collection($agent->items()),
            'pageSize' => $agent->perPage(),
            'pageNo' => $agent->currentPage(),
            'totalPage' => $agent->lastPage(),
            'totalCount' => $agent->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store networks');
        $request->validate([
            'name' => [
                'required',
                Rule::unique('agents')->whereNull('deleted_at')
            ],
            'ip' => 'required_without:domain|nullable',
            'port' => 'required',
            'domain' => 'required_without:ip|nullable',
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
        $token = Str::uuid();

        $agent = Agent::create([
            'name' => $request->input('name'),
            'ip' => $request->input('ip'),
            'port' => $request->input('port'),
            'domain' => $request->input('domain'),
            'notes' => $request->input('notes', ''),
            'token' => $token
        ]);

        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $agent->tags()->sync($tagIds);

        // 预加载 tags
        $agent->load('tags');

        return new AgentResource($agent);
    }

    /**
     * Display the specified resource.
     */
    public function show(Agent $agent)
    {
        return new AgentResource($agent);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('agents')->ignore($agent->id)->whereNull('deleted_at')
            ],
            'ip' => 'required_without:domain|nullable|ip',
            'port' => 'required',
            'domain' => 'required_without:ip|nullable|domain',
            'notes' => 'nullable',
            'new_tags' => [
                'nullable',
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
        $agent->update($validatedData);
        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $agent->tags()->sync($tagIds);

        // 返回资源
        return new AgentResource($agent);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agent $agent)
    {
        $agent->delete();
        return response()->json(null, 204);
    }
}
