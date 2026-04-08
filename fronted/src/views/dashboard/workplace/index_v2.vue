<template>
  <div class="dashboard-container">
    <!-- 时间选择器 -->
    <div class="date-selector-container">
      <a-card size="small" :bordered="false" :body-style="{ padding: '16px 24px 20px 24px' }">
        <div class="date-controls">
          <a-space wrap>
            <span class="date-label">{{ $t('dashboard.dateRange') }}:</span>
            <a-button
              :type="dateType === 'today' ? 'primary' : 'default'"
              @click="setDateRange('today')"
            >
              {{ $t('dashboard.today') }}
            </a-button>
            <a-button
              :type="dateType === 'yesterday' ? 'primary' : 'default'"
              @click="setDateRange('yesterday')"
            >
              {{ $t('dashboard.yesterday') }}
            </a-button>
            <a-button
              :type="dateType === 'thisWeek' ? 'primary' : 'default'"
              @click="setDateRange('thisWeek')"
            >
              {{ $t('dashboard.thisWeek') }}
            </a-button>
            <a-button
              :type="dateType === 'lastWeek' ? 'primary' : 'default'"
              @click="setDateRange('lastWeek')"
            >
              {{ $t('dashboard.lastWeek') }}
            </a-button>
            <a-button
              :type="dateType === 'thisMonth' ? 'primary' : 'default'"
              @click="setDateRange('thisMonth')"
            >
              {{ $t('dashboard.thisMonth') }}
            </a-button>
            <a-button
              :type="dateType === 'lastMonth' ? 'primary' : 'default'"
              @click="setDateRange('lastMonth')"
            >
              {{ $t('dashboard.lastMonth') }}
            </a-button>
            <a-range-picker
              v-model:value="customDateRange"
              :disabled-date="disabledDate"
              @change="onCustomDateChange"
              format="YYYY-MM-DD"
            />
          </a-space>
          <div class="date-info">
            <span class="current-range">
              {{ formatDateRange(currentDateRange.date_start, currentDateRange.date_stop) }}
            </span>
          </div>
        </div>
      </a-card>
    </div>

    <!-- Overview 数据 -->
    <div class="overview-section">
      <a-row :gutter="16">
        <a-col :span="24">
          <h2 class="section-title">
            {{ $t('dashboard.overview') }}
            <small style="margin-left: 16px; color: #999;">
              (Campaign Tags: {{ campaignTagsData.length }},
              Offers: {{ offersData.length }},
              Ad Accounts: {{ adAccountsData.length }},
              Campaigns: {{ campaignsData.length }},
              Adsets: {{ adsetsData.length }})
            </small>
          </h2>
        </a-col>
      </a-row>

      <!-- 总览数据卡片 -->
      <a-row :gutter="16" class="overview-cards">
        <a-col :xs="24" :sm="12" :md="8" :lg="4" :xl="4">
          <a-card size="small" :loading="overviewLoading">
            <a-statistic
              title="Spend"
              :value="overviewData.spend"
              :precision="2"
              prefix="$"
              :value-style="{ color: '#cf1322' }"
            />
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="8" :lg="4" :xl="4">
          <a-card size="small" :loading="overviewLoading">
            <a-statistic
              title="Conversions"
              :value="overviewData.sales"
              :value-style="{ color: '#3f8600' }"
            />
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="8" :lg="4" :xl="4">
          <a-card size="small" :loading="overviewLoading">
            <a-statistic
              title="Revenue"
              :value="overviewData.revenue"
              :precision="2"
              prefix="$"
              :value-style="{ color: '#1890ff' }"
            />
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="8" :lg="4" :xl="4">
          <a-card size="small" :loading="overviewLoading">
            <a-statistic
              title="Profit"
              :value="overviewData.profit"
              :precision="2"
              prefix="$"
              :value-style="{ color: overviewData.profit >= 0 ? '#3f8600' : '#cf1322' }"
            />
          </a-card>
        </a-col>
        <a-col :xs="24" :sm="12" :md="8" :lg="4" :xl="4">
          <a-card size="small" :loading="overviewLoading">
            <a-statistic
              title="ROI"
              :value="overviewData.roi * 100"
              :precision="2"
              suffix="%"
              :value-style="{ color: overviewData.roi >= 0 ? '#3f8600' : '#cf1322' }"
            />
          </a-card>
        </a-col>
      </a-row>

      <!-- 用户数据表格 (仅admin可见) -->
      <div v-if="isAdmin" class="users-section">
        <a-card :title="$t('dashboard.userPerformance')" size="small" :loading="usersLoading">
          <a-table
            ref="usersTable"
            size="small"
            :columns="usersColumns"
            :data-source="usersData"
            :pagination="false"
            :scroll="{ x: 800 }"
            :row-key="record => record.user_id || record.label"
            bordered
sticky
            @change="() => {}"
          />
        </a-card>
      </div>

      <!-- Campaign Tags 数据表格 -->
      <div class="campaign-tags-section">
        <a-card :title="$t('dashboard.campaignTags')" size="small" :loading="campaignTagsLoading">
          <a-table
            ref="campaignTagsTable"
            size="small"
            :columns="campaignTagsColumns"
            :data-source="campaignTagsData"
            :pagination="campaignTagsPagination"
            :scroll="{ x: 1000 }"
            :row-key="record => record.tag_id"
            bordered
sticky
            @change="handleCampaignTagsTableChange"
          />
        </a-card>
      </div>

      <!-- Offers 数据表格 -->
      <div class="offers-section">
        <a-card :title="$t('dashboard.offers')" size="small" :loading="offersLoading">
          <a-table
            ref="offersTable"
            size="small"
            :columns="offersColumns"
            :data-source="offersData"
            :pagination="offersPagination"
            :scroll="{ x: 1000 }"
            :row-key="record => record.offer_source_id"
            bordered
sticky
            @change="handleOffersTableChange"
          />
        </a-card>
      </div>

      <!-- Ad Accounts 数据表格 -->
      <div class="ad-accounts-section">
        <a-card :title="$t('dashboard.adAccounts')" size="small" :loading="adAccountsLoading">
          <a-table
            ref="adAccountsTable"
            size="small"
            :columns="adAccountsColumns"
            :data-source="adAccountsData"
            :pagination="adAccountsPagination"
            :scroll="{ x: 1200 }"
            :row-key="record => record.account_id"
            bordered
sticky
            @change="handleAdAccountsTableChange"
          />
        </a-card>
      </div>

      <!-- Campaigns 数据表格 -->
      <div class="campaigns-section">
        <a-card :title="$t('dashboard.campaigns')" size="small" :loading="campaignsLoading">
          <a-table
            ref="campaignsTable"
            size="small"
            :columns="campaignsColumns"
            :data-source="campaignsData"
            :pagination="campaignsPagination"
            :scroll="{ x: 1400 }"
            :row-key="record => record.campaign_id"
            bordered
sticky
            @change="handleCampaignsTableChange"
          />
        </a-card>
      </div>

      <!-- Adsets 数据表格 -->
      <div class="adsets-section">
        <a-card :title="$t('dashboard.adsets')" size="small" :loading="adsetsLoading">
          <a-table
            ref="adsetsTable"
            size="small"
            :columns="adsetsColumns"
            :data-source="adsetsData"
            :pagination="adsetsPagination"
            :scroll="{ x: 1400 }"
            :row-key="record => record.adset_id"
            bordered
sticky
            @change="handleAdsetsTableChange"
          />
        </a-card>
      </div>
    </div>

    <!-- Trends 数据 -->
    <div class="trends-section">
      <a-row :gutter="16">
        <a-col :span="24">
          <h2 class="section-title">
            {{ $t('dashboard.trends') }}
            <a-button
              type="primary"
              :loading="trendsLoading"
              @click="loadTrendsData"
              style="margin-left: 16px;"
            >
              {{ $t('dashboard.refresh') }}
            </a-button>
          </h2>
        </a-col>
      </a-row>

      <!-- 趋势图表 -->
      <div class="trends-chart-section">
        <a-card :title="$t('dashboard.trendsChart')" size="small" class="chart-card">
          <template #extra>
            <a-tag color="blue">
              {{ formatDateRange(currentDateRange.date_start, currentDateRange.date_stop) }}
            </a-tag>
          </template>
          <div class="chart-controls">
            <a-space>
              <span class="control-label">{{ $t('dashboard.selectMetrics') }}:</span>
              <a-select
                v-model:value="selectedMetrics"
                mode="multiple"
                style="min-width: 320px;"
                :options="metricsOptions"
                @change="updateChart"
                :disabled="trendsData.length === 0"
                placeholder="请选择要显示的指标"
              />
            </a-space>
          </div>
          <div ref="trendsChartRef" class="chart-container">
            <div v-if="trendsData.length === 0" class="empty-chart">
              <a-empty style="margin-top: 150px;" />
            </div>
          </div>
        </a-card>
      </div>

      <!-- 趋势数据表格 -->
      <div class="trends-table-section">
        <a-card :title="$t('dashboard.trendsData')" size="small">
          <a-table
            ref="trendsTable"
            size="small"
            :columns="trendsColumns"
            :data-source="trendsData"
            :pagination="trendsPagination"
            :scroll="{ x: 1000 }"
            :row-key="record => record.date"
            bordered
sticky
            @change="handleTrendsTableChange"
          >
            <template #emptyText>
              <a-empty />
            </template>
          </a-table>
        </a-card>
      </div>

      <!-- 用户趋势数据 (仅admin可见) -->
      <div v-if="isAdmin" class="user-trends-section">
        <a-card
          :title="$t('dashboard.userTrends')"
          size="small"
          :loading="userTrendsLoading"
        >
          <template #extra>
            <a-button
              type="primary"
              :loading="userTrendsLoading"
              @click="loadUserTrendsData"
            >
              {{ $t('dashboard.refresh') }}
            </a-button>
          </template>
          <a-table
            ref="userTrendsTable"
            size="small"
            :columns="userTrendsColumns"
            :data-source="flattenedUserTrendsData"
            :pagination="userTrendsPagination"
            :scroll="{ x: 1200 }"
            :row-key="record => `${record.date}-${record.user_id || record.user_name}`"
            bordered
sticky
            @change="handleUserTrendsTableChange"
          >
            <template #emptyText>
              <a-empty />
            </template>
          </a-table>
        </a-card>
      </div>

      <!-- Offer 趋势数据 -->
      <div class="offer-trends-section">
        <a-card
          :title="$t('dashboard.offerTrends')"
          size="small"
          :loading="offerTrendsLoading"
        >
          <template #extra>
            <a-button
              type="primary"
              :loading="offerTrendsLoading"
              @click="loadOfferTrendsData"
            >
              {{ $t('dashboard.refresh') }}
            </a-button>
          </template>
          <a-table
            ref="offerTrendsTable"
            size="small"
            :columns="offerTrendsColumns"
            :data-source="flattenedOfferTrendsData"
            :pagination="offerTrendsPagination"
            :scroll="{ x: 1200 }"
            :row-key="record => `${record.date}-${record.offer_source_id}`"
            bordered
sticky
            @change="handleOfferTrendsTableChange"
          >
            <template #emptyText>
              <a-empty />
            </template>
          </a-table>
        </a-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import { useUserStore } from '@/store/user';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import type { Dayjs } from 'dayjs';
import * as echarts from 'echarts';
import type { ECharts } from 'echarts';
import type { EChartOption } from 'echarts';
import type { SortOrder } from 'ant-design-vue/es/table/interface';
import {
  getOverview,
  getOverviewUsers,
  getOverviewCampaignTags,
  getOverviewOffers,
  getOverviewAdAccounts,
  getOverviewCampaigns,
  getOverviewAdsets,
  getTrends,
  getTrendsUsers,
  getTrendsOffers,
  type DateRange,
  type OverviewData,
  type UserOverviewData,
  type CampaignTagData,
  type OfferData,
  type AdAccountData,
  type CampaignData,
  type AdsetData,
  type TrendData,
  type UserTrendData,
  type OfferTrendData,
} from '@/api/insights';

const { t } = useI18n();
const userStore = useUserStore();

// 检查是否为admin用户
const isAdmin = computed(() => {
  return userStore.currentUser?.role?.name === 'admin';
});

// 日期相关状态
const dateType = ref<'today' | 'yesterday' | 'thisWeek' | 'lastWeek' | 'thisMonth' | 'lastMonth' | 'custom'>('today');
const customDateRange = ref<[Dayjs, Dayjs] | null>(null);
const currentDateRange = reactive<DateRange>({
  date_start: dayjs().format('YYYY-MM-DD'),
  date_stop: dayjs().format('YYYY-MM-DD'),
});

// 加载状态
const overviewLoading = ref(false);
const usersLoading = ref(false);
const campaignTagsLoading = ref(false);
const offersLoading = ref(false);
const adAccountsLoading = ref(false);
const campaignsLoading = ref(false);
const adsetsLoading = ref(false);
const trendsLoading = ref(false);
const userTrendsLoading = ref(false);
const offerTrendsLoading = ref(false);

// 数据状态
const overviewData = reactive<OverviewData>({
  spend: 0,
  link_clicks: 0,
  offer_clicks: 0,
  leads: 0,
  sales: 0,
  revenue: 0,
  profit: 0,
  roi: 0,
  date_start: '',
  date_stop: '',
});

const usersData = ref<UserOverviewData[]>([]);
const campaignTagsData = ref<CampaignTagData[]>([]);
const offersData = ref<OfferData[]>([]);
const adAccountsData = ref<AdAccountData[]>([]);
const campaignsData = ref<CampaignData[]>([]);
const adsetsData = ref<AdsetData[]>([]);
const trendsData = ref<TrendData[]>([]);
const userTrendsData = ref<UserTrendData[]>([]);
const offerTrendsData = ref<OfferTrendData[]>([]);

// 分页状态
const campaignTagsPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showTotal: (total: number, range: [number, number]) => `第 ${range[0]}-${range[1]} 条，共 ${total} 条`,
  pageSizeOptions: ['10', '20', '50', '100'],
});

const offersPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showTotal: (total: number, range: [number, number]) => `第 ${range[0]}-${range[1]} 条，共 ${total} 条`,
  pageSizeOptions: ['10', '20', '50', '100'],
});

const adAccountsPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showTotal: (total: number, range: [number, number]) => `第 ${range[0]}-${range[1]} 条，共 ${total} 条`,
  pageSizeOptions: ['10', '20', '50', '100', '200'],
});

const campaignsPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showTotal: (total: number, range: [number, number]) => `第 ${range[0]}-${range[1]} 条，共 ${total} 条`,
  pageSizeOptions: ['10', '20', '50', '100', '200'],
});

const adsetsPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showTotal: (total: number, range: [number, number]) => `第 ${range[0]}-${range[1]} 条，共 ${total} 条`,
  pageSizeOptions: ['10', '20', '50', '100', '200'],
});

const trendsPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showTotal: (total: number, range: [number, number]) => `第 ${range[0]}-${range[1]} 条，共 ${total} 条`,
  pageSizeOptions: ['10', '20', '50', '100'],
});

const userTrendsPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showTotal: (total: number, range: [number, number]) => `第 ${range[0]}-${range[1]} 条，共 ${total} 条`,
  pageSizeOptions: ['10', '20', '50', '100'],
});

const offerTrendsPagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  showTotal: (total: number, range: [number, number]) => `第 ${range[0]}-${range[1]} 条，共 ${total} 条`,
  pageSizeOptions: ['10', '20', '50', '100'],
});

// 图表相关
const trendsChartRef = ref<HTMLDivElement>();
let trendsChart: ECharts | null = null;
const selectedMetrics = ref(['spend', 'revenue', 'profit']);
const metricsOptions = [
  { label: 'Spend', value: 'spend' },
  { label: 'Revenue', value: 'revenue' },
  { label: 'Profit', value: 'profit' },
  { label: 'ROI', value: 'roi' },
  { label: 'Lead', value: 'leads' },
  { label: 'Conversions', value: 'sales' },
  { label: 'Link Clicks', value: 'link_clicks' },
  { label: 'Offer Clicks', value: 'offer_clicks' },
];

// 表格列定义
const usersColumns = [
  {
    title: t('dashboard.user'),
    dataIndex: 'label',
    key: 'label',
    width: 150,
  },
  {
    title: 'Spend',
    dataIndex: 'spend',
    key: 'spend',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.spend) - Number(b.spend),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Profit',
    dataIndex: 'profit',
    key: 'profit',
    width: 120,
    customRender: ({ text }) => ({
      children: `$${Number(text).toFixed(2)}`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.profit) - Number(b.profit),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'ROI',
    dataIndex: 'roi',
    key: 'roi',
    width: 100,
    customRender: ({ text }) => ({
      children: `${(Number(text) * 100).toFixed(2)}%`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.roi) - Number(b.roi),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Offer Clicks',
    dataIndex: 'offer_clicks',
    key: 'offer_clicks',
    width: 120,
    sorter: (a, b) => Number(a.offer_clicks) - Number(b.offer_clicks),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Conversions',
    dataIndex: 'sales',
    key: 'sales',
    width: 120,
    sorter: (a, b) => Number(a.sales) - Number(b.sales),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

const campaignTagsColumns = [
  {
    title: t('dashboard.tagName'),
    dataIndex: 'tag_name',
    key: 'tag_name',
    width: 150,
  },
  {
    title: 'Spend',
    dataIndex: 'spend',
    key: 'spend',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.spend) - Number(b.spend),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Profit',
    dataIndex: 'profit',
    key: 'profit',
    width: 120,
    customRender: ({ text }) => ({
      children: `$${Number(text).toFixed(2)}`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.profit) - Number(b.profit),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'ROI',
    dataIndex: 'roi',
    key: 'roi',
    width: 100,
    customRender: ({ text }) => ({
      children: `${(Number(text) * 100).toFixed(2)}%`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.roi) - Number(b.roi),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Offer Clicks',
    dataIndex: 'offer_clicks',
    key: 'offer_clicks',
    width: 120,
    sorter: (a, b) => Number(a.offer_clicks) - Number(b.offer_clicks),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Conversions',
    dataIndex: 'sales',
    key: 'sales',
    width: 120,
    sorter: (a, b) => Number(a.sales) - Number(b.sales),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

const offersColumns = [
  {
    title: t('dashboard.offerName'),
    dataIndex: 'offer_name',
    key: 'offer_name',
    width: 300,
    ellipsis: true,
    resizable: true,
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'EPC',
    dataIndex: 'epc',
    key: 'epc',
    width: 120,
    customRender: ({ record }) => {
      const revenue = Number(record.revenue) || 0;
      const offerClicks = Number(record.offer_clicks) || 0;
      const epc = offerClicks > 0 ? revenue / offerClicks : 0;
      return `$${epc.toFixed(2)}`;
    },
    sorter: (a, b) => {
      const epcA = Number(a.offer_clicks) > 0 ? Number(a.revenue) / Number(a.offer_clicks) : 0;
      const epcB = Number(b.offer_clicks) > 0 ? Number(b.revenue) / Number(b.offer_clicks) : 0;
      return epcA - epcB;
    },
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Offer Clicks',
    dataIndex: 'offer_clicks',
    key: 'offer_clicks',
    width: 120,
    sorter: (a, b) => Number(a.offer_clicks) - Number(b.offer_clicks),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Conversions',
    dataIndex: 'sales',
    key: 'sales',
    width: 120,
    sorter: (a, b) => Number(a.sales) - Number(b.sales),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

const adAccountsColumns = [
  {
    title: t('dashboard.accountName'),
    dataIndex: 'account_name',
    key: 'account_name',
    width: 200,
    ellipsis: true,
    resizable: true,
  },
  {
    title: t('dashboard.accountId'),
    dataIndex: 'account_id',
    key: 'account_id',
    width: 150,
  },
  {
    title: t('dashboard.accountStatus'),
    dataIndex: 'account_status',
    key: 'account_status',
    width: 120,
  },
  {
    title: 'Spend',
    dataIndex: 'spend',
    key: 'spend',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.spend) - Number(b.spend),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Profit',
    dataIndex: 'profit',
    key: 'profit',
    width: 120,
    customRender: ({ text }) => ({
      children: `$${Number(text).toFixed(2)}`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.profit) - Number(b.profit),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'ROI',
    dataIndex: 'roi',
    key: 'roi',
    width: 100,
    customRender: ({ text }) => ({
      children: `${(Number(text) * 100).toFixed(2)}%`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.roi) - Number(b.roi),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Conversions',
    dataIndex: 'sales',
    key: 'sales',
    width: 120,
    sorter: (a, b) => Number(a.sales) - Number(b.sales),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

const campaignsColumns = [
  {
    title: t('dashboard.campaignName'),
    dataIndex: 'campaign_name',
    key: 'campaign_name',
    width: 250,
    ellipsis: true,
    resizable: true,
  },
  {
    title: t('dashboard.campaignId'),
    dataIndex: 'campaign_id',
    key: 'campaign_id',
    width: 150,
  },
  {
    title: t('dashboard.adAccountId'),
    dataIndex: 'ad_account_id',
    key: 'ad_account_id',
    width: 150,
  },
  {
    title: t('dashboard.accountStatus'),
    dataIndex: 'account_status',
    key: 'account_status',
    width: 120,
  },
  {
    title: 'Spend',
    dataIndex: 'spend',
    key: 'spend',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.spend) - Number(b.spend),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Profit',
    dataIndex: 'profit',
    key: 'profit',
    width: 120,
    customRender: ({ text }) => ({
      children: `$${Number(text).toFixed(2)}`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.profit) - Number(b.profit),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'ROI',
    dataIndex: 'roi',
    key: 'roi',
    width: 100,
    customRender: ({ text }) => ({
      children: `${(Number(text) * 100).toFixed(2)}%`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.roi) - Number(b.roi),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Offer Clicks',
    dataIndex: 'offer_clicks',
    key: 'offer_clicks',
    width: 120,
    sorter: (a, b) => Number(a.offer_clicks) - Number(b.offer_clicks),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Conversions',
    dataIndex: 'sales',
    key: 'sales',
    width: 120,
    sorter: (a, b) => Number(a.sales) - Number(b.sales),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

const adsetsColumns = [
  {
    title: t('dashboard.adsetName'),
    dataIndex: 'adset_name',
    key: 'adset_name',
    width: 250,
    ellipsis: true,
    resizable: true,
  },
  {
    title: t('dashboard.adsetId'),
    dataIndex: 'adset_id',
    key: 'adset_id',
    width: 150,
  },
  {
    title: t('dashboard.adAccountId'),
    dataIndex: 'ad_account_id',
    key: 'ad_account_id',
    width: 150,
  },
  {
    title: t('dashboard.accountStatus'),
    dataIndex: 'account_status',
    key: 'account_status',
    width: 120,
  },
  {
    title: 'Spend',
    dataIndex: 'spend',
    key: 'spend',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.spend) - Number(b.spend),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Profit',
    dataIndex: 'profit',
    key: 'profit',
    width: 120,
    customRender: ({ text }) => ({
      children: `$${Number(text).toFixed(2)}`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.profit) - Number(b.profit),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'ROI',
    dataIndex: 'roi',
    key: 'roi',
    width: 100,
    customRender: ({ text }) => ({
      children: `${(Number(text) * 100).toFixed(2)}%`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.roi) - Number(b.roi),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Offer Clicks',
    dataIndex: 'offer_clicks',
    key: 'offer_clicks',
    width: 120,
    sorter: (a, b) => Number(a.offer_clicks) - Number(b.offer_clicks),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Conversions',
    dataIndex: 'sales',
    key: 'sales',
    width: 120,
    sorter: (a, b) => Number(a.sales) - Number(b.sales),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

const trendsColumns = [
  {
    title: t('dashboard.date'),
    dataIndex: 'date',
    key: 'date',
    width: 120,
  },
  {
    title: 'Spend',
    dataIndex: 'spend',
    key: 'spend',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.spend) - Number(b.spend),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a, b) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Profit',
    dataIndex: 'profit',
    key: 'profit',
    width: 120,
    customRender: ({ text }) => ({
      children: `$${Number(text).toFixed(2)}`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.profit) - Number(b.profit),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'ROI',
    dataIndex: 'roi',
    key: 'roi',
    width: 100,
    customRender: ({ text }) => ({
      children: `${(Number(text) * 100).toFixed(2)}%`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a, b) => Number(a.roi) - Number(b.roi),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Lead',
    dataIndex: 'leads',
    key: 'leads',
    width: 100,
    sorter: (a, b) => Number(a.leads) - Number(b.leads),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Conversions',
    dataIndex: 'sales',
    key: 'sales',
    width: 120,
    sorter: (a, b) => Number(a.sales) - Number(b.sales),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Link Clicks',
    dataIndex: 'link_clicks',
    key: 'link_clicks',
    width: 120,
    sorter: (a, b) => Number(a.link_clicks) - Number(b.link_clicks),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Offer Clicks',
    dataIndex: 'offer_clicks',
    key: 'offer_clicks',
    width: 120,
    sorter: (a, b) => Number(a.offer_clicks) - Number(b.offer_clicks),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

const userTrendsColumns = [
  {
    title: t('dashboard.date'),
    dataIndex: 'date',
    key: 'date',
    width: 120,
  },
  {
    title: t('dashboard.user'),
    dataIndex: 'user_name',
    key: 'user_name',
    width: 150,
  },
  {
    title: 'Spend',
    dataIndex: 'spend',
    key: 'spend',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a: any, b: any) => Number(a.spend) - Number(b.spend),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a: any, b: any) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Profit',
    dataIndex: 'profit',
    key: 'profit',
    width: 120,
    customRender: ({ text }) => ({
      children: `$${Number(text).toFixed(2)}`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a: any, b: any) => Number(a.profit) - Number(b.profit),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'ROI',
    dataIndex: 'roi',
    key: 'roi',
    width: 100,
    customRender: ({ text }) => ({
      children: `${(Number(text) * 100).toFixed(2)}%`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a: any, b: any) => Number(a.roi) - Number(b.roi),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

const offerTrendsColumns = [
  {
    title: t('dashboard.date'),
    dataIndex: 'date',
    key: 'date',
    width: 120,
  },
  {
    title: t('dashboard.offerName'),
    dataIndex: 'offer_name',
    key: 'offer_name',
    width: 250,
    ellipsis: true,
  },
  {
    title: 'Spend',
    dataIndex: 'spend',
    key: 'spend',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a: any, b: any) => Number(a.spend) - Number(b.spend),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Revenue',
    dataIndex: 'revenue',
    key: 'revenue',
    width: 120,
    customRender: ({ text }) => `$${Number(text).toFixed(2)}`,
    sorter: (a: any, b: any) => Number(a.revenue) - Number(b.revenue),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'Profit',
    dataIndex: 'profit',
    key: 'profit',
    width: 120,
    customRender: ({ text }) => ({
      children: `$${Number(text).toFixed(2)}`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a: any, b: any) => Number(a.profit) - Number(b.profit),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
  {
    title: 'ROI',
    dataIndex: 'roi',
    key: 'roi',
    width: 100,
    customRender: ({ text }) => ({
      children: `${(Number(text) * 100).toFixed(2)}%`,
      style: { color: Number(text) >= 0 ? '#3f8600' : '#cf1322' },
    }),
    sorter: (a: any, b: any) => Number(a.roi) - Number(b.roi),
    sortDirections: ['descend', 'ascend'] as SortOrder[],
  },
];

// 计算属性：展开的用户趋势数据
const flattenedUserTrendsData = computed(() => {
  const result: any[] = [];
  userTrendsData.value.forEach(dayData => {
    dayData.users.forEach(user => {
      result.push({
        date: dayData.date,
        user_id: user.user_id,
        user_name: user.user_name,
        spend: user.spend,
        revenue: user.revenue,
        profit: user.profit,
        roi: user.roi,
        offer_clicks: user.offer_clicks,
        sales: user.sales,
      });
    });
  });
  return result;
});

// 计算属性：展开的Offer趋势数据
const flattenedOfferTrendsData = computed(() => {
  const result: any[] = [];
  offerTrendsData.value.forEach(dayData => {
    dayData.offers.forEach(offer => {
      result.push({
        date: dayData.date,
        offer_name: offer.offer_name,
        offer_source_id: offer.offer_source_id,
        spend: offer.spend,
        revenue: offer.revenue,
        profit: offer.profit,
        roi: offer.roi,
        offer_clicks: offer.offer_clicks,
        sales: offer.sales,
      });
    });
  });
  return result;
});

// 方法：设置日期范围
const setDateRange = (type: 'today' | 'yesterday' | 'thisWeek' | 'lastWeek' | 'thisMonth' | 'lastMonth') => {
  dateType.value = type;
  customDateRange.value = null;

  let start: string, end: string;

  switch (type) {
    case 'today':
      start = end = dayjs().format('YYYY-MM-DD');
      break;
    case 'yesterday':
      start = end = dayjs().subtract(1, 'day').format('YYYY-MM-DD');
      break;
    case 'thisWeek':
      start = dayjs().startOf('week').format('YYYY-MM-DD');
      end = dayjs().format('YYYY-MM-DD');
      break;
    case 'lastWeek':
      start = dayjs().subtract(1, 'week').startOf('week').format('YYYY-MM-DD');
      end = dayjs().subtract(1, 'week').endOf('week').format('YYYY-MM-DD');
      break;
    case 'thisMonth':
      start = dayjs().startOf('month').format('YYYY-MM-DD');
      end = dayjs().format('YYYY-MM-DD');
      break;
    case 'lastMonth':
      start = dayjs().subtract(1, 'month').startOf('month').format('YYYY-MM-DD');
      end = dayjs().subtract(1, 'month').endOf('month').format('YYYY-MM-DD');
      break;
  }

  currentDateRange.date_start = start;
  currentDateRange.date_stop = end;

  loadOverviewData();
};

// 方法：自定义日期变化
const onCustomDateChange = (dates: [Dayjs, Dayjs] | null) => {
  if (dates && dates.length === 2) {
    dateType.value = 'custom';
    currentDateRange.date_start = dates[0].format('YYYY-MM-DD');
    currentDateRange.date_stop = dates[1].format('YYYY-MM-DD');
    loadOverviewData();
  }
};

// 方法：禁用未来日期
const disabledDate = (current: Dayjs) => {
  return current && current > dayjs().endOf('day');
};

// 方法：格式化日期范围显示
const formatDateRange = (start: string, stop: string) => {
  if (start === stop) {
    return start;
  }
  return `${start} ~ ${stop}`;
};


// 方法：加载Overview数据
const loadOverviewData = async () => {
  try {
    // 并行加载所有Overview数据
    const promises = [
      loadOverview(),
      isAdmin.value ? loadUsersData() : Promise.resolve(),
      loadCampaignTagsData(),
      loadOffersData(),
      loadAdAccountsData(),
      loadCampaignsData(),
      loadAdsetsData(),
    ];

    await Promise.all(promises);
  } catch (error) {
    console.error('Load overview data error:', error);
    message.error(t('dashboard.loadDataError'));
  }
};

// 方法：加载总览数据
const loadOverview = async () => {
  overviewLoading.value = true;
  try {
    const response = await getOverview(currentDateRange);
    if (response.success) {
      Object.assign(overviewData, response.data);
    }
  } catch (error) {
    console.error('Load overview error:', error);
  } finally {
    overviewLoading.value = false;
  }
};

// 方法：加载用户数据
const loadUsersData = async () => {
  if (!isAdmin.value) return;

  usersLoading.value = true;
  try {
    const response = await getOverviewUsers(currentDateRange);
    if (response.success) {
      const { users, other } = response.data;
      usersData.value = [...users, other];
    }
  } catch (error) {
    console.error('Load users data error:', error);
  } finally {
    usersLoading.value = false;
  }
};

// 方法：加载Campaign Tags数据
const loadCampaignTagsData = async () => {
  campaignTagsLoading.value = true;
  try {
    const response = await getOverviewCampaignTags(currentDateRange);
    if (response.success) {
      campaignTagsData.value = response.data.tags || [];
      campaignTagsPagination.total = campaignTagsData.value.length;
      campaignTagsPagination.current = 1;
    }
  } catch (error) {
    console.error('Load campaign tags data error:', error);
  } finally {
    campaignTagsLoading.value = false;
  }
};

// 方法：加载Offers数据
const loadOffersData = async () => {
  offersLoading.value = true;
  try {
    const response = await getOverviewOffers(currentDateRange);
    if (response.success) {
      offersData.value = response.data.offers || [];
      offersPagination.total = offersData.value.length;
      offersPagination.current = 1;
    }
  } catch (error) {
    console.error('Load offers data error:', error);
  } finally {
    offersLoading.value = false;
  }
};

// 方法：加载Ad Accounts数据
const loadAdAccountsData = async () => {
  adAccountsLoading.value = true;
  try {
    const response = await getOverviewAdAccounts(currentDateRange);
    if (response.success) {
      adAccountsData.value = response.data.accounts;
      adAccountsPagination.total = adAccountsData.value.length;
      adAccountsPagination.current = 1;
    }
  } catch (error) {
    console.error('Load ad accounts data error:', error);
  } finally {
    adAccountsLoading.value = false;
  }
};

// 方法：加载Campaigns数据
const loadCampaignsData = async () => {
  campaignsLoading.value = true;
  try {
    const response = await getOverviewCampaigns(currentDateRange);
    if (response.success) {
      campaignsData.value = response.data.campaigns;
      campaignsPagination.total = campaignsData.value.length;
      campaignsPagination.current = 1;
    }
  } catch (error) {
    console.error('Load campaigns data error:', error);
  } finally {
    campaignsLoading.value = false;
  }
};

// 方法：加载Adsets数据
const loadAdsetsData = async () => {
  adsetsLoading.value = true;
  try {
    const response = await getOverviewAdsets(currentDateRange);
    if (response.success) {
      adsetsData.value = response.data.adsets;
      adsetsPagination.total = adsetsData.value.length;
      adsetsPagination.current = 1;
    }
  } catch (error) {
    console.error('Load adsets data error:', error);
  } finally {
    adsetsLoading.value = false;
  }
};

// 方法：加载Trends数据
const loadTrendsData = async () => {
  trendsLoading.value = true;
  try {
    const response = await getTrends(currentDateRange);
    if (response.success) {
      trendsData.value = response.data.trends;
      trendsPagination.total = trendsData.value.length;
      trendsPagination.current = 1;

      // 初始化图表
      if (trendsData.value.length > 0) {
        await nextTick();
        initChart();
      }
    }
  } catch (error) {
    console.error('Load trends data error:', error);
    message.error(t('dashboard.loadTrendsError'));
  } finally {
    trendsLoading.value = false;
  }
};

// 方法：初始化图表
const initChart = () => {
  if (!trendsChartRef.value || trendsData.value.length === 0) return;

  if (trendsChart) {
    trendsChart.dispose();
  }

  trendsChart = echarts.init(trendsChartRef.value);
  updateChart();
};

// 方法：更新图表
const updateChart = () => {
  if (!trendsChart || trendsData.value.length === 0) return;

  const dates = trendsData.value.map(item => item.date);
  const series: any[] = [];

  // 定义颜色主题
  const colors = ['#1890ff', '#52c41a', '#faad14', '#f5222d', '#722ed1', '#13c2c2', '#eb2f96', '#fa541c'];

  selectedMetrics.value.forEach((metric, index) => {
    const metricOption = metricsOptions.find(opt => opt.value === metric);
    if (!metricOption) return;

    const data = trendsData.value.map(item => {
      const value = (item as any)[metric];
      return metric === 'roi' ? (value * 100) : value;
    });

    series.push({
      name: metricOption.label,
      type: 'line',
      data,
      smooth: true,
      symbol: 'circle',
      symbolSize: 8,
      lineStyle: {
        width: 3,
        color: colors[index % colors.length],
      },
      itemStyle: {
        color: colors[index % colors.length],
        borderWidth: 2,
        borderColor: '#fff',
      },
      areaStyle: {
        color: {
          type: 'linear',
          x: 0,
          y: 0,
          x2: 0,
          y2: 1,
          colorStops: [{
            offset: 0,
            color: colors[index % colors.length] + '30',
          }, {
            offset: 1,
            color: colors[index % colors.length] + '08',
          }],
        },
      },
      emphasis: {
        focus: 'series',
        lineStyle: {
          width: 4,
        },
        itemStyle: {
          borderWidth: 3,
        },
      },
      animationDelay: index * 100,
    });
  });

  const option: EChartOption = {
    backgroundColor: '#ffffff',
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'cross',
        crossStyle: {
          color: '#999',
        },
      },
      backgroundColor: 'rgba(255, 255, 255, 0.95)',
      borderColor: '#e8e8e8',
      borderWidth: 1,
      textStyle: {
        color: '#666',
        fontSize: 13,
      },
      formatter: (params: any) => {
        let html = `<div style="font-weight: 600; margin-bottom: 8px; color: #262626;">${params[0].axisValue}</div>`;
        params.forEach((param: any) => {
          const value = param.seriesName === 'ROI'
            ? `${param.value.toFixed(2)}%`
            : param.seriesName.includes('Spend') || param.seriesName.includes('Revenue') || param.seriesName.includes('Profit')
              ? `$${param.value.toLocaleString()}`
              : param.value.toLocaleString();
          html += `<div style="margin: 4px 0;">${param.marker} <span style="font-weight: 500;">${param.seriesName}</span>: <span style="color: #262626; font-weight: 600;">${value}</span></div>`;
        });
        return html;
      },
    },
    legend: {
      data: selectedMetrics.value.map(metric => metricsOptions.find(opt => opt.value === metric)?.label || metric),
      top: 20,
      textStyle: {
        fontSize: 13,
        color: '#595959',
      },
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '8%',
      top: 60,
      containLabel: true,
    },
    xAxis: {
      type: 'category' as const,
      boundaryGap: false,
      data: dates,
      axisLine: {
        lineStyle: {
          color: '#d9d9d9',
        },
      },
      axisTick: {
        lineStyle: {
          color: '#d9d9d9',
        },
      },
      axisLabel: {
        color: '#8c8c8c',
        fontSize: 12,
      },
    },
    yAxis: {
      type: 'value' as const,
      axisLine: {
        show: false,
      },
      axisTick: {
        show: false,
      },
      splitLine: {
        lineStyle: {
          color: '#f0f0f0',
          type: 'dashed',
        },
      },
      axisLabel: {
        color: '#8c8c8c',
        fontSize: 12,
        formatter: (value: number) => {
          if (value >= 1000000) {
            return (value / 1000000).toFixed(1) + 'M';
          } else if (value >= 1000) {
            return (value / 1000).toFixed(1) + 'K';
          }
          return value.toString();
        },
      },
    },
    animation: true,
    animationDuration: 1000,
    animationEasing: 'cubicOut',
    series,
  };

  trendsChart.setOption(option, true);
};

// 表格变化处理函数
const handleCampaignTagsTableChange = (pagination: any) => {
  campaignTagsPagination.current = pagination.current;
  campaignTagsPagination.pageSize = pagination.pageSize;
};

const handleOffersTableChange = (pagination: any) => {
  offersPagination.current = pagination.current;
  offersPagination.pageSize = pagination.pageSize;
};

const handleAdAccountsTableChange = (pagination: any) => {
  adAccountsPagination.current = pagination.current;
  adAccountsPagination.pageSize = pagination.pageSize;
};

const handleCampaignsTableChange = (pagination: any) => {
  campaignsPagination.current = pagination.current;
  campaignsPagination.pageSize = pagination.pageSize;
};

const handleAdsetsTableChange = (pagination: any) => {
  adsetsPagination.current = pagination.current;
  adsetsPagination.pageSize = pagination.pageSize;
};

const handleTrendsTableChange = (pagination: any) => {
  trendsPagination.current = pagination.current;
  trendsPagination.pageSize = pagination.pageSize;
};

const handleUserTrendsTableChange = (pagination: any) => {
  userTrendsPagination.current = pagination.current;
  userTrendsPagination.pageSize = pagination.pageSize;
};

const handleOfferTrendsTableChange = (pagination: any) => {
  offerTrendsPagination.current = pagination.current;
  offerTrendsPagination.pageSize = pagination.pageSize;
};

// 方法：加载用户趋势数据
const loadUserTrendsData = async () => {
  if (!isAdmin.value) return;

  userTrendsLoading.value = true;
  try {
    const response = await getTrendsUsers(currentDateRange);
    if (response.success) {
      userTrendsData.value = response.data.trends;
      userTrendsPagination.total = flattenedUserTrendsData.value.length;
      userTrendsPagination.current = 1;
    }
  } catch (error) {
    console.error('Load user trends data error:', error);
    message.error(t('dashboard.loadTrendsError'));
  } finally {
    userTrendsLoading.value = false;
  }
};

// 方法：加载Offer趋势数据
const loadOfferTrendsData = async () => {
  offerTrendsLoading.value = true;
  try {
    const response = await getTrendsOffers(currentDateRange);
    if (response.success) {
      offerTrendsData.value = response.data.trends;
      offerTrendsPagination.total = flattenedOfferTrendsData.value.length;
      offerTrendsPagination.current = 1;
    }
  } catch (error) {
    console.error('Load offer trends data error:', error);
    message.error(t('dashboard.loadTrendsError'));
  } finally {
    offerTrendsLoading.value = false;
  }
};

// 生命周期：组件挂载
onMounted(() => {
  loadOverviewData();
});
</script>

<style scoped lang="less">
.dashboard-container {
  padding: 16px;

  .date-selector-container {
    margin-bottom: 48px;

    .date-controls {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;

      .date-label {
        font-weight: 500;
        color: rgba(0, 0, 0, 0.85);
      }

      .date-info {
        .current-range {
          color: rgba(0, 0, 0, 0.65);
          font-size: 14px;
          background-color: #f5f5f5;
          padding: 6px 12px;
          border-radius: 6px;
          font-weight: 500;
        }
      }
    }
  }

  .section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    color: rgba(0, 0, 0, 0.85);
  }

  .overview-section {
    margin-bottom: 32px;

    .overview-cards {
      margin-bottom: 24px;
    }

    .users-section,
    .campaign-tags-section,
    .offers-section,
    .ad-accounts-section,
    .campaigns-section,
    .adsets-section {
      margin-bottom: 24px;
    }
  }

  .trends-section {
    .trends-chart-section,
    .trends-table-section,
    .user-trends-section,
    .offer-trends-section {
      margin-bottom: 24px;
    }

    .chart-card {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      border-radius: 8px;
      overflow: hidden;
    }

    .chart-controls {
      margin-bottom: 20px;
      padding: 16px;
      background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
      border-radius: 6px;
      border: 1px solid #e8e8e8;

      .control-label {
        font-weight: 500;
        color: #595959;
      }
    }

    .chart-container {
      height: 450px;
      width: 100%;
      background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
      border-radius: 8px;
      position: relative;
      overflow: hidden;

      &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, #e8e8e8 50%, transparent 100%);
        z-index: 1;
      }
    }

    .empty-chart {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
      background-color: #fafafa;
      border-radius: 6px;
    }
  }
}

// 响应式设计
@media (max-width: 768px) {
  .dashboard-container {
    padding: 8px;

    .date-controls {
      flex-direction: column;
      align-items: flex-start !important;
    }
  }
}
</style>
