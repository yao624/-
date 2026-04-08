import request from '@/utils/request';

export async function getFraudConfigList(params) {
  return request.get('/fraud-config', { params });
}

export async function deleteFraudConfig(id: string) {
  return request(`/fraud-config/${id}`, {
    method: 'DELETE',
  });
}

export async function updateFraudConfig(params) {
  return request(`/fraud-config/${params.id}`, {
    method: 'PUT',
    data: params,
  });
}

export async function createFraudConfigs(params) {
  return request('/fraud-config', {
    method: 'POST',
    data: params,
  });
}
