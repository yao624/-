<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbPageResource;
use App\Http\Resources\FbPixelResource;
use App\Http\Resources\NetworkResource;
use App\Jobs\FacebookFetchPageForms;
use App\Jobs\FacebookFetchPageToken;
use App\Models\FbPage;
use App\Models\Network;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class FbPageController extends BaseController
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
        Log::debug($tagNames);

        $searchableFields = [
            'id' => $request->get('id'),
            'name' => $request->get('name'),
            'source_id' => $request->get('source_id'),
            'notes' => $request->get('notes'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $fbPage = FbPage::searchByTagNames($tagNames)->search($searchableFields);

        if ($request->get('bm_system_user_id')) {
            $apiTokenId = $request->get('bm_system_user_id');
            $fbPage = $fbPage->whereHas('fbApiTokens', function ($query) use ($apiTokenId) {
                $query->where('fb_api_tokens.id', $apiTokenId);
            });
        }

        if ($request->get('fb_account_id')) {
            $fbAccountId = $request->get('fb_account_id');
            $fbPage = $fbPage->whereHas('fbAccounts', function ($query) use ($fbAccountId) {
                $query->where('fb_accounts.id', $fbAccountId);
            });
        }

        if ($request->get('key')) {
            $kw = $request->get('key');
            $fbPage = $fbPage->where('name', 'LIKE', '%' . $kw . '%')
                ->orWhere('source_id', 'LIKE', '%' . $kw . '%');
        }

        $fbPage = $fbPage->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)->with('fbAccounts')
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbPageResource::collection($fbPage->items()),
            'pageSize' => $fbPage->perPage(),
            'pageNo' => $fbPage->currentPage(),
            'totalPage' => $fbPage->lastPage(),
            'totalCount' => $fbPage->total(),
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
    public function show(FbPage $fbPage)
    {
        return new FbPageResource($fbPage);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbPage $fbPage)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'notes' => 'string|nullable',
            'pbia' => 'string|nullable'
        ]);

        // 更新模型实例
        $fbPage->update($validatedData);
        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $fbPage->tags()->sync($tagIds);

        // 返回资源
        return new FbPageResource($fbPage);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbPage $fbPage)
    {
        //
    }

    /**
     * Set pbia for the specified FbPage.
     */
    public function setPbia(Request $request, FbPage $fbPage)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'pbia' => 'string|nullable'
        ]);

        // 更新 pbia 字段
        $fbPage->update(['pbia' => $validatedData['pbia']]);

        // 返回资源
        return response()->json([
            'success' => true,
            'message' => 'PBIA 设置成功',
            'data' => new FbPageResource($fbPage)
        ]);
    }

    public function refreshToken(Request $request)
    {
        $cleanData = $request->validate([
            'page_ids' => 'array',
            'page_ids.*' => 'string|exists:fb_pages,id'
        ]);

        // 检查用户是否有同步 token 的权限
        $pages = FbPage::query()->whereIn('id', collect($cleanData['page_ids'])->unique())
            ->pluck('id')->toArray();
        $jobs = [];

        foreach ($pages as $page_id) {
            $jobs[] = new FacebookFetchPageToken($page_id);
        }
        Bus::batch($jobs)->finally(function ($cb) {
            Log::info("finished fetch page token");
        })->onQueue('facebook')->allowFailures()->dispatch();

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }

    public function sync_page_forms(Request $request)
    {
        $cleanData = $request->validate([
            'page_ids' => 'array',
            'page_ids.*' => 'string|exists:fb_pages,id'
        ]);

        // 检查用户是否有同步 token 的权限
        $pages = FbPage::query()->whereIn('id', collect($cleanData['page_ids'])->unique())
            ->pluck('source_id')->toArray();
        $jobs = [];

        foreach ($pages as $page_source_id) {
            $jobs[] = new FacebookFetchPageForms($page_source_id);
        }
        Bus::batch($jobs)->finally(function ($cb) {
            Log::info("finished sync page forms");
        })->onQueue('facebook-page-form')->allowFailures()->dispatch();

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ], 200);
    }
}
