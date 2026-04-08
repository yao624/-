import request from '@/utils/request';

const BASE = '/meta-ad-creation-creative-groups';

export interface CreativeGroupItem {
  id: string;
  name: string;
  creativeType: string;
  materialIds: string[];
  materials: any[];
  postIds: string[];
  format: string;
  settingMode: string;
  deepLink?: string;
  body?: string;
  title?: string;
  cta?: string;
  tags?: any;
  videoOptimization?: string;
  imageOptimization?: string;
  multilang: boolean;
  createdAt?: string;
  createdAtText?: string;
}

/** 创意组列表 */
export function listMetaAdCreationCreativeGroups() {
  return request.get<{ success: boolean; data: CreativeGroupItem[] }>(BASE);
}

/** 新建创意组 */
export function createMetaAdCreationCreativeGroup(params: {
  name: string;
  creative_type?: string;
  material_ids?: string[];
  materials?: any[];
  post_ids?: string[];
  format?: string;
  setting_mode?: string;
  deep_link?: string;
  body?: string;
  title?: string;
  cta?: string;
  tags?: any;
  video_optimization?: string;
  image_optimization?: string;
  multilang?: boolean;
}) {
  return request.post<{ success: boolean; data: CreativeGroupItem; message?: string }>(BASE, params);
}

/** 单条详情 */
export function getMetaAdCreationCreativeGroup(id: string) {
  return request.get<{ success: boolean; data: CreativeGroupItem }>(`${BASE}/${id}`);
}

/** 更新创意组 */
export function updateMetaAdCreationCreativeGroup(
  id: string,
  params: {
    name: string;
    creative_type?: string;
    material_ids?: string[];
    materials?: any[];
    post_ids?: string[];
    format?: string;
    setting_mode?: string;
    deep_link?: string;
    body?: string;
    title?: string;
    cta?: string;
    tags?: any;
    video_optimization?: string;
    image_optimization?: string;
    multilang?: boolean;
  },
) {
  return request.put<{ success: boolean; data: CreativeGroupItem; message?: string }>(`${BASE}/${id}`, params);
}

/** 删除创意组 */
export function deleteMetaAdCreationCreativeGroup(id: string) {
  return request.delete<{ success: boolean; message?: string }>(`${BASE}/${id}`);
}
