// 广告账户管理页面类型定义

export type AuthorizationStatus = 'authorized' | 'expired' | 'pending';
export type AccountStatus = 'active' | 'disabled' | 'pending';
export type FilterTab = 'users' | 'adAccounts';
export type PlatformType = 'meta' | 'google' | 'tiktok';

export interface Platform {
  key: PlatformType;
  name: string;
  nameEn: string;
  icon: string;
  color: string;
}

// 用户账号（对应 /api/v2/account-manage/accounts）
export interface UserAccount {
  id: string;
  name: string;
  username: string;
  platform: PlatformType;
  autoBind: boolean;
  switching?: boolean;
  boundCount: number;
  personalAccount: string;
  authorizer: string;
  lastAuthTime: string;
  authStatus: 'authorized' | 'unauthorized' | 'expired' | 'failed' | 'pending';
  authFailReason?: string | null;
}

// 用户信息
export interface UserInfo {
  id: number;
  name: string;
}

// 广告账户（对应 /api/v2/account-manage/ad-accounts）
export interface AdAccount {
  id: string;
  name: string;
  source_id?: string;              // 原始广告账户ID
  platform: PlatformType;
  bm?: string;                     // Business Manager (FB) / MCC (Google)
  balance?: string | null;         // 余额
  personalAccountCount: number;
  personalAccounts: string[];
  authorizationStatus: AuthorizationStatus;
  accountStatus: AccountStatus;
  owner: UserInfo;                 // 所属人员
  assistants: UserInfo[];          // 协助人员列表
  authorizationTime: string;
  accountNote?: string;
  entity?: string;                 // 主体 (TikTok)
}

export interface AdAccountListParams {
  page?: number;
  pageSize?: number;
  tab?: FilterTab;
  keyword?: string;
  platform?: PlatformType;
}

export interface AdAccountListResponse {
  data: AdAccount[];
  total: number;
}
