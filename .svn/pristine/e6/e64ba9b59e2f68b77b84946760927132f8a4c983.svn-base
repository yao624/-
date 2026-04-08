import request from '@/utils/request';

export interface MetaReportDashboardRow {
  id: string;
  name: string;
  folder_id: string | null;
  channel: string;
  board_type: 'comprehensive' | 'material' | 'tag' | 'custom' | 'landing';
  group_compare: boolean;
  last_saved_at?: string | null;
  status: string;
}

export interface MetaReportFolderNode {
  id: string;
  name: string;
  parent_id: string | null;
  sort_order: number;
  status: string;
  children?: MetaReportFolderNode[];
}

export interface MetaReportCardRow {
  id: string;
  dashboard_id: string;
  title: string;
  chart_type: string;
  shape: 'large' | 'medium' | 'small';
  sort_order: number;
  query_config?: Record<string, unknown> | null;
  style_config?: Record<string, unknown> | null;
}

export async function fetchMetaReportDashboards(params?: { folder_id?: string; board_type?: string; keyword?: string }) {
  return request.get<any, { data: MetaReportDashboardRow[] }>('/meta-report-dashboards', { params });
}

export async function createMetaReportDashboard(payload: {
  name: string;
  folder_id?: string | null;
  channel?: string;
  board_type: 'comprehensive' | 'material' | 'tag' | 'custom' | 'landing';
  group_compare?: boolean;
}) {
  return request.post<any, { data: MetaReportDashboardRow }>('/meta-report-dashboards', payload);
}

export async function updateMetaReportDashboard(
  id: string,
  payload: Partial<{
    name: string;
    folder_id: string | null;
    channel: string;
    group_compare: boolean;
    status: string;
  }>,
) {
  return request.patch<any, { data: MetaReportDashboardRow }>(`/meta-report-dashboards/${id}`, payload);
}

export async function deleteMetaReportDashboard(id: string) {
  return request.delete(`/meta-report-dashboards/${id}`);
}

export async function saveMetaReportDashboard(id: string) {
  return request.post<any, { data: MetaReportDashboardRow }>(`/meta-report-dashboards/${id}/save`, {});
}

export async function duplicateMetaReportDashboard(id: string, payload: { name: string; folder_id?: string | null }) {
  return request.post<any, { data: MetaReportDashboardRow & { cards?: MetaReportCardRow[] } }>(
    `/meta-report-dashboards/${id}/duplicate`,
    payload,
  );
}

export async function fetchMetaReportFolders() {
  return request.get<any, { data: MetaReportFolderNode[] }>('/meta-report-dashboard-folders');
}

export async function createMetaReportFolder(payload: { name: string; parent_id?: string | null; sort_order?: number }) {
  return request.post<any, { data: MetaReportFolderNode }>('/meta-report-dashboard-folders', payload);
}

export async function updateMetaReportFolder(
  id: string,
  payload: Partial<{ name: string; sort_order: number; status: string }>,
) {
  return request.patch<any, { data: MetaReportFolderNode }>(`/meta-report-dashboard-folders/${id}`, payload);
}

export async function deleteMetaReportFolder(id: string) {
  return request.delete(`/meta-report-dashboard-folders/${id}`);
}

export async function fetchMetaReportCards(dashboardId: string) {
  return request.get<any, { data: MetaReportCardRow[] }>(`/meta-report-dashboards/${dashboardId}/cards`);
}

export async function createMetaReportCard(
  dashboardId: string,
  payload: {
    title: string;
    chart_type: string;
    shape: 'large' | 'medium' | 'small';
    sort_order?: number;
    query_config?: Record<string, unknown> | null;
    style_config?: Record<string, unknown> | null;
  },
) {
  return request.post<any, { data: MetaReportCardRow }>(`/meta-report-dashboards/${dashboardId}/cards`, payload);
}

export async function updateMetaReportCard(
  cardId: string,
  payload: Partial<{
    title: string;
    chart_type: string;
    shape: 'large' | 'medium' | 'small';
    sort_order: number;
    query_config: Record<string, unknown> | null;
    style_config: Record<string, unknown> | null;
  }>,
) {
  return request.patch<any, { data: MetaReportCardRow }>(`/meta-report-dashboard-cards/${cardId}`, payload);
}

export async function deleteMetaReportCard(cardId: string) {
  return request.delete(`/meta-report-dashboard-cards/${cardId}`);
}

