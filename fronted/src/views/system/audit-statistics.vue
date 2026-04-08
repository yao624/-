<template>
  <a-card :loading="loading">
    <template #title>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span>{{ t('pages.audit.statistics.title') }}</span>
        <a-button
          type="text"
          size="small"
          @click="collapsed = !collapsed"
          style="padding: 0; height: auto;"
        >
          <down-outlined v-if="!collapsed" style="font-size: 12px;" />
          <up-outlined v-else style="font-size: 12px;" />
        </a-button>
      </div>
    </template>
    <template #extra>
      <a-space>
        <a-range-picker
          v-model:value="dateRange"
          :placeholder="[t('pages.audit.statistics.start_date'), t('pages.audit.statistics.end_date')]"
          :presets="rangePresets"
          @change="handleDateChange"
        />
        <a-button @click="fetchStatistics">
          <template #icon><reload-outlined /></template>
          {{ t('pages.refresh') }}
        </a-button>
      </a-space>
    </template>

    <div v-show="!collapsed" v-if="statistics">
      <!-- 概览卡片 -->
      <a-row :gutter="16" style="margin-bottom: 24px;">
        <a-col :xs="24" :sm="12" :md="6">
          <a-card :bordered="false" class="stat-card">
            <a-statistic
              :title="t('pages.audit.statistics.total_requests')"
              :value="statistics.summary.total_requests"
              :value-style="{ color: '#1890ff' }"
            >
              <template #prefix>
                <api-outlined />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="6">
          <a-card :bordered="false" class="stat-card">
            <a-statistic
              :title="t('pages.audit.statistics.unique_users')"
              :value="statistics.summary.unique_users"
              :value-style="{ color: '#52c41a' }"
            >
              <template #prefix>
                <user-outlined />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="6">
          <a-card :bordered="false" class="stat-card">
            <a-statistic
              :title="t('pages.audit.statistics.unique_ips')"
              :value="statistics.summary.unique_ips"
              :value-style="{ color: '#faad14' }"
            >
              <template #prefix>
                <global-outlined />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="6">
          <a-card :bordered="false" class="stat-card">
            <a-statistic
              :title="t('pages.audit.statistics.avg_response_time')"
              :value="statistics.summary.avg_response_time"
              :precision="2"
              suffix="ms"
              :value-style="{ color: '#722ed1' }"
            >
              <template #prefix>
                <clock-circle-outlined />
              </template>
            </a-statistic>
          </a-card>
        </a-col>
      </a-row>

      <!-- 图表区域 -->
      <a-row :gutter="16">
        <!-- 请求方法分布 -->
        <a-col :xs="24" :md="12" style="margin-bottom: 16px;">
          <a-card :title="t('pages.audit.statistics.method_distribution')" :bordered="false">
            <div style="min-height: 200px;">
              <a-row v-for="(count, method) in statistics.method_stats" :key="method" style="margin-bottom: 8px;">
                <a-col :span="4">
                  <a-tag :color="getMethodColor(method)">{{ method }}</a-tag>
                </a-col>
                <a-col :span="16">
                  <a-progress
                    :percent="(count / statistics.summary.total_requests * 100)"
                    :show-info="false"
                    :stroke-color="getMethodColor(method)"
                  />
                </a-col>
                <a-col :span="4" style="text-align: right;">
                  {{ count }}
                </a-col>
              </a-row>
            </div>
          </a-card>
        </a-col>

        <!-- 响应状态分布 -->
        <a-col :xs="24" :md="12" style="margin-bottom: 16px;">
          <a-card :title="t('pages.audit.statistics.status_distribution')" :bordered="false">
            <div style="min-height: 200px;">
              <a-row v-for="(count, status) in statistics.status_stats" :key="status" style="margin-bottom: 8px;">
                <a-col :span="4">
                  <a-tag :color="getStatusColor(Number(status))">{{ status }}</a-tag>
                </a-col>
                <a-col :span="16">
                  <a-progress
                    :percent="(count / statistics.summary.total_requests * 100)"
                    :show-info="false"
                    :stroke-color="getStatusColor(Number(status))"
                  />
                </a-col>
                <a-col :span="4" style="text-align: right;">
                  {{ count }}
                </a-col>
              </a-row>
            </div>
          </a-card>
        </a-col>

        <!-- 热门API路径 -->
        <a-col :xs="24" :md="12" style="margin-bottom: 16px;">
          <a-card :title="t('pages.audit.statistics.top_paths')" :bordered="false">
            <a-list
              :data-source="statistics.top_paths"
              :locale="{ emptyText: t('pages.audit.statistics.no_data') }"
            >
              <template #renderItem="{ item, index }">
                <a-list-item>
                  <a-list-item-meta>
                    <template #avatar>
                      <a-avatar :style="{ backgroundColor: getTopColor(index) }">
                        {{ index + 1 }}
                      </a-avatar>
                    </template>
                    <template #title>
                      <div style="word-break: break-all;">{{ item.path }}</div>
                    </template>
                    <template #description>
                      {{ t('pages.audit.statistics.request_count') }}: {{ item.count }}
                    </template>
                  </a-list-item-meta>
                </a-list-item>
              </template>
            </a-list>
          </a-card>
        </a-col>

        <!-- 活跃IP -->
        <a-col :xs="24" :md="12" style="margin-bottom: 16px;">
          <a-card :title="t('pages.audit.statistics.top_ips')" :bordered="false">
            <a-list
              :data-source="statistics.top_ips"
              :locale="{ emptyText: t('pages.audit.statistics.no_data') }"
            >
              <template #renderItem="{ item, index }">
                <a-list-item>
                  <a-list-item-meta>
                    <template #avatar>
                      <a-avatar :style="{ backgroundColor: getTopColor(index) }">
                        {{ index + 1 }}
                      </a-avatar>
                    </template>
                    <template #title>
                      {{ item.ip }}
                    </template>
                    <template #description>
                      {{ t('pages.audit.statistics.request_count') }}: {{ item.count }}
                    </template>
                  </a-list-item-meta>
                </a-list-item>
              </template>
            </a-list>
          </a-card>
        </a-col>
      </a-row>
    </div>
  </a-card>
</template>

<script lang="ts" setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import type { Dayjs } from 'dayjs';
import dayjs from 'dayjs';
import {
  ReloadOutlined,
  ApiOutlined,
  UserOutlined,
  GlobalOutlined,
  ClockCircleOutlined,
  DownOutlined,
  UpOutlined,
} from '@ant-design/icons-vue';
import { getRequestLogsStatistics } from '@/api/request_logs';
import type { Statistics } from '@/api/request_logs';

const { t } = useI18n();

const loading = ref(false);
const collapsed = ref(false);
const statistics = ref<Statistics | null>(null);
const dateRange = ref<[Dayjs, Dayjs]>([dayjs().subtract(6, 'days'), dayjs()]);

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

const getMethodColor = (method: string) => {
  const colors: Record<string, string> = {
    GET: '#1890ff',
    POST: '#52c41a',
    PUT: '#faad14',
    DELETE: '#f5222d',
    PATCH: '#722ed1',
  };
  return colors[method] || '#d9d9d9';
};

const getStatusColor = (status: number) => {
  if (status >= 200 && status < 300) return '#52c41a';
  if (status >= 300 && status < 400) return '#faad14';
  if (status >= 400 && status < 500) return '#f5222d';
  if (status >= 500) return '#cf1322';
  return '#d9d9d9';
};

const getTopColor = (index: number) => {
  const colors = ['#f56a00', '#7265e6', '#ffbf00', '#00a2ae', '#52c41a'];
  return colors[index] || '#1890ff';
};

const handleDateChange = () => {
  fetchStatistics();
};

const fetchStatistics = async () => {
  loading.value = true;
  try {
    const params = {
      date_start: dateRange.value[0].format('YYYY-MM-DD'),
      date_end: dateRange.value[1].format('YYYY-MM-DD'),
    };
    const res = await getRequestLogsStatistics(params);
    statistics.value = res.data;
  } catch (error: any) {
    message.error(error.response?.data?.message || t('pages.audit.error.fetch_statistics'));
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchStatistics();
});
</script>

<style scoped>
.stat-card {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

:deep(.ant-statistic-title) {
  font-size: 14px;
  color: rgba(0, 0, 0, 0.65);
}

:deep(.ant-statistic-content) {
  font-size: 24px;
  font-weight: 600;
}
</style>

