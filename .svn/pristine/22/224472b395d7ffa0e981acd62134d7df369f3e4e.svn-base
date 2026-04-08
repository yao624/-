<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
                            {email : 租户登录邮箱}
                            {database_name : 租户数据库名称}
                            {--name= : 租户名称（可选）}
                            {--host=127.0.0.1 : 数据库主机}
                            {--port=3306 : 数据库端口}
                            {--db-user=root : 数据库用户名}
                            {--db-password= : 数据库密码}
                            {--create-db : 是否创建数据库}
                            {--create-user : 是否创建初始用户}
                            {--user-name=admin : 初始用户名}
                            {--user-password= : 初始用户密码}
                            {--run-migrations : 是否执行迁移}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建新租户（包括数据库和初始用户）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $databaseName = $this->argument('database_name');
        $name = $this->option('name') ?? $this->ask('请输入租户名称（可选）', '');
        $host = $this->option('host');
        $port = (int) $this->option('port');
        $dbUser = $this->option('db-user');
        $dbPassword = $this->option('db-password') ?? $this->secret('请输入数据库密码');
        $createDb = $this->option('create-db');
        $createUser = $this->option('create-user');
        $userName = $this->option('user-name');
        $userPassword = $this->option('user-password') ?? $this->secret('请输入初始用户密码');
        $runMigrations = $this->option('run-migrations');

        // 验证输入
        $validator = Validator::make([
            'email' => $email,
            'database_name' => $databaseName,
            'host' => $host,
            'port' => $port,
            'db_user' => $dbUser,
            'db_password' => $dbPassword,
        ], [
            'email' => 'required|email|unique:tenants,email',
            'database_name' => 'required|regex:/^[a-z0-9_]+$/',
            'host' => 'required',
            'port' => 'required|integer|min:1|max:65535',
            'db_user' => 'required',
            'db_password' => 'required',
        ]);

        if ($validator->fails()) {
            $this->error('验证失败：');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  - ' . $error);
            }
            return 1;
        }

        try {
            DB::beginTransaction();

            // 1. 创建租户记录
            $this->info('正在创建租户记录...');
            $uuid = Str::uuid()->toString();
            $ulid = (string) Str::ulid();

            $tenant = Tenant::create([
                'id' => $ulid,
                'uuid' => $uuid,
                'email' => $email,
                'name' => $name ?: null,
                'database_name' => $databaseName,
                'database_host' => $host,
                'database_port' => $port,
                'database_username' => $dbUser,
                'database_password' => $dbPassword,
                'status' => 'active',
            ]);

            $this->info("✓ 租户记录创建成功");
            $this->line("  - UUID: {$uuid}");
            $this->line("  - ID: {$ulid}");

            // 2. 创建数据库
            if ($createDb) {
                $this->info('正在创建数据库...');
                try {
                    TenantService::createTenantDatabase($tenant);
                    $this->info("✓ 数据库创建成功: {$databaseName}");
                } catch (\Exception $e) {
                    $this->error("✗ 数据库创建失败: " . $e->getMessage());
                    DB::rollBack();
                    return 1;
                }
            } else {
                $this->warn('跳过数据库创建（使用 --create-db 选项可自动创建）');
            }

            // 3. 执行迁移
            if ($runMigrations && $createDb) {
                $this->info('正在执行数据库迁移...');
                try {
                    $this->call('tenant:migrate', ['uuid' => $uuid]);
                    $this->info('✓ 数据库迁移完成');
                } catch (\Exception $e) {
                    $this->error("✗ 数据库迁移失败: " . $e->getMessage());
                    // 迁移失败不影响租户创建，继续执行
                }
            }

            // 4. 创建初始用户
            if ($createUser && $createDb) {
                $this->info('正在创建初始用户...');
                try {
                    // 设置租户数据库连接
                    TenantService::setTenantConnection($uuid);

                    // 检查 users 表是否存在
                    if (!DB::getSchemaBuilder()->hasTable('users')) {
                        $this->warn('users 表不存在，请先执行迁移');
                    } else {
                        // 创建用户
                        $userId = (string) Str::ulid();
                        DB::table('users')->insert([
                            'id' => $userId,
                            'name' => $userName,
                            'email' => $email, // 必须与租户 email 一致
                            'password' => Hash::make($userPassword),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->info("✓ 初始用户创建成功");
                        $this->line("  - 用户名: {$userName}");
                        $this->line("  - 邮箱: {$email}");
                        $this->line("  - 密码: {$userPassword}");
                    }

                    // 恢复默认连接
                    DB::setDefaultConnection(config('database.default'));
                } catch (\Exception $e) {
                    $this->error("✗ 初始用户创建失败: " . $e->getMessage());
                    // 用户创建失败不影响租户创建，继续执行
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('═══════════════════════════════════════');
            $this->info('✓ 租户创建完成！');
            $this->info('═══════════════════════════════════════');
            $this->table(
                ['项目', '值'],
                [
                    ['租户 UUID', $uuid],
                    ['租户 ID', $ulid],
                    ['邮箱', $email],
                    ['名称', $name ?: '未设置'],
                    ['数据库名称', $databaseName],
                    ['数据库主机', "{$host}:{$port}"],
                    ['数据库状态', $createDb ? '已创建' : '未创建'],
                    ['初始用户', $createUser && $createDb ? '已创建' : '未创建'],
                ]
            );

            $this->newLine();
            $this->info('登录信息：');
            $this->line("  邮箱: {$email}");
            if ($createUser && $createDb) {
                $this->line("  用户名: {$userName}");
                $this->line("  密码: {$userPassword}");
            } else {
                $this->warn('  注意：需要在租户数据库中手动创建用户');
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('租户创建失败: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

