import request from '@/utils/request';

export interface BasicFormData {
  title?: string;
  date?: [string, string];
  goal?: string;
  standard?: string;
}

export interface BasicFormResponse {
  saveId: number;
}

/**
 * Save basic form data to backend
 *
 * @param formData
 * @return Promise<{ message: string; code: number }>
 */
export function saveBasicFormData(
  formData: BasicFormData,
): Promise<{ message: string; code: number }> {
  return request.post('forms/basic-form', formData);
}
