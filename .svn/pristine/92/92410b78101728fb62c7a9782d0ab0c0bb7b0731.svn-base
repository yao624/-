import request from '@/utils/request';

export async function queryCopywritingsApi(params?: { [key: string]: any }) {
  return request.get('/copywritings', { params });
}

export async function addCopywritingsOneApi(params: Record<string, any>) {
  const url = params.id ? `copywritings/${params.id}` : '/copywritings';
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    data: params,
  });
}

export async function deleteCopywritingsApi(id?: string) {
  return request('/copywritings/' + id, {
    method: 'DELETE',
    data: {},
  });
}

export async function shareCopywritingsApi(params: Record<string, any>) {
  return request('/copywritings/share', {
    method: 'POST',
    data: params,
  });
}

export async function unShareCopywritingsApi(params: Record<string, any>) {
  return request('/copywritings/unshare', {
    method: 'POST',
    data: params,
  });
}
