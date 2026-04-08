/*
 Navicat Premium Data Transfer

 Source Server         : 127.0.0.1
 Source Server Type    : MySQL
 Source Server Version : 80012
 Source Host           : 127.0.0.1:3306
 Source Schema         : laravel

 Target Server Type    : MySQL
 Target Server Version : 80012
 File Encoding         : 65001

 Date: 02/04/2026 16:17:09
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for meta_permissions
-- ----------------------------
DROP TABLE IF EXISTS `meta_permissions`;
CREATE TABLE `meta_permissions`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '权限父级id',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '权限名称',
  `slug` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '权限标识，如 user:create, user:edit',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menu' COMMENT '类型: menu, button，data',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态，1：启用，0：禁用',
  `alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '别名，用于国际化如page.name',
  `icon` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '图标class',
  `path` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '路由路径，如 /tenants',
  `component` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '组件路径，如views/ads/index_v2.vue',
  `redirect` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '重定向地址',
  `hide_in_menu` tinyint(1) NULL DEFAULT 0 COMMENT '是否在菜单中隐藏 ,1:是，0：否',
  `hide_children_in_menu` tinyint(1) NULL DEFAULT 0 COMMENT '是否隐藏子菜单 ,1:是，0：否',
  `hide_in_breadcrumb` tinyint(1) NULL DEFAULT 0 COMMENT '是否在面包屑中隐藏 ,1:是，0：否',
  `sort` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序，越小越靠前',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_permissions_slug`(`slug`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 185 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'meta_permissions 权限表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of meta_permissions
-- ----------------------------
INSERT INTO `meta_permissions` VALUES (1, 0, '系统管理', 'system', 'menu', 1, 'pages.system.title', 'SettingOutlined', '/system', 'RouteView', NULL, 0, 0, 0, 3, '2026-03-24 16:21:20', '2026-04-01 10:20:46');
INSERT INTO `meta_permissions` VALUES (2, 1, '用户列表', 'user:query', 'menu', 1, 'pages.modal.users', 'UserOutlined', '/system/user-list', '@/views/system/user-list.vue', NULL, 0, 0, 0, 2, '2026-03-24 16:21:20', '2026-04-01 05:43:56');
INSERT INTO `meta_permissions` VALUES (3, 1, '角色列表', 'role:query', 'menu', 1, 'pages.system.role-list.title', 'SafetyCertificateOutlined', '/system/role-list', '@/views/system/role-list.vue', NULL, 0, 0, 0, 3, '2026-03-24 16:21:20', '2026-04-01 05:44:10');
INSERT INTO `meta_permissions` VALUES (4, 1, '权限列表', 'menu:query', 'menu', 1, 'pages.system.permission.title', 'SafetyCertificateOutlined', '/system/permission-list', '@/views/system/permission.vue', NULL, 0, 0, 0, 5, '2026-03-24 16:21:20', '2026-04-01 08:05:37');
INSERT INTO `meta_permissions` VALUES (11, 3, '新增角色', 'role:add', 'button', 1, 'pages.user.add', 'add', '/roles/create', NULL, NULL, 0, 0, 0, 0, '2026-03-25 16:01:54', NULL);
INSERT INTO `meta_permissions` VALUES (12, 1, '防盗刷配置', 'source', 'menu', 1, NULL, 'SecurityScanOutlined', '/system/fraud-config', '@/views/fraud_config/index_v2.vue', NULL, 0, 0, 0, 250, '2026-03-25 16:02:02', NULL);
INSERT INTO `meta_permissions` VALUES (14, 1, 'FB API Token', 'test', 'menu', 1, NULL, 'ApiOutlined', '/system/fb-api-token', '@/views/fb-api-token/index_v2.vue', NULL, 0, 0, 0, 99, '2026-03-25 08:24:03', '2026-03-31 08:56:51');
INSERT INTO `meta_permissions` VALUES (16, 3, '编辑角色', 'role:edit', 'button', 1, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-25 10:06:11', '2026-03-25 10:06:11');
INSERT INTO `meta_permissions` VALUES (17, 2, '新增用户', 'user:create', 'button', 1, 'pages.user.add', NULL, NULL, NULL, NULL, 0, 0, 0, 0, '2026-03-26 09:23:35', '2026-03-26 09:28:21');
INSERT INTO `meta_permissions` VALUES (18, 0, '资源管理', NULL, 'menu', 1, 'pages.resource.title', 'DatabaseOutlined', '/resource', 'RouteView', NULL, 0, 0, 0, 10, '2026-03-26 09:40:39', '2026-03-26 10:32:05');
INSERT INTO `meta_permissions` VALUES (19, 18, '像素', 'pixels:query', 'menu', 1, 'pages.pixels.title', 'CodeOutlined', '/resource/pixels', '@/views/pixels/index.vue', NULL, 0, 0, 0, 0, '2026-03-26 10:31:33', '2026-03-26 10:31:33');
INSERT INTO `meta_permissions` VALUES (20, 0, '仪表板', NULL, 'menu', 1, 'pages.dashboard.title', 'DashboardOutlined', '/workplace', '@/views/dashboard/workplace/index_v2.vue', NULL, 0, 0, 0, 0, '2026-04-01 18:22:40', '2026-04-01 10:23:55');
INSERT INTO `meta_permissions` VALUES (21, 18, '标签管理', 'tags:query', 'menu', 1, 'pages.common.tags', 'FileOutlined', '/tags/index', '@/views/tags/index.vue', NULL, 0, 0, 0, 0, '2026-03-27 07:40:28', '2026-03-27 07:41:44');
INSERT INTO `meta_permissions` VALUES (22, 0, '素材库', NULL, 'menu', 1, 'pages.materialLibrary.title', 'FolderOutlined', '/promotion/material-library', '@/views/material-library/index.vue', NULL, 0, 0, 0, 99, '2026-03-27 08:04:06', '2026-04-01 08:06:26');
INSERT INTO `meta_permissions` VALUES (23, 18, '广告账户', 'ad-account:query', 'menu', 1, '广告账户', 'FileOutlined', '/ad-account', '@/views/ad_account/index.vue', NULL, 0, 0, 0, 0, '2026-03-30 02:48:12', '2026-03-30 05:32:53');
INSERT INTO `meta_permissions` VALUES (24, 18, '广告模版', 'template:list', 'menu', 1, '广告模版', 'FileOutlined', '/ad-template', '@/views/ad_template/index.vue', NULL, 0, 0, 0, 0, '2026-03-30 02:50:51', '2026-03-30 05:33:12');
INSERT INTO `meta_permissions` VALUES (25, 18, '媒体素材库', 'manage:list', 'menu', 1, '媒体素材库', 'FileOutlined', '/channel-manage', '@/views/channelmanage/index.vue', NULL, 0, 0, 0, 0, '2026-03-30 07:22:25', '2026-04-02 03:47:16');
INSERT INTO `meta_permissions` VALUES (26, 18, '素材编辑', 'material_editor_manage:list', 'menu', 1, '素材编辑', 'FileOutlined', '/material-editor-manage', '@/views/material_editor_manage/index.vue', NULL, 0, 0, 0, 0, '2026-03-30 07:44:49', '2026-03-30 07:44:49');
INSERT INTO `meta_permissions` VALUES (44, 0, '看板', 'promotion-dashboard', 'menu', 1, 'pages.promotionDashboard.title', 'DashboardOutlined', '/promotion/dashboard', '@/views/promotion/dashboard/index.vue', NULL, 0, 0, 0, 310, '2026-04-01 16:52:06', '2026-04-01 16:53:43');
INSERT INTO `meta_permissions` VALUES (45, 0, '创意', 'creative', 'menu', 1, 'pages.creative.title', 'BulbOutlined', '/creative', 'RouteView', NULL, 0, 0, 0, 320, '2026-04-01 16:52:06', '2026-04-01 16:53:43');
INSERT INTO `meta_permissions` VALUES (46, 0, '工具', 'instrument', 'menu', 1, '工具', 'ToolOutlined', '/instrument', 'RouteView', '/instrument/facebook-pages', 0, 0, 0, 330, '2026-04-01 16:52:06', '2026-04-01 16:53:43');
INSERT INTO `meta_permissions` VALUES (47, 45, '文案', 'copywritings:query', 'menu', 1, 'pages.copywritings.title', 'FileTextOutlined', '/creative/copywritings', '@/views/copywritings/index_v2.vue', NULL, 0, 0, 0, 321, '2026-04-01 16:52:06', '2026-04-01 16:53:43');
INSERT INTO `meta_permissions` VALUES (48, 46, 'Facebook主页', 'instrument:facebook-pages', 'menu', 1, 'Facebook主页', 'FacebookOutlined', '/instrument/facebook-pages', '@/views/instrument/facebook-pages.vue', NULL, 0, 0, 0, 331, '2026-04-01 16:52:06', '2026-04-01 16:53:43');
INSERT INTO `meta_permissions` VALUES (49, 46, '任务中心', 'instrument:task-center', 'menu', 1, '任务中心', 'ProjectOutlined', '/instrument/task-center', '@/views/instrument/task-center.vue', NULL, 0, 0, 0, 332, '2026-04-01 16:52:06', '2026-04-01 16:53:43');
INSERT INTO `meta_permissions` VALUES (50, 46, '通知中心', 'instrument:notification-center', 'menu', 1, '通知中心', 'BellOutlined', '/instrument/notification-center', '@/views/instrument/notification-center.vue', NULL, 0, 0, 0, 333, '2026-04-01 16:52:06', '2026-04-01 16:53:43');
INSERT INTO `meta_permissions` VALUES (51, 46, '定时报表', 'instrument:scheduled-report', 'menu', 1, '定时报表', 'FileTextOutlined', '/instrument/scheduled-report', '@/views/instrument/scheduled-report.vue', NULL, 0, 0, 0, 334, '2026-04-01 16:52:06', '2026-04-01 16:53:43');
INSERT INTO `meta_permissions` VALUES (52, 46, '新增报表', 'instrument:scheduled-report-create', 'menu', 1, '新增报表', NULL, '/instrument/scheduled-report/create', '@/views/instrument/scheduled-report-edit.vue', NULL, 0, 0, 0, 335, '2026-04-01 17:19:53', '2026-04-01 17:19:53');
INSERT INTO `meta_permissions` VALUES (53, 46, '编辑报表', 'instrument:scheduled-report-edit', 'menu', 1, '编辑报表', NULL, '/instrument/scheduled-report/edit', '@/views/instrument/scheduled-report-edit.vue', NULL, 0, 0, 0, 336, '2026-04-01 17:19:53', '2026-04-01 17:19:53');
INSERT INTO `meta_permissions` VALUES (173, 0, '推广', 'promotion:query', 'menu', 1, 'pages.promotion.title', 'RocketOutlined', '/promotion', '@/views/ads/meta-ad-creation/index.vue', NULL, 0, 0, 0, 1, '2026-04-01 09:12:12', '2026-04-01 10:20:20');
INSERT INTO `meta_permissions` VALUES (174, 0, '推广报表', 'promotionReports:query', 'menu', 1, 'pages.promotionReports.title', 'BarChartOutlined', '/promotion/reports', '@/views/promotion/reports/index.vue', NULL, 0, 0, 0, 2, '2026-04-01 10:18:23', '2026-04-01 10:20:54');
INSERT INTO `meta_permissions` VALUES (175, 0, '自动优化', NULL, 'menu', 1, 'pages.autoOptimization.title', 'RobotOutlined', '/promotion/auto-optimization', 'RouteView', NULL, 0, 1, 0, 5, '2026-04-01 10:27:24', '2026-04-01 11:35:21');
INSERT INTO `meta_permissions` VALUES (176, 175, 'CreateAutoRule', NULL, 'menu', 1, 'pages.createAutoRule.title', NULL, 'create-rule', '@/views/promotion/auto-optimization/create-rule.vue', NULL, 1, 0, 0, 0, '2026-04-01 10:33:23', '2026-04-01 12:16:05');
INSERT INTO `meta_permissions` VALUES (177, 175, '首页', NULL, 'menu', 1, 'pages.autoOptimization.title', NULL, '/promotion/auto-optimization', '@/views/promotion/auto-optimization/index.vue', NULL, 0, 0, 0, 0, '2026-04-01 12:21:58', '2026-04-01 12:21:58');
INSERT INTO `meta_permissions` VALUES (178, 18, '主页管理', NULL, 'menu', 1, 'pages.pages.title', 'FileOutlined', '/resource/pages', '@/views/pages/index.vue', NULL, 0, 0, 0, 0, '2026-04-01 12:27:23', '2026-04-01 12:27:23');
INSERT INTO `meta_permissions` VALUES (179, 18, '表单', NULL, 'menu', 1, '表单', 'FormOutlined', '/resource/forms', '@/views/page-forms/index_v2.vue', NULL, 0, 0, 0, 0, '2026-04-01 12:28:49', '2026-04-01 12:28:49');
INSERT INTO `meta_permissions` VALUES (180, 18, '商务管理平台', NULL, 'menu', 1, 'pages.business.manager.title', 'ShopOutlined', '/resource/business-manager', '@/views/business_manager/index_v2.vue', NULL, 0, 0, 0, 0, '2026-04-01 12:29:56', '2026-04-01 12:29:56');
INSERT INTO `meta_permissions` VALUES (181, 0, '日志管理', NULL, 'menu', 1, 'pages.log.title', 'FileTextOutlined', '/logs', 'RouteView', NULL, 0, 0, 0, 999, '2026-04-01 12:34:34', '2026-04-01 12:38:26');
INSERT INTO `meta_permissions` VALUES (182, 181, '登录日志', NULL, 'menu', 1, 'pages.log.login.title', 'LoginOutlined', '/logs/login', '@/views/logs/login-log.vue', NULL, 0, 0, 0, 0, '2026-04-01 12:35:16', '2026-04-01 12:35:16');
INSERT INTO `meta_permissions` VALUES (183, 181, '用户日志', NULL, 'menu', 1, 'pages.log.user.title', 'UserOutlined', '/logs/user', '@/views/logs/user-log.vue', NULL, 0, 0, 0, 0, '2026-04-01 12:36:28', '2026-04-01 12:36:28');
INSERT INTO `meta_permissions` VALUES (184, 181, '系统日志', NULL, 'menu', 1, 'pages.log.system.title', 'SettingOutlined', '/logs/system', '@/views/logs/system-log.vue', NULL, 0, 0, 0, 0, '2026-04-01 12:37:18', '2026-04-01 12:37:18');

SET FOREIGN_KEY_CHECKS = 1;
