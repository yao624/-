import request from '@/utils/request';

export async function queryPagesApi(params?: { [key: string]: any }) {
  try {
    return await request.get('/meta-fb/pages', { params });
  } catch (_e) {
    // 后端 500 时降级，避免主页管理页弹 Request Error
    return { data: [], totalCount: 0 };
  }
}

export async function updatePagesOneApi(params: Record<string, any>) {
  return request(`fb-pages/${params.id}`, {
    method: 'PATCH',
    data: params,
  });
}

export async function getPageForms(params?: Record<string, any>) {
  try {
    return await request(`fb-page-forms`, { params });
  } catch (_e) {
    // 后端 500 时降级，避免表单页弹 Request Error
    return { data: [], totalCount: 0 };
  }
}

export async function updatePageFormNote(id: string, notes: string) {
  return request.put(`fb-page-forms/${id}`, { notes });
}

export async function refreshPageToken(params) {
  return request.post('/fb-pages/refresh-token', {
    ...params,
  });
}

export async function queryMetaFbCommentsApi(params?: { [key: string]: any }) {
  try {
    return await request.get('/meta-fb/comments', { params });
  } catch (_e) {
    return { data: [], totalCount: 0 };
  }
}

export async function queryMetaFbKeywordPacksApi(params?: { [key: string]: any }) {
  try {
    return await request.get('/meta-fb/keyword-packs', { params });
  } catch (_e) {
    return { data: [], totalCount: 0 };
  }
}

export async function queryMetaFbPageOptionsApi() {
  try {
    return await request.get('/meta-fb/page-options');
  } catch (_e) {
    return { data: [] };
  }
}

export async function getMetaFbAutoRuleApi(pageId: string) {
  return request.get(`/meta-fb/pages/${pageId}/auto-rule`);
}

export async function saveMetaFbAutoRuleApi(pageId: string, payload: Record<string, any>) {
  return request.put(`/meta-fb/pages/${pageId}/auto-rule`, payload);
}

export async function batchSyncMetaFbPagesApi(payload: { page_ids?: number[] }) {
  return request.post('/meta-fb/pages/batch-sync-latest', payload);
}

export async function batchHideMetaFbCommentsApi(payload: { comment_ids: number[] }) {
  return request.post('/meta-fb/comments/batch-hide', payload);
}

export async function batchDeleteMetaFbCommentsApi(payload: { comment_ids: number[] }) {
  return request.post('/meta-fb/comments/batch-delete', payload);
}

export async function batchDeleteMetaFbKeywordPacksApi(payload: { pack_ids: number[] }) {
  return request.post('/meta-fb/keyword-packs/batch-delete', payload);
}

export async function batchActionMetaFbCommentsApi(payload: {
  comment_ids: number[];
  action: 'REPLY' | 'HIDE' | 'UNHIDE' | 'LIKE' | 'UNLIKE' | 'DELETE';
  reply_text?: string;
}) {
  return request.post('/meta-fb/comments/batch-action', payload);
}

export async function syncPageFormsApi(params) {
  return request.post('/fb-pages/sync-page-forms', {
    ...params,
  });
}

export async function createMetaFbKeywordPackApi(payload: {
  pack_name: string;
  keywords: string[];
  status?: string;
}) {
  return request.post('/meta-fb/keyword-packs', payload);
}

export async function updateMetaFbKeywordPackApi(
  packId: string,
  payload: { pack_name?: string; keywords?: string[]; status?: string },
) {
  return request(`/meta-fb/keyword-packs/${packId}`, {
    method: 'PATCH',
    data: payload,
  });
}

export async function deleteMetaFbKeywordPackApi(packId: string) {
  return request(`/meta-fb/keyword-packs/${packId}`, {
    method: 'DELETE',
  });
}

export async function getMetaFbKeywordPackKeywordsApi(packId: string) {
  return request.get(`/meta-fb/keyword-packs/${packId}/keywords`);
}
