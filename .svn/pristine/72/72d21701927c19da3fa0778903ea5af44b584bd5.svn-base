import request from '@/utils/request';

export async function queryMetaAdCreationTemplatesApi(params?: Record<string, any>) {
  return request.get('/meta-ad-creation-templates', { params });
}

export async function getMetaAdCreationTemplateApi(id: string) {
  return request.get(`/meta-ad-creation-templates/${id}`);
}

export async function createMetaAdCreationTemplateApi(data: Record<string, any>) {
  return request('/meta-ad-creation-templates', {
    method: 'POST',
    data,
  });
}

export async function deleteMetaAdCreationTemplateApi(id: string) {
  return request(`/meta-ad-creation-templates/${id}`, {
    method: 'DELETE',
  });
}
