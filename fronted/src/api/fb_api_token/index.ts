import request from '@/utils/request';

export async function getFbApiTokens(params) {
  return request.get('/fb-api-token', { params });
}

export async function deleteFbApiToken(id: string) {
  return request(`/fb-api-token/${id}`, {
    method: 'DELETE',
  });
}

export async function updateFbApiToken(params) {
  return request(`/fb-api-token/${params.id}`, {
    method: 'PUT',
    data: params,
  });
}

export async function createFbApiTokens(params) {
  return request('/fb-api-token', {
    method: 'POST',
    data: params,
  });
}

export async function syncTokenResourceApi(params) {
  return request('/fb-ad-accounts/sync-api-resource', {
    method: 'POST',
    data: params,
  });
}

export async function subscribeApp(params) {
  return request('/fb-ad-accounts/subscribe-app', {
    method: 'POST',
    data: params,
  });
}
