import type { Material, MaterialFilter, PlatformType } from './types';

const platforms: PlatformType[] = ['meta', 'google', 'tiktok', 'kwai'];
const shapes: Material['shape'][] = ['square', 'landscape', 'portrait', 'unknown'];
const formats: Material['format'][] = ['mp4', 'jpg', 'png', 'gif', 'webp'];
const sources: Material['source'][] = ['upload', 'sync', 'import'];

// 生成随机素材数据
function generateMockMaterials(count: number = 50): Material[] {
  const materials: Material[] = [];

  for (let i = 1; i <= count; i++) {
    const platform = platforms[Math.floor(Math.random() * platforms.length)];
    const shape = shapes[Math.floor(Math.random() * shapes.length)];
    const format = formats[Math.floor(Math.random() * formats.length)];
    const source = sources[Math.floor(Math.random() * sources.length)];

    const isVideo = format === 'mp4';
    const size = generateSize(shape);
    const duration = isVideo ? `${Math.floor(Math.random() * 60) + 5}s` : '-';

    const accountCount = Math.floor(Math.random() * 5);
    const usedAccounts: string[] = [];
    for (let j = 0; j < accountCount; j++) {
      usedAccounts.push(`account_${Math.floor(Math.random() * 1000)}`);
    }

    const hasRejection = Math.random() > 0.8;

    materials.push({
      id: `MAT${String(i).padStart(6, '0')}`,
      name: `${platform.toUpperCase()}素材_${i}.${format}`,
      platform,
      usedAccounts,
      ownerAccount: `user_${Math.floor(Math.random() * 100)}`,
      size,
      duration,
      shape,
      format,
      source,
      remark: Math.random() > 0.5 ? `备注信息${i}` : '',
      createdAt: generateRandomDate(i),
      rejectionInfo: hasRejection ? `违规原因: 内容不符合规范` : null,
      thumbnail: `https://via.placeholder.com/100?text=MAT${i}`,
    });
  }

  return materials;
}

function generateSize(shape: Material['shape']): string {
  const sizes: Record<Material['shape'], string[]> = {
    square: ['1080x1080', '512x512'],
    landscape: ['1920x1080', '1280x720', '1080x608'],
    portrait: ['1080x1920', '720x1280'],
    unknown: ['800x600'],
  };
  const sizeOptions = sizes[shape];
  return sizeOptions[Math.floor(Math.random() * sizeOptions.length)];
}

function generateRandomDate(index: number): string {
  const now = new Date();
  const daysAgo = Math.floor(index / 3);
  const date = new Date(now.getTime() - daysAgo * 24 * 60 * 60 * 1000);

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');

  return `${year}-${month}-${day} ${hours}:${minutes}`;
}

// 模拟数据存储
let allMaterials = generateMockMaterials(50);

export async function getMaterialList(params: {
  page: number;
  pageSize: number;
  keyword?: string;
  platform?: PlatformType;
  materialType?: string;
  startTime?: string;
  endTime?: string;
  adAccount?: string;
  fbPersonal?: string;
}): Promise<{ data: Material[]; total: number }> {
  await new Promise((resolve) => setTimeout(resolve, 300));

  let filtered = [...allMaterials];

  // 关键词筛选
  if (params.keyword) {
    const keyword = params.keyword.toLowerCase();
    filtered = filtered.filter(
      (m) =>
        m.name.toLowerCase().includes(keyword) ||
        m.id.toLowerCase().includes(keyword) ||
        m.remark.toLowerCase().includes(keyword)
    );
  }

  // 渠道筛选
  if (params.platform) {
    filtered = filtered.filter((m) => m.platform === params.platform);
  }

  // 素材类型筛选
  if (params.materialType) {
    if (params.materialType === 'video') {
      filtered = filtered.filter((m) => m.format === 'mp4');
    } else if (params.materialType === 'image') {
      filtered = filtered.filter((m) => ['jpg', 'png', 'gif', 'webp'].includes(m.format));
    }
  }

  // 时间范围筛选
  if (params.startTime) {
    filtered = filtered.filter((m) => m.createdAt >= params.startTime!);
  }
  if (params.endTime) {
    filtered = filtered.filter((m) => m.createdAt <= params.endTime!);
  }

  // 广告账户筛选
  if (params.adAccount) {
    filtered = filtered.filter((m) => m.usedAccounts.includes(params.adAccount!));
  }

  // FB个人号筛选
  if (params.fbPersonal) {
    filtered = filtered.filter((m) => m.ownerAccount === params.fbPersonal);
  }

  const total = filtered.length;
  const start = (params.page - 1) * params.pageSize;
  const end = start + params.pageSize;
  const data = filtered.slice(start, end);

  return { data, total };
}

export async function deleteMaterial(id: string): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve, 200));
  const index = allMaterials.findIndex((m) => m.id === id);
  if (index !== -1) {
    allMaterials.splice(index, 1);
  }
}

export async function batchDeleteMaterials(ids: string[]): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  allMaterials = allMaterials.filter((m) => !ids.includes(m.id));
}
