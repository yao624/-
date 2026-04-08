<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbBmResource;
use App\Http\Resources\FbPageResource;
use App\Http\Resources\NetworkResource;
use App\Jobs\FacebookBmClaimPage;
use App\Jobs\FacebookBmManageUserAdAccount;
use App\Jobs\FacebookBmManageUserCatalog;
use App\Jobs\FacebookBmManageUserPage;
use App\Jobs\FacebookSharePixel;
use App\Jobs\TriggerFacebookFetchApiResource;
use App\Models\FbAdAccount;
use App\Models\FbApiToken;
use App\Models\FbBm;
use App\Models\FbBusinessUser;
use App\Models\FbCatalog;
use App\Models\FbPage;
use App\Models\FbPixel;
use App\Models\Network;
use App\Models\Tag;
use App\Utils\DevUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FbBmController extends BaseController
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

        $tagNames =  $request->get('tags', []);
        Log::debug($tagNames);

        $searchableFields = [
            'name' => $request->get('name'),
            'source_id' => $request->get('source_id'),
            'notes' => $request->get('notes'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $bms = FbBm::search($searchableFields)
            ->with(['fbAdAccounts', 'fbAdAccounts.fbPixels', 'fbApps', 'fbAdAccounts.subscribedApps']);

        $userId = auth()->id();

        if ($tagNames) {
            $bms = $bms->whereHas('tags', function ($query) use ($userId, $tagNames) {
                $query->where('tags.user_id', $userId)->whereIn('tags.name', $tagNames);
            });
        }

        if ($request->get('ad_account_id')) {
            $ad_account_source_id = $request->get('ad_account_id');
            $bms = $bms->whereHas('fbAdAccounts', function ($query) use ($ad_account_source_id) {
                $query->where('fb_ad_accounts.source_id', 'LIKE', '%' . $ad_account_source_id . '%');
            });
        }

        if ($request->get('pixel_id')) {
            $pixel_id = $request->get('pixel_id');
            $bms = $bms->whereHas('pixels', function ($query) use ($pixel_id) {
                $query->where('fb_pixels.pixel', 'LIKE', '%' . $pixel_id . '%');
            });
        }

        if ($request->get('page_id')) {
            $page_source_id = $request->get('page_id');
            $bms = $bms->whereHas('fbPages', function ($query) use ($page_source_id) {
                $query->where('fb_pages.source_id', 'LIKE', '%' . $page_source_id . '%');
            });
        }

        $bms = $bms->with('fbBusinessUsers')->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbBmResource::collection($bms->items()),
            'pageSize' => $bms->perPage(),
            'pageNo' => $bms->currentPage(),
            'totalPage' => $bms->lastPage(),
            'totalCount' => $bms->total(),
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
    public function show(FbBm $fbBm)
    {
        return new FbBmResource($fbBm);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbBm $fbBm)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'notes' => 'string|nullable'
        ]);

        // 更新模型实例
        $fbBm->update($validatedData);
        // 创建新的 Tag
        $newTags = collect($request->get('new_tags'))->map(function ($name) {
            return Tag::query()->firstOrCreate(['name' => $name])->id;
        });

        // 使用已经有的 tags
        $tagIds = array_merge($request->get('tag_ids', []), $newTags->toArray());
        $fbBm->tags()->sync($tagIds);

        // 返回资源
        return new FbBmResource($fbBm);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbBm $fbBm)
    {
        //
    }

    public function share_pixel(Request $request)
    {
        Log::debug("share pixel");
        // TODO: 权限校验
        $validatedData = $request->validate([
            'pixel_id' => 'string',
            'bm_id' => 'string',
            'ad_account_ids' => 'array|nullable',
            'ad_account_ids.*' => 'string',
            'catalog_ids' => 'array|nullable',
            'catalog_ids.*' => 'string',
            'action' => 'required|in:share,unshare',
            'share_type' => 'required|in:ad_account,catalog'
        ]);

        $adAccountIds = $validatedData['ad_account_ids'] ?? [];
        $catalogIds = $validatedData['catalog_ids'] ?? [];
        // Check if pixel, bm, ad accounts exist
        if (!DevUtils::exists(FbPixel::class, $validatedData['pixel_id'])) {
            Log::debug("pixel not exists");
            return response()->json(['error' => 'Pixel ID does not exist.'], 404);
        }
        if (!DevUtils::exists(FbBm::class, $validatedData['bm_id'])) {
            return response()->json(['error' => 'BM ID does not exist.'], 404);
        }

        // TODO: 检查 AdAccount 是否都属于BM
        foreach ($adAccountIds as $adAccountId) {
            if (!DevUtils::exists(FbAdAccount::class, $adAccountId)) {
                return response()->json(['error' => "AdAccount ID {$adAccountId} does not exist."], 404);
            }
        }

        foreach ($catalogIds as $catalogId) {
            if (!DevUtils::exists(FbCatalog::class, $catalogId)) {
                return response()->json(['error' => "Catalog ID {$catalogId} does not exist."], 404);
            }
        }

        $action = $validatedData['action'] ?? 'share';
        $share_type = $validatedData['share_type'];
        if ($share_type === 'ad_account') {
            foreach ($validatedData['ad_account_ids'] as $index => $adAccountId) {
                FacebookSharePixel::dispatch($validatedData['pixel_id'],$validatedData['bm_id'], $action, $share_type,
                    $adAccountId, null)->onQueue('facebook')->delay($index*5);
            }
        } elseif ($share_type === 'catalog') {
            foreach ($validatedData['catalog_ids'] as $index => $catalog_id) {
                FacebookSharePixel::dispatch($validatedData['pixel_id'],$validatedData['bm_id'], $action, $share_type,
                    null, $catalog_id)->onQueue('facebook')->delay($index*5);
            }
        }


        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);

    }

    public function sync(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'array',
            'ids.*' => 'string',
        ]);

        foreach ($validatedData['ids'] as $bmId) {
            if (!DevUtils::exists(FbBm::class, $bmId)) {
                return response()->json(['error' => "FbBm ID {$bmId} does not exist."], 404);
            }
        }

        foreach ($validatedData['ids'] as $bmId) {
            $fbApiToken = FbBm::query()->firstWhere('id', $bmId)->fbApiTokens()->firstWhere('active', true);
            if ($fbApiToken) {
                TriggerFacebookFetchApiResource::dispatch([$fbApiToken['id']])->onQueue('facebook');
            }
        }

//        FbBm::query()->whereIn('id', $validatedData['ids'])
//        FbApiToken::query()->whereHas()
//        TriggerFacebookFetchApiResource::dispatch($clenData['ids']);

        return response()->json([
            'message' => trans('message.task_submitted', [], $this->language),
            'success' => true
        ]);
    }

    public function claim_pages(Request $request)
    {
        $validatedData = $request->validate([
            'ids' => 'array',
            'ids.*' => 'string',
            'bm_id' => 'string'
        ]);

        if (!DevUtils::exists(FbBm::class, $validatedData['bm_id'])) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }
        foreach ($validatedData['ids'] as $pageId) {
            if (!DevUtils::exists(FbPage::class, $pageId)) {
                return response()->json(['error' => "FbBm ID {$pageId} does not exist."], 404);
            }
        }

        foreach ($validatedData['ids'] as $pageId) {
            FacebookBmClaimPage::dispatch($validatedData['bm_id'], $pageId)->onQueue('facebook');
        }

        return response()->json([
            'message' => 'submitted, please ask page admin approved request',
            'success' => true
        ]);
    }

    public function assign_user_account(Request $request)
    {
        $validatedData = $request->validate([
            'accs' => 'array',
            'accs.*.id' => 'string',
            'accs.*.role' => 'string',
            'bm_id' => 'string',
            'bm_user_id' => 'string',
            'action' => 'string|in:add,delete'
        ]);

        # TODO: 检查权限

        if (!DevUtils::exists(FbBm::class, $validatedData['bm_id'])) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        if (!DevUtils::exists(FbBusinessUser::class, $validatedData['bm_user_id'])) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        foreach ($validatedData['accs'] as $acc) {
            if (!DevUtils::exists(FbAdAccount::class, $acc['id'])) {
                return response()->json(['error' => "Some ad account does not exist."], 404);
            }
        }

        foreach ($validatedData['accs'] as $acc) {
            FacebookBmManageUserAdAccount::dispatch($validatedData['action'], $validatedData['bm_id'],
                $validatedData['bm_user_id'], $acc['id'], $acc['role']);
        }

        return response()->json([
            'message' => 'submitted, please ask page admin approved request',
            'success' => true
        ]);
    }

    public function assign_user_page(Request $request)
    {
        $validatedData = $request->validate([
            'pages' => 'array',
            'pages.*.id' => 'string',
            'pages.*.role' => 'string',
            'bm_id' => 'string',
            'bm_user_id' => 'string',
            'action' => 'string|in:add,delete'
        ]);

        # TODO: 检查权限

        if (!DevUtils::exists(FbBm::class, $validatedData['bm_id'])) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        if (!DevUtils::exists(FbBusinessUser::class, $validatedData['bm_user_id'])) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        foreach ($validatedData['pages'] as $page) {
            if (!DevUtils::exists(FbPage::class, $page['id'])) {
                return response()->json(['error' => "Some ad account does not exist."], 404);
            }
        }

        foreach ($validatedData['pages'] as $page) {
            FacebookBmManageUserPage::dispatch($validatedData['action'], $validatedData['bm_id'],
                $validatedData['bm_user_id'], $page['id'], $page['role']);
        }

        return response()->json([
            'message' => 'submitted, please ask page admin approved request',
            'success' => true
        ]);
    }

    public function assign_user_catalog(Request $request)
    {
        $validatedData = $request->validate([
            'catalogs' => 'array',
            'catalogs.*.id' => 'string',
            'catalogs.*.role' => 'string',
            'bm_id' => 'string',
            'bm_user_id' => 'string',
            'action' => 'string|in:add,delete'
        ]);

        # TODO: 检查权限

        if (!DevUtils::exists(FbBm::class, $validatedData['bm_id'])) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        if (!DevUtils::exists(FbBusinessUser::class, $validatedData['bm_user_id'])) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        foreach ($validatedData['catalogs'] as $catalog) {
            if (!DevUtils::exists(FbCatalog::class, $catalog['id'])) {
                return response()->json(['error' => "Some catalog does not exist."], 404);
            }
        }

        foreach ($validatedData['catalogs'] as $catalog) {
            FacebookBmManageUserCatalog::dispatch($validatedData['action'], $validatedData['bm_id'],
                $validatedData['bm_user_id'], $catalog['id'], $catalog['role']);
        }

        return response()->json([
            'message' => 'submitted',
            'success' => true
        ]);
    }


}
