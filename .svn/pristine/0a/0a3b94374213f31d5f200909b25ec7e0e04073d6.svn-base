import request from '@/utils/request';

export interface UserOption {
  label: string;
  value: string;
}

/**
 * 获取用户选项列表
 */
export async function getUserOptions(): Promise<{
  status: boolean;
  data: UserOption[];
  message?: string;
}> {
  return request('user/options', {
    method: 'GET',
  });
}
