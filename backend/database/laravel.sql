/*
 Navicat Premium Dump SQL

 Source Server         : 47.251.71.111
 Source Server Type    : MySQL
 Source Server Version : 100527 (10.5.27-MariaDB-log)
 Source Host           : localhost:3306
 Source Schema         : laravel

 Target Server Type    : MySQL
 Target Server Version : 100527 (10.5.27-MariaDB-log)
 File Encoding         : 65001

 Date: 23/03/2026 16:52:44
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ad_logs
-- ----------------------------
DROP TABLE IF EXISTS `ad_logs`;
CREATE TABLE `ad_logs`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告账户ID',
  `fb_ad_template_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告模板ID',
  `fb_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Facebook账户ID',
  `fb_api_token_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'API Token ID',
  `fb_pixel_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '像素ID',
  `fb_page_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '主页ID',
  `fb_page_form_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '主页表单ID',
  `material_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '素材ID',
  `copywriting_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '文案ID',
  `link_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '链接ID',
  `operator_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '操作类型',
  `launch_mode` int NULL DEFAULT NULL COMMENT '启动模式',
  `post_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '帖子源ID',
  `is_success` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '是否成功',
  `failed_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '失败原因',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告日志表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ad_logs
-- ----------------------------

-- ----------------------------
-- Table structure for adlog_ad
-- ----------------------------
DROP TABLE IF EXISTS `adlog_ad`;
CREATE TABLE `adlog_ad`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `adlog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告日志ID',
  `ad_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告源ID',
  `ad_created` tinyint(1) NULL DEFAULT NULL COMMENT '是否创建成功',
  `ad_failed_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '失败原因',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告日志-广告关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of adlog_ad
-- ----------------------------

-- ----------------------------
-- Table structure for adlog_adset
-- ----------------------------
DROP TABLE IF EXISTS `adlog_adset`;
CREATE TABLE `adlog_adset`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `adlog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告日志ID',
  `adset_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告组源ID',
  `adset_created` tinyint(1) NULL DEFAULT NULL COMMENT '是否创建成功',
  `adset_failed_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '失败原因',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告日志-广告组关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of adlog_adset
-- ----------------------------

-- ----------------------------
-- Table structure for adlog_campaign
-- ----------------------------
DROP TABLE IF EXISTS `adlog_campaign`;
CREATE TABLE `adlog_campaign`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `adlog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告日志ID',
  `campaign_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告系列源ID',
  `campaign_created` tinyint(1) NULL DEFAULT NULL COMMENT '是否创建成功',
  `campaign_failed_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '失败原因',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告日志-广告系列关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of adlog_campaign
-- ----------------------------

-- ----------------------------
-- Table structure for adlog_material
-- ----------------------------
DROP TABLE IF EXISTS `adlog_material`;
CREATE TABLE `adlog_material`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `adlog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告日志ID',
  `material_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '素材ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告日志-素材关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of adlog_material
-- ----------------------------

-- ----------------------------
-- Table structure for adtemplate_shares
-- ----------------------------
DROP TABLE IF EXISTS `adtemplate_shares`;
CREATE TABLE `adtemplate_shares`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `adtemplate_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告模板ID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `adtemplate_shares_adtemplate_id_foreign`(`adtemplate_id` ASC) USING BTREE,
  INDEX `adtemplate_shares_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `adtemplate_shares_adtemplate_id_foreign` FOREIGN KEY (`adtemplate_id`) REFERENCES `fb_ad_templates` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `adtemplate_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告模板分享表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of adtemplate_shares
-- ----------------------------

-- ----------------------------
-- Table structure for agents
-- ----------------------------
DROP TABLE IF EXISTS `agents`;
CREATE TABLE `agents`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '代理商表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of agents
-- ----------------------------

-- ----------------------------
-- Table structure for card_bins
-- ----------------------------
DROP TABLE IF EXISTS `card_bins`;
CREATE TABLE `card_bins`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `card_provider_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡片提供商ID',
  `card_bin` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡号前缀(6-10位)',
  `card_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'virtual' COMMENT '卡类型',
  `active` tinyint(1) NULL DEFAULT 1 COMMENT '是否启用',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `card_bins_provider_bin_unique`(`card_provider_id` ASC, `card_bin` ASC) USING BTREE,
  CONSTRAINT `card_bins_card_provider_id_foreign` FOREIGN KEY (`card_provider_id`) REFERENCES `card_providers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '卡片BIN表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of card_bins
-- ----------------------------

-- ----------------------------
-- Table structure for card_fb_ad_account
-- ----------------------------
DROP TABLE IF EXISTS `card_fb_ad_account`;
CREATE TABLE `card_fb_ad_account`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡片ID',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告账户ID',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认卡片',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `card_fb_ad_account_unique`(`card_id` ASC, `fb_ad_account_id` ASC) USING BTREE,
  INDEX `card_fb_ad_account_card_id_index`(`card_id` ASC) USING BTREE,
  INDEX `card_fb_ad_account_fb_ad_account_id_index`(`fb_ad_account_id` ASC) USING BTREE,
  INDEX `card_fb_ad_account_is_default_index`(`is_default` ASC) USING BTREE,
  CONSTRAINT `card_fb_ad_account_card_id_foreign` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `card_fb_ad_account_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '卡片-广告账户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of card_fb_ad_account
-- ----------------------------

-- ----------------------------
-- Table structure for card_providers
-- ----------------------------
DROP TABLE IF EXISTS `card_providers`;
CREATE TABLE `card_providers`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内部使用的provider名称',
  `nick_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '前端显示的provider昵称',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'provider配置信息',
  `active` tinyint(1) NULL DEFAULT 1 COMMENT '是否启用',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `name`(`name` ASC) USING BTREE,
  UNIQUE INDEX `nick_name`(`nick_name` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '卡片提供商表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of card_providers
-- ----------------------------

-- ----------------------------
-- Table structure for card_transactions
-- ----------------------------
DROP TABLE IF EXISTS `card_transactions`;
CREATE TABLE `card_transactions`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `card_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡片ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '交易源ID',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '状态',
  `transaction_amount` float NOT NULL COMMENT '交易金额',
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '货币',
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP COMMENT '交易日期',
  `transaction_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '交易类型',
  `merchant_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '商户名称',
  `custom_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '自定义字段1',
  `posted_date` timestamp NULL DEFAULT NULL COMMENT '入账日期',
  `failure_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '失败原因',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '卡片交易表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of card_transactions
-- ----------------------------

-- ----------------------------
-- Table structure for cards
-- ----------------------------
DROP TABLE IF EXISTS `cards`;
CREATE TABLE `cards`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `card_provider_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '卡片提供商ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡片源ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '卡片名称',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '状态',
  `balance` float NOT NULL COMMENT '余额',
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '卡号',
  `cvv` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CVV',
  `expiration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '过期日期',
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '货币',
  `limits` float NULL DEFAULT NULL COMMENT '限额',
  `single_transaction_limit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '单笔交易限额',
  `applied_at` datetime NULL DEFAULT NULL COMMENT '申请时间',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `cards_card_provider_id_foreign`(`card_provider_id` ASC) USING BTREE,
  CONSTRAINT `cards_card_provider_id_foreign` FOREIGN KEY (`card_provider_id`) REFERENCES `card_providers` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '卡片表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cards
-- ----------------------------

-- ----------------------------
-- Table structure for clicks
-- ----------------------------
DROP TABLE IF EXISTS `clicks`;
CREATE TABLE `clicks`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '交易ID',
  `click_datetime` timestamp NULL DEFAULT NULL COMMENT '点击时间',
  `network_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '网络ID',
  `offer_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Offer源ID',
  `offer_source_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Offer源名称',
  `sub_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub1',
  `sub_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub2',
  `sub_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub3',
  `sub_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub4',
  `sub_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub5',
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `fb_campaign_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告系列ID',
  `fb_adset_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告组ID',
  `fb_ad_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告ID',
  `fb_pixel_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB像素号',
  `aff_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '联盟ID',
  `identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标识符',
  `country_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '国家代码',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `clicks_fb_campaign_source_id_index`(`fb_campaign_source_id` ASC) USING BTREE,
  INDEX `clicks_fb_adset_source_id_index`(`fb_adset_source_id` ASC) USING BTREE,
  INDEX `clicks_fb_ad_source_id_index`(`fb_ad_source_id` ASC) USING BTREE,
  INDEX `clicks_click_datetime_index`(`click_datetime` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '点击数据表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of clicks
-- ----------------------------

-- ----------------------------
-- Table structure for cloudflares
-- ----------------------------
DROP TABLE IF EXISTS `cloudflares`;
CREATE TABLE `cloudflares`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮箱',
  `account_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '账户ID',
  `api_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'API Token',
  `kv_namespace_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'KV命名空间ID',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Cloudflare配置表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cloudflares
-- ----------------------------

-- ----------------------------
-- Table structure for conversions
-- ----------------------------
DROP TABLE IF EXISTS `conversions`;
CREATE TABLE `conversions`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '交易ID',
  `conversion_datetime` timestamp NULL DEFAULT NULL COMMENT '转化时间',
  `network_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '网络ID',
  `offer_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Offer源ID',
  `offer_source_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Offer源名称',
  `sub_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub1',
  `sub_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub2',
  `sub_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub3',
  `sub_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub4',
  `sub_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub5',
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `fb_campaign_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告系列ID',
  `fb_adset_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告组ID',
  `fb_ad_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告ID',
  `fb_pixel_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB像素号',
  `price` float NULL DEFAULT NULL COMMENT '价格',
  `aff_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '联盟ID',
  `identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标识符',
  `country_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '国家代码',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `conversions_fb_campaign_source_id_index`(`fb_campaign_source_id` ASC) USING BTREE,
  INDEX `conversions_fb_adset_source_id_index`(`fb_adset_source_id` ASC) USING BTREE,
  INDEX `conversions_fb_ad_source_id_index`(`fb_ad_source_id` ASC) USING BTREE,
  INDEX `conversions_conversion_datetime_index`(`conversion_datetime` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '转化数据表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of conversions
-- ----------------------------

-- ----------------------------
-- Table structure for copywriting_shares
-- ----------------------------
DROP TABLE IF EXISTS `copywriting_shares`;
CREATE TABLE `copywriting_shares`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `copywriting_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文案ID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `copywriting_shares_copywriting_id_foreign`(`copywriting_id` ASC) USING BTREE,
  INDEX `copywriting_shares_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `copywriting_shares_copywriting_id_foreign` FOREIGN KEY (`copywriting_id`) REFERENCES `copywritings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `copywriting_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '文案分享表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of copywriting_shares
-- ----------------------------

-- ----------------------------
-- Table structure for copywritings
-- ----------------------------
DROP TABLE IF EXISTS `copywritings`;
CREATE TABLE `copywritings`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `primary_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '主文案',
  `headline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标题',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '描述',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '文案表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of copywritings
-- ----------------------------

-- ----------------------------
-- Table structure for cron_jobs
-- ----------------------------
DROP TABLE IF EXISTS `cron_jobs`;
CREATE TABLE `cron_jobs`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务名称',
  `object_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '对象类型',
  `object_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '对象值',
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '时区',
  `start_time` time NULL DEFAULT NULL COMMENT '开始时间',
  `stop_time` time NULL DEFAULT NULL COMMENT '结束时间',
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `active` tinyint(1) NULL DEFAULT NULL COMMENT '是否激活',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '定时任务表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cron_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID',
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '连接',
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '队列',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '负载',
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '异常信息',
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '失败时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uuid`(`uuid` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '失败任务表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for fb_account_fb_ad_account
-- ----------------------------
DROP TABLE IF EXISTS `fb_account_fb_ad_account`;
CREATE TABLE `fb_account_fb_ad_account`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告账户ID',
  `fb_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Facebook账户ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '源ID',
  `relation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '关系类型',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_account_fb_ad_account_fb_ad_account_id_foreign`(`fb_ad_account_id` ASC) USING BTREE,
  INDEX `fb_account_fb_ad_account_fb_account_id_foreign`(`fb_account_id` ASC) USING BTREE,
  CONSTRAINT `fb_account_fb_ad_account_fb_account_id_foreign` FOREIGN KEY (`fb_account_id`) REFERENCES `fb_accounts` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fb_account_fb_ad_account_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Facebook账户-广告账户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_account_fb_ad_account
-- ----------------------------

-- ----------------------------
-- Table structure for fb_account_page
-- ----------------------------
DROP TABLE IF EXISTS `fb_account_page`;
CREATE TABLE `fb_account_page`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook账户ID',
  `fb_page_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主页ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '源ID',
  `source_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '源名称',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '任务权限',
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '角色',
  `role_human` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '角色描述',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否激活',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `fb_account_page_unique`(`fb_account_id` ASC, `fb_page_id` ASC) USING BTREE,
  INDEX `fb_account_page_fb_page_id_foreign`(`fb_page_id` ASC) USING BTREE,
  CONSTRAINT `fb_account_page_fb_account_id_foreign` FOREIGN KEY (`fb_account_id`) REFERENCES `fb_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_account_page_fb_page_id_foreign` FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Facebook账户-主页关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_account_page
-- ----------------------------

-- ----------------------------
-- Table structure for fb_account_user
-- ----------------------------
DROP TABLE IF EXISTS `fb_account_user`;
CREATE TABLE `fb_account_user`  (
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `fb_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook账户ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`, `fb_account_id`) USING BTREE,
  INDEX `fb_account_user_fb_account_id_foreign`(`fb_account_id` ASC) USING BTREE,
  CONSTRAINT `fb_account_user_fb_account_id_foreign` FOREIGN KEY (`fb_account_id`) REFERENCES `fb_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_account_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户-Facebook账户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_account_user
-- ----------------------------

-- ----------------------------
-- Table structure for fb_accounts
-- ----------------------------
DROP TABLE IF EXISTS `fb_accounts`;
CREATE TABLE `fb_accounts`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '所属用户ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Facebook账户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '账户名称',
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '名',
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '姓',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '密码',
  `gender` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '性别',
  `picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '头像',
  `twofa_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '2FA密钥',
  `cookies` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Cookies',
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Access Token',
  `token_valid` tinyint(1) NULL DEFAULT 0 COMMENT 'Token是否有效',
  `useragent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'User Agent',
  `fingerbrowser_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '指纹浏览器ID',
  `proxy_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '代理ID',
  `is_archived` tinyint(1) NULL DEFAULT 0 COMMENT '是否归档',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_accounts_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `fb_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Facebook账户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_accounts
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ad_account_fb_bm
-- ----------------------------
DROP TABLE IF EXISTS `fb_ad_account_fb_bm`;
CREATE TABLE `fb_ad_account_fb_bm`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告账户ID',
  `fb_bm_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BM ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '源ID',
  `relation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '关系类型(Owner/Partner)',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '任务权限',
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '角色',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_ad_account_fb_bm_fb_ad_account_id_foreign`(`fb_ad_account_id` ASC) USING BTREE,
  INDEX `fb_ad_account_fb_bm_fb_bm_id_foreign`(`fb_bm_id` ASC) USING BTREE,
  CONSTRAINT `fb_ad_account_fb_bm_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fb_ad_account_fb_bm_fb_bm_id_foreign` FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告账户-BM关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ad_account_fb_bm
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ad_account_fb_business_user
-- ----------------------------
DROP TABLE IF EXISTS `fb_ad_account_fb_business_user`;
CREATE TABLE `fb_ad_account_fb_business_user`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告账户ID',
  `fb_business_user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '业务用户ID',
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '任务权限',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_ad_account_fb_business_user_fb_ad_account_id_foreign`(`fb_ad_account_id` ASC) USING BTREE,
  INDEX `fb_ad_account_fb_business_user_fb_business_user_id_foreign`(`fb_business_user_id` ASC) USING BTREE,
  CONSTRAINT `fb_ad_account_fb_business_user_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_ad_account_fb_business_user_fb_business_user_id_foreign` FOREIGN KEY (`fb_business_user_id`) REFERENCES `fb_business_users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告账户-业务用户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ad_account_fb_business_user
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ad_account_insights
-- ----------------------------
DROP TABLE IF EXISTS `fb_ad_account_insights`;
CREATE TABLE `fb_ad_account_insights`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `account_currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户货币',
  `account_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户ID',
  `account_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户名称',
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '操作',
  `action_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '操作值',
  `clicks` bigint NULL DEFAULT NULL COMMENT '点击数',
  `cost_per_action_type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '每操作成本',
  `cost_per_inline_link_click` float NULL DEFAULT NULL COMMENT '每内联链接点击成本',
  `cpc` float NULL DEFAULT NULL COMMENT 'CPC',
  `cpm` float NULL DEFAULT NULL COMMENT 'CPM',
  `ctr` float NULL DEFAULT NULL COMMENT 'CTR',
  `date_start` date NOT NULL COMMENT '开始日期',
  `date_stop` date NOT NULL COMMENT '结束日期',
  `frequency` float NULL DEFAULT NULL COMMENT '频率',
  `impressions` bigint NULL DEFAULT NULL COMMENT '展示数',
  `inline_link_click_ctr` float NULL DEFAULT NULL COMMENT '内联链接点击率',
  `inline_link_clicks` bigint NULL DEFAULT NULL COMMENT '内联链接点击数',
  `objective` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '目标',
  `purchase_roas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '购买ROAS',
  `purchase_roas_value` float NULL DEFAULT NULL COMMENT '购买ROAS值',
  `quality_ranking` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '质量排名',
  `reach` bigint NULL DEFAULT NULL COMMENT '触达数',
  `spend` float NULL DEFAULT NULL COMMENT '花费',
  `purchase` int NULL DEFAULT NULL COMMENT '购买数',
  `purchase_value` float NULL DEFAULT NULL COMMENT '购买价值',
  `cost_per_purchase` float NULL DEFAULT NULL COMMENT '每购买成本',
  `lead` int NULL DEFAULT NULL COMMENT '线索数',
  `cost_per_lead` float NULL DEFAULT NULL COMMENT '每线索成本',
  `add_to_cart` int NULL DEFAULT NULL COMMENT '加购数',
  `cost_to_add_to_cart` float NULL DEFAULT NULL COMMENT '每加购成本',
  `comment` int NULL DEFAULT NULL COMMENT '评论数',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_ad_account_insights_account_id_index`(`account_id` ASC) USING BTREE,
  INDEX `fb_ad_account_insights_date_start_index`(`date_start` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告账户洞察表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ad_account_insights
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ad_account_user
-- ----------------------------
DROP TABLE IF EXISTS `fb_ad_account_user`;
CREATE TABLE `fb_ad_account_user`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告账户ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_ad_account_user_user_id_foreign`(`user_id` ASC) USING BTREE,
  INDEX `fb_ad_account_user_fb_ad_account_id_foreign`(`fb_ad_account_id` ASC) USING BTREE,
  CONSTRAINT `fb_ad_account_user_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_ad_account_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户-广告账户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ad_account_user
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ad_accounts
-- ----------------------------
DROP TABLE IF EXISTS `fb_ad_accounts`;
CREATE TABLE `fb_ad_accounts`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook广告账户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户名称',
  `adtrust_dsl` float NULL DEFAULT NULL COMMENT '广告信任度',
  `account_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户状态',
  `account_status_code` int NOT NULL COMMENT '账户状态码',
  `adspaymentcycle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '支付周期',
  `age` float NULL DEFAULT NULL COMMENT '账户年龄',
  `total_spent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '总花费',
  `balance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '余额',
  `amount_spent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '已花费金额',
  `assigned_partners` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '分配的合作伙伴',
  `business` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '关联业务',
  `spend_cap` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '消费上限',
  `business_restriction_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '业务限制原因',
  `created_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '货币',
  `current_unbilled_spend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '当前未结算花费',
  `disable_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '禁用原因',
  `disable_reason_code` int NULL DEFAULT NULL COMMENT '禁用原因码',
  `max_billing_threshold` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '最大账单阈值',
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '所有者',
  `is_orignal` tinyint(1) NULL DEFAULT NULL COMMENT '是否原始账户',
  `timezone_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '时区ID',
  `timezone_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '时区名称',
  `enable_rule` tinyint(1) NULL DEFAULT 0 COMMENT '是否启用规则',
  `is_prepay_account` tinyint(1) NULL DEFAULT NULL COMMENT '是否预付账户',
  `fixed_balance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '固定余额',
  `is_archived` tinyint(1) NULL DEFAULT 0 COMMENT '是否归档',
  `original_adtrust_dsl` int NULL DEFAULT NULL COMMENT '原始广告信任度',
  `original_balance` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '原始余额',
  `original_amount_spent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '原始已花费金额',
  `original_spend_cap` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '原始消费上限',
  `auto_sync` tinyint(1) NULL DEFAULT 1 COMMENT '是否自动同步',
  `default_funding` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '默认资金来源',
  `funding_type` int NULL DEFAULT NULL COMMENT '资金类型',
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'FB API过滤器',
  `is_topup` tinyint(1) NULL DEFAULT 0 COMMENT '是否充值账户',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告账户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ad_accounts
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ad_fb_page
-- ----------------------------
DROP TABLE IF EXISTS `fb_ad_fb_page`;
CREATE TABLE `fb_ad_fb_page`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_ad_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告ID',
  `fb_page_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '主页ID',
  `fb_page_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '主页源ID(如果主页不在系统中)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_ad_fb_page_fb_ad_id_foreign`(`fb_ad_id` ASC) USING BTREE,
  INDEX `fb_ad_fb_page_fb_page_id_foreign`(`fb_page_id` ASC) USING BTREE,
  CONSTRAINT `fb_ad_fb_page_fb_ad_id_foreign` FOREIGN KEY (`fb_ad_id`) REFERENCES `fb_ads` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_ad_fb_page_fb_page_id_foreign` FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告-主页关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ad_fb_page
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ad_insights
-- ----------------------------
DROP TABLE IF EXISTS `fb_ad_insights`;
CREATE TABLE `fb_ad_insights`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `account_currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户货币',
  `account_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户ID',
  `account_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户名称',
  `campaign_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列ID',
  `campaign_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '系列名称',
  `adset_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告组ID',
  `adset_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告组名称',
  `ad_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告ID',
  `ad_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告名称',
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '操作',
  `action_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '操作值',
  `clicks` bigint NULL DEFAULT NULL COMMENT '点击数',
  `cost_per_action_type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '每操作成本',
  `cost_per_inline_link_click` float NULL DEFAULT NULL COMMENT '每内联链接点击成本',
  `cpc` float NULL DEFAULT NULL COMMENT 'CPC',
  `cpm` float NULL DEFAULT NULL COMMENT 'CPM',
  `ctr` float NULL DEFAULT NULL COMMENT 'CTR',
  `date_start` date NOT NULL COMMENT '开始日期',
  `date_stop` date NOT NULL COMMENT '结束日期',
  `frequency` float NULL DEFAULT NULL COMMENT '频率',
  `impressions` bigint NULL DEFAULT NULL COMMENT '展示数',
  `inline_link_click_ctr` float NULL DEFAULT NULL COMMENT '内联链接点击率',
  `inline_link_clicks` bigint NULL DEFAULT NULL COMMENT '内联链接点击数',
  `objective` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '目标',
  `purchase_roas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '购买ROAS',
  `purchase_roas_value` float NULL DEFAULT NULL COMMENT '购买ROAS值',
  `quality_ranking` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '质量排名',
  `reach` bigint NULL DEFAULT NULL COMMENT '触达数',
  `spend` float NULL DEFAULT NULL COMMENT '花费',
  `purchase` int NULL DEFAULT NULL COMMENT '购买数',
  `purchase_value` float NULL DEFAULT NULL COMMENT '购买价值',
  `cost_per_purchase` float NULL DEFAULT NULL COMMENT '每购买成本',
  `lead` int NULL DEFAULT NULL COMMENT '线索数',
  `cost_per_lead` float NULL DEFAULT NULL COMMENT '每线索成本',
  `add_to_cart` int NULL DEFAULT NULL COMMENT '加购数',
  `cost_to_add_to_cart` float NULL DEFAULT NULL COMMENT '每加购成本',
  `comment` int NULL DEFAULT NULL COMMENT '评论数',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_ad_insights_ad_id_index`(`ad_id` ASC) USING BTREE,
  INDEX `fb_ad_insights_date_start_index`(`date_start` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告洞察表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ad_insights
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ad_templates
-- ----------------------------
DROP TABLE IF EXISTS `fb_ad_templates`;
CREATE TABLE `fb_ad_templates`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板名称',
  `campaign_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '广告系列名称模板',
  `adset_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '广告组名称模板',
  `ad_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '广告名称模板',
  `bid_strategy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '出价策略',
  `bid_amount` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '出价金额',
  `budget_level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '预算级别',
  `budget_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '预算类型',
  `budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '预算',
  `objective` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '目标',
  `accelerated` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '加速投放',
  `conversion_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '转化位置',
  `optimization_goal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '优化目标',
  `pixel_event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '像素事件',
  `advantage_plus_audience` tinyint(1) NULL DEFAULT NULL COMMENT 'Advantage+受众',
  `genders` int NULL DEFAULT NULL COMMENT '性别',
  `age_min` int NULL DEFAULT NULL COMMENT '最小年龄',
  `age_max` int NULL DEFAULT NULL COMMENT '最大年龄',
  `primary_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '主文案',
  `headline_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '标题文案',
  `description_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '描述文案',
  `countries_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '包含国家',
  `countries_excluded` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '排除国家',
  `regions_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '包含地区',
  `regions_excluded` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '排除地区',
  `cities_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '包含城市',
  `cities_excluded` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '排除城市',
  `locales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '语言',
  `interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '兴趣',
  `publisher_platforms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '发布平台',
  `device_platforms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '设备平台',
  `wireless_carrier` tinyint(1) NULL DEFAULT NULL COMMENT '无线运营商',
  `call_to_action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '行动号召',
  `url_params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'URL参数',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告模板表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ad_templates
-- ----------------------------

-- ----------------------------
-- Table structure for fb_ads
-- ----------------------------
DROP TABLE IF EXISTS `fb_ads`;
CREATE TABLE `fb_ads`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_campaign_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告系列ID',
  `fb_adset_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告组ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook广告ID',
  `adset_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告组ID',
  `campaign_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告名称',
  `configured_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置状态',
  `effective_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '有效状态',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '状态',
  `creative` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '创意内容',
  `preview_shareable_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '预览分享链接',
  `source_ad_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '源广告ID',
  `post_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '帖子URL',
  `auto_add_languages` tinyint(1) NULL DEFAULT 0 COMMENT '是否自动添加多语言',
  `created_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `is_deleted_on_fb` tinyint(1) NULL DEFAULT 0 COMMENT '是否在FB上已删除',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_ads_source_id_index`(`source_id` ASC) USING BTREE,
  INDEX `fb_ads_fb_campaign_id_index`(`fb_campaign_id` ASC) USING BTREE,
  INDEX `fb_ads_fb_adset_id_index`(`fb_adset_id` ASC) USING BTREE,
  CONSTRAINT `fb_ads_fb_adset_id_foreign` FOREIGN KEY (`fb_adset_id`) REFERENCES `fb_adsets` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_ads_fb_campaign_id_foreign` FOREIGN KEY (`fb_campaign_id`) REFERENCES `fb_campaigns` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_ads
-- ----------------------------

-- ----------------------------
-- Table structure for fb_adset_insights
-- ----------------------------
DROP TABLE IF EXISTS `fb_adset_insights`;
CREATE TABLE `fb_adset_insights`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `account_currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户货币',
  `account_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户ID',
  `account_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户名称',
  `campaign_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列ID',
  `campaign_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '系列名称',
  `adset_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告组ID',
  `adset_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告组名称',
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '操作',
  `action_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '操作值',
  `clicks` bigint NULL DEFAULT NULL COMMENT '点击数',
  `cost_per_action_type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '每操作成本',
  `cost_per_inline_link_click` float NULL DEFAULT NULL COMMENT '每内联链接点击成本',
  `cpc` float NULL DEFAULT NULL COMMENT 'CPC',
  `cpm` float NULL DEFAULT NULL COMMENT 'CPM',
  `ctr` float NULL DEFAULT NULL COMMENT 'CTR',
  `date_start` date NOT NULL COMMENT '开始日期',
  `date_stop` date NOT NULL COMMENT '结束日期',
  `frequency` float NULL DEFAULT NULL COMMENT '频率',
  `impressions` bigint NULL DEFAULT NULL COMMENT '展示数',
  `inline_link_click_ctr` float NULL DEFAULT NULL COMMENT '内联链接点击率',
  `inline_link_clicks` bigint NULL DEFAULT NULL COMMENT '内联链接点击数',
  `objective` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '目标',
  `purchase_roas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '购买ROAS',
  `purchase_roas_value` float NULL DEFAULT NULL COMMENT '购买ROAS值',
  `quality_ranking` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '质量排名',
  `reach` bigint NULL DEFAULT NULL COMMENT '触达数',
  `spend` float NULL DEFAULT NULL COMMENT '花费',
  `purchase` int NULL DEFAULT NULL COMMENT '购买数',
  `purchase_value` float NULL DEFAULT NULL COMMENT '购买价值',
  `cost_per_purchase` float NULL DEFAULT NULL COMMENT '每购买成本',
  `lead` int NULL DEFAULT NULL COMMENT '线索数',
  `cost_per_lead` float NULL DEFAULT NULL COMMENT '每线索成本',
  `add_to_cart` int NULL DEFAULT NULL COMMENT '加购数',
  `cost_to_add_to_cart` float NULL DEFAULT NULL COMMENT '每加购成本',
  `comment` int NULL DEFAULT NULL COMMENT '评论数',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_adset_insights_adset_id_index`(`adset_id` ASC) USING BTREE,
  INDEX `fb_adset_insights_date_start_index`(`date_start` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告组洞察表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_adset_insights
-- ----------------------------

-- ----------------------------
-- Table structure for fb_adsets
-- ----------------------------
DROP TABLE IF EXISTS `fb_adsets`;
CREATE TABLE `fb_adsets`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_campaign_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告系列ID',
  `pixel_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '像素ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook广告组ID',
  `account_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户ID',
  `campaign_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告组名称',
  `billing_event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '计费事件',
  `optimization_goal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '优化目标',
  `bid_strategy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '出价策略',
  `bid_amount` float NULL DEFAULT NULL COMMENT '出价金额',
  `original_bid_amount` float NULL DEFAULT NULL COMMENT '原始出价金额',
  `budget_remaining` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '剩余预算',
  `daily_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '日预算',
  `lifetime_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '总预算',
  `original_daily_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '原始日预算',
  `original_lifetime_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '原始总预算',
  `configured_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置状态',
  `effective_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '有效状态',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '状态',
  `is_dynamic_creative` tinyint(1) NOT NULL COMMENT '是否动态创意',
  `promoted_object` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '推广对象',
  `targeting` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '定向设置',
  `source_adset_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '源广告组ID',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '开始时间',
  `created_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `is_deleted_on_fb` tinyint(1) NULL DEFAULT 0 COMMENT '是否在FB上已删除',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_adsets_source_id_index`(`source_id` ASC) USING BTREE,
  INDEX `fb_adsets_fb_campaign_id_index`(`fb_campaign_id` ASC) USING BTREE,
  INDEX `fb_adsets_account_id_index`(`account_id` ASC) USING BTREE,
  INDEX `fb_adsets_pixel_id_foreign`(`pixel_id` ASC) USING BTREE,
  CONSTRAINT `fb_adsets_fb_campaign_id_foreign` FOREIGN KEY (`fb_campaign_id`) REFERENCES `fb_campaigns` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_adsets_pixel_id_foreign` FOREIGN KEY (`pixel_id`) REFERENCES `fb_pixels` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告组表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_adsets
-- ----------------------------

-- ----------------------------
-- Table structure for fb_api_token_fb_ad_account
-- ----------------------------
DROP TABLE IF EXISTS `fb_api_token_fb_ad_account`;
CREATE TABLE `fb_api_token_fb_ad_account`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_api_token_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'API Token ID',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告账户ID',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '任务权限',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_api_token_fb_ad_account_fb_api_token_id_foreign`(`fb_api_token_id` ASC) USING BTREE,
  INDEX `fb_api_token_fb_ad_account_fb_ad_account_id_foreign`(`fb_ad_account_id` ASC) USING BTREE,
  CONSTRAINT `fb_api_token_fb_ad_account_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_api_token_fb_ad_account_fb_api_token_id_foreign` FOREIGN KEY (`fb_api_token_id`) REFERENCES `fb_api_tokens` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'API Token-广告账户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_api_token_fb_ad_account
-- ----------------------------

-- ----------------------------
-- Table structure for fb_api_token_fb_page
-- ----------------------------
DROP TABLE IF EXISTS `fb_api_token_fb_page`;
CREATE TABLE `fb_api_token_fb_page`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_api_token_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'API Token ID',
  `fb_page_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主页ID',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '任务权限',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_api_token_fb_page_fb_api_token_id_foreign`(`fb_api_token_id` ASC) USING BTREE,
  INDEX `fb_api_token_fb_page_fb_page_id_foreign`(`fb_page_id` ASC) USING BTREE,
  CONSTRAINT `fb_api_token_fb_page_fb_api_token_id_foreign` FOREIGN KEY (`fb_api_token_id`) REFERENCES `fb_api_tokens` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_api_token_fb_page_fb_page_id_foreign` FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'API Token-主页关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_api_token_fb_page
-- ----------------------------

-- ----------------------------
-- Table structure for fb_api_tokens
-- ----------------------------
DROP TABLE IF EXISTS `fb_api_tokens`;
CREATE TABLE `fb_api_tokens`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Token名称',
  `token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Access Token',
  `active` tinyint(1) NOT NULL COMMENT '是否激活',
  `bm_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'BM ID',
  `token_type` int NULL DEFAULT 1 COMMENT 'Token类型',
  `app` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '关联的FbApp的source_id',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_api_tokens_app_index`(`app` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'API Token表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_api_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for fb_app_fb_ad_account
-- ----------------------------
DROP TABLE IF EXISTS `fb_app_fb_ad_account`;
CREATE TABLE `fb_app_fb_ad_account`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_app_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '应用ID',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告账户ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `fb_app_fb_ad_account_unique`(`fb_app_id` ASC, `fb_ad_account_id` ASC) USING BTREE,
  INDEX `fb_app_fb_ad_account_fb_app_id_index`(`fb_app_id` ASC) USING BTREE,
  INDEX `fb_app_fb_ad_account_fb_ad_account_id_index`(`fb_ad_account_id` ASC) USING BTREE,
  CONSTRAINT `fb_app_fb_ad_account_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_app_fb_ad_account_fb_app_id_foreign` FOREIGN KEY (`fb_app_id`) REFERENCES `fb_apps` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '应用-广告账户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_app_fb_ad_account
-- ----------------------------

-- ----------------------------
-- Table structure for fb_app_fb_bm
-- ----------------------------
DROP TABLE IF EXISTS `fb_app_fb_bm`;
CREATE TABLE `fb_app_fb_bm`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_app_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '应用ID',
  `fb_bm_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BM ID',
  `relation` enum('owner','client') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关系类型(owner/client)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `fb_app_fb_bm_unique`(`fb_app_id` ASC, `fb_bm_id` ASC, `relation` ASC) USING BTREE,
  INDEX `fb_app_fb_bm_fb_app_id_relation_index`(`fb_app_id` ASC, `relation` ASC) USING BTREE,
  INDEX `fb_app_fb_bm_fb_bm_id_relation_index`(`fb_bm_id` ASC, `relation` ASC) USING BTREE,
  CONSTRAINT `fb_app_fb_bm_fb_app_id_foreign` FOREIGN KEY (`fb_app_id`) REFERENCES `fb_apps` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_app_fb_bm_fb_bm_id_foreign` FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '应用-BM关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_app_fb_bm
-- ----------------------------

-- ----------------------------
-- Table structure for fb_apps
-- ----------------------------
DROP TABLE IF EXISTS `fb_apps`;
CREATE TABLE `fb_apps`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook应用ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '应用名称',
  `created_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `source_id`(`source_id` ASC) USING BTREE,
  INDEX `fb_apps_source_id_index`(`source_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Facebook应用表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_apps
-- ----------------------------

-- ----------------------------
-- Table structure for fb_bm_fb_catalog
-- ----------------------------
DROP TABLE IF EXISTS `fb_bm_fb_catalog`;
CREATE TABLE `fb_bm_fb_catalog`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_bm_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BM ID',
  `fb_catalog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目录ID',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '任务权限',
  `relation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关系类型',
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_bm_fb_catalog_fb_bm_id_foreign`(`fb_bm_id` ASC) USING BTREE,
  INDEX `fb_bm_fb_catalog_fb_catalog_id_foreign`(`fb_catalog_id` ASC) USING BTREE,
  CONSTRAINT `fb_bm_fb_catalog_fb_bm_id_foreign` FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_bm_fb_catalog_fb_catalog_id_foreign` FOREIGN KEY (`fb_catalog_id`) REFERENCES `fb_catalogs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'BM-目录关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_bm_fb_catalog
-- ----------------------------

-- ----------------------------
-- Table structure for fb_bm_fb_page
-- ----------------------------
DROP TABLE IF EXISTS `fb_bm_fb_page`;
CREATE TABLE `fb_bm_fb_page`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_bm_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BM ID',
  `fb_page_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主页ID',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '任务权限',
  `is_owner` tinyint(1) NULL DEFAULT 0 COMMENT '是否所有者',
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '角色',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_bm_fb_page_fb_bm_id_foreign`(`fb_bm_id` ASC) USING BTREE,
  INDEX `fb_bm_fb_page_fb_page_id_foreign`(`fb_page_id` ASC) USING BTREE,
  CONSTRAINT `fb_bm_fb_page_fb_bm_id_foreign` FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_bm_fb_page_fb_page_id_foreign` FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'BM-主页关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_bm_fb_page
-- ----------------------------

-- ----------------------------
-- Table structure for fb_bms
-- ----------------------------
DROP TABLE IF EXISTS `fb_bms`;
CREATE TABLE `fb_bms`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook BM ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BM名称',
  `created_time` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '创建时间',
  `timezone_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '时区ID',
  `two_factor_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '双因素认证类型',
  `verification_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '验证状态',
  `is_disabled_for_integrity_reasons` tinyint(1) NULL DEFAULT NULL COMMENT '是否因诚信原因被禁用',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Business Manager表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_bms
-- ----------------------------

-- ----------------------------
-- Table structure for fb_business_user_fb_catalog
-- ----------------------------
DROP TABLE IF EXISTS `fb_business_user_fb_catalog`;
CREATE TABLE `fb_business_user_fb_catalog`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_business_user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '业务用户ID',
  `fb_catalog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目录ID',
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '任务权限',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_business_user_fb_catalog_fb_business_user_id_foreign`(`fb_business_user_id` ASC) USING BTREE,
  INDEX `fb_business_user_fb_catalog_fb_catalog_id_foreign`(`fb_catalog_id` ASC) USING BTREE,
  CONSTRAINT `fb_business_user_fb_catalog_fb_business_user_id_foreign` FOREIGN KEY (`fb_business_user_id`) REFERENCES `fb_business_users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_business_user_fb_catalog_fb_catalog_id_foreign` FOREIGN KEY (`fb_catalog_id`) REFERENCES `fb_catalogs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '业务用户-目录关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_business_user_fb_catalog
-- ----------------------------

-- ----------------------------
-- Table structure for fb_business_user_fb_page
-- ----------------------------
DROP TABLE IF EXISTS `fb_business_user_fb_page`;
CREATE TABLE `fb_business_user_fb_page`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_business_user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '业务用户ID',
  `fb_page_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主页ID',
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色',
  `tasks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '任务权限',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_business_user_fb_page_fb_business_user_id_foreign`(`fb_business_user_id` ASC) USING BTREE,
  INDEX `fb_business_user_fb_page_fb_page_id_foreign`(`fb_page_id` ASC) USING BTREE,
  CONSTRAINT `fb_business_user_fb_page_fb_business_user_id_foreign` FOREIGN KEY (`fb_business_user_id`) REFERENCES `fb_business_users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_business_user_fb_page_fb_page_id_foreign` FOREIGN KEY (`fb_page_id`) REFERENCES `fb_pages` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '业务用户-主页关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_business_user_fb_page
-- ----------------------------

-- ----------------------------
-- Table structure for fb_business_users
-- ----------------------------
DROP TABLE IF EXISTS `fb_business_users`;
CREATE TABLE `fb_business_users`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Facebook账户ID',
  `fb_bm_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'BM ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook业务用户ID',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '邮箱',
  `finance_permission` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '财务权限',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '名',
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '姓',
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色',
  `two_fac_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '双因素状态',
  `expiry_time` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '过期时间',
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户类型(business_user/system_user)',
  `is_operator` tinyint(1) NULL DEFAULT 0 COMMENT '是否操作用户',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_business_users_fb_account_id_foreign`(`fb_account_id` ASC) USING BTREE,
  INDEX `fb_business_users_fb_bm_id_foreign`(`fb_bm_id` ASC) USING BTREE,
  CONSTRAINT `fb_business_users_fb_account_id_foreign` FOREIGN KEY (`fb_account_id`) REFERENCES `fb_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_business_users_fb_bm_id_foreign` FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '业务用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_business_users
-- ----------------------------

-- ----------------------------
-- Table structure for fb_campaign_insights
-- ----------------------------
DROP TABLE IF EXISTS `fb_campaign_insights`;
CREATE TABLE `fb_campaign_insights`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `account_currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户货币',
  `account_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户ID',
  `account_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户名称',
  `campaign_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列ID',
  `campaign_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '系列名称',
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '操作',
  `action_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '操作值',
  `clicks` bigint NULL DEFAULT NULL COMMENT '点击数',
  `cost_per_action_type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '每操作成本',
  `cost_per_inline_link_click` float NULL DEFAULT NULL COMMENT '每内联链接点击成本',
  `cpc` float NULL DEFAULT NULL COMMENT 'CPC',
  `cpm` float NULL DEFAULT NULL COMMENT 'CPM',
  `ctr` float NULL DEFAULT NULL COMMENT 'CTR',
  `date_start` date NOT NULL COMMENT '开始日期',
  `date_stop` date NOT NULL COMMENT '结束日期',
  `frequency` float NULL DEFAULT NULL COMMENT '频率',
  `impressions` bigint NULL DEFAULT NULL COMMENT '展示数',
  `inline_link_click_ctr` float NULL DEFAULT NULL COMMENT '内联链接点击率',
  `inline_link_clicks` bigint NULL DEFAULT NULL COMMENT '内联链接点击数',
  `objective` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '目标',
  `purchase_roas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '购买ROAS',
  `purchase_roas_value` float NULL DEFAULT NULL COMMENT '购买ROAS值',
  `quality_ranking` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '质量排名',
  `reach` bigint NULL DEFAULT NULL COMMENT '触达数',
  `spend` float NULL DEFAULT NULL COMMENT '花费',
  `purchase` int NULL DEFAULT NULL COMMENT '购买数',
  `purchase_value` float NULL DEFAULT NULL COMMENT '购买价值',
  `cost_per_purchase` float NULL DEFAULT NULL COMMENT '每购买成本',
  `lead` int NULL DEFAULT NULL COMMENT '线索数',
  `cost_per_lead` float NULL DEFAULT NULL COMMENT '每线索成本',
  `add_to_cart` int NULL DEFAULT NULL COMMENT '加购数',
  `cost_to_add_to_cart` float NULL DEFAULT NULL COMMENT '每加购成本',
  `comment` int NULL DEFAULT NULL COMMENT '评论数',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_campaign_insights_campaign_id_index`(`campaign_id` ASC) USING BTREE,
  INDEX `fb_campaign_insights_date_start_index`(`date_start` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告系列洞察表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_campaign_insights
-- ----------------------------

-- ----------------------------
-- Table structure for fb_campaigns
-- ----------------------------
DROP TABLE IF EXISTS `fb_campaigns`;
CREATE TABLE `fb_campaigns`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告账户ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Facebook广告系列ID',
  `account_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '账户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系列名称',
  `objective` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '目标',
  `bid_strategy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '出价策略',
  `budget_remaining` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '剩余预算',
  `daily_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '日预算',
  `lifetime_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '总预算',
  `original_daily_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '原始日预算',
  `original_lifetime_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '原始总预算',
  `configured_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '配置状态',
  `effective_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '有效状态',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '状态',
  `source_campaign_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '源广告系列ID',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '开始时间',
  `created_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `is_archived` tinyint(1) NULL DEFAULT 0 COMMENT '是否归档',
  `is_deleted_on_fb` tinyint(1) NULL DEFAULT 0 COMMENT '是否在FB上已删除',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_campaigns_source_id_index`(`source_id` ASC) USING BTREE,
  INDEX `fb_campaigns_fb_ad_account_id_index`(`fb_ad_account_id` ASC) USING BTREE,
  CONSTRAINT `fb_campaigns_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '广告系列表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_campaigns
-- ----------------------------

-- ----------------------------
-- Table structure for fb_catalog_fb_pixel
-- ----------------------------
DROP TABLE IF EXISTS `fb_catalog_fb_pixel`;
CREATE TABLE `fb_catalog_fb_pixel`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_catalog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目录ID',
  `fb_pixel_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '像素ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_catalog_fb_pixel_fb_catalog_id_foreign`(`fb_catalog_id` ASC) USING BTREE,
  INDEX `fb_catalog_fb_pixel_fb_pixel_id_foreign`(`fb_pixel_id` ASC) USING BTREE,
  CONSTRAINT `fb_catalog_fb_pixel_fb_catalog_id_foreign` FOREIGN KEY (`fb_catalog_id`) REFERENCES `fb_catalogs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_catalog_fb_pixel_fb_pixel_id_foreign` FOREIGN KEY (`fb_pixel_id`) REFERENCES `fb_pixels` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '目录-像素关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_catalog_fb_pixel
-- ----------------------------

-- ----------------------------
-- Table structure for fb_catalog_product_fb_catalog_product_set
-- ----------------------------
DROP TABLE IF EXISTS `fb_catalog_product_fb_catalog_product_set`;
CREATE TABLE `fb_catalog_product_fb_catalog_product_set`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_catalog_product_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品ID',
  `fb_catalog_product_set_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品集ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `product_product_set_product_id_foreign`(`fb_catalog_product_id` ASC) USING BTREE,
  INDEX `product_product_set_product_set_id_foreign`(`fb_catalog_product_set_id` ASC) USING BTREE,
  CONSTRAINT `product_product_set_product_id_foreign` FOREIGN KEY (`fb_catalog_product_id`) REFERENCES `fb_catalog_products` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `product_product_set_product_set_id_foreign` FOREIGN KEY (`fb_catalog_product_set_id`) REFERENCES `fb_catalog_product_sets` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '产品集-产品关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_catalog_product_fb_catalog_product_set
-- ----------------------------

-- ----------------------------
-- Table structure for fb_catalog_product_sets
-- ----------------------------
DROP TABLE IF EXISTS `fb_catalog_product_sets`;
CREATE TABLE `fb_catalog_product_sets`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_catalog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目录ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook产品集ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品集名称',
  `filter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '过滤条件',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '目录产品集表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_catalog_product_sets
-- ----------------------------

-- ----------------------------
-- Table structure for fb_catalog_products
-- ----------------------------
DROP TABLE IF EXISTS `fb_catalog_products`;
CREATE TABLE `fb_catalog_products`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_catalog_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目录ID',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook产品ID',
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品名称',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品描述',
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产品URL',
  `image_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '图片URL',
  `video_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '视频URL',
  `video_handler` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '视频处理器',
  `retailer_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '零售商ID',
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '货币',
  `price` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '价格',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '目录产品表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_catalog_products
-- ----------------------------

-- ----------------------------
-- Table structure for fb_catalogs
-- ----------------------------
DROP TABLE IF EXISTS `fb_catalogs`;
CREATE TABLE `fb_catalogs`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook目录ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目录名称',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '目录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_catalogs
-- ----------------------------

-- ----------------------------
-- Table structure for fb_client_ad_accounts
-- ----------------------------
DROP TABLE IF EXISTS `fb_client_ad_accounts`;
CREATE TABLE `fb_client_ad_accounts`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '客户端广告账户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_client_ad_accounts
-- ----------------------------

-- ----------------------------
-- Table structure for fb_page_forms
-- ----------------------------
DROP TABLE IF EXISTS `fb_page_forms`;
CREATE TABLE `fb_page_forms`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook表单ID',
  `locale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '语言',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表单名称',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '状态',
  `created_time` datetime NOT NULL COMMENT '创建时间',
  `thank_you_page` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '感谢页面',
  `privacy_policy_url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '隐私政策URL',
  `legal_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '法律内容',
  `follow_up_action_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '后续操作URL',
  `leads_count` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '线索数量',
  `page_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主页源ID',
  `page_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主页名称',
  `page_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '主页ID',
  `questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '问题',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '主页表单表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_page_forms
-- ----------------------------

-- ----------------------------
-- Table structure for fb_page_posts
-- ----------------------------
DROP TABLE IF EXISTS `fb_page_posts`;
CREATE TABLE `fb_page_posts`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook帖子ID',
  `primary_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '主文案',
  `headline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标题',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '描述',
  `post_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '帖子类型',
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'URL',
  `permalink_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '永久链接',
  `created_time` datetime NOT NULL COMMENT '创建时间',
  `campaign_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告系列ID',
  `adset_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告组ID',
  `ad_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告ID',
  `page_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '主页ID',
  `ad_account_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告账户ID',
  `media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '媒体内容',
  `url_tags` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'URL标签',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '主页帖子表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_page_posts
-- ----------------------------

-- ----------------------------
-- Table structure for fb_pages
-- ----------------------------
DROP TABLE IF EXISTS `fb_pages`;
CREATE TABLE `fb_pages`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Facebook主页ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主页名称',
  `fan_count` int NULL DEFAULT NULL COMMENT '粉丝数',
  `promotion_eligible` tinyint(1) NULL DEFAULT NULL COMMENT '是否可推广',
  `verification_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '验证状态',
  `picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '头像',
  `tokens` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT 'Access Tokens',
  `pbia` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'PBIA',
  `access_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Access Token',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Facebook主页表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_pages
-- ----------------------------

-- ----------------------------
-- Table structure for fb_pixel_fb_ad_account
-- ----------------------------
DROP TABLE IF EXISTS `fb_pixel_fb_ad_account`;
CREATE TABLE `fb_pixel_fb_ad_account`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_pixel_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '像素ID',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '广告账户ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_pixel_fb_ad_account_fb_pixel_id_foreign`(`fb_pixel_id` ASC) USING BTREE,
  INDEX `fb_pixel_fb_ad_account_fb_ad_account_id_foreign`(`fb_ad_account_id` ASC) USING BTREE,
  CONSTRAINT `fb_pixel_fb_ad_account_fb_ad_account_id_foreign` FOREIGN KEY (`fb_ad_account_id`) REFERENCES `fb_ad_accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_pixel_fb_ad_account_fb_pixel_id_foreign` FOREIGN KEY (`fb_pixel_id`) REFERENCES `fb_pixels` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '像素-广告账户关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_pixel_fb_ad_account
-- ----------------------------

-- ----------------------------
-- Table structure for fb_pixel_fb_bm
-- ----------------------------
DROP TABLE IF EXISTS `fb_pixel_fb_bm`;
CREATE TABLE `fb_pixel_fb_bm`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `fb_pixel_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '像素ID',
  `fb_bm_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'BM ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fb_pixel_fb_bm_fb_pixel_id_foreign`(`fb_pixel_id` ASC) USING BTREE,
  INDEX `fb_pixel_fb_bm_fb_bm_id_foreign`(`fb_bm_id` ASC) USING BTREE,
  CONSTRAINT `fb_pixel_fb_bm_fb_bm_id_foreign` FOREIGN KEY (`fb_bm_id`) REFERENCES `fb_bms` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fb_pixel_fb_bm_fb_pixel_id_foreign` FOREIGN KEY (`fb_pixel_id`) REFERENCES `fb_pixels` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '像素-BM关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_pixel_fb_bm
-- ----------------------------

-- ----------------------------
-- Table structure for fb_pixels
-- ----------------------------
DROP TABLE IF EXISTS `fb_pixels`;
CREATE TABLE `fb_pixels`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '像素名称',
  `pixel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '像素ID',
  `is_created_by_business` tinyint(1) NOT NULL COMMENT '是否由业务创建',
  `is_unavailable` tinyint(1) NOT NULL COMMENT '是否不可用',
  `owner_business` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '所有者业务',
  `creator` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '创建者',
  `is_dataset` tinyint(1) NULL DEFAULT 0 COMMENT '是否数据集',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Pixel像素表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fb_pixels
-- ----------------------------

-- ----------------------------
-- Table structure for finger_browser_groups
-- ----------------------------
DROP TABLE IF EXISTS `finger_browser_groups`;
CREATE TABLE `finger_browser_groups`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '指纹浏览器组表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of finger_browser_groups
-- ----------------------------

-- ----------------------------
-- Table structure for finger_browsers
-- ----------------------------
DROP TABLE IF EXISTS `finger_browsers`;
CREATE TABLE `finger_browsers`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '指纹浏览器表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of finger_browsers
-- ----------------------------

-- ----------------------------
-- Table structure for fraud_configs
-- ----------------------------
DROP TABLE IF EXISTS `fraud_configs`;
CREATE TABLE `fraud_configs`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型',
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '值',
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '动作',
  `active` tinyint(1) NULL DEFAULT 0 COMMENT '是否激活',
  `excluded_ads` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '排除检测的广告source_id列表',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '防盗刷配置表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fraud_configs
-- ----------------------------

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '批次名称',
  `total_jobs` int NOT NULL COMMENT '总任务数',
  `pending_jobs` int NOT NULL COMMENT '待处理任务数',
  `failed_jobs` int NOT NULL COMMENT '失败任务数',
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '失败任务ID列表',
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '选项',
  `cancelled_at` int NULL DEFAULT NULL COMMENT '取消时间',
  `created_at` int NOT NULL COMMENT '创建时间',
  `finished_at` int NULL DEFAULT NULL COMMENT '完成时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '批量任务表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of job_batches
-- ----------------------------

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '队列名称',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '任务负载',
  `attempts` tinyint UNSIGNED NOT NULL COMMENT '尝试次数',
  `reserved_at` int UNSIGNED NULL DEFAULT NULL COMMENT '保留时间',
  `available_at` int UNSIGNED NOT NULL COMMENT '可用时间',
  `created_at` int UNSIGNED NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `jobs_queue_index`(`queue` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '队列任务表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of jobs
-- ----------------------------

-- ----------------------------
-- Table structure for link_shares
-- ----------------------------
DROP TABLE IF EXISTS `link_shares`;
CREATE TABLE `link_shares`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `link_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '链接ID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `link_shares_link_id_foreign`(`link_id` ASC) USING BTREE,
  INDEX `link_shares_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `link_shares_link_id_foreign` FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `link_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '链接分享表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of link_shares
-- ----------------------------

-- ----------------------------
-- Table structure for links
-- ----------------------------
DROP TABLE IF EXISTS `links`;
CREATE TABLE `links`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '链接URL',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '链接表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of links
-- ----------------------------

-- ----------------------------
-- Table structure for material_shares
-- ----------------------------
DROP TABLE IF EXISTS `material_shares`;
CREATE TABLE `material_shares`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `material_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '素材ID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `material_shares_material_id_foreign`(`material_id` ASC) USING BTREE,
  INDEX `material_shares_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `material_shares_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `material_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '素材分享表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of material_shares
-- ----------------------------

-- ----------------------------
-- Table structure for materials
-- ----------------------------
DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '素材名称',
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件名',
  `filepath` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件路径',
  `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '原始文件名',
  `type` enum('image','video') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '素材类型',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '素材表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of materials
-- ----------------------------

-- ----------------------------
-- Table structure for meta_ad_creation_creative_groups
-- ----------------------------
DROP TABLE IF EXISTS `meta_ad_creation_creative_groups`;
CREATE TABLE `meta_ad_creation_creative_groups`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '创建人',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '创意组名称',
  `creative_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'create' COMMENT 'create / post',
  `material_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '素材 ID 列表',
  `materials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '素材快照（用于展示）',
  `post_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '帖子 ID 列表',
  `format` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single' COMMENT 'flexible / single / carousel',
  `setting_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'by_group' COMMENT 'by_group / by_material',
  `deep_link` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '深度链接',
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '正文',
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标题',
  `cta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '行动号召',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '标签',
  `video_optimization` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '视频优化',
  `image_optimization` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '图片优化',
  `multilang` tinyint(1) NOT NULL DEFAULT 0 COMMENT '多语言',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id` ASC) USING BTREE,
  INDEX `idx_created_at`(`created_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Meta广告创建创意组' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of meta_ad_creation_creative_groups
-- ----------------------------

-- ----------------------------
-- Table structure for meta_ad_creation_drafts
-- ----------------------------
DROP TABLE IF EXISTS `meta_ad_creation_drafts`;
CREATE TABLE `meta_ad_creation_drafts`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '创建用户',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告账户（fb_ad_accounts.id）',
  `tag` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '草稿标签',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '草稿名称',
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '9 步表单数据',
  `meta_counts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '地区组/定向包/出价预算/创意组数量',
  `current_step` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前步骤 0-8',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id` ASC) USING BTREE,
  INDEX `idx_fb_ad_account_id`(`fb_ad_account_id` ASC) USING BTREE,
  INDEX `idx_tag`(`tag` ASC) USING BTREE,
  INDEX `idx_user_tag`(`user_id` ASC, `tag` ASC) USING BTREE,
  INDEX `idx_updated_at`(`updated_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Meta广告创建草稿' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of meta_ad_creation_drafts
-- ----------------------------

-- ----------------------------
-- Table structure for meta_ad_creation_records
-- ----------------------------
DROP TABLE IF EXISTS `meta_ad_creation_records`;
CREATE TABLE `meta_ad_creation_records`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '创建人',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告账户',
  `draft_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '来源草稿 ID',
  `template_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '来源模板 ID',
  `region_group_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '地区组 ID（选择已有地区组时）',
  `creative_group_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '创意组 ID（选择已有创意组时）',
  `ad_log_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '关联投放日志',
  `form_data_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '提交时的 9 步表单快照',
  `fb_campaign_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '本次创建产生的广告系列',
  `fb_adset_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '广告组 ID 列表',
  `fb_ad_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '广告 ID 列表',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id` ASC) USING BTREE,
  INDEX `idx_fb_ad_account_id`(`fb_ad_account_id` ASC) USING BTREE,
  INDEX `idx_fb_campaign_id`(`fb_campaign_id` ASC) USING BTREE,
  INDEX `idx_draft_id`(`draft_id` ASC) USING BTREE,
  INDEX `idx_ad_log_id`(`ad_log_id` ASC) USING BTREE,
  INDEX `idx_region_group_id`(`region_group_id` ASC) USING BTREE,
  INDEX `idx_creative_group_id`(`creative_group_id` ASC) USING BTREE,
  INDEX `idx_created_at`(`created_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Meta广告创建记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of meta_ad_creation_records
-- ----------------------------
INSERT INTO `meta_ad_creation_records` VALUES ('1', 'USER_ULID', NULL, NULL, NULL, NULL, NULL, NULL, '{\"stepOne\":{\"objective\":\"OUTCOME_SALES\",\"conversionLocation\":\"website\"},\"stepTwo\":{},\"stepDelivery\":{},\"stepRegion\":{},\"stepPlacement\":{},\"stepTargeting\":{},\"stepBidBudget\":{},\"stepCreativeSettings\":{},\"stepCreativeGroup\":{}}', NULL, NULL, NULL, '2026-03-20 17:19:07', '2026-03-20 17:19:07', NULL);
INSERT INTO `meta_ad_creation_records` VALUES ('2', '001', NULL, NULL, NULL, NULL, NULL, NULL, '{\"stepOne\":{\"objective\":\"OUTCOME_SALES\",\"conversionLocation\":\"website\"},\"stepTwo\":{},\"stepDelivery\":{},\"stepRegion\":{},\"stepPlacement\":{},\"stepTargeting\":{},\"stepBidBudget\":{},\"stepCreativeSettings\":{},\"stepCreativeGroup\":{}}', NULL, NULL, NULL, '2026-03-20 17:19:25', '2026-03-20 17:19:25', NULL);
INSERT INTO `meta_ad_creation_records` VALUES ('RECORD_ULID', 'USER_ULID', NULL, NULL, NULL, NULL, NULL, NULL, '{\"stepOne\":{\"objective\":\"OUTCOME_SALES\",\"conversionLocation\":\"website\"},\"stepTwo\":{},\"stepDelivery\":{},\"stepRegion\":{},\"stepPlacement\":{},\"stepTargeting\":{},\"stepBidBudget\":{},\"stepCreativeSettings\":{},\"stepCreativeGroup\":{}}', NULL, NULL, NULL, '2026-03-20 17:07:03', '2026-03-20 17:07:03', NULL);

-- ----------------------------
-- Table structure for meta_ad_creation_region_groups
-- ----------------------------
DROP TABLE IF EXISTS `meta_ad_creation_region_groups`;
CREATE TABLE `meta_ad_creation_region_groups`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '创建人',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '地区组名称',
  `countries_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '定向地区 [{key, name}, ...]',
  `countries_excluded` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '排除地区 [{key, name}, ...]',
  `regions_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '定向大区 [{key, name}, ...]',
  `regions_excluded` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '排除大区 [{key, name}, ...]',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id` ASC) USING BTREE,
  INDEX `idx_created_at`(`created_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Meta广告创建地区组' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of meta_ad_creation_region_groups
-- ----------------------------
INSERT INTO `meta_ad_creation_region_groups` VALUES ('1', '001', '地区组名', '[{\"key\":\"US\",\"name\":\"United States\"}]', '[]', NULL, NULL, '2026-03-20 17:23:53', '2026-03-20 17:23:53', NULL);

-- ----------------------------
-- Table structure for meta_ad_creation_templates
-- ----------------------------
DROP TABLE IF EXISTS `meta_ad_creation_templates`;
CREATE TABLE `meta_ad_creation_templates`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '创建用户',
  `fb_ad_account_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '广告账户（fb_ad_accounts.id）',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板名称',
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '9 步表单数据',
  `meta_counts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '地区组/定向包/出价预算/创意组数量',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_id`(`user_id` ASC) USING BTREE,
  INDEX `idx_fb_ad_account_id`(`fb_ad_account_id` ASC) USING BTREE,
  INDEX `idx_created_at`(`created_at` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Meta广告创建模板' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of meta_ad_creation_templates
-- ----------------------------

-- ----------------------------
-- Table structure for model_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions`  (
  `permission_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '权限ID',
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型类型',
  `model_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型ID',
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`) USING BTREE,
  INDEX `model_has_permissions_model_id_model_type_index`(`model_id` ASC, `model_type` ASC) USING BTREE,
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '模型权限关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of model_has_permissions
-- ----------------------------

-- ----------------------------
-- Table structure for model_has_roles
-- ----------------------------
DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles`  (
  `role_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色ID',
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型类型',
  `model_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模型ID',
  PRIMARY KEY (`role_id`, `model_id`, `model_type`) USING BTREE,
  INDEX `model_has_roles_model_id_model_type_index`(`model_id` ASC, `model_type` ASC) USING BTREE,
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '模型角色关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of model_has_roles
-- ----------------------------

-- ----------------------------
-- Table structure for networks
-- ----------------------------
DROP TABLE IF EXISTS `networks`;
CREATE TABLE `networks`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `subid_mapping_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'SubID映射ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '网络名称',
  `system_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '系统类型(Cake/Everflow/Keitaro/Jumb)',
  `aff_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '联盟ID',
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'API端点',
  `apikey` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'API密钥',
  `click_placeholder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '点击占位符',
  `active` tinyint(1) NULL DEFAULT 0 COMMENT '是否激活',
  `is_subnetwork` tinyint(1) NULL DEFAULT 0 COMMENT '是否子网络',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `name`(`name` ASC) USING BTREE,
  INDEX `networks_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `networks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '网络表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of networks
-- ----------------------------

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens`  (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮箱',
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '重置令牌',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '密码重置令牌表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '权限名称',
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '守卫名称',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `permissions_name_guard_name_unique`(`name` ASC, `guard_name` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '权限表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of permissions
-- ----------------------------

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '令牌所属模型类型',
  `tokenable_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '令牌所属模型ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '令牌名称',
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '令牌值',
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '权限',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT '最后使用时间',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `token`(`token` ASC) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type` ASC, `tokenable_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '个人访问令牌表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for proxies
-- ----------------------------
DROP TABLE IF EXISTS `proxies`;
CREATE TABLE `proxies`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `proxies_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `proxies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '代理表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of proxies
-- ----------------------------

-- ----------------------------
-- Table structure for request_logs
-- ----------------------------
DROP TABLE IF EXISTS `request_logs`;
CREATE TABLE `request_logs`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IP地址',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'User Agent',
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '请求方法',
  `request_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '请求路径',
  `query_parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '查询参数',
  `request_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '请求体',
  `response_status` int NULL DEFAULT NULL COMMENT '响应状态码',
  `response_time` int NULL DEFAULT NULL COMMENT '响应时间(毫秒)',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP COMMENT '请求时间',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `request_logs_user_id_index`(`user_id` ASC) USING BTREE,
  INDEX `request_logs_ip_address_index`(`ip_address` ASC) USING BTREE,
  INDEX `request_logs_request_method_index`(`request_method` ASC) USING BTREE,
  INDEX `request_logs_request_path_index`(`request_path` ASC) USING BTREE,
  INDEX `request_logs_response_status_index`(`response_status` ASC) USING BTREE,
  INDEX `request_logs_requested_at_index`(`requested_at` ASC) USING BTREE,
  INDEX `request_logs_user_id_requested_at_index`(`user_id` ASC, `requested_at` ASC) USING BTREE,
  INDEX `request_logs_ip_address_requested_at_index`(`ip_address` ASC, `requested_at` ASC) USING BTREE,
  INDEX `request_logs_request_method_request_path_index`(`request_method` ASC, `request_path` ASC) USING BTREE,
  CONSTRAINT `request_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '请求日志表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of request_logs
-- ----------------------------

-- ----------------------------
-- Table structure for role_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions`  (
  `permission_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '权限ID',
  `role_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色ID',
  PRIMARY KEY (`permission_id`, `role_id`) USING BTREE,
  INDEX `role_has_permissions_role_id_foreign`(`role_id` ASC) USING BTREE,
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '角色权限关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of role_has_permissions
-- ----------------------------

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色名称',
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '守卫名称',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `roles_name_guard_name_unique`(`name` ASC, `guard_name` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '角色表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of roles
-- ----------------------------

-- ----------------------------
-- Table structure for ruleables
-- ----------------------------
DROP TABLE IF EXISTS `ruleables`;
CREATE TABLE `ruleables`  (
  `rule_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '规则ID',
  `ruleable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关联模型类型',
  `ruleable_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关联模型ID',
  PRIMARY KEY (`rule_id`, `ruleable_id`, `ruleable_type`) USING BTREE,
  INDEX `ruleables_ruleable_type_ruleable_id_index`(`ruleable_type` ASC, `ruleable_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '规则关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ruleables
-- ----------------------------

-- ----------------------------
-- Table structure for rules
-- ----------------------------
DROP TABLE IF EXISTS `rules`;
CREATE TABLE `rules`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '规则名称',
  `date_preset` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '日期预设',
  `scope` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '作用范围',
  `relation` tinyint(1) NOT NULL COMMENT '关系(AND/OR)',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '条件',
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '动作',
  `white_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '白名单',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'Active' COMMENT '状态',
  `resource_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL COMMENT '资源ID列表',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `rules_user_id_foreign`(`user_id` ASC) USING BTREE,
  CONSTRAINT `rules_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '规则表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of rules
-- ----------------------------

-- ----------------------------
-- Table structure for search_bookmarks
-- ----------------------------
DROP TABLE IF EXISTS `search_bookmarks`;
CREATE TABLE `search_bookmarks`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '书签名称',
  `search_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '搜索条件',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `search_bookmarks_user_id_deleted_at_index`(`user_id` ASC, `deleted_at` ASC) USING BTREE,
  CONSTRAINT `search_bookmarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '搜索书签表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of search_bookmarks
-- ----------------------------

-- ----------------------------
-- Table structure for subid_mappings
-- ----------------------------
DROP TABLE IF EXISTS `subid_mappings`;
CREATE TABLE `subid_mappings`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '映射名称',
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户ID',
  `subid_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SubID 1',
  `subid_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SubID 2',
  `subid_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SubID 3',
  `subid_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SubID 4',
  `subid_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SubID 5',
  `fb_campaign_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告系列ID',
  `fb_adset_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告组ID',
  `fb_ad_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'FB广告ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'SubID映射表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of subid_mappings
-- ----------------------------

-- ----------------------------
-- Table structure for taggables
-- ----------------------------
DROP TABLE IF EXISTS `taggables`;
CREATE TABLE `taggables`  (
  `taggable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关联模型类型',
  `taggable_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '关联模型ID',
  `tag_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标签ID',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  PRIMARY KEY (`tag_id`, `taggable_id`, `taggable_type`) USING BTREE,
  INDEX `taggables_taggable_type_taggable_id_index`(`taggable_type` ASC, `taggable_id` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '标签关联表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of taggables
-- ----------------------------

-- ----------------------------
-- Table structure for tags
-- ----------------------------
DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `user_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '用户ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标签名称',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '标签表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tags
-- ----------------------------

-- ----------------------------
-- Table structure for tracker_campaigns
-- ----------------------------
DROP TABLE IF EXISTS `tracker_campaigns`;
CREATE TABLE `tracker_campaigns`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `tracker_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '追踪器ID',
  `campaign_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '活动名称',
  `campaign_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '活动源ID',
  `alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '别名',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '追踪器活动表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tracker_campaigns
-- ----------------------------

-- ----------------------------
-- Table structure for tracker_offer_clicks
-- ----------------------------
DROP TABLE IF EXISTS `tracker_offer_clicks`;
CREATE TABLE `tracker_offer_clicks`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `tracker_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '追踪器ID',
  `tracker_campaign_id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '追踪器活动ID',
  `campaign_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '活动源ID',
  `subid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'SubID',
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'IP地址',
  `sub_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub1',
  `sub_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub2',
  `sub_3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub3',
  `sub_4` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub4',
  `sub_5` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Sub5',
  `click_date` timestamp NULL DEFAULT NULL COMMENT '点击日期',
  `offer` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Offer',
  `landing` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '落地页',
  `country_flag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '国家标识',
  `network_identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '网络标识符',
  `identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标识符',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '追踪器Offer点击表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tracker_offer_clicks
-- ----------------------------

-- ----------------------------
-- Table structure for trackers
-- ----------------------------
DROP TABLE IF EXISTS `trackers`;
CREATE TABLE `trackers`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '追踪器名称',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否归档',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '追踪器表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of trackers
-- ----------------------------

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ULID主键',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '邮箱',
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT '邮箱验证时间',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '记住我令牌',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `email`(`email` ASC) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES ('1', 'admin', 'admin@example.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '2026-01-02 18:30:13', '2026-01-02 18:30:13');

SET FOREIGN_KEY_CHECKS = 1;
