import request from '@/utils/request';

export interface MetaTaskJobRow {
  id: string;
  account: string;
  submit_time: string | null;
  end_time: string | null;
  submit_method: string;
  creator: string;
}

export interface MetaTaskJobItemRow {
  id: string;
  job_id: string;
  target_type: string;
  target_id: string;
  target_name?: string | null;
  status: string;
  result?: string | null;
  message?: string | null;
  started_at?: string | null;
  finished_at?: string | null;
  retry_count: number;
  original_item_id?: string | null;
  payload?: unknown;
}

export interface MetaTaskOperationLogRow {
  id: string;
  job_id: string;
  job_item_id?: string | null;
  action_type: string;
  operator_id?: number | null;
  action_payload?: unknown;
  result_status: string;
  result_message?: string | null;
  created_at?: string | null;
}

export interface MetaTaskDetailResponse {
  job: Record<string, unknown>;
  items: MetaTaskJobItemRow[];
  operation_logs: MetaTaskOperationLogRow[];
}

export async function fetchMetaTasks(params: {
  pageNo?: number;
  pageSize?: number;
  type?: string;
  taskId?: number | string;
  status?: string;
  result?: string;
  submitMethod?: string;
  creator?: string;
  channel?: string;
  account?: string;
  asset?: string;
  batchId?: string;
  dateRange?: [string, string];
  submitTimeRange?: [string, string];
  executeTimeRange?: [string, string];
  adAsset?: string;
  rule?: string;
  executeResult?: string;
  executeStrategy?: string;
  monitorTarget?: string;
}) {
  const res = await request.get<any, { data: MetaTaskJobRow[]; totalCount: number; pageNo: number; pageSize: number }>(
    '/meta-tasks',
    { params },
  );
  return res;
}

export async function fetchMetaTaskDetail(id: string) {
  const res = await request.get<any, { data: MetaTaskDetailResponse }>(`/meta-tasks/${id}`);
  return res;
}

export async function fetchMetaTaskOperationLogs(id: string) {
  const res = await request.get<any, { data: MetaTaskOperationLogRow[] }>(`/meta-tasks/${id}/operation-logs`);
  return res;
}

export async function copyMetaTask(id: string) {
  return request.post<any, { success: boolean; data: { new_job_id: string } }>(`/meta-tasks/${id}/copy`);
}

export async function retryFailedMetaTask(id: string) {
  return request.post<any, { success: boolean; data: { new_job_id: string } }>(`/meta-tasks/${id}/retry-failed`);
}

