<template>
  <page-container>
    <a-card :bordered="false" title="广告账户">
      <template #extra>总数：{{ total }}</template>
      <a-space style="margin-bottom: 12px" wrap>
        <a-input v-model:value="filters.keyword" placeholder="账户名/账户ID" style="width: 220px" />
        <a-select v-model:value="filters.account_status" placeholder="状态" allow-clear style="width: 140px">
          <a-select-option v-for="opt in accountStatusOptions" :key="opt.value" :value="String(opt.value)">
            {{ opt.label }}
          </a-select-option>
        </a-select>
        <a-select v-model:value="filters.currency" placeholder="币种" allow-clear style="width: 120px">
          <a-select-option v-for="cur in filterOptions.currencies" :key="cur" :value="cur">
            {{ cur }}
          </a-select-option>
        </a-select>
        <a-select v-model:value="filters.bm_id" placeholder="BM" allow-clear style="width: 180px">
          <a-select-option v-for="bm in filterOptions.bms" :key="bm.id" :value="String(bm.id)">
            {{ bm.name }}
          </a-select-option>
        </a-select>
        <a-button type="primary" @click="handleSearch">查询</a-button>
        <a-button @click="handleReset" class="font-ali">重置</a-button>
        <a-button @click="showImport = true">导入</a-button>
        <a-button @click="handleExport">导出</a-button>
        <a-button @click="showColumnDrawer = true">列设置</a-button>
      </a-space>

      <a-table
        :columns="visibleColumns"
        :data-source="list"
        :pagination="pagination"
        :loading="loading"
        row-key="id"
        @change="handleTableChange"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'account_status'">
            {{ formatAccountStatus(record?.account_status) }}
          </template>
          <template v-if="column.key === 'actions'">
            <a-space>
              <a-button type="primary" size="small" @click="openRename(record)">改名</a-button>
              <a-button type="primary" size="small" @click="openPixels(record)">像素</a-button>
              <a-button type="primary" size="small" @click="handleSync(record)">同步</a-button>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>

    <a-drawer v-model:open="showColumnDrawer" title="列设置" width="320">
      <a-checkbox-group v-model:value="enabledColumnKeys" style="display: grid; gap: 8px">
        <a-checkbox v-for="col in allColumns.filter(c => c.key !== 'actions')" :key="col.key" :value="col.key">
          {{ col.title }}
        </a-checkbox>
      </a-checkbox-group>
    </a-drawer>

    <ImportModal v-model:open="showImport" :loading="actionLoading" @submit="handleImport" />
    <RenameModal
      v-model:open="showRename"
      :loading="actionLoading"
      :current-name="currentRecord?.name"
      @submit="handleRename"
    />
    <PixelModal
      v-model:open="showPixels"
      :loading="actionLoading"
      :pixels="pixels"
      @attach="handlePixelAttach"
      @detach="handlePixelDetach"
    />
  </page-container>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { message } from 'ant-design-vue';
import type { TableColumnType } from 'ant-design-vue';
import { useRoute, useRouter } from 'vue-router';
import ImportModal from './components/ImportModal.vue';
import RenameModal from './components/RenameModal.vue';
import PixelModal from './components/PixelModal.vue';
import {
  adAccountPixelOperationApi,
  exportAdAccountsApi,
  getAdAccountFilterOptionsApi,
  getAdAccountPixelsApi,
  getAdAccountsApi,
  getExportTaskDownloadApi,
  getExportTaskStatusApi,
  importAdAccountsApi,
  renameAdAccountApi,
  syncBusinessManagerApi,
} from '@/api/adaccountMigration';

const COLUMN_KEY = 'adAccountsColumnSettings';
const route = useRoute();
const router = useRouter();
const loading = ref(false);
const actionLoading = ref(false);
const list = ref<any[]>([]);
const total = ref(0);
const pixels = ref<any[]>([]);
const currentRecord = ref<any>(null);
const showImport = ref(false);
const showRename = ref(false);
const showPixels = ref(false);
const showColumnDrawer = ref(false);
const enabledColumnKeys = ref<string[]>([]);
const filters = reactive({
  keyword: '',
  account_status: undefined as string | undefined,
  currency: undefined as string | undefined,
  bm_id: undefined as string | undefined,
});
const pagination = reactive({ current: 1, pageSize: 20, total: 0 });
const sorter = reactive({ sortField: 'created_at', sortOrder: 'descend' as 'ascend' | 'descend' });
const filterOptions = reactive<{ statuses: string[]; currencies: string[]; bms: any[] }>({ statuses: [], currencies: [], bms: [] });

const accountStatusOptions = [
  { value: 1, label: '已激活' },
  { value: 2, label: '已暂停' },
  { value: 3, label: '已禁用' },
];

const formatAccountStatus = (value: any) => {
  const v = Number(value);
  const hit = accountStatusOptions.find(x => x.value === v);
  return hit ? hit.label : String(value ?? '');
};

const allColumns: TableColumnType<any>[] = [
  { title: '账户ID', dataIndex: 'source_id', key: 'source_id', width: 160 },
  { title: '账户名称', dataIndex: 'name', key: 'name', width: 220, ellipsis: true },
  { title: '状态', dataIndex: 'account_status', key: 'account_status', width: 120 },
  { title: 'BM', dataIndex: 'bm_name', key: 'bm_name', width: 180, ellipsis: true },
  { title: '币种', dataIndex: 'currency', key: 'currency', width: 100 },
  { title: '时区', dataIndex: 'timezone_name', key: 'timezone_name', width: 140 },
  { title: '创建时间', dataIndex: 'created_at', key: 'created_at', width: 180 },
  { title: '操作', key: 'actions', fixed: 'right' as const, width: 220 },
];

const visibleColumns = computed(() =>
  allColumns.filter(c => c.key === 'actions' || enabledColumnKeys.value.includes(String(c.key))),
);

const syncRouteQuery = () => {
  router.replace({
    query: {
      ...route.query,
      page: String(pagination.current),
      pageSize: String(pagination.pageSize),
      keyword: filters.keyword || undefined,
      account_status: filters.account_status || undefined,
      currency: filters.currency || undefined,
      bm_id: filters.bm_id || undefined,
      sortField: sorter.sortField,
      sortOrder: sorter.sortOrder,
    },
  });
};

const fetchList = async () => {
  loading.value = true;
  try {
    const resp: any = await getAdAccountsApi({
      page: pagination.current,
      pageSize: pagination.pageSize,
      ...filters,
      sortField: sorter.sortField,
      sortOrder: sorter.sortOrder,
    });

    // 严格按当前接口返回：{ success, data: [], page, pageSize, total }
    list.value = Array.isArray(resp?.data) ? resp.data : [];
    total.value = Number(resp?.total || 0);
    pagination.total = total.value;
    if (resp?.page) pagination.current = Number(resp.page);
    if (resp?.pageSize) pagination.pageSize = Number(resp.pageSize);
  } finally {
    loading.value = false;
  }
};

const handleSearch = async () => {
  pagination.current = 1;
  syncRouteQuery();
  await fetchList();
};
const handleReset = async () => {
  filters.keyword = '';
  filters.account_status = undefined;
  filters.currency = undefined;
  filters.bm_id = undefined;
  await handleSearch();
};

const handleTableChange = async (pager: any, _f: any, s: any) => {
  pagination.current = pager.current || 1;
  pagination.pageSize = pager.pageSize || 20;
  if (s?.field) sorter.sortField = s.field;
  if (s?.order) sorter.sortOrder = s.order;
  syncRouteQuery();
  await fetchList();
};

const handleImport = async (file: File) => {
  actionLoading.value = true;
  try {
    await importAdAccountsApi(file);
    message.success('导入任务已提交');
    showImport.value = false;
    await fetchList();
  } finally {
    actionLoading.value = false;
  }
};

const downloadBlob = (blob: Blob, filename: string) => {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
};

const handleExport = async () => {
  actionLoading.value = true;
  try {
    const created = await exportAdAccountsApi({ ...filters, sortField: sorter.sortField, sortOrder: sorter.sortOrder });
    const taskId = created?.data?.task_id || created?.task_id;
    if (!taskId) throw new Error('任务创建失败');
    let done = false;
    for (let i = 0; i < 20; i += 1) {
      // eslint-disable-next-line no-await-in-loop
      await new Promise(resolve => setTimeout(resolve, 1000));
      // eslint-disable-next-line no-await-in-loop
      const statusResp = await getExportTaskStatusApi(taskId);
      const status = statusResp?.data?.status || statusResp?.status;
      if (status === 'completed') {
        done = true;
        break;
      }
      if (status === 'failed') throw new Error(statusResp?.data?.message || '导出失败');
    }
    if (!done) throw new Error('导出超时');
    const fileResp = await getExportTaskDownloadApi(taskId);
    downloadBlob(fileResp, `ad-accounts-${Date.now()}.csv`);
    message.success('导出成功');
  } catch (e: any) {
    message.error(e?.message || '导出失败');
  } finally {
    actionLoading.value = false;
  }
};

const openRename = (record: any) => {
  currentRecord.value = record;
  showRename.value = true;
};
const handleRename = async (name: string) => {
  if (!name || !currentRecord.value) return;
  actionLoading.value = true;
  try {
    await renameAdAccountApi(currentRecord.value.id, name);
    showRename.value = false;
    message.success('修改成功');
    await fetchList();
  } finally {
    actionLoading.value = false;
  }
};

const openPixels = async (record: any) => {
  currentRecord.value = record;
  showPixels.value = true;
  actionLoading.value = true;
  try {
    const resp = await getAdAccountPixelsApi(record.id);
    pixels.value = resp?.data?.data || resp?.data || [];
  } finally {
    actionLoading.value = false;
  }
};
const handlePixelAttach = async (name: string) => {
  if (!currentRecord.value) return;
  actionLoading.value = true;
  try {
    await adAccountPixelOperationApi(currentRecord.value.id, 'attach', { name });
    await openPixels(currentRecord.value);
  } finally {
    actionLoading.value = false;
  }
};
const handlePixelDetach = async (pixel: any) => {
  if (!currentRecord.value) return;
  actionLoading.value = true;
  try {
    await adAccountPixelOperationApi(currentRecord.value.id, 'detach', { pixel_id: pixel.id });
    await openPixels(currentRecord.value);
  } finally {
    actionLoading.value = false;
  }
};

const handleSync = async (record: any) => {
  actionLoading.value = true;
  try {
    if (!record?.bm_id) throw new Error('该账户未关联BM');
    await syncBusinessManagerApi(record.bm_id);
    message.success('同步任务已提交');
  } catch (e: any) {
    message.error(e?.message || '同步失败');
  } finally {
    actionLoading.value = false;
  }
};

watch(
  enabledColumnKeys,
  value => localStorage.setItem(COLUMN_KEY, JSON.stringify(value)),
  { deep: true },
);

onMounted(async () => {
  const savedColumns = localStorage.getItem(COLUMN_KEY);
  enabledColumnKeys.value = savedColumns
    ? JSON.parse(savedColumns)
    : allColumns.filter(c => c.key !== 'actions').map(c => String(c.key));

  pagination.current = Number(route.query.page || 1);
  pagination.pageSize = Number(route.query.pageSize || 20);
  filters.keyword = String(route.query.keyword || '');
  filters.account_status = route.query.account_status as string | undefined;
  filters.currency = route.query.currency as string | undefined;
  filters.bm_id = route.query.bm_id as string | undefined;
  sorter.sortField = String(route.query.sortField || 'created_at');
  sorter.sortOrder = (route.query.sortOrder as any) || 'descend';

  const options = await getAdAccountFilterOptionsApi();
  filterOptions.statuses = options?.data?.statuses || [];
  filterOptions.currencies = options?.data?.currencies || [];
  filterOptions.bms = options?.data?.bms || [];
  await fetchList();
});
</script>
