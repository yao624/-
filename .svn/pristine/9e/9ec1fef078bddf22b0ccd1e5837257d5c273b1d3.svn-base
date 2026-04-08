import request from '@/utils/request';

export async function queryTagsApi(params?: { [key: string]: any }) {
  return request.get('/tags', {
    params,
  });
}
export async function addTagsOneApi(params: Record<string, any>) {
  return request('/tags', {
    method: 'POST',
    data: params,
  });
}
export async function deleteTagsOneApi(id?: string) {
  return request('/tags/' + id, {
    method: 'DELETE',
    data: {},
  });
}

export async function manageTags(modelType: string, params) {
  return request('/tags/' + modelType, {
    method: 'post',
    data: params,
  });
}

export async function createTagApi(params: Record<string, any>) {
  return request('/meta-tags', {
    method: 'POST',
    data: params,
  });
}

export async function getTagFoldersApi() {
  return request.get('/meta-tag-folders');
}

export async function createFolderApi(params: Record<string, any>) {
  return request('/meta-tag-folders', {
    method: 'POST',
    data: params,
  });
}

export async function updateFolderApi(id: number | string, params: Record<string, any>) {
  return request(`/meta-tag-folders/${id}`, {
    method: 'PUT',
    data: params,
  });
}

export async function deleteFolderApi(id: number | string) {
  return request(`/meta-tag-folders/${id}`, {
    method: 'DELETE',
  });
}

export async function deleteTagApi(id: number | string) {
  return request(`/meta-tags/${id}`, {
    method: 'DELETE',
  });
}

export async function updateTagApi(id: number | string, params: Record<string, any>) {
  return request(`/meta-tags/${id}`, {
    method: 'PUT',
    data: params,
  });
}

export async function createTagOptionApi(params: Record<string, any>) {
  return request('/meta-tag-options', {
    method: 'POST',
    data: params,
  });
}

export async function updateTagOptionApi(params: Record<string, any>) {
  return request('/meta-tag-options', {
    method: 'PUT',
    data: params,
  });
}

export async function deleteTagOptionApi(params: { ids: (number | string)[] }) {
  return request('/meta-tag-options', {
    method: 'DELETE',
    data: params,
  });
}

export async function uploadTagOptionImageApi(file: File) {
  const formData = new FormData();
  formData.append('image', file);
  return request('/meta-tag-options/upload-image', {
    method: 'POST',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
}

export async function getTagOptionsApi(parentId: number | string) {
  return request.get('/meta-tag-options', {
    params: { parent_id: parentId },
  });
}
