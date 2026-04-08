import request from '@/utils/request';

export async function queryAgentsApi(params?: { [key: string]: any }) {
  return request.get('/agents', {
    params,
  });
}

export async function addAgentsOneApi(params: Record<string, any>) {
  const url = params.id ? `agents/${params.id}` : '/agents';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function deletAgentsApi(id?: string) {
  return request('/agents/' + id, {
    method: 'DELETE',
    data: {},
  });
}
