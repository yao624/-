<template>
  <div class="ad-group-report">
    <div class="filter-bar">
      <a-button :icon="h(FilterOutlined)">{{ t('筛选') }}</a-button>
      <a-select
        v-model:value="filters.adGroup"
        :placeholder="t('广告组: 请选择')"
        style="width: 150px"
        allow-clear
      />
      <a-select
        v-model:value="filters.campaign"
        :placeholder="t('广告系列: 请选择')"
        style="width: 150px"
        allow-clear
      />
      <a-select
        v-model:value="filters.status"
        :placeholder="t('状态: 请选择')"
        style="width: 150px"
        allow-clear
      />
      <a-select
        v-model:value="subdivisionData"
        :placeholder="t('细分数据: 请选择')"
        style="width: 150px"
        allow-clear
      />
    </div>
    <div class="action-bar">
      <a-checkbox v-model:checked="batchOperationChecked">{{ t('批量操作') }}</a-checkbox>
      <a-dropdown v-if="batchOperationChecked">
        <template #overlay>
          <a-menu>
            <a-menu-item>{{ t('Excel编辑') }}</a-menu-item>
            <a-menu-item>{{ t('开启') }}</a-menu-item>
            <a-menu-item>{{ t('暂停') }}</a-menu-item>
            <a-menu-item>{{ t('复制广告组') }}</a-menu-item>
            <a-menu-item>{{ t('定时开关') }}</a-menu-item>
            <a-menu-item>{{ t('修改出价') }}</a-menu-item>
            <a-menu-item>{{ t('修改竞价策略') }}</a-menu-item>
            <a-menu-item>{{ t('修改预算') }}</a-menu-item>
            <a-menu-item>{{ t('查看广告') }}</a-menu-item>
            <a-menu-item>{{ t('批量添加广告') }}</a-menu-item>
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
      <template #emptyText>
        <a-empty :description="t('没有数据')" />
      </template>
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'spend'">
          {{ formatCurrency(record.spend, record.currency) }}
        </template>
        <template v-else-if="column.dataIndex === 'cpm'">
          {{ record.cpm ? formatCurrency(record.cpm, record.currency) : '-' }}
        </template>
        <template v-else-if="column.dataIndex === 'cpc'">
          {{ record.cpc ? formatCurrency(record.cpc, record.currency) : '-' }}
        </template>
        <template v-else-if="column.dataIndex === 'ctr'">
          {{ record.ctr ? (record.ctr * 100).toFixed(2) + '%' : '-' }}
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
} from '@ant-design/icons-vue';
import { getAdGroupReportData } from '../mock-data';

interface Props {
  platform?: string;
}

const props = withDefaults(defineProps<Props>(), {
  platform: 'meta',
});

const { t } = useI18n();

const filters = ref({
  adGroup: undefined,
  campaign: undefined,
  status: undefined,
});

const subdivisionData = ref(undefined);
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
  { title: t('组名称'), dataIndex: 'adGroupName', key: 'adGroupName', sorter: true },
  { title: t('操作'), dataIndex: 'operation', key: 'operation' },
  { title: t('状态'), dataIndex: 'status', key: 'status', sorter: true },
  { title: t('产品'), dataIndex: 'product', key: 'product' },
  { title: t('预算'), dataIndex: 'budget', key: 'budget', sorter: true },
  { title: t('日预算消耗'), dataIndex: 'dailyBudgetConsumption', key: 'dailyBudgetConsumption', sorter: true },
  { title: t('最近操作'), dataIndex: 'recentOperation', key: 'recentOperation' },
  { title: t('花费'), dataIndex: 'spend', key: 'spend', sorter: true },
  { title: t('展示数'), dataIndex: 'impressions', key: 'impressions', sorter: true },
  { title: t('千次展示成本'), dataIndex: 'cpm', key: 'cpm', sorter: true },
  { title: t('点击成本'), dataIndex: 'cpc', key: 'cpc', sorter: true },
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

let isMounted = true;

const loadData = async () => {
  loading.value = true;
  try {
    const result = await getAdGroupReportData({
      page: pagination.value.current,
      pageSize: pagination.value.pageSize,
      platform: props.platform,
      ...filters.value,
    });
    if (isMounted) {
      tableData.value = result.data;
      pagination.value.total = result.total;
    }
  } catch (error) {
    console.error('加载数据失败:', error);
  } finally {
    if (isMounted) {
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

const stopWatch = watch(
  () => props.platform,
  () => {
    loadData();
  },
);

onUnmounted(() => {
  isMounted = false;
  stopWatch();
});

loadData();
</script>

<style lang="less" scoped>
.ad-group-report {
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

