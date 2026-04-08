import request from '@/utils/request';

export async function queryClicks(params?: { [key: string]: any }) {
  return request.get('/networks/clicks', {
    params,
  });
}
