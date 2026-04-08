-- ============================================
-- XMP 素材库（素材管理）完整建表脚本
-- 说明：
-- 1. 该脚本用于租户库（tenant DB）中创建 meta_* 相关表
-- 2. 不包含外键约束（避免依赖主库表结构/类型差异导致执行失败）
-- 3. 主键默认使用 BIGINT(20) + AUTO_INCREMENT，可按需要替换为 ULID/UUID
--
-- 执行方式（示例）：
--   mysql -u <user> -p <tenant_database> < create_xmp_material_management_tables.sql
-- ============================================

SET @charset := 'utf8mb4';
SET @collation := 'utf8mb4_unicode_ci';

-- 先 drop（按依赖顺序：关联表 -> 主表）
DROP TABLE IF EXISTS `meta_material_usages`;
DROP TABLE IF EXISTS `meta_material_favorites`;
DROP TABLE IF EXISTS `meta_media_material_sync`;
DROP TABLE IF EXISTS `meta_media_materials`;
DROP TABLE IF EXISTS `meta_material_statistics`;
DROP TABLE IF EXISTS `meta_material_tags`;
DROP TABLE IF EXISTS `meta_upload_batches`;
DROP TABLE IF EXISTS `meta_material_tags`;
DROP TABLE IF EXISTS `meta_materials`;
DROP TABLE IF EXISTS `meta_folders`;
DROP TABLE IF EXISTS `meta_material_groups`;
DROP TABLE IF EXISTS `meta_designers`;
DROP TABLE IF EXISTS `meta_creators`;
DROP TABLE IF EXISTS `meta_tags`;
DROP TABLE IF EXISTS `meta_enterprise_libraries`;

-- =========================
-- 1) 标签表（meta_tags）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_tags` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 标签名称（唯一）
  `tag_name` VARCHAR(100) NOT NULL COMMENT '标签名称（唯一）',
  -- 标签颜色（可选）
  `tag_color` VARCHAR(20) NULL COMMENT '标签颜色（可选）',
  -- 0-普通 1-系统
  `tag_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0-普通 1-系统',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_tags_name` (`tag_name`),
  KEY `idx_meta_tags_type` (`tag_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_tags 标签表';

-- =========================
-- 2) 设计师表（meta_designers）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_designers` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 设计师名称
  `designer_name` VARCHAR(100) NOT NULL COMMENT '设计师名称',
  -- 设计师编码（可选）
  `designer_code` VARCHAR(50) NULL COMMENT '设计师编码（可选）',
  -- 0-禁用 1-启用
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0-禁用 1-启用',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_designers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_designers 设计师表';

-- =========================
-- 3) 创意人表（meta_creators）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_creators` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 创意人名称
  `creator_name` VARCHAR(100) NOT NULL COMMENT '创意人名称',
  -- 创意人编码（可选）
  `creator_code` VARCHAR(50) NULL COMMENT '创意人编码（可选）',
  -- 0-禁用 1-启用
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0-禁用 1-启用',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_creators_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_creators 创意人表';

-- =========================
-- 4) 素材组表（meta_material_groups）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_material_groups` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 分组名称
  `group_name` VARCHAR(100) NOT NULL COMMENT '分组名称',
  -- 分组描述（可选）
  `group_desc` VARCHAR(500) NULL COMMENT '分组描述（可选）',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_material_groups_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_material_groups 素材组表';

-- =========================
-- 5) 文件夹表（meta_folders）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_folders` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 文件夹名称
  `folder_name` VARCHAR(100) NOT NULL COMMENT '文件夹名称',
  -- 自关联：父文件夹ID，0表示根目录
  `parent_id` BIGINT(20) UNSIGNED NULL DEFAULT 0 COMMENT '自关联：父文件夹ID，0表示根目录',
  -- 完整路径（建议后端计算并写入）
  `folder_path` VARCHAR(1000) NOT NULL COMMENT '完整路径（建议后端计算并写入）',
  -- 0-个人 1-企业
  `library_type` TINYINT(1) NOT NULL COMMENT '0-个人 1-企业',
  -- owner_id：沿用 owner_id（个人/部门/企业归属都可放这里）
  `owner_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'owner_id：沿用 owner_id（个人/部门/企业归属都可放这里）',
  -- 可选：用于限制最大20级、排序等
  -- 层级（用于限制/展示层级）
  `level` INT NULL COMMENT '层级（用于限制/展示层级）',
  -- 排序序号
  `sort_order` INT NULL COMMENT '排序序号',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  -- 软删除（删除文件夹保留历史）
  `deleted_at` DATETIME NULL DEFAULT NULL COMMENT '软删除（删除文件夹保留历史）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_folders_parent` (`parent_id`),
  KEY `idx_meta_folders_owner_type` (`owner_id`, `library_type`),
  KEY `idx_meta_folders_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_folders 文件夹表';

-- =========================
-- 6) 企业素材库表（meta_enterprise_libraries）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_enterprise_libraries` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 素材库名称
  `library_name` VARCHAR(100) NOT NULL COMMENT '素材库名称',
  -- 企业ID（归属企业）
  `enterprise_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '企业ID（归属企业）',
  -- 管理员ID（拥有管理权限）
  `manager_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '管理员ID（拥有管理权限）',
  -- 共享范围 JSON（思维导图：共享范围(JSON格式)）
  `shared_scope` JSON NULL COMMENT '共享范围 JSON（思维导图：共享范围(JSON格式)）',
  -- 思维导图弹窗项：开启审核状态（额外字段，便于落库）
  `review_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '思维导图弹窗项：开启审核状态（额外字段，便于落库）',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_enterprise_libraries_enterprise` (`enterprise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_enterprise_libraries 企业素材库表';

-- =========================
-- 7) 上传批次表（meta_upload_batches）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_upload_batches` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 上传批次编码（批次唯一标识）
  `batch_code` VARCHAR(50) NOT NULL COMMENT '上传批次编码（批次唯一标识）',
  -- 0-单文件 1-文件夹 2-批量
  `upload_type` TINYINT(1) NOT NULL COMMENT '0-单文件 1-文件夹 2-批量',
  -- 总数
  `total_count` INT NOT NULL DEFAULT 0 COMMENT '总数',
  -- 成功数
  `success_count` INT NOT NULL DEFAULT 0 COMMENT '成功数',
  -- 失败数
  `fail_count` INT NOT NULL DEFAULT 0 COMMENT '失败数',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_upload_batches_batch_code` (`batch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_upload_batches 上传批次表';

-- =========================
-- 7.1) 上传会话表（meta_upload_sessions）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_upload_sessions` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 上传会话编码（会话唯一标识）
  `session_code` VARCHAR(50) NOT NULL COMMENT '上传会话编码（唯一）',
  -- 用户ID（来自鉴权，便于清理与安全校验）
  `owner_id` BIGINT(20) UNSIGNED NULL COMMENT '用户ID（可空）',
  -- 目标文件夹ID
  `folder_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '目标文件夹ID',
  -- 0:file 1:folder
  `upload_mode` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '上传模式：0-文件 1-文件夹',
  -- 0:regular 1:playable
  `material_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '素材类型',
  -- 标签模式：0-unified 1-smart（MVP：smart 忽略）
  `tag_mode` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '标签模式',
  -- 标签ID集合（JSON）
  `tag_ids_json` JSON NULL COMMENT '标签ID集合（JSON）',
  -- 设计师模式：0-unified 1-smart（MVP：smart 忽略）
  `designer_mode` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '设计师模式',
  `designer_id` BIGINT(20) UNSIGNED NULL COMMENT '设计师ID',
  -- 创意人模式：0-unified 1-smart（MVP：smart 忽略）
  `creator_mode` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '创意人模式',
  `creator_id` BIGINT(20) UNSIGNED NULL COMMENT '创意人ID',
  -- 素材组：当前上传接口只使用第一个值（与旧逻辑保持一致）
  `material_group_id` BIGINT(20) UNSIGNED NULL COMMENT '素材组ID',
  -- 批量前缀（用于 local_id / batch_code）
  `batch_prefix` VARCHAR(50) NULL COMMENT '批量前缀',
  -- 0-uploading 1-committed 2-cancelled/expired
  `status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '会话状态',
  -- 过期时间（用于后续清理，前端可按需触发删除）
  `expires_at` DATETIME NULL COMMENT '过期时间（用于清理）',
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` DATETIME NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_upload_sessions_session_code` (`session_code`),
  KEY `idx_meta_upload_sessions_owner` (`owner_id`),
  KEY `idx_meta_upload_sessions_folder` (`folder_id`),
  KEY `idx_meta_upload_sessions_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_upload_sessions 上传会话表';

-- =========================
-- 7.2) 会话文件清单表（meta_upload_session_files）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_upload_session_files` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 会话ID
  `session_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '上传会话ID',
  -- 文件键：在同一会话内唯一，用于续传幂等
  `file_key` VARCHAR(200) NOT NULL COMMENT '文件键（同一会话唯一）',
  -- 文件在选择列表里的顺序
  `file_index` INT NOT NULL COMMENT '文件序号（用于 local_id 稳定生成）',
  -- 原文件名
  `file_name` VARCHAR(255) NOT NULL COMMENT '原文件名',
  -- 文件大小
  `file_size` BIGINT(20) NULL COMMENT '文件大小',
  -- 总 chunk 数
  `chunk_total` INT NOT NULL COMMENT '总分片数',
  -- 已收到 chunk 数
  `received_chunks_count` INT NOT NULL DEFAULT 0 COMMENT '已收到分片数',
  -- 0-pending 1-success 2-failed
  `upload_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '文件上传状态',
  -- 失败原因（可空）
  `error_message` VARCHAR(500) NULL COMMENT '失败原因',
  -- 文件夹模式下的相对路径（用于 commit 生成 folder_path）
  `relative_path` VARCHAR(500) NULL COMMENT 'relativePath（folder 模式使用）',
  -- commit 后落库的 local_id/material_id
  `local_id` VARCHAR(50) NULL COMMENT '落库 Local ID',
  `material_id` BIGINT(20) UNSIGNED NULL COMMENT '落库素材ID',
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` DATETIME NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_upload_session_files_session_filekey` (`session_id`, `file_key`),
  KEY `idx_meta_upload_session_files_session_index` (`session_id`, `file_index`),
  KEY `idx_meta_upload_session_files_session_status` (`session_id`, `upload_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_upload_session_files 上传会话文件清单';

-- =========================
-- 7.3) 会话分片表（meta_upload_session_chunks）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_upload_session_chunks` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 会话文件ID
  `session_file_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '会话文件ID',
  -- chunk 序号（从 0 开始）
  `chunk_index` INT NOT NULL COMMENT '分片序号（从0开始）',
  -- 写入大小（可空）
  `chunk_size` INT NULL COMMENT '分片大小',
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_upload_session_chunks_file_chunk` (`session_file_id`, `chunk_index`),
  KEY `idx_meta_upload_session_chunks_session_file_id` (`session_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_upload_session_chunks 上传会话分片状态';

-- =========================
-- 8) 素材主表（meta_materials）
-- =========================
-- 注：meta_tag 为“系统预设生命周期枚举”
CREATE TABLE IF NOT EXISTS `meta_materials` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- Local ID：同一素材家族 key
  `local_id` VARCHAR(50) NULL COMMENT 'Local ID：同一素材家族 key',
  -- 素材名称
  `material_name` VARCHAR(255) NOT NULL COMMENT '素材名称',
  -- 0-常规 1-试玩
  `material_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0-常规 1-试玩',
  -- 基础变体：版本号（额外字段，来自你的计划文档）
  `version_no` INT NOT NULL DEFAULT 1 COMMENT '基础变体：版本号（额外字段，来自你的计划文档）',
  -- 主文件地址（存储路径/URL）
  `file_url` VARCHAR(500) NOT NULL COMMENT '主文件地址（存储路径/URL）',
  -- 缩略图地址（可选）
  `thumbnail_url` VARCHAR(500) NULL COMMENT '缩略图地址（可选）',
  -- 文件大小（字节数，可选）
  `file_size` BIGINT(20) NULL COMMENT '文件大小（字节数，可选）',
  -- jpg/png/mp4 等
  `file_format` VARCHAR(20) NULL COMMENT 'jpg/png/mp4 等',
  -- 图片宽度（可空，非图片可为空）
  `width` INT NULL COMMENT '图片宽度（可空，非图片可为空）',
  -- 图片高度（可空，非图片可为空）
  `height` INT NULL COMMENT '图片高度（可空，非图片可为空）',
  -- 所属文件夹ID（可空）
  `folder_id` BIGINT(20) UNSIGNED NULL COMMENT '所属文件夹ID（可空）',
  -- 设计师ID（可空）
  `designer_id` BIGINT(20) UNSIGNED NULL COMMENT '设计师ID（可空）',
  -- 创意人ID（可空）
  `creator_id` BIGINT(20) UNSIGNED NULL COMMENT '创意人ID（可空）',
  -- XMP生命周期枚举：0..7
  `meta_tag` TINYINT(1) NULL COMMENT 'XMP生命周期枚举：0..7',
  -- 0-上传中 1-上传成功 2-上传失败
  `upload_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0-上传中 1-上传成功 2-上传失败',
  -- 0-待审核 1-审核通过 2-审核拒绝
  `audit_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0-待审核 1-审核通过 2-审核拒绝',
  -- 素材来源（可选，平台/渠道等）
  `source` VARCHAR(50) NULL COMMENT '素材来源（可选，平台/渠道等）',
  -- 评分（0.5~5.0，支持半星）
  `rating` DECIMAL(2,1) NULL COMMENT '评分（0.5~5.0，支持半星）',
  -- 审核拒绝原因（可选）
  `reject_reason` VARCHAR(500) NULL COMMENT '审核拒绝原因（可选）',
  -- 创建时间
  `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  -- 素材组ID（可选）
  `material_group_id` BIGINT(20) UNSIGNED NULL COMMENT '素材组ID（可选）',
  -- 备注（可选）
  `remarks` VARCHAR(500) NULL COMMENT '备注（可选）',
  -- 关联上传批次（额外字段，计划文档建议）
  `upload_batch_id` BIGINT(20) UNSIGNED NULL COMMENT '关联上传批次（额外字段，计划文档建议）',
  -- Mindworks标记：禁止下载与导出（额外字段，计划文档建议）
  `mindworks_locked` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Mindworks标记：禁止下载与导出（额外字段，计划文档建议）',
  -- 软删除（删除素材保留历史）
  `deleted_at` DATETIME NULL DEFAULT NULL COMMENT '软删除（删除素材保留历史）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_materials_folder` (`folder_id`),
  KEY `idx_meta_materials_designer` (`designer_id`),
  KEY `idx_meta_materials_creator` (`creator_id`),
  KEY `idx_meta_materials_type` (`material_type`),
  KEY `idx_meta_materials_audit` (`audit_status`),
  KEY `idx_meta_materials_upload_status` (`upload_status`),
  KEY `idx_meta_materials_meta_tag` (`meta_tag`),
  KEY `idx_meta_materials_rating` (`rating`),
  KEY `idx_meta_materials_localid_version` (`local_id`, `version_no`),
  KEY `idx_meta_materials_deleted_at` (`deleted_at`),
  KEY `idx_meta_materials_folder_deleted_at` (`folder_id`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_materials 素材主表';

-- =========================
-- 9) 素材标签关联表（meta_material_tags）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_material_tags` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 素材ID
  `material_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '素材ID',
  -- 标签ID
  `tag_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '标签ID',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_material_tags_material_tag` (`material_id`, `tag_id`),
  KEY `idx_meta_material_tags_tag` (`tag_id`),
  KEY `idx_meta_material_tags_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_material_tags 素材标签关联表';

-- =========================
-- 10) 素材数据统计表（meta_material_statistics）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_material_statistics` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 素材ID
  `material_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '素材ID',
  -- 制作/生产成本（可空）
  `production_cost` DECIMAL(10,2) NULL COMMENT '制作/生产成本（可空）',
  -- 花费 spend（可空）
  `spend` DECIMAL(10,2) NULL COMMENT '花费 spend（可空）',
  -- 曝光次数（可空）
  `impressions` INT NULL COMMENT '曝光次数（可空）',
  -- CPM（可空）
  `cpm` DECIMAL(10,2) NULL COMMENT 'CPM（可空）',
  -- 点击次数（可空）
  `clicks` INT NULL COMMENT '点击次数（可空）',
  -- CPC（可空）
  `cpc` DECIMAL(10,2) NULL COMMENT 'CPC（可空）',
  -- CTR（可空）
  `ctr` DECIMAL(5,2) NULL COMMENT 'CTR（可空）',
  -- 转化次数（可空）
  `conversions` INT NULL COMMENT '转化次数（可空）',
  -- CPA（可空）
  `cpa` DECIMAL(10,2) NULL COMMENT 'CPA（可空）',
  -- 关联创意数量（默认为0）
  `associated_creative_count` INT NOT NULL DEFAULT 0 COMMENT '关联创意数量（默认为0）',
  -- 统计起始日期
  `statistics_date` DATE NOT NULL COMMENT '统计起始日期',
  -- 统计结束日期（可空，支持区间统计）
  `statistics_end_date` DATE NULL COMMENT '统计结束日期（可空，支持区间统计）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_material_statistics_material_date` (`material_id`, `statistics_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_material_statistics 素材数据统计表';

-- =========================
-- 11) 媒体素材表（meta_media_materials）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_media_materials` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 素材ID（外链到 meta_materials，类型按你选择的实现方式）
  `material_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '素材ID（外链到 meta_materials）',
  -- 媒体素材名称
  `name` VARCHAR(255) NOT NULL COMMENT '媒体素材名称',
  -- 媒体/平台渠道
  `channel` VARCHAR(50) NOT NULL COMMENT '媒体/平台渠道',
  -- 使用账户（可选）
  `use_account` VARCHAR(255) NULL COMMENT '使用账户（可选）',
  -- 归属账户（可选）
  `belong_account` VARCHAR(255) NULL COMMENT '归属账户（可选）',
  -- 文件大小/尺寸字符串（可选）
  `size` VARCHAR(20) NULL COMMENT '文件大小/尺寸字符串（可选）',
  -- 时长（可选，单位由你约定）
  `duration` DECIMAL(10,2) NULL COMMENT '时长（可选，单位由你约定）',
  -- 形状/比例（可选）
  `shape` VARCHAR(20) NULL COMMENT '形状/比例（可选）',
  -- 格式（可选）
  `format` VARCHAR(20) NULL COMMENT '格式（可选）',
  -- 来源（可选）
  `source` VARCHAR(50) NULL COMMENT '来源（可选）',
  -- 拒绝/失败信息（可选）
  `reject_info` VARCHAR(500) NULL COMMENT '拒绝/失败信息（可选）',
  -- 媒体素材备注（可选）
  `material_note` VARCHAR(500) NULL COMMENT '媒体素材备注（可选）',
  -- 创建时间
  `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_media_materials_material` (`material_id`),
  KEY `idx_meta_media_materials_channel_account` (`channel`, `belong_account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_media_materials 媒体素材表';

-- =========================
-- 12) 媒体素材同步表（meta_media_material_sync）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_media_material_sync` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 账户ID
  `account_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '账户ID',
  -- 渠道
  `channel` VARCHAR(50) NOT NULL COMMENT '渠道',
  -- 0-待同步 1-同步中 2-同步成功 3-同步失败
  `sync_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0-待同步 1-同步中 2-同步成功 3-同步失败',
  -- 总数
  `total_count` INT NOT NULL DEFAULT 0 COMMENT '总数',
  -- 成功数
  `success_count` INT NOT NULL DEFAULT 0 COMMENT '成功数',
  -- 失败数
  `fail_count` INT NOT NULL DEFAULT 0 COMMENT '失败数',
  -- 同步时间
  `sync_time` DATETIME NOT NULL COMMENT '同步时间',
  -- 错误信息（可空）
  `error_message` VARCHAR(500) NULL COMMENT '错误信息（可空）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_media_material_sync_account_channel_time` (`account_id`, `channel`, `sync_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_media_material_sync 媒体素材同步表';

-- =========================
-- 13) 素材使用记录/引用关系（meta_material_usages）（额外表：来自你的计划文档）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_material_usages` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 素材ID
  `material_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '素材ID',
  -- 使用类型（例如 campaign/adset 等，按你计划文档）
  `usage_type` VARCHAR(100) NOT NULL COMMENT '使用类型（例如 campaign/adset 等，按你计划文档）',
  -- 引用对象类型
  `ref_type` VARCHAR(100) NOT NULL COMMENT '引用对象类型',
  -- 引用对象 id（类型可能多样，因此用字符串容器）
  `ref_id` VARCHAR(50) NOT NULL COMMENT '引用对象 id（类型可能多样，因此用字符串容器）',
  -- 使用时间
  `used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '使用时间',
  -- 操作人ID（可空）
  `operator_id` BIGINT(20) UNSIGNED NULL COMMENT '操作人ID（可空）',
  -- 额外元数据（可选）
  `metadata` JSON NULL COMMENT '额外元数据（可选）',
  -- 创建时间（可空）
  `create_time` DATETIME NULL DEFAULT NULL COMMENT '创建时间（可空）',
  -- 更新时间（可空）
  `update_time` DATETIME NULL DEFAULT NULL COMMENT '更新时间（可空）',
  PRIMARY KEY (`id`),
  KEY `idx_meta_material_usages_material_used_at` (`material_id`, `used_at`),
  KEY `idx_meta_material_usages_ref` (`ref_type`, `ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_material_usages 素材使用记录/引用关系表';

-- =========================
-- 14) 我的收藏（meta_material_favorites）（额外表：来自你的计划文档）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_material_favorites` (
  -- 主键
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  -- 收藏者ID（owner_id）
  `owner_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '收藏者ID（owner_id）',
  -- 被收藏的素材ID
  `material_id` BIGINT(20) UNSIGNED NOT NULL COMMENT '被收藏的素材ID',
  -- 收藏创建时间
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_material_favorites_owner_material` (`owner_id`, `material_id`),
  KEY `idx_meta_material_favorites_owner` (`owner_id`),
  KEY `idx_meta_material_favorites_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='meta_material_favorites 我的收藏表';

-- =========================
-- 15) 拒审信息筛选配置表（meta_reject_reason_options）
-- =========================
CREATE TABLE IF NOT EXISTS `meta_reject_reason_options` (
  `id` INT UNSIGNED NOT NULL COMMENT '选项ID（前端筛选值）',
  `option_code` VARCHAR(64) NOT NULL COMMENT '内部编码',
  `option_label` VARCHAR(100) NOT NULL COMMENT '显示文案',
  `channel_scope` VARCHAR(32) NOT NULL DEFAULT 'all' COMMENT '渠道范围：all/meta/google/tiktok/mintegral/unity',
  `reject_state` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '拒审状态：0-未拒审 1-有拒审记录',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_reject_reason_options_code` (`option_code`),
  KEY `idx_meta_reject_reason_options_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='拒审信息筛选配置表';

INSERT INTO `meta_reject_reason_options`
  (`id`, `option_code`, `option_label`, `channel_scope`, `reject_state`, `sort_order`, `is_active`)
VALUES
  (1, 'all_none', '所有渠道：未拒审', 'all', 0, 10, 1),
  (2, 'meta_none', 'Meta：未拒审', 'meta', 0, 20, 1),
  (3, 'meta_has', 'Meta：有拒审记录', 'meta', 1, 30, 1),
  (4, 'google_none', 'Google：未拒审', 'google', 0, 40, 1),
  (5, 'google_has', 'Google：有拒审记录', 'google', 1, 50, 1),
  (6, 'tiktok_none', 'Tiktok：未拒审', 'tiktok', 0, 60, 1),
  (7, 'tiktok_has', 'Tiktok：有拒审记录', 'tiktok', 1, 70, 1),
  (8, 'mintegral_none', 'Mintegral：未拒审', 'mintegral', 0, 80, 1),
  (9, 'mintegral_has', 'Mintegral：有拒审记录', 'mintegral', 1, 90, 1),
  (10, 'unity_none', 'Unity：未拒审', 'unity', 0, 100, 1),
  (11, 'unity_has', 'Unity：有拒审记录', 'unity', 1, 110, 1)
ON DUPLICATE KEY UPDATE
  `option_label` = VALUES(`option_label`),
  `channel_scope` = VALUES(`channel_scope`),
  `reject_state` = VALUES(`reject_state`),
  `sort_order` = VALUES(`sort_order`),
  `is_active` = VALUES(`is_active`);

-- ============================================
-- Done
-- ============================================

--20260401
-- 1) 系统标签数据表（字典表）
CREATE TABLE IF NOT EXISTS `meta_system_tags` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag_key` VARCHAR(64) NOT NULL COMMENT '稳定key(用于程序逻辑/多语言)',
  `tag_name` VARCHAR(64) NOT NULL COMMENT '展示名(可后续做多语言)',
  `tag_group` VARCHAR(64) NULL DEFAULT 'system_tag' COMMENT '分组/来源',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_system_tags_tag_key` (`tag_key`),
  KEY `idx_meta_system_tags_group_active_sort` (`tag_group`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统标签字典表';
-- 2) 素材-系统标签关联表（多对多）
CREATE TABLE IF NOT EXISTS `meta_material_system_tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `system_tag_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_material_tag` (`material_id`, `system_tag_id`),
  KEY `idx_tag_material` (`system_tag_id`, `material_id`),
  CONSTRAINT `fk_mmst_material` FOREIGN KEY (`material_id`) REFERENCES `meta_materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mmst_tag` FOREIGN KEY (`system_tag_id`) REFERENCES `meta_system_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='素材-系统标签关联表';
-- 3) 初始化固定选项（按你目前UI）
INSERT INTO `meta_system_tags` (`tag_key`, `tag_name`, `tag_group`, `sort_order`, `is_active`)
VALUES
  ('unused', '未使用', 'system_tag', 10, 1),
  ('no_spend', '无消耗', 'system_tag', 20, 1),
  ('has_spend', '有消耗', 'system_tag', 30, 1),
  ('youtube', 'Youtube', 'system_tag', 40, 1),
  ('new_7d', '近7天新素材', 'system_tag', 50, 1),
  ('low_efficiency', '低效素材', 'system_tag', 60, 1)
ON DUPLICATE KEY UPDATE
  `tag_name` = VALUES(`tag_name`),
  `tag_group` = VALUES(`tag_group`),
  `sort_order` = VALUES(`sort_order`),
  `is_active` = VALUES(`is_active`);

-- 20260401 rating增量兼容（已存在库可直接执行）
ALTER TABLE `meta_materials`
  ADD COLUMN IF NOT EXISTS `rating` DECIMAL(2,1) NULL COMMENT '评分（0.5~5.0，支持半星）' AFTER `source`,
  ADD INDEX IF NOT EXISTS `idx_meta_materials_rating` (`rating`);
