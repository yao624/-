import request from '@/utils/request';

export async function queryFBAccountsApi(params?: { [key: string]: any }) {
  return request.get('/fb-accounts', {
    params,
  });
}

export async function getFbAccountOne(params?: { [key: string]: any }) {
  return request.get(`/fb-accounts/${params.id}`);
}

export async function addFBAccountsOneApi(params: Record<string, any>) {
  const url = params.id ? `fb-accounts/${params.id}` : '/fb-accounts';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function deletFBAccountsApi(id: string) {
  return request('/fb-accounts/' + id, {
    method: 'DELETE',
    data: {},
  });
}

export async function syncResources(id: string) {
  return request('/fb-accounts/' + id + '/sync-resources', {
    method: 'post',
    data: {},
  });
}

export async function setTokenValid(id: string, params: Record<string, any>) {
  return request('/fb-accounts/' + id + '/set-token-valid', {
    method: 'patch',
    data: params,
  });
}

export async function batchSyncResources(params) {
  return request('/fb-accounts/batch-sync-resources', {
    method: 'post',
    data: params,
  });
}

export async function getFbAccountsValidTags() {
  return request('/tags/fbaccounts/', {
    method: 'GET',
    data: {},
  });
}

export async function assignUser(params) {
  return request('/fb-accounts/assign', {
    method: 'post',
    data: params,
  });
}
