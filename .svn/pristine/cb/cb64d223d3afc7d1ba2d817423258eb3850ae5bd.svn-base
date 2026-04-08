import request from '@/utils/request';

export interface MetaScheduledReportRow {
  id: string;
  status: 'ENABLED' | 'DISABLED';
  report_name: string;
  report_content: string | null;
  push_status: string;
  frequency: string | null;
  push_time: string | null;
  created_time: string;
  creator: string;
}

export interface MetaScheduledReportDetailCard {
  id?: string;
  title: string;
  reportType: 'comprehensive' | 'material';
  level: string;
  dateRange: string | null;
  compare: boolean;
  selectedDimensions: string[];
  selectedMetrics: string[];
  sortBy: string | null;
  sortOrder: 'asc' | 'desc' | null;
  limit: number;
  filterOptions: any[];
  metricFilterEnabled: boolean;
  metricRows: any[];
}

export interface MetaScheduledReportDetail {
  id: string;
  name: string;
  frequencyType: string;
  time: string | null;
  email: string | null;
  status: string;
  dataCards: MetaScheduledReportDetailCard[];
}

export async function fetchMetaScheduledReports(params?: {
  pageNo?: number;
  pageSize?: number;
  reportName?: string;
  status?: string;
  creator?: string;
}) {
  return request.get<any, { data: MetaScheduledReportRow[]; totalCount: number; pageNo: number; pageSize: number }>(
    '/meta-scheduled-reports',
    { params },
  );
}

export async function fetchMetaScheduledReportDetail(id: string) {
  return request.get<any, { data: MetaScheduledReportDetail }>(`/meta-scheduled-reports/${id}`);
}

export async function createMetaScheduledReport(payload: {
  name: string;
  frequencyType: string;
  time: string | null;
  email: string | null;
  dataCards: MetaScheduledReportDetailCard[];
}) {
  return request.post<any, { data: { id: string } }>('/meta-scheduled-reports', payload);
}

export async function updateMetaScheduledReport(
  id: string,
  payload: Partial<{
    name: string;
    frequencyType: string;
    time: string | null;
    email: string | null;
    status: string;
    dataCards: MetaScheduledReportDetailCard[];
  }>,
) {
  return request.patch<any, { data: { id: string } }>(`/meta-scheduled-reports/${id}`, payload);
}

export async function deleteMetaScheduledReport(id: string) {
  return request.delete(`/meta-scheduled-reports/${id}`);
}

export async function downloadMetaScheduledReport(id: string) {
  // 返回 Blob，用于在前端触发浏览器下载
  return request.post<Blob>(`/meta-scheduled-reports/${id}/download`, null, {
    responseType: 'blob',
  } as any);
}


