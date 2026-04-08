<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TenantMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate 
                            {uuid : 租户 UUID}
                            {--fresh : 删除所有表并重新运行所有迁移}
                            {--seed : 运行迁移后执行数据填充}
                            {--sql : 使用 SQL 脚本而不是 Laravel 迁移文件}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '为指定租户执行数据库迁移';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uuid = $this->argument('uuid');

        try {
            // 从主数据库查询租户
            $tenant = Tenant::findByUuid($uuid);

            if (!$tenant) {
                $this->error("租户不存在: {$uuid}");
                return 1;
            }

            $this->info("找到租户: {$tenant->name} ({$tenant->email})");
            $this->info("数据库: {$tenant->database_name}");

            // 检查数据库是否存在
            if (!TenantService::tenantDatabaseExists($uuid)) {
                $this->error("数据库不存在: {$tenant->database_name}");
                $this->info("请先创建数据库或使用 --create-db 选项");
                return 1;
            }

            // 设置租户数据库连接
            $this->info("正在连接租户数据库...");
            TenantService::setTenantConnection($uuid);
            $this->info("✓ 已连接到租户数据库");

            // 执行迁移
            if ($this->option('sql')) {
                // 使用 SQL 脚本
                $this->info("正在执行 SQL 脚本...");
                $sqlFile = database_path('scripts/create_tenant_all_tables.sql');
                
                if (!file_exists($sqlFile)) {
                    $this->error("SQL 脚本不存在: {$sqlFile}");
                    return 1;
                }

                $sql = file_get_contents($sqlFile);
                
                // 如果使用 --fresh，先删除所有表
                if ($this->option('fresh')) {
                    $this->warn("--fresh 选项对 SQL 脚本无效，请手动删除表");
                }

                // 执行 SQL
                try {
                    DB::connection('tenant')->unprepared($sql);
                    $this->info("✓ SQL 脚本执行完成");
                } catch (\Exception $e) {
                    $this->error("✗ SQL 脚本执行失败: " . $e->getMessage());
                    return 1;
                }
            } else {
                // 使用 Laravel 迁移文件
                $this->info("正在执行数据库迁移...");
                
                $migrateCommand = 'migrate';
                if ($this->option('fresh')) {
                    $migrateCommand = 'migrate:fresh';
                }

                $exitCode = Artisan::call($migrateCommand, [
                    '--database' => 'tenant',
                ]);

                if ($exitCode === 0) {
                    $this->info("✓ 数据库迁移完成");
                } else {
                    $this->error("✗ 数据库迁移失败");
                    return 1;
                }
            }

            // 执行数据填充（如果指定）
            if ($this->option('seed')) {
                $this->info("正在执行数据填充...");
                $seedExitCode = Artisan::call('db:seed', [
                    '--database' => 'tenant',
                ]);

                if ($seedExitCode === 0) {
                    $this->info("✓ 数据填充完成");
                } else {
                    $this->warn("数据填充失败");
                }
            }

            // 恢复默认数据库连接
            DB::setDefaultConnection(config('database.default'));

            $this->newLine();
            $this->info("═══════════════════════════════════════");
            $this->info("✓ 租户数据库迁移完成！");
            $this->info("═══════════════════════════════════════");

            return 0;

        } catch (\Exception $e) {
            // 确保恢复默认数据库连接
            try {
                DB::setDefaultConnection(config('database.default'));
            } catch (\Exception $cleanupException) {
                // 忽略清理错误
            }

            $this->error("迁移失败: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

