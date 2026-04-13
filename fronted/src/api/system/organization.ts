import request from '@/utils/request';

export interface OrganizationUser {
  id: number;
  name: string;
  email?: string;
  is_super?: number;
}

export interface OrganizationNode {
  id: number;
  parent_id: number;
  name: string;
  code?: string;
  type: 'org';
  children: OrganizationNode[];
  users: OrganizationUser[];
}

export interface MenuNode {
  id: number;
  name: string;
  checked: boolean;
  children?: MenuNode[];
}

export async function getOrganizationTree() {
  return request.get<OrganizationNode[]>('/meta-organizations/tree');
}

export async function createOrganization(data: { parent_id: number; name: string; code?: string; sort?: number }) {
  return request.post('/meta-organizations', data);
}

export async function createUser(data: { name: string; email: string; password: string; organization_id: number; is_super?: number }) {
  return request.post('/user/register', data);
}

export async function getUserMenus(userId: number) {
  return request.get<MenuNode[]>('/meta-users/' + userId + '/menus');
}

export async function assignUserMenus(userId: number, menuIds: number[]) {
  return request.post('/meta-users/' + userId + '/menus', { menu_ids: menuIds });
}

export async function removeUserMenu(userId: number, menuId: number) {
  return request.delete('/meta-users/' + userId + '/menus/' + menuId);
}

export async function updateUser(userId: number, data: { name?: string; email?: string; password?: string; is_super?: number; organization_id?: number }) {
  return request.put('/user/update/' + userId, data);
}

export async function getOrganizationList() {
  return request.get('/meta-organizations');
}

export async function updateOrganization(orgId: number, data: { parent_id?: number; name?: string; code?: string; sort?: number }) {
  return request.put('/meta-organizations/' + orgId, data);
}
