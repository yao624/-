<template>
  <page-container title="业务管理账号">
    <a-card :bordered="false" class="bm-toolbar">
      <a-space wrap class="toolbar-space">
        <a-button type="primary" @click="openCreateModal">添加BM-ID</a-button>
        <a-input v-model:value="filters.search" allow-clear placeholder="关键词（名称/业务ID）"
          class="toolbar-input toolbar-input-lg" @pressEnter="handleSearch" />
        <a-input v-model:value="filters.business_id" allow-clear placeholder="业务ID"
          class="toolbar-input toolbar-input-md" @pressEnter="handleSearch" />
        <a-select v-model:value="filters.status" allow-clear placeholder="状态" class="toolbar-select">
          <a-select-option value="active">活跃</a-select-option>
          <a-select-option value="inactive">非活跃</a-select-option>
          <a-select-option value="error">异常</a-select-option>
        </a-select>
        <a-button @click="handleSearch">查询</a-button>
        <a-button @click="handleReset">重置</a-button>
        <a-button @click="goToSyncLogs">同步日志</a-button>
      </a-space>
    </a-card>

    <a-card :bordered="false" class="bm-table-card">
      <template #title>
        <div class="table-title">
          <span>业务管理账号列表</span>
          <span class="table-total">(共 {{ pagination.total }} 个)</span>
        </div>
      </template>
      <template #extra>
        <a-button @click="fetchList">刷新</a-button>
      </template>
      <a-table class="bm-table" :loading="loading" :columns="columns" :data-source="dataSource" :pagination="pagination"
        :row-key="record => record.id" :row-class-name="getRowClassName" size="middle" @change="handleTableChange">
        <template #bodyCell="{ column, record }">
          <template v-if="column.dataIndex === 'status'">
            <a-tag :color="record.status === 'active' ? 'green' : record.status === 'error' ? 'red' : 'default'">
              {{
                record.status === 'active'
                  ? '活跃'
                  : record.status === 'inactive'
                    ? '非活跃'
                    : record.status === 'error'
                      ? '异常'
                      : '-'
              }}
            </a-tag>
          </template>
          <template v-else-if="column.dataIndex === 'access_token'">
            <a-input :value="record.access_token || ''" size="small" readonly class="token-input" />
          </template>
          <template v-else-if="column.dataIndex === 'proxy_ip'">
            <div class="proxy-cell">
              <a-tag :color="record.use_proxy ? 'blue' : 'default'">{{ record.use_proxy ? '启用' : '停用' }}</a-tag>
              <div class="proxy-main">{{ record.proxy_ip || '-' }}{{ record.proxy_port ? `:${record.proxy_port}` : '' }}
              </div>
              <div v-if="record.proxy_username" class="proxy-sub">用户: {{ record.proxy_username }}</div>
            </div>
          </template>
          <template v-else-if="column.dataIndex === 'last_sync_at' || column.dataIndex === 'created_at'">
            {{ formatDateTime(record[column.dataIndex]) }}
          </template>
          <template v-else-if="column.dataIndex === 'closecard'">
            <a-switch :checked="record.closecard === 1" checked-children="开" un-checked-children="关"
              @change="(checked: boolean) => handleLockCardChange(record, checked)" />
          </template>
          <template v-else-if="column.dataIndex === 'operation'">
            <a-space size="small" wrap>
              <a-button type="primary" size="small" @click="handleTestToken(record)">测试令牌</a-button>
              <a-button type="primary" size="small" @click="handleCheckStatus(record)">检测状态</a-button>
              <a-button type="primary" size="small" @click="openAssignModal(record)">测试分配</a-button>
              <a-button  type="primary"  size="small" @click="openEditModal(record)">编辑</a-button>
              <a-popconfirm title="确认删除该BM？" @confirm="handleDelete(record)">
                <a-button type="primary" danger size="small">删除</a-button>
              </a-popconfirm>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>

    <a-modal v-model:open="editModalOpen" :title="editingId ? '编辑BM' : '新增BM'" @ok="handleSave">
      <a-form layout="vertical">
        <a-form-item label="名称" required><a-input v-model:value="formState.name" /></a-form-item>
        <a-form-item label="业务ID" required><a-input v-model:value="formState.business_id" /></a-form-item>
        <a-form-item label="访问令牌"><a-input v-model:value="formState.access_token" /></a-form-item>
        <a-form-item label="状态">
          <a-select v-model:value="formState.status">
            <a-select-option value="active">active</a-select-option>
            <a-select-option value="inactive">inactive</a-select-option>
            <a-select-option value="error">error</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="使用代理">
          <a-switch v-model:checked="formState.use_proxy" />
        </a-form-item>
        <template v-if="formState.use_proxy">
          <a-form-item label="代理IP"><a-input v-model:value="formState.proxy_ip" /></a-form-item>
          <a-form-item label="代理端口"><a-input v-model:value="formState.proxy_port" /></a-form-item>
          <a-form-item label="代理用户名"><a-input v-model:value="formState.proxy_username" /></a-form-item>
          <a-form-item label="代理密码"><a-input v-model:value="formState.proxy_password" /></a-form-item>
        </template>
      </a-form>
    </a-modal>

    <a-modal v-model:open="assignModalOpen" title="测试分配" @ok="handleAssign">
      <a-form layout="vertical">
        <a-form-item label="邮箱（每行一个）">
          <a-textarea v-model:value="assignEmailsText" :rows="6" placeholder="a@test.com&#10;b@test.com" />
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>

<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';
import {
  checkBusinessManagerStatusApi,
  createBusinessManagerApi,
  deleteBusinessManagerApi,
  getBusinessManagerListApi,
  testBusinessManagerAssignApi,
  testBusinessManagerTokenApi,
  updateBusinessManagerApi,
  updateBusinessManagerLockCardSettingApi,
} from '@/api/businessManager';

const loading = ref(false);
const router = useRouter();
const dataSource = ref<any[]>([]);
const pagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: true });
const filters = reactive({ search: '', business_id: '', status: undefined as string | undefined });

const defaultFormState = () => ({
  name: '',
  business_id: '',
  access_token: '',
  status: 'active' as 'active' | 'inactive' | 'error',
  use_proxy: false,
  proxy_ip: '',
  proxy_port: '',
  proxy_username: '',
  proxy_password: '',
});
const formState = reactive(defaultFormState());
const editModalOpen = ref(false);
const editingId = ref<string | number | null>(null);

const assignModalOpen = ref(false);
const assignTargetId = ref<string | number | null>(null);
const assignEmailsText = ref('');

const columns = computed(() => [
  { title: 'ID', dataIndex: 'id', width: 72 },
  { title: '名称', dataIndex: 'name', width: 170, ellipsis: true },
  { title: '业务ID', dataIndex: 'business_id', width: 160, ellipsis: true },
  { title: '访问令牌', dataIndex: 'access_token', width: 260, ellipsis: true },
  { title: '代理IP', dataIndex: 'proxy_ip', width: 210 },
  { title: '状态', dataIndex: 'status', width: 92 },
  { title: '广告账户数', dataIndex: 'ad_accounts_count', width: 110 },
  { title: '最后同步', dataIndex: 'last_sync_at', width: 180 },
  { title: '创建时间', dataIndex: 'created_at', width: 180 },
  { title: '锁卡', dataIndex: 'closecard', width: 84 },
  { title: '操作', dataIndex: 'operation', width: 300, fixed: 'right' as const },
]);

const formatDateTime = (value?: string | null) => {
  if (!value) return '-';
  const d = dayjs(value);
  return d.isValid() ? d.format('YYYY-MM-DD HH:mm:ss') : '-';
};

const parseListResult = (res: any) => {
  // 兼容两种返回形态：
  // 1) axios 原始响应：{ data: { success, data: [], total } }
  // 2) request 已解包：{ success, data: [], total }
  const root = res?.data && !Array.isArray(res?.data) ? res.data : (res ?? {});
  const directList = Array.isArray(root?.data) ? root.data : null;
  const list = directList ?? root?.items ?? root?.list ?? [];
  const total = root?.total ?? root?.totalCount ?? root?.meta?.total ?? (Array.isArray(list) ? list.length : 0);
  return { list: Array.isArray(list) ? list : [], total: Number(total) || 0 };
};

const fetchList = async () => {
  loading.value = true;
  try {
    const res = await getBusinessManagerListApi({
      page: pagination.current,
      pageSize: pagination.pageSize,
      search: filters.search || undefined,
      business_id: filters.business_id || undefined,
      status: filters.status,
    });
    const { list, total } = parseListResult(res);
    dataSource.value = list;
    pagination.total = total;
  } catch (e: any) {
    message.error(e?.message || '加载失败');
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
  filters.business_id = '';
  filters.status = undefined;
  pagination.current = 1;
  fetchList();
};

const goToSyncLogs = () => {
  router.push('/bm-management/sync-logs');
};

const handleTableChange = (pag: any) => {
  pagination.current = pag.current;
  pagination.pageSize = pag.pageSize;
  fetchList();
};

const getRowClassName = (_record: any, index: number) => (index % 2 === 1 ? 'row-alt' : '');

const openCreateModal = () => {
  Object.assign(formState, defaultFormState());
  editingId.value = null;
  editModalOpen.value = true;
};

const openEditModal = (record: any) => {
  Object.assign(formState, defaultFormState(), record);
  editingId.value = record.id;
  editModalOpen.value = true;
};

const handleSave = async () => {
  if (!formState.name || !formState.business_id) {
    message.warning('请填写名称和业务ID');
    return;
  }
  const payload = { ...formState };
  try {
    if (editingId.value) {
      await updateBusinessManagerApi(editingId.value, payload);
      message.success('更新成功');
    } else {
      await createBusinessManagerApi(payload);
      message.success('创建成功');
    }
    editModalOpen.value = false;
    fetchList();
  } catch (e: any) {
    message.error(e?.message || '保存失败');
  }
};

const handleDelete = async (record: any) => {
  try {
    await deleteBusinessManagerApi(record.id);
    message.success('删除成功');
    fetchList();
  } catch (e: any) {
    message.error(e?.message || '删除失败');
  }
};

const handleTestToken = async (record: any) => {
  try {
    await testBusinessManagerTokenApi(record.id);
    message.success('测试令牌完成');
  } catch (e: any) {
    message.error(e?.message || '测试令牌失败');
  }
};

const handleCheckStatus = async (record: any) => {
  try {
    await checkBusinessManagerStatusApi(record.id);
    message.success('检测状态完成');
    fetchList();
  } catch (e: any) {
    message.error(e?.message || '检测状态失败');
  }
};

const openAssignModal = (record: any) => {
  assignTargetId.value = record.id;
  assignEmailsText.value = '';
  assignModalOpen.value = true;
};

const handleAssign = async () => {
  if (!assignTargetId.value) return;
  const emails = assignEmailsText.value
    .split('\n')
    .map(v => v.trim())
    .filter(Boolean);
  if (!emails.length) {
    message.warning('请至少输入一个邮箱');
    return;
  }
  try {
    await testBusinessManagerAssignApi(assignTargetId.value, emails);
    message.success('测试分配请求已完成');
    assignModalOpen.value = false;
  } catch (e: any) {
    message.error(e?.message || '测试分配失败');
  }
};

const handleLockCardChange = async (record: any, checked: boolean) => {
  try {
    await updateBusinessManagerLockCardSettingApi(record.id, checked ? 1 : 0);
    message.success('锁卡设置已更新');
    record.closecard = checked ? 1 : 0;
  } catch (e: any) {
    message.error(e?.message || '锁卡设置更新失败');
  }
};

onMounted(() => {
  fetchList();
});
</script>

<style scoped>
.bm-toolbar {
  margin-bottom: 12px;
  border-radius: 10px;
}

.toolbar-space {
  width: 100%;
}

.toolbar-input-lg {
  width: 260px;
}

.toolbar-input-md {
  width: 180px;
}

.toolbar-select {
  width: 140px;
}

.bm-table-card {
  margin-top: 12px;
  border-radius: 10px;
}

.table-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.table-total {
  font-size: 16px;
  font-weight: 600;
}

.token-input :deep(.ant-input) {
  border-radius: 8px;
  font-family: 'Consolas', 'Courier New', monospace;
  font-size: 12px;
  color: #5b657a;
}

.proxy-cell {
  line-height: 1.25;
}

.proxy-main {
  color: #2a3347;
  margin-top: 4px;
}

.proxy-sub {
  margin-top: 4px;
  color: #8a94a6;
  font-size: 12px;
}

.bm-table :deep(.ant-table-thead > tr > th) {
  background: #fafbfc;
  color: #445066;
  font-weight: 600;
}

.bm-table :deep(.ant-table-tbody > tr.row-alt > td) {
  background: #fcfdff;
}

.bm-table :deep(.ant-table-tbody > tr:hover > td) {
  background: #f2f7ff !important;
}

.bm-table :deep(.ant-table-cell) {
  padding-top: 12px;
  padding-bottom: 12px;
}
</style>
