// Stub API functions for user/template-related operations
// TODO: Implement actual API calls when backend is ready

import request from '@/utils/request';
import { Message } from 'view-ui-plus';

/**
 * Upload image
 */
export async function uploadImg(formData: FormData) {
  // Stub implementation
  return Promise.resolve({
    data: [{
      id: 'stub-img-id',
      url: '',
    }],
  });
}

/**
 * Create template
 */
export async function createdTempl(params: any) {
  // Stub implementation
  console.log('createdTempl called with:', params);
  return Promise.resolve({
    data: {
      data: {
        id: 'stub-tmpl-id',
        ...params.data,
      },
    },
  });
}

/**
 * Get template info
 */
export async function getTmplInfo(id: string) {
  // Stub implementation
  return Promise.resolve({
    data: {
      id,
      name: 'Stub Template',
      json: '{}',
      img: { id: 'stub-img-id' },
    },
  });
}

/**
 * Update template
 */
export async function updataTempl(id: string, data: any) {
  // Stub implementation
  console.log('updataTempl called with:', id, data);
  return Promise.resolve({ success: true });
}

/**
 * Remove template
 */
export async function removeTempl(id: string) {
  // Stub implementation
  console.log('removeTempl called with:', id);
  return Promise.resolve({ success: true });
}

/**
 * Get template list
 */
export async function getTmplList(params?: any) {
  // Stub implementation
  return Promise.resolve({
    data: [],
    meta: {
      pagination: {
        page: 1,
        pageSize: 10,
        pageCount: 1,
        total: 0,
      },
    },
  });
}

// Re-export types from user/login for convenience
export type { LoginParams, LoginResp, UserInfo, Role, RouteItem, Action } from './user/login';
export { postAccountLogin, postLogout, getCurrentUser, getCurrentUserNav } from './user/login';
