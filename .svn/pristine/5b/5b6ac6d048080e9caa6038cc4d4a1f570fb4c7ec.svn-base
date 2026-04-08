import request from '@/utils/request';

const getMaterialLibraryBaseURL = () => {
  const base = String(process.env.VUE_APP_API_BASE_URL || '');
  if (base.endsWith('/v2')) return base.slice(0, -3);
  return base.replace('/v2', '');
};

export async function getMaterialLibraryTags() {
  return request.get<any, any>('/material-library/tags', { baseURL: getMaterialLibraryBaseURL() });
}

export async function getMaterialLibraryDesigners() {
  return request.get<any, any>('/material-library/designers', { baseURL: getMaterialLibraryBaseURL() });
}

export async function getMaterialLibraryCreators() {
  return request.get<any, any>('/material-library/creators', { baseURL: getMaterialLibraryBaseURL() });
}

export async function getMaterialLibraryRejectReasonOptions() {
  return request.get<any, any>('/material-library/reject-reason-options', { baseURL: getMaterialLibraryBaseURL() });
}

export async function getMaterialLibrarySystemTags(params?: { group?: string }) {
  return request.get<any, any>('/material-library/system-tags', { params, baseURL: getMaterialLibraryBaseURL() });
}

export async function getMaterialLibraryMaterialGroups(params?: {
  search?: string;
  pageNo?: number;
  pageSize?: number;
}) {
  return request.get<any, any>('/material-library/material-groups', {
    params,
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function createMaterialLibraryMaterialGroup(payload: { name: string; description?: string }) {
  return request.post<any, any>('/material-library/material-groups', payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function updateMaterialLibraryMaterialGroup(
  id: number | string,
  payload: { name: string; description?: string },
) {
  return request.put<any, any>(`/material-library/material-groups/${id}`, payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function deleteMaterialLibraryMaterialGroup(id: number | string) {
  return request.delete<any, any>(`/material-library/material-groups/${id}`, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

