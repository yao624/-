import request from '@/utils/request';

export async function queryAdAccountInsight(params: Record<string, any>) {
  const url = 'overview-ads/insights-ad-account';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function queryCampaignInsight(params: Record<string, any>) {
  const url = 'overview-ads/insights-campaign';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function queryAdsetInsight(params: Record<string, any>) {
  const url = 'overview-ads/insights-adset';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function queryAdInsight(params: Record<string, any>) {
  const url = 'overview-ads/insights-ad';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function batchUpdateFbObjectStatus(params: Record<string, any>) {
  const url = 'overview-ads/batch-update-object-status';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function UpdateFbObjectBudget(params: Record<string, any>) {
  const url = 'overview-ads/update-object-budget';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function CopyFbObject(params: Record<string, any>) {
  const url = 'overview-ads/copy-object';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function RenameFbObject(params: Record<string, any>) {
  const url = 'overview-ads/rename-object';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function getAdLogs() {
  const url = 'ad-logs';
  return request(url, {
    method: 'get',
  });
}

export async function UpdateBidAmountApi(params: Record<string, any>) {
  const url = 'overview-ads/update-bid-amount';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function UpdateBidStrategyApi(params: Record<string, any>) {
  const url = 'overview-ads/update-bid-strategy';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function deleteFbObject(params: Record<string, any>) {
  const url = 'fb-campaigns/delete';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function getLanguages() {
  const url = 'languages';
  return request(url, {
    method: 'GET',
  });
}

// 获取广告媒体（图片/视频）
export async function getAdsMedia(
  hash: string,
  id: string,
  type: 'image' | 'video',
  page?: string,
) {
  const url = `ads-media`;
  const params: any = {
    hash, // 媒体哈希值
    id, // ad_account_id
    type, // 媒体类型：image 或 video
  };

  // 对于视频类型，添加page参数（actor_id）
  if (type === 'video' && page) {
    params.page = page;
  }

  return request(url, {
    method: 'GET',
    params,
  });
}

// 上传素材到FB
export async function uploadMaterialToFB(materials: Array<{ ad_account: string; material_id: string }>) {
  return request.post('/materials/upload-to-fb', materials);
}

// 更新广告多语言配置
export async function updateFbAds(params: Array<{
  ad_account: string;
  id: string;
  payload: {
    object_story_spec?: any;
    asset_feed_spec?: any;
    url_tags?: string;
  };
}>) {
  return request('fb-ads/update', {
    method: 'POST',
    data: params,
  });
}

// 获取视频缩略图
export async function getVideoThumbnails(adAccount: string, videoId: string) {
  const url = 'video-thumbnail';
  return request(url, {
    method: 'GET',
    params: {
      ad_account: adAccount,
      video_id: videoId,
    },
  });
}

// 获取多语言广告预览链接
export async function getMultiLanguagePreview(params: {
  ad_account: string;
  ad_id: string;
  label_name: string;
}) {
  const url = 'fb-ads/multi-lang-preview';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

// 更新Adset定位设置
export const updateAdsetTargeting = (params: {
  targeting: any;
  ad_account: string;
  adset_id: string;
}) => {
  return request({
    url: 'fb-adsets/update-targeting',
    method: 'POST',
    data: params,
  });
};

// 搜索书签相关API
// 获取当前用户的所有搜索书签
export async function getSearchBookmarks(): Promise<{
  success: boolean;
  data: any[];
  message?: string;
}> {
  return request('search-bookmarks', {
    method: 'GET',
  }) as any;
}

// 创建新的搜索书签
export async function createSearchBookmark(params: {
  name: string;
  search_conditions: Record<string, any>;
  description?: string;
}): Promise<{
  success: boolean;
  data?: any;
  message?: string;
}> {
  return request('search-bookmarks', {
    method: 'POST',
    data: params,
  }) as any;
}

// 删除搜索书签
export async function deleteSearchBookmark(id: string): Promise<{
  success: boolean;
  message?: string;
}> {
  return request(`search-bookmarks/${id}`, {
    method: 'DELETE',
  }) as any;
}

// 获取指定的搜索书签
export async function getSearchBookmark(id: string) {
  return request(`search-bookmarks/${id}`, {
    method: 'GET',
  });
}

// 批量添加语言到广告
export async function addLanguagesToAds(params: {
  ad_source_ids: string[];
  language_count: number;
}) {
  return request('fb-ads/add-languages', {
    method: 'POST',
    data: params,
  });
}

// 设置广告的自动添加多语言开关
export async function setAutoAddLanguages(params: {
  ad_source_ids: string[];
  auto_add_languages: boolean;
}) {
  return request('fb-ads/set-auto-add-languages', {
    method: 'POST',
    data: params,
  });
}

// 批量复制广告
export async function copyAds(params: Array<{
  ad_id: string;
  count: number;
}>, mode: number = 1) {
  return request('fb-ads/copy', {
    method: 'POST',
    data: {
      ads: params,
      mode: mode,
    },
  });
}

// 复制广告到广告组
export async function copyAdToAdsets(params: {
  ad_source_id: string;
  adset_source_ids: string[];
}) {
  return request('fb-ads/copy-ad-2-adsets', {
    method: 'POST',
    data: params,
  });
}

// 批量更新预算
export async function batchUpdateObjectBudget(params: {
  items: Array<{
    id: string;
    object_type: 'campaign' | 'adset';
    budget_type: 'daily_budget' | 'lifetime_budget';
    budget: string;
  }>;
}) {
  return request('overview-ads/batch-update-object-budget', {
    method: 'POST',
    data: params,
  });
}

// 获取Facebook广告账户信息
export async function getFbAdAccounts(adAccountIds: string[]) {
  const params = new URLSearchParams();
  adAccountIds.forEach(id => {
    params.append('ad_account_ids[]', id);
  });

  return request(`fb-ad-accounts?${params.toString()}`, {
    method: 'GET',
  });
}
