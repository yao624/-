import request from '@/utils/request';

export type MetaCopyItemTranslations = Record<
  string,
  Partial<{
    primary_text: string;
    headline: string;
    description: string;
  }>
>;

export const META_COPY_LOCALE_OPTIONS: Array<{ label: string; value: string }> = [
  { label: 'English (US)', value: 'en_US' },
  { label: 'English (UK)', value: 'en_GB' },
  { label: 'English (CA)', value: 'en_CA' },
  { label: 'English (AU)', value: 'en_AU' },
  { label: 'English (SG)', value: 'en_SG' },
  { label: 'English (IN)', value: 'en_IN' },
  { label: '中文(简体)', value: 'zh_CN' },
  { label: '中文(繁体-台湾)', value: 'zh_TW' },
  { label: '中文(繁体-香港)', value: 'zh_HK' },
  { label: '日本語', value: 'ja_JP' },
  { label: '한국어', value: 'ko_KR' },
  { label: 'Español (España)', value: 'es_ES' },
  { label: 'Español (México)', value: 'es_MX' },
  { label: 'Español (Argentina)', value: 'es_AR' },
  { label: 'Português (Brasil)', value: 'pt_BR' },
  { label: 'Português (Portugal)', value: 'pt_PT' },
  { label: 'Français (France)', value: 'fr_FR' },
  { label: 'Français (Canada)', value: 'fr_CA' },
  { label: 'Deutsch', value: 'de_DE' },
  { label: 'Italiano', value: 'it_IT' },
  { label: 'Nederlands', value: 'nl_NL' },
  { label: 'Русский', value: 'ru_RU' },
  { label: 'Українська', value: 'uk_UA' },
  { label: 'Türkçe', value: 'tr_TR' },
  { label: 'ภาษาไทย', value: 'th_TH' },
  { label: 'Tiếng Việt', value: 'vi_VN' },
  { label: 'Bahasa Indonesia', value: 'id_ID' },
  { label: 'Bahasa Melayu', value: 'ms_MY' },
  { label: 'العربية', value: 'ar_AR' },
  { label: 'हिन्दी', value: 'hi_IN' },
];

export interface MetaCopyLibraryRow {
  id: string;
  name: string;
  type: 'personal' | 'enterprise';
  owner_user_id: string;
  visibility_scope?: Record<string, unknown> | null;
  status: string;
}

export interface MetaCopyFolderTreeNode {
  id: string;
  name: string;
  level: number;
  sort_order: number;
  direct_copy_count: number;
  total_copy_count: number;
  children?: MetaCopyFolderTreeNode[];
}

export interface MetaCopyItemRow {
  id: string;
  library_id: string;
  folder_id: string;
  primary_text?: string | null;
  headline?: string | null;
  description?: string | null;
  translations?: MetaCopyItemTranslations | null;
  remark?: string | null;
  status: string;
  impressions?: number;
  clicks?: number;
  spend?: number;
  conversions?: number;
  revenue?: number;
  created_at?: string;
}

export async function fetchMetaCopyLibraries(): Promise<MetaCopyLibraryRow[]> {
  const res = await request.get<any, { data: MetaCopyLibraryRow[] }>('/meta-copy-libraries');
  return res?.data ?? [];
}

export async function createMetaCopyEnterpriseLibrary(name: string) {
  return request.post<any, { data: MetaCopyLibraryRow }>('/meta-copy-libraries', { name });
}

export async function createMetaCopyLibrary(payload: {
  name: string;
  type: 'personal' | 'enterprise';
  visibility_scope?: Record<string, unknown> | null;
}) {
  return request.post<any, { data: MetaCopyLibraryRow }>('/meta-copy-libraries', payload);
}

export async function deleteMetaCopyLibrary(id: string) {
  return request.delete(`/meta-copy-libraries/${id}`);
}

export async function updateMetaCopyLibrary(
  id: string,
  payload: {
    name?: string;
    visibility_scope?: Record<string, unknown> | null;
    status?: string;
  },
) {
  // 后端 MetaCopyLibraryController@update 期望字段：name / visibility_scope / status
  return request.patch<any, { data: MetaCopyLibraryRow }>(`/meta-copy-libraries/${id}`, payload);
}

export async function fetchMetaCopyFolderTree(libraryId: string): Promise<MetaCopyFolderTreeNode[]> {
  const res = await request.get<any, { data: MetaCopyFolderTreeNode[] }>('/meta-copy-folders/tree', {
    params: { library_id: libraryId },
  });
  return res?.data ?? [];
}

export async function createMetaCopyFolder(payload: {
  library_id: string;
  parent_id?: string | null;
  name: string;
  sort_order?: number;
}) {
  return request.post<any, { data: unknown }>('/meta-copy-folders', payload);
}

export async function updateMetaCopyFolder(
  id: string,
  payload: { name?: string; sort_order?: number; parent_id?: string | null },
) {
  return request.patch(`/meta-copy-folders/${id}`, payload);
}

export async function reorderMetaCopyFolders(payload: {
  library_id: string;
  parent_id?: string | null;
  ordered_ids: string[];
}) {
  return request.post('/meta-copy-folders/reorder', payload);
}

export async function deleteMetaCopyFolder(id: string) {
  return request.delete(`/meta-copy-folders/${id}`);
}

export async function fetchMetaCopyItems(params: {
  folder_id: string;
  include_children?: boolean | number;
  keyword?: string;
  date_start?: string;
  date_end?: string;
  pageNo?: number;
  pageSize?: number;
}) {
  return request.get<any, {
    data: MetaCopyItemRow[];
    pageSize: number;
    pageNo: number;
    totalPage: number;
    totalCount: number;
  }>('/meta-copy-items', { params });
}

export async function createMetaCopyItem(payload: {
  library_id: string;
  folder_id: string;
  primary_text?: string;
  headline?: string;
  description?: string;
  translations?: MetaCopyItemTranslations | null;
  remark?: string;
  status?: string;
}) {
  return request.post<any, { data: MetaCopyItemRow }>('/meta-copy-items', payload);
}

export async function updateMetaCopyItem(
  id: string,
  payload: Partial<{
    folder_id: string;
    primary_text: string;
    headline: string;
    description: string;
    translations: MetaCopyItemTranslations | null;
    remark: string;
    status: string;
  }>,
) {
  return request.patch<any, { data: MetaCopyItemRow }>(`/meta-copy-items/${id}`, payload);
}

export async function fetchMetaCopyItem(id: string) {
  return request.get<any, { data: MetaCopyItemRow }>(`/meta-copy-items/${id}`);
}

export async function deleteMetaCopyItem(id: string) {
  return request.delete(`/meta-copy-items/${id}`);
}

export async function batchDeleteMetaCopyItems(ids: string[]) {
  return request.post('/meta-copy-items/batch-delete', { ids });
}
