import request from '@/utils/request';

export async function queryBMsApi(params?: { [key: string]: any }) {
  return request.get('/fb-bms', {
    params,
  });
}

export async function updateBMsOneApi(params: Record<string, any>) {
  return request(`fb-bms/${params.id}`, {
    method: 'PATCH',
    data: params,
  });
}

export async function sharePixelApi(params: Record<string, any>) {
  const url = 'fb-bms/share-pixel';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function syncBmApi(params: Record<string, any>) {
  const url = 'fb-bms/sync';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function claimPagesApi(params: Record<string, any>) {
  const url = 'fb-bms/claim-pages';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function manageBmUserAdAccApi(params: Record<string, any>) {
  const url = 'fb-bms/assign-user-account';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function manageBmUserPageApi(params: Record<string, any>) {
  const url = 'fb-bms/assign-user-page';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function updateProductSetApi(params: Record<string, any>) {
  const url = 'fb-catalog/update-product-set';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function updateProductApi(params: Record<string, any>) {
  const url = 'fb-catalog/update-product';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function bulkCreateProductsApi(params: Record<string, any>) {
  const url = 'fb-catalog/bulk-create-product';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function bulkCreateProductSetApi(params: Record<string, any>) {
  const url = 'fb-catalog/bulk-create-product-set';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function createCatalogApi(params: Record<string, any>) {
  const url = 'fb-catalog/create-catalog';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function manageBmUserCatalogApi(params: Record<string, any>) {
  const url = 'fb-bms/assign-user-catalog';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function updateProductVideoApi(params: Record<string, any>) {
  const url = 'fb-catalog/update-product-video';
  return request(url, {
    method: 'POST',
    data: params,
  });
}

export async function setOperatorApi(params: Record<string, any>) {
  const url = 'fb-catalog/set-operator';
  return request(url, {
    method: 'POST',
    data: params,
  });
}
