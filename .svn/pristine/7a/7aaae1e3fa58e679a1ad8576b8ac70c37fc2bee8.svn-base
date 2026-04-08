<?php

namespace App\Http\Controllers;

use App\Jobs\FacebookCatalogUploadVideo;
use App\Jobs\FacebookCreateCatalog;
use App\Jobs\FacebookCreateProduct;
use App\Jobs\FacebookCreateProductSet;
use App\Jobs\FacebookUpdateProduct;
use App\Jobs\FacebookUpdateProductSet;
use App\Models\FbBm;
use App\Models\FbBusinessUser;
use App\Models\FbCatalog;
use App\Models\FbCatalogProduct;
use App\Models\FbCatalogProductSet;
use App\Utils\DevUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FbCatalogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(FbCatalog $fbCatalog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FbCatalog $fbCatalog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FbCatalog $fbCatalog)
    {
        //
    }

    public function update_product_set(Request $request)
    {
        $validatedData = $request->validate([
            'bm_id' => 'required|string',
            'name' => 'required|string',
            'product_set_id' => 'required|string',
            'filter' => 'required|array',
        ]);

        // TODO: 权限管理

        $bm_id = $validatedData['bm_id'];
        $product_set_id = $validatedData['product_set_id'];

        if (!DevUtils::exists(FbBm::class, $bm_id)) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        if (!DevUtils::exists(FbCatalogProductSet::class, $product_set_id)) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        FacebookUpdateProductSet::dispatch($bm_id, $product_set_id, $validatedData['name'], $validatedData['filter'])
            ->onQueue('facebook');

        return response()->json([
            'message' => 'submitted, please ask page admin approved request',
            'success' => true
        ]);
    }


    public function update_product(Request $request)
    {
        $validatedData = $request->validate([
            'bm_id' => 'string',
            'name' => 'required||string',
            'description' => 'required|string',
            'url' => 'required|string',
            'image_url' => 'required|string',
            'price' => 'required|numeric',
            'id' => 'required|string',
        ]);

        // TODO: 权限管理

        $bm_id = $validatedData['bm_id'] ?? null;
        $id = $validatedData['id'];
        $name = $validatedData['name'];
        $desc = $validatedData['description'];
        $url = $validatedData['url'];
        $image_url = $validatedData['image_url'];
        $price = $validatedData['price'];

        if ($bm_id) {
            if (!DevUtils::exists(FbBm::class, $bm_id)) {
                return response()->json(['error' => "FbBm does not exist."], 404);
            }
        }

        if (!DevUtils::exists(FbCatalogProduct::class, $id)) {
            return response()->json(['error' => "FbProduct does not exist."], 404);
        }

        FacebookUpdateProduct::dispatch($bm_id, $id, $name, $desc, $url, $image_url, $price)
            ->onQueue('facebook');

        return response()->json([
            'message' => 'submitted',
            'success' => true
        ]);
    }

    public function bulk_create_product(Request $request)
    {
        $validatedData = $request->validate([
            'bm_id' => 'required|string',
            'catalog_id' => 'required|string',
            'products' => 'required|array',
            'products.*.name' => 'required|string',
            'products.*.description' => 'required|string',
            'products.*.url' => 'required|string',
            'products.*.image_url' => 'required|string',
            'products.*.currency' => 'required|string',
            'products.*.price' => 'required|numeric',
        ]);

        $bm_id = $validatedData['bm_id'];
        $catalog_id = $validatedData['catalog_id'];
        $products = $validatedData['products'];

        if (!DevUtils::exists(FbBm::class, $bm_id)) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }
        if (!DevUtils::exists(FbCatalog::class, $catalog_id)) {
            return response()->json(['error' => "Catalog does not exist."], 404);
        }
        foreach ($products as $index => $product) {
            FacebookCreateProduct::dispatch($bm_id, $catalog_id, $product)->delay($index*5)
                ->onQueue('facebook');
        }

        return response()->json([
            'message' => 'submitted',
            'success' => true
        ]);
    }

    public function bulk_create_product_set(Request $request)
    {
        $validatedData = $request->validate([
            'bm_id' => 'required|string',
            'catalog_id' => 'required|string',
            'product_sets' => 'required|array',
            'product_sets.*.name' => 'required|string',
            'product_sets.*.filter' => 'required|array',
        ]);

        $bm_id = $validatedData['bm_id'];
        $catalog_id = $validatedData['catalog_id'];
        $product_sets = $validatedData['product_sets'];

        if (!DevUtils::exists(FbBm::class, $bm_id)) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }
        if (!DevUtils::exists(FbCatalog::class, $catalog_id)) {
            return response()->json(['error' => "Catalog does not exist."], 404);
        }

        foreach ($product_sets as $index => $product_set) {
            FacebookCreateProductSet::dispatch($bm_id, $catalog_id, $product_set)->delay($index*5)
                ->onQueue('facebook');
        }

        return response()->json([
            'message' => 'submitted',
            'success' => true
        ]);
    }

    public function create_catalog(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'bm_id' => 'required|string'
        ]);

        $bm_id = $validatedData['bm_id'];
        if (!DevUtils::exists(FbBm::class, $bm_id)) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }

        FacebookCreateCatalog::dispatch($bm_id, $validatedData['name'])->onQueue('facebook');

        return response()->json([
            'message' => 'submitted',
            'success' => true
        ]);
    }

    public function update_product_video(Request $request)
    {
        $validatedData = $request->validate([
            'video_url' => 'required|string',
            'bm_id' => 'string',
            'catalog_id' => 'required|string',
            'retailer_id' => 'required|string'
        ]);

        $bm_id = $validatedData['bm_id'] ?? null;
        $catalog_id = $validatedData['catalog_id'];
        $retailer_id = $validatedData['retailer_id'];

        if ($bm_id && !DevUtils::exists(FbBm::class, $bm_id)) {
            return response()->json(['error' => "FbBm does not exist."], 404);
        }
        if (!DevUtils::exists(FbCatalog::class, $catalog_id)) {
            return response()->json(['error' => "FbCatalog does not exist."], 404);
        }
        if (!DevUtils::exists(FbCatalogProduct::class, $retailer_id, 'retailer_id')) {
            return response()->json(['error' => "FbCatalogProduct does not exist."], 404);
        }

        FacebookCatalogUploadVideo::dispatch($bm_id, $catalog_id, $retailer_id, $validatedData['video_url'])
            ->onQueue('facebook');

        return response()->json([
            'message' => 'submitted',
            'success' => true
        ]);
    }

    public function set_operator(Request $request)
    {
        $validated = $request->validate([
            'bm_id' => [
                'required',
                Rule::exists('fb_bms', 'id'), // 确保 fb_bms 表中存在这个 id
            ],
            'user_id' => [
                'required',
                Rule::exists('fb_business_users', 'id'), // 确保 fb_business_users 表中存在这个 id
            ],
        ], [
            'bm_id.required' => 'BM ID 不能为空。',
            'bm_id.exists' => '指定的 BM 不存在。',
            'user_id.required' => '用户 ID 不能为空。',
            'user_id.exists' => '指定的用户不存在。',
        ]);

        $bmId = $validated['bm_id'];
        $userId = $validated['user_id'];

        // --- 2. 验证用户是否属于该 BM ---
        // 这一步非常重要，确保要设置的用户确实是这个 BM 下的用户
        $targetUserExistsInBm = FbBusinessUser::where('id', $userId)
            ->where('fb_bm_id', $bmId) // 检查外键 fb_bm_id 是否匹配
            ->exists();

        if (!$targetUserExistsInBm) {
            // 返回 422 Unprocessable Entity 错误，并附带具体错误信息
            return response()->json([
                'message' => '验证失败。', // 或者 '操作无法完成。'
                'errors' => [
                    'user_id' => ['所选用户不属于指定的 BM。']
                ]
            ], 422);
        }

        // --- 3. 在数据库事务中执行更新 ---
        // 使用事务确保数据的一致性：要么全部更新成功，要么全部失败回滚
        try {
            DB::transaction(function () use ($bmId, $userId) {
                // 步骤 1: 将此 BM 下的所有业务用户的 is_operator 设置为 false
                FbBusinessUser::where('fb_bm_id', $bmId)
                    ->update(['is_operator' => false]);

                // 步骤 2: 将指定的业务用户的 is_operator 设置为 true
                // 直接更新比先查询再保存更高效
                $updatedCount = FbBusinessUser::where('id', $userId)
                    ->where('fb_bm_id', $bmId) // 最好再次限定 bm_id 增加安全性
                    ->update(['is_operator' => true]);

                // 可选：检查目标用户是否真的被更新了。
                // 理论上因为前面的验证，$updatedCount 应该是 1，但加上判断更健壮。
                if ($updatedCount === 0) {
                    // 这个情况理论上不会发生，因为前面的验证保证了用户存在且属于该 BM
                    throw new \Exception("未能更新目标操作员用户。");
                }

            }); // 如果没有抛出异常，事务会自动提交

            // --- 4. 成功响应 ---
            // 可以选择性地获取更新后的操作员信息并返回
            $operator = FbBusinessUser::find($userId);

            return response()->json([
                'message' => '操作员设置成功。',
                'operator' => $operator // 返回更新后的操作员信息
            ], 200); // HTTP 状态码 200 OK

        } catch (\Throwable $e) {
            // --- 5. 错误处理 ---
            // 记录详细错误信息，方便排查问题
            Log::error('设置操作员失败: ' . $e->getMessage(), [
                'bm_id' => $bmId,
                'user_id' => $userId,
                'exception' => $e
            ]);

            // 返回通用的服务器错误信息给前端
            return response()->json([
                'message' => '设置操作员时发生错误，请稍后重试。',
                // 在开发环境中可以考虑返回更详细的错误信息 $e->getMessage()
                // 'error' => $e->getMessage()
            ], 500); // HTTP 状态码 500 Internal Server Error
        }

    }

}
