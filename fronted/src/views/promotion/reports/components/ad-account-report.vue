<template>
  <div class="ad-account-report">
    <div class="filter-bar">
      <a-button :icon="h(FilterOutlined)">{{ t('筛选') }}</a-button>
      <a-select
        v-model:value="filters.account"
        :placeholder="t('广告账户: 请选择')"
        style="width: 150px"
        allow-clear
        :options="accountOptions"
      />
      <a-select
        v-model:value="filters.product"
        :placeholder="t('产品: 请选择')"
        style="width: 150px"
        allow-clear
        :options="productOptions"
      />
      <a-select
        v-model:value="filters.optimizer"
        :placeholder="t('优化师: 请选择')"
        style="width: 150px"
        allow-clear
        :options="optimizerOptions"
      />
      <a-select
        v-model:value="subdivisionData"
        :placeholder="t('细分数据: 请选择')"
        style="width: 150px"
        allow-clear
        :options="[]"
      >
        <template #suffixIcon>
          <a-tooltip>
            <template #title>{{ t('细分数据说明') }}</template>
            <question-circle-outlined style="color: #1890ff; cursor: help" />
          </a-tooltip>
        </template>
      </a-select>
    </div>
    <div class="action-bar">
      <a-checkbox v-model:checked="batchOperationChecked">{{ t('批量操作') }}</a-checkbox>
      <a-dropdown v-if="batchOperationChecked">
        <template #overlay>
          <a-menu>
            <a-menu-item>{{ t('Excel编辑') }}</a-menu-item>
            <a-menu-item>{{ t('开启') }}</a-menu-item>
            <a-menu-item>{{ t('暂停') }}</a-menu-item>
            <a-menu-item>{{ t('复制广告账户') }}</a-menu-item>
          </a-menu>
        </template>
        <a-button>{{ t('批量操作') }}</a-button>
      </a-dropdown>
      <a-button>{{ t('分组分析') }}</a-button>
      <div style="margin-left: auto; display: flex; gap: 8px; align-items: center">
        <a-button :icon="h(ReloadOutlined)" @click="reloadData">{{ t('刷新') }}</a-button>
        <a-button :icon="h(SyncOutlined)">{{ t('同步数据') }}</a-button>
        <a-button :icon="h(DownloadOutlined)">{{ t('导出') }}</a-button>
        <a-button :icon="h(SettingOutlined)">{{ t('自定义列') }}</a-button>
      </div>
    </div>
    <a-table
      :loading="loading"
      :columns="columns"
      :data-source="tableData"
      :pagination="pagination"
      :row-key="record => record.id"
      :row-selection="rowSelection"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'operation'">
          <a-space>
            <a-button type="link" size="small" @click="handleSync(record)">
              {{ t('同步') }}
            </a-button>
            <a-button type="link" size="small" @click="handleHourlyReport(record)">
              {{ t('小时报表') }}
            </a-button>
          </a-space>
        </template>
        <template v-else-if="column.dataIndex === 'authorizationStatus'">
          <a-space>
            <a-badge :status="record.authorizationStatus === '已授权' ? 'success' : 'default'" />
            <span>{{ record.authorizationStatus }}</span>
          </a-space>
        </template>
        <template v-else-if="column.dataIndex === 'spend'">
          {{ formatCurrency(record.spend, record.currency) }}
        </template>
        <template v-else-if="column.dataIndex === 'cpm'">
          {{ record.cpm ? formatCurrency(record.cpm, record.currency) : '-' }}
        </template>
        <template v-else-if="column.dataIndex === 'cpc'">
          {{ record.cpc ? formatCurrency(record.cpc, record.currency) : '-' }}
        </template>
      </template>
    </a-table>
  </div>
</template>

<script lang="ts" setup>
import { ref, h, computed, watch, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  FilterOutlined,
  ReloadOutlined,
  SyncOutlined,
  DownloadOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import {
  getAdAccountReportData,
  getAccounts,
  getOptimizers,
  getProducts,
} from '../mock-data';

interface Props {
  platform?: string;
}

const props = withDefaults(defineProps<Props>(), {
  platform: 'meta',
});

const { t } = useI18n();

const filters = ref({
  account: undefined,
  product: undefined,
  optimizer: undefined,
});

const accounts = ref<any[]>([]);
const products = ref<any[]>([]);
const optimizers = ref<any[]>([]);
const subdivisionData = ref(undefined);

const accountOptions = computed(() => {
  if (!Array.isArray(accounts.value)) {
    return [];
  }
  return accounts.value
    .filter((acc) => acc && acc.id && acc.name)
    .map((acc) => ({ label: acc.name, value: acc.id }));
});

const productOptions = computed(() => {
  if (!Array.isArray(products.value)) {
    return [];
  }
  return products.value
    .filter((prod) => prod && prod.id && prod.name)
    .map((prod) => ({ label: prod.name, value: prod.id }));
});

const optimizerOptions = computed(() => {
  if (!Array.isArray(optimizers.value)) {
    return [];
  }
  return optimizers.value
    .filter((opt) => opt && opt.id && opt.name)
    .map((opt) => ({ label: opt.name, value: opt.id }));
});
const batchOperationChecked = ref(false);
const selectedRowKeys = ref<string[]>([]);

const loading = ref(false);
const tableData = ref<any[]>([]);
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0,
  showTotal: (total: number) => t('共') + total + t('条'),
});

const columns = ref([
  { title: t('广告账户名称'), dataIndex: 'accountName', key: 'accountName', sorter: true },
  { title: t('广告账户ID'), dataIndex: 'accountId', key: 'accountId', sorter: true },
  { title: t('操作'), dataIndex: 'operation', key: 'operation' },
  { title: t('授权状态'), dataIndex: 'authorizationStatus', key: 'authorizationStatus', sorter: true },
  { title: t('优化师'), dataIndex: 'optimizer', key: 'optimizer', sorter: true },
  { title: t('上次更新时间'), dataIndex: 'lastUpdateTime', key: 'lastUpdateTime', sorter: true },
  { title: t('花费'), dataIndex: 'spend', key: 'spend', sorter: true },
  { title: t('展示数'), dataIndex: 'impressions', key: 'impressions', sorter: true },
  { title: t('千次展示成本'), dataIndex: 'cpm', key: 'cpm', sorter: true },
  { title: t('点击成本'), dataIndex: 'cpc', key: 'cpc', sorter: true },
  { title: t('点击率'), dataIndex: 'ctr', key: 'ctr', sorter: true },
]);

const rowSelection = computed(() => ({
  selectedRowKeys: selectedRowKeys.value,
  onChange: (keys: string[]) => {
    selectedRowKeys.value = keys;
  },
}));

const formatCurrency = (value: number, currency: string = 'USD') => {
  if (!value && value !== 0) return '-';
  return new Intl.NumberFormat('zh-CN', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 3,
    maximumFractionDigits: 3,
  }).format(value);
};

const handleSync = (record: any) => {
  message.info(t('同步') + ': ' + record.accountName);
};

const handleHourlyReport = (record: any) => {
  message.info(t('小时报表') + ': ' + record.accountName);
};

let isMounted = true;
let abortController: AbortController | null = null;

const loadData = async () => {
  // 取消之前的请求
  if (abortController) {
    abortController.abort();
  }
  abortController = new AbortController();
  
  loading.value = true;
  try {
    const result = await getAdAccountReportData({
      page: pagination.value.current,
      pageSize: pagination.value.pageSize,
      platform: props.platform,
      ...filters.value,
    });
    // 检查是否已卸载或请求已取消
    if (isMounted && !abortController.signal.aborted) {
      tableData.value = result.data;
      pagination.value.total = result.total;
    }
  } catch (error: any) {
    // 忽略取消请求的错误
    if (error?.name !== 'AbortError') {
      console.error('加载数据失败:', error);
    }
  } finally {
    if (isMounted && !abortController?.signal.aborted) {
      loading.value = false;
    }
  }
};

const handleTableChange = (pag: any) => {
  pagination.value.current = pag.current;
  pagination.value.pageSize = pag.pageSize;
  loadData();
};

const reloadData = () => {
  loadData();
};

const loadFilterOptions = async () => {
  try {
    const [accountsData, productsData, optimizersData] = await Promise.all([
      getAccounts(),
      getProducts(),
      getOptimizers(),
    ]);
    
    if (isMounted) {
      accounts.value = Array.isArray(accountsData) ? accountsData : [];
      products.value = Array.isArray(productsData) ? productsData : [];
      optimizers.value = Array.isArray(optimizersData) ? optimizersData : [];
    }
  } catch (error) {
    console.error('加载筛选选项失败:', error);
    if (isMounted) {
      accounts.value = [];
      products.value = [];
      optimizers.value = [];
    }
  }
};

// 监听平台变化
const stopWatch = watch(
  () => props.platform,
  () => {
    loadData();
  },
);

onUnmounted(() => {
  isMounted = false;
  if (abortController) {
    abortController.abort();
  }
  stopWatch();
});

loadData();
loadFilterOptions();
</script>

<style lang="less" scoped>
.ad-account-report {
  .filter-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    align-items: center;
  }

  .action-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    align-items: center;
  }
}
</style>

