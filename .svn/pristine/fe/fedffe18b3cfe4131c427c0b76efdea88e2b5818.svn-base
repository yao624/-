import type { MaterialEditorTask, MaterialEditItem, TaskFilter, TaskStatus, MaterialEditStatus } from './types';

const statuses: TaskStatus[] = ['pending', 'processing', 'completed', 'failed'];
const materialStatuses: MaterialEditStatus[] = ['success', 'failed', 'pending'];
const creators = ['张三', '李四', '王五', '赵六', '钱七', '孙八', '周九', '吴十'];
const editContents = [
  '调整亮度 +10%',
  '裁剪为1:1',
  '添加水印',
  '调整饱和度 +20%',
  '去除背景',
  '添加文字',
  '调整对比度',
  '缩放至1080x1080',
];
const reasons = [
  'AI自动优化',
  '用户手动调整',
  '批量处理规则',
  '模板应用',
  '智能裁剪',
];

// 生成随机素材编辑项
function generateMockMaterialItems(taskId: string, count: number): MaterialEditItem[] {
  const items: MaterialEditItem[] = [];
  for (let i = 1; i <= count; i++) {
    const status = materialStatuses[Math.floor(Math.random() * materialStatuses.length)];
    items.push({
      id: `MAT${taskId}${String(i).padStart(3, '0')}`,
      name: `素材_${taskId}_${i}.jpg`,
      previewUrl: `https://via.placeholder.com/80?text=${taskId}-${i}`,
      originalPreviewUrl: `https://via.placeholder.com/80?text=ORIG-${i}`,
      status,
      editContent: editContents[Math.floor(Math.random() * editContents.length)],
      reason: reasons[Math.floor(Math.random() * reasons.length)],
    });
  }
  return items;
}

// 生成随机任务数据
function generateMockTasks(count: number = 40): MaterialEditorTask[] {
  const tasks: MaterialEditorTask[] = [];

  for (let i = 1; i <= count; i++) {
    const status = statuses[Math.floor(Math.random() * statuses.length)];

    tasks.push({
      id: `TASK${String(i).padStart(6, '0')}`,
      status,
      createdAt: generateRandomDate(i),
      createdBy: creators[Math.floor(Math.random() * creators.length)],
      folderId: status === 'completed' ? `FOLDER${i}` : undefined,
      folderName: status === 'completed' ? `素材文件夹${i}` : undefined,
    });
  }

  return tasks;
}

function generateRandomDate(index: number): string {
  const now = new Date();
  const daysAgo = Math.floor(index / 2);
  const date = new Date(now.getTime() - daysAgo * 24 * 60 * 60 * 1000);

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');

  return `${year}-${month}-${day} ${hours}:${minutes}`;
}

// 模拟数据存储
let allTasks = generateMockTasks(40);

// 为每个任务生成素材编辑项的缓存
const materialItemsCache = new Map<string, MaterialEditItem[]>();

function getMaterialItemsForTask(taskId: string): MaterialEditItem[] {
  if (!materialItemsCache.has(taskId)) {
    const count = Math.floor(Math.random() * 30) + 10; // 10-40个素材
    materialItemsCache.set(taskId, generateMockMaterialItems(taskId, count));
  }
  return materialItemsCache.get(taskId)!;
}

export async function getTaskList(params: {
  page: number;
  pageSize: number;
  taskId?: string;
  status?: TaskStatus;
  startTime?: string;
  endTime?: string;
  createdBy?: string;
}): Promise<{ data: MaterialEditorTask[]; total: number }> {
  await new Promise((resolve) => setTimeout(resolve, 300));

  let filtered = [...allTasks];

  // 任务ID筛选
  if (params.taskId) {
    filtered = filtered.filter((t) => t.id.toLowerCase().includes(params.taskId!.toLowerCase()));
  }

  // 状态筛选
  if (params.status) {
    filtered = filtered.filter((t) => t.status === params.status);
  }

  // 创建人筛选
  if (params.createdBy) {
    filtered = filtered.filter((t) => t.createdBy.includes(params.createdBy!));
  }

  // 时间范围筛选
  if (params.startTime) {
    filtered = filtered.filter((t) => t.createdAt >= params.startTime!);
  }
  if (params.endTime) {
    filtered = filtered.filter((t) => t.createdAt <= params.endTime!);
  }

  const total = filtered.length;
  const start = (params.page - 1) * params.pageSize;
  const end = start + params.pageSize;
  const data = filtered.slice(start, end);

  return { data, total };
}

export async function getTaskMaterialItems(params: {
  taskId: string;
  page: number;
  pageSize: number;
}): Promise<{ data: MaterialEditItem[]; total: number }> {
  await new Promise((resolve) => setTimeout(resolve, 200));

  const allItems = getMaterialItemsForTask(params.taskId);
  const total = allItems.length;
  const start = (params.page - 1) * params.pageSize;
  const end = start + params.pageSize;
  const data = allItems.slice(start, end);

  return { data, total };
}

export async function deleteTask(id: string): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve, 200));
  const index = allTasks.findIndex((t) => t.id === id);
  if (index !== -1) {
    allTasks.splice(index, 1);
  }
}

export async function batchDeleteTasks(ids: string[]): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve, 300));
  allTasks = allTasks.filter((t) => !ids.includes(t.id));
}
