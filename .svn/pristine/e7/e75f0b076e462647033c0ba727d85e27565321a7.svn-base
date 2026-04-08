import request from '@/utils/request';

export async function queryMetaAdCreationDraftsApi(params?: Record<string, any>) {
  return request.get('/meta-ad-creation-drafts', { params });
}

export async function queryMetaAdCreationDraftTagsApi() {
  return request.get('/meta-ad-creation-drafts/tags');
}

export async function getMetaAdCreationDraftApi(id: string) {
  return request.get(`/meta-ad-creation-drafts/${id}`);
}

export async function createMetaAdCreationDraftApi(data: Record<string, any>) {
  return request('/meta-ad-creation-drafts', {
    method: 'POST',
    data,
  });
}

export async function updateMetaAdCreationDraftApi(id: string, data: Record<string, any>) {
  return request(`/meta-ad-creation-drafts/${id}`, {
    method: 'PUT',
    data,
  });
}

export async function deleteMetaAdCreationDraftApi(id: string) {
  return request(`/meta-ad-creation-drafts/${id}`, {
    method: 'DELETE',
  });
}
