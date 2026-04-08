// 广告模板管理页面模拟数据

import type { AdTemplate, TemplateListParams, TemplateListResponse, PlatformType, TemplateStatus, TemplateType } from './types';
import dayjs from 'dayjs';

// 生成模拟广告模板数据
function generateMockTemplates(count: number = 40): AdTemplate[] {
  const platforms: PlatformType[] = ['meta', 'google', 'tiktok', 'kwai'];
  const statuses: TemplateStatus[] = ['active', 'inactive', 'draft', 'archived'];
  const types: TemplateType[] = ['campaign', 'adset', 'ad', 'creative'];
  const creators = ['张三', '李四', '王五', '赵六', '钱七'];
  const tagSets = [
    ['电商', '促销'],
    ['品牌', '形象'],
    ['应用', '下载'],
    ['游戏', '娱乐'],
    ['教育', '培训'],
    ['金融', '理财'],
  ];

  return Array.from({ length: count }, (_, i) => {
    const platform = platforms[i % platforms.length];
    const status = statuses[i % statuses.length];
    const type = types[i % types.length];

    return {
      id: `template_${1000 + i}`,
      name: `${platform.toUpperCase()}_${type}_${i + 1}号模板`,
      description: `这是一个${platform}平台的${type}模板，用于${status === 'active' ? '活跃' : '测试'}广告投放。`,
      platform,
      type,
      status,
      createdBy: creators[i % creators.length],
      createdAt: dayjs().subtract(i % 60, 'day').format('YYYY-MM-DD HH:mm:ss'),
      updatedAt: dayjs().subtract(i % 10, 'day').format('YYYY-MM-DD HH:mm:ss'),
      isOwner: i % 3 !== 0, // 部分模板不是当前用户创建
      tags: tagSets[i % tagSets.length],
      thumbnail: i % 2 === 0 ? `https://via.placeholder.com/60x40?text=T${i + 1}` : undefined,
    };
  });
}

// 获取广告模板列表数据
export async function getTemplateList(params?: TemplateListParams): Promise<TemplateListResponse> {
  await new Promise((resolve) => setTimeout(resolve, 300));

  const page = params?.page || 1;
  const pageSize = params?.pageSize || 20;
  const keyword = params?.keyword || '';
  const platform = params?.platform;
  const status = params?.status;
  const type = params?.type;

  let allData = generateMockTemplates(40);

  // 根据关键词过滤
  if (keyword) {
    allData = allData.filter(
      (item) =>
        item.name.includes(keyword) ||
        item.description?.includes(keyword) ||
        item.tags?.some(tag => tag.includes(keyword))
    );
  }

  // 根据平台过滤
  if (platform) {
    allData = allData.filter((item) => item.platform === platform);
  }

  // 根据状态过滤
  if (status) {
    allData = allData.filter((item) => item.status === status);
  }

  // 根据类型过滤
  if (type) {
    allData = allData.filter((item) => item.type === type);
  }

  const start = (page - 1) * pageSize;
  const end = start + pageSize;
  const data = allData.slice(start, end);

  return {
    data,
    total: allData.length,
  };
}

// 获取单个广告模板详情
export async function getTemplateDetail(id: string): Promise<AdTemplate | null> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  const allData = generateMockTemplates(40);
  return allData.find((item) => item.id === id) || null;
}

// 创建广告模板
export async function createTemplate(data: Partial<AdTemplate>): Promise<boolean> {
  await new Promise((resolve) => setTimeout(resolve, 500));
  console.log('Creating template:', data);
  return true;
}

// 更新广告模板
export async function updateTemplate(id: string, data: Partial<AdTemplate>): Promise<boolean> {
  await new Promise((resolve) => setTimeout(resolve, 500));
  console.log('Updating template:', id, data);
  return true;
}

// 删除广告模板
export async function deleteTemplate(id: string): Promise<boolean> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  console.log('Deleting template:', id);
  return true;
}

// 批量删除广告模板
export async function batchDeleteTemplates(ids: string[]): Promise<boolean> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  console.log('Batch deleting templates:', ids.join(', '));
  return true;
}

// 复制广告模板
export async function cloneTemplate(id: string): Promise<boolean> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  console.log('Cloning template:', id);
  return true;
}
