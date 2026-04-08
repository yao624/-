<template>
  <div class="optimizer-report">
    <!-- 筛选栏 -->
    <div class="filter-bar">
      <a-button :icon="h(FilterOutlined)">{{ t('筛选') }}</a-button>
      <a-select
        v-model:value="filters.department"
        :placeholder="t('部门: 请选择')"
        style="width: 150px"
        allow-clear
      >
        <a-select-option v-for="dept in departments" :key="dept.id" :value="dept.id">
          {{ dept.name }}
        </a-select-option>
      </a-select>
      <a-select
        v-model:value="filters.optimizer"
        :placeholder="t('优化师: 请选择')"
        style="width: 150px"
        allow-clear
      >
        <a-select-option v-for="opt in optimizers" :key="opt.id" :value="opt.id">
          {{ opt.name }}
        </a-select-option>
      </a-select>
      <a-select
        v-model:value="filters.account"
        :placeholder="t('所属账户: 所属账户')"
        style="width: 150px"
        allow-clear
      >
        <a-select-option v-for="acc in accounts" :key="acc.id" :value="acc.id">
          {{ acc.name }}
        </a-select-option>
      </a-select>
      <a-select
        v-model:value="filters.channel"
        :placeholder="t('渠道: 请选择')"
        style="width: 150px"
        allow-clear
      >
        <a-select-option v-for="ch in channels" :key="ch" :value="ch">
          {{ ch }}
        </a-select-option>
      </a-select>
    </div>

    <!-- 数据预览 -->
    <div class="data-preview">
      <div class="preview-header">
        <span>{{ t('数据预览') }}</span>
        <div class="view-icons">
          <line-chart-outlined />
          <bar-chart-outlined />
          <calendar-outlined />
        </div>
        <a-select v-model:value="metric1" style="width: 150px">
          <a-select-option
            v-for="m in metrics"
            :key="m.value"
            :value="m.value"
          >
            {{ t('指标1') }}: {{ m.label }}
          </a-select-option>
        </a-select>
        <a-select v-model:value="metric2" :placeholder="t('指标2: 请选择')" style="width: 150px">
          <a-select-option
            v-for="m in metrics"
            :key="m.value"
            :value="m.value"
          >
            {{ m.label }}
          </a-select-option>
        </a-select>
      </div>
      <div class="chart-placeholder">
        <span>- {{ t('指标1') }}</span>
        <div class="chart-date">2026/01/05</div>
      </div>
    </div>

    <!-- 详细数据 -->
    <div class="detailed-data">
      <div class="data-header">
        <span>{{ t('详细数据') }}</span>
        <div class="data-actions">
          <a-checkbox v-model:checked="showDataShadow">{{ t('数据占比阴影') }}</a-checkbox>
          <a-button :icon="h(ReloadOutlined)" @click="reloadData">{{ t('刷新') }}</a-button>
          <a-button :icon="h(DownloadOutlined)" @click="exportData">{{ t('导出') }}</a-button>
          <a-button :icon="h(SettingOutlined)">{{ t('自定义列') }}</a-button>
        </div>
      </div>
      <a-table
        :loading="loading"
        :columns="columns"
        :data-source="tableData"
        :pagination="pagination"
        :row-key="record => record.id"
        @change="handleTableChange"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.dataIndex === 'spend'">
            {{ formatCurrency(record.spend) }}
          </template>
          <template v-else-if="column.dataIndex === 'cpm'">
            {{ formatCurrency(record.cpm) }}
          </template>
          <template v-else-if="column.dataIndex === 'cpc'">
            {{ formatCurrency(record.cpc) }}
          </template>
          <template v-else-if="column.dataIndex === 'conversionCost'">
            {{ formatCurrency(record.conversionCost) }}
          </template>
        </template>
      </a-table>
    </div>

    <!-- 右侧Top 10 -->
    <div class="top-10-sidebar">
      <a-card :title="`${t('花费')}: Top 10`" size="small">
        <div class="top-10-content">
          <div v-for="(item, index) in top10Data" :key="index" class="top-10-item">
            <span class="rank">{{ index + 1 }}</span>
            <span class="name">{{ item.name }}</span>
            <span class="value">{{ formatCurrency(item.value) }}</span>
          </div>
        </div>
      </a-card>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, h } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  FilterOutlined,
  ReloadOutlined,
  DownloadOutlined,
  SettingOutlined,
  LineChartOutlined,
  BarChartOutlined,
  CalendarOutlined,
} from '@ant-design/icons-vue';
import { getOptimizerReportData, getDepartments, getOptimizers, getAccounts } from '../mock-data';

const { t } = useI18n();

// 筛选
const filters = ref({
  department: undefined,
  optimizer: undefined,
  account: undefined,
  channel: undefined,
});

const departments = ref<any[]>([]);
const optimizers = ref<any[]>([]);
const accounts = ref<any[]>([]);
const channels = ref(['Meta', 'Google', 'TikTok']);

// 指标
const metric1 = ref('spend');
const metric2 = ref(undefined);
const metrics = ref([
  { value: 'spend', label: t('花费') },
  { value: 'impressions', label: t('展示数') },
  { value: 'cpm', label: t('千次展示成本') },
  { value: 'clicks', label: t('点击数') },
  { value: 'cpc', label: t('点击成本') },
  { value: 'ctr', label: t('点击率') },
  { value: 'conversions', label: t('转化数') },
  { value: 'conversionCost', label: t('转化成本') },
]);

// 表格
const loading = ref(false);
const tableData = ref<any[]>([]);
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0,
  showTotal: (total: number) => t('共') + total + t('条'),
});

const columns = ref([
  { title: t('优化师'), dataIndex: 'optimizer', key: 'optimizer', sorter: true },
  { title: t('花费'), dataIndex: 'spend', key: 'spend', sorter: true },
  { title: t('展示数'), dataIndex: 'impressions', key: 'impressions', sorter: true },
  { title: t('千次展示成本'), dataIndex: 'cpm', key: 'cpm', sorter: true },
  { title: t('点击数'), dataIndex: 'clicks', key: 'clicks', sorter: true },
  { title: t('点击成本'), dataIndex: 'cpc', key: 'cpc', sorter: true },
  { title: t('点击率'), dataIndex: 'ctr', key: 'ctr', sorter: true },
  { title: t('转化数'), dataIndex: 'conversions', key: 'conversions', sorter: true },
  { title: t('转化成本'), dataIndex: 'conversionCost', key: 'conversionCost', sorter: true },
  {
    title: t('AdsPolar创建广告组数'),
    dataIndex: 'adGroupsCreated',
    key: 'adGroupsCreated',
    sorter: true,
  },
  {
    title: t('AdsPolar创建广告数'),
    dataIndex: 'adsCreated',
    key: 'adsCreated',
    sorter: true,
  },
  {
    title: t('AdsPolar创建流水'),
    dataIndex: 'revenue',
    key: 'revenue',
    sorter: true,
  },
]);

// Top 10数据
const top10Data = ref<any[]>([]);
const showDataShadow = ref(false);

// 格式化货币
const formatCurrency = (value: number) => {
  if (!value) return '-';
  return new Intl.NumberFormat('zh-CN', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(value);
};

// 加载数据
const loadData = async () => {
  loading.value = true;
  try {
    const result = await getOptimizerReportData({
      page: pagination.value.current,
      pageSize: pagination.value.pageSize,
      ...filters.value,
    });
    tableData.value = result.data;
    pagination.value.total = result.total;
    top10Data.value = result.top10 || [];
  } catch (error) {
    console.error('加载数据失败:', error);
  } finally {
    loading.value = false;
  }
};

// 表格变化
const handleTableChange = (pag: any) => {
  pagination.value.current = pag.current;
  pagination.value.pageSize = pag.pageSize;
  loadData();
};

// 刷新数据
const reloadData = () => {
  loadData();
};

// 导出数据
const exportData = () => {
  // TODO: 实现导出功能
};

// 加载筛选选项
const loadFilterOptions = async () => {
  try {
    departments.value = await getDepartments();
    optimizers.value = await getOptimizers();
    accounts.value = await getAccounts();
  } catch (error) {
    console.error('加载筛选选项失败:', error);
  }
};

// 初始化
loadData();
loadFilterOptions();
</script>

<style lang="less" scoped>
.optimizer-report {
  display: flex;
  gap: 16px;

  .filter-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    align-items: center;
  }

  .data-preview {
    margin-bottom: 24px;
    padding: 16px;
    background: #fafafa;
    border-radius: 4px;

    .preview-header {
      display: flex;
      gap: 16px;
      align-items: center;
      margin-bottom: 16px;

      .view-icons {
        display: flex;
        gap: 8px;
        margin-left: auto;
        color: #1890ff;
        cursor: pointer;
      }
    }

    .chart-placeholder {
      height: 300px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #fff;
      border-radius: 4px;
      color: #999;

      .chart-date {
        margin-top: 8px;
        font-size: 12px;
      }
    }
  }

  .detailed-data {
    flex: 1;

    .data-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;

      .data-actions {
        display: flex;
        gap: 8px;
        align-items: center;
      }
    }
  }

  .top-10-sidebar {
    width: 300px;

    .top-10-content {
      .top-10-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;

        .rank {
          width: 24px;
          text-align: center;
          font-weight: bold;
          color: #1890ff;
        }

        .name {
          flex: 1;
        }

        .value {
          color: #666;
          font-size: 12px;
        }
      }
    }
  }
}
</style>

