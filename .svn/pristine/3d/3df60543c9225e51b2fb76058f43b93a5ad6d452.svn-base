<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbAdsetResource;
use App\Http\Resources\FbCampaignResource;
use App\Models\FbAdAccount;
use App\Models\FbAdset;
use App\Models\FbCampaign;
use App\Utils\FbUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class FbAdsetController extends Controller
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
            'name' => $request->get('name'),
            'endpoint' => $request->get('endpoint'),
            'date_start' => $request->get('date_start'),
            'date_stop' => $request->get('date_end')
        ];

        $adets = FbAdset::searchByTagNames($tagNames)->search($searchableFields)->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbAdsetResource::collection($adets->items()),
            'pageSize' => $adets->perPage(),
            'pageNo' => $adets->currentPage(),
            'totalPage' => $adets->lastPage(),
            'totalCount' => $adets->total(),
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
    public function show(FbAdset $fbAdset)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbAdset $fbAdset)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbAdset $fbAdset)
    {
        //
    }

    /**
     * 编辑 Adset 语言设置（更新 targeting）
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTargeting(Request $request)
    {
        // 验证请求参数
        $request->validate([
            'targeting' => 'required|array',
            'ad_account' => 'required|string',
            'adset_id' => 'required|string'
        ]);

        $targeting = $request->input('targeting');
        $adAccountSourceId = $request->input('ad_account');
        $adsetId = $request->input('adset_id');

        try {
            // 根据 source_id 查询 FbAdAccount
            $fbAdAccount = FbAdAccount::where('source_id', $adAccountSourceId)->first();

            if (!$fbAdAccount) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到对应的广告账户'
                ], 404);
            }

            // 查询对应的 FbApiToken (token_type = 1, active = true)
            $fbApiToken = $fbAdAccount->apiTokens()
                ->where('token_type', 1)
                ->where('active', true)
                ->first();

            if (!$fbApiToken) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到有效的API Token'
                ], 404);
            }

            // 验证 adset 是否属于指定的 ad account
            $fbAdset = FbAdset::where('source_id', $adsetId)
                ->where('account_id', $adAccountSourceId)
                ->first();

            if (!$fbAdset) {
                return response()->json([
                    'success' => false,
                    'message' => '未找到对应的 Adset 或 Adset 不属于指定的广告账户'
                ], 404);
            }

            // 构建 Facebook Ad Set Update API 的 URL
            $endpoint = "https://graph.facebook.com/" . FbUtils::$API_Version . "/{$adsetId}";

            // 准备更新数据
            $updateData = [
                'targeting' => json_encode($targeting)
            ];

            // 使用 FbUtils 发送 POST 请求来更新 adset
            $response = FbUtils::makeRequest(
                null,
                $endpoint,
                null,
                'POST',
                $updateData,
                '',
                $fbApiToken->token
            );

            if ($response['success']) {
                // 更新本地数据库中的 targeting 信息
                $fbAdset->targeting = $targeting;
                $fbAdset->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Adset targeting 更新成功',
                    'data' => [
                        'adset_id' => $adsetId,
                        'ad_account' => $adAccountSourceId,
                        'targeting' => $targeting
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Facebook API 调用失败'
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('FbAdsetController updateTargeting error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '更新 Adset targeting 失败: ' . $e->getMessage()
            ], 500);
        }
    }
}
