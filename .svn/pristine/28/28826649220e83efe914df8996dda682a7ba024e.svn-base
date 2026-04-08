export type PlatformType = 'meta' | 'google' | 'tiktok' | 'kwai' | 'fb';

export type MaterialShape = '正方形' | 'landscape' | 'portrait' | 'square' | 'unknown';

export type MaterialFormat = 'mp4' | 'jpg' | 'png' | 'gif' | 'webp';

export type MaterialSource = 'upload' | 'sync' | 'import';

export interface Material {
  id: string;                    // 素材Id
  materialId: string;             // 素材ID
  name: string;                  // 名称
  channel: string;               // 渠道
  useAccount: string;            // 使用账户
  useAccountName: string;        // 使用账户名称
  belongAccount: string;         // 所属账户
  belongAccountName: string;     // 所属账户名称
  size: string;                  // 尺寸
  duration: string;              // 时长
  shape: string;                 // 形状
  format: string;                // 格式
  source: string | null;         // 来源
  rejectInfo: string | null;     // 拒审信息
  materialNote: string;          // 素材备注
  createTime: string;            // 创建时间
  thumbnail?: string;            // 缩略图
}

export interface MaterialFilter {
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
}

export interface MaterialListResponse {
  data: Material[];
  pageSize: number;
  pageNo: number;
  totalPage: number;
  totalCount: number;
}
