import request from '@/utils/request';

const getMaterialLibraryBaseURL = () => {
  const base = String(process.env.VUE_APP_API_BASE_URL || '');
  if (base.endsWith('/v2')) return base.slice(0, -3);
  return base.replace('/v2', '');
};

export async function getMaterialLibraryMaterials(params: Record<string, any>) {
  return request.get<any, any>('/material-library/materials', {
    params,
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function getMaterialLibraryFavorites(params: Record<string, any>) {
  return request.get<any, any>('/material-library/materials/favorites', {
    params,
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function uploadMaterial(payload: FormData) {
  return request('/material-library/materials/upload', {
    method: 'POST',
    headers: {
      'Content-Type': 'multipart/form-data',
    },
    data: payload,
    baseURL: getMaterialLibraryBaseURL(),
  });
}

// =========================
// 上传素材：临时分片会话（upload-temp）
// =========================
export async function uploadTempSessionInit(payload: Record<string, any>) {
  return request.post<any, any>('/material-library/materials/upload-temp/session', payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function uploadTempSessionChunk(sessionId: number | string, payload: FormData) {
  return request(`/material-library/materials/upload-temp/session/${sessionId}/chunk`, {
    method: 'POST',
    data: payload,
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function uploadTempSessionFilesStatus(sessionId: number | string) {
  return request.get<any, any>(`/material-library/materials/upload-temp/session/${sessionId}/files/status`, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function uploadTempSessionCommit(sessionId: number | string, payload?: Record<string, any>) {
  return request.post<any, any>(`/material-library/materials/upload-temp/session/${sessionId}/commit`, payload || {}, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function deleteUploadTempSession(sessionId: number | string) {
  return request.delete<any, any>(`/material-library/materials/upload-temp/session/${sessionId}`, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function updateMaterial(
  id: number | string,
  payload: Record<string, any>,
) {
  return request.put<any, any>(`/material-library/materials/${id}`, payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function updateMaterialProductionCost(
  id: number | string,
  payload: { production_cost: number | null },
) {
  return request.put<any, any>(`/material-library/materials/${id}/production-cost`, payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function deleteMaterial(id: number | string) {
  return request.delete<any, any>(`/material-library/materials/${id}`, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

export async function batchMaterialActions(payload: Record<string, any>) {
  return request.post<any, any>('/material-library/materials/batch-actions', payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

// 7.9 审核工作流
export async function auditMaterial(
  id: number | string,
  payload: { status: 1 | 2; reject_reason?: string },
) {
  return request.post<any, any>(`/material-library/materials/${id}/audit`, payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

// 7.10 数据导出（MVP：返回 CSV 字符串）
export async function exportMaterials(payload: Record<string, any>) {
  return request.post<any, any>('/material-library/materials/export', payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

// 7.11 投放数据统计
export async function getMaterialStatistics(params: Record<string, any>) {
  return request.get<any, any>('/material-library/materials/statistics', {
    params,
    baseURL: getMaterialLibraryBaseURL(),
  });
}

// 7.12 XMP 标签自动更新
export async function autoUpdateXmpTags(payload: Record<string, any>) {
  return request.post<any, any>('/material-library/materials/auto-update-xmp-tags', payload, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}

// 7.13 媒体素材同步
export async function syncMediaMaterials(payload: Record<string, any>) {
  return request('material-library/media-materials/sync', {
    method: 'POST',
    data: payload,
  });
}

export async function getMediaMaterialsSyncStatus(syncId: number | string) {
  return request(`material-library/media-materials/syncs/${syncId}`, {
    method: 'GET',
  });
}

export async function listMediaMaterials(params: Record<string, any>) {
  return request('material-library/media-materials/list', {
    method: 'GET',
    params,
  });
}

// 7.14 使用记录
export async function getMaterialUsages(materialId: number | string) {
  return request.get<any, any>(`/material-library/materials/${materialId}/usages`, {
    baseURL: getMaterialLibraryBaseURL(),
  });
}
