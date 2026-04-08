import request from '@/utils/request';

export async function queryFB_AD_AccountsApi(params?: { [key: string]: any }) {
  // Ensure with-campaign parameter is included if not already set
  const queryParams = { ...params };
  if (queryParams && typeof queryParams === 'object' && !queryParams['with-campaign']) {
    queryParams['with-campaign'] = false;
  }

  return request.get('/fb-ad-accounts', {
    params: queryParams,
  });
}

export async function queryFB_AD_AccountOneApi(params?: { [key: string]: any }) {
  const { id, ...queryParams } = params || {};

  // 仅在未传时默认 true；显式 false 必须保留，否则后端会走 WithCampaign 分支且不 load fbAccounts，个号/像素为空
  if (queryParams['with-campaign'] === undefined) {
    queryParams['with-campaign'] = true;
  }

  return request.get(`/fb-ad-accounts/${id}`, {
    params: queryParams,
  });
}

export async function updateFB_AD_AccountsOneApi(params: Record<string, any>) {
  const url = `fb-ad-accounts/${params.id}`;
  return request(url, {
    method: 'PATCH',
    data: params,
  });
}

export async function archiveFbAdAccounts(params: Record<string, any>) {
  const url = 'fb-ad-accounts/archive';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function unarchiveFbAdAccounts(params: Record<string, any>) {
  const url = 'fb-ad-accounts/unarchive';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function enableRule(params: Record<string, any>) {
  const url = 'fb-ad-accounts/enable-rule';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function disableRule(params: Record<string, any>) {
  const url = 'fb-ad-accounts/disable-rule';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function fetchData(data) {
  return request('/fb-ad-accounts/fetch-data', {
    method: 'post',
    data: data,
  });
}

export async function fetchDataRecently(data) {
  return request('/fb-ad-accounts/fetch-data-recently', {
    method: 'post',
    data: data,
  });
}

export async function toggleTopup(params: {
  source_ids: string[];
  value: boolean;
}) {
  return request('/fb-ad-accounts/toggle-topup', {
    method: 'POST',
    data: params,
  });
}

export async function syncAccountSpendInfo(params: {
  source_id: string;
}) {
  return request('/fb-ad-accounts/sync-account-spend-info', {
    method: 'POST',
    data: params,
  });
}

export async function fetchAdAccountInfo(data) {
  return request('/fb-ad-accounts/fetch-ad-account-info', {
    method: 'post',
    data: data,
  });
}

export async function getFbAdAccountsValidTags() {
  return request('/tags/fbadaccounts/', {
    method: 'GET',
    data: {},
  });
}

export async function getCampaignTags() {
  return request('/tags/fbcampaigns/', {
    method: 'GET',
    data: {},
  });
}

export async function assignUsers(data) {
  return request('/fb-ad-accounts/assign-users', {
    method: 'post',
    data: data,
  });
}

export async function removeUsers(data) {
  return request('/fb-ad-accounts/remove-users', {
    method: 'post',
    data: data,
  });
}

export async function syncAdAccountData(data) {
  return request('/fb-ad-accounts/sync-ad-account-data', {
    method: 'post',
    data: data,
  });
}

export async function syncCampaignData(data) {
  return request('/fb-ad-accounts/sync-campaign-data', {
    method: 'post',
    data: data,
  });
}

export async function syncAdsetData(data) {
  return request('/fb-ad-accounts/sync-adset-data', {
    method: 'post',
    data: data,
  });
}

export async function syncAdData(data) {
  return request('/fb-ad-accounts/sync-ad-data', {
    method: 'post',
    data: data,
  });
}

export async function autoSyncConfig(data) {
  return request('/fb-ad-accounts/auto-sync', {
    method: 'post',
    data: data,
  });
}

export async function setAccountSpendCap(data: {
  ids: string[];
  cap_type: 'amount' | 'reset' | 'remove';
  cap_value?: number;
}) {
  return request('/fb-ad-accounts/set-account-spend-cap', {
    method: 'post',
    data: data,
  });
}

export async function updateAdAccountFilters(data: {
  ids: string[];
  filters: Array<{
    field: string;
    operator: string;
    value: string | string[];
    scope: string[];
  }>;
}) {
  return request('/fb-ad-accounts/update-filters', {
    method: 'post',
    data: data,
  });
}

export async function clearAdAccountFilters(data: {
  ids: string[];
}) {
  return request('/fb-ad-accounts/clear-filters', {
    method: 'post',
    data: data,
  });
}
