<template>
  <page-container title="同步日志">
    <div class="page-desc">查看业务管理器的同步状态和日志记录</div>

    <a-row :gutter="12" class="stats-row">
      <a-col :span="6"><a-card><div class="stats-item"><div class="stats-label">总业务管理器</div><div class="stats-value">{{ stats.business_managers?.total || 0 }}</div></div></a-card></a-col>
      <a-col :span="6"><a-card><div class="stats-item"><div class="stats-label">已完成同步</div><div class="stats-value">{{ stats.sync_status?.completed || 0 }}</div></div></a-card></a-col>
      <a-col :span="6"><a-card><div class="stats-item"><div class="stats-label">同步失败</div><div class="stats-value">{{ stats.sync_status?.failed || 0 }}</div></div></a-card></a-col>
      <a-col :span="6"><a-card><div class="stats-item"><div class="stats-label">正在同步</div><div class="stats-value">{{ stats.sync_status?.running || 0 }}</div></div></a-card></a-col>
    </a-row>

    <a-card :bordered="false">
      <a-space wrap>
        <a-input v-model:value="filters.search" allow-clear placeholder="搜索业务管理器名称或ID" style="width: 220px" @pressEnter="handleSearch" />
        <a-select v-model:value="filters.status" allow-clear placeholder="状态" style="width: 150px">
          <a-select-option value="success">已完成</a-select-option>
          <a-select-option value="failed">有失败</a-select-option>
          <a-select-option value="running">进行中</a-select-option>
        </a-select>
        <a-button type="primary" @click="handleSearch">搜索</a-button>
        <a-button @click="handleReset">重置</a-button>
        <a-button @click="handleRefresh">刷新</a-button>
      </a-space>
    </a-card>

    <a-card :bordered="false" style="margin-top: 12px">
      <template #title>同步日志列表（共 {{ pagination.total }} 条）</template>
      <a-table :loading="loading" :columns="columns" :data-source="dataSource" :pagination="pagination" :row-key="record => record.id" @change="handleTableChange">
        <template #bodyCell="{ column, record }">
          <template v-if="column.dataIndex === 'status'">
            <a-tag :color="record.status === 'success' ? 'green' : record.status === 'running' ? 'blue' : 'red'">{{ record.status || '-' }}</a-tag>
          </template>
          <template v-else-if="column.dataIndex === 'created_at'">{{ formatDateTime(record.created_at) }}</template>
          <template v-else-if="column.dataIndex === 'message'"><span>{{ record.message || '-' }}</span></template>
          <template v-else-if="column.dataIndex === 'business_manager_name'"><span>{{ record.business_manager_name || '-' }}</span></template>
        </template>
      </a-table>
    </a-card>
  </page-container>
</template>

<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue';
import dayjs from 'dayjs';
import { message } from 'ant-design-vue';
import { getBusinessManagerOperationLogsApi, getBusinessManagerOperationLogsStatsApi } from '@/api/businessManager';

const loading = ref(false);
const dataSource = ref<any[]>([]);
const stats = ref<any>({});
const pagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: true });
const filters = reactive({
  search: '',
  status: undefined as string | undefined,
});

const columns = computed(() => [
  { title: '日志ID', dataIndex: 'id', width: 90 },
  { title: 'BM ID', dataIndex: 'business_manager_id', width: 100 },
  { title: '业务管理器名称', dataIndex: 'business_manager_name', width: 180, ellipsis: true },
  { title: '操作类型', dataIndex: 'operation_type', width: 130 },
  { title: '结果', dataIndex: 'status', width: 100 },
  { title: '操作人', dataIndex: 'operator_name', width: 120 },
  { title: '消息', dataIndex: 'message', ellipsis: true },
  { title: '时间', dataIndex: 'created_at', width: 180 },
]);

const formatDateTime = (value?: string | null) => {
  if (!value) return '-';
  const d = dayjs(value);
  return d.isValid() ? d.format('YYYY-MM-DD HH:mm:ss') : '-';
};

const parseListResult = (res: any) => {
  const root = res?.data && !Array.isArray(res?.data) ? res.data : (res ?? {});
  const directList = Array.isArray(root?.data) ? root.data : null;
  const list = directList ?? root?.items ?? root?.list ?? [];
  const total = root?.total ?? root?.totalCount ?? root?.meta?.total ?? (Array.isArray(list) ? list.length : 0);
  return { list: Array.isArray(list) ? list : [], total: Number(total) || 0 };
};

const loadStats = async () => {
  try {
    const res = await getBusinessManagerOperationLogsStatsApi();
    const root = res?.data && !Array.isArray(res?.data) ? res.data : (res ?? {});
    stats.value = root?.data ?? root ?? {};
  } catch (_e) {}
};

const fetchList = async () => {
  loading.value = true;
  try {
    const res = await getBusinessManagerOperationLogsApi({
      page: pagination.current,
      pageSize: pagination.pageSize,
      search: filters.search || undefined,
      status: filters.status,
    });
    const { list, total } = parseListResult(res);
    dataSource.value = list;
    pagination.total = total;
  } catch (e: any) {
    message.error(e?.message || '加载日志失败');
  } finally {
    loading.value = false;
  }
};

const handleSearch = () => {
  pagination.current = 1;
  fetchList();
};

const handleReset = () => {
  filters.search = '';
  filters.status = undefined;
  pagination.current = 1;
  fetchList();
};

const handleRefresh = () => {
  loadStats();
  fetchList();
};

const handleTableChange = (pag: any) => {
  pagination.current = pag.current;
  pagination.pageSize = pag.pageSize;
  fetchList();
};

onMounted(() => {
  loadStats();
  fetchList();
});
</script>

<style scoped>
.page-desc { margin-bottom: 12px; color: #8a94a6; }
.stats-row { margin-bottom: 12px; }
.stats-item { display: flex; flex-direction: column; gap: 6px; }
.stats-label { color: #8a94a6; font-size: 13px; }
.stats-value { font-size: 24px; font-weight: 600; color: #1f2d3d; }
</style>
