// Stub API functions for admin-related operations
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
      id: 'stub-admin-img-id',
      url: '',
    }],
  });
}

/**
 * Create template
 */
export async function createdTempl(params: any) {
  // Stub implementation
  console.log('admin createdTempl called with:', params);
  return Promise.resolve({
    data: {
      id: 'stub-admin-tmpl-id',
      ...params,
    },
  });
}

/**
 * Get template
 */
export async function getTempl(id: string) {
  // Stub implementation
  return Promise.resolve({
    data: {
      id,
      name: 'Stub Admin Template',
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
  console.log('admin updataTempl called with:', id, data);
  return Promise.resolve({ success: true });
}

/**
 * Delete image
 */
export async function deleteImg(id: string) {
  // Stub implementation
  console.log('deleteImg called with:', id);
  return Promise.resolve({ success: true });
}

/**
 * Get token
 */
export async function getToken() {
  // Stub implementation
  return Promise.resolve(localStorage.getItem('token') || '');
}

/**
 * Set token
 */
export async function setToken(token: string) {
  // Stub implementation
  localStorage.setItem('token', token);
  return Promise.resolve({ success: true });
}
