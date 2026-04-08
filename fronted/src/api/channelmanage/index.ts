import request from '@/utils/request';

/**
 * 获取媒体素材列表
 * @param params 查询参数
 */
export interface MediaMaterialListParams {
  name?: string;
  materialId?: string;
  channel?: string;
  useAccount?: string;
  belongAccount?: string;
  size?: string;
  duration?: string;
  shape?: string;
  format?: string;
  source?: string;
  materialNote?: string;
  createTimeStart?: string;
  createTimeEnd?: string;
  rejectInfo?: string;
  pageNo?: number;
  pageSize?: number;
}

export interface MediaMaterialItem {
  id: string;
  materialId: string;
  name: string;
  channel: string;
  useAccount: string;
  useAccountName: string;
  belongAccount: string;
  belongAccountName: string;
  size: string;
  duration: string;
  shape: string;
  format: string;
  source: string | null;
  rejectInfo: string | null;
  materialNote: string;
  createTime: string;
}

export interface MediaMaterialListResponse {
  status: boolean;
  message: string;
  data: {
    data: MediaMaterialItem[];
    pageSize: number;
    pageNo: number;
    totalPage: number;
    totalCount: number;
  };
}

export async function getMediaMaterialList(params: MediaMaterialListParams) {
  return request.get<any, MediaMaterialListResponse>('/material-library/media-materials/list', {
    params,
  });
}
