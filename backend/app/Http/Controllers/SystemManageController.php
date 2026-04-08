<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemManageController extends Controller
{
    public function roles(Request $request)
    {
        // 获取所有角色并分页
        $pageSize = $request->get('pageSize', 10);
        $pageNo = $request->get('pageNo', 1);
        $roles = Role::with('permissions')->paginate($pageSize, ['*'], 'page', $pageNo);

        // 转换数据格式以包括角色的权限
        $transformedRoles = $roles->getCollection()->map(function ($role) {
            // 构建权限数据，按资源分组
            $groupedPermissions = $role->permissions->groupBy(function ($permission) {
                $parts = explode('.', $permission->name);
                return $parts[0]; // 使用资源名分组
            });

            // 将分组的权限数据转换为需要的格式
            $permissions = $groupedPermissions->map(function ($permissionGroup, $resource) {
                return [
                    'id' => $permissionGroup->first()->id, // 取第一个权限的ID作为组的ID
                    'name' => $resource,
                    'label' => ucfirst($resource), // 标签可以是资源名称的首字母大写，或者您可以添加一个标签字段
                    'actions' => $permissionGroup->map(function ($permission) use ($resource) {
                        return str_replace($resource . '.', '', $permission->name); // 移除资源名，保留动作名
                    })->unique()->values()->all(),
                ];
            })->values()->all();

            return [
                'id' => $role->id,
                'name' => $role->name,
                'describe' => $role->name, // 描述可以是角色名称的别名，或者您可以添加一个描述字段
                'permissions' => $permissions,
            ];
        });

        return [
            'data' => $transformedRoles,
            'pageSize' => $roles->perPage(),
            'pageNo' => $roles->currentPage(),
            'totalPage' => $roles->lastPage(),
            'totalCount' => $roles->total(),
        ];
    }

    public function storeRole(Request $request)
    {
        // 验证请求数据
        $validatedData = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string',
            'permissions.*.actions' => 'required|array',
            'permissions.*.actions.*' => 'required|string',
        ]);

        // 开始事务
        DB::beginTransaction();

        try {
            // 创建新角色
            $role = Role::create(['name' => $validatedData['name']]);

            // 处理权限和动作
            foreach ($validatedData['permissions'] as $permissionData) {
                foreach ($permissionData['actions'] as $action) {
                    // 构建完整的权限名称
                    $permissionName = $permissionData['name'] . '.' . $action;
                    // 查找权限
                    $permission = Permission::findByName($permissionName);
                    // 给角色赋予权限
                    $role->givePermissionTo($permission);
                }
            }

            // 提交事务
            DB::commit();

            // 返回成功响应
            return response()->json(['message' => 'Role created successfully', 'role' => $role], 201);
        } catch (\Exception $e) {
            // 回滚事务
            DB::rollBack();

            // 如果出现异常，返回错误响应
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to create role'], 500);
        }
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'sometimes|array',
            'permissions.*.id' => 'sometimes|exists:permissions,id',
            'permissions.*.actions' => 'sometimes|array',
            'permissions.*.actions.*' => 'sometimes|string|in:add,delete,query,update,share,copy,preview',
        ]);

        // 更新角色名称
        $role->name = $request->name;
        $role->save();

        // 如果提供了权限，更新角色的权限
        if ($request->has('permissions')) {
            // 先移除所有权限
            $role->permissions()->detach();

            foreach ($request->permissions as $permissionData) {
                foreach ($permissionData['actions'] as $action) {
                    // 构建权限名称
                    $permissionName = $permissionData['name'] . '.' . $action;
                    // 查找或创建权限
                    $permission = Permission::findOrCreate($permissionName);
                    // 给角色赋予权限
                    $role->givePermissionTo($permission);
                }
            }
        }

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role
        ]);
    }

    public function destroyRole(Request $request, $id)
    {
        try {
            // 使用事务确保数据库操作的一致性
            DB::beginTransaction();

            // 通过ID查找角色
            $role = Role::query()->firstWhere('id', $id);
            // 检查是否有用户仍分配了这个角色
            if ($role->users()->count() > 0) {
                // 如果有用户正在使用该角色，返回错误响应
                return response()->json(['message' => 'Role is in use and cannot be deleted'], 400);
            }

            // 删除角色
            $role->delete();

            // 提交事务
            DB::commit();

            // 返回成功响应
            return response()->json(['message' => 'Role deleted successfully'], 200);
        } catch (\Exception $e) {
            // 如果发生异常，回滚事务
            DB::rollBack();

            // 返回错误响应
            return response()->json(['message' => 'Failed to delete role', 'error' => $e->getMessage()], 500);
        }
    }

    public function permissions(Request $request)
    {
        // 获取所有权限并分页
        $permissions = Permission::paginate($request->input('perPage', 1000)); // 默认每页显示10条

        // 转换数据格式以包括权限的名称和动作
        $transformedPermissions = $permissions->getCollection()->map(function ($permission) {
            // 假设权限名称格式为 'resource.action'
            $parts = explode('.', $permission->name);
            $resource = $parts[0];
            $action = $parts[1];

            return [
                'id' => $permission->id,
                'name' => $resource,
                'label' => ucfirst($resource), // 标签可以是资源名称的首字母大写，或者您可以添加一个标签字段
                'actions' => [$action], // 动作作为数组的单个元素
            ];
        })->groupBy('name')->map(function ($groupedPermissions, $resource) {
            // 将同一个资源的所有动作合并到一个数组中
            $actions = $groupedPermissions->flatMap(function ($permission) {
                return $permission['actions'];
            })->unique()->values()->all();

            return [
                'id' => $groupedPermissions->first()['id'], // 取第一个权限的ID作为组的ID
                'name' => $resource,
                'label' => ucfirst($resource), // 标签可以是资源名称的首字母大写，或者您可以添加一个标签字段
                'actions' => $actions,
            ];
        })->values()->all();

        return [
            'data' => $transformedPermissions,
            'pageSize' => $permissions->perPage(),
            'pageNo' => $permissions->currentPage(),
            'totalPage' => $permissions->lastPage(),
            'totalCount' => $permissions->total(),
        ];
    }
}
