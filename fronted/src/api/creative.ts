import request from '@/utils/request';

export interface ImageTemplate {
  id?: string;
  name: string;
  width: number;
  height: number;
  json: any; // 画布JSON数据
  dynamicVariables?: any;
  previewImage?: string;
  variableCount?: number;
  description?: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface ImageTemplateListParams {
  template_name?: string;
  canvas_width?: number;
  canvas_height?: number;
  pageSize?: number;
  pageNo?: number;
}

export interface ImageTemplateListResponse {
  data: ImageTemplate[];
  pageSize: number;
  pageNo: number;
  totalPage: number;
  totalCount: number;
}

export interface ApiResponse<T = any> {
  status: boolean;
  message: string;
  data: T;
}

/**
 * 获取图片模板列表
 */
export function getImageTemplateList(params: ImageTemplateListParams): Promise<ApiResponse<ImageTemplateListResponse>> {
  return request.get('/image-templates/list', { params });
}

/**
 * 获取图片模板详情
 */
export function getImageTemplateDetail(id: string | number): Promise<ApiResponse<ImageTemplate>> {
  return request.get('/image-templates/detail', { params: { id } });
}

/**
 * 创建图片模板
 */
export function createImageTemplate(data: ImageTemplate): Promise<ApiResponse<{ id: string }>> {
  return request.post('/image-templates/create', data);
}

/**
 * 更新图片模板
 */
export function updateImageTemplate(data: ImageTemplate & { id: string | number }): Promise<ApiResponse<{ id: string }>> {
  return request.post('/image-templates/update', data);
}

/**
 * 删除图片模板
 */
export function deleteImageTemplate(id: string | number): Promise<ApiResponse> {
  return request.post('/image-templates/delete', { id });
}

/**
 * 批量删除图片模板
 */
export function batchDeleteImageTemplates(ids: (string | number)[]): Promise<ApiResponse<{ deletedCount: number }>> {
  return request.post('/image-templates/batch-delete', { ids });
}

/**
 * 复制图片模板
 */
export function copyImageTemplate(id: string | number): Promise<ApiResponse<{ id: string; name: string }>> {
  return request.post('/image-templates/copy', { id });
}
