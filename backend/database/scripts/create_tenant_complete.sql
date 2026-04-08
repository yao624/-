-- ============================================
-- 完整租户创建脚本（手动执行版本）
-- 说明：此脚本用于手动创建租户和数据库
-- 执行方式：在主数据库中执行此脚本
-- ============================================

-- 步骤 1：设置变量（请根据实际情况修改）
SET @tenant_email = 'tenant@example.com';              -- 租户登录邮箱
SET @tenant_name = '测试租户';                          -- 租户名称
SET @database_name = 'tenant_test_db';                 -- 租户数据库名称
SET @database_host = '127.0.0.1';                      -- 数据库主机
SET @database_port = 3306;                             -- 数据库端口
SET @database_username = 'root';                       -- 数据库用户名
SET @database_password = 'password';                   -- 数据库密码

-- 步骤 2：生成 UUID 和 ULID（需要手动生成或使用 MySQL 函数）
-- UUID: 可以使用 UUID() 函数
-- ULID: 需要手动生成（可以使用在线工具或 Laravel 的 Str::ulid()）
SET @tenant_uuid = UUID();                             -- 自动生成 UUID
SET @tenant_id = '01ARZ3NDEKTSV4RRFFQ69G5FAV';        -- 手动生成 ULID（请替换）

-- 步骤 3：在主数据库中插入租户记录
INSERT INTO `tenants` (
    `id`,
    `uuid`,
    `email`,
    `name`,
    `database_name`,
    `database_host`,
    `database_port`,
    `database_username`,
    `database_password`,
    `status`,
    `created_at`,
    `updated_at`
) VALUES (
    @tenant_id,
    @tenant_uuid,
    @tenant_email,
    @tenant_name,
    @database_name,
    @database_host,
    @database_port,
    @database_username,
    @database_password,
    'active',
    NOW(),
    NOW()
);

-- 步骤 4：创建租户数据库
-- 注意：需要使用有 CREATE DATABASE 权限的数据库用户执行
-- 如果租户数据库在不同的服务器上，需要在该服务器上执行此 SQL
-- 注意：MySQL 变量不能直接用于数据库名称，需要手动替换 @database_name 的值
SET @create_db_sql = CONCAT('CREATE DATABASE IF NOT EXISTS `', @database_name, '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
PREPARE stmt FROM @create_db_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 步骤 4.5：在主数据库中添加 tenant_uuid 字段（只需执行一次，如果已存在可跳过）
-- 注意：此步骤只需要在主数据库中执行一次，不是每个租户都需要执行
-- 如果 personal_access_tokens 表已经有 tenant_uuid 字段，可以跳过此步骤
-- ALTER TABLE `personal_access_tokens` 
-- ADD COLUMN IF NOT EXISTS `tenant_uuid` CHAR(36) NULL COMMENT '租户 UUID' AFTER `tokenable_type`,
-- ADD INDEX IF NOT EXISTS `idx_tenant_uuid` (`tenant_uuid`);

-- 步骤 5：在租户数据库中创建初始用户（可选）
-- 注意：需要先切换到租户数据库，或使用 USE 语句
-- 注意：需要先执行迁移创建 users 表，或者手动创建 users 表
SET @use_db_sql = CONCAT('USE `', @database_name, '`');
PREPARE stmt2 FROM @use_db_sql;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 检查 users 表是否存在（如果不存在，需要先执行迁移）
-- 如果表已存在，可以插入初始用户
-- 注意：需要手动生成 ULID 作为用户 ID
SET @user_id = '01ARZ3NDEKTSV4RRFFQ69G5FAW';           -- 手动生成 ULID（请替换，必须与 tenant_id 不同）
SET @user_name = 'admin';
SET @user_email = @tenant_email;                       -- 必须与租户 email 一致
SET @user_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; -- password 的哈希值

-- 插入用户（如果 users 表已存在）
-- 注意：必须先执行迁移创建 users 表，或者手动创建 users 表
-- 取消下面的注释以创建初始用户
INSERT INTO `users` (
    `id`,
    `name`,
    `email`,
    `password`,
    `created_at`,
    `updated_at`
) VALUES (
    @user_id,
    @user_name,
    @user_email,
    @user_password,
    NOW(),
    NOW()
);

-- ============================================
-- 完整执行步骤
-- ============================================
-- 步骤 A：准备工作
--   1. 修改步骤 1 中的变量值
--   2. 生成 ULID（可以使用在线工具或 Laravel: Str::ulid()）
--      - tenant_id: 租户记录的 ULID
--      - user_id: 用户记录的 ULID（必须与 tenant_id 不同）
--   3. 生成密码哈希（使用 Laravel: Hash::make('password') 或在线工具）
--
-- 步骤 B：在主数据库中执行
--   1. 确保 tenants 表已创建（执行 create_tenants_table.sql）
--   2. 确保 personal_access_tokens 表有 tenant_uuid 字段（执行 add_tenant_uuid_to_personal_access_tokens_table.sql，只需执行一次）
--   3. 执行步骤 2-3（创建租户记录）
--   4. 执行步骤 4.5（添加 tenant_uuid 字段，如果还没有）
--
-- 步骤 C：在租户数据库服务器上执行
--   1. 执行步骤 4（创建数据库）
--     注意：如果数据库在不同的服务器，需要在那个服务器上执行
--
-- 步骤 D：在租户数据库中执行迁移
--   1. 执行迁移：php artisan tenant:migrate {tenant_uuid}
--     或者手动创建所有表结构
--
-- 步骤 E：在租户数据库中创建初始用户
--   1. 执行步骤 5（切换到租户数据库）
--   2. 取消步骤 6 的注释并执行（创建初始用户）
--
-- 步骤 F：测试登录
--   1. 使用邮箱和密码登录系统

-- ============================================
-- 生成密码哈希的方法
-- ============================================
-- 方法 1：使用 Laravel Tinker
-- php artisan tinker
-- >>> Hash::make('password')
--
-- 方法 2：使用在线工具
-- https://bcrypt-generator.com/
--
-- 方法 3：使用 PHP
-- <?php
-- echo password_hash('password', PASSWORD_BCRYPT);
-- ?>

