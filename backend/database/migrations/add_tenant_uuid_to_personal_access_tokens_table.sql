-- ============================================
-- 为 personal_access_tokens 表添加 tenant_uuid 字段
-- 说明：此字段用于存储 Token 所属的租户 UUID
-- 执行方式：手动在主数据库和所有租户数据库中执行此 SQL 脚本
-- ============================================

-- 为主数据库的 personal_access_tokens 表添加字段
ALTER TABLE `personal_access_tokens` 
ADD COLUMN `tenant_uuid` CHAR(36) NULL COMMENT '租户 UUID' AFTER `tokenable_type`,
ADD INDEX `idx_tenant_uuid` (`tenant_uuid`);

-- 注意：如果使用分库架构，需要在每个租户数据库中也执行此 SQL
-- 但通常 personal_access_tokens 表只在主数据库中，或者每个租户库都有独立的表
-- 请根据实际架构决定是否需要为每个租户库执行

