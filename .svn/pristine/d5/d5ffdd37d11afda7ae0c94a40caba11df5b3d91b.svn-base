import request from '@/utils/request';

/** 用服务端配置的 BM + Graph token 从 Meta 拉取账户/个号/主页/像素并写入业务库，再调 GET /fb-ad-accounts 即有数据 */
export async function syncMetaAdCreationBmGraphAssetsApi(data?: { bm_id?: string }) {
  return request.post('/meta-ad-creation/sync-bm-graph-assets', data ?? {});
}

/**
 * 个号列表：后端走 Graph GET /{bm-id}/business_users（含 user{id}），与广告账户无关。
 * bm_source_id 缺省由后端使用配置的 BM。
 */
export async function getMetaAdCreationBmPersonalAccountsApi(params?: { bm_source_id?: string }) {
  return request.get('/meta-ad-creation/bm-personal-accounts', { params: params ?? {} });
}

/** Meta Marketing API：GET /{ad-account-id}/targetingbrowse（需 fb_ad_account_id → act_） */
export async function targetingBrowseApi(params: {
  fb_ad_account_id: string;
  targeting_category: 'demographics' | 'behaviors' | 'interests';
  parent_key?: string;
  locale?: string;
}) {
  return request.get('/meta-ad-creation/targeting-browse', { params });
}

/** Meta Marketing API：GET /{ad-account-id}/targetingsearch（详细定位关键词） */
export async function targetingSearchDetailedApi(params: {
  fb_ad_account_id: string;
  q: string;
  limit_type?: string;
  /** Graph 默认条数，后端默认 50 */
  limit?: number;
  locale?: string;
}) {
  return request.get('/meta-ad-creation/targeting-search-detailed', { params });
}
