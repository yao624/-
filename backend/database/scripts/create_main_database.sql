-- ============================================
-- 主数据库创建脚本
-- 说明：此脚本用于创建主数据库（如果不存在）
-- 执行方式：在 MySQL 服务器上手动执行此脚本（不需要先选择数据库）
-- ============================================

-- ============================================
-- 使用说明
-- ============================================
-- 1. 主数据库名称已设置为 'firefly_main'
--    如需修改，请编辑下面的 @db_name 变量
--
-- 2. 数据库名称应该与 .env 文件中的 DB_DATABASE 保持一致
--
-- 3. 执行方式：
--    mysql -u root -p < create_main_database.sql
--    或者
--    在 MySQL 客户端中执行此脚本
--
-- 4. 执行后，再执行 create_main_database_tables.sql 来创建表
-- ============================================

-- ============================================
-- 创建主数据库
-- ============================================
-- 请修改下面的数据库名称
SET @db_name = 'firefly_main';  -- ← 修改这里：替换为实际的主数据库名称

-- 创建数据库（如果不存在）
SET @sql = CONCAT('CREATE DATABASE IF NOT EXISTS `', @db_name, '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 显示创建结果
SELECT CONCAT('Database ''', @db_name, ''' created successfully (or already exists)') AS result;

-- ============================================
-- 验证数据库是否创建成功
-- ============================================
-- 执行以下命令验证：
-- SHOW DATABASES LIKE 'firefly_main';
-- USE firefly_main;
-- SHOW TABLES;

-- ============================================
-- 下一步操作
-- ============================================
-- 1. 确认数据库已创建
-- 2. 执行 create_main_database_tables.sql 创建表
-- 3. 验证 .env 文件中的 DB_DATABASE 配置与此数据库名称一致

