<template>
  <page-container>
    <!-- 统计面板 -->
    <audit-statistics style="margin-bottom: 24px;" />

    <!-- 日志列表 -->
    <div class="ant-pro-table">
      <a-card :body-style="{ padding: 0 }" ref="elRef">
        <!-- 筛选表单 -->
        <div class="ant-pro-table-search">
          <a-form layout="horizontal" :label-col="{ style: { width: '80px' } }">
            <a-row :gutter="16">
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.audit.field.user_name')" style="margin-bottom: 16px;">
                  <a-select
                    v-model:value="filters.user_name"
                    :placeholder="t('pages.audit.filter.user_name_placeholder')"
                    allow-clear
                    show-search
                    :filter-option="filterUserOption"
                  >
                    <a-select-option v-for="user in userList" :key="user.id" :value="user.name">
                      {{ user.name }}
                    </a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.audit.field.ip_address')" style="margin-bottom: 16px;">
                  <a-input
                    v-model:value="filters.ip_address"
                    :placeholder="t('pages.audit.filter.ip_placeholder')"
                    allow-clear
                  />
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.audit.field.request_method')" style="margin-bottom: 16px;">
                  <a-select
                    v-model:value="filters.request_method"
                    :placeholder="t('pages.audit.filter.method_placeholder')"
                    allow-clear
                  >
                    <a-select-option value="GET">GET</a-select-option>
                    <a-select-option value="POST">POST</a-select-option>
                    <a-select-option value="PUT">PUT</a-select-option>
                    <a-select-option value="DELETE">DELETE</a-select-option>
                    <a-select-option value="PATCH">PATCH</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.audit.field.request_path')" style="margin-bottom: 16px;">
                  <a-input
                    v-model:value="filters.request_path"
                    :placeholder="t('pages.audit.filter.path_placeholder')"
                    allow-clear
                  />
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.audit.field.response_status')" style="margin-bottom: 16px;">
                  <a-select
                    v-model:value="filters.response_status"
                    :placeholder="t('pages.audit.filter.status_placeholder')"
                    allow-clear
                  >
                    <a-select-option :value="200">200 - OK</a-select-option>
                    <a-select-option :value="201">201 - Created</a-select-option>
                    <a-select-option :value="400">400 - Bad Request</a-select-option>
                    <a-select-option :value="401">401 - Unauthorized</a-select-option>
                    <a-select-option :value="403">403 - Forbidden</a-select-option>
                    <a-select-option :value="404">404 - Not Found</a-select-option>
                    <a-select-option :value="500">500 - Server Error</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="12" :lg="8" :xl="6">
                <a-form-item :label="t('pages.audit.filter.date_range')" style="margin-bottom: 16px;">
                  <a-range-picker
                    v-model:value="dateRange"
                    style="width: 100%;"
                    :placeholder="[t('pages.audit.filter.start_date'), t('pages.audit.filter.end_date')]"
                    :presets="rangePresets"
                  />
                </a-form-item>
              </a-col>
              <a-col :xs="24" :sm="24" :lg="24" :xl="12">
                <a-form-item :wrapper-col="{ offset: 0 }" style="margin-bottom: 16px;">
                  <a-space>
                    <a-button type="primary" @click="handleSearch">
                      <template #icon><search-outlined /></template>
                      {{ t('pages.search') }}
                    </a-button>
                    <a-button @click="handleReset">
                      <template #icon><clear-outlined /></template>
                      {{ t('pages.reset') }}
                    </a-button>
                  </a-space>
                </a-form-item>
              </a-col>
            </a-row>
          </a-form>
        </div>

        <!-- 工具栏 -->
        <div class="ant-pro-table-list-toolbar">
          <div class="ant-pro-table-list-toolbar-container">
            <div class="ant-pro-table-list-toolbar-left">
              <div class="ant-pro-table-list-toolbar-title">{{ t('pages.audit.title') }}</div>
            </div>
            <div class="ant-pro-table-list-toolbar-right">
              <a-space>
                <a-button danger @click="handleCleanup">
                  <template #icon><delete-outlined /></template>
                  {{ t('pages.audit.action.cleanup') }}
                </a-button>
              </a-space>
              <div class="ant-pro-table-list-toolbar-divider">
                <a-divider type="vertical" />
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip :title="t('pages.refresh')">
                  <reload-outlined @click="reload" />
                </a-tooltip>
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip :title="t('pages.density')">
                  <a-dropdown :trigger="['click']" placement="bottomRight">
                    <column-height-outlined />
                    <template #overlay>
                      <a-menu
                        style="width: 80px"
                        :selected-keys="[state.tableSize]"
                        @click="
                          ({ key }) => {
                            state.tableSize = key;
                          }
                        "
                      >
                        <a-menu-item key="large">{{ t('pages.large') }}</a-menu-item>
                        <a-menu-item key="middle">{{ t('pages.middle') }}</a-menu-item>
                        <a-menu-item key="small">{{ t('pages.small') }}</a-menu-item>
                      </a-menu>
                    </template>
                  </a-dropdown>
                </a-tooltip>
              </div>
              <div class="ant-pro-table-list-toolbar-setting-item">
                <a-tooltip :title="t('pages.fullscreen')">
                  <fullscreen-outlined v-if="!screenState" @click="setFull" />
                  <fullscreen-exit-outlined v-else @click="exitFull" />
                </a-tooltip>
              </div>
            </div>
          </div>
        </div>

        <!-- 数据表格 -->
        <a-table
          row-key="id"
          :size="state.tableSize"
          :loading="state.loading"
          :columns="columns"
          :data-source="state.dataSource"
          :pagination="{
            current: state.current,
            pageSize: state.pageSize,
            total: state.total,
            showSizeChanger: true,
            showQuickJumper: true,
            showTotal: (total: number) => t('pages.audit.pagination.total', { total }),
          }"
          @change="handleTableChange"
        >
          <template #bodyCell="{ text, record, column }">
            <template v-if="column.dataIndex === 'user_name'">
              {{ text || '-' }}
            </template>
            <template v-else-if="column.dataIndex === 'request_method'">
              <a-tag :color="getMethodColor(text)">{{ text }}</a-tag>
            </template>
            <template v-else-if="column.dataIndex === 'request_path'">
              <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ text }}
              </div>
            </template>
            <template v-else-if="column.dataIndex === 'response_status'">
              <a-tag :color="getStatusColor(text)">{{ text }}</a-tag>
            </template>
            <template v-else-if="column.dataIndex === 'response_time_formatted'">
              <span :style="{ color: getResponseTimeColor(record.response_time) }">
                {{ text }}
              </span>
            </template>
            <template v-else-if="column.dataIndex === 'action'">
              <a @click="() => handleViewDetail(record.id)">{{ t('pages.audit.action.view_detail') }}</a>
            </template>
          </template>
        </a-table>
      </a-card>
    </div>

    <!-- 详情Modal -->
    <audit-detail-modal
      :visible="detailModal.visible"
      :log-id="detailModal.logId"
      @cancel="() => { detailModal.visible = false; detailModal.logId = null; }"
    />

    <!-- 清理Modal -->
    <a-modal
      v-model:visible="cleanupModal.visible"
      :title="t('pages.audit.cleanup.title')"
      @ok="handleCleanupConfirm"
      :confirm-loading="cleanupModal.loading"
    >
      <a-form layout="vertical">
        <a-form-item :label="t('pages.audit.cleanup.days_label')">
          <a-input-number
            v-model:value="cleanupModal.days"
            :min="1"
            :max="365"
            :placeholder="t('pages.audit.cleanup.days_placeholder')"
            style="width: 100%;"
          />
          <div style="color: rgba(0, 0, 0, 0.45); font-size: 12px; margin-top: 8px;">
            {{ t('pages.audit.cleanup.hint') }}
          </div>
        </a-form-item>
      </a-form>
    </a-modal>
  </page-container>
</template>

<script lang="ts" setup>
import { reactive, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { message, Modal } from 'ant-design-vue';
import dayjs from 'dayjs';
import type { Dayjs } from 'dayjs';
import {
  ReloadOutlined,
  ColumnHeightOutlined,
  FullscreenOutlined,
  FullscreenExitOutlined,
  SearchOutlined,
  ClearOutlined,
  DeleteOutlined,
} from '@ant-design/icons-vue';
import type { Pagination, TableColumn } from '@/typing';
import { useFetchData } from '@/utils/hooks/useFetchData';
import { useFullscreen } from '@/utils/hooks/useFullscreen';
import { getRequestLogs, cleanupRequestLogs } from '@/api/request_logs';
import type { RequestLogsParams } from '@/api/request_logs';
import { getUsers } from '@/api/user/role_v2';
import AuditDetailModal from './audit-detail-modal.vue';
import AuditStatistics from './audit-statistics.vue';

const { t } = useI18n();

// 用户列表
const userList = ref<Array<{ id: string; name: string }>>([]);

// 筛选条件
const filters = reactive<RequestLogsParams>({
  user_name: undefined,
  ip_address: undefined,
  request_method: undefined,
  request_path: undefined,
  response_status: undefined,
});

const dateRange = ref<[Dayjs, Dayjs] | null>(null);

// 日期范围预设
const rangePresets = ref([
  { label: t('pages.audit.presets.today'), value: [dayjs(), dayjs()] },
  { label: t('pages.audit.presets.yesterday'), value: [dayjs().subtract(1, 'day'), dayjs().subtract(1, 'day')] },
  { label: t('pages.audit.presets.last_7_days'), value: [dayjs().subtract(6, 'days'), dayjs()] },
  { label: t('pages.audit.presets.last_30_days'), value: [dayjs().subtract(29, 'days'), dayjs()] },
  { label: t('pages.audit.presets.this_week'), value: [dayjs().startOf('week'), dayjs().endOf('week')] },
  { label: t('pages.audit.presets.last_week'), value: [dayjs().subtract(1, 'week').startOf('week'), dayjs().subtract(1, 'week').endOf('week')] },
  { label: t('pages.audit.presets.this_month'), value: [dayjs().startOf('month'), dayjs().endOf('month')] },
  { label: t('pages.audit.presets.last_month'), value: [dayjs().subtract(1, 'month').startOf('month'), dayjs().subtract(1, 'month').endOf('month')] },
]);

// 表格列定义
const columns: TableColumn[] = [
  {
    title: t('pages.audit.field.user_name'),
    dataIndex: 'user_name',
    width: 120,
  },
  {
    title: t('pages.audit.field.ip_address'),
    dataIndex: 'ip_address',
    width: 140,
  },
  {
    title: t('pages.audit.field.request_method'),
    dataIndex: 'request_method',
    width: 100,
  },
  {
    title: t('pages.audit.field.request_path'),
    dataIndex: 'request_path',
    ellipsis: true,
  },
  {
    title: t('pages.audit.field.response_status'),
    dataIndex: 'response_status',
    width: 120,
  },
  {
    title: t('pages.audit.field.response_time'),
    dataIndex: 'response_time_formatted',
    width: 120,
    sorter: true,
  },
  {
    title: t('pages.audit.field.requested_at'),
    dataIndex: 'requested_at_formatted',
    width: 180,
    sorter: true,
  },
  {
    title: t('pages.audit.field.action'),
    dataIndex: 'action',
    width: 100,
    fixed: 'right',
  },
];

// 额外的请求参数（排序、筛选等）
const requestParams = reactive<Omit<RequestLogsParams, 'pageNo' | 'pageSize'>>({
  sortField: 'requested_at',
  sortOrder: 'desc',
  ...filters,
});

// 数据获取上下文
const fetchDataContext = reactive({
  pageNo: 1,
  pageSize: 20,
  requestParams,
});

// 使用自定义hook获取数据
const { reload, context: state } = useFetchData(
  async (params: RequestLogsParams) => {
    const res = await getRequestLogs(params);
    // 将 UTC 时间转换为 UTC+8
    if (res.data && Array.isArray(res.data)) {
      res.data = res.data.map(item => {
        let formattedTime = '-';
        if (item.requested_at) {
          // 使用 dayjs.utc() 解析 UTC 时间，然后加8小时转为 UTC+8
          const utcTime = dayjs.utc(item.requested_at);
          formattedTime = utcTime.add(8, 'hour').format('YYYY-MM-DD HH:mm:ss');
        }
        return {
          ...item,
          requested_at_formatted: formattedTime,
        };
      });
    }
    return res;
  },
  fetchDataContext,
);
state.tableSize = 'middle';

// 全屏功能
// elRef 在模板中使用: <a-card ref="elRef">
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const [elRef, screenState, { setFull, exitFull }] = useFullscreen();

// 表格变化处理
const handleTableChange = ({ current, pageSize }: Pagination, _filters: any, sorter: any) => {
  state.current = current;
  state.pageSize = pageSize;

  if (sorter.field) {
    requestParams.sortField = sorter.field === 'response_time_formatted' ? 'response_time' :
                               sorter.field === 'requested_at_formatted' ? 'requested_at' : sorter.field;
    requestParams.sortOrder = sorter.order === 'ascend' ? 'asc' : 'desc';
  } else {
    // 如果没有排序，恢复默认排序
    requestParams.sortField = 'requested_at';
    requestParams.sortOrder = 'desc';
  }
};

// 搜索
const handleSearch = () => {
  Object.assign(requestParams, filters);

  if (dateRange.value) {
    requestParams.date_start = dateRange.value[0].format('YYYY-MM-DD');
    requestParams.date_end = dateRange.value[1].format('YYYY-MM-DD');
  } else {
    requestParams.date_start = undefined;
    requestParams.date_end = undefined;
  }

  state.current = 1;
};

// 重置
const handleReset = () => {
  filters.user_name = undefined;
  filters.ip_address = undefined;
  filters.request_method = undefined;
  filters.request_path = undefined;
  filters.response_status = undefined;
  dateRange.value = null;

  requestParams.date_start = undefined;
  requestParams.date_end = undefined;
  state.current = 1;

  handleSearch();
};

// 详情Modal
const detailModal = reactive({
  visible: false,
  logId: null as string | null,
});

const handleViewDetail = (id: string) => {
  detailModal.visible = true;
  detailModal.logId = id;
};

// 清理Modal
const cleanupModal = reactive({
  visible: false,
  loading: false,
  days: 30,
});

const handleCleanup = () => {
  cleanupModal.visible = true;
  cleanupModal.days = 30;
};

const handleCleanupConfirm = async () => {
  Modal.confirm({
    title: t('pages.audit.cleanup.confirm_title'),
    content: t('pages.audit.cleanup.confirm_content', { days: cleanupModal.days }),
    okText: t('pages.confirm'),
    cancelText: t('pages.cancel'),
    onOk: async () => {
      cleanupModal.loading = true;
      try {
        const res = await cleanupRequestLogs({ days: cleanupModal.days });
        message.success(t('pages.audit.cleanup.success', { count: res.data.deleted_count }));
        cleanupModal.visible = false;
        reload();
      } catch (error: any) {
        message.error(error.response?.data?.message || t('pages.audit.error.cleanup'));
      } finally {
        cleanupModal.loading = false;
      }
    },
  });
};

// 颜色辅助函数
const getMethodColor = (method: string) => {
  const colors: Record<string, string> = {
    GET: 'blue',
    POST: 'green',
    PUT: 'orange',
    DELETE: 'red',
    PATCH: 'purple',
  };
  return colors[method] || 'default';
};

const getStatusColor = (status: number) => {
  if (status >= 200 && status < 300) return 'success';
  if (status >= 300 && status < 400) return 'warning';
  if (status >= 400 && status < 500) return 'error';
  if (status >= 500) return 'error';
  return 'default';
};

const getResponseTimeColor = (time: number) => {
  if (time < 100) return '#52c41a';
  if (time < 500) return '#faad14';
  return '#f5222d';
};

// 用户下拉搜索过滤
const filterUserOption = (input: string, option: any) => {
  return option.value.toLowerCase().indexOf(input.toLowerCase()) >= 0;
};

// 获取用户列表
const fetchUserList = async () => {
  try {
    const res = await getUsers();
    userList.value = (res.data || []).map((user: any) => ({
      id: String(user.id),
      name: user.name,
    }));
  } catch (error: any) {
    console.error('Failed to fetch user list:', error);
  }
};

// 页面加载时获取用户列表
onMounted(() => {
  fetchUserList();
});
</script>

<style scoped>
.ant-pro-table-search {
  padding: 20px 24px;
  background-color: #fafafa;
  border-bottom: 1px solid #f0f0f0;
}

:deep(.ant-form-item-label) {
  padding-bottom: 4px;
}

:deep(.ant-form-item-label > label) {
  font-size: 14px;
  color: rgba(0, 0, 0, 0.85);
}

.ant-pro-table-list-toolbar {
  padding: 16px;
}

.ant-pro-table-list-toolbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ant-pro-table-list-toolbar-left {
  display: flex;
  align-items: center;
}

.ant-pro-table-list-toolbar-title {
  font-size: 16px;
  font-weight: 600;
}

.ant-pro-table-list-toolbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ant-pro-table-list-toolbar-divider {
  height: 24px;
}

.ant-pro-table-list-toolbar-setting-item {
  font-size: 16px;
  cursor: pointer;
  color: rgba(0, 0, 0, 0.45);
  transition: color 0.3s;
}

.ant-pro-table-list-toolbar-setting-item:hover {
  color: #1890ff;
}
</style>

