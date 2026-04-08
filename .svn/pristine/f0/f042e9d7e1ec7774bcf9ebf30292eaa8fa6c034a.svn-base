// 广告账户管理页面模拟数据

import type { AdAccount, AdAccountListParams, AdAccountListResponse, PlatformType } from './types';
import dayjs from 'dayjs';

// 用户接口
export interface User {
  id: string;
  name: string;
  platform?: PlatformType;
  autoBind: boolean;
  switching?: boolean;
  boundCount: number;
  personalAccount: string;
  authorizer: string;
  lastAuthTime: string;
  authStatus: 'authorized' | 'unauthorized' | 'expired' | 'failed';
  authFailReason?: string;
}

export interface UserListParams {
  page?: number;
  pageSize?: number;
  keyword?: string;
  platform?: PlatformType;
  autoBind?: boolean;
  personalAccount?: string;
  authorizer?: string;
  authStatus?: string;
}

export interface UserListResponse {
  data: User[];
  total: number;
}

// 生成模拟广告账户数据
function generateMockAdAccounts(count: number = 50): AdAccount[] {
  const authorizationStatuses: Array<'authorized' | 'expired' | 'pending'> = ['authorized', 'expired', 'pending'];
  const accountStatuses: Array<'active' | 'disabled' | 'pending'> = ['active', 'disabled', 'pending'];
  const bms = ['BM_001', 'BM_002', 'BM_003', 'BM_004', 'BM_005'];
  const owners = ['张三', '李四', '王五', '赵六', '钱七'];
  const assistantsList = [
    ['助手A', '助手B'],
    ['助手C'],
    ['助手D', '助手E', '助手F'],
    ['助手G'],
    [],
  ];
  const platforms: PlatformType[] = ['meta', 'google', 'tiktok'];

  return Array.from({ length: count }, (_, i) => {
    const authStatus = authorizationStatuses[i % 3];
    const accStatus = accountStatuses[i % 3];
    const accountCount = (i % 3) + 1;
    const platform = platforms[i % platforms.length];

    const account: AdAccount = {
      id: `${platform === 'meta' ? 'act' : platform.toUpperCase()}_${1000000000 + i}`,
      name: `${platform.toUpperCase()}_广告账户_${i + 1}`,
      personalAccountCount: accountCount,
      personalAccounts: Array.from({ length: accountCount }, (_, j) => `${platform.toUpperCase()}_Account_${i}_${j + 1}`),
      authorizationStatus: authStatus,
      accountStatus: accStatus,
      owner: owners[i % owners.length],
      assistants: assistantsList[i % assistantsList.length],
      authorizationTime: dayjs().subtract(i % 30, 'day').format('YYYY-MM-DD HH:mm:ss'),
      platform,
      accountNote: `备注${i + 1}`,
    };

    // FB和Google有BM/MCC
    if (platform === 'meta' || platform === 'google') {
      account.bm = bms[i % bms.length];
    }

    // TikTok有余额和主体
    if (platform === 'tiktok') {
      account.balance = `$${(Math.random() * 1000).toFixed(2)}`;
      account.entity = `主体_${(i % 5) + 1}`;
    }

    return account;
  });
}

// 获取广告账户列表数据
export async function getAdAccountList(params?: AdAccountListParams): Promise<AdAccountListResponse> {
  await new Promise((resolve) => setTimeout(resolve, 300));

  const page = params?.page || 1;
  const pageSize = params?.pageSize || 20;
  const keyword = params?.keyword || '';
  const platform = params?.platform;

  let allData = generateMockAdAccounts(50);

  // 根据平台过滤
  if (platform) {
    allData = allData.filter((item) => item.platform === platform);
  }

  // 根据关键词过滤
  if (keyword) {
    allData = allData.filter(
      (item) =>
        item.id.includes(keyword) ||
        item.name.includes(keyword) ||
        item.bm?.includes(keyword) ||
        item.owner.includes(keyword) ||
        item.accountNote?.includes(keyword),
    );
  }

  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  const data = allData.slice(start, end);

  return {
    data,
    total: allData.length,
  };
}

// 生成模拟用户数据
function generateMockUsers(count: number = 30): User[] {
  const authStatuses: Array<'authorized' | 'unauthorized' | 'expired' | 'failed'> = ['authorized', 'unauthorized', 'expired', 'failed'];
  const platforms: PlatformType[] = ['meta', 'google', 'tiktok'];
  const failReasons = [
    'Token已过期',
    '权限不足',
    '账户已禁用',
    '网络连接失败',
  ];

  return Array.from({ length: count }, (_, i) => {
    const authStatus = authStatuses[i % 4];
    const platform = platforms[i % platforms.length];
    const boundCount = Math.floor(Math.random() * 20) + 1;
    const autoBind = i % 2 === 0;

    return {
      id: `user_${1000 + i}`,
      name: `${platform.toUpperCase()}_用户_${i + 1}`,
      platform,
      autoBind,
      boundCount,
      personalAccount: `${platform.toUpperCase()}_Personal_Account_${i + 1}`,
      authorizer: `授权人${(i % 5) + 1}`,
      lastAuthTime: dayjs().subtract(i % 30, 'day').format('YYYY-MM-DD HH:mm:ss'),
      authStatus,
      authFailReason: authStatus === 'failed' ? failReasons[i % failReasons.length] : undefined,
    };
  });
}

// 获取用户列表数据
export async function getUserList(params?: UserListParams): Promise<UserListResponse> {
  await new Promise((resolve) => setTimeout(resolve, 300));

  const page = params?.page || 1;
  const pageSize = params?.pageSize || 20;
  const keyword = params?.keyword || '';
  const platform = params?.platform;
  const autoBind = params?.autoBind;
  const personalAccount = params?.personalAccount;
  const authorizer = params?.authorizer;
  const authStatus = params?.authStatus;

  let allData = generateMockUsers(30);

  // 根据平台过滤
  if (platform) {
    allData = allData.filter((item) => item.platform === platform);
  }

  // 根据关键词过滤
  if (keyword) {
    allData = allData.filter(
      (item) =>
        item.id.includes(keyword) ||
        item.name.includes(keyword) ||
        item.personalAccount.includes(keyword),
    );
  }

  // 根据自动绑定开关过滤
  if (autoBind !== undefined) {
    allData = allData.filter((item) => item.autoBind === autoBind);
  }

  // 根据个人号过滤
  if (personalAccount) {
    allData = allData.filter((item) => item.personalAccount.includes(personalAccount));
  }

  // 根据授权人过滤
  if (authorizer) {
    allData = allData.filter((item) => item.authorizer.includes(authorizer));
  }

  // 根据授权状态过滤
  if (authStatus) {
    allData = allData.filter((item) => item.authStatus === authStatus);
  }

  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  const data = allData.slice(start, end);

  return {
    data,
    total: allData.length,
  };
}

// 获取单个广告账户详情
export async function getAdAccountDetail(id: string): Promise<AdAccount | null> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const allData = generateMockAdAccounts(50);
  return allData.find((item) => item.id === id) || null;
}

// 删除广告账户
export async function deleteAdAccount(id: string): Promise<boolean> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  // 模拟删除操作
  console.log(`Deleting ad account: ${id}`);
  return true;
}

// 批量删除广告账户
export async function batchDeleteAdAccounts(ids: string[]): Promise<boolean> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  console.log(`Batch deleting ad accounts: ${ids.join(', ')}`);
  return true;
}

// 同步广告账户数据
export async function syncAdAccountData(): Promise<boolean> {
  await new Promise((resolve) => setTimeout(resolve, 500));
  console.log('Syncing ad account data...');
  return true;
}
