<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardBinResource;
use App\Models\CardBin;
use App\Models\CardProvider;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CardBinController extends BaseController
{
    /**
     * 获取所有card bin列表
     */
    public function index(Request $request): JsonResponse
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'card_bin' => $request->get('card_bin'),
            'card_type' => $request->get('card_type'),
            'active' => $request->get('active'),
        ];

        $cardBins = CardBin::with('cardProvider')
            ->search($searchableFields)
            ->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return response()->json([
            'data' => CardBinResource::collection($cardBins->items()),
            'pageSize' => $cardBins->perPage(),
            'pageNo' => $cardBins->currentPage(),
            'totalPage' => $cardBins->lastPage(),
            'totalCount' => $cardBins->total(),
        ]);
    }

    /**
     * 创建新的card bin
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'card_provider_id' => 'required|exists:card_providers,id',
            'card_bin' => 'required|string|min:6|max:10',
            'card_type' => 'required|string|in:virtual,physical',
            'active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // 检查provider是否存在且active
        $provider = CardProvider::find($request->card_provider_id);
        if (!$provider || !$provider->active) {
            return response()->json([
                'message' => 'Provider不存在或已被禁用',
                'success' => false
            ], 400);
        }

        // 检查card_bin是否已存在于该provider下
        $existingBin = CardBin::where('card_provider_id', $request->card_provider_id)
            ->where('card_bin', $request->card_bin)
            ->first();

        if ($existingBin) {
            return response()->json([
                'message' => '该Card Bin已存在',
                'success' => false
            ], 400);
        }

        $cardBin = CardBin::create([
            'card_provider_id' => $request->card_provider_id,
            'card_bin' => $request->card_bin,
            'card_type' => $request->card_type,
            'active' => $request->active ?? true,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Card Bin创建成功',
            'data' => new CardBinResource($cardBin->load('cardProvider')),
            'success' => true
        ]);
    }

    /**
     * 获取指定card bin详情
     */
    public function show(CardBin $cardBin): JsonResponse
    {
        $cardBin->load('cardProvider');
        return response()->json([
            'data' => new CardBinResource($cardBin),
            'success' => true
        ]);
    }

    /**
     * 更新card bin
     */
    public function update(Request $request, CardBin $cardBin): JsonResponse
    {
        $request->validate([
            'card_bin' => 'required|string|min:6|max:10',
            'card_type' => 'required|string|in:virtual,physical',
            'active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // 检查card_bin是否已存在于该provider下（排除当前记录）
        $existingBin = CardBin::where('card_provider_id', $cardBin->card_provider_id)
            ->where('card_bin', $request->card_bin)
            ->where('id', '!=', $cardBin->id)
            ->first();

        if ($existingBin) {
            return response()->json([
                'message' => '该Card Bin已存在',
                'success' => false
            ], 400);
        }

        $cardBin->update([
            'card_bin' => $request->card_bin,
            'card_type' => $request->card_type,
            'active' => $request->active ?? $cardBin->active,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Card Bin更新成功',
            'data' => new CardBinResource($cardBin->load('cardProvider')),
            'success' => true
        ]);
    }

    /**
     * 删除card bin
     */
    public function destroy(CardBin $cardBin): JsonResponse
    {
        $cardBin->delete();

        return response()->json([
            'message' => 'Card Bin删除成功',
            'success' => true
        ]);
    }

    /**
     * 根据provider获取card bin列表
     */
    public function getByProvider(CardProvider $cardProvider): JsonResponse
    {
        $cardBins = CardBin::where('card_provider_id', $cardProvider->id)
            ->where('active', true)
            ->get();

        return response()->json([
            'data' => CardBinResource::collection($cardBins),
            'success' => true
        ]);
    }

    /**
     * 批量更新card bin状态
     */
    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|exists:card_bins,id',
            'active' => 'required|boolean',
        ]);

        $affected = CardBin::whereIn('id', $request->ids)
            ->update(['active' => $request->active]);

        return response()->json([
            'message' => "成功更新了 {$affected} 个Card Bin的状态",
            'success' => true
        ]);
    }

    /**
     * 批量删除card bin
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|exists:card_bins,id',
        ]);

        $affected = CardBin::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => "成功删除了 {$affected} 个Card Bin",
            'success' => true
        ]);
    }
}
