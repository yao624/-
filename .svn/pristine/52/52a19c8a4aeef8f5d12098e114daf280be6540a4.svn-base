import request from '@/utils/request';

/**
 * 获取租户列表
 */
export async function queryTenantsApi(params?: { [key: string]: any }) {
  return request.get('/tenants', {
    params,
  });
}

/**
 * 获取单个租户详情
 */
export async function getTenantApi(id: string) {
  return request.get(`/tenants/${id}`);
}

/**
 * 创建租户
 */
export async function createTenantApi(params: Record<string, any>) {
  return request('/tenants', {
    method: 'POST',
    data: params,
  });
}

/**
 * 更新租户
 */
export async function updateTenantApi(id: string, params: Record<string, any>) {
  return request(`/tenants/${id}`, {
    method: 'PUT',
    data: params,
  });
}

/**
 * 删除租户
 */
export async function deleteTenantApi(id: string) {
  return request(`/tenants/${id}`, {
    method: 'DELETE',
  });
}

/**
 * 批量删除租户
 */
export async function batchDeleteTenantsApi(ids: string[]) {
  return request('/tenants/batch-delete', {
    method: 'POST',
    data: { ids },
  });
}

/**
 * 测试租户数据库连接
 */
export async function testTenantConnectionApi(id: string) {
  return request(`/tenants/${id}/test-connection`, {
    method: 'POST',
  });
}

/**
 * 创建租户数据库
 */
export async function createTenantDatabaseApi(id: string) {
  return request(`/tenants/${id}/create-database`, {
    method: 'POST',
  });
}

