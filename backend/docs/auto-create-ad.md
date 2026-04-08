# 自动创建广告（Auto Create Ad）- 需求/数据/接口对齐文档

适用范围：本项目 `#/promotion/auto-create-ad`（前端） + `backend`（Laravel）  
目标：实现“基于筛选规则，按频次自动创建广告”的模板化能力，并支持任务日志、失败重试、配额统计。

参考：AdsPolar 帮助中心《自动创建广告》`https://help.adspolar.com/docs/Auto-Ad-Create`

---

## 功能概述

自动创建广告 = **模板（Template）** + **筛选规则（Rules）** + **调度（Schedule）** + **任务执行与日志（Jobs/Logs）**。

### 业务价值
- 自动化投放：无需人工干预，按固定频次创建 Campaign / Adset / Ad
- 可控性：仅在模板“开启 + 有效期内”才执行
- 可审计：每次执行都有任务记录与创建结果（成功/失败/部分成功）
- 可复用：同一模板可复制、编辑、停用、删除

---

## 前端入口与页面范围

### 入口
- 路由：`/promotion/auto-create-ad`
- 页面文件：`fronted/src/views/promotion/auto-create-ad/index.vue`

### 列表字段（最小 MVP）
对应前端表格列：
- 状态：启用/停用（开关）
- 模板 ID
- 模板名称
- 通路（channel）
- 账号（支持多账户展示）
- 执行次数
- 最后执行时间（UTC+8）
- 创建人
- 创建时间（UTC+8）
- 备注

---

## 术语与核心对象

### Template（模板）
描述“如何创建广告”的配置集合，包含：
- 广告基础信息（目标、预算、出价、版位、定向、创意设置等）
- 选择素材/定向包的筛选条件（可多个创意组规则）
- 生效期与执行频次（时区、检查频次）

### Rule（筛选规则 / 创意组规则）
一个模板可以包含多个 Rule，每个 Rule 可产生 0~N 个“创意组/创建组”，并且可配置：
- 单创意组最大素材数
- 创意组最大分组数量
- 动态筛选 / 指定素材
- 只用新素材、最大使用次数、最小使用间隔
- 多规则间：过滤已选素材（避免重复）

### Job（执行任务）
模板在某个时刻触发一次执行，产生一个 Job，用于：
- 记录本次执行是否成功
- 记录创建了多少 Campaign/Adset/Ad
- 记录失败原因
- 支持“查看日志 / 一键重试失败”

### Job Item（任务明细）
Job 内部的细粒度对象：按“创建动作/创建对象”拆分（例如每个 Adset 或 Ad 一条），用于失败重试与详情展示。

---

## 数据来源与依赖

### 投放数据（用于筛选素材、指标过滤等）
本项目已有 “insights” 类表（投放指标明细），常用：
- `fb_ad_account_insights`
- `fb_campaign_insights`
- `fb_adset_insights`
- `fb_ad_insights`

> 注意：自动创建广告“创建动作”依赖媒体 API（Meta/Google/TikTok）。投放数据是“筛选条件/指标过滤”的输入之一，不等同于创建广告本身。

### 素材库（用于素材筛选）
可复用项目现有素材库数据（例如 `materials` / material-library 模块相关表与接口）。

---

## 数据库设计（建议）

本模块建议新增 4~5 张表（模板/规则/任务/明细/素材使用记录）。  
说明：看板配置表你已存在；自动创建广告属于“模板+调度+任务中心”范式，建议独立表，便于后期扩展到多渠道。

### 1) 模板表：`meta_auto_create_ad_templates`

**用途**：存放模板主信息、调度信息、基础配置快照。

字段建议：
- `id` ULID 主键
- `name` string 模板名称
- `channel` string 渠道：`facebook|google|tiktok`
- `status` string：`active|inactive`（也可扩展 `draft|expired`）
- `language` string|null（若模板与多语言配置绑定，可保留；否则不必）
- `creator_id`（关联 `users.id`）
- `account_ids` json（支持 1~N 个广告账户）
- `base_config` json（广告基础配置：目标/预算/出价/版位/定向/创意设置等）
- `timezone` string（建议与账户时区一致）
- `check_frequency` string（例如：`15m|30m|1h|1d`）
- `effective_start_at` datetime|null
- `effective_end_at` datetime|null
- `next_run_at` datetime|null（调度器用）
- `last_run_at` datetime|null
- `exec_count` int 默认 0
- `remark` text|null
- `created_at` `updated_at` `deleted_at`

索引建议：
- (`status`, `next_run_at`)
- (`creator_id`, `created_at`)

### 2) 规则表：`meta_auto_create_ad_template_rules`

**用途**：一个模板 N 条 Rule，描述“素材/定向包如何筛选 & 如何分组”。

字段建议：
- `id` ULID
- `template_id` ULID FK
- `rule_name` string|null
- `priority` int（规则顺序）
- `filter_mode` string：`dynamic|specified`（动态筛选/指定素材）
- `asset_type` string：`video|image|both`
- `creative_group_max_count` int|null（创意组最大分组数量）
- `max_assets_per_group` int|null（单创意组内最大素材数量）
- `folder_ids` json|null（素材文件夹范围，含子文件夹逻辑由服务层展开）
- `dynamic_filters` json|null（筛选条件/指标过滤：标签、评分、投放指标阈值等）
- `only_new_assets` bool 默认 false
- `max_usage_count` int|null
- `min_reuse_interval_days` int|null
- `exclude_assets_selected_by_other_rules` bool 默认 false
- `specified_asset_ids` json|null（指定素材模式）
- `targeting_package_ids` json|null（若按定向包创建）
- `created_at` `updated_at`

索引建议：
- (`template_id`, `priority`)

### 3) 任务表：`meta_auto_create_ad_jobs`

**用途**：记录每次执行结果（类似任务中心）。

字段建议：
- `id` ULID
- `template_id` ULID
- `status` string：`queued|processing|success|partial_fail|fail`
- `run_at` datetime（计划执行时间）
- `started_at` datetime|null
- `finished_at` datetime|null
- `created_campaign_count` int 默认 0
- `created_adset_count` int 默认 0
- `created_ad_count` int 默认 0
- `payload` json（本次执行快照：模板/规则/筛选命中素材/目标账户等）
- `error_message` text|null
- `operator_id`（系统执行可为空；手动重试可记录用户）
- `created_at` `updated_at`

索引建议：
- (`template_id`, `created_at`)
- (`status`, `created_at`)

### 4) 任务明细表：`meta_auto_create_ad_job_items`

**用途**：把一次 Job 拆成多个可追踪的创建动作，支持“失败重试”。

字段建议：
- `id` ULID
- `job_id` ULID
- `rule_id` ULID|null
- `account_id` string（媒体账户 id / 或本系统 ad_account source_id）
- `object_type` string：`campaign|adset|ad`
- `object_name` string|null
- `object_source_id` string|null（媒体返回 id）
- `status` string：`success|fail`
- `request_payload` json|null
- `response_payload` json|null
- `error_message` text|null
- `created_at` `updated_at`

索引建议：
- (`job_id`, `status`)
- (`account_id`, `created_at`)

### 5) 素材使用记录表：`meta_auto_create_ad_asset_usages`（可选但强烈建议）

**用途**：实现“只用新素材 / 最大使用次数 / 最小使用间隔”。

字段建议：
- `id` ULID
- `template_id` ULID
- `rule_id` ULID
- `asset_id` ULID/string（对应素材库的素材 id）
- `job_id` ULID
- `account_id` string
- `used_at` datetime

索引建议：
- (`template_id`, `rule_id`, `asset_id`)
- (`asset_id`, `used_at`)

---

## 状态枚举（建议统一）

### Template.status
- `active`：启用（允许调度执行）
- `inactive`：停用（不执行）

### Job.status
- `queued`：已入队等待执行
- `processing`：执行中
- `success`：全部成功
- `partial_fail`：部分成功
- `fail`：全部失败或关键步骤失败

### JobItem.status
- `success`
- `fail`

---

## 执行流程（MVP）

### 触发条件
模板满足以下条件才会执行：
- `status = active`
- 当前时间在有效期内（若配置了 `effective_start_at/effective_end_at`）
- 到达 `next_run_at`

### 执行步骤（建议）
1. 创建 `Job(status=queued)`，写入模板快照 `payload`
2. 标记 `Job(status=processing, started_at=now)`
3. 对每个账户、每条规则：
   - 拉取候选素材/定向包
   - 按分组规则切分为创意组（受 `creative_group_max_count`/`max_assets_per_group` 约束）
   - 应用“去重/只用新素材/最大使用次数/最小间隔”
4. 调用媒体 API 创建：
   - Campaign
   - Adset
   - Ad
   并对每一步生成 `JobItem`
5. 汇总创建数量，写入 `exec_count++`、`last_run_at`
6. 计算下一次执行时间写入 `next_run_at`
7. `Job` 结束：写 `finished_at` + `status(success/partial_fail/fail)`

---

## 接口设计（建议 /v2 前缀，需 auth:sanctum）

### 1) 模板列表
`GET /api/v2/meta-auto-create-ad/templates`

Query：
- `keyword`（模板名模糊）
- `status`（active/inactive）
- `creator_id`
- `channel`
- `page` `pageSize`

Response（示例）：
```json
{
  "data": [
    {
      "id": "01J...",
      "status": "active",
      "name": "自动筛选素材-北美",
      "channel": "facebook",
      "account_ids": ["act_123", "act_456"],
      "exec_count": 12,
      "last_run_at": "2026-04-07 10:00:00",
      "creator": "Z",
      "created_at": "2026-04-01 12:00:00",
      "remark": "只用新素材"
    }
  ],
  "total": 1
}
```

### 2) 创建模板
`POST /api/v2/meta-auto-create-ad/templates`

Body（最小）：
- `name`
- `channel`
- `account_ids`
- `timezone`
- `check_frequency`
- `effective_start_at`/`effective_end_at`（可选）
- `base_config`
- `rules[]`

### 3) 模板详情
`GET /api/v2/meta-auto-create-ad/templates/{id}`

### 4) 更新模板
`PATCH /api/v2/meta-auto-create-ad/templates/{id}`

### 5) 启停模板
`PATCH /api/v2/meta-auto-create-ad/templates/{id}/status`
Body：`{ "status": "active" }`

### 6) 删除模板
`DELETE /api/v2/meta-auto-create-ad/templates/{id}`

### 7) 查看执行日志（任务列表）
`GET /api/v2/meta-auto-create-ad/templates/{id}/jobs`

### 8) 查看任务详情（含 items）
`GET /api/v2/meta-auto-create-ad/jobs/{jobId}`

### 9) 一键重试失败
`POST /api/v2/meta-auto-create-ad/jobs/{jobId}/retry-failed`

行为：
- 创建一个新 Job
- 仅把旧 Job 中 `JobItems(status=fail)` 重新入队执行

---

## 与“任务中心”整合（可选方案）

如果希望复用现有 `meta_task_jobs` / `meta_task_job_items` / `meta_task_operation_logs`：
- 自动创建广告的每次执行也写入 `meta_task_jobs.type = "AUTO_CREATE_AD"`
- `payload` 内保存 `template_id` 与创建快照
- 列表/详情可直接复用任务中心 UI 与接口风格

优点：少建表、少做 UI  
缺点：模板仍需单独表；并且任务中心字段命名可能需要适配自动创建广告的展示字段

---

## 权限与审计（建议）
- 所有接口走 `auth:sanctum`
- 模板 CRUD：仅创建者可见/可操作（或管理员可见全部）
- 任务日志：至少创建者可见；重试动作写入操作日志（operator_id）

---

## 边界与约束（建议落地）
- 频次最小粒度：15 分钟（或按产品约束）
- 多账户：同一模板可绑定多个账户，执行时逐账户独立创建
- 配额：按月创建上限（前端提示“本月已创建…上限…”），建议后端提供统计接口：
  - `GET /api/v2/meta-auto-create-ad/quota/monthly-summary`
- 防重复：同一 `template_id + run_at` 需幂等（避免调度器重复触发）
- 失败策略：媒体 API 不稳定时允许重试，失败项可单独重试

