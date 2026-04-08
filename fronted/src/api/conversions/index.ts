import request from '@/utils/request';

export async function queryConversions(params?: { [key: string]: any }) {
  return request.get('/networks/conversions', {
    params,
  });
}
