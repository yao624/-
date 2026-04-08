// 广告模板管理页面类型定义

import type { TemplateItem as ApiTemplateItem, TemplateListParams as ApiTemplateListParams } from '@/api/ad_template';

// 重新导出 API 类型
export type { ApiTemplateItem, ApiTemplateListParams };

// 从 API 数据转换后的显示数据结构（与 API 返回一致）
export interface AdTemplate {
  id: string;
  channel: string;
  name: string;
  templateId: string;
  description: string;
  creator: string;
  creatorId: string;
  updatedAt: string;
}

// 筛选参数类型（用于前端筛选）
export interface FilterParams {
  keyword?: string;
  channel?: string;
}

// 分页信息
export interface PaginationInfo {
  current: number;
  pageSize: number;
  total: number;
}
