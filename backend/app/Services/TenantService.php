<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Exception;

class TenantService
{
    /**
     * 当前租户 UUID（在请求生命周期内）
     */
    private static ?string $currentTenantUuid = null;

    /**
     * 当前租户实例（缓存）
     */
    private static ?Tenant $currentTenant = null;

    /**
     * 设置当前租户的数据库连接
     *
     * @param string $tenantUuid 租户 UUID
     * @return Tenant
     * @throws Exception
     */
    public static function setTenantConnection(string $tenantUuid): Tenant
    {
        // 如果已经设置过相同的租户，直接返回
        if (self::$currentTenantUuid === $tenantUuid && self::$currentTenant !== null) {
            return self::$currentTenant;
        }

        // 从主数据库查询租户信息（显式 mysql_main，避免默认连接为业务库时误查 laravel.tenants）
        $tenant = Tenant::on('mysql_main')->where('uuid', $tenantUuid)->where('status', 'active')->first();
        
        if (!$tenant) {
            throw new Exception("Tenant not found: {$tenantUuid}");
        }

        if ($tenant->status !== 'active') {
            throw new Exception("Tenant is not active: {$tenantUuid}");
        }

        // 获取租户数据库配置
        $config = $tenant->getDatabaseConfig();

        // 动态设置 tenant 连接配置
        Config::set('database.connections.tenant', $config);

        // 重新连接数据库（使用 tenant 连接）
        DB::purge('tenant');
        DB::reconnect('tenant');

        // 设置默认连接为 tenant
        DB::setDefaultConnection('tenant');

        // 缓存当前租户信息
        self::$currentTenantUuid = $tenantUuid;
        self::$currentTenant = $tenant;

        // 设置 Redis 前缀（租户隔离）
        self::setRedisPrefix($tenantUuid);

        Log::debug("Tenant connection set: {$tenantUuid} -> {$tenant->database_name}");

        return $tenant;
    }

    /**
     * 根据邮箱获取租户 UUID
     *
     * @param string $email
     * @return string|null
     */
    public static function getTenantUuidByEmail(string $email): ?string
    {
        $tenant = Tenant::findByEmail($email);
        return $tenant ? $tenant->uuid : null;
    }

    /**
     * 获取租户数据库名称
     *
     * @param string $tenantUuid
     * @return string|null
     */
    public static function getTenantDatabaseName(string $tenantUuid): ?string
    {
        $tenant = Tenant::findByUuid($tenantUuid);
        return $tenant ? $tenant->database_name : null;
    }

    /**
     * 获取当前租户实例
     *
     * @return Tenant|null
     */
    public static function getCurrentTenant(): ?Tenant
    {
        return self::$currentTenant;
    }

    /**
     * 获取当前租户 UUID
     *
     * @return string|null
     */
    public static function getCurrentTenantUuid(): ?string
    {
        return self::$currentTenantUuid;
    }

    /**
     * 创建新租户数据库
     *
     * @param Tenant $tenant
     * @return bool
     * @throws Exception
     */
    public static function createTenantDatabase(Tenant $tenant): bool
    {
        try {
            $databaseName = $tenant->database_name;
            $charset = 'utf8mb4';
            $collation = 'utf8mb4_unicode_ci';

            // 检查数据库是否已存在
            if (self::tenantDatabaseExists($tenant->uuid)) {
                Log::warning("Tenant database already exists: {$databaseName}");
                return true;
            }

            // 使用租户配置的数据库连接信息创建数据库
            // 注意：创建数据库时不需要指定 database 名称，使用一个临时连接
            $tempConfig = [
                'driver' => 'mysql',
                'host' => $tenant->database_host,
                'port' => $tenant->database_port,
                'database' => null, // 创建数据库时不需要指定数据库名
                'username' => $tenant->database_username,
                'password' => $tenant->database_password,
                'charset' => $charset,
                'collation' => $collation,
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ];

            // 创建临时连接用于创建数据库
            Config::set('database.connections.tenant_temp', $tempConfig);
            DB::purge('tenant_temp');
            
            $tempConnection = DB::connection('tenant_temp');

            // 创建数据库
            $tempConnection->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET {$charset} COLLATE {$collation}");

            // 清理临时连接
            DB::purge('tenant_temp');

            Log::info("Tenant database created: {$databaseName} on {$tenant->database_host}:{$tenant->database_port}");

            return true;
        } catch (Exception $e) {
            Log::error("Failed to create tenant database: " . $e->getMessage());
            // 清理临时连接
            try {
                DB::purge('tenant_temp');
            } catch (\Exception $cleanupException) {
                // 忽略清理错误
            }
            throw $e;
        }
    }

    /**
     * 设置 Redis 前缀（租户隔离）
     *
     * 仅改 config + purge 连接，不直接调 phpredis::setOption，兼容 phpredis / predis、无 ext-redis 时由 Laravel 客户端处理。
     *
     * @param string $tenantUuid
     * @return void
     */
    private static function setRedisPrefix(string $tenantUuid): void
    {
        if (! config('tenant.redis_prefix_enabled', false)) {
            return;
        }

        $prefix = "tenant_{$tenantUuid}_";

        try {
            Config::set('database.redis.options.prefix', $prefix);

            foreach (['default', 'cache'] as $name) {
                if (! is_array(config("database.redis.{$name}", null))) {
                    continue;
                }
                try {
                    Redis::purge($name);
                } catch (\Throwable $e) {
                    Log::debug("Redis::purge({$name}): {$e->getMessage()}");
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Set tenant redis prefix failed: {$e->getMessage()}", [
                'tenant_uuid' => $tenantUuid,
                'prefix' => $prefix,
            ]);
        }
    }

    /**
     * 重置租户上下文（用于测试或清理）
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$currentTenantUuid = null;
        self::$currentTenant = null;
        
        // 恢复默认数据库连接
        DB::setDefaultConnection(Config::get('database.default'));
    }

    /**
     * 检查租户数据库是否存在
     *
     * @param string $tenantUuid
     * @return bool
     */
    public static function tenantDatabaseExists(string $tenantUuid): bool
    {
        $tenant = Tenant::findByUuid($tenantUuid);
        if (!$tenant) {
            return false;
        }

        try {
            // 临时连接到租户数据库
            $config = $tenant->getDatabaseConfig();
            Config::set('database.connections.tenant_check', $config);
            DB::purge('tenant_check');
            
            // 尝试连接
            DB::connection('tenant_check')->getPdo();
            
            // 清理临时连接
            DB::purge('tenant_check');
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

