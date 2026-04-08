import request from '@/utils/request';

export async function queryTags(): Promise<{ list: any[] }> {
  return request.get('/tags');
}
