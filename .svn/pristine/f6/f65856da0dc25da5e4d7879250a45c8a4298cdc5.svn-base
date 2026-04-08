import request from '@/utils/request';

export interface TemplateListParams {
  channel?: string;
  name?: string;
  description?: string;
  user_id?: string;
  page?: number;
  pageSize?: number;
}

export interface TemplateItem {
  id: string;
  channel: string;
  name: string;
  templateId: string;
  description: string;
  creator: string;
  creatorId: string;
  updatedAt: string;
}

export interface TemplateListResponse {
  status: boolean;
  message: string;
  data: {
    data: TemplateItem[];
    pageSize: number;
    pageNo: number;
    totalPage: number;
  };
}

/**
 * 更新模板请求参数
 */
export interface UpdateTemplateParams {
  template_id: string;  // 模板ID（必填）
  name?: string;        // 模板名称（可选，最大255字符）
  description?: string; // 模板描述（可选，最大500字符）
}

/**
 * 更新模板响应
 */
export interface UpdateTemplateResponse {
  status: boolean;
  message: string;
  data: null;
}

/**
 * 获取广告模板列表
 */
export async function getTemplateList(params: TemplateListParams): Promise<TemplateListResponse> {
  return request.get('templates/list', { params });
}

/**
 * 删除广告模板
 * DELETE /api/v2/templates/delete
 */
export async function deleteTemplates(params: { template_ids: string[] }): Promise<{
  status: boolean;
  message: string;
  data: null;
}> {
  return request.delete('templates/delete', { data: params });
}

/**
 * 更新广告模板
 * PUT /api/v2/templates/update
 */
export async function updateTemplate(params: UpdateTemplateParams): Promise<UpdateTemplateResponse> {
  return request.put('templates/update', params);
}

/**
 * 获取广告模板详情
 */
export async function getTemplateDetail(id: string): Promise<any> {
  return request.get(`templates/${id}`);
}
