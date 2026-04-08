import request from '@/utils/request';

export async function materialsList(params?: { [key: string]: any }) {
  return request.get('/materials', {
    params,
  });
}

/** GET /materials/{id} — MaterialResource（含临时下载 url 可作缩略图/预览） */
export async function getMaterialApi(id: string) {
  return request.get(`/materials/${id}`);
}

export async function addMaterialsOneApi(params: Record<string, any>) {
  const url = params.id ? `materials/${params.id}` : '/materials';
  const formData = new FormData();
  formData.append('name', params.name);
  formData.append('notes', params.notes);
  formData.append('file', params.file.originFileObj);
  if (params.tag_ids && params.tag_ids.length > 0) {
    params.tag_ids.forEach(id => {
      formData.append('tag_ids[]', id);
    });
  }
  return request(url, {
    method: params.id ? 'PUT' : 'POST',
    headers: {
      'Content-Type': 'multipart/form-data',
    },
    data: formData,
  });
}

export async function deletOneMaterialsApi(id?: string) {
  return request('/materials/' + id, {
    method: 'DELETE',
    data: {},
  });
}

export async function shareMaterialsApi(params: Record<string, any>) {
  return request('/materials/share', {
    method: 'POST',
    data: params,
  });
}

export async function unShareMaterialsApi(params: Record<string, any>) {
  return request('/materials/unshare', {
    method: 'POST',
    data: params,
  });
}

export async function editMaterialApi(id: string, params: Record<string, any>) {
  return request('/materials/' + id, {
    method: 'PUT',
    data: params,
  });
}
