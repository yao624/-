import request from '@/utils/request';

export async function queryListApi(params?: { [key: string]: any }) {
  return request.get('/expense', {
    params,
  });
}
