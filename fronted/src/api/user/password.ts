import request from '@/utils/request';

export interface ChangePwdParams {
  old_password: string;
  new_password: string;
  new_password2: string;
}

export async function changePwd(params: ChangePwdParams) {
  return request.post<ChangePwdParams, string>('/user/change-password', params);
}
