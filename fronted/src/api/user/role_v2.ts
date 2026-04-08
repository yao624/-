import request from '@/utils/request';
import type { PageResult } from '../typing';
import type { Permission, Role } from './login';


export async function getRoles(params?: { [key: string]: any }) {
  return request.get('/roles', {
    params,
  });
}

export async function addRole(data: Role) {
  return request.post<Permission, any>('/roles', data);
}

export async function updateRole(data: Role) {
  const id = data['id'];
  return request.put<Permission, any>('/roles/' + id, data);
}

export async function deleteRole(params: string) {
  return request.delete<Permission, any>('/roles/' + params);
}

export async function getRole(id: number) {
  return request.get<Permission, any>('/roles/' + id);
}

export async function getPermissions(params?: { [key: string]: any }) {
  return request.get('/permissions', {
    params,
  });
}

export async function savePermission(data: Permission) {
  return request.post<Permission, any>('/permissions', data);
}

export async function updatePermission(data: Permission) {
  const id = data['id'];
  return request.put<Permission, any>('/permissions/' + id, data);
}

export async function addPermission(data: Permission) {
  return request.post<Permission, any>('/system-manage/permissions', data);
}

// export async function updatePermission(data: Permission) {
//   return request.put<Permission, any>('/system-manage/permissions', data);
// }



export async function getUsers(params?: { [key: string]: any }) {
  return request.get<any, PageResult<Role>>('/user/list', { params });
}

export async function addUser(data) {
  return request.post('/user/register', data);
}

export async function updateUser(data) {
  const id = data['id'];
  return request.put('/user/update/' + id, data);
}

export async function batchDeleteUser(data) {
  return request.post('/user/batch-delete', data);
}

export async function fetchUserToken(id) {
  return request.post('/user/token/' + id);
}
