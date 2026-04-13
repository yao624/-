import request from '@/utils/request';

export interface BusinessManagerQuery {
  page?: number;
  pageSize?: number;
  search?: string;
  business_id?: string;
  status?: string;
}

export interface BusinessManagerOperationLogQuery {
  page?: number;
  pageSize?: number;
  search?: string;
  business_manager_id?: string | number;
  operation_type?: string;
  status?: string;
}

export interface BusinessManagerPayload {
  name: string;
  business_id: string;
  access_token?: string;
  sync_frequency?: number;
  status?: 'active' | 'inactive' | 'error';
  use_proxy?: boolean;
  proxy_ip?: string;
  proxy_port?: string;
  proxy_username?: string;
  proxy_password?: string;
}

export const getBusinessManagerListApi = (params: BusinessManagerQuery) =>
  request.get('/business-managers', { params });

export const createBusinessManagerApi = (data: BusinessManagerPayload) =>
  request.post('/business-managers', data);

export const updateBusinessManagerApi = (id: string | number, data: Partial<BusinessManagerPayload>) =>
  request.put(`/business-managers/${id}`, data);

export const deleteBusinessManagerApi = (id: string | number) =>
  request.delete(`/business-managers/${id}`);

export const testBusinessManagerTokenApi = (id: string | number) =>
  request.post(`/business-managers/${id}/test-token`);

export const checkBusinessManagerStatusApi = (id: string | number) =>
  request.post(`/business-managers/${id}/check-status`);

export const testBusinessManagerAssignApi = (id: string | number, emails: string[]) =>
  request.post(`/business-managers/${id}/test-assign`, { emails });

export const getBusinessManagerLockCardSettingApi = (id: string | number) =>
  request.get(`/business-managers/${id}/lock-card-setting`);

export const updateBusinessManagerLockCardSettingApi = (
  id: string | number,
  closecard: 0 | 1,
) => request.put(`/business-managers/${id}/lock-card-setting`, { closecard });

export const getBusinessManagerOperationLogsApi = (params: BusinessManagerOperationLogQuery) =>
  request.get('/business-managers/operation-logs', { params });

export const getBusinessManagerOperationLogsStatsApi = () =>
  request.get('/business-managers/operation-logs/stats');
