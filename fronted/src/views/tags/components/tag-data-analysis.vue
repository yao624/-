<template>
  <div class="tag-data-analysis">
    <!-- 筛选条件区域 -->
    <div class="filter-section">
      <div class="filter-row">
        <div class="filter-item">
          <span class="filter-label">{{ t('pages.tags.tagObject') }}</span>
          <a-select
            v-model:value="filterTagObject"
            :placeholder="t('pages.tags.selectTagObject')"
            style="width: 160px"
            allow-clear
          >
            <a-select-option v-for="obj in tagObjectOptions" :key="obj.id" :value="obj.id">{{ obj.name }}</a-select-option>
          </a-select>
        </div>
        <div class="filter-item">
          <span class="filter-label">{{ t('pages.tags.dateRange') }}</span>
          <a-range-picker
            v-model:value="filterDateRange"
            style="width: 240px"
          />
        </div>
        <div class="filter-item">
          <span class="filter-label">{{ t('pages.tags.tag') }}</span>
          <a-select
            v-model:value="filterTag"
            :placeholder="t('pages.tags.selectTag')"
            style="width: 160px"
            allow-clear
          >
            <a-select-option v-for="tag in tagOptions" :key="tag.id" :value="tag.id">{{ tag.name }}</a-select-option>
          </a-select>
        </div>
        <div class="filter-item">
          <span class="filter-label">{{ t('pages.tags.tagOption') }}</span>
          <a-select
            v-model:value="filterTagOption"
            :placeholder="t('pages.tags.selectTagOption')"
            style="width: 160px"
            allow-clear
          >
            <a-select-option v-for="opt in tagOptionOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</a-select-option>
          </a-select>
        </div>
        <div class="filter-actions">
          <a-button type="primary" @click="handleQuery">{{ t('pages.tags.query') }}</a-button>
          <a-button @click="handleReset">{{ t('pages.tags.reset') }}</a-button>
        </div>
      </div>
    </div>

    <!-- 图表区域 -->
    <div class="chart-section">
      <div class="chart-header">
        <div class="chart-controls">
          <a-popover v-model:open="chartMetricsVisible" trigger="click" placement="bottomLeft" :title="t('pages.tags.selectMetric')">
            <template #content>
              <div class="metrics-setting" style="margin-bottom: 8px">
                <div class="metrics-list">
                  <div v-if="chartType === 'line'">
                    <a-radio-group v-model:value="selectedMetric">
                      <div v-for="m in lineMetrics" :key="m.key" class="metrics-item">
                        <a-radio :value="m.key">{{ t(m.label) }}</a-radio>
                      </div>
                    </a-radio-group>
                  </div>
                  <div v-else>
                    <div v-for="m in barMetrics" :key="m.key" class="metrics-item">
                      <a-checkbox
                        :checked="selectedMetricsBar.includes(m.key)"
                        @change="() => toggleBarMetric(m.key)"
                      >{{ t(m.label) }}</a-checkbox>
                    </div>
                  </div>
                </div>
              </div>
            </template>
            <a-button type="text" class="chart-metrics-btn">
              {{ t('pages.tags.selectMetric') }}: {{ chartType === 'line' ? t(getMetricLabel(selectedMetric)) : t('pages.tags.selected') + selectedMetricsBar.length }}
              <DownOutlined />
            </a-button>
          </a-popover>
          <a-radio-group v-model:value="chartType" size="small" class="chart-type-toggle">
            <a-radio-button value="line">{{ t('pages.tags.lineChart') }}</a-radio-button>
            <a-radio-button value="bar">{{ t('pages.tags.barChart') }}</a-radio-button>
          </a-radio-group>
        </div>
      </div>
      <div class="chart-placeholder">
        <!-- 折线图 -->
        <div v-if="chartType === 'line'" class="simple-chart">
          <svg class="line-chart-svg" viewBox="0 0 700 180" preserveAspectRatio="xMidYMid meet">
            <!-- 网格线 -->
            <line v-for="i in 4" :key="'grid-'+i" :x1="40" :y1="20 + (i-1)*40" :x2="680" :y2="20 + (i-1)*40" stroke="#f0f0f0" stroke-width="1" />
            <!-- 折线 -->
            <polyline
              :points="lineChartPoints"
              fill="none"
              :stroke="lineColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <!-- 数据点 -->
            <circle
              v-for="(point, index) in lineChartPointsArray"
              :key="'point-'+index"
              :cx="point.x"
              :cy="point.y"
              r="4"
              :fill="lineColor"
            />
            <!-- X轴标签 -->
            <text
              v-for="(item, index) in chartData"
              :key="'label-'+index"
              :x="50 + index * 100"
              y="195"
              text-anchor="middle"
              font-size="12"
              fill="#999"
            >{{ item.date }}</text>
          </svg>
        </div>
        <!-- 柱状图 -->
        <div v-else class="simple-chart">
          <div class="chart-bars">
            <div v-for="(item, index) in chartData" :key="index" class="bar-item" :style="{ width: (100 / selectedMetricsBar.length) + '%' }">
              <div class="bar-stack">
                <div
                  v-for="(metric, mi) in selectedMetricsBar"
                  :key="metric"
                  class="bar bar-metric"
                  :class="'bar-color-' + mi"
                  :style="{ height: (getBarMetricValue(item, metric) / getMaxMetricValue(metric) * 100) + '%' }"
                ></div>
              </div>
              <div class="bar-label">{{ item.date }}</div>
              <div class="bar-legend">
                <span v-for="(metric, mi) in selectedMetricsBar" :key="metric" class="legend-item" :class="'legend-color-' + mi">
                  {{ t(getMetricLabel(metric)) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 数据表格 -->
    <div class="data-table">
      <div class="table-header">
        <a-popover v-model:open="metricsSettingVisible" trigger="click" placement="bottomRight" :title="t('pages.tags.metricsSetting')">
          <template #content>
            <div class="metrics-setting">
              <div class="metrics-list">
                <div v-for="m in allMetrics" :key="m.key" class="metrics-item">
                  <a-checkbox v-model:checked="m.checked">{{ t(m.label) }}</a-checkbox>
                </div>
              </div>
              <div class="metrics-actions" style="margin-top: 10px">
                <a-button size="small" @click="handleResetMetrics">{{ t('pages.tags.reset') }}</a-button>&nbsp;&nbsp;
                <a-button type="primary" size="small" @click="handleConfirmMetrics">{{ t('pages.tags.confirm') }}</a-button>
              </div>
            </div>
          </template>
          <a-button type="text" class="metrics-setting-btn">
            <SettingOutlined /> {{ t('pages.tags.metricsSetting') }}
          </a-button>
        </a-popover>
      </div>
      <a-table
        :columns="visibleColumns"
        :data-source="tableData"
        :pagination="false"
        :loading="loading"
        :scroll="{ x: 1500 }"
        row-key="id"
      >
        <template #bodyCell="{ column, text }">
          <template v-if="column.dataIndex === 'spend'">
            ${{ text }}
          </template>
          <template v-else-if="column.dataIndex === 'unit_price'">
            ${{ text }}
          </template>
          <template v-else-if="column.dataIndex === 'conversion_cost'">
            ${{ text }}
          </template>
          <template v-else-if="column.dataIndex === 'roi'">
            <span :class="{ 'high-roi': text > 2 }">{{ text }}</span>
          </template>
        </template>
      </a-table>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { SettingOutlined, DownOutlined } from '@ant-design/icons-vue';

const { t } = useI18n();

const props = defineProps<{
  loading?: boolean;
  tags?: any[];
  tagOptions?: any[];
}>();

// 筛选条件
const filterTagObject = ref<string | null>(null);
const filterDateRange = ref<[any, any] | null>(null);
const filterTag = ref<string | null>(null);
const filterTagOption = ref<string | null>(null);

// 标签对象选项
const tagObjectOptions = ref([
  { id: 'summary', name: '汇总' },
  { id: 'meta', name: 'Meta' },
  { id: 'google', name: 'Google' },
  { id: 'tiktok', name: 'TikTok' },
]);

// 标签选项
const tagOptions = computed(() => {
  if (props.tags && props.tags.length > 0) {
    return props.tags;
  }
  return [
    { id: '1', name: '短视频' },
    { id: '2', name: '图片广告' },
    { id: '3', name: '轮播图' },
    { id: '4', name: '信息流' },
    { id: '5', name: '品牌广告' },
    { id: '6', name: '促销标签' },
  ];
});

// 标签选项选项
const tagOptionOptions = computed(() => {
  if (props.tagOptions && props.tagOptions.length > 0) {
    return props.tagOptions;
  }
  return [
    { id: '1', name: '竖版短视频' },
    { id: '2', name: '横版短视频' },
    { id: '3', name: '方形短视频' },
    { id: '4', name: '横版视频A' },
    { id: '5', name: '竖版视频B' },
  ];
});

// 查询
const handleQuery = () => {
  // 模拟查询，实际应用中根据筛选条件获取数据
  console.log('查询条件:', {
    tagObject: filterTagObject.value,
    dateRange: filterDateRange.value,
    tag: filterTag.value,
    tagOption: filterTagOption.value,
  });
};

// 重置
const handleReset = () => {
  filterTagObject.value = null;
  filterDateRange.value = null;
  filterTag.value = null;
  filterTagOption.value = null;
};

// 图表数据（模拟）
const chartData = ref([
  { date: '03-01', spend: 1200, impressions: 52000, cpm: 23.08, clicks: 1850, ctr: 3.56, conversions: 72 },
  { date: '03-05', spend: 1800, impressions: 78000, cpm: 23.08, clicks: 2800, ctr: 3.59, conversions: 105 },
  { date: '03-10', spend: 1500, impressions: 62000, cpm: 24.19, clicks: 2100, ctr: 3.39, conversions: 88 },
  { date: '03-15', spend: 2200, impressions: 95000, cpm: 23.16, clicks: 3400, ctr: 3.58, conversions: 130 },
  { date: '03-20', spend: 1900, impressions: 82000, cpm: 23.17, clicks: 2900, ctr: 3.54, conversions: 112 },
  { date: '03-25', spend: 2400, impressions: 105000, cpm: 22.86, clicks: 3800, ctr: 3.62, conversions: 145 },
  { date: '03-30', spend: 2100, impressions: 92000, cpm: 22.83, clicks: 3300, ctr: 3.59, conversions: 128 },
]);

// 图表类型
const chartType = ref<'line' | 'bar'>('line');

// 指标选项
const lineMetrics = [
  { key: 'spend', label: 'pages.tags.spend' },
  { key: 'impressions', label: 'pages.tags.impressions' },
  { key: 'cpm', label: 'pages.tags.cpm' },
  { key: 'clicks', label: 'pages.tags.clicks' },
  { key: 'ctr', label: 'pages.tags.ctr' },
  { key: 'conversions', label: 'pages.tags.conversions' },
];

const barMetrics = [
  { key: 'spend', label: 'pages.tags.spend' },
  { key: 'impressions', label: 'pages.tags.impressions' },
  { key: 'cpm', label: 'pages.tags.cpm' },
  { key: 'clicks', label: 'pages.tags.clicks' },
  { key: 'ctr', label: 'pages.tags.ctr' },
  { key: 'conversions', label: 'pages.tags.conversions' },
];

const selectedMetric = ref('spend');
const selectedMetricsBar = ref<string[]>(['spend', 'impressions']);

const lineColor = computed(() => {
  const colors: Record<string, string> = {
    spend: '#1890ff',
    impressions: '#52c41a',
    cpm: '#fa8c16',
    clicks: '#722ed1',
    ctr: '#eb2f96',
    conversions: '#13c2c2',
  };
  return colors[selectedMetric.value] || '#1890ff';
});

const barColors = ['#1890ff', '#52c41a'];

// 折线图点计算
const lineChartPointsArray = computed(() => {
  const data = chartData.value;
  const max = getMaxMetricValue(selectedMetric.value);
  return data.map((item, index) => ({
    x: 50 + index * 100,
    y: 160 - (getMetricValue(item, selectedMetric.value) / max) * 140,
  }));
});

const lineChartPoints = computed(() => {
  return lineChartPointsArray.value.map(p => `${p.x},${p.y}`).join(' ');
});

const maxSpend = computed(() => {
  return Math.max(...chartData.value.map(item => item.spend));
});

// 获取指标值
const getMetricValue = (item: any, metric: string): number => {
  const values: Record<string, number> = {
    spend: item.spend,
    impressions: item.impressions,
    cpm: item.cpm,
    clicks: item.clicks,
    ctr: item.ctr,
    conversions: item.conversions,
  };
  return values[metric] || 0;
};

const getBarMetricValue = (item: any, metric: string): number => {
  return getMetricValue(item, metric);
};

const getMaxMetricValue = (metric: string): number => {
  const data = chartData.value;
  const maxVal = Math.max(...data.map(item => getMetricValue(item, metric)));
  return maxVal || 1;
};

const getMetricLabel = (metric: string): string => {
  const m = lineMetrics.find(m => m.key === metric);
  return m?.label || metric;
};

// 柱状图指标限制最多选2个
const handleBarMetricChange = (values: string[]) => {
  if (values.length > 2) {
    selectedMetricsBar.value = values.slice(0, 2);
  }
};

// 表格数据 - 使用标签选项数据
const tableData = computed(() => {
  if (props.tagOptions && props.tagOptions.length > 0) {
    return props.tagOptions.map((opt: any) => ({
      id: opt.id,
      option_name: opt.name,
      description: opt.description || '-',
      url: opt.url || '-',
      remark1: opt.remark1 || '-',
      remark2: opt.remark2 || '-',
      // 以下为模拟数据，实际应从接口获取
      impressions: Math.floor(Math.random() * 100000),
      clicks: Math.floor(Math.random() * 10000),
      spend: (Math.random() * 10000).toFixed(2),
      conversions: Math.floor(Math.random() * 500),
    }));
  }
  return [];
});

// 指标设置
const metricsSettingVisible = ref(false);

// 图表指标弹窗
const chartMetricsVisible = ref(false);

// 图表柱状图切换指标
const toggleBarMetric = (key: string) => {
  const idx = selectedMetricsBar.value.indexOf(key);
  if (idx > -1) {
    if (selectedMetricsBar.value.length > 1) {
      selectedMetricsBar.value.splice(idx, 1);
    }
  } else {
    if (selectedMetricsBar.value.length < 2) {
      selectedMetricsBar.value.push(key);
    } else {
      selectedMetricsBar.value.splice(0, 1, key);
    }
  }
};

// 所有指标
const allMetrics = ref([
  { key: 'impressions', label: 'pages.tags.impressions', checked: true },
  { key: 'clicks', label: 'pages.tags.clicks', checked: true },
  { key: 'ctr', label: 'pages.tags.ctr', checked: false },
  { key: 'spend', label: 'pages.tags.spend', checked: true },
  { key: 'cpm', label: 'pages.tags.cpm', checked: true },
  { key: 'conversions', label: 'pages.tags.conversions', checked: true },
  { key: 'conversion_cost', label: 'pages.tags.conversionCost', checked: false },
  { key: 'roi', label: 'pages.tags.roi', checked: false },
  { key: 'roas', label: 'pages.tags.roas7d', checked: false },
]);

// 重置指标设置
const handleResetMetrics = () => {
  allMetrics.value.forEach(m => {
    m.checked = false;
  });
};

// 确认指标设置
const handleConfirmMetrics = () => {
  metricsSettingVisible.value = false;
};

// 可见列
const visibleColumns = computed(() => {
  const fixedColumn = { title: t('pages.tags.optionName'), dataIndex: 'option_name', width: 200, fixed: 'left' as const };
  const metricCols = allMetrics.value
    .filter(m => m.checked)
    .map(m => {
      const colMap: Record<string, any> = {
        impressions: { title: t('pages.tags.impressions'), dataIndex: 'impressions', width: 120 },
        clicks: { title: t('pages.tags.clicks'), dataIndex: 'clicks', width: 100 },
        ctr: { title: t('pages.tags.ctr'), dataIndex: 'ctr', width: 80 },
        spend: { title: t('pages.tags.spend'), dataIndex: 'spend', width: 120 },
        cpm: { title: t('pages.tags.cpm'), dataIndex: 'cpm', width: 100 },
        conversions: { title: t('pages.tags.conversions'), dataIndex: 'conversions', width: 100 },
        conversion_cost: { title: t('pages.tags.conversionCost'), dataIndex: 'conversion_cost', width: 120 },
        roi: { title: t('pages.tags.roi'), dataIndex: 'roi', width: 80 },
        roas: { title: t('pages.tags.roas7d'), dataIndex: 'roas', width: 100 },
      };
      return colMap[m.key] || { title: m.key, dataIndex: m.key, width: 100 };
    });
  return [fixedColumn, ...metricCols];
});

// 格式化数字
const formatNumber = (num: number) => {
  return num.toLocaleString();
};
</script>

<style lang="less" scoped>
.tag-data-analysis {
  padding: 16px;

  .filter-section {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

    .filter-row {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;

      .filter-item {
        display: flex;
        align-items: center;
        gap: 8px;

        .filter-label {
          font-size: 14px;
          color: #666;
          white-space: nowrap;
        }
      }

      .filter-actions {
        display: flex;
        gap: 8px;
        margin-left: auto;
      }
    }
  }

  .chart-section {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

    .chart-header {
      margin-bottom: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;

      .chart-controls {
        display: flex;
        align-items: center;
        gap: 8px;

        .chart-metrics-btn {
          color: #666;
          font-size: 14px;
        }
      }

      .chart-type-toggle {
        margin-left: auto;
      }
    }

    .chart-placeholder {
      height: 200px;

      .line-chart-svg {
        width: 100%;
        height: 200px;
      }

      .simple-chart {
        height: 100%;
        display: flex;
        align-items: flex-end;

        .chart-bars {
          display: flex;
          align-items: flex-end;
          justify-content: space-around;
          width: 100%;
          height: 180px;
          border-bottom: 1px solid #eee;

          .bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;

            .bar-stack {
              display: flex;
              align-items: flex-end;
              height: 160px;
              gap: 4px;
            }

            .bar-metric {
              width: 28px;
              border-radius: 4px 4px 0 0;
              min-height: 4px;
              transition: height 0.3s ease;
            }

            .bar-color-0 {
              background: linear-gradient(180deg, #1890ff 0%, #69c0ff 100%);
            }

            .bar-color-1 {
              background: linear-gradient(180deg, #52c41a 0%, #b7eb8f 100%);
            }

            .bar-label {
              margin-top: 8px;
              font-size: 12px;
              color: #999;
            }

            .bar-legend {
              margin-top: 4px;
              display: flex;
              flex-direction: column;
              align-items: center;
              gap: 2px;

              .legend-item {
                font-size: 10px;
                color: #666;
              }

              .legend-color-0 {
                color: #1890ff;
              }

              .legend-color-1 {
                color: #52c41a;
              }
            }
          }
        }
      }
    }
  }

  .data-table {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);

    .table-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px;
      border-bottom: 1px solid #f0f0f0;

      .table-title {
        font-size: 16px;
        font-weight: 500;
        color: #333;
      }

      .metrics-setting-btn {
        color: #666;
        font-size: 14px;
      }
    }

    .metrics-setting {
      width: 240px;

      .metrics-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 16px;
      }

      .metrics-actions {
        display: flex;
        justify-content: space-between;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
      }
    }

    .high-roi {
      color: #52c41a;
      font-weight: 500;
    }
  }
}
</style>
