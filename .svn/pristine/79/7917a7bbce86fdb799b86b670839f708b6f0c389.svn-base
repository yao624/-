import request from '@/utils/request';

export async function queryListApi(params?: { [key: string]: any }) {
  return request.get('/operator', {
    params,
  });
}

export async function addOneApi(params: Record<string, any>) {
  const url = params.id ? `operator/${params.id}` : '/operator';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function pullAdAccountdataApi(params: Record<string, any>) {
  return request('/operator/pull-ad-account-data', {
    method: 'POST',
    data: {
      params,
    },
  });
}

export async function deleteOneApi(id?: string) {
  return request('/operator/' + id, {
    method: 'DELETE',
    data: {},
  });
}
