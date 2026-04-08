import request from '@/utils/request';

export async function queryProxiesApi(params?: { [key: string]: any }) {
  return request.get('/proxies', {
    params,
  });
}

export async function addProxiesOneApi(params: Record<string, any>) {
  const url = params.id ? `proxies/${params.id}` : '/proxies';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function deletProxiesApi(id?: string) {
  return request('/proxies/' + id, {
    method: 'DELETE',
    data: {},
  });
}
