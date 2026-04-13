import request from '@/utils/request';

/** 与 `@/utils/request` 响应拦截器一致：返回的是 `response.data`，不是 `AxiosResponse` */
export interface ExportCreateResponse {
  success?: boolean;
  data?: { task_id?: string };
  task_id?: string;
}

export interface ExportTaskStatusResponse {
  success?: boolean;
  data?: { status?: string; message?: string };
  status?: string;
  message?: string;
}

export interface AdAccountQuery {
  page?: number;
  pageSize?: number;
  keyword?: string;
  account_status?: string;
  currency?: string;
  bm_id?: string;
  sortField?: string;
  sortOrder?: 'ascend' | 'descend';
}

export const getAdAccountsApi = (params: AdAccountQuery) => request.get('/ad-accounts', { params });
export const getAdAccountFilterOptionsApi = () => request.get('/ad-accounts/filter-options');

export const exportAdAccountsApi = (payload: Record<string, any>) =>
  request.post('/ad-accounts/export', payload) as Promise<ExportCreateResponse>;

export const getExportTaskStatusApi = (id: string) =>
  request.get('/ad-accounts/export-task-status', { params: { id } }) as Promise<ExportTaskStatusResponse>;

export const getExportTaskDownloadApi = (id: string) =>
  request.get('/ad-accounts/export-task-download', { params: { id }, responseType: 'blob' }) as Promise<Blob>;

export const importAdAccountsApi = (file: File) => {
  const formData = new FormData();
  formData.append('file', file);
  return request.post('/ad-accounts/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
};

export const syncBusinessManagerApi = (id: string | number) =>
  request.post(`/business-managers/${id}/sync`);

export const renameAdAccountApi = (id: string | number, name: string) =>
  request.put(`/ad-accounts/${id}/name`, { name });

export const getAdAccountPixelsApi = (id: string | number) =>
  request.get(`/ad-accounts/${id}/pixels`);

export const adAccountPixelOperationApi = (
  id: string | number,
  action: 'attach' | 'detach',
  payload: Record<string, any>,
) => request.post(`/ad-accounts/${id}/pixel-operation`, { action, ...payload });
