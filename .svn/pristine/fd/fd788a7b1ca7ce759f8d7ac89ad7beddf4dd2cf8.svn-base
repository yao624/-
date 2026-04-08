import request from '@/utils/request';

export async function queryLinksApi(params?: { [key: string]: any }) {
  try {
    return await request.get('/links', { params });
  } catch (_e) {
    // 后端接口异常时降级，避免页面报 Request Error
    return { data: [], totalCount: 0 };
  }
}

export async function addLinksOneApi(params: Record<string, any>) {
  const url = params.id ? `links/${params.id}` : '/links';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function deleteLinksOneApi(id?: string) {
  return request('/links/' + id, {
    method: 'DELETE',
    data: {},
  });
}

export async function getLinksValidTags() {
  return request('/tags/links/', {
    method: 'GET',
    data: {},
  });
}

export async function shareLinksApi(params: Record<string, any>) {
  return request('/links/share', {
    method: 'POST',
    data: params,
  });
}

export async function unShareLinksApi(params: Record<string, any>) {
  return request('/links/unshare', {
    method: 'POST',
    data: params,
  });
}
