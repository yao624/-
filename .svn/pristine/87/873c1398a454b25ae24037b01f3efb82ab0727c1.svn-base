<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardProviderResource;
use App\Models\CardProvider;
use App\Services\CardProviderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CardProviderController extends BaseController
{
    private CardProviderService $cardProviderService;

    public function __construct(CardProviderService $cardProviderService)
    {
        $this->cardProviderService = $cardProviderService;
    }

    /**
     * 获取所有provider列表
     */
    public function index(Request $request): JsonResponse
    {
        $sortField = $request->get('sortField', 'created_at');
        $sortDirection = $request->get('sortOrder', 'desc');
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);

        $searchableFields = [
            'name' => $request->get('name'),
            'nick_name' => $request->get('nick_name'),
            'active' => $request->get('active'),
        ];

        $providers = CardProvider::with('cardBins')
            ->search($searchableFields)
            ->orderBy($sortField, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return response()->json([
            'data' => CardProviderResource::collection($providers->items()),
            'pageSize' => $providers->perPage(),
            'pageNo' => $providers->currentPage(),
            'totalPage' => $providers->lastPage(),
            'totalCount' => $providers->total(),
        ]);
    }

    /**
     * 创建新的provider
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:card_providers',
            'nick_name' => 'required|string|max:255|unique:card_providers',
            'config' => 'nullable|array',
            'active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // 检查provider类型是否支持
        $availableProviders = $this->cardProviderService->getAvailableProviders();
        if (!in_array($request->name, $availableProviders)) {
            return response()->json([
                'message' => '不支持的provider类型',
                'available_providers' => $availableProviders,
                'success' => false
            ], 400);
        }

        $provider = CardProvider::create([
            'name' => $request->name,
            'nick_name' => $request->nick_name,
            'config' => $request->config ?? [],
            'active' => $request->active ?? true,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Provider创建成功',
            'data' => new CardProviderResource($provider),
            'success' => true
        ]);
    }

    /**
     * 获取指定provider详情
     */
    public function show(CardProvider $cardProvider): JsonResponse
    {
        $cardProvider->load('cardBins');
        return response()->json([
            'data' => new CardProviderResource($cardProvider),
            'success' => true
        ]);
    }

    /**
     * 更新provider
     */
    public function update(Request $request, CardProvider $cardProvider): JsonResponse
    {
        $request->validate([
            'nick_name' => 'required|string|max:255|unique:card_providers,nick_name,' . $cardProvider->id,
            'config' => 'nullable|array',
            'active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $cardProvider->update([
            'nick_name' => $request->nick_name,
            'config' => $request->config ?? $cardProvider->config,
            'active' => $request->active ?? $cardProvider->active,
            'notes' => $request->notes,
        ]);

        // 清理provider缓存
        $this->cardProviderService->clearProviderCache();

        return response()->json([
            'message' => 'Provider更新成功',
            'data' => new CardProviderResource($cardProvider),
            'success' => true
        ]);
    }

    /**
     * 删除provider
     */
    public function destroy(CardProvider $cardProvider): JsonResponse
    {
        // 检查是否有关联的卡片
        if ($cardProvider->cards()->exists()) {
            return response()->json([
                'message' => '该Provider下还有卡片，无法删除',
                'success' => false
            ], 400);
        }

        $cardProvider->delete();

        // 清理provider缓存
        $this->cardProviderService->clearProviderCache();

        return response()->json([
            'message' => 'Provider删除成功',
            'success' => true
        ]);
    }

    /**
     * 获取provider列表用于前端选择（只返回nick_name和card_bins）
     */
    public function listForSelection(): JsonResponse
    {
        $providers = CardProvider::with(['cardBins' => function ($query) {
            $query->where('active', true);
        }])
            ->where('active', true)
            ->get();

        $result = $providers->map(function ($provider) {
            return [
                'id' => $provider->id,
                'nick_name' => $provider->nick_name,
                'card_bins' => $provider->cardBins->map(function ($bin) {
                    return [
                        'id' => $bin->id,
                        'card_bin' => $bin->card_bin,
                        'card_type' => $bin->card_type,
                    ];
                })
            ];
        });

        return response()->json([
            'data' => $result,
            'success' => true
        ]);
    }

    /**
     * 获取可用的provider类型
     */
    public function getAvailableTypes(): JsonResponse
    {
        $availableTypes = $this->cardProviderService->getAvailableProviders();

        return response()->json([
            'data' => $availableTypes,
            'success' => true
        ]);
    }

    /**
     * 测试provider连接
     */
    public function testConnection(CardProvider $cardProvider): JsonResponse
    {
        try {
            $provider = $this->cardProviderService->getProviderByModel($cardProvider);
            $token = $provider->getToken();

            if ($token) {
                return response()->json([
                    'message' => 'Provider连接测试成功',
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'message' => 'Provider连接测试失败',
                    'success' => false
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Provider连接测试失败', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Provider连接测试失败: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
