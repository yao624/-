<?php

namespace App\Http\Controllers\AccountManage;

use App\Http\Controllers\Controller;
use App\Http\Resources\FbAccountResource;
use App\Http\Resources\FbAdAccountResource;
use App\Models\FbAccount;
use App\Models\FbAdAccount;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * 广告账户管理页面控制器
 * 统一处理前端广告账户页面的所有接口需求
 * 支持多平台：meta(Facebook), google, tiktok
 */
class AdAccountPageController extends Controller
{
    use ApiResponse;

    /**
     * 获取用户列表（FB个人号列表）
     * 对应前端"用户"Tab
     *
     * @queryParam platform string 平台类型: meta, google, tiktok
     * @queryParam page int 页码
     * @queryParam pageSize int 每页数量
     * @queryParam keyword string 搜索关键词
     * @queryParam autoBind bool 自动绑定筛选
     * @queryParam personalAccount string 个人账号名称筛选
     * @queryParam authorizer string 授权人筛选
     * @queryParam authStatus string 授权状态筛选
     * @queryParam authTimeStart string 授权开始时间
     * @queryParam authTimeEnd string 授权结束时间
     */
    public function getUserList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'required|in:meta,google,tiktok',
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'keyword' => 'nullable|string',
            'autoBind' => 'nullable|boolean',
            'personalAccount' => 'nullable|string',
            'authorizer' => 'nullable|string',
            'authStatus' => 'nullable|in:authorized,unauthorized,expired,failed,pending',
            'authTimeStart' => 'nullable|date',
            'authTimeEnd' => 'nullable|date|after:authTimeStart',
        ]);

        $platform = $validated['platform'];
        $page = $validated['page'] ?? 1;
        $pageSize = $validated['pageSize'] ?? 20;
        $keyword = $validated['keyword'] ?? '';
        $userId = Auth::id();
        $isAdmin = Auth::user()->hasRole('admin');

        // 目前只实现 meta 平台
        if ($platform !== 'meta') {
            return $this->success([
                'data' => [],
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => 0,
            ], '该平台暂未开放');
        }

        $query = $isAdmin
            ? FbAccount::query()
            : FbAccount::where('user_id', $userId);

        // 关键词搜索
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('username', 'like', "%{$keyword}%")
                    ->orWhere('source_id', 'like', "%{$keyword}%");
            });
        }

        // 个人账号名称筛选
        if (!empty($validated['personalAccount'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', "%{$validated['personalAccount']}%")
                    ->orWhere('username', 'like', "%{$validated['personalAccount']}%");
            });
        }

        // 授权人筛选
        if (!empty($validated['authorizer'])) {
            $query->whereHas('authorizedBy', function ($q) use ($validated) {
                $q->where('name', 'like', "%{$validated['authorizer']}%")
                    ->orWhere('email', 'like', "%{$validated['authorizer']}%");
            });
        }

        // 授权状态筛选
        if (!empty($validated['authStatus'])) {
            $query->where('authorization_status', $validated['authStatus']);
        }

        // 授权时间范围筛选
        if (!empty($validated['authTimeStart'])) {
            $query->where('authorized_at', '>=', $validated['authTimeStart']);
        }
        if (!empty($validated['authTimeEnd'])) {
            $query->where('authorized_at', '<=', $validated['authTimeEnd']);
        }

        // 加载关联关系和统计
        $query->with(['authorizedBy', 'user'])
            ->withCount('fbAdAccounts as binding_count');

        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        // 转换为前端需要的格式
        $data = collect($paginator->items())->map(function ($fbAccount) {
            return [
                'id' => $fbAccount->id,
                'name' => $fbAccount->name,
                'username' => $fbAccount->username,
                'platform' => 'meta',
                'autoBind' => (bool)$fbAccount->auto_bind,
                'switching' => false,
                'boundCount' => $fbAccount->binding_count ?? 0,
                'personalAccount' => $fbAccount->name ?? ($fbAccount->username ?? $fbAccount->source_id),
                'authorizer' => $fbAccount->authorizedBy->name ?? null,
                'lastAuthTime' => $fbAccount->authorized_at?->format('Y-m-d H:i:s'),
                'authStatus' => $fbAccount->authorization_status ?? 'unauthorized',
                'authFailReason' => $fbAccount->authorization_fail_reason,
            ];
        })->values();

        return $this->success([
            'data' => $data,
            'page' => $paginator->currentPage(),
            'pageSize' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * 获取广告账户列表
     * 对应前端"广告账户"Tab
     *
     * @queryParam platform string 平台类型: meta, google, tiktok
     * @queryParam page int 页码
     * @queryParam pageSize int 每页数量
     * @queryParam keyword string 搜索关键词
     * @queryParam personalAccount string 个人账号筛选
     * @queryParam accountStatus string 账户状态筛选
     * @queryParam authStatus string 授权状态筛选
     * @queryParam owner string 所属人员筛选
     * @queryParam assistant string 协助人员筛选
     * @queryParam bm string BM筛选
     * @queryParam authTimeStart string 授权开始时间
     * @queryParam authTimeEnd string 授权结束时间
     */
    public function getAdAccountList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'required|in:meta,google,tiktok',
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'keyword' => 'nullable|string',
            'personalAccount' => 'nullable|string',
            'accountStatus' => 'nullable|string',
            'authStatus' => 'nullable|in:authorized,unauthorized,expired,failed,pending',
            'owner' => 'nullable|integer|exists:users,id',
            'assistant' => 'nullable|integer|exists:users,id',
            'bm' => 'nullable|string',
            'authTimeStart' => 'nullable|date',
            'authTimeEnd' => 'nullable|date|after:authTimeStart',
        ]);

        $platform = $validated['platform'];
        $page = $validated['page'] ?? 1;
        $pageSize = $validated['pageSize'] ?? 20;
        $keyword = $validated['keyword'] ?? '';
        $userId = Auth::id();
        $isAdmin = Auth::user()->hasRole('admin');

        // 目前只实现 meta 平台
        if ($platform !== 'meta') {
            return $this->success([
                'data' => [],
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => 0,
            ], '该平台暂未开放');
        }

        $query = FbAdAccount::query();

        // 权限控制（临时注释，用于调试）
        // if (!$isAdmin) {
        //     $query->where(function ($q) use ($userId) {
        //         $q->whereHas('fbAccounts', function ($subQ) use ($userId) {
        //             $subQ->where('fb_accounts.user_id', $userId);
        //         })->orWhereHas('users', function ($subQ) use ($userId) {
        //             $subQ->where('users.id', $userId);
        //         });
        //     });
        // }

        // 关键词搜索
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('source_id', 'like', "%{$keyword}%")
                    ->orWhere('alias', 'like', "%{$keyword}%");
            });
        }

        // 个人账号筛选
        if (!empty($validated['personalAccount'])) {
            $query->whereHas('fbAccounts', function ($q) use ($validated) {
                $q->where('name', 'like', "%{$validated['personalAccount']}%")
                    ->orWhere('username', 'like', "%{$validated['personalAccount']}%");
            });
        }

        // 账户状态筛选
        if (!empty($validated['accountStatus'])) {
            $query->where('account_status', $validated['accountStatus']);
        }

        // 授权状态筛选
        if (!empty($validated['authStatus'])) {
            $query->where('authorization_status', $validated['authStatus']);
        }

        // 所属人员筛选 - 精确匹配用户ID
        if (!empty($validated['owner'])) {
            $query->where('owner', $validated['owner']);
        }

        // 协助人员筛选 - 通过多对多关系查询
        if (!empty($validated['assistant'])) {
            $query->whereHas('users', function ($q) use ($validated) {
                $q->where('users.id', $validated['assistant']);
            });
        }

        // BM筛选
        if (!empty($validated['bm'])) {
            $query->whereHas('fbBms', function ($q) use ($validated) {
                $q->where('fb_bms.source_id', 'like', "%{$validated['bm']}%")
                    ->orWhere('fb_bms.name', 'like', "%{$validated['bm']}%");
            });
        }

        // 授权时间范围筛选
        if (!empty($validated['authTimeStart'])) {
            $query->where('authorized_at', '>=', $validated['authTimeStart']);
        }
        if (!empty($validated['authTimeEnd'])) {
            $query->where('authorized_at', '<=', $validated['authTimeEnd']);
        }

        // 加载关联关系
        $query->with(['fbAccounts', 'fbBms', 'users']);

        // 获取所有 owner 用户信息（用于后续映射）
        $adAccounts = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        $ownerIds = collect($adAccounts->items())->pluck('owner')->filter()->unique();
        $ownersMap = User::whereIn('id', $ownerIds)->pluck('name', 'id')->toArray();

        // 转换为前端需要的格式
        $data = collect($adAccounts->items())->map(function ($adAccount) use ($ownersMap) {
            // 获取关联的FB个人号信息
            $fbAccounts = $adAccount->fbAccounts ?? [];
            $personalAccounts = $fbAccounts->pluck('name')->filter()->toArray();
            $personalAccountCount = $fbAccounts->count();

            // 获取所属人员（从 fb_ad_accounts.owner 字段）
            $owner = null;
            if (!empty($adAccount->owner) && isset($ownersMap[$adAccount->owner])) {
                $owner = ['id' => (int)$adAccount->owner, 'name' => $ownersMap[$adAccount->owner]];
            }

            // 获取协助人员（从多对多关系 users）
            $assistants = ($adAccount->users ?? collect())
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                ->values()
                ->toArray();

            // 获取BM信息
            $bm = $adAccount->fbBms->first()?->source_id ?? null;

            // 账户状态映射
            $accountStatus = 'active';
            if ($adAccount->account_status === 'disabled') {
                $accountStatus = 'disabled';
            } elseif ($adAccount->account_status === 'pending') {
                $accountStatus = 'pending';
            } elseif ($adAccount->account_status === 'unactivated') {
                $accountStatus = 'disabled';
            }

            // 授权状态映射
            $authorizationStatus = 'authorized';
            if ($adAccount->authorization_status === 'failed') {
                $authorizationStatus = 'expired';
            } elseif ($adAccount->authorization_status === 'pending') {
                $authorizationStatus = 'pending';
            }

            return [
                'id' => $adAccount->id,
                'name' => $adAccount->name,
                'source_id' => $adAccount->source_id,
                'platform' => 'meta',
                'bm' => $bm,
                'balance' => $adAccount->balance,
                'personalAccountCount' => $personalAccountCount,
                'personalAccounts' => $personalAccounts,
                'authorizationStatus' => $authorizationStatus,
                'accountStatus' => $accountStatus,
                'owner' => $owner,
                'assistants' => $assistants,
                'authorizationTime' => $adAccount->authorized_at?->format('Y-m-d H:i:s'),
                'accountNote' => $adAccount->alias,
            ];
        })->values();

        return $this->success([
            'data' => $data,
            'page' => $adAccounts->currentPage(),
            'pageSize' => $adAccounts->perPage(),
            'total' => $adAccounts->total(),
        ]);
    }

    /**
     * 编辑广告账户（单个）
     * 更新所属人员和协助人员
     *
     * @bodyParam id string 广告账户ID
     * @bodyParam owner string 所属人员ID
     * @bodyParam assistants array 协助人员ID数组
     */
    public function editAdAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|exists:fb_ad_accounts,id',
            'owner' => 'nullable|int|exists:users,id',
            'assistants' => 'nullable|array',
            'assistants.*' => 'int|exists:users,id',
        ]);

        $adAccount = FbAdAccount::findOrFail($validated['id']);
        $user = Auth::user();

        // 权限检查
        if (!$user->can('operate', $adAccount)) {
            return $this->fail('没有权限操作该广告账户', 403);
        }

        DB::beginTransaction();
        try {
            // 更新所属人员（使用 fb_ad_accounts.owner 字段）
            if (array_key_exists('owner', $validated)) {
                $adAccount->owner = $validated['owner'];
                $adAccount->save();
            }

            // 更新协助人员（使用多对多关系）
            if (array_key_exists('assistants', $validated)) {
                $assistantIds = $validated['assistants'] ?? [];
                $adAccount->users()->sync($assistantIds);
            }

            DB::commit();

            return $this->success(null, '编辑成功');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('编辑广告账户失败: ' . $e->getMessage());
            return $this->fail('编辑失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量编辑广告账户
     * 批量更新所属人员和协助人员
     *
     * @bodyParam ids array 广告账户ID数组
     * @bodyParam owner string|null 所属人员ID（为空则不更新）
     * @bodyParam assistants array|null 协助人员ID数组（为空则不更新）
     */
    public function batchEditAdAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
            'owner' => 'nullable|int|exists:users,id',
            'assistants' => 'nullable|array',
            'assistants.*' => 'int|exists:users,id',
        ]);

        $user = Auth::user();

        // 权限检查
        foreach ($validated['ids'] as $id) {
            $adAccount = FbAdAccount::find($id);
            if (!$adAccount || !$user->can('operate', $adAccount)) {
                return $this->fail("没有权限操作广告账户: {$id}", 403);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($validated['ids'] as $id) {
                $adAccount = FbAdAccount::find($id);

                // 更新所属人员（使用 fb_ad_accounts.owner 字段）
                if (array_key_exists('owner', $validated)) {
                    $adAccount->owner = $validated['owner'];
                    $adAccount->save();
                }

                // 更新协助人员（使用多对多关系）
                if (array_key_exists('assistants', $validated)) {
                    $assistantIds = $validated['assistants'] ?? [];
                    $adAccount->users()->sync($assistantIds);
                }
            }

            DB::commit();

            return $this->success(null, '批量编辑成功');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('批量编辑广告账户失败: ' . $e->getMessage());
            return $this->fail('批量编辑失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量解绑广告账户
     * 将广告账户与FB个人号解绑
     *
     * @bodyParam ids array 广告账户ID数组
     */
    public function batchUnbind(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
        ]);

        $user = Auth::user();

        // 权限检查
        foreach ($validated['ids'] as $id) {
            $adAccount = FbAdAccount::find($id);
            if (!$adAccount || !$user->can('operate', $adAccount)) {
                return $this->fail("没有权限操作广告账户: {$id}", 403);
            }
        }

        try {
            // 更新授权状态为已解绑
            FbAdAccount::whereIn('id', $validated['ids'])
                ->update([
                    'authorization_status' => 'pending',
                    'authorized_at' => null,
                ]);

            return $this->success(null, '批量解绑成功');
        } catch (\Exception $e) {
            Log::error('批量解绑广告账户失败: ' . $e->getMessage());
            return $this->fail('批量解绑失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量删除广告账户
     *
     * @bodyParam ids array 广告账户ID数组
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|exists:fb_ad_accounts,id',
        ]);

        $user = Auth::user();

        // 权限检查
        foreach ($validated['ids'] as $id) {
            $adAccount = FbAdAccount::find($id);
            if (!$adAccount || !$user->can('operate', $adAccount)) {
                return $this->fail("没有权限操作广告账户: {$id}", 403);
            }
        }

        try {
            // 软删除
            FbAdAccount::whereIn('id', $validated['ids'])->delete();

            return $this->success(null, '批量删除成功');
        } catch (\Exception $e) {
            Log::error('批量删除广告账户失败: ' . $e->getMessage());
            return $this->fail('批量删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量修改广告账户关联的FB个人号
     *
     * @bodyParam ad_account_ids array 广告账户ID数组
     * @bodyParam fb_account_id string 新的FB个人号ID
     */
    public function batchModifyFbAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ad_account_ids' => 'required|array|min:1',
            'ad_account_ids.*' => 'required|string|exists:fb_ad_accounts,id',
            'fb_account_id' => 'required|string|exists:fb_accounts,id',
        ]);

        $user = Auth::user();
        $fbAccount = FbAccount::find($validated['fb_account_id']);

        // 检查FB个人号权限
        if (!$user->hasRole('admin') && $fbAccount->user_id !== $user->id) {
            return $this->fail('没有权限操作该FB个人号', 403);
        }

        // 权限检查
        foreach ($validated['ad_account_ids'] as $id) {
            $adAccount = FbAdAccount::find($id);
            if (!$adAccount || !$user->can('operate', $adAccount)) {
                return $this->fail("没有权限操作广告账户: {$id}", 403);
            }
        }

        try {
            // 清除旧的FB个人号关联
            foreach ($validated['ad_account_ids'] as $id) {
                $adAccount = FbAdAccount::find($id);
                $adAccount->fbAccounts()->detach();
            }

            // 建立新的FB个人号关联
            $fbAccount->fbAdAccounts()->attach($validated['ad_account_ids']);

            return $this->success(null, '批量修改FB个人号成功');
        } catch (\Exception $e) {
            Log::error('批量修改FB个人号失败: ' . $e->getMessage());
            return $this->fail('批量修改FB个人号失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除用户（FB个人号）
     *
     * @bodyParam id string FB个人号ID
     */
    public function deleteUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|exists:fb_accounts,id',
        ]);

        $fbAccount = FbAccount::findOrFail($validated['id']);
        $user = Auth::user();

        // 权限检查
        if (!$user->hasRole('admin') && (string)$fbAccount->user_id !== (string)$user->id) {
            return $this->fail('没有权限操作该FB个人号', 403);
        }

        try {
            // 软删除
            $fbAccount->delete();

            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            Log::error('删除FB个人号失败: ' . $e->getMessage());
            return $this->fail('删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新用户自动绑定状态
     *
     * @bodyParam id string FB个人号ID
     * @bodyParam autoBind boolean 自动绑定状态
     */
    public function updateAutoBind(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string|exists:fb_accounts,id',
            'autoBind' => 'required|boolean',
        ]);

        $fbAccount = FbAccount::findOrFail($validated['id']);
        $user = Auth::user();

        // 权限检查
        if (!$user->hasRole('admin') && (string)$fbAccount->user_id !== (string)$user->id) {
            return $this->fail('没有权限操作该FB个人号', 403);
        }

        try {
            // 更新自动绑定状态
            $fbAccount->auto_bind = $validated['autoBind'];
            $fbAccount->save();

            return $this->success([
                'id' => $fbAccount->id,
                'autoBind' => $fbAccount->auto_bind,
            ], '自动绑定状态更新成功');
        } catch (\Exception $e) {
            Log::error('更新自动绑定状态失败: ' . $e->getMessage());
            return $this->fail('更新失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取授权账号限制信息
     * 返回总授权数、已授权数、剩余数
     */
    public function getAuthLimit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'required|in:meta,google,tiktok',
        ]);

        $platform = $validated['platform'];
        $userId = Auth::id();
        $isAdmin = Auth::user()->hasRole('admin');

        // 目前只实现 meta 平台
        if ($platform !== 'meta') {
            return $this->success([
                'total' => 2000,
                'authorized' => 0,
                'remaining' => 2000,
            ]);
        }

        // 获取授权数量统计
        $query = $isAdmin
            ? FbAccount::query()
            : FbAccount::where('user_id', $userId);

        $total = 2000; // 总授权数，可以从配置中读取
        $authorized = $query->where('authorization_status', 'authorized')->count();
        $remaining = max(0, $total - $authorized);

        return $this->success([
            'total' => $total,
            'authorized' => $authorized,
            'remaining' => $remaining,
        ]);
    }

    /**
     * 获取可用的FB个人号列表
     * 用于修改FB个人号弹窗
     */
    public function getFbAccountsList(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->hasRole('admin');

        $query = $isAdmin
            ? FbAccount::query()
            : FbAccount::where('user_id', $userId);

        $fbAccounts = $query->with(['authorizedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $fbAccounts->map(function ($fbAccount) {
            return [
                'id' => $fbAccount->id,
                'fbAccount' => $fbAccount->name ?? ($fbAccount->username ?? $fbAccount->source_id),
                'fbAccountId' => $fbAccount->source_id,
                'authStatus' => $fbAccount->authorization_status ?? 'unauthorized',
                'adspolarUsername' => $fbAccount->authorizedBy->name ?? null,
                'authTime' => $fbAccount->authorized_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return $this->success($data);
    }

    /**
     * 获取用户选项列表（级联选择器）
     * 用于编辑弹窗中的所属人员和协助人员选择
     */
    public function getUserOptions(Request $request): JsonResponse
    {
        $users = User::select('id', 'name', 'email')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // 按部门分组（这里简化处理，实际可能需要从部门表获取）
        $departments = [
            [
                'label' => '客户服务部',
                'value' => 'dept-customer-service',
                'children' => $users->take(3)->map(fn($u) => [
                    'label' => $u->name,
                    'value' => $u->id,
                ])->toArray(),
            ],
            [
                'label' => '市场部',
                'value' => 'dept-marketing',
                'children' => $users->skip(3)->take(3)->map(fn($u) => [
                    'label' => $u->name,
                    'value' => $u->id,
                ])->toArray(),
            ],
            [
                'label' => '技术部',
                'value' => 'dept-tech',
                'children' => $users->skip(6)->map(fn($u) => [
                    'label' => $u->name,
                    'value' => $u->id,
                ])->toArray(),
            ],
        ];

        return $this->success($departments);
    }

    /**
     * 删除单个FB个人号
     * 使用 RESTful 风格，参考 FbAccountController::destroy
     *
     * @param string $id FB个人号ID
     * @return JsonResponse
     */
    public function destroyAccount(string $id): JsonResponse
    {
        $fbAccount = FbAccount::findOrFail($id);
        $user = Auth::user();

        // 权限检查
        if (!$user->hasRole('admin') && (string)$fbAccount->user_id !== (string)$user->id) {
            return $this->fail('没有权限操作该FB个人号', 403);
        }

        try {
            $fbAccount->delete();
            return $this->success(null, '删除成功', 204);
        } catch (\Exception $e) {
            Log::error('删除FB个人号失败: ' . $e->getMessage());
            return $this->fail('删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除单个广告账户
     * 使用 RESTful 风格，参考 FbAccountController::destroy
     *
     * @param string $id 广告账户ID
     * @return JsonResponse
     */
    public function destroyAdAccount(string $id): JsonResponse
    {
        $fbAdAccount = FbAdAccount::findOrFail($id);
        $user = Auth::user();

        // 权限检查
        if (!$user->can('operate', $fbAdAccount)) {
            return $this->fail('没有权限操作该广告账户', 403);
        }

        try {
            $fbAdAccount->delete();
            return $this->success(null, '删除成功', 204);
        } catch (\Exception $e) {
            Log::error('删除广告账户失败: ' . $e->getMessage());
            return $this->fail('删除失败: ' . $e->getMessage(), 500);
        }
    }
}
