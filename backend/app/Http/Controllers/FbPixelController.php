<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbAdAccountResource;
use App\Http\Resources\FbPixelResource;
use App\Http\Resources\NetworkResource;
use App\Models\FbPixel;
use App\Models\Network;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FbPixelController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Log::info("list pixel");
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $tagNames = $request->has('tags') ? explode(',', $request->get('tags')) : [];
        Log::debug($tagNames);

        $searchableFields = [
            'name' => $request->get('name'),
            'pixel' => $request->get('pixel'),
            'notes' => $request->get('notes'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];
        Log::debug($searchableFields);

        $fbPixels = FbPixel::searchByTagNames($tagNames)->search($searchableFields)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbPixelResource::collection($fbPixels->items()),
            'pageSize' => $fbPixels->perPage(),
            'pageNo' => $fbPixels->currentPage(),
            'totalPage' => $fbPixels->lastPage(),
            'totalCount' => $fbPixels->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(FbPixel $fbPixel)
    {
        return new FbPixelResource($fbPixel);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbPixel $fbPixel)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'notes' => 'string|nullable'
        ]);

        // 更新模型实例
        $fbPixel->update($validatedData);
        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $fbPixel->tags()->sync($tagIds);

        // 返回资源
        return new FbPixelResource($fbPixel);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbPixel $fbPixel)
    {
        //
    }

    public function shareToAdAccount(Request $request)
    {
        $validatedData = $request->validate([
            'ad_account_ids' => 'array',
        ]);
        Log::debug($validatedData);
        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }
}
