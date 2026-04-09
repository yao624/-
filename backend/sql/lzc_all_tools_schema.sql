-- lzc 全量建表汇总（来自 backend/sql/lzc 下各模块 SQL）
-- 生成时间：2026-04-01
-- 说明：仅汇总 DDL，不包含测试数据。

/* =====================================================================
 * 来源文件：tool_copy_library.sql
 * 模块：文案库
 * ===================================================================== */

-- 文案库：建表（仅 DDL）
-- 对应迁移: database/migrations/2026_03_25_100000_create_meta_copy_library_tables.php
-- 说明: 不对 users 建外键，避免与库中 users.id 类型/排序规则不一致导致 3780；由应用层保证 owner_user_id、created_by 有效。

CREATE TABLE IF NOT EXISTS `meta_copy_libraries` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `name` VARCHAR(191) NOT NULL COMMENT '文案库名称',
  `type` VARCHAR(32) NOT NULL COMMENT '库类型：personal=我的文案库，enterprise=企业文案库',
  `owner_user_id` CHAR(26) NOT NULL COMMENT '创建人用户 ID',
  `visibility_scope` JSON NULL COMMENT '企业库共享范围等扩展配置（JSON）',
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状态：active=启用，disabled=停用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_meta_copy_libraries_owner_user_id` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文案库：根库（个人/企业），对应 XMP 我的/企业文案库';

CREATE TABLE IF NOT EXISTS `meta_copy_folders` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `library_id` CHAR(26) NOT NULL COMMENT '所属文案库 ID',
  `parent_id` CHAR(26) NULL COMMENT '父文件夹 ID，根目录为 NULL',
  `name` VARCHAR(191) NOT NULL COMMENT '文件夹名称',
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '层级深度，业务上限 20',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '同级排序，用于拖拽排序',
  `direct_copy_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前文件夹直系文案条数（可异步校准）',
  `total_copy_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '含子文件夹文案总数（可异步校准）',
  `created_by` CHAR(26) NULL COMMENT '创建人',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_copy_folders_library_parent_idx` (`library_id`, `parent_id`),
  CONSTRAINT `fk_meta_copy_folders_library_id`
    FOREIGN KEY (`library_id`) REFERENCES `meta_copy_libraries` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meta_copy_folders_parent_id`
    FOREIGN KEY (`parent_id`) REFERENCES `meta_copy_folders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文案库：多级文件夹（最多 20 级，由应用层校验）';

CREATE TABLE IF NOT EXISTS `meta_copy_items` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `library_id` CHAR(26) NOT NULL COMMENT '所属文案库 ID',
  `folder_id` CHAR(26) NOT NULL COMMENT '所属文件夹 ID',
  `primary_text` TEXT NULL COMMENT '正文/主要文案（对齐 Meta primary_text）',
  `headline` VARCHAR(512) NULL COMMENT '标题（对齐 Meta headline）',
  `description` TEXT NULL COMMENT '描述/链接说明（对齐 Meta description）',
  `remark` TEXT NULL COMMENT '备注（XMP 自定义备注）',
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状态：draft=草稿，active=启用',
  `created_by` CHAR(26) NULL COMMENT '创建人',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_copy_items_library_folder_idx` (`library_id`, `folder_id`),
  CONSTRAINT `fk_meta_copy_items_library_id`
    FOREIGN KEY (`library_id`) REFERENCES `meta_copy_libraries` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meta_copy_items_folder_id`
    FOREIGN KEY (`folder_id`) REFERENCES `meta_copy_folders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文案库：单条文案（三文案位 + 备注）';

CREATE TABLE IF NOT EXISTS `meta_copy_library_permissions` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `library_id` CHAR(26) NOT NULL COMMENT '文案库 ID',
  `subject_type` VARCHAR(32) NOT NULL COMMENT '授权主体类型：user=用户，role=角色（Spatie 角色名等）',
  `subject_id` VARCHAR(64) NOT NULL COMMENT '主体 ID：用户 ULID 或角色标识',
  `can_manage` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否可管理库设置/可见范围',
  `can_write` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否可新增、编辑文案与文件夹',
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否可删除文案/文件夹',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_copy_lib_perm_unique` (`library_id`, `subject_type`, `subject_id`),
  CONSTRAINT `fk_meta_copy_library_permissions_library_id`
    FOREIGN KEY (`library_id`) REFERENCES `meta_copy_libraries` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文案库：企业库成员/角色授权（后端权限兜底）';

CREATE TABLE IF NOT EXISTS `meta_copy_performance_daily` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `copy_item_id` CHAR(26) NOT NULL COMMENT '关联 meta_copy_items.id',
  `stat_date` DATE NOT NULL COMMENT '统计日期',
  `channel` VARCHAR(64) NOT NULL DEFAULT 'meta' COMMENT '渠道标识，如 meta；默认 meta 保证日维度唯一索引',
  `impressions` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '展示次数',
  `clicks` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击次数',
  `spend` DECIMAL(14,4) NOT NULL DEFAULT 0.0000 COMMENT '花费',
  `conversions` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '转化次数',
  `revenue` DECIMAL(14,4) NOT NULL DEFAULT 0.0000 COMMENT '收入/转化价值（口径与报表对齐）',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_copy_perf_item_date_ch` (`copy_item_id`, `stat_date`, `channel`),
  CONSTRAINT `fk_meta_copy_performance_daily_copy_item_id`
    FOREIGN KEY (`copy_item_id`) REFERENCES `meta_copy_items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文案库：文案维度投放日快照（列表展示投放数据）';

/* =====================================================================
 * 来源文件：tool_report_dashboard.sql
 * 模块：报表看板
 * ===================================================================== */

CREATE TABLE IF NOT EXISTS `meta_report_dashboard_folders` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `owner_user_id` CHAR(26) NOT NULL COMMENT '所属用户 ID（与 users.id 同类型时可直接存 ULID）',
  `parent_id` CHAR(26) NULL COMMENT '父文件夹 ID，根节点为 NULL',
  `name` VARCHAR(200) NOT NULL COMMENT '文件夹名称',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '同级排序',
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状态：active/archived',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_report_folder_owner_parent_idx` (`owner_user_id`, `parent_id`),
  CONSTRAINT `fk_meta_report_dashboard_folders_parent_id`
    FOREIGN KEY (`parent_id`) REFERENCES `meta_report_dashboard_folders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 报表看板：左侧文件夹树（支持多级目录）';

CREATE TABLE IF NOT EXISTS `meta_report_dashboards` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `name` VARCHAR(200) NOT NULL COMMENT '看板名称',
  `folder_id` CHAR(26) NULL COMMENT '所属文件夹 ID',
  `location` VARCHAR(32) NOT NULL DEFAULT 'mine' COMMENT '看板位置：mine=我的看板，shared=分享给我的，system=系统看板',
  `channel` VARCHAR(32) NOT NULL DEFAULT 'summary' COMMENT '渠道：summary/meta/google/tiktok 等',
  `board_type` VARCHAR(32) NOT NULL COMMENT '看板类型：comprehensive/material/tag/custom/landing',
  `group_compare` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '分组对比开关',
  `default_filters` JSON NULL COMMENT '默认筛选配置（JSON）',
  `last_saved_at` TIMESTAMP NULL DEFAULT NULL COMMENT '上次保存时间',
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状态：active/archived',
  `owner_user_id` CHAR(26) NOT NULL COMMENT '所属用户 ID',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_report_dashboards_owner_folder_idx` (`owner_user_id`, `folder_id`),
  KEY `meta_report_dashboards_owner_type_idx` (`owner_user_id`, `board_type`),
  KEY `meta_report_dashboards_owner_status_idx` (`owner_user_id`, `status`),
  CONSTRAINT `fk_meta_report_dashboards_folder_id`
    FOREIGN KEY (`folder_id`) REFERENCES `meta_report_dashboard_folders` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 报表看板：看板主表（名称、类型、默认筛选、保存时间）';

CREATE TABLE IF NOT EXISTS `meta_report_dashboard_cards` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `dashboard_id` CHAR(26) NOT NULL COMMENT '所属看板 ID',
  `title` VARCHAR(200) NOT NULL COMMENT '卡片标题',
  `chart_type` VARCHAR(64) NOT NULL COMMENT '图表类型：table/line/area/stack-area 等',
  `shape` VARCHAR(32) NOT NULL DEFAULT 'medium' COMMENT '卡片形状：large/medium/small',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序序号',
  `query_config` JSON NULL COMMENT '查询配置（维度、指标、筛选）',
  `style_config` JSON NULL COMMENT '样式配置（颜色、主题、展示方式）',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_report_cards_dashboard_sort_idx` (`dashboard_id`, `sort_order`),
  CONSTRAINT `fk_meta_report_dashboard_cards_dashboard_id`
    FOREIGN KEY (`dashboard_id`) REFERENCES `meta_report_dashboards` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 报表看板：看板卡片配置（图表类型、布局、查询参数）';

CREATE TABLE IF NOT EXISTS `meta_report_dashboard_shares` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `dashboard_id` CHAR(26) NOT NULL COMMENT '看板 ID',
  `subject_type` VARCHAR(32) NOT NULL COMMENT '共享主体类型：user=用户，role=角色',
  `subject_id` VARCHAR(64) NOT NULL COMMENT '共享主体 ID',
  `can_edit` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否可编辑',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_report_dashboard_shares_unique` (`dashboard_id`, `subject_type`, `subject_id`),
  CONSTRAINT `fk_meta_report_dashboard_shares_dashboard_id`
    FOREIGN KEY (`dashboard_id`) REFERENCES `meta_report_dashboards` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 报表看板：看板分享/协作权限';

/* =====================================================================
 * 来源文件：tool_facebook_homepage.sql
 * 模块：工具-Facebook主页
 * ===================================================================== */

CREATE TABLE IF NOT EXISTS `meta_fb_pages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `public_page_id` VARCHAR(64) NOT NULL COMMENT 'Facebook公共主页ID（外部ID）',
  `public_page_name` VARCHAR(255) NOT NULL COMMENT '公共主页名称',
  `authorization_status` VARCHAR(32) NOT NULL DEFAULT 'UNAUTHORIZED' COMMENT '授权状态（AUTHORIZED/UNAUTHORIZED）',
  `authorization_time` DATETIME NULL COMMENT '授权时间',
  `rating` DECIMAL(5,2) NULL COMMENT '评分（可选）',
  `auto_rule_status` VARCHAR(32) NULL COMMENT '自动规则状态缓存（可选）',
  `auto_hide_comments` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '自动隐藏评论开关缓存（0关1开，可选）',
  `last_sync_at` DATETIME NULL COMMENT '最近一次同步时间',
  `created_by` BIGINT UNSIGNED NULL COMMENT '创建人ID（系统内用户）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_pages_public_page_id` (`public_page_id`),
  KEY `idx_meta_fb_pages_authorization_status` (`authorization_status`),
  KEY `idx_meta_fb_pages_last_sync_at` (`last_sync_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook主页表';

CREATE TABLE IF NOT EXISTS `meta_fb_keyword_packs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pack_name` VARCHAR(128) NOT NULL COMMENT '关键词包名称',
  `status` VARCHAR(32) NOT NULL DEFAULT 'ENABLED' COMMENT '状态（ENABLED/DISABLED）',
  `created_by` BIGINT UNSIGNED NULL COMMENT '创建人ID',
  `created_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '业务创建时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_keyword_packs_pack_name` (`pack_name`),
  KEY `idx_meta_fb_keyword_packs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook关键词包表';

CREATE TABLE IF NOT EXISTS `meta_fb_keywords` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pack_id` BIGINT UNSIGNED NOT NULL COMMENT '关键词包ID',
  `keyword` VARCHAR(255) NOT NULL COMMENT '关键词',
  `match_type` VARCHAR(32) NOT NULL DEFAULT 'CONTAINS' COMMENT '匹配类型（EXACT/CONTAINS/REGEX）',
  `priority` INT NOT NULL DEFAULT 100 COMMENT '优先级（数值越小优先级越高）',
  `reply_template` TEXT NULL COMMENT '回复模板内容',
  `enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用（0否1是）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_keywords_pack_keyword` (`pack_id`, `keyword`),
  KEY `idx_meta_fb_keywords_pack_enabled` (`pack_id`, `enabled`),
  CONSTRAINT `fk_meta_fb_keywords_pack_id`
    FOREIGN KEY (`pack_id`) REFERENCES `meta_fb_keyword_packs` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook关键词表';

CREATE TABLE IF NOT EXISTS `meta_fb_page_auto_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `page_id` BIGINT UNSIGNED NOT NULL COMMENT '主页ID',
  `status` VARCHAR(32) NOT NULL DEFAULT 'DISABLED' COMMENT '规则状态（ENABLED/DISABLED）',
  `action` VARCHAR(32) NOT NULL DEFAULT 'HIDE_ALL' COMMENT '规则动作（HIDE_ALL/HIDE_KEYWORDS）',
  `keyword_pack_id` BIGINT UNSIGNED NULL COMMENT '关键词包ID（action=HIDE_KEYWORDS时必填）',
  `updated_by` BIGINT UNSIGNED NULL COMMENT '最后更新人ID',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_page_auto_rules_page_id` (`page_id`),
  KEY `idx_meta_fb_page_auto_rules_status_action` (`status`, `action`),
  KEY `idx_meta_fb_page_auto_rules_keyword_pack_id` (`keyword_pack_id`),
  CONSTRAINT `fk_meta_fb_page_auto_rules_page_id`
    FOREIGN KEY (`page_id`) REFERENCES `meta_fb_pages` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meta_fb_page_auto_rules_keyword_pack_id`
    FOREIGN KEY (`keyword_pack_id`) REFERENCES `meta_fb_keyword_packs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook主页自动规则配置表';

CREATE TABLE IF NOT EXISTS `meta_fb_page_bindings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `page_id` BIGINT UNSIGNED NOT NULL COMMENT '主页ID',
  `source_type` VARCHAR(32) NOT NULL COMMENT '来源类型（fb_personal_account/business/token/asset）',
  `source_id` VARCHAR(128) NOT NULL COMMENT '来源对象ID',
  `source_name` VARCHAR(255) NULL COMMENT '来源对象名称（如FB个人号昵称）',
  `status` VARCHAR(32) NOT NULL DEFAULT 'ACTIVE' COMMENT '绑定状态（ACTIVE/REVOKED/EXPIRED）',
  `authorized_at` DATETIME NULL COMMENT '授权生效时间',
  `expired_at` DATETIME NULL COMMENT '授权过期时间',
  `last_sync_at` DATETIME NULL COMMENT '最近同步时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_page_bindings_page_source` (`page_id`, `source_type`, `source_id`),
  KEY `idx_meta_fb_page_bindings_source` (`source_type`, `source_id`),
  KEY `idx_meta_fb_page_bindings_status` (`status`),
  CONSTRAINT `fk_meta_fb_page_bindings_page_id`
    FOREIGN KEY (`page_id`) REFERENCES `meta_fb_pages` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook主页绑定来源表';

CREATE TABLE IF NOT EXISTS `meta_fb_post_comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `page_id` BIGINT UNSIGNED NOT NULL COMMENT '主页ID',
  `post_id` VARCHAR(128) NOT NULL COMMENT '帖子ID',
  `comment_id` VARCHAR(128) NOT NULL COMMENT '评论外部ID',
  `comment_content` TEXT NOT NULL COMMENT '评论内容',
  `status` VARCHAR(32) NOT NULL DEFAULT 'VISIBLE' COMMENT '评论状态（VISIBLE/HIDDEN/DELETED）',
  `likes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
  `replies` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复数',
  `created_time` DATETIME NULL COMMENT '评论创建时间（来源时间）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '入库创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_post_comments_comment_id` (`comment_id`),
  KEY `idx_meta_fb_post_comments_page_post` (`page_id`, `post_id`),
  KEY `idx_meta_fb_post_comments_status_created_time` (`status`, `created_time`),
  CONSTRAINT `fk_meta_fb_post_comments_page_id`
    FOREIGN KEY (`page_id`) REFERENCES `meta_fb_pages` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook评论表';

CREATE TABLE IF NOT EXISTS `meta_fb_comment_actions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `comment_row_id` BIGINT UNSIGNED NOT NULL COMMENT '评论行ID（关联评论主表）',
  `action_type` VARCHAR(32) NOT NULL COMMENT '操作类型（HIDE/DELETE/REPLY）',
  `action_payload` JSON NULL COMMENT '操作参数（如回复内容、原因）',
  `operator_id` BIGINT UNSIGNED NULL COMMENT '操作人ID',
  `result_status` VARCHAR(32) NOT NULL DEFAULT 'SUCCESS' COMMENT '执行结果（SUCCESS/FAIL）',
  `result_message` VARCHAR(1000) NULL COMMENT '执行结果信息',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_fb_comment_actions_comment_row_id` (`comment_row_id`),
  KEY `idx_meta_fb_comment_actions_action_type` (`action_type`),
  KEY `idx_meta_fb_comment_actions_result_status` (`result_status`),
  CONSTRAINT `fk_meta_fb_comment_actions_comment_row_id`
    FOREIGN KEY (`comment_row_id`) REFERENCES `meta_fb_post_comments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook评论操作记录表';

/* =====================================================================
 * 来源文件：tool_task_center.sql
 * 模块：工具-任务中心
 * ===================================================================== */

CREATE TABLE IF NOT EXISTS `meta_task_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务ID（任务中心列表的任务ID）',
  `type` VARCHAR(32) NOT NULL COMMENT '任务类型（create_ad/batch_edit/material_push/ai_record）',
  `status` VARCHAR(32) NOT NULL DEFAULT 'processing' COMMENT '任务状态（processing/completed/failed）',
  `result` VARCHAR(32) NULL COMMENT '任务结果（success/fail/partial）',
  `submit_method` VARCHAR(32) NOT NULL DEFAULT 'manual' COMMENT '提交方式（manual/auto）',
  `submit_time` DATETIME NOT NULL COMMENT '提交时间（建议按业务时区入库后展示）',
  `end_time` DATETIME NULL COMMENT '任务结束时间',
  `creator_id` BIGINT UNSIGNED NULL COMMENT '创建人ID（系统内用户）',
  `channel` VARCHAR(32) NULL COMMENT '通道/平台（如facebook/google/tiktok等）',
  `account` VARCHAR(128) NULL COMMENT '账户标识（按页面筛选口径落地）',
  `asset` VARCHAR(128) NULL COMMENT '资产标识（按页面筛选口径落地）',
  `batch_id` VARCHAR(128) NULL COMMENT 'AI运行批次ID（当 type=ai_record 时）',
  `source_job_id` BIGINT UNSIGNED NULL COMMENT '复制来源任务ID（用于“复制已有任务”还原创建草稿）',
  `payload` JSON NULL COMMENT '任务参数快照（原始参数/审计/重放）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_task_jobs_type` (`type`),
  KEY `idx_meta_task_jobs_status` (`status`),
  KEY `idx_meta_task_jobs_submit_time` (`submit_time`),
  KEY `idx_meta_task_jobs_creator_id` (`creator_id`),
  KEY `idx_meta_task_jobs_source_job_id` (`source_job_id`),
  CONSTRAINT `fk_meta_task_jobs_source_job_id`
    FOREIGN KEY (`source_job_id`) REFERENCES `meta_task_jobs` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-任务中心-任务主表';

CREATE TABLE IF NOT EXISTS `meta_task_job_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务明细ID（任务对象执行结果）',
  `job_id` BIGINT UNSIGNED NOT NULL COMMENT '任务主表ID（meta_task_jobs.id）',
  `target_type` VARCHAR(64) NOT NULL COMMENT '执行目标类型（account/material/ad_asset/ad_plan/ad_group/rule等）',
  `target_id` VARCHAR(128) NOT NULL COMMENT '执行目标ID（外部ID或内部ID）',
  `target_name` VARCHAR(255) NULL COMMENT '执行目标名称（用于详情/日志展示）',
  `status` VARCHAR(32) NOT NULL DEFAULT 'processing' COMMENT '执行状态（processing/success/fail）',
  `result` VARCHAR(32) NULL COMMENT '执行结果（success/fail/partial）',
  `message` VARCHAR(2000) NULL COMMENT '执行信息/失败原因（摘要）',
  `started_at` DATETIME NULL COMMENT '开始时间',
  `finished_at` DATETIME NULL COMMENT '结束时间',
  `retry_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
  `original_item_id` BIGINT UNSIGNED NULL COMMENT '重试关联的原任务明细ID（用于追溯失败对象并重跑）',
  `payload` JSON NULL COMMENT '明细参数/回执快照',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_task_job_items_job_id` (`job_id`),
  KEY `idx_meta_task_job_items_target_type` (`target_type`),
  KEY `idx_meta_task_job_items_status` (`status`),
  KEY `idx_meta_task_job_items_original_item_id` (`original_item_id`),
  KEY `idx_meta_task_job_items_target_id` (`target_id`),
  CONSTRAINT `fk_meta_task_job_items_job_id`
    FOREIGN KEY (`job_id`) REFERENCES `meta_task_jobs` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meta_task_job_items_original_item_id`
    FOREIGN KEY (`original_item_id`) REFERENCES `meta_task_job_items` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-任务中心-任务明细表';

CREATE TABLE IF NOT EXISTS `meta_task_operation_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '操作日志ID',
  `job_id` BIGINT UNSIGNED NOT NULL COMMENT '任务主表ID（meta_task_jobs.id）',
  `job_item_id` BIGINT UNSIGNED NULL COMMENT '关联的任务明细ID（meta_task_job_items.id，可选）',
  `action_type` VARCHAR(64) NOT NULL COMMENT '操作类型（CREATE/EDIT/COPY/AI_EXECUTE/RETRY等）',
  `operator_id` BIGINT UNSIGNED NULL COMMENT '执行人ID（系统内用户）',
  `action_payload` JSON NULL COMMENT '操作参数（复制来源、AI助手输入输出、重试参数等）',
  `result_status` VARCHAR(32) NOT NULL DEFAULT 'SUCCESS' COMMENT '执行结果（SUCCESS/FAIL）',
  `result_message` VARCHAR(2000) NULL COMMENT '执行结果信息/失败原因',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_task_operation_logs_job_id` (`job_id`),
  KEY `idx_meta_task_operation_logs_job_item_id` (`job_item_id`),
  KEY `idx_meta_task_operation_logs_action_type` (`action_type`),
  KEY `idx_meta_task_operation_logs_result_status` (`result_status`),
  CONSTRAINT `fk_meta_task_operation_logs_job_id`
    FOREIGN KEY (`job_id`) REFERENCES `meta_task_jobs` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meta_task_operation_logs_job_item_id`
    FOREIGN KEY (`job_item_id`) REFERENCES `meta_task_job_items` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-任务中心-任务操作日志表';

/* =====================================================================
 * 来源文件：tool_notifications.sql
 * 模块：工具-通知中心
 * ===================================================================== */

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `meta_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通知ID',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '接收用户ID（系统内用户）',
  `type` VARCHAR(64) NOT NULL COMMENT '通知类型（system/task/ai/marketing等）',
  `title` VARCHAR(255) NOT NULL COMMENT '通知标题',
  `content` TEXT NOT NULL COMMENT '通知内容（已渲染文案，支持多语言）',
  `extra` JSON NULL COMMENT '扩展字段（跳转链接、业务ID等）',
  `status` VARCHAR(32) NOT NULL DEFAULT 'unread' COMMENT '通知状态（unread/read/deleted）',
  `read_at` DATETIME NULL COMMENT '阅读时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_notifications_user_id` (`user_id`),
  KEY `idx_meta_notifications_status` (`status`),
  KEY `idx_meta_notifications_type` (`type`),
  KEY `idx_meta_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-通知中心-用户通知表';

/* =====================================================================
 * 来源文件：tool_scheduled_reports.sql
 * 模块：工具-定时报表
 * ===================================================================== */

CREATE TABLE IF NOT EXISTS `meta_scheduled_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '定时报表ID',
  `company_id` BIGINT UNSIGNED NOT NULL COMMENT '公司主体ID（用于限额与权限）',
  `name` VARCHAR(128) NOT NULL COMMENT '定时报表名称',
  `description` VARCHAR(255) NULL COMMENT '定时报表描述',
  `status` VARCHAR(32) NOT NULL DEFAULT 'enabled' COMMENT '状态（enabled/disabled）',
  `schedule_type` VARCHAR(32) NOT NULL COMMENT '调度类型（daily/weekly/custom）',
  `cron_expression` VARCHAR(64) NULL COMMENT '自定义cron表达式（当schedule_type=custom时生效）',
  `run_time_of_day` TIME NULL COMMENT '每日/每周执行时间（本地业务时区）',
  `run_weekday` TINYINT UNSIGNED NULL COMMENT '每周执行星期（1-7，周一=1）',
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Shanghai' COMMENT '时区标识',
  `max_card_count` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '卡片数量上限（默认5）',
  `email_to` VARCHAR(500) NOT NULL COMMENT '收件人邮箱列表（逗号分隔）',
  `email_cc` VARCHAR(500) NULL COMMENT '抄送邮箱列表（逗号分隔）',
  `last_run_time` DATETIME NULL COMMENT '上次执行时间',
  `next_run_time` DATETIME NULL COMMENT '下次计划执行时间',
  `last_run_status` VARCHAR(32) NULL COMMENT '上次执行状态（success/fail/running）',
  `creator_id` BIGINT UNSIGNED NOT NULL COMMENT '创建人用户ID',
  `department_id` BIGINT UNSIGNED NULL COMMENT '部门ID（用于权限控制）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_scheduled_reports_company` (`company_id`),
  KEY `idx_meta_scheduled_reports_creator` (`creator_id`),
  KEY `idx_meta_scheduled_reports_status` (`status`),
  KEY `idx_meta_scheduled_reports_next_run_time` (`next_run_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-定时报表-主表';

CREATE TABLE IF NOT EXISTS `meta_scheduled_report_cards` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '定时报表卡片ID',
  `report_id` BIGINT UNSIGNED NOT NULL COMMENT '所属定时报表ID（meta_scheduled_reports.id）',
  `title` VARCHAR(128) NOT NULL COMMENT '卡片标题（邮件中展示标题）',
  `report_type` VARCHAR(32) NOT NULL COMMENT '报表类型（summary/material）',
  `channel` VARCHAR(32) NOT NULL COMMENT '渠道（summary/facebook/google/tiktok）',
  `data_time_mode` VARCHAR(32) NOT NULL DEFAULT 'single' COMMENT '数据时间模式（single/compare）',
  `date_range_start` DATE NULL COMMENT '单个时间范围-开始日期',
  `date_range_end` DATE NULL COMMENT '单个时间范围-结束日期',
  `compare_date_range_start` DATE NULL COMMENT '对比时间范围-开始日期',
  `compare_date_range_end` DATE NULL COMMENT '对比时间范围-结束日期',
  `filters` JSON NULL COMMENT '筛选条件（维度筛选/按指标数值筛选等）',
  `sort_field` VARCHAR(64) NULL COMMENT '排序字段',
  `sort_direction` VARCHAR(8) NULL COMMENT '排序方向（asc/desc）',
  `limit_rows` INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '数据节选条数（10/20/50）',
  `position` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '卡片在邮件中的排序位置（从1开始）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_scheduled_report_cards_report_id` (`report_id`),
  CONSTRAINT `fk_meta_scheduled_report_cards_report_id`
    FOREIGN KEY (`report_id`) REFERENCES `meta_scheduled_reports` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-定时报表-数据卡片表';

CREATE TABLE IF NOT EXISTS `meta_scheduled_report_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '运行记录ID',
  `report_id` BIGINT UNSIGNED NOT NULL COMMENT '定时报表ID（meta_scheduled_reports.id）',
  `trigger_type` VARCHAR(32) NOT NULL COMMENT '触发类型（schedule/manual）',
  `status` VARCHAR(32) NOT NULL COMMENT '运行状态（queued/running/success/fail）',
  `message` VARCHAR(2000) NULL COMMENT '运行结果描述/失败原因',
  `email_to` VARCHAR(500) NOT NULL COMMENT '本次实际发送的收件人邮箱',
  `email_cc` VARCHAR(500) NULL COMMENT '本次实际发送的抄送邮箱',
  `attachment_url` VARCHAR(500) NULL COMMENT 'xlsx 报表下载地址',
  `started_at` DATETIME NULL COMMENT '开始时间',
  `finished_at` DATETIME NULL COMMENT '结束时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_scheduled_report_runs_report_id` (`report_id`),
  KEY `idx_meta_scheduled_report_runs_status` (`status`),
  KEY `idx_meta_scheduled_report_runs_started_at` (`started_at`),
  CONSTRAINT `fk_meta_scheduled_report_runs_report_id`
    FOREIGN KEY (`report_id`) REFERENCES `meta_scheduled_reports` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-定时报表-运行记录表';
/* =====================================================================
 * 2026-04-08 链接模块功能更新
 * 说明: 增加链接多语言字段、导入能力，并修复所有者判定
 * 涉及文件: database/migrations/2026_04_08_160000_add_language_fields_to_links_table.php
 *          backend/app/Http/Resources/LinkResource.php
 *          backend/app/Http/Controllers/LinkController.php
 * ===================================================================== */

/* =====================================================================
 * 2026-04-08 links 表结构更新
 * 迁移文件: database/migrations/2026_04_08_160000_add_language_fields_to_links_table.php
 * 数据表: links
 * 新增字段: default_locale VARCHAR(32) NULL,
 *          language_variants JSON NULL,
 *          import_source VARCHAR(32) NULL
 * ===================================================================== */

ALTER TABLE `links`
  ADD COLUMN `default_locale` VARCHAR(32) NULL COMMENT '默认语言代码' AFTER `notes`,
  ADD COLUMN `language_variants` JSON NULL COMMENT '多语言 URL 变体配置' AFTER `default_locale`,
  ADD COLUMN `import_source` VARCHAR(32) NULL COMMENT '手动或导入来源标记' AFTER `language_variants`;
/* =====================================================================
 * 2026-04-08 links 独立标签表
 * 迁移文件: database/migrations/2026_04_08_170000_create_link_tags_table.php
 * 数据表: link_tags
 * 用途说明: links 模块不再复用通用 tags/taggables，改为使用独立 link_tags 表存储链接标签
 * ===================================================================== */

CREATE TABLE `link_tags` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `link_id` CHAR(26) NOT NULL COMMENT '关联 links.id',
  `user_id` VARCHAR(26) NOT NULL COMMENT '所属用户 ID',
  `name` VARCHAR(191) NOT NULL COMMENT '标签名称',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_link_tags_link_user` (`link_id`, `user_id`),
  KEY `idx_link_tags_user_name` (`user_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='链接模块独立标签表';
/* =====================================================================
 * 2026-04-08 link_tags 表注释补充
 * 迁移文件: database/migrations/2026_04_08_180000_add_comments_to_link_tags_table.php
 * 修改说明: 为 link_tags 表及其字段补充中文注释，并将存储引擎调整为 InnoDB
 * ===================================================================== */