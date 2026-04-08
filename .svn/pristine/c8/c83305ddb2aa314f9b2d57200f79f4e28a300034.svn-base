<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends BaseController
{
    /**
     * 获取租户列表
     */
    public function index(Request $request)
    {
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);
        $search = $request->get('search', '');

        $query = Tenant::query();

        // 搜索功能
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('database_name', 'like', "%{$search}%");
            });
        }

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $tenants = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNo);

        return [
            'data' => $tenants->items(),
            'pageSize' => $tenants->perPage(),
            'pageNo' => $tenants->currentPage(),
            'totalPage' => $tenants->lastPage(),
            'totalCount' => $tenants->total(),
        ];
    }

    /**
     * 获取单个租户详情
     */
    public function show($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // 检查数据库是否存在
        $tenant->database_exists = TenantService::tenantDatabaseExists($tenant->uuid);
        
        return response()->json([
            'status' => true,
            'data' => $tenant
        ]);
    }

    /**
     * 创建新租户
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:tenants,email',
            'name' => 'nullable|string|max:255',
            'database_name' => 'required|string|max:255|regex:/^[a-z0-9_]+$/',
            'database_host' => 'nullable|string|max:255',
            'database_port' => 'nullable|integer|min:1|max:65535',
            'database_username' => 'required|string|max:255',
            'database_password' => 'required|string',
            'status' => 'nullable|in:active,inactive,suspended',
            'create_database' => 'nullable|boolean', // 是否自动创建数据库
            'run_migrations' => 'nullable|boolean', // 是否自动执行迁移
        ]);

        DB::beginTransaction();
        try {
            // 生成 UUID 和 ULID
            $uuid = Str::uuid()->toString();
            $ulid = (string) Str::ulid();

            // 创建租户记录
            $tenant = Tenant::create([
                'id' => $ulid,
                'uuid' => $uuid,
                'email' => $validated['email'],
                'name' => $validated['name'] ?? null,
                'database_name' => $validated['database_name'],
                'database_host' => $validated['database_host'] ?? '127.0.0.1',
                'database_port' => $validated['database_port'] ?? 3306,
                'database_username' => $validated['database_username'],
                'database_password' => $validated['database_password'],
                'status' => $validated['status'] ?? 'active',
            ]);

            // 如果请求创建数据库，则创建
            if ($request->boolean('create_database')) {
                TenantService::createTenantDatabase($tenant);
                Log::info("Tenant database created: {$tenant->database_name}");
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tenant created successfully',
                'data' => $tenant
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create tenant: " . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to create tenant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 更新租户信息
     */
    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'email' => [
                'sometimes',
                'email',
                Rule::unique('tenants', 'email')->ignore($tenant->id)
            ],
            'name' => 'nullable|string|max:255',
            'database_name' => 'sometimes|string|max:255|regex:/^[a-z0-9_]+$/',
            'database_host' => 'nullable|string|max:255',
            'database_port' => 'nullable|integer|min:1|max:65535',
            'database_username' => 'sometimes|string|max:255',
            'database_password' => 'sometimes|string',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        DB::beginTransaction();
        try {
            $tenant->fill($validated);
            $tenant->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Tenant updated successfully',
                'data' => $tenant
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update tenant: " . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to update tenant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 删除租户
     */
    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);

        // 检查是否有用户使用此租户（可选，根据业务需求）
        // 这里只是软删除，不删除数据库

        $tenant->delete();

        return response()->json([
            'status' => true,
            'message' => 'Tenant deleted successfully'
        ]);
    }

    /**
     * 批量删除租户
     */
    public function batchDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string'
        ]);

        $ids = $request->get('ids');
        $count = Tenant::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => true,
            'message' => "Deleted {$count} tenant(s) successfully",
            'data' => $count
        ]);
    }

    /**
     * 测试租户数据库连接
     */
    public function testConnection($id)
    {
        $tenant = Tenant::findOrFail($id);

        try {
            $exists = TenantService::tenantDatabaseExists($tenant->uuid);
            
            if ($exists) {
                return response()->json([
                    'status' => true,
                    'message' => 'Database connection successful',
                    'data' => [
                        'database_exists' => true,
                        'database_name' => $tenant->database_name
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Database does not exist or connection failed',
                    'data' => [
                        'database_exists' => false,
                        'database_name' => $tenant->database_name
                    ]
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 创建租户数据库
     */
    public function createDatabase($id)
    {
        $tenant = Tenant::findOrFail($id);

        try {
            TenantService::createTenantDatabase($tenant);
            
            return response()->json([
                'status' => true,
                'message' => 'Database created successfully',
                'data' => [
                    'database_name' => $tenant->database_name
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create tenant database: " . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to create database: ' . $e->getMessage()
            ], 500);
        }
    }
}

