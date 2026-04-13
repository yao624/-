-- lzc 全量建表汇（来自 backend/sql/lzc 下各模块 SQL?-- 生成时间?026-04-01
-- 说明：仅汇?DDL，不包含测试数据?
/* =====================================================================
 * 来源文件：tool_copy_library.sql
 * 模块：文案库
 * ===================================================================== */

-- 文库：建表（仅 DDL?-- 对应迁移: database/migrations/2026_03_25_100000_create_meta_copy_library_tables.php
-- 说明: 不 users 建锼避免与库?users.id 类型/排序规则不一致?3780；由应用层保?owner_user_id、created_by 有效?
CREATE TABLE IF NOT EXISTS `meta_copy_libraries` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `name` VARCHAR(191) NOT NULL COMMENT '文库名?,
  `type` VARCHAR(32) NOT NULL COMMENT '库类型：personal=我的文库，enterprise=企业文?,
  `owner_user_id` CHAR(26) NOT NULL COMMENT '创建人用?ID',
  `visibility_scope` JSON NULL COMMENT '企业库共二围等扩展配置（JSON?,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状：active=吔，disabled=停用',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_meta_copy_libraries_owner_user_id` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文库：根库（个?企业），对应 XMP 我的/企业文?;

CREATE TABLE IF NOT EXISTS `meta_copy_folders` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `library_id` CHAR(26) NOT NULL COMMENT '属文案库 ID',
  `parent_id` CHAR(26) NULL COMMENT '父文件夹 ID，根盽?NULL',
  `name` VARCHAR(191) NOT NULL COMMENT '文件夹名?,
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '层级深度，业务上?20',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '同级排序，用于拖拽排?,
  `direct_copy_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前文件夹直系文案条数（叼步校准）',
  `total_copy_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '吭文件夹文案数（可异校准?,
  `created_by` CHAR(26) NULL COMMENT '创建?,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文库：多级文件夹（?20 级，由应用层校验?;

CREATE TABLE IF NOT EXISTS `meta_copy_items` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `library_id` CHAR(26) NOT NULL COMMENT '属文案库 ID',
  `folder_id` CHAR(26) NOT NULL COMMENT '属文件夹 ID',
  `primary_text` TEXT NULL COMMENT '正文/主文（?Meta primary_text?,
  `headline` VARCHAR(512) NULL COMMENT '标（?Meta headline?,
  `description` TEXT NULL COMMENT '描述/链接说明（?Meta description?,
  `remark` TEXT NULL COMMENT '备注（XMP 臮义泼',
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状：draft=草，active=吔',
  `created_by` CHAR(26) NULL COMMENT '创建?,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文库：单条文（三文?+ 备注?;

CREATE TABLE IF NOT EXISTS `meta_copy_library_permissions` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `library_id` CHAR(26) NOT NULL COMMENT '文?ID',
  `subject_type` VARCHAR(32) NOT NULL COMMENT '授权主体类型：user=用户，role=角色（Spatie 角色名等?,
  `subject_id` VARCHAR(64) NOT NULL COMMENT '主体 ID：用?ULID 或色标?,
  `can_manage` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '昐理库设置/范围',
  `can_write` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '昐取增编辑文案与文件?,
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '昐又除文?文件?,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_copy_lib_perm_unique` (`library_id`, `subject_type`, `subject_id`),
  CONSTRAINT `fk_meta_copy_library_permissions_library_id`
    FOREIGN KEY (`library_id`) REFERENCES `meta_copy_libraries` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文库：企业库成?角色授权（后竝限兜底）';

CREATE TABLE IF NOT EXISTS `meta_copy_performance_daily` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `copy_item_id` CHAR(26) NOT NULL COMMENT '关联 meta_copy_items.id',
  `stat_date` DATE NOT NULL COMMENT '统日期',
  `channel` VARCHAR(64) NOT NULL DEFAULT 'meta' COMMENT '渠道标识， meta；默?meta 保证日维度唯索引',
  `impressions` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '展示次数',
  `clicks` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击次数',
  `spend` DECIMAL(14,4) NOT NULL DEFAULT 0.0000 COMMENT '花费',
  `conversions` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '轌次数',
  `revenue` DECIMAL(14,4) NOT NULL DEFAULT 0.0000 COMMENT '收入/轌价（口径与报表齐）',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_copy_perf_item_date_ch` (`copy_item_id`, `stat_date`, `channel`),
  CONSTRAINT `fk_meta_copy_performance_daily_copy_item_id`
    FOREIGN KEY (`copy_item_id`) REFERENCES `meta_copy_items` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 文库：文维度投放日快照（列表展示投放数据?;

/* =====================================================================
 * 来源文件：tool_report_dashboard.sql
 * 模块：报表看? * ===================================================================== */

CREATE TABLE IF NOT EXISTS `meta_report_dashboard_folders` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `owner_user_id` CHAR(26) NOT NULL COMMENT '属用?ID（与 users.id 同类型时叛接存 ULID?,
  `parent_id` CHAR(26) NULL COMMENT '父文件夹 ID，根节点?NULL',
  `name` VARCHAR(200) NOT NULL COMMENT '文件夹名?,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '同级排序',
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状：active/archived',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_report_folder_owner_parent_idx` (`owner_user_id`, `parent_id`),
  CONSTRAINT `fk_meta_report_dashboard_folders_parent_id`
    FOREIGN KEY (`parent_id`) REFERENCES `meta_report_dashboard_folders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 报表看板：左侧文件夹树（攌多级盽?;

CREATE TABLE IF NOT EXISTS `meta_report_dashboards` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `name` VARCHAR(200) NOT NULL COMMENT '看板名称',
  `folder_id` CHAR(26) NULL COMMENT '属文件夹 ID',
  `location` VARCHAR(32) NOT NULL DEFAULT 'mine' COMMENT '看板位置：mine=我的看板，shared=分享给我的，system=系统看板',
  `channel` VARCHAR(32) NOT NULL DEFAULT 'summary' COMMENT '渠道：summary/meta/google/tiktok ?,
  `board_type` VARCHAR(32) NOT NULL COMMENT '看板类型：comprehensive/material/tag/custom/landing',
  `group_compare` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '分组对比?,
  `default_filters` JSON NULL COMMENT '默筛配罼JSON?,
  `last_saved_at` TIMESTAMP NULL DEFAULT NULL COMMENT '上保存时间',
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状：active/archived',
  `owner_user_id` CHAR(26) NOT NULL COMMENT '属用?ID',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 报表看板：看板主衼名称、类型默认筛选保存时间）';

CREATE TABLE IF NOT EXISTS `meta_report_dashboard_cards` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `dashboard_id` CHAR(26) NOT NULL COMMENT '属看?ID',
  `title` VARCHAR(200) NOT NULL COMMENT '卡片标',
  `chart_type` VARCHAR(64) NOT NULL COMMENT '图表类型：table/line/area/stack-area ?,
  `shape` VARCHAR(32) NOT NULL DEFAULT 'medium' COMMENT '卡片形状：large/medium/small',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序序号',
  `query_config` JSON NULL COMMENT '查配置（维度指标筛选）',
  `style_config` JSON NULL COMMENT '样式配置（色主题展示方式）',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_report_cards_dashboard_sort_idx` (`dashboard_id`, `sort_order`),
  CONSTRAINT `fk_meta_report_dashboard_cards_dashboard_id`
    FOREIGN KEY (`dashboard_id`) REFERENCES `meta_report_dashboards` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 报表看板：看板卡片配罼图表类型、布、查询参数）';

CREATE TABLE IF NOT EXISTS `meta_report_dashboard_shares` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `dashboard_id` CHAR(26) NOT NULL COMMENT '看板 ID',
  `subject_type` VARCHAR(32) NOT NULL COMMENT '共享主体类型：user=用户，role=角色',
  `subject_id` VARCHAR(64) NOT NULL COMMENT '共享主体 ID',
  `can_edit` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '昐叼?,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_report_dashboard_shares_unique` (`dashboard_id`, `subject_type`, `subject_id`),
  CONSTRAINT `fk_meta_report_dashboard_shares_dashboard_id`
    FOREIGN KEY (`dashboard_id`) REFERENCES `meta_report_dashboards` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta 报表看板：看板分?协作权限';

CREATE TABLE IF NOT EXISTS `meta_report_metric_dicts` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `metric_key` VARCHAR(64) NOT NULL COMMENT '指标编码，供看板/报表/权限统一引用',
  `metric_name` VARCHAR(100) NOT NULL COMMENT '指标世名称',
  `metric_name_en` VARCHAR(100) NULL COMMENT '指标英文名称',
  `unit` VARCHAR(32) NULL COMMENT '指标单位， currency/count/percent',
  `data_type` VARCHAR(32) NOT NULL DEFAULT 'number' COMMENT '数据类型：number/integer/decimal/percent/currency',
  `aggregation_type` VARCHAR(32) NOT NULL DEFAULT 'sum' COMMENT '聚合方式：sum/avg/count/rate/custom',
  `supported_levels` JSON NULL COMMENT '适用层级， account/campaign/adset/ad',
  `supported_chart_types` JSON NULL COMMENT '适用图表类型',
  `is_filterable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '昐攌作为指标筛条?,
  `is_sortable` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '昐攌排序',
  `is_permission_controlled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '昐受看板指标权限控?,
  `permission_slug` VARCHAR(128) NULL COMMENT '对应权限节点 slug',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序?,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状：active/inactive',
  `description` TEXT NULL COMMENT '指标说明/口径说明',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_report_metric_dicts_metric_key_unique` (`metric_key`),
  KEY `meta_report_metric_dicts_status_sort_idx` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta ֵָ';

CREATE TABLE IF NOT EXISTS `meta_report_dimension_dicts` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `dimension_key` VARCHAR(64) NOT NULL COMMENT '维度编码，供看板/报表/筛统引用',
  `dimension_name` VARCHAR(100) NOT NULL COMMENT '维度世名称',
  `dimension_name_en` VARCHAR(100) NULL COMMENT '维度英文名称',
  `value_type` VARCHAR(32) NOT NULL DEFAULT 'string' COMMENT '值类型：string/date/datetime/number/enum',
  `supported_levels` JSON NULL COMMENT '适用层级， account/campaign/adset/ad',
  `is_groupable` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '昐攌分组展示',
  `is_filterable` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '昐攌筛?,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '昐默常用维度',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序?,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active' COMMENT '状：active/inactive',
  `description` TEXT NULL COMMENT '维度说明',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_report_dimension_dicts_dimension_key_unique` (`dimension_key`),
  KEY `meta_report_dimension_dicts_status_sort_idx` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta άֵ';

/* =====================================================================
 * 来源文件：tool_facebook_homepage.sql
 * 模块：工?Facebook主页
 * ===================================================================== */

CREATE TABLE IF NOT EXISTS `meta_fb_pages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `public_page_id` VARCHAR(64) NOT NULL COMMENT 'Facebook充主页ID（部ID?,
  `public_page_name` VARCHAR(255) NOT NULL COMMENT '充主页名称',
  `authorization_status` VARCHAR(32) NOT NULL DEFAULT 'UNAUTHORIZED' COMMENT '授权状（AUTHORIZED/UNAUTHORIZED?,
  `authorization_time` DATETIME NULL COMMENT '授权时间',
  `rating` DECIMAL(5,2) NULL COMMENT '评分（可选）',
  `auto_rule_status` VARCHAR(32) NULL COMMENT '臊规则状缓存（叉）',
  `auto_hide_comments` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '臊隐藏评关缓存（0?，可选）',
  `last_sync_at` DATETIME NULL COMMENT '近一次同步时?,
  `created_by` BIGINT UNSIGNED NULL COMMENT '创建人ID（系统内用户?,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_pages_public_page_id` (`public_page_id`),
  KEY `idx_meta_fb_pages_authorization_status` (`authorization_status`),
  KEY `idx_meta_fb_pages_last_sync_at` (`last_sync_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook主页?;

CREATE TABLE IF NOT EXISTS `meta_fb_keyword_packs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pack_name` VARCHAR(128) NOT NULL COMMENT '关键词包名称',
  `status` VARCHAR(32) NOT NULL DEFAULT 'ENABLED' COMMENT '状（ENABLED/DISABLED?,
  `created_by` BIGINT UNSIGNED NULL COMMENT '创建人ID',
  `created_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '业务创建时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_keyword_packs_pack_name` (`pack_name`),
  KEY `idx_meta_fb_keyword_packs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook关键词包?;

CREATE TABLE IF NOT EXISTS `meta_fb_keywords` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `pack_id` BIGINT UNSIGNED NOT NULL COMMENT '关键词包ID',
  `keyword` VARCHAR(255) NOT NULL COMMENT '关键?,
  `match_type` VARCHAR(32) NOT NULL DEFAULT 'CONTAINS' COMMENT '匹配类型（EXACT/CONTAINS/REGEX?,
  `priority` INT NOT NULL DEFAULT 100 COMMENT '优先级（数越小优先级越高?,
  `reply_template` TEXT NULL COMMENT '回模板内',
  `enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '昐吔??昼',
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
  `status` VARCHAR(32) NOT NULL DEFAULT 'DISABLED' COMMENT '规则状（ENABLED/DISABLED?,
  `action` VARCHAR(32) NOT NULL DEFAULT 'HIDE_ALL' COMMENT '规则动作（HIDE_ALL/HIDE_KEYWORDS?,
  `keyword_pack_id` BIGINT UNSIGNED NULL COMMENT '关键词包ID（action=HIDE_KEYWORDS时必塼',
  `updated_by` BIGINT UNSIGNED NULL COMMENT '后更新人ID',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook主页臊规则配置?;

CREATE TABLE IF NOT EXISTS `meta_fb_page_bindings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `page_id` BIGINT UNSIGNED NOT NULL COMMENT '主页ID',
  `source_type` VARCHAR(32) NOT NULL COMMENT '来源类型（fb_personal_account/business/token/asset?,
  `source_id` VARCHAR(128) NOT NULL COMMENT '来源对象ID',
  `source_name` VARCHAR(255) NULL COMMENT '来源对象名称（FB为号昵称）',
  `status` VARCHAR(32) NOT NULL DEFAULT 'ACTIVE' COMMENT '绑定状（ACTIVE/REVOKED/EXPIRED?,
  `authorized_at` DATETIME NULL COMMENT '授权生效时间',
  `expired_at` DATETIME NULL COMMENT '授权过期时间',
  `last_sync_at` DATETIME NULL COMMENT '近同步时?,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_page_bindings_page_source` (`page_id`, `source_type`, `source_id`),
  KEY `idx_meta_fb_page_bindings_source` (`source_type`, `source_id`),
  KEY `idx_meta_fb_page_bindings_status` (`status`),
  CONSTRAINT `fk_meta_fb_page_bindings_page_id`
    FOREIGN KEY (`page_id`) REFERENCES `meta_fb_pages` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook主页绑定来源?;

CREATE TABLE IF NOT EXISTS `meta_fb_post_comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `page_id` BIGINT UNSIGNED NOT NULL COMMENT '主页ID',
  `post_id` VARCHAR(128) NOT NULL COMMENT '帖子ID',
  `comment_id` VARCHAR(128) NOT NULL COMMENT '评外部ID',
  `comment_content` TEXT NOT NULL COMMENT '评内',
  `status` VARCHAR(32) NOT NULL DEFAULT 'VISIBLE' COMMENT '评状（VISIBLE/HIDDEN/DELETED?,
  `likes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞?,
  `replies` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '回?,
  `created_time` DATETIME NULL COMMENT '评创建时间（来源时间）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '入库创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_meta_fb_post_comments_comment_id` (`comment_id`),
  KEY `idx_meta_fb_post_comments_page_post` (`page_id`, `post_id`),
  KEY `idx_meta_fb_post_comments_status_created_time` (`status`, `created_time`),
  CONSTRAINT `fk_meta_fb_post_comments_page_id`
    FOREIGN KEY (`page_id`) REFERENCES `meta_fb_pages` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook评?;

CREATE TABLE IF NOT EXISTS `meta_fb_comment_actions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `comment_row_id` BIGINT UNSIGNED NOT NULL COMMENT '评行ID（关联评论主衼',
  `action_type` VARCHAR(32) NOT NULL COMMENT '操作类型（HIDE/DELETE/REPLY?,
  `action_payload` JSON NULL COMMENT '操作参数（回内、原因）',
  `operator_id` BIGINT UNSIGNED NULL COMMENT '操作人ID',
  `result_status` VARCHAR(32) NOT NULL DEFAULT 'SUCCESS' COMMENT '执结果（SUCCESS/FAIL?,
  `result_message` VARCHAR(1000) NULL COMMENT '执结果信息',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_fb_comment_actions_comment_row_id` (`comment_row_id`),
  KEY `idx_meta_fb_comment_actions_action_type` (`action_type`),
  KEY `idx_meta_fb_comment_actions_result_status` (`result_status`),
  CONSTRAINT `fk_meta_fb_comment_actions_comment_row_id`
    FOREIGN KEY (`comment_row_id`) REFERENCES `meta_fb_post_comments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-Facebook评操作记录?;

/* =====================================================================
 * 来源文件：tool_task_center.sql
 * 模块：工?任务丿
 * ===================================================================== */

CREATE TABLE IF NOT EXISTS `meta_task_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务ID（任务中心列表的任务ID?,
  `type` VARCHAR(32) NOT NULL COMMENT '任务类型（create_ad/batch_edit/material_push/ai_record?,
  `status` VARCHAR(32) NOT NULL DEFAULT 'processing' COMMENT '任务状（processing/completed/failed?,
  `result` VARCHAR(32) NULL COMMENT '任务结果（success/fail/partial?,
  `submit_method` VARCHAR(32) NOT NULL DEFAULT 'manual' COMMENT '提交方式（manual/auto?,
  `submit_time` DATETIME NOT NULL COMMENT '提交时间（建讌业务时区入库后展示）',
  `end_time` DATETIME NULL COMMENT '任务结束时间',
  `creator_id` BIGINT UNSIGNED NULL COMMENT '创建人ID（系统内用户?,
  `channel` VARCHAR(32) NULL COMMENT '通道/平台（facebook/google/tiktok等）',
  `account` VARCHAR(128) NULL COMMENT '账户标识（按页面筛口径落地）',
  `asset` VARCHAR(128) NULL COMMENT '资产标识（按页面筛口径落地）',
  `batch_id` VARCHAR(128) NULL COMMENT 'AI运批ID（当 type=ai_record 时）',
  `source_job_id` BIGINT UNSIGNED NULL COMMENT '复制来源任务ID（用于制已有任务还原创建草稿）',
  `payload` JSON NULL COMMENT '任务参数必（原始参?审/重放?,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-任务丿-任务主表';

CREATE TABLE IF NOT EXISTS `meta_task_job_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务明细ID（任务象执行结果）',
  `job_id` BIGINT UNSIGNED NOT NULL COMMENT '任务主表ID（meta_task_jobs.id?,
  `target_type` VARCHAR(64) NOT NULL COMMENT '执盠类型（account/material/ad_asset/ad_plan/ad_group/rule等）',
  `target_id` VARCHAR(128) NOT NULL COMMENT '执盠ID（部ID或内部ID?,
  `target_name` VARCHAR(255) NULL COMMENT '执盠名称（用于?日志展示?,
  `status` VARCHAR(32) NOT NULL DEFAULT 'processing' COMMENT '执状（processing/success/fail?,
  `result` VARCHAR(32) NULL COMMENT '执结果（success/fail/partial?,
  `message` VARCHAR(2000) NULL COMMENT '执信息/失败原因（摘要）',
  `started_at` DATETIME NULL COMMENT '始时?,
  `finished_at` DATETIME NULL COMMENT '结束时间',
  `retry_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '重试次数',
  `original_item_id` BIGINT UNSIGNED NULL COMMENT '重试关联的原任务明细ID（用于追溤败象并重跑?,
  `payload` JSON NULL COMMENT '明细参数/回执必',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-任务丿-任务明细?;

CREATE TABLE IF NOT EXISTS `meta_task_operation_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '操作日志ID',
  `job_id` BIGINT UNSIGNED NOT NULL COMMENT '任务主表ID（meta_task_jobs.id?,
  `job_item_id` BIGINT UNSIGNED NULL COMMENT '关联的任务明细ID（meta_task_job_items.id，可选）',
  `action_type` VARCHAR(64) NOT NULL COMMENT '操作类型（CREATE/EDIT/COPY/AI_EXECUTE/RETRY等）',
  `operator_id` BIGINT UNSIGNED NULL COMMENT '执人ID（系统内用户?,
  `action_payload` JSON NULL COMMENT '操作参数（制来源AI助手输入输出、重试参数等?,
  `result_status` VARCHAR(32) NOT NULL DEFAULT 'SUCCESS' COMMENT '执结果（SUCCESS/FAIL?,
  `result_message` VARCHAR(2000) NULL COMMENT '执结果信息/失败原因',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-任务丿-任务操作日志?;

/* =====================================================================
 * 来源文件：tool_notifications.sql
 * 模块：工?通知丿
 * ===================================================================== */

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `meta_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通知ID',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '接收用户ID（系统内用户?,
  `type` VARCHAR(64) NOT NULL COMMENT '通知类型（system/task/ai/marketing等）',
  `title` VARCHAR(255) NOT NULL COMMENT '通知标',
  `content` TEXT NOT NULL COMMENT '通知内（已渲染文，支持诨?,
  `extra` JSON NULL COMMENT '扩展字（跳轓接业D等）',
  `status` VARCHAR(32) NOT NULL DEFAULT 'unread' COMMENT '通知状（unread/read/deleted?,
  `read_at` DATETIME NULL COMMENT '阅时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_notifications_user_id` (`user_id`),
  KEY `idx_meta_notifications_status` (`status`),
  KEY `idx_meta_notifications_type` (`type`),
  KEY `idx_meta_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-通知丿-用户通知?;

/* =====================================================================
 * 来源文件：tool_scheduled_reports.sql
 * 模块：工?定时报表
 * ===================================================================== */

CREATE TABLE IF NOT EXISTS `meta_scheduled_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '定时报表ID',
  `company_id` BIGINT UNSIGNED NOT NULL COMMENT '兏主体ID（用于限额与权限?,
  `name` VARCHAR(128) NOT NULL COMMENT '定时报表名称',
  `description` VARCHAR(255) NULL COMMENT '定时报表描述',
  `status` VARCHAR(32) NOT NULL DEFAULT 'enabled' COMMENT '状（enabled/disabled?,
  `schedule_type` VARCHAR(32) NOT NULL COMMENT '调度类型（daily/weekly/custom?,
  `cron_expression` VARCHAR(64) NULL COMMENT '臮义cron表达式（当schedule_type=custom时生效）',
  `run_time_of_day` TIME NULL COMMENT '每日/每周执时间（本地业务时区）',
  `run_weekday` TINYINT UNSIGNED NULL COMMENT '每周执星期?-7，周=1?,
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'Asia/Shanghai' COMMENT '时区标识',
  `max_card_count` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '卡片数量上限（默??,
  `email_to` VARCHAR(500) NOT NULL COMMENT '收件人邮箱列衼逗号分隔?,
  `email_cc` VARCHAR(500) NULL COMMENT '抄邮箱列衼逗号分隔?,
  `last_run_time` DATETIME NULL COMMENT '上执时间',
  `next_run_time` DATETIME NULL COMMENT '下计划执时间',
  `last_run_status` VARCHAR(32) NULL COMMENT '上执状（success/fail/running?,
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
  `report_id` BIGINT UNSIGNED NOT NULL COMMENT '属定时报表ID（meta_scheduled_reports.id?,
  `title` VARCHAR(128) NOT NULL COMMENT '卡片标（邮件中展示标?,
  `report_type` VARCHAR(32) NOT NULL COMMENT '报表类型（summary/material?,
  `channel` VARCHAR(32) NOT NULL COMMENT '渠道（summary/facebook/google/tiktok?,
  `data_time_mode` VARCHAR(32) NOT NULL DEFAULT 'single' COMMENT '数据时间模式（single/compare?,
  `date_range_start` DATE NULL COMMENT '单个时间范围-始日?,
  `date_range_end` DATE NULL COMMENT '单个时间范围-结束日期',
  `compare_date_range_start` DATE NULL COMMENT '对比时间范围-始日?,
  `compare_date_range_end` DATE NULL COMMENT '对比时间范围-结束日期',
  `filters` JSON NULL COMMENT '筛条件（维度筛?按指标数值筛选等?,
  `sort_field` VARCHAR(64) NULL COMMENT '排序字',
  `sort_direction` VARCHAR(8) NULL COMMENT '排序方向（asc/desc?,
  `limit_rows` INT UNSIGNED NOT NULL DEFAULT 10 COMMENT '数据节条数（10/20/50?,
  `position` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '卡片在邮件中的排序位罼?始）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_scheduled_report_cards_report_id` (`report_id`),
  CONSTRAINT `fk_meta_scheduled_report_cards_report_id`
    FOREIGN KEY (`report_id`) REFERENCES `meta_scheduled_reports` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-定时报表-数据卡片?;

CREATE TABLE IF NOT EXISTS `meta_scheduled_report_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '运记录ID',
  `report_id` BIGINT UNSIGNED NOT NULL COMMENT '定时报表ID（meta_scheduled_reports.id?,
  `trigger_type` VARCHAR(32) NOT NULL COMMENT '触发类型（schedule/manual?,
  `status` VARCHAR(32) NOT NULL COMMENT '运状（queued/running/success/fail?,
  `message` VARCHAR(2000) NULL COMMENT '运结果描述/失败原因',
  `email_to` VARCHAR(500) NOT NULL COMMENT '实际发的收件人邮?,
  `email_cc` VARCHAR(500) NULL COMMENT '实际发的抄邮?,
  `attachment_url` VARCHAR(500) NULL COMMENT 'xlsx 报表下载地址',
  `started_at` DATETIME NULL COMMENT '始时?,
  `finished_at` DATETIME NULL COMMENT '结束时间',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_meta_scheduled_report_runs_report_id` (`report_id`),
  KEY `idx_meta_scheduled_report_runs_status` (`status`),
  KEY `idx_meta_scheduled_report_runs_started_at` (`started_at`),
  CONSTRAINT `fk_meta_scheduled_report_runs_report_id`
    FOREIGN KEY (`report_id`) REFERENCES `meta_scheduled_reports` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta工具-定时报表-运记录?;
/* =====================================================================
 * 2026-04-08 链接模块功能更新
 * 说明: 增加链接多字、入能力，并修复所有判?
 * 涉及文件: database/migrations/2026_04_08_160000_add_language_fields_to_links_table.php
 *          backend/app/Http/Resources/LinkResource.php
 *          backend/app/Http/Controllers/LinkController.php
 * ===================================================================== */

/* =====================================================================
 * 2026-04-08 links 表结构更?
 * 迁移文件: database/migrations/2026_04_08_160000_add_language_fields_to_links_table.php
 * 数据? links
 * 新字: default_locale VARCHAR(32) NULL,
 *          language_variants JSON NULL,
 *          import_source VARCHAR(32) NULL
 * ===================================================================== */

ALTER TABLE `links`
  ADD COLUMN `default_locale` VARCHAR(32) NULL COMMENT '默认语言代码' AFTER `notes`,
  ADD COLUMN `language_variants` JSON NULL COMMENT '多语言 URL 变体配置' AFTER `default_locale`,
  ADD COLUMN `import_source` VARCHAR(32) NULL COMMENT '手动或导入来源标记' AFTER `language_variants`;
/* =====================================================================
 * 2026-04-08 links 标签表
 * 迁移文件: database/migrations/2026_04_08_170000_create_link_tags_table.php
 * 数据表: link_tags
 * 用途: links 模块不再复用通用 tags/taggables，改为使用独立 link_tags 表存储链接标签
 * ===================================================================== */

CREATE TABLE `link_tags` (
  `id` CHAR(26) NOT NULL COMMENT '主键 ULID',
  `link_id` CHAR(26) NOT NULL COMMENT '关联 links.id',
  `user_id` VARCHAR(26) NOT NULL COMMENT '所属用户 ID',
  `name` VARCHAR(191) NOT NULL COMMENT '标签名称快照',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT '更新时间',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_link_tags_link_user` (`link_id`, `user_id`),
  KEY `idx_link_tags_user_name` (`user_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='链接模块独立标签表';
/* =====================================================================
 * 2026-04-08 link_tags 表注释补充
 * 迁移文件: database/migrations/2026_04_08_180000_add_comments_to_link_tags_table.php
 * 变更说明: 为 link_tags 表及其字段补充中文注释，并将存储引擎调整为 InnoDB
 * ===================================================================== */

/* =====================================================================
 * 2026-04-10 link_tags 补充标签选项关联字段
 * 迁移文件: database/migrations/2026_04_10_170000_add_meta_tag_option_id_to_link_tags_table.php
 * 变更说明: 增加 meta_tag_option_id，用于关联 meta_tag_options.id，解决同名标签无法精确关联的问题
 * ===================================================================== */

ALTER TABLE `link_tags`
  ADD COLUMN `meta_tag_option_id` BIGINT UNSIGNED NULL COMMENT '关联 meta_tag_options.id' AFTER `user_id`,
  ADD KEY `idx_link_tags_user_option` (`user_id`, `meta_tag_option_id`),
  ADD KEY `idx_link_tags_link_option` (`link_id`, `meta_tag_option_id`);

