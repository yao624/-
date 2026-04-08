<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class InitPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:init-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize or update permissions';

    protected array $permissionsMap = [
        'dashboard' => ['query'],
        'accounts' => ['add', 'delete', 'query', 'update'],
        'adaccounts' => ['query', 'update'],
        'ads' => ['add', 'delete', 'query', 'update', 'preview'],
        'rules' => ['add', 'delete', 'query', 'update'],
        'pages' => ['query', 'update'],
        'bms' => ['query', 'update'],
        'pixels' => ['query', 'update'],
        'networks' => ['add', 'delete', 'query', 'update'],
        'clicks' => ['query'],
        'conversions' => ['query'],
        'vcc' => ['add', 'delete', 'query', 'update'],
        'materials' => ['add', 'delete', 'query', 'update'],
        'copywritings' => ['add', 'delete', 'query', 'update'],
        'links' => ['add', 'delete', 'query', 'update'],
        'proxies' => ['add', 'delete', 'query', 'update'],
        'agents' => ['add', 'delete', 'query', 'update'],
        'expense' => ['add', 'delete', 'query', 'update'],
        'roles' => ['add', 'delete', 'query', 'update'],
        'permissions' => ['add', 'delete', 'query', 'update'],
        'templates' => ['copy', 'add', 'delete', 'query', 'update', 'share'],
    ];

    protected $rolePermissionsMap = [
        'admin' => [
            'dashboard' => ['query'],
            'accounts' => ['add', 'delete', 'query', 'update'],
            'adaccounts' => ['query', 'update'],
            'ads' => ['add', 'delete', 'query', 'update', 'preview'],
            'rules' => ['add', 'delete', 'query', 'update'],
            'pages' => ['query', 'update'],
            'bms' => ['query', 'update'],
            'pixels' => ['query', 'update'],
            'networks' => ['add', 'delete', 'query', 'update'],
            'clicks' => ['query'],
            'conversions' => ['query'],
            'vcc' => ['add', 'delete', 'query', 'update'],
            'materials' => ['add', 'delete', 'query', 'update'],
            'copywritings' => ['add', 'delete', 'query', 'update'],
            'links' => ['add', 'delete', 'query', 'update'],
            'proxies' => ['add', 'delete', 'query', 'update'],
            'agents' => ['add', 'delete', 'query', 'update'],
            'expense' => ['add', 'delete', 'query', 'update'],
            'roles' => ['add', 'delete', 'query', 'update'],
            'permissions' => ['add', 'delete', 'query', 'update'],
            'templates' => ['copy', 'add', 'delete', 'query', 'update', 'share'],
        ],
        'publisher' => [
            'dashboard' => ['query'],
            'accounts' => ['add', 'delete', 'query', 'update'],
            'adaccounts' => ['query', 'update'],
            'ads' => ['add', 'delete', 'query', 'update'],
            'rules' => ['add', 'delete', 'query', 'update'],
            'pages' => ['query', 'update'],
            'pixels' => ['query', 'update'],
            'networks' => ['add', 'delete', 'query', 'update'],
            'clicks' => ['query'],
            'conversions' => ['query'],
            'materials' => ['add', 'delete', 'query', 'update'],
            'copywritings' => ['add', 'delete', 'query', 'update'],
            'links' => ['add', 'delete', 'query', 'update'],
            'proxies' => ['add', 'delete', 'query', 'update'],
            'templates' => ['copy', 'add', 'delete', 'query', 'update', 'share']
        ],
        'tester' => [
            'dashboard' => ['query'],
            'accounts' => ['add', 'delete', 'query', 'update'],
            'adaccounts' => ['query', 'update'],
            'ads' => ['add', 'delete', 'query', 'update'],
            'rules' => ['add', 'delete', 'query', 'update'],
            'pages' => ['query', 'update'],
            'pixels' => ['query', 'update'],
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 遍历权限映射
        foreach ($this->permissionsMap as $permission => $actions) {
            foreach ($actions as $action) {
                // 构建权限名称
                $permissionName = $permission . '.' . $action;
                // 创建或更新权限
                Permission::findOrCreate($permissionName, 'sanctum');
                $this->info("Permission '{$permissionName}' created or updated.");
            }
        }

        $this->info('All permissions have been initialized or updated successfully.');


        foreach ($this->rolePermissionsMap as $roleName => $permissionsMap) {
            // 创建或找到角色
            $role = Role::findOrCreate($roleName, 'sanctum');
            $this->info("Role '{$roleName}' created or updated.");

            // 构建当前角色的所有可能权限
            $currentPermissions = [];
            foreach ($permissionsMap as $permissionGroup => $actions) {
                foreach ($actions as $action) {
                    $currentPermissions[] = $permissionGroup . '.' . $action;
                }
            }

            // 获取角色当前所有权限
            $rolePermissions = $role->permissions->pluck('name')->toArray();

            // 移除不再存在于映射中的权限
            foreach ($rolePermissions as $permissionName) {
                if (!in_array($permissionName, $currentPermissions)) {
                    $role->revokePermissionTo($permissionName);
                    $this->info("Permission '{$permissionName}' removed from role '{$roleName}'.");
                }
            }

            // 同步权限（添加新权限）
            foreach ($currentPermissions as $permissionName) {
                if (!in_array($permissionName, $rolePermissions)) {
                    $role->givePermissionTo($permissionName);
                    $this->info("Permission '{$permissionName}' assigned to role '{$roleName}'.");
                }
            }
        }

        $this->info('All roles and permissions have been synchronized successfully.');

        $admin = User::query()->first();
        $adminRole = Role::query()->firstWhere('name', 'admin');
        $admin->syncRoles([$adminRole]);

    }
}
