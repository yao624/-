import request from '@/utils/request';

const getMaterialLibraryBaseURL = () => {
  const base = String(process.env.VUE_APP_API_BASE_URL || '');
  if (base.endsWith('/v2')) return base.slice(0, -3);
  return base.replace('/v2', '');
};

export async function getMaterialLibraryFolders(params: {
  library_type?: number | string;
  owner_id?: number | string;
} = {}) {
  return request.get<any, any>('/material-library/folders', {
    params,
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function getMaterialLibraryFolderChildren(
  id: number | string,
  params?: { library_type?: number | string; owner_id?: number | string },
) {
  return request.get<any, any>(`/material-library/folders/${id}/children`, {
    params,
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function createMaterialLibraryFolder(payload: {
  parent_id: number | string;
  folder_name: string;
  notes?: string;
  library_type?: number;
  owner_id?: number | string;
}) {
  return request.post<any, any>('/material-library/folders', payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function createMaterialLibraryEnterpriseLibrary(payload: {
  library_name: string;
  visibility: 'company' | 'self' | 'specified';
  specifiedUsers?: Array<number | string>;
  reviewEnabled: 'enabled' | 'disabled';
  enterprise_id?: number | string;
  manager_id?: number | string;
}) {
  return request.post<any, any>('/material-library/enterprise-libraries', {
    library_name: payload.library_name,
    visibility: payload.visibility,
    specifiedUsers: payload.specifiedUsers ?? [],
    review_enabled: payload.reviewEnabled,
    enterprise_id: payload.enterprise_id,
    manager_id: payload.manager_id,
  }, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function deleteMaterialLibraryFolder(id: number | string) {
  return request.delete<any, any>(`/material-library/folders/${id}`, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function moveMaterialLibraryFolder(id: number | string, payload: {
  target_parent_id: string | number;
  target_owner_id?: string | number;
}) {
  return request.put<any, any>(`/material-library/folders/${id}/move`, payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

