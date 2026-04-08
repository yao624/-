-- ============================================
-- 租户数据库完整建表脚本
-- 说明：此脚本包含租户数据库需要的所有业务表
-- 执行方式：在租户数据库中执行此脚本
-- 注意：此脚本基于当前所有迁移文件生成，确保租户可以使用系统的所有功能
-- ============================================

-- 设置数据库
-- USE `your_tenant_database_name`;

-- ============================================
-- 1. 基础表（Laravel 核心表）
-- ============================================

-- 1.1 用户表
CREATE TABLE IF NOT EXISTS `users` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `email_verified_at` TIMESTAMP NULL,
    `password` VARCHAR(255) NOT NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2 密码重置令牌表
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.3 失败任务表
CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(255) NOT NULL UNIQUE,
    `connection` TEXT NOT NULL,
    `queue` TEXT NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 注意：personal_access_tokens 表在主数据库中，不在租户数据库

-- ============================================
-- 2. 权限管理表（Spatie Permission）
-- ============================================

-- 2.1 权限表
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `guard_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 角色表
CREATE TABLE IF NOT EXISTS `roles` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `guard_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3 模型权限关联表
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
    `permission_id` CHAR(26) NOT NULL,
    `model_type` VARCHAR(255) NOT NULL,
    `model_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
    INDEX `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
    CONSTRAINT `model_has_permissions_permission_id_foreign` 
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4 模型角色关联表
CREATE TABLE IF NOT EXISTS `model_has_roles` (
    `role_id` CHAR(26) NOT NULL,
    `model_type` VARCHAR(255) NOT NULL,
    `model_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`role_id`, `model_id`, `model_type`),
    INDEX `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
    CONSTRAINT `model_has_roles_role_id_foreign` 
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5 角色权限关联表
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
    `permission_id` CHAR(26) NOT NULL,
    `role_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`permission_id`, `role_id`),
    CONSTRAINT `role_has_permissions_permission_id_foreign` 
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `role_has_permissions_role_id_foreign` 
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. 队列相关表
-- ============================================

-- 3.1 任务表
CREATE TABLE IF NOT EXISTS `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    INDEX `idx_queue` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.2 任务批次表
CREATE TABLE IF NOT EXISTS `job_batches` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL,
    `cancelled_at` INT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. 追踪系统相关表
-- ============================================

-- 4.1 网络表
CREATE TABLE IF NOT EXISTS `networks` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `system_type` VARCHAR(255) NOT NULL COMMENT 'Cake, Everflow, Jumb, Keitaro',
    `aff_id` VARCHAR(255) NOT NULL,
    `endpoint` TEXT NOT NULL,
    `apikey` TEXT NOT NULL,
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    `click_placeholder` TEXT NULL,
    `notes` TEXT NULL,
    `user_id` CHAR(26) NULL,
    `subid_mapping_id` CHAR(26) NULL,
    `is_subnetwork` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_system_type` (`system_type`),
    INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.2 点击表
CREATE TABLE IF NOT EXISTS `clicks` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `network_id` CHAR(26) NOT NULL,
    `click_datetime` TIMESTAMP NOT NULL,
    `sub_1` VARCHAR(255) NULL,
    `sub_2` VARCHAR(255) NULL,
    `sub_3` VARCHAR(255) NULL,
    `sub_4` VARCHAR(255) NULL,
    `sub_5` VARCHAR(255) NULL,
    `aff_id` VARCHAR(255) NULL,
    `country_code` VARCHAR(10) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_network_id` (`network_id`),
    INDEX `idx_click_datetime` (`click_datetime`),
    INDEX `idx_sub_1` (`sub_1`),
    INDEX `idx_aff_id` (`aff_id`),
    CONSTRAINT `clicks_network_id_foreign` 
        FOREIGN KEY (`network_id`) REFERENCES `networks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.3 转化表
CREATE TABLE IF NOT EXISTS `conversions` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `network_id` CHAR(26) NOT NULL,
    `conversion_datetime` TIMESTAMP NOT NULL,
    `sub_1` VARCHAR(255) NULL,
    `sub_2` VARCHAR(255) NULL,
    `sub_3` VARCHAR(255) NULL,
    `sub_4` VARCHAR(255) NULL,
    `sub_5` VARCHAR(255) NULL,
    `aff_id` VARCHAR(255) NULL,
    `price` DECIMAL(10, 2) NULL,
    `country_code` VARCHAR(10) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_network_id` (`network_id`),
    INDEX `idx_conversion_datetime` (`conversion_datetime`),
    INDEX `idx_sub_1` (`sub_1`),
    INDEX `idx_aff_id` (`aff_id`),
    CONSTRAINT `conversions_network_id_foreign` 
        FOREIGN KEY (`network_id`) REFERENCES `networks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.4 追踪器表
CREATE TABLE IF NOT EXISTS `trackers` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `system_type` VARCHAR(255) NOT NULL,
    `url` TEXT NOT NULL,
    `username` VARCHAR(255) NOT NULL,
    `password` TEXT NOT NULL,
    `user_id` CHAR(26) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.5 追踪器活动表
CREATE TABLE IF NOT EXISTS `tracker_campaigns` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `tracker_id` CHAR(26) NOT NULL,
    `campaign_id` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_tracker_id` (`tracker_id`),
    CONSTRAINT `tracker_campaigns_tracker_id_foreign` 
        FOREIGN KEY (`tracker_id`) REFERENCES `trackers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.6 追踪器点击表
CREATE TABLE IF NOT EXISTS `tracker_offer_clicks` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `tracker_id` CHAR(26) NOT NULL,
    `campaign_id` VARCHAR(255) NULL,
    `offer_id` VARCHAR(255) NULL,
    `click_datetime` TIMESTAMP NULL,
    `sub_1` VARCHAR(255) NULL,
    `sub_2` VARCHAR(255) NULL,
    `sub_3` VARCHAR(255) NULL,
    `sub_4` VARCHAR(255) NULL,
    `sub_5` VARCHAR(255) NULL,
    `identifier` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_tracker_id` (`tracker_id`),
    CONSTRAINT `tracker_offer_clicks_tracker_id_foreign` 
        FOREIGN KEY (`tracker_id`) REFERENCES `trackers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.7 SubID 映射表
CREATE TABLE IF NOT EXISTS `subid_mappings` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `user_id` CHAR(26) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. Facebook 相关表
-- ============================================

-- 5.1 Facebook 账户表
CREATE TABLE IF NOT EXISTS `fb_accounts` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `access_token` TEXT NULL,
    `picture` TEXT NULL,
    `user_id` CHAR(26) NULL,
    `is_archived` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.2 Facebook 广告账户表
CREATE TABLE IF NOT EXISTS `fb_ad_accounts` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `account_status` INT NULL,
    `amount_spent` DECIMAL(10, 2) NULL,
    `balance` DECIMAL(10, 2) NULL,
    `currency` VARCHAR(10) NULL,
    `timezone_name` VARCHAR(255) NULL,
    `timezone_offset_hours_utc` INT NULL,
    `is_notifications_enabled` BOOLEAN NULL,
    `is_prepay_account` BOOLEAN NULL,
    `is_original` BOOLEAN NULL,
    `total_spent` DECIMAL(10, 2) NULL,
    `fixed_balance` DECIMAL(10, 2) NULL,
    `is_prepay` BOOLEAN NULL,
    `adtrust_dsl` DECIMAL(10, 2) NULL,
    `auto_sync` BOOLEAN NOT NULL DEFAULT FALSE,
    `is_archived` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`),
    INDEX `idx_account_status` (`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.3 Facebook 活动表
CREATE TABLE IF NOT EXISTS `fb_campaigns` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL,
    `status` VARCHAR(255) NULL,
    `objective` VARCHAR(255) NULL,
    `daily_budget` DECIMAL(10, 2) NULL,
    `lifetime_budget` DECIMAL(10, 2) NULL,
    `bid_strategy` VARCHAR(255) NULL,
    `fb_ad_account_id` CHAR(26) NULL,
    `is_archived` BOOLEAN NOT NULL DEFAULT FALSE,
    `is_deleted_on_fb` BOOLEAN NOT NULL DEFAULT FALSE,
    `original_daily_budget_id` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`),
    INDEX `idx_fb_ad_account_id` (`fb_ad_account_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fb_campaigns_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.4 Facebook 广告组表
CREATE TABLE IF NOT EXISTS `fb_adsets` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL,
    `status` VARCHAR(255) NULL,
    `daily_budget` DECIMAL(10, 2) NULL,
    `lifetime_budget` DECIMAL(10, 2) NULL,
    `bid_amount` DECIMAL(10, 2) NULL,
    `billing_event` VARCHAR(255) NULL,
    `optimization_goal` VARCHAR(255) NULL,
    `fb_campaign_id` CHAR(26) NULL,
    `promoted_object` JSON NULL,
    `is_deleted_on_fb` BOOLEAN NOT NULL DEFAULT FALSE,
    `original_daily_budget_id` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`),
    INDEX `idx_fb_campaign_id` (`fb_campaign_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fb_adsets_fb_campaign_id_foreign` 
        FOREIGN KEY (`fb_campaign_id`) REFERENCES `fb_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.5 Facebook 广告表
CREATE TABLE IF NOT EXISTS `fb_ads` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL,
    `status` VARCHAR(255) NULL,
    `creative` JSON NULL,
    `fb_adset_id` CHAR(26) NULL,
    `is_deleted_on_fb` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`),
    INDEX `idx_fb_adset_id` (`fb_adset_id`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fb_ads_fb_adset_id_foreign` 
        FOREIGN KEY (`fb_adset_id`) REFERENCES `fb_adsets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.6 Facebook 洞察数据表（广告账户）
CREATE TABLE IF NOT EXISTS `fb_ad_account_insights` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_ad_account_id` CHAR(26) NOT NULL,
    `date_start` DATE NOT NULL,
    `date_stop` DATE NOT NULL,
    `spend` DECIMAL(10, 2) NULL,
    `impressions` INT NULL,
    `clicks` INT NULL,
    `reach` INT NULL,
    `actions` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_fb_ad_account_id` (`fb_ad_account_id`),
    INDEX `idx_date_start` (`date_start`),
    INDEX `idx_date_stop` (`date_stop`),
    CONSTRAINT `fb_ad_account_insights_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.7 Facebook 洞察数据表（活动）
CREATE TABLE IF NOT EXISTS `fb_campaign_insights` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_campaign_id` CHAR(26) NOT NULL,
    `date_start` DATE NOT NULL,
    `date_stop` DATE NOT NULL,
    `spend` DECIMAL(10, 2) NULL,
    `impressions` INT NULL,
    `clicks` INT NULL,
    `reach` INT NULL,
    `actions` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_fb_campaign_id` (`fb_campaign_id`),
    INDEX `idx_date_start` (`date_start`),
    CONSTRAINT `fb_campaign_insights_fb_campaign_id_foreign` 
        FOREIGN KEY (`fb_campaign_id`) REFERENCES `fb_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.8 Facebook 洞察数据表（广告组）
CREATE TABLE IF NOT EXISTS `fb_adset_insights` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_adset_id` CHAR(26) NOT NULL,
    `date_start` DATE NOT NULL,
    `date_stop` DATE NOT NULL,
    `spend` DECIMAL(10, 2) NULL,
    `impressions` INT NULL,
    `clicks` INT NULL,
    `reach` INT NULL,
    `actions` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_fb_adset_id` (`fb_adset_id`),
    INDEX `idx_date_start` (`date_start`),
    CONSTRAINT `fb_adset_insights_fb_adset_id_foreign` 
        FOREIGN KEY (`fb_adset_id`) REFERENCES `fb_adsets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.9 Facebook 洞察数据表（广告）
CREATE TABLE IF NOT EXISTS `fb_ad_insights` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_ad_id` CHAR(26) NOT NULL,
    `date_start` DATE NOT NULL,
    `date_stop` DATE NOT NULL,
    `spend` DECIMAL(10, 2) NULL,
    `impressions` INT NULL,
    `clicks` INT NULL,
    `reach` INT NULL,
    `actions` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_fb_ad_id` (`fb_ad_id`),
    INDEX `idx_date_start` (`date_start`),
    CONSTRAINT `fb_ad_insights_fb_ad_id_foreign` 
        FOREIGN KEY (`fb_ad_id`) REFERENCES `fb_ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.10 Facebook 业务管理器表
CREATE TABLE IF NOT EXISTS `fb_bms` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `timezone_id` INT NULL,
    `is_disabled_for_integrity_reasons` BOOLEAN NULL,
    `created_time` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.11 Facebook 页面表
CREATE TABLE IF NOT EXISTS `fb_pages` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `access_token` TEXT NULL,
    `category` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.12 Facebook 像素表
CREATE TABLE IF NOT EXISTS `fb_pixels` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.13 Facebook 业务用户表
CREATE TABLE IF NOT EXISTS `fb_business_users` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `email` VARCHAR(255) NULL,
    `two_fac_status` VARCHAR(255) NULL,
    `fb_bm_id` CHAR(26) NULL,
    `user_type` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`),
    INDEX `idx_fb_bm_id` (`fb_bm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.14 Facebook 客户端广告账户表
CREATE TABLE IF NOT EXISTS `fb_client_ad_accounts` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.15 Facebook API Token 表
CREATE TABLE IF NOT EXISTS `fb_api_tokens` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `token` TEXT NOT NULL,
    `app_id` VARCHAR(255) NULL,
    `app_secret` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.16 Facebook 应用表
CREATE TABLE IF NOT EXISTS `fb_apps` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.17 Facebook 页面表单表
CREATE TABLE IF NOT EXISTS `fb_page_forms` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_page_id` CHAR(26) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(255) NULL,
    `locale` VARCHAR(10) NULL,
    `privacy_policy_url` TEXT NULL,
    `questions` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_fb_page_id` (`fb_page_id`),
    INDEX `idx_source_id` (`source_id`),
    CONSTRAINT `fb_page_forms_fb_page_id_foreign` 
        FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.18 Facebook 页面帖子表
CREATE TABLE IF NOT EXISTS `fb_page_posts` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_page_id` CHAR(26) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `message` TEXT NULL,
    `created_time` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_fb_page_id` (`fb_page_id`),
    INDEX `idx_source_id` (`source_id`),
    CONSTRAINT `fb_page_posts_fb_page_id_foreign` 
        FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.19 Facebook 广告模板表
CREATE TABLE IF NOT EXISTS `fb_ad_templates` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `user_id` CHAR(26) NULL,
    `template_data` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.20 Facebook 目录表
CREATE TABLE IF NOT EXISTS `fb_catalogs` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.21 Facebook 目录产品表
CREATE TABLE IF NOT EXISTS `fb_catalog_products` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_catalog_id` CHAR(26) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(255) NULL,
    `product_data` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_fb_catalog_id` (`fb_catalog_id`),
    INDEX `idx_source_id` (`source_id`),
    CONSTRAINT `fb_catalog_products_fb_catalog_id_foreign` 
        FOREIGN KEY (`fb_catalog_id`) REFERENCES `fb_catalogs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.22 Facebook 目录产品集表
CREATE TABLE IF NOT EXISTS `fb_catalog_product_sets` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_catalog_id` CHAR(26) NOT NULL,
    `source_id` VARCHAR(255) NOT NULL UNIQUE,
    `name` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_fb_catalog_id` (`fb_catalog_id`),
    INDEX `idx_source_id` (`source_id`),
    CONSTRAINT `fb_catalog_product_sets_fb_catalog_id_foreign` 
        FOREIGN KEY (`fb_catalog_id`) REFERENCES `fb_catalogs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. Facebook 关联表（多对多关系）
-- ============================================

-- 6.1 Facebook 账户-用户关联表
CREATE TABLE IF NOT EXISTS `fb_account_user` (
    `fb_account_id` CHAR(26) NOT NULL,
    `user_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_account_id`, `user_id`),
    CONSTRAINT `fb_account_user_fb_account_id_foreign` 
        FOREIGN KEY (`fb_account_id`) REFERENCES `fb_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_account_user_user_id_foreign` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.2 Facebook 账户-页面关联表
CREATE TABLE IF NOT EXISTS `fb_account_page` (
    `fb_account_id` CHAR(26) NOT NULL,
    `fb_page_id` CHAR(26) NOT NULL,
    `role` VARCHAR(255) NULL,
    `role_human` VARCHAR(255) NULL,
    `source_id` VARCHAR(255) NULL,
    `name` VARCHAR(255) NULL,
    PRIMARY KEY (`fb_account_id`, `fb_page_id`),
    CONSTRAINT `fb_account_page_fb_account_id_foreign` 
        FOREIGN KEY (`fb_account_id`) REFERENCES `fb_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_account_page_fb_page_id_foreign` 
        FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.3 Facebook 账户-广告账户关联表
CREATE TABLE IF NOT EXISTS `fb_account_fb_ad_account` (
    `fb_account_id` CHAR(26) NOT NULL,
    `fb_ad_account_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_account_id`, `fb_ad_account_id`),
    CONSTRAINT `fb_account_fb_ad_account_fb_account_id_foreign` 
        FOREIGN KEY (`fb_account_id`) REFERENCES `fb_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_account_fb_ad_account_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.4 Facebook 广告账户-业务管理器关联表
CREATE TABLE IF NOT EXISTS `fb_ad_account_fb_bm` (
    `fb_ad_account_id` CHAR(26) NOT NULL,
    `fb_bm_id` CHAR(26) NOT NULL,
    `source_id` VARCHAR(255) NULL,
    PRIMARY KEY (`fb_ad_account_id`, `fb_bm_id`),
    CONSTRAINT `fb_ad_account_fb_bm_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_ad_account_fb_bm_fb_bm_id_foreign` 
        FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.5 Facebook 广告账户-业务用户关联表
CREATE TABLE IF NOT EXISTS `fb_ad_account_fb_business_user` (
    `fb_ad_account_id` CHAR(26) NOT NULL,
    `fb_business_user_id` CHAR(26) NOT NULL,
    `role` VARCHAR(255) NULL,
    PRIMARY KEY (`fb_ad_account_id`, `fb_business_user_id`),
    CONSTRAINT `fb_ad_account_fb_business_user_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_ad_account_fb_business_user_fb_business_user_id_foreign` 
        FOREIGN KEY (`fb_business_user_id`) REFERENCES `fb_business_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.6 Facebook 广告账户-用户关联表
CREATE TABLE IF NOT EXISTS `fb_ad_account_user` (
    `fb_ad_account_id` CHAR(26) NOT NULL,
    `user_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_ad_account_id`, `user_id`),
    CONSTRAINT `fb_ad_account_user_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_ad_account_user_user_id_foreign` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.7 Facebook 像素-广告账户关联表
CREATE TABLE IF NOT EXISTS `fb_pixel_fb_ad_account` (
    `fb_pixel_id` CHAR(26) NOT NULL,
    `fb_ad_account_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_pixel_id`, `fb_ad_account_id`),
    CONSTRAINT `fb_pixel_fb_ad_account_fb_pixel_id_foreign` 
        FOREIGN KEY (`fb_pixel_id`) REFERENCES `fb_pixels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_pixel_fb_ad_account_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.8 Facebook 像素-业务管理器关联表
CREATE TABLE IF NOT EXISTS `fb_pixel_fb_bm` (
    `fb_pixel_id` CHAR(26) NOT NULL,
    `fb_bm_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_pixel_id`, `fb_bm_id`),
    CONSTRAINT `fb_pixel_fb_bm_fb_pixel_id_foreign` 
        FOREIGN KEY (`fb_pixel_id`) REFERENCES `fb_pixels` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_pixel_fb_bm_fb_bm_id_foreign` 
        FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.9 Facebook 广告-页面关联表
CREATE TABLE IF NOT EXISTS `fb_ad_fb_page` (
    `fb_ad_id` CHAR(26) NOT NULL,
    `fb_page_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_ad_id`, `fb_page_id`),
    CONSTRAINT `fb_ad_fb_page_fb_ad_id_foreign` 
        FOREIGN KEY (`fb_ad_id`) REFERENCES `fb_ads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_ad_fb_page_fb_page_id_foreign` 
        FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.10 Facebook API Token-广告账户关联表
CREATE TABLE IF NOT EXISTS `fb_api_token_fb_ad_account` (
    `fb_api_token_id` CHAR(26) NOT NULL,
    `fb_ad_account_id` CHAR(26) NOT NULL,
    `tasks` JSON NULL,
    PRIMARY KEY (`fb_api_token_id`, `fb_ad_account_id`),
    CONSTRAINT `fb_api_token_fb_ad_account_fb_api_token_id_foreign` 
        FOREIGN KEY (`fb_api_token_id`) REFERENCES `fb_api_tokens` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_api_token_fb_ad_account_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.11 Facebook API Token-页面关联表
CREATE TABLE IF NOT EXISTS `fb_api_token_fb_page` (
    `fb_api_token_id` CHAR(26) NOT NULL,
    `fb_page_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_api_token_id`, `fb_page_id`),
    CONSTRAINT `fb_api_token_fb_page_fb_api_token_id_foreign` 
        FOREIGN KEY (`fb_api_token_id`) REFERENCES `fb_api_tokens` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_api_token_fb_page_fb_page_id_foreign` 
        FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.12 Facebook 业务管理器-页面关联表
CREATE TABLE IF NOT EXISTS `fb_bm_fb_page` (
    `fb_bm_id` CHAR(26) NOT NULL,
    `fb_page_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_bm_id`, `fb_page_id`),
    CONSTRAINT `fb_bm_fb_page_fb_bm_id_foreign` 
        FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_bm_fb_page_fb_page_id_foreign` 
        FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.13 Facebook 业务用户-页面关联表
CREATE TABLE IF NOT EXISTS `fb_business_user_fb_page` (
    `fb_business_user_id` CHAR(26) NOT NULL,
    `fb_page_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_business_user_id`, `fb_page_id`),
    CONSTRAINT `fb_business_user_fb_page_fb_business_user_id_foreign` 
        FOREIGN KEY (`fb_business_user_id`) REFERENCES `fb_business_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_business_user_fb_page_fb_page_id_foreign` 
        FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.14 Facebook 业务管理器-目录关联表
CREATE TABLE IF NOT EXISTS `fb_bm_fb_catalog` (
    `fb_bm_id` CHAR(26) NOT NULL,
    `fb_catalog_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_bm_id`, `fb_catalog_id`),
    CONSTRAINT `fb_bm_fb_catalog_fb_bm_id_foreign` 
        FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_bm_fb_catalog_fb_catalog_id_foreign` 
        FOREIGN KEY (`fb_catalog_id`) REFERENCES `fb_catalogs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.15 Facebook 业务用户-目录关联表
CREATE TABLE IF NOT EXISTS `fb_business_user_fb_catalog` (
    `fb_business_user_id` CHAR(26) NOT NULL,
    `fb_catalog_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_business_user_id`, `fb_catalog_id`),
    CONSTRAINT `fb_business_user_fb_catalog_fb_business_user_id_foreign` 
        FOREIGN KEY (`fb_business_user_id`) REFERENCES `fb_business_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_business_user_fb_catalog_fb_catalog_id_foreign` 
        FOREIGN KEY (`fb_catalog_id`) REFERENCES `fb_catalogs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.16 Facebook 目录-像素关联表
CREATE TABLE IF NOT EXISTS `fb_catalog_fb_pixel` (
    `fb_catalog_id` CHAR(26) NOT NULL,
    `fb_pixel_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_catalog_id`, `fb_pixel_id`),
    CONSTRAINT `fb_catalog_fb_pixel_fb_catalog_id_foreign` 
        FOREIGN KEY (`fb_catalog_id`) REFERENCES `fb_catalogs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_catalog_fb_pixel_fb_pixel_id_foreign` 
        FOREIGN KEY (`fb_pixel_id`) REFERENCES `fb_pixels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.17 Facebook 应用-业务管理器关联表
CREATE TABLE IF NOT EXISTS `fb_app_fb_bm` (
    `fb_app_id` CHAR(26) NOT NULL,
    `fb_bm_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_app_id`, `fb_bm_id`),
    CONSTRAINT `fb_app_fb_bm_fb_app_id_foreign` 
        FOREIGN KEY (`fb_app_id`) REFERENCES `fb_apps` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_app_fb_bm_fb_bm_id_foreign` 
        FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.18 Facebook 应用-广告账户关联表
CREATE TABLE IF NOT EXISTS `fb_app_fb_ad_account` (
    `fb_app_id` CHAR(26) NOT NULL,
    `fb_ad_account_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_app_id`, `fb_ad_account_id`),
    CONSTRAINT `fb_app_fb_ad_account_fb_app_id_foreign` 
        FOREIGN KEY (`fb_app_id`) REFERENCES `fb_apps` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fb_app_fb_ad_account_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.19 产品集-产品关联表
CREATE TABLE IF NOT EXISTS `product_set_and_product` (
    `fb_catalog_product_set_id` CHAR(26) NOT NULL,
    `fb_catalog_product_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`fb_catalog_product_set_id`, `fb_catalog_product_id`),
    CONSTRAINT `product_set_and_product_fb_catalog_product_set_id_foreign` 
        FOREIGN KEY (`fb_catalog_product_set_id`) REFERENCES `fb_catalog_product_sets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `product_set_and_product_fb_catalog_product_id_foreign` 
        FOREIGN KEY (`fb_catalog_product_id`) REFERENCES `fb_catalog_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. 资源管理表
-- ============================================

-- 7.1 链接表
CREATE TABLE IF NOT EXISTS `links` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `url` TEXT NOT NULL,
    `user_id` CHAR(26) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.2 素材表
CREATE TABLE IF NOT EXISTS `materials` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `original_filename` VARCHAR(255) NULL,
    `filepath` TEXT NOT NULL,
    `type` VARCHAR(50) NULL COMMENT 'image, video',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.3 文案表
CREATE TABLE IF NOT EXISTS `copywritings` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `content` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.4 标签表
CREATE TABLE IF NOT EXISTS `tags` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `user_id` CHAR(26) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.5 标签关联表（多态）
CREATE TABLE IF NOT EXISTS `taggables` (
    `tag_id` CHAR(26) NOT NULL,
    `taggable_type` VARCHAR(255) NOT NULL,
    `taggable_id` CHAR(26) NOT NULL,
    `user_id` CHAR(26) NULL,
    PRIMARY KEY (`tag_id`, `taggable_id`, `taggable_type`),
    INDEX `idx_taggable` (`taggable_id`, `taggable_type`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `taggables_tag_id_foreign` 
        FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.6 资源分享表
CREATE TABLE IF NOT EXISTS `material_shares` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `material_id` CHAR(26) NOT NULL,
    `user_id` CHAR(26) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_material_id` (`material_id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `material_shares_material_id_foreign` 
        FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
    CONSTRAINT `material_shares_user_id_foreign` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `link_shares` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `link_id` CHAR(26) NOT NULL,
    `user_id` CHAR(26) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_link_id` (`link_id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `link_shares_link_id_foreign` 
        FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE CASCADE,
    CONSTRAINT `link_shares_user_id_foreign` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `copywriting_shares` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `copywriting_id` CHAR(26) NOT NULL,
    `user_id` CHAR(26) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_copywriting_id` (`copywriting_id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `copywriting_shares_copywriting_id_foreign` 
        FOREIGN KEY (`copywriting_id`) REFERENCES `copywritings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `copywriting_shares_user_id_foreign` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `adtemplate_shares` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_ad_template_id` CHAR(26) NOT NULL,
    `user_id` CHAR(26) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_fb_ad_template_id` (`fb_ad_template_id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `adtemplate_shares_fb_ad_template_id_foreign` 
        FOREIGN KEY (`fb_ad_template_id`) REFERENCES `fb_ad_templates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `adtemplate_shares_user_id_foreign` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. 规则和自动化表
-- ============================================

-- 8.1 规则表
CREATE TABLE IF NOT EXISTS `rules` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `user_id` CHAR(26) NULL,
    `ad_status` VARCHAR(255) NULL,
    `resource_ids` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.2 规则关联表（多态）
CREATE TABLE IF NOT EXISTS `ruleables` (
    `rule_id` CHAR(26) NOT NULL,
    `ruleable_type` VARCHAR(255) NOT NULL,
    `ruleable_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`rule_id`, `ruleable_id`, `ruleable_type`),
    INDEX `idx_ruleable` (`ruleable_id`, `ruleable_type`),
    CONSTRAINT `ruleables_rule_id_foreign` 
        FOREIGN KEY (`rule_id`) REFERENCES `rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.3 定时任务表
CREATE TABLE IF NOT EXISTS `cron_jobs` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `object_type` VARCHAR(255) NULL,
    `object_value` JSON NULL,
    `start_time` TIME NULL,
    `stop_time` TIME NULL,
    `timezone` VARCHAR(255) NULL,
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    `user_id` CHAR(26) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. 虚拟卡片相关表
-- ============================================

-- 9.1 卡片提供商表
CREATE TABLE IF NOT EXISTS `card_providers` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `nick_name` VARCHAR(255) NULL,
    `type` VARCHAR(255) NOT NULL,
    `api_endpoint` TEXT NULL,
    `api_key` TEXT NULL,
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9.2 卡片表
CREATE TABLE IF NOT EXISTS `cards` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `number` VARCHAR(255) NOT NULL UNIQUE,
    `source_id` VARCHAR(255) NOT NULL,
    `card_provider_id` CHAR(26) NULL,
    `status` VARCHAR(255) NOT NULL,
    `currency` VARCHAR(10) NULL,
    `balance` DECIMAL(10, 2) NULL,
    `total_limit` DECIMAL(10, 2) NULL,
    `single_trans_limit` DECIMAL(10, 2) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_card_provider_id` (`card_provider_id`),
    INDEX `idx_number` (`number`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `cards_card_provider_id_foreign` 
        FOREIGN KEY (`card_provider_id`) REFERENCES `card_providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9.3 卡片交易表
CREATE TABLE IF NOT EXISTS `card_transactions` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `card_id` CHAR(26) NOT NULL,
    `transaction_id` VARCHAR(255) NOT NULL,
    `transaction_date` TIMESTAMP NOT NULL,
    `transaction_amount` DECIMAL(10, 2) NOT NULL,
    `status` VARCHAR(255) NOT NULL,
    `merchant_name` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_card_id` (`card_id`),
    INDEX `idx_transaction_date` (`transaction_date`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `card_transactions_card_id_foreign` 
        FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9.4 卡片 BIN 表
CREATE TABLE IF NOT EXISTS `card_bins` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `card_provider_id` CHAR(26) NOT NULL,
    `bin` VARCHAR(20) NOT NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_card_provider_id` (`card_provider_id`),
    INDEX `idx_bin` (`bin`),
    CONSTRAINT `card_bins_card_provider_id_foreign` 
        FOREIGN KEY (`card_provider_id`) REFERENCES `card_providers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9.5 卡片-广告账户关联表
CREATE TABLE IF NOT EXISTS `card_fb_ad_account` (
    `card_id` CHAR(26) NOT NULL,
    `fb_ad_account_id` CHAR(26) NOT NULL,
    `is_default` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`card_id`, `fb_ad_account_id`),
    INDEX `idx_fb_ad_account_id` (`fb_ad_account_id`),
    CONSTRAINT `card_fb_ad_account_card_id_foreign` 
        FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE,
    CONSTRAINT `card_fb_ad_account_fb_ad_account_id_foreign` 
        FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. 代理和浏览器相关表
-- ============================================

-- 10.1 代理表
CREATE TABLE IF NOT EXISTS `proxies` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `host` VARCHAR(255) NOT NULL,
    `port` INT NOT NULL,
    `username` VARCHAR(255) NULL,
    `password` VARCHAR(255) NULL,
    `user_id` CHAR(26) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10.2 代理表
CREATE TABLE IF NOT EXISTS `agents` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `user_id` CHAR(26) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10.3 指纹浏览器组表
CREATE TABLE IF NOT EXISTS `finger_browser_groups` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `group_id` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10.4 指纹浏览器表
CREATE TABLE IF NOT EXISTS `finger_browsers` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `finger_browser_group_id` CHAR(26) NULL,
    `agent_id` CHAR(26) NULL,
    `user_id` CHAR(26) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_finger_browser_group_id` (`finger_browser_group_id`),
    INDEX `idx_agent_id` (`agent_id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `finger_browsers_finger_browser_group_id_foreign` 
        FOREIGN KEY (`finger_browser_group_id`) REFERENCES `finger_browser_groups` (`id`) ON DELETE SET NULL,
    CONSTRAINT `finger_browsers_agent_id_foreign` 
        FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. 其他功能表
-- ============================================

-- 11.1 Cloudflare 表
CREATE TABLE IF NOT EXISTS `cloudflares` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `kv_pairs` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11.2 广告日志表
CREATE TABLE IF NOT EXISTS `ad_logs` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `fb_ad_id` CHAR(26) NULL,
    `fb_adset_id` CHAR(26) NULL,
    `fb_campaign_id` CHAR(26) NULL,
    `material_id` CHAR(26) NULL,
    `is_success` BOOLEAN NOT NULL DEFAULT FALSE,
    `message` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `idx_fb_ad_id` (`fb_ad_id`),
    INDEX `idx_fb_adset_id` (`fb_adset_id`),
    INDEX `idx_fb_campaign_id` (`fb_campaign_id`),
    INDEX `idx_material_id` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11.3 广告日志关联表
CREATE TABLE IF NOT EXISTS `adlog_campaign` (
    `ad_log_id` CHAR(26) NOT NULL,
    `fb_campaign_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`ad_log_id`, `fb_campaign_id`),
    CONSTRAINT `adlog_campaign_ad_log_id_foreign` 
        FOREIGN KEY (`ad_log_id`) REFERENCES `ad_logs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `adlog_campaign_fb_campaign_id_foreign` 
        FOREIGN KEY (`fb_campaign_id`) REFERENCES `fb_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `adlog_adset` (
    `ad_log_id` CHAR(26) NOT NULL,
    `fb_adset_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`ad_log_id`, `fb_adset_id`),
    CONSTRAINT `adlog_adset_ad_log_id_foreign` 
        FOREIGN KEY (`ad_log_id`) REFERENCES `ad_logs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `adlog_adset_fb_adset_id_foreign` 
        FOREIGN KEY (`fb_adset_id`) REFERENCES `fb_adsets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `adlog_ad` (
    `ad_log_id` CHAR(26) NOT NULL,
    `fb_ad_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`ad_log_id`, `fb_ad_id`),
    CONSTRAINT `adlog_ad_ad_log_id_foreign` 
        FOREIGN KEY (`ad_log_id`) REFERENCES `ad_logs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `adlog_ad_fb_ad_id_foreign` 
        FOREIGN KEY (`fb_ad_id`) REFERENCES `fb_ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `adlog_material` (
    `ad_log_id` CHAR(26) NOT NULL,
    `material_id` CHAR(26) NOT NULL,
    PRIMARY KEY (`ad_log_id`, `material_id`),
    CONSTRAINT `adlog_material_ad_log_id_foreign` 
        FOREIGN KEY (`ad_log_id`) REFERENCES `ad_logs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `adlog_material_material_id_foreign` 
        FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11.4 防盗刷配置表
CREATE TABLE IF NOT EXISTS `fraud_configs` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(255) NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `config` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11.5 搜索书签表
CREATE TABLE IF NOT EXISTS `search_bookmarks` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `name` VARCHAR(255) NOT NULL,
    `user_id` CHAR(26) NOT NULL,
    `bookmark_data` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `search_bookmarks_user_id_foreign` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11.6 请求日志表
CREATE TABLE IF NOT EXISTS `request_logs` (
    `id` CHAR(26) NOT NULL PRIMARY KEY COMMENT 'ULID',
    `method` VARCHAR(10) NOT NULL,
    `path` TEXT NOT NULL,
    `ip` VARCHAR(45) NULL,
    `user_id` CHAR(26) NULL,
    `request_data` JSON NULL,
    `response_status` INT NULL,
    `response_data` JSON NULL,
    `created_at` TIMESTAMP NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. 迁移记录表（Laravel 必需）
-- ============================================

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 使用说明
-- ============================================
-- 1. 此脚本包含租户数据库需要的所有业务表
-- 2. 执行前请确保已创建租户数据库
-- 3. 执行方式：
--    USE `your_tenant_database_name`;
--    然后执行此脚本
-- 4. 或者使用 Laravel 迁移：
--    php artisan tenant:migrate {tenant_uuid}
-- 5. 注意：personal_access_tokens 表在主数据库中，不在租户数据库

