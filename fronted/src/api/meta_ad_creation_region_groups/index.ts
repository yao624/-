import request from '@/utils/request';

/** 列表：当前用户保存的地区组；可选 q 按名称模糊筛选；tag 按标签精确筛选（JSON 数组包含） */
export async function queryMetaAdCreationRegionGroupsApi(params?: { q?: string; tag?: string }) {
  return request.get('/meta-ad-creation-region-groups', { params });
}

export async function getMetaAdCreationRegionGroupApi(id: string) {
  return request.get(`/meta-ad-creation-region-groups/${id}`);
}

export async function createMetaAdCreationRegionGroupApi(data: Record<string, any>) {
  return request.post('/meta-ad-creation-region-groups', data);
}

export async function updateMetaAdCreationRegionGroupApi(id: string, data: Record<string, any>) {
  return request.put(`/meta-ad-creation-region-groups/${id}`, data);
}

export async function deleteMetaAdCreationRegionGroupApi(id: string) {
  return request.delete(`/meta-ad-creation-region-groups/${id}`);
}
