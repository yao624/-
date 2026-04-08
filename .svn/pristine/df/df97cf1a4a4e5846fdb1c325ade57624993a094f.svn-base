<?php

namespace App\Http\Controllers;

use App\Http\Resources\FbAdResource;
use App\Http\Resources\FbApiTokenResource;
use App\Models\FbAd;
use App\Models\FbApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FbApiTokenController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'active' => 'in:true,false'
        ]);

        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'name' => $request->get('name'),
            'notes' => $request->get('notes'),
            'token' => $request->get('token'),
            'bm_id' => $request->get('bm_id')
        ];

        $apiToken = FbApiToken::search($searchableFields);

        if ($request->get('active')) {
            $apiToken = $apiToken->where('active', $request->boolean('active'));
        }

        $apiToken = $apiToken->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FbApiTokenResource::collection($apiToken->items()),
            'pageSize' => $apiToken->perPage(),
            'pageNo' => $apiToken->currentPage(),
            'totalPage' => $apiToken->lastPage(),
            'totalCount' => $apiToken->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::debug('store fb api token');
        $request->validate([
            'name' => 'required|string',
            'bm_id' => 'nullable|string',
            'token' => 'required|string',
            'active' => 'boolean',
            'token_type' => 'required|numeric|in:1,2,3',
            'notes' => 'nullable',
            'app' => 'nullable|string'
        ]);

        $apiToken = FbApiToken::create([
            'name' => $request->input('name'),
            'bm_id' => $request->input('bm_id'),
            'token' => $request->input('token'),
            'active' => $request->input('active', true),
            'notes' => $request->input('notes', ''),
            'app' => $request->input('app', '')
        ]);

        return new FbApiTokenResource($apiToken);
    }

    /**
     * Display the specified resource.
     */
    public function show(FbApiToken $fbApiToken)
    {
        return new FbApiTokenResource($fbApiToken);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbApiToken $fbApiToken)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'name' => 'nullable',
            'token' => 'nullable',
            'active' => 'nullable|boolean',
            'token_type' => 'numeric|in:1,2,3',
            'notes' => 'string',
            'bm_id' => 'nullable|string',
            'app' => 'nullable|string'
        ]);

        // 更新模型实例
        $fbApiToken->update($validatedData);

        // 返回资源
        return new FbApiTokenResource($fbApiToken);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbApiToken $fbApiToken)
    {
        $fbApiToken->delete();
        return response()->json(null, 204);
    }
}
