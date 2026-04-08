-- ============================================
-- 主数据库完整建表脚本
-- 说明：此脚本用于创建主数据库中的所有表
-- 主数据库连接：使用 config/database.php 中的 'mysql' 连接
-- 数据库名称：由环境变量 DB_DATABASE 指定
-- 执行方式：在主数据库中手动执行此脚本
-- ============================================

-- ============================================
-- 1. 租户管理表（必需）
-- ============================================

CREATE TABLE IF NOT EXISTS `tenants` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID 主键',
    `uuid` CHAR(36) NOT NULL UNIQUE COMMENT '租户唯一标识 UUID',
    `email` VARCHAR(255) NOT NULL UNIQUE COMMENT '租户登录邮箱（用于查找租户）',
    `name` VARCHAR(255) NULL COMMENT '租户名称',
    `database_name` VARCHAR(255) NOT NULL COMMENT '租户数据库名称',
    `database_host` VARCHAR(255) NOT NULL DEFAULT '127.0.0.1' COMMENT '数据库主机地址',
    `database_port` INT NOT NULL DEFAULT 3306 COMMENT '数据库端口',
    `database_username` VARCHAR(255) NOT NULL COMMENT '数据库用户名',
    `database_password` VARCHAR(255) NOT NULL COMMENT '数据库密码',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active' COMMENT '租户状态',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_email` (`email`),
    INDEX `idx_uuid` (`uuid`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='租户管理表（主数据库）';

-- ============================================
-- 2. Sanctum Token 表（必需）
-- ============================================

-- 2.1 创建 personal_access_tokens 表（如果不存在）
-- 注意：如果已经通过 Laravel 迁移创建过，可以跳过此步骤
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `tokenable_type` VARCHAR(255) NOT NULL,
    `tokenable_id` CHAR(26) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `abilities` TEXT NULL,
    `last_used_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_tokenable` (`tokenable_type`, `tokenable_id`),
    INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 为 personal_access_tokens 表添加 tenant_uuid 字段（如果不存在）
-- 注意：如果字段已存在，此语句会报错，可以忽略
ALTER TABLE `personal_access_tokens` 
ADD COLUMN IF NOT EXISTS `tenant_uuid` CHAR(36) NULL COMMENT '租户 UUID' AFTER `tokenable_type`,
ADD INDEX IF NOT EXISTS `idx_tenant_uuid` (`tenant_uuid`);

-- 如果 MySQL 版本不支持 IF NOT EXISTS，使用以下方式：
-- 先检查字段是否存在，如果不存在再添加
-- SET @dbname = DATABASE();
-- SET @tablename = 'personal_access_tokens';
-- SET @columnname = 'tenant_uuid';
-- SET @preparedStatement = (SELECT IF(
--   (
--     SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
--     WHERE
--       (TABLE_SCHEMA = @dbname)
--       AND (TABLE_NAME = @tablename)
--       AND (COLUMN_NAME = @columnname)
--   ) > 0,
--   'SELECT 1',
--   CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `tenant_uuid` CHAR(36) NULL COMMENT ''租户 UUID'' AFTER `tokenable_type`, ADD INDEX `idx_tenant_uuid` (`tenant_uuid`)')
-- ));
-- PREPARE alterIfNotExists FROM @preparedStatement;
-- EXECUTE alterIfNotExists;
-- DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 3. 迁移记录表（Laravel 必需）
-- ============================================

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL,
    INDEX `idx_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 使用说明
-- ============================================
-- 1. 主数据库连接配置：
--    - 连接名称：mysql（在 config/database.php 中定义）
--    - 数据库名称：由环境变量 DB_DATABASE 指定
--    - 主机：由环境变量 DB_HOST 指定（默认 127.0.0.1）
--    - 端口：由环境变量 DB_PORT 指定（默认 3306）
--    - 用户名：由环境变量 DB_USERNAME 指定
--    - 密码：由环境变量 DB_PASSWORD 指定
--
-- 2. 执行步骤：
--    a. 连接到主数据库
--    b. 执行此脚本
--    c. 验证表是否创建成功：
--       - SELECT COUNT(*) FROM tenants;
--       - SELECT COUNT(*) FROM personal_access_tokens;
--       - SHOW COLUMNS FROM personal_access_tokens LIKE 'tenant_uuid';
--
-- 3. 注意事项：
--    - tenants 表是必需的，用于存储所有租户信息
--    - personal_access_tokens 表是 Sanctum 认证必需的
--    - tenant_uuid 字段用于关联 Token 和租户
--    - 如果 personal_access_tokens 表已存在但没有 tenant_uuid 字段，需要执行步骤 2.2
--
-- 4. 验证主数据库配置：
--    查看 .env 文件中的以下配置：
--    DB_CONNECTION=mysql
--    DB_HOST=127.0.0.1
--    DB_PORT=3306
--    DB_DATABASE=firefly_main
--    DB_USERNAME=your_username
--    DB_PASSWORD=your_password

