import request from '@/utils/request';
import type { CSSProperties } from 'vue';

export type NoticeIconData = {
  avatar?: string;
  title?: string;
  description?: string;
  datetime?: string;
  extra?: string;
  style?: CSSProperties;
  key?: string | number;
  read?: boolean;
};

export type NoticeItem = {
  id: string;
  type: string;
  status: string;
} & NoticeIconData;

export type NoticesQueryParams = {
  pageNo?: number;
  pageSize?: number;
  // 后端字段：type/status 用于筛选
  type?: string;
  status?: 'unread' | 'read';
};

export type BackendNoticeRow = {
  id: string | number;
  type: string;
  title: string;
  content: string;
  extra?: any;
  status: string; // unread/read
  read_at?: string | null;
  created_at?: string;
};

export type BackendNoticesPage = {
  data: BackendNoticeRow[];
  totalCount: number;
  pageNo: number;
  pageSize: number;
};

export type NoticesPageResult = {
  list: NoticeItem[];
  totalCount: number;
  pageNo: number;
  pageSize: number;
};

function backendExtraToText(extra: any): string | undefined {
  if (extra === null || extra === undefined) return undefined;
  if (typeof extra === 'string') return extra;
  if (typeof extra !== 'object') return String(extra);

  // 优先把一些常见业务字段展示成短文本
  if (extra.level) return String(extra.level);
  if (extra.jump_to) return String(extra.jump_to);
  if (extra.job_id) return `job_id:${extra.job_id}`;
  if (extra.batch_id) return `batch_id:${extra.batch_id}`;
  if (extra.result) return `result:${extra.result}`;

  try {
    return JSON.stringify(extra);
  } catch (_e) {
    return undefined;
  }
}

function mapBackendTypeToUiType(backendType: string): string {
  // 适配前端通知下拉组件 tabs：notification/message/event
  if (backendType === 'task') return 'message';
  if (backendType === 'ai') return 'event';
  return 'notification';
}

function mapBackendToNoticeItem(row: BackendNoticeRow): NoticeItem {
  const read = row.status === 'read';
  return {
    id: String(row.id),
    type: mapBackendTypeToUiType(row.type),
    // 用于 tag 颜色的 status 字段：当前后端不直接对应，置空避免误着色
    status: '',
    title: row.title,
    description: row.content,
    datetime: row.created_at || row.read_at || '',
    extra: backendExtraToText(row.extra),
    read,
  };
}

export async function queryNoticesPage(params: NoticesQueryParams = {}): Promise<NoticesPageResult> {
  // 由于 request.ts 使用响应拦截器返回 response.data，
  // 这里对返回做一次强制断言，避免 axios 类型推断出 AxiosResponse 的联合类型。
  const payload = (await request.get<any>('/notices', { params })) as unknown;

  // 兼容本项目 mock：GET /api/notices 直接返回数组（无分页字段）
  if (Array.isArray(payload)) {
    const list = payload
      .map((row: any) => {
        const read = Boolean(row.read);
        return {
          id: String(row.id),
          type: typeof row.type === 'string' ? row.type : 'notification',
          status: '',
          title: row.title,
          description: row.description || row.content || '',
          datetime: row.datetime || '',
          extra: typeof row.extra === 'string' ? row.extra : backendExtraToText(row.extra),
          read,
        } as NoticeItem;
      })
      // mock 的 notices 结构没有 totalCount，这里做一个合理兜底
      .filter(Boolean);

    return {
      list,
      totalCount: list.length,
      pageNo: 1,
      pageSize: list.length,
    };
  }

  const page = (payload || ({} as BackendNoticesPage)) as BackendNoticesPage;
  const rows = Array.isArray(page.data) ? page.data : [];
  return {
    list: rows.map(mapBackendToNoticeItem),
    totalCount: typeof page.totalCount === 'number' ? page.totalCount : rows.length,
    pageNo: typeof page.pageNo === 'number' ? page.pageNo : 1,
    pageSize: typeof page.pageSize === 'number' ? page.pageSize : rows.length,
  };
}

// 给顶部下拉角标用：只需要返回列表数组
export async function queryNotices(): Promise<NoticeItem[]> {
  try {
    const res = await queryNoticesPage();
    return res.list;
  } catch (_e) {
    return [];
  }
}

export async function getNoticeDetail(id: string | number): Promise<NoticeItem | null> {
  const res = await request.get<any>(`/notices/${id}`);
  const row: BackendNoticeRow | undefined = res?.data;
  if (!row) return null;
  return mapBackendToNoticeItem(row);
}

export async function changeNoticeReadState(ids: Array<string | number>): Promise<any> {
  return request.post('/change-notices-read', { ids });
}
