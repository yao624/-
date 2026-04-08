import request from '@/utils/request';

export async function queryMetaAdCreationRecordsApi(params?: Record<string, any>) {
  return request.get('/meta-ad-creation-records', { params });
}

export async function getMetaAdCreationRecordApi(id: string) {
  return request.get(`/meta-ad-creation-records/${id}`);
}

export async function createMetaAdCreationRecordApi(data: Record<string, any>) {
  return request('/meta-ad-creation-records', {
    method: 'POST',
    data,
  });
}

export async function syncMetaAdCreationRecordApi(id: string) {
  return request(`/meta-ad-creation-records/${id}/sync`, {
    method: 'POST',
  });
}
