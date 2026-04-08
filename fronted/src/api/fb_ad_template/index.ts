import request from '@/utils/request';

export async function getFbAdTemplates(params) {
  return request.get('/fb-ad-templates', { params });
}

export async function getFbAdTemplate(id: string) {
  return request.get(`/fb-ad-templates/${id}`);
}

export async function deleteFbAdTemplate(id: string) {
  return request(`/fb-ad-templates/${id}`, {
    method: 'DELETE',
  });
}

export async function updateFbAdTemplate(id, params) {
  return request(`/fb-ad-templates/${id}`, {
    method: 'PUT',
    data: params,
  });
}

export async function createFbAdTemplate(params) {
  return request('/fb-ad-templates', {
    method: 'POST',
    data: params,
  });
}

export async function searchLanguages(q: string) {
  const params = { q };
  return request.get('/fb-ad-templates/locale-search', { params });
}

export async function searchInterests(q: string) {
  const params = { q };
  return request.get('/fb-ad-templates/interests-search', { params });
}

export async function shareFbAdTemplateApi(params: Record<string, any>) {
  return request('/fb-ad-templates/share', {
    method: 'POST',
    data: params,
  });
}

export async function unShareFbAdTemplateApi(params: Record<string, any>) {
  return request('/fb-ad-templates/unshare', {
    method: 'POST',
    data: params,
  });
}
