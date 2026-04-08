<template>
  <div class="material-report">
    <div class="sub-tabs">
      <a-tabs v-model:activeKey="activeSubTab" @change="handleSubTabChange">
        <a-tab-pane key="type" :tab="t('类型')" />
        <a-tab-pane key="material" :tab="t('素材')" />
        <a-tab-pane key="material-group" :tab="t('素材组')" />
        <a-tab-pane key="designer" :tab="t('设计师')" />
        <a-tab-pane key="tag" :tab="t('标签')" />
        <a-tab-pane key="creator" :tab="t('创意人')" />
      </a-tabs>
    </div>
    <div class="filter-bar">
      <a-button :icon="h(FilterOutlined)">{{ t('筛选') }}</a-button>
      <a-select
        v-model:value="filters.aggregationMethod"
        style="width: 180px"
      >
        <a-select-option value="material_id">{{ t('汇总方式: 素材ID') }}</a-select-option>
      </a-select>
      <a-select
        v-if="activeSubTab === 'material'"
        v-model:value="filters.material"
        :placeholder="t('素材: 请选择')"
        style="width: 150px"
        allow-clear
        show-search
      />
      <a-select
        v-if="activeSubTab === 'designer'"
        v-model:value="filters.designer"
        :placeholder="t('设计师: 请搜索')"
        style="width: 150px"
        allow-clear
        show-search
      />
      <a-select
        v-if="activeSubTab === 'tag'"
        v-model:value="filters.tagLevel"
        style="width: 150px"
      >
        <a-select-option value="secondary">{{ t('标签级别:二级标签') }}</a-select-option>
      </a-select>
      <a-select
        v-if="activeSubTab === 'tag'"
        v-model:value="filters.tag"
        :placeholder="t('标签: 请选择')"
        style="width: 150px"
        allow-clear
      />
      <a-select
        v-if="activeSubTab === 'creator'"
        v-model:value="filters.creator"
        :placeholder="t('创意人: 请搜索')"
        style="width: 150px"
        allow-clear
        show-search
      />
      <a-select
        v-model:value="filters.product"
        :placeholder="t('产品: 请选择')"
        style="width: 150px"
        allow-clear
      />
    </div>
    <div class="chart-placeholder">
      <a-space>
        <a-button type="text" :icon="h(LineChartOutlined)" />
        <a-button type="text" :icon="h(BarChartOutlined)" />
        <a-button type="text" :icon="h(PieChartOutlined)" />
      </a-space>
    </div>
    <a-alert
      type="info"
      :message="t('素材报表数据与推广报表有差异,查看详情')"
      show-icon
      style="margin-bottom: 16px"
    >
      <template #message>
        {{ t('素材报表数据与推广报表有差异,查看详情') }}
        <a style="margin-left: 8px">{{ t('查看详情') }}</a>
      </template>
    </a-alert>
    <div class="action-bar">
      <a-checkbox v-model:checked="dataProportionShadow">{{ t('数据占比阴影') }}</a-checkbox>
      <a-tooltip>
        <template #title>{{ t('数据占比阴影说明') }}</template>
        <question-circle-outlined style="margin-left: 4px; color: #1890ff; cursor: help" />
      </a-tooltip>
      <div style="margin-left: auto; display: flex; gap: 8px; align-items: center">
        <a-button :icon="h(ReloadOutlined)" @click="reloadData">{{ t('刷新') }}</a-button>
        <a-dropdown>
          <template #overlay>
            <a-menu>
              <a-menu-item>{{ t('导出Excel') }}</a-menu-item>
              <a-menu-item>{{ t('导出CSV') }}</a-menu-item>
            </a-menu>
          </template>
          <a-button :icon="h(DownloadOutlined)">{{ t('导出') }}</a-button>
        </a-dropdown>
        <a-button :icon="h(SettingOutlined)">{{ t('自定义列') }}</a-button>
        <a-dropdown>
          <template #overlay>
            <a-menu>
              <a-menu-item>{{ t('展开') }}</a-menu-item>
            </a-menu>
          </template>
          <a-button>{{ t('展开') }}</a-button>
        </a-dropdown>
      </div>
    </div>
    <a-table
      :loading="loading"
      :columns="currentColumns"
      :data-source="tableData"
      :pagination="pagination"
      :row-key="record => record.id"
      @change="handleTableChange"
    >
      <template #emptyText>
        <a-empty :description="t('没有数据')" />
      </template>
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'thumbnail'">
          <img
            v-if="record.thumbnail"
            :src="record.thumbnail"
            alt=""
            style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px"
          />
          <span v-else>-</span>
        </template>
        <template v-else-if="column.dataIndex === 'tags'">
          <a-space>
            <a-tag v-for="tag in record.tags" :key="tag">{{ tag }}</a-tag>
          </a-space>
        </template>
        <template v-else-if="column.dataIndex === 'rating'">
          <a-rate :value="record.rating" disabled allow-half />
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
        <template v-else-if="column.dataIndex === 'ctr'">
          {{ record.ctr ? (record.ctr * 100).toFixed(2) + '%' : '-' }}
        </template>
        <template v-else-if="column.dataIndex === 'clickInstallRate'">
          {{ record.clickInstallRate ? (record.clickInstallRate * 100).toFixed(2) + '%' : '-' }}
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
  DownloadOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
  LineChartOutlined,
  BarChartOutlined,
  PieChartOutlined,
} from '@ant-design/icons-vue';
import { getMaterialReportData } from '../mock-data';

interface Props {
  platform?: string;
}

const props = withDefaults(defineProps<Props>(), {
  platform: 'meta',
});

const { t } = useI18n();

const activeSubTab = ref('material');
const filters = ref({
  aggregationMethod: 'material_id',
  material: undefined,
  designer: undefined,
  tagLevel: 'secondary',
  tag: undefined,
  creator: undefined,
  product: undefined,
});

const dataProportionShadow = ref(false);
const loading = ref(false);
const tableData = ref<any[]>([]);
const pagination = ref({
  current: 1,
  pageSize: 20,
  total: 0,
  showTotal: (total: number) => t('共') + total + t('条'),
});

const materialColumns = [
  { title: t('缩略图'), dataIndex: 'thumbnail', key: 'thumbnail' },
  { title: t('素材名称'), dataIndex: 'materialName', key: 'materialName', sorter: true },
  { title: t('评分(均衡)'), dataIndex: 'rating', key: 'rating', sorter: true },
  { title: t('标签'), dataIndex: 'tags', key: 'tags' },
  { title: t('设计师'), dataIndex: 'designer', key: 'designer' },
  { title: t('来源'), dataIndex: 'source', key: 'source' },
  { title: t('类型'), dataIndex: 'type', key: 'type' },
  { title: t('关联创意数'), dataIndex: 'creativeCount', key: 'creativeCount' },
  { title: t('花费'), dataIndex: 'spend', key: 'spend', sorter: true },
  { title: t('展示数'), dataIndex: 'impressions', key: 'impressions', sorter: true },
];

const designerColumns = [
  { title: t('设计师'), dataIndex: 'designer', key: 'designer', sorter: true },
  { title: t('花费'), dataIndex: 'spend', key: 'spend', sorter: true },
  { title: t('展示数'), dataIndex: 'impressions', key: 'impressions', sorter: true },
  { title: t('千次展示成本'), dataIndex: 'cpm', key: 'cpm', sorter: true },
  { title: t('点击数'), dataIndex: 'clicks', key: 'clicks', sorter: true },
  { title: t('点击成本'), dataIndex: 'cpc', key: 'cpc', sorter: true },
  { title: t('点击率'), dataIndex: 'ctr', key: 'ctr', sorter: true },
  { title: t('点击安装率'), dataIndex: 'clickInstallRate', key: 'clickInstallRate', sorter: true },
  { title: t('移动应用购物'), dataIndex: 'mobileAppPurchases', key: 'mobileAppPurchases', sorter: true },
];

const tagColumns = [
  { title: t('标签'), dataIndex: 'tag', key: 'tag', sorter: true },
  { title: t('花费'), dataIndex: 'spend', key: 'spend', sorter: true },
  { title: t('展示数'), dataIndex: 'impressions', key: 'impressions', sorter: true },
  { title: t('千次展示成本'), dataIndex: 'cpm', key: 'cpm', sorter: true },
  { title: t('展示成本'), dataIndex: 'displayCost', key: 'displayCost', sorter: true },
  { title: t('点击数'), dataIndex: 'clicks', key: 'clicks', sorter: true },
  { title: t('点击成本'), dataIndex: 'cpc', key: 'cpc', sorter: true },
  { title: t('点击率'), dataIndex: 'ctr', key: 'ctr', sorter: true },
  { title: t('点击安装率'), dataIndex: 'clickInstallRate', key: 'clickInstallRate', sorter: true },
  { title: t('移动应用购物'), dataIndex: 'mobileAppPurchases', key: 'mobileAppPurchases', sorter: true },
];

const creatorColumns = [
  { title: t('创意人'), dataIndex: 'creator', key: 'creator', sorter: true },
  { title: t('花费'), dataIndex: 'spend', key: 'spend', sorter: true },
  { title: t('展示数'), dataIndex: 'impressions', key: 'impressions', sorter: true },
  { title: t('千次展示成本'), dataIndex: 'cpm', key: 'cpm', sorter: true },
  { title: t('点击数'), dataIndex: 'clicks', key: 'clicks', sorter: true },
  { title: t('点击成本'), dataIndex: 'cpc', key: 'cpc', sorter: true },
  { title: t('点击率'), dataIndex: 'ctr', key: 'ctr', sorter: true },
  { title: t('点击安装率'), dataIndex: 'clickInstallRate', key: 'clickInstallRate', sorter: true },
  { title: t('移动应用购物'), dataIndex: 'mobileAppPurchases', key: 'mobileAppPurchases', sorter: true },
];

const currentColumns = computed(() => {
  switch (activeSubTab.value) {
    case 'material':
      return materialColumns;
    case 'designer':
      return designerColumns;
    case 'tag':
      return tagColumns;
    case 'creator':
      return creatorColumns;
    default:
      return materialColumns;
  }
});

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
    const result = await getMaterialReportData({
      page: pagination.value.current,
      pageSize: pagination.value.pageSize,
      platform: props.platform,
      subTab: activeSubTab.value,
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

const handleSubTabChange = () => {
  pagination.value.current = 1;
  loadData();
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
.material-report {
  .sub-tabs {
    margin-bottom: 16px;
  }

  .filter-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    align-items: center;
  }

  .chart-placeholder {
    margin-bottom: 16px;
  }

  .action-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    align-items: center;
  }
}
</style>

