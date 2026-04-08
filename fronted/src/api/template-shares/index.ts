import request from '@/utils/request';

/**
 * 分享模板请求参数
 */
export interface ShareTemplateParams {
  template_ids: string[];  // 模板ID列表
  user_ids: string[];      // 用户ID列表
}

/**
 * 分享模板响应
 */
export interface ShareTemplateResponse {
  status: boolean;
  message: string;
  data: null;
}

/**
 * 分享模板给用户
 * POST /api/v2/template-shares/share
 */
export async function shareTemplates(params: ShareTemplateParams): Promise<ShareTemplateResponse> {
  return request('template-shares/share', {
    method: 'POST',
    data: params,
  });
}
