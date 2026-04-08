import request from '@/utils/request';

export async function queryListApi(params?: { [key: string]: any }) {
  return request.get('/fb-ad-account', {
    params,
  });
}

export async function queryOneApi(id?: string) {
  return request.get('/fb-ad-account/' + id);
}

export async function enableOneApi(params: Record<string, any>) {
  return request('/fb-ad-account/' + params.id, {
    method: 'PUT',
    data: {
      ...params,
      enable_rule: params.enable_rule ? false : true,
    },
  });
}

export async function deleteOneApi(id?: string) {
  return request('/fb-ad-account/' + id, {
    method: 'DELETE',
    data: {},
  });
}

export async function getAdAccountData(params: { ad_account_ids: string[] }) {
  return request.get('/fb-ad-accounts', {
    params: {
      ad_account_ids: params.ad_account_ids,
    },
  });
}

/**
 * 编辑单个广告账户
 * POST /api/v2/account-manage/ad-accounts/edit
 */
export async function editAdAccount(params: {
  id: string;
  owner?: string;
  assistants?: string[];
}) {
  return request('account-manage/ad-accounts/edit', {
    method: 'POST',
    data: params,
  });
}

/**
 * 批量编辑广告账户
 * POST /api/v2/account-manage/ad-accounts/batch-edit
 */
export async function batchEditAdAccounts(params: {
  ids: string[];
  owner?: string;
  assistants?: string[];
}) {
  return request('account-manage/ad-accounts/batch-edit', {
    method: 'POST',
    data: params,
  });
}
