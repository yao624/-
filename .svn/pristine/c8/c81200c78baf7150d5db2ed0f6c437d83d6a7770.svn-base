<?php

namespace App\Http\Controllers;

use App\Http\Resources\FraudConfigResource;
use App\Models\FraudConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FraudConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Log::info("list fraud config");
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'asc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $fraudConfig = FraudConfig::orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => FraudConfigResource::collection($fraudConfig->items()),
            'pageSize' => $fraudConfig->perPage(),
            'pageNo' => $fraudConfig->currentPage(),
            'totalPage' => $fraudConfig->lastPage(),
            'totalCount' => $fraudConfig->total(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 目前只支持 domain_whitelist
        $cleanData = $request->validate([
            'type' => 'string|unique:fraud_configs,type,NULL,id,deleted_at,NULL|in:domain_whitelist,url_whitelist',
            'value' => 'array|nullable',
            'actions' => 'array|nullable',
            'actions.*' => 'string',
            'active' => 'boolean',
            'excluded_ads' => 'array|nullable',
            'excluded_ads.*' => 'string',
        ]);

        $item = FraudConfig::query()->create($cleanData);

        return new FraudConfigResource($item);
    }

    /**
     * Display the specified resource.
     */
    public function show(FraudConfig $fraudConfig)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FraudConfig $fraudConfig)
    {
        // 目前只支持 domain_whitelist
        $cleanData = $request->validate([
            'type' => 'string|unique:fraud_configs,type,' . $fraudConfig->id . ',id,deleted_at,NULL|in:domain_whitelist,url_whitelist',
            'value' => 'array|nullable',
            'actions' => 'array|nullable',
            'actions.*' => 'string',
            'active' => 'boolean|nullable',
            'excluded_ads' => 'array|nullable',
            'excluded_ads.*' => 'string',
        ]);

        // 更新数据
        $fraudConfig->update($cleanData);

        // 返回更新后的资源
        return new FraudConfigResource($fraudConfig);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FraudConfig $fraudConfig)
    {
        $fraudConfig->delete();
        return response()->json(null, 204);
    }
}
