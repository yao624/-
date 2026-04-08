import request from '@/utils/request';

// 账号管理相关接口
export interface AccountManageListParams {
  platform: 'meta' | 'google' | 'tiktok';
  page?: number;
  pageSize?: number;
  keyword?: string;
  autoBind?: boolean;
  personalAccount?: string;
  authorizer?: string;
  authStatus?: 'authorized' | 'unauthorized' | 'expired' | 'failed' | 'pending';
  authTimeStart?: string;
  authTimeEnd?: string;
}

export interface AccountManageAdAccountsParams extends Omit<AccountManageListParams, 'autoBind' | 'authorizer'> {
  accountStatus?: string;
  owner?: number;
  assistant?: number;
  bm?: string;
}

// 用户选项
export interface UserOption {
  label: string;
  value: number;
  name: string;
  email: string;
}

// 用户账号列表
export async function getAccountsApi(params: AccountManageListParams) {
  return request.get('/account-manage/accounts', {
    params,
  });
}

// 广告账户列表
export async function getAdAccountsApi(params: AccountManageAdAccountsParams) {
  return request.get('/account-manage/ad-accounts', {
    params,
  });
}

// 获取用户选项列表
export async function getUserOptionsApi() {
  return request.get('/user/options');
}

/**
 * 编辑单个广告账户
 * POST /api/v2/account-manage/ad-accounts/edit
 */
export async function editAdAccountApi(params: {
  id: string;
  owner?: string | null;
  assistants?: string[] | null;
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
export async function batchEditAdAccountsApi(params: {
  ids: string[];
  owner?: string | null;
  assistants?: string[] | null;
}) {
  return request('account-manage/ad-accounts/batch-edit', {
    method: 'POST',
    data: params,
  });
}

/**
 * 删除单个广告账户
 * DELETE /api/v2/account-manage/ad-accounts/{id}
 */
export async function deleteAdAccountApi(id: string) {
  return request(`account-manage/ad-accounts/${id}`, {
    method: 'DELETE',
  });
}

/**
 * 批量删除广告账户
 * POST /api/v2/account-manage/ad-accounts/batch-delete
 */
export async function batchDeleteAdAccountsApi(params: {
  ids: string[];
}) {
  return request('account-manage/ad-accounts/batch-delete', {
    method: 'POST',
    data: params,
  });
}

/**
 * 删除 Facebook 个人用户
 * DELETE /api/v2/account-manage/accounts/{id}
 */
export async function deleteAccountApi(id: string) {
  return request(`account-manage/accounts/${id}`, {
    method: 'DELETE',
  });
}

/**
 * 更新账号自动绑定状态
 * PATCH /api/v2/account-manage/accounts/{id}/auto-bind
 */
export async function updateAutoBindApi(params: {
  id: string;
  autoBind: boolean;
}) {
  return request(`account-manage/accounts/${params.id}/auto-bind`, {
    method: 'PATCH',
    data: params,
  });
}
