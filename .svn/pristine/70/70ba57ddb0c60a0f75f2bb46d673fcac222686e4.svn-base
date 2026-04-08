import request from '@/utils/request';

export async function queryFB_PixelsApi(params?: { [key: string]: any }) {
  return request.get('/fb-pixels', {
    params,
  });
}

export async function updateFB_PixelsOneApi(params: Record<string, any>) {
  const url = `fb-pixels/${params.id}`;
  return request(url, {
    method: 'PATCH',
    data: params,
  });
}
