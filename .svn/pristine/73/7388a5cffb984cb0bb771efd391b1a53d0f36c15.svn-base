-- ============================================
-- 主数据库：创建 tenants 表
-- 说明：此表存储在主数据库中，用于管理所有租户信息
-- 执行方式：手动在主数据库中执行此 SQL 脚本
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

