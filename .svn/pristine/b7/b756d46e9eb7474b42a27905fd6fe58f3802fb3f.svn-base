<template>
  <div class="create-rule-page">
    <div class="page-head">
      <a-breadcrumb>
        <a-breadcrumb-item>
          <a @click.prevent="goRuleManage">{{ t('规则管理') }}</a>
        </a-breadcrumb-item>
        <a-breadcrumb-item>
          <a @click.prevent="goCreateRule">{{ pageTitle }}</a>
        </a-breadcrumb-item>
      </a-breadcrumb>
    </div>

    <div class="wizard-layout">
      <div class="wizard-steps-nav">
        <div
          class="wizard-step-item"
          :class="{ active: currentStep === 1 }"
        >
          <span class="wizard-step-index">1</span>
          <span class="wizard-step-text">{{ t('选择规则类型') }}</span>
        </div>
        <div
          class="wizard-step-item"
          :class="{ active: currentStep === 2 }"
        >
          <span class="wizard-step-index">2</span>
          <span class="wizard-step-text">{{ t('设置规则') }}</span>
        </div>
      </div>

      <div class="wizard-main">
        <!-- 步骤1: 选择模板 -->
        <div v-show="currentStep === 1" class="step-content">
          <div class="step-header">
            <div class="step-title-wrap">
              <a-popconfirm
                :open="headerBackConfirmVisible"
                :title="t('当前填写内容将丢失，确认返回吗？')"
                :ok-text="t('确认')"
                :cancel-text="t('取消')"
                @confirm="confirmHeaderBack"
                @cancel="headerBackConfirmVisible = false"
              >
                <a-tooltip :title="t('返回上一级')">
                  <a-button
                    shape="circle"
                    class="back-icon-button"
                    @click="onHeaderBackClick"
                  >
                    <arrow-left-outlined />
                  </a-button>
                </a-tooltip>
              </a-popconfirm>
              <div class="step-title-block">
                <div class="step-title">{{ t('创建规则') }}</div>
              </div>
            </div>
          </div>

          <!-- 监控设置 -->
          <div class="monitoring-settings">
            <div class="setting-item">
              <label>{{ t('监控渠道') }}:</label>
              <a-select v-model:value="monitoringChannel" style="width: 150px">
                <a-select-option value="meta">Meta</a-select-option>
              </a-select>
            </div>
            <div class="setting-item">
              <label>{{ t('监控对象') }}:</label>
              <a-tabs v-model:activeKey="monitoringObject" type="card">
                <a-tab-pane key="ad-account" :tab="t('广告账号')" />
                <a-tab-pane key="campaign" :tab="t('广告系列')" />
                <a-tab-pane key="ad-group" :tab="t('广告组')" />
                <a-tab-pane key="ad" :tab="t('广告')" />
                <a-tab-pane key="ad-material" :tab="t('广告+素材')" />
                <a-tab-pane key="material" :tab="t('素材')" />
              </a-tabs>
            </div>
          </div>

          <div v-if="!isEditMode" class="template-hint">{{ t('请选择下方模板或直接创建规则:') }}</div>

          <!-- 规则模板卡片 -->
          <div v-if="!isEditMode" class="rule-templates">
            <div
              v-for="template in ruleTemplates"
              :key="template.id"
              class="template-card"
              @click="selectTemplate(template.id)"
            >
              <div class="template-icon" :style="{ background: template.color }">
                <component :is="template.icon" />
              </div>
              <div class="template-title">{{ template.title }}</div>
              <div v-if="template.description" class="template-desc">{{ template.description }}</div>
            </div>
          </div>
        </div>

        <!-- 步骤2: 配置规则 -->
        <div v-show="currentStep === 2" class="step-content">
          <div class="step-header">
            <div class="step-title-wrap">
              <a-popconfirm
                :open="headerBackConfirmVisible"
                :title="t('当前填写内容将丢失，确认返回吗？')"
                :ok-text="t('确认')"
                :cancel-text="t('取消')"
                @confirm="confirmHeaderBack"
                @cancel="headerBackConfirmVisible = false"
              >
                <a-tooltip :title="t('返回上一级')">
                  <a-button
                    shape="circle"
                    class="back-icon-button"
                    @click="onHeaderBackClick"
                  >
                    <arrow-left-outlined />
                  </a-button>
                </a-tooltip>
              </a-popconfirm>
              <div class="step-title-block">
                <div class="step-title">
                  {{ pageTitle }}
                  <span class="step-title-type" v-if="selectedTemplateTitle">
                    {{ t('规则类型') }}：{{ selectedTemplateTitle }}
                  </span>
                </div>
                <div class="step-subtitle">
                  {{ t('因网络延迟等客观因素,以下条件判断可能无法100%准确，仅供辅助参考,请注意广告账户的安全使用,避免账户被盗') }}
                </div>
              </div>
            </div>
          </div>

          <!-- 筛选 + 指标过滤（广告防盗刷模板不使用本区域） -->
          <div v-if="!isAntiFraudTemplate" class="rule-section">
            <div class="section-title">
              <filter-outlined />
              {{ t('筛选') }}
            </div>
            <div class="filter-row">
              <a-select
                v-model:value="ruleConfig.filters.adGroup"
                :placeholder="t('广告组: 请选择')"
                style="width: 200px"
                allow-clear
              >
                <a-select-option v-for="ag in adGroupOptions" :key="ag.id" :value="ag.id">
                  {{ ag.name }}
                </a-select-option>
              </a-select>
              <a-select
                v-model:value="ruleConfig.filters.campaign"
                :placeholder="t('广告系列: 请选择')"
                style="width: 200px"
                allow-clear
              >
                <a-select-option v-for="camp in campaignOptions" :key="camp.id" :value="camp.id">
                  {{ camp.name }}
                </a-select-option>
              </a-select>
              <a-select
                v-model:value="ruleConfig.filters.status"
                :placeholder="t('状态: 请选择')"
                style="width: 150px"
                allow-clear
              >
                <a-select-option value="enabled">{{ t('已开启') }}</a-select-option>
                <a-select-option value="disabled">{{ t('已关闭') }}</a-select-option>
              </a-select>
            </div>

            <!-- 指标过滤 -->
            <div class="metric-filter">
              <div class="metric-title">
                {{ t('指标过滤') }}
                <a-tooltip>
                  <template #title>
                    {{ t('因三方数据指标的延迟较长,若选择监控三方指标,建议选择2天及以前的数据时间') }}
                  </template>
                  <info-circle-outlined style="margin-left: 8px; color: #1890ff" />
                </a-tooltip>
              </div>
              <a-alert
                type="info"
                :message="t('因三方数据指标的延迟较长,若选择监控三方指标,建议选择2天及以前的数据时间')"
                show-icon
                style="margin-bottom: 16px"
              />
              <div
                v-for="(condition, index) in ruleConfig.metricConditions"
                :key="index"
                class="metric-condition"
              >
                <div class="condition-number">{{ index + 1 }}</div>
                <a-select
                  v-model:value="condition.metric"
                  style="width: 350px"
                  :options="metricOptions"
                />
                <a-select
                  v-model:value="condition.date"
                  :placeholder="t('今天')"
                  style="width: 150px"
                >
                  <a-select-option
                    v-for="date in dateOptions"
                    :key="date.value"
                    :value="date.value"
                  >
                    {{ date.label }}
                  </a-select-option>
                </a-select>
                <a-select
                  v-model:value="condition.operator"
                  style="width: 80px"
                  :options="operatorOptions"
                />
                <a-select
                  v-model:value="condition.valueType"
                  style="width: 100px"
                  :options="getValueTypeOptions(condition.metric)"
                />
                <!-- 单值：>,<,>=,<=,=；范围：介于/不介于 -->
                <template
                  v-if="condition.operator === 'between' || condition.operator === 'not_between'"
                >
                  <a-input-number
                    v-model:value="condition.valueMin"
                    :placeholder="'最小值'"
                    style="width: 120px"
                  />
                  <span style="margin: 0 6px; color: #666">~</span>
                  <a-input-number
                    v-model:value="condition.valueMax"
                    :placeholder="'最大值'"
                    style="width: 120px"
                  />
                </template>
                <a-input-number
                  v-else
                  v-model:value="condition.value"
                  :placeholder="t('数值')"
                  style="width: 120px"
                />
                <a-select
                  v-if="condition.valueType === 'number'"
                  v-model:value="condition.valueUnit"
                  style="width: 80px"
                  :options="valueUnitOptions"
                />
                <a-select
                  v-else-if="condition.valueType === 'spend'"
                  v-model:value="condition.currency"
                  style="width: 80px"
                  :options="spendCurrencyOptions"
                />
                <a-select
                  v-else
                  v-model:value="condition.currency"
                  style="width: 80px"
                  :options="currencyOptions"
                />
                <a-button
                  type="link"
                  danger
                  @click="removeCondition(index)"
                  style="margin-left: 8px"
                >
                  {{ t('删除') }}
                </a-button>
              </div>
              <a-button type="link" @click="addCondition" style="padding: 0">
                {{ t('添加条件') }}
              </a-button>
            </div>
          </div>

          <!-- 广告防盗刷：专用条件（与参考产品一致，不走筛选/指标过滤） -->
          <div v-else class="rule-section anti-fraud-template-section">
            <div class="section-title">
              <safety-outlined />
              {{ t('防盗刷条件') }}
            </div>
            <anti-fraud-rule-section v-model="ruleConfig.antiFraud" />
          </div>

          <!-- 执行动作 -->
          <div class="rule-section">
            <div class="section-title">
              <play-circle-outlined />
              {{ t('执行动作') }}
              <a-tooltip>
                <template #title>{{ t('依次执行以下动作') }}</template>
                <info-circle-outlined style="margin-left: 8px; color: #1890ff" />
              </a-tooltip>
            </div>
            <a-checkbox v-model:checked="ruleConfig.executeInOrder">
              {{ t('依次执行以下动作') }}
            </a-checkbox>
            <div class="action-list">
              <div
                v-for="(action, index) in ruleConfig.actions"
                :key="index"
                class="action-card"
              >
                <div class="action-card-number">{{ index + 1 }}</div>
                <a-button
                  type="link"
                  danger
                  class="action-card-delete"
                  @click="removeAction(index)"
                >
                  {{ t('删除') }}
                </a-button>

                <div class="action-card-content">
                  <action-config
                    :key="`action-${index}-${isEditMode ? props.editData?.id : 'new'}`"
                    :action="action"
                    @update="updateAction(index, $event)"
                  />
                </div>
              </div>
            </div>

            <a-button type="link" class="add-action-link" @click="addAction">
              <plus-outlined />
              {{ t('添加动作') }}
            </a-button>
          </div>

          <!-- 执行频次 -->
          <div class="rule-section">
            <div class="section-title">
              <clock-circle-outlined />
              {{ t('执行频次') }}
            </div>
            <div class="frequency-setting">
              <span>{{ t('若监控对象在多次检查中均满足条件,那么距离上次执行动作的时间间隔是') }}</span>
              <a-select v-model:value="ruleConfig.executionInterval" style="width: 100px; margin-left: 8px">
                <a-select-option value="24h">24h</a-select-option>
                <a-select-option value="12h">12h</a-select-option>
                <a-select-option value="6h">6h</a-select-option>
              </a-select>
            </div>
            <div class="check-time-setting">
              <div class="setting-row">
                <label>{{ t('使用时区') }}:</label>
                <a-select v-model:value="ruleConfig.timezone" style="width: 150px">
                  <a-select-option value="UTC+8">UTC+8</a-select-option>
                  <a-select-option value="UTC+0">UTC+0</a-select-option>
                </a-select>
                <span class="help-text">
                  {{ t('请选择广告账户对应的时区,以确保数据与媒体后台保持一致') }}
                </span>
              </div>
              <div class="setting-row flex-wrap">
                <label>{{ t('生效时段') }}:</label>
                <a-radio-group v-model:value="ruleConfig.effectivePeriod" button-style="solid">
                  <a-radio-button value="long-term">{{ t('长期') }}</a-radio-button>
                  <a-radio-button value="custom">{{ t('自定义') }}</a-radio-button>
                </a-radio-group>
              </div>
              <div class="setting-row flex-wrap">
                <label>{{ t('检查频次') }}:</label>
                <a-radio-group v-model:value="ruleConfig.checkFrequency" button-style="solid">
                  <a-radio-button value="15min">{{ t('每15分钟') }}</a-radio-button>
                  <a-radio-button value="30min">{{ t('每半小时') }}</a-radio-button>
                  <a-radio-button value="1h">{{ t('每小时') }}</a-radio-button>
                  <a-radio-button value="daily">{{ t('每天') }}</a-radio-button>
                  <a-radio-button value="weekly">{{ t('每周') }}</a-radio-button>
                  <a-radio-button value="custom">{{ t('自定义') }}</a-radio-button>
                </a-radio-group>
              </div>
              <div v-if="ruleConfig.checkFrequency === 'custom'" class="custom-time-slots">
                <div
                  v-for="(time, index) in ruleConfig.customCheckTimes"
                  :key="index"
                  class="time-slot-row"
                >
                  <a-date-picker
                    v-model:value="ruleConfig.customCheckTimes[index]"
                    format="YYYY-MM-DD HH:mm"
                    show-time
                    :default-value="dayjs().startOf('day')"
                    placeholder="选择日期和时间"
                    style="width: 180px"
                    :disabled-date="(current: dayjs.Dayjs) => current && current < dayjs().startOf('day')"
                  />
                  <a-button
                    v-if="ruleConfig.customCheckTimes.length > 1"
                    type="text"
                    danger
                    @click="removeTimeSlot(index)"
                  >
                    <close-outlined />
                  </a-button>
                </div>
                <a-button
                  v-if="ruleConfig.customCheckTimes.length < 10"
                  type="link"
                  @click="addTimeSlot"
                  class="add-time-slot-btn"
                >
                  <plus-outlined />
                  {{ t('新增') }}
                </a-button>
              </div>
            </div>
          </div>

          <!-- 基础信息 -->
          <div class="rule-section">
            <div class="section-title">
              <file-text-outlined />
              {{ t('基础信息') }}
            </div>
            <a-form :model="ruleConfig.basicInfo" layout="vertical">
              <a-form-item :label="t('规则名称')">
                <a-input
                  v-model:value="ruleConfig.basicInfo.ruleName"
                  :placeholder="t('请输入规则名称')"
                />
              </a-form-item>
              <a-form-item :label="t('规则描述')">
                <a-textarea
                  v-model:value="ruleConfig.basicInfo.description"
                  :placeholder="t('请输入规则描述')"
                  :rows="3"
                />
              </a-form-item>
              <a-form-item :label="t('规则状态')">
                <a-radio-group v-model:value="ruleConfig.basicInfo.status" button-style="solid">
                  <a-radio-button value="enabled">{{ t('启用') }}</a-radio-button>
                  <a-radio-button value="disabled">{{ t('停用') }}</a-radio-button>
                </a-radio-group>
              </a-form-item>
              <a-form-item :label="t('货币单位')">
                <a-radio-group v-model:value="ruleConfig.basicInfo.currency" button-style="solid">
                  <a-radio-button value="USD">{{ t('美元') }}</a-radio-button>
                  <a-radio-button value="CNY">{{ t('人民币') }}</a-radio-button>
                </a-radio-group>
              </a-form-item>
            </a-form>
          </div>
        </div>
      </div>
    </div>

    <div v-if="currentStep === 2" class="footer-actions">
      <a-button type="primary" size="large" :loading="submitLoading" @click="handleSubmit">{{ t('提交') }}</a-button>
      <a-button size="large" @click="onCancelClick">{{ t('取消') }}</a-button>
    </div>
    <a-modal v-model:open="cancelConfirmVisible" :title="t('提示')" :ok-text="t('确认')" :cancel-text="t('继续编辑')" @ok="confirmCancelBack">
      <p>{{ t('当前填写内容将丢失，确认取消吗？') }}</p>
    </a-modal>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import {
  ArrowLeftOutlined,
  PlusOutlined,
  CloseOutlined,
  BellOutlined,
  StopOutlined,
  SafetyOutlined,
  ReloadOutlined,
  RiseOutlined,
  FallOutlined,
  FileAddOutlined,
  MinusOutlined,
  TagOutlined,
  FilterOutlined,
  PlayCircleOutlined,
  ClockCircleOutlined,
  FileTextOutlined,
  InfoCircleOutlined,
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import ActionConfig from './components/action-config.vue';
import AntiFraudRuleSection from './components/anti-fraud-rule-section.vue';
import { getRuleTemplates, createRule, updateRule } from '@/api/promotion/index';
import metricFilterOptionsRaw from './metric-filter-options.json';

const { t } = useI18n();
const emit = defineEmits<{
  (e: 'close'): void;
}>();

const props = defineProps<{
  editData?: any;
}>();

const isEditMode = computed(() => !!props.editData);
const pageTitle = computed(() => isEditMode.value ? t('编辑规则') : t('创建规则'));

const currentStep = ref(1);
const monitoringChannel = ref('meta');
const monitoringObject = ref('ad-account');
const selectedTemplate = ref<string | null>(null);

/** 与后端 RuleTemplatesController 中「广告防盗刷」模板 id 一致 */
const ANTI_FRAUD_TEMPLATE_ID = 'anti-fraud';
const isAntiFraudTemplate = computed(() => selectedTemplate.value === ANTI_FRAUD_TEMPLATE_ID);

const selectedTemplateTitle = computed(() => {
  if (!selectedTemplate.value) return '';
  return ruleTemplates.value.find((tpl) => tpl.id === selectedTemplate.value)?.title ?? t('直接创建');
});
const agreedToDisclaimer = ref(false);
const headerBackConfirmVisible = ref(false);
const cancelConfirmVisible = ref(false);
const submitLoading = ref(false);

type RuleTemplateApi = {
  id: string;
  title: string;
  description: string;
  icon: string;
  color: string;
};

type RuleTemplateCard = {
  id: string;
  title: string;
  description: string;
  icon: any; // @ant-design/icons-vue 图标组件
  color: string;
};

type ValueTypeRule = {
  includes: string[];
  excludes?: string[];
  options: string[];
};

type DateOption = { label: string; value: string };
type OperatorOption = { label: string; value: string };

type MetricFilterOptionsConfig = {
  dateOptions: DateOption[];
  operatorOptions: OperatorOption[];
  valueTypeDefaults: string[];
  valueTypeRules: ValueTypeRule[];
  currencyOptions: string[];
  metrics: string[];
};

const iconComponentByName: Record<string, any> = {
  BellOutlined,
  StopOutlined,
  SafetyOutlined,
  ReloadOutlined,
  RiseOutlined,
  FallOutlined,
  FileAddOutlined,
  MinusOutlined,
  TagOutlined,
  PlusOutlined,
};

const ruleTemplates = ref<RuleTemplateCard[]>([]);
const loadingRuleTemplates = ref(false);

/** 广告系列 mock 数据 */
const campaignOptions = ref([
  { id: 'camp_001', name: '夏季促销系列' },
  { id: 'camp_002', name: '新品推广系列' },
  { id: 'camp_003', name: '品牌曝光系列' },
  { id: 'camp_004', name: '周年庆系列' },
  { id: 'camp_005', name: '节日特惠系列' },
]);

/** 广告组 mock 数据 */
const adGroupOptions = ref([
  { id: 'ag_001', name: '北美地区-英语' },
  { id: 'ag_002', name: '欧洲地区-英语' },
  { id: 'ag_003', name: '东南亚-中文' },
  { id: 'ag_004', name: '日本-日语' },
  { id: 'ag_005', name: '韩国-韩语' },
]);

const loadRuleTemplates = async () => {
  loadingRuleTemplates.value = true;
  try {
    const apiTemplates = await getRuleTemplates(monitoringObject.value);
    ruleTemplates.value = (apiTemplates ?? []).map((tpl: RuleTemplateApi) => ({
      id: tpl.id,
      title: tpl.title,
      description: tpl.description,
      color: tpl.color,
      icon: iconComponentByName[tpl.icon] ?? iconComponentByName.PlusOutlined,
    }));
  } finally {
    loadingRuleTemplates.value = false;
  }
};

const metricFilterOptionsConfig = metricFilterOptionsRaw as MetricFilterOptionsConfig;

const metricOptions = metricFilterOptionsConfig.metrics.map((m) => ({
  label: m.label,
  value: m.value,
}));

const DEFAULT_METRIC = metricOptions[0]?.value ?? 'bid';

// 指标映射：后端中文值 -> 前端英文值
const metricChineseToEnglish: Record<string, string> = {
  '出价': 'bid',
  '展示数': 'impressions',
  '千次展示成本': 'cpm',
  '点击数': 'clicks',
  '花费': 'spend',
  '点击率': 'ctr',
  '点击安装率': 'click_to_install',
  '链接点击量': 'outbound_clicks',
  '单次链接点击费用': 'cost_per_outbound_click',
  '链接点击率': 'outbound_ctr',
  '出站点击率': 'outbound_clicks_ctr',
  '购物': 'purchases',
  '网站购物': 'website_purchases',
  '单次购物费用': 'cost_per_purchase',
  '购物转化价值': 'purchase_value',
  '网站购物转化价值': 'website_purchase_value',
  'ROAS - 购物': 'roas_purchases',
  'ROAS - 网站购物': 'roas_website_purchases',
  '完成注册': 'lead',
  '单次完成注册费用': 'cost_per_lead',
  '开始试用': 'trial',
  '单次开始试用费用': 'cost_per_trial',
  '内容查看': 'view_content',
  '单次内容查看费用': 'cost_per_view_content',
  '加入购物车': 'add_to_cart',
  '单次加入购物车费用': 'cost_per_add_to_cart',
  '发起结账': 'initiate_checkout',
  '单次发起结账费用': 'cost_per_initiate_checkout',
  '添加支付信息': 'add_payment_info',
  '单次添加支付信息费用': 'cost_per_add_payment_info',
  '潜在客户': 'custom',
  '网站潜在客户': 'website_custom',
  'Meta 站内潜在客户': 'meta_inbox_custom',
  '单条线索费用': 'cost_per_custom',
  '单条网站线索费用': 'cost_per_website_custom',
  '单条Meta 站内线索费用': 'cost_per_meta_inbox_custom',
  '订阅': 'subscribe',
  '单次订阅费用': 'cost_per_subscribe',
  '加入心愿单': 'add_to_wishlist',
  '单次加入心愿单费用': 'cost_per_add_to_wishlist',
  '网站订阅转化价值': 'website_subscribe_value',
  '移动应用订阅转化价值': 'mobile_app_subscribe_value',
  '订阅转化价值': 'subscribe_value',
  '提交申请': 'submit_application',
  '单次提交申请费用': 'cost_per_submit_application',
  '捐款': 'donate',
  '单次捐款费用': 'cost_per_donate',
  '搜索': 'search',
  '单次搜索费用': 'cost_per_search',
  '定制商品': 'customize_product',
  '单次定制商品费用': 'cost_per_customize_product',
  '网点搜索': 'find_location',
  '单次网点搜索费用': 'cost_per_find_location',
  '联系': 'contact',
  '单次联系费用': 'cost_per_contact',
  '安排预约': 'schedule',
  '单次安排预约费用': 'cost_per_schedule',
  '视频播放量': 'video_views',
  '持续播放视频达2秒的次数': 'video_2_sec_watched',
  '播放视频达 3 秒的次数': 'video_3_sec_watched',
  '公共主页互动': 'page_engagement',
  '公共主页获赞数': 'page_likes',
  '帖子评论': 'post_comment',
  '帖文互动': 'post_engagement',
  '帖子心情': 'post_reaction',
  '帖子收藏': 'post_save',
  '帖子分享': 'post_share',
  '单次帖文互动费用': 'cost_per_post_engagement',
  '新增消息联系人数量': 'new_messaging_connection',
  '每位新增消息联系人费用': 'cost_per_new_messaging_connection',
  '拉黑消息联系人数量': 'messaging_blocked',
  '消息对话发起次数': 'messaging_conversations_started',
  '单次发起消息对话费用': 'cost_per_messaging_conversation_started',
  '消息订阅': 'messaging_subscribe',
  '单次消息订阅费用': 'cost_per_messaging_subscribe',
};

// 指标映射：前端英文值 -> 中文显示标签（用于值类型匹配）
const metricEnglishToLabel: Record<string, string> = Object.fromEntries(
  metricFilterOptionsConfig.metrics.map((m) => [m.value, m.label])
);

// 值类型映射：中文标签 -> 英文值
const valueTypeChineseToEnglish: Record<string, string> = {
  '花费': 'spend',
  '百分比': 'percentage',
  '数值': 'number',
  '点击率': 'ctr',
  '单次点击费用': 'cpc',
  '广告支出回报率': 'roas',
};

// 值类型映射：英文值 -> 中文标签（用于显示）
const valueTypeEnglishToChinese: Record<string, string> = Object.fromEntries(
  Object.entries(valueTypeChineseToEnglish).map(([cn, en]) => [en, cn])
);

onMounted(() => {
  loadRuleTemplates();
  if (isEditMode.value && props.editData) {
    // 编辑模式：加载已有数据
    const data = props.editData;
    monitoringChannel.value = data.channel || 'meta';
    monitoringObject.value = data.monitoring_object || 'ad-account';
    selectedTemplate.value = data.template_id || null;

    // 使用 Object.assign 替换整个对象以保持响应式
    Object.assign(ruleConfig.value, {
      filters: {
        adGroup: data.filters?.ad_group_ids?.[0]?.toString() || data.filters?.adgroup_ids?.[0]?.toString() || undefined,
        campaign: data.filters?.campaign_ids?.[0]?.toString() || undefined,
        status: data.filters?.status?.[0]?.toLowerCase(),
      },
      metricConditions: (data.metric_conditions?.conditions || []).map((c: any) => ({
        metric: c.metric || 'spend',
        date: c.date_preset || 'today',
        operator: c.operator || '>',
        valueType: valueTypeChineseToEnglish[c.value_type] || c.value_type || 'number',
        value: c.value,
        valueMin: c.value_min,
        valueMax: c.value_max,
        valueUnit: '个',
        currency: 'USD',
      })),
      actions: (data.actions?.actions || []).map((a: any) => ({
        type: a.type,
        enabled: a.enabled,
        config: a.config || {},
      })),
      executeInOrder: !!data.execute_in_order,
      executionInterval: data.execution_interval || '24h',
      timezone: data.timezone || 'Asia/Shanghai',
      effectivePeriod: data.effective_period || 'long-term',
      checkFrequency: data.check_frequency || '1h',
      customCheckTimes: [dayjs().startOf('day')],
      basicInfo: {
        ruleName: data.name || '',
        description: data.description || '',
        status: data.status === 1 ? 'enabled' : 'disabled',
        currency: data.currency || 'USD',
      },
      antiFraud: data.anti_fraud_config || { enabled: false },
    });
    currentStep.value = 2; // 编辑模式直接跳到步骤2
  }
});

const createDefaultRuleConfig = () => ({
  filters: {
    adGroup: undefined,
    campaign: undefined,
    status: undefined,
  },
  metricConditions: [
    {
      metric: DEFAULT_METRIC,
      date: 'today',
      operator: '>',
      valueType: 'number',
      value: 50,
      valueMin: undefined,
      valueMax: undefined,
      valueUnit: '个',
      currency: 'USD',
    },
  ],
  actions: [
    {
      type: 'send-email',
      config: {
        recipients: [],
        subject: '',
        body: '',
      },
    },
  ],
  executeInOrder: true,
  executionInterval: '24h',
  timezone: 'UTC+8',
  effectivePeriod: 'long-term',
  checkFrequency: '1h',
  customCheckTimes: [dayjs().startOf('day')] as dayjs.Dayjs[],
  basicInfo: {
    ruleName: '',
    description: '',
    status: 'enabled',
    currency: 'USD',
  },
  antiFraud: {
    accountScope: 'all' as const,
    specifiedAccountIds: [] as string[],
    conditionMatch: 'all' as const,
    notCreatedInAdsPolar: false,
    nonWorkingHours: false,
    nonWorkingHourSlotIds: [] as string[],
  },
});

// 表单数据：跨步骤切换不丢；切换规则模板或监控对象时会重置
const ruleConfig = ref(createDefaultRuleConfig());

watch(monitoringObject, () => {
  loadRuleTemplates();
  // 监控对象切换后模板列表变化，清空「设置规则」与已选模板，避免沿用上一对象下的配置
  ruleConfig.value = createDefaultRuleConfig();
  selectedTemplate.value = null;
  agreedToDisclaimer.value = false;
});

const dateOptions = metricFilterOptionsConfig.dateOptions;

const operatorOptions = metricFilterOptionsConfig.operatorOptions.map((op) => ({
  label: op.label === '介于' || op.label === '不介于' ? t(op.label) : op.label,
  value: op.value,
}));

const toSelectOptions = (values: string[]) => values.map((value) => ({ label: t(value), value }));

const valueTypeDefaultOptions = toSelectOptions(metricFilterOptionsConfig.valueTypeDefaults);

const getValueTypeValuesByMetric = (metric: string) => {
  if (!metric) return metricFilterOptionsConfig.valueTypeDefaults;
  // 使用中文标签进行匹配（valueTypeRules 使用中文关键词）
  const chineseLabel = metricEnglishToLabel[metric] || metric;
  const matchedRule = metricFilterOptionsConfig.valueTypeRules.find((rule) => {
    const includeOk = rule.includes.some((keyword) => chineseLabel.includes(keyword));
    if (!includeOk) return false;
    const excludeOk = (rule.excludes ?? []).every((keyword) => !chineseLabel.includes(keyword));
    return excludeOk;
  });
  // 返回英文值而非中文标签
  const chineseOptions = matchedRule?.options ?? metricFilterOptionsConfig.valueTypeDefaults;
  return chineseOptions.map((opt) => valueTypeChineseToEnglish[opt] || opt);
};

const getValueTypeOptions = (metric: string) => {
  const values = getValueTypeValuesByMetric(metric);
  if (values.length) {
    return values.map((v) => ({ label: valueTypeEnglishToChinese[v] || v, value: v }));
  }
  return valueTypeDefaultOptions;
};

const currencyOptions = metricFilterOptionsConfig.currencyOptions.map((value) => ({
  label: value,
  value,
}));

const normalizeConditionValueType = (condition: { metric: string; valueType: string }) => {
  const availableValueTypes = getValueTypeValuesByMetric(condition.metric);
  if (!availableValueTypes.includes(condition.valueType)) {
    condition.valueType = availableValueTypes[0] ?? 'number';
  }
};

const valueUnitOptions = [
  { label: '个', value: '个' },
  { label: '次', value: '次' },
  { label: '%', value: '%' },
];

const spendCurrencyOptions = [
  { label: 'USD', value: 'USD' },
];

const normalizeConditionUnits = (condition: { valueType: string; valueUnit?: string; currency?: string }) => {
  if (condition.valueType === 'number') {
    const allowed = valueUnitOptions.map((o) => o.value);
    if (!condition.valueUnit || !allowed.includes(condition.valueUnit)) {
      condition.valueUnit = allowed[0] ?? '个';
    }
  }

  if (condition.valueType === 'spend') {
    condition.currency = 'USD';
  }
};

const normalizeConditionOperatorValues = (condition: {
  operator: string;
  value?: number;
  valueMin?: number;
  valueMax?: number;
}) => {
  const isRange = condition.operator === 'between' || condition.operator === 'not_between';

  if (isRange) {
    // 从单值切换过来时，尽可能把旧值迁移到 valueMin。
    if (condition.valueMin === undefined && condition.value !== undefined) {
      condition.valueMin = condition.value;
    }
  } else {
    // 非范围时，隐藏字段不参与提交，清掉避免残留脏值。
    if (condition.value === undefined && condition.valueMin !== undefined) {
      condition.value = condition.valueMin;
    }
    condition.valueMin = undefined;
    condition.valueMax = undefined;
  }
};

watch(
  () => ruleConfig.value.metricConditions.map((condition) => condition.metric),
  () => {
    ruleConfig.value.metricConditions.forEach((condition) => {
      normalizeConditionValueType(condition);
      normalizeConditionUnits(condition);
    });
  },
  { immediate: true },
);

watch(
  () => ruleConfig.value.metricConditions.map((condition) => condition.valueType),
  () => {
    ruleConfig.value.metricConditions.forEach((condition) => normalizeConditionUnits(condition));
  },
  { immediate: true },
);

watch(
  () => ruleConfig.value.metricConditions.map((condition) => condition.operator),
  () => {
    ruleConfig.value.metricConditions.forEach((condition) =>
      normalizeConditionOperatorValues(condition),
    );
  },
  { immediate: true },
);

const selectTemplate = (templateId: string) => {
  const previousTemplateId = selectedTemplate.value;
  selectedTemplate.value = templateId;

  // 切换规则类型（含首次从「未选」点到某一模板）时重置「设置规则」表单
  if (previousTemplateId !== templateId) {
    ruleConfig.value = createDefaultRuleConfig();
    agreedToDisclaimer.value = false;
  }

  currentStep.value = 2;
};

const switchStep = (step: number) => {
  currentStep.value = step === 1 ? 1 : 2;
};

const addCondition = () => {
  const defaultValueType = getValueTypeValuesByMetric(DEFAULT_METRIC)[0] ?? 'number';
  ruleConfig.value.metricConditions.push({
    metric: DEFAULT_METRIC,
    date: 'today',
    operator: '>',
    valueType: defaultValueType,
    value: undefined,
    valueMin: undefined,
    valueMax: undefined,
    valueUnit: '个',
    currency: 'USD',
  });
};

const removeCondition = (index: number) => {
  ruleConfig.value.metricConditions.splice(index, 1);
};

const addAction = () => {
  ruleConfig.value.actions.push({
    type: 'send-email',
    config: {
      recipients: [],
      subject: '',
      body: '',
    },
  });
};

const removeAction = (index: number) => {
  ruleConfig.value.actions.splice(index, 1);
};

const updateAction = (index: number, action: any) => {
  ruleConfig.value.actions[index] = action;
};

const addTimeSlot = () => {
  ruleConfig.value.customCheckTimes.push(dayjs().startOf('day'));
};

const removeTimeSlot = (index: number) => {
  ruleConfig.value.customCheckTimes.splice(index, 1);
};

const hasUnsavedChanges = () => {
  // 仅将“第2步设置规则”视为可丢失内容：
  // 第1步切换模板/监控对象不触发返回确认。
  return JSON.stringify(ruleConfig.value) !== JSON.stringify(createDefaultRuleConfig());
};

const goRuleManage = () => {
  emit('close');
};

const goCreateRule = () => {
  // 单页父子组件模式下，当前即为创建页，不需要路由跳转。
  return;
};

/** 离开创建页：由父组件切换列表，不走路由跳转 */
const navigateBackWithState = () => {
  emit('close');
};

const onHeaderBackClick = (e: Event) => {
  e.stopPropagation();
  // 编辑模式：直接返回列表页
  if (isEditMode.value) {
    navigateBackWithState();
    return;
  }
  // 第2步点击返回，仅回到”选择规则类型”步骤，不离开当前页面。
  if (currentStep.value === 2) {
    switchStep(1);
    return;
  }

  // 第1步点击返回，才离开创建页返回上一级。
  if (!hasUnsavedChanges()) {
    navigateBackWithState();
    return;
  }
  headerBackConfirmVisible.value = true;
};

const onCancelClick = (e: Event) => {
  e.stopPropagation();
  cancelConfirmVisible.value = true;
};

const confirmHeaderBack = () => {
  headerBackConfirmVisible.value = false;
  navigateBackWithState();
};

const confirmCancelBack = () => {
  cancelConfirmVisible.value = false;
  navigateBackWithState();
};

/** 构建提交数据 */
const buildSubmitData = () => {
  const config = ruleConfig.value;

  // 处理指标条件
  const conditions = config.metricConditions.map((cond) => ({
    metric: cond.metric,
    date_preset: cond.date,
    operator: cond.operator,
    value_type: cond.valueType,
    value: cond.operator === 'between' || cond.operator === 'not_between' ? undefined : cond.value,
    value_min: cond.operator === 'between' || cond.operator === 'not_between' ? cond.valueMin : undefined,
    value_max: cond.operator === 'between' || cond.operator === 'not_between' ? cond.valueMax : undefined,
  }));

  // 处理执行动作
  const actions = config.actions.map((action) => ({
    type: action.type,
    enabled: true,
    config: action.config || {},
  }));

  // 构建提交数据
  return {
    name: config.basicInfo.ruleName,
    description: config.basicInfo.description,
    channel: monitoringChannel.value,
    monitoring_object: monitoringObject.value,
    template_id: selectedTemplate.value || '',
    status: config.basicInfo.status === 'enabled' ? 1 : 0,
    currency: config.basicInfo.currency,
    timezone: config.timezone,
    effective_period: config.effectivePeriod,
    check_frequency: config.checkFrequency,
    execution_interval: config.executionInterval,
    execute_in_order: config.executeInOrder ? 1 : 0,
    filters: {
      status: config.filters.status ? [config.filters.status.toUpperCase()] : [],
      campaign_ids: config.filters.campaign ? [config.filters.campaign] : [],
      ad_group_ids: config.filters.adGroup ? [config.filters.adGroup] : [],
    },
    metric_conditions: {
      logic: 'and',
      conditions,
    },
    anti_fraud_config: config.antiFraud?.enabled ? config.antiFraud : { enabled: false },
    actions: {
      execute_in_order: config.executeInOrder,
      actions,
    },
  };
};

const handleSubmit = async () => {
  if (!ruleConfig.value.basicInfo.ruleName) {
    message.warning(t('请输入规则名称'));
    return;
  }

  submitLoading.value = true;
  try {
    const submitData = buildSubmitData();
    if (isEditMode.value) {
      await updateRule(props.editData.id, submitData);
      message.success(t('规则更新成功'));
    } else {
      await createRule(submitData);
      message.success(t('规则创建成功'));
    }
    emit('close');
  } catch (error) {
    console.error(isEditMode.value ? '更新规则失败:' : '创建规则失败:', error);
    message.error(t(isEditMode.value ? '更新失败，请重试' : '创建失败，请重试'));
  } finally {
    submitLoading.value = false;
  }
};

const handleConfirm = () => {
  if (!agreedToDisclaimer.value) {
    message.warning(t('请先阅读并同意免责声明'));
    return;
  }
  handleSubmit();
};

const handleSaveTemplate = () => {
  message.success(t('模板保存成功'));
};
</script>

<style lang="less" scoped>
.create-rule-page {
  .page-head {
    margin-bottom: 12px;
  }

  .wizard-layout {
    display: flex;
    gap: 24px;
    background: #fff;
    padding: 24px;
    border-radius: 4px;
  }

  .wizard-steps-nav {
    width: 200px;
    flex-shrink: 0;
    background: #fafafa;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    padding: 8px;
  }

  .wizard-step-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 12px;
    border-radius: 8px;
    cursor: pointer;
    color: #666;
    transition: all 0.2s;
    user-select: none;

    &:hover {
      background: #f0f7ff;
      color: #1890ff;
    }

    &.active {
      background: #e6f7ff;
      color: #1890ff;
      font-weight: 600;
    }
  }

  .wizard-step-index {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #d9d9d9;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
    transition: all 0.2s;
  }

  .wizard-step-item.active {
    .wizard-step-index {
      background: #1890ff;
      color: #fff;
    }
  }

  .wizard-main {
    flex: 1;
    min-width: 0;
  }

  .step-content {
    background: #fff;
    padding: 24px;
    border-radius: 4px;
    margin-bottom: 16px;

    .step-header {
      margin-bottom: 24px;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;

      .step-title-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .step-title-block {
        display: flex;
        flex-direction: column;
      }

      .back-icon-button {
        border-color: #d9d9d9;
        color: #595959;
      }

      .back-icon-button:hover {
        border-color: #40a9ff;
        color: #1890ff;
      }

      .step-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 8px;
      }

      .step-title-type {
        font-size: 14px;
        font-weight: 500;
        color: #1890ff;
        margin-left: 10px;
        vertical-align: middle;
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 999px;
        border: 1px solid rgba(24, 144, 255, 0.25);
        background: rgba(24, 144, 255, 0.08);
        box-shadow: 0 2px 8px rgba(24, 144, 255, 0.08);
      }

      .step-subtitle {
        color: #666;
      }
    }

    .monitoring-settings {
      margin-bottom: 24px;
      padding-bottom: 24px;
      border-bottom: 1px solid #e8e8e8;

      .setting-item {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;

        label {
          min-width: 80px;
          font-weight: 500;
        }
      }
    }

    .template-hint {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 16px;
    }

    .rule-templates {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 16px;

      .template-card {
        padding: 24px;
        border: 1px solid #e8e8e8;
        border-radius: 4px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;

        &:hover {
          border-color: #1890ff;
          box-shadow: 0 2px 8px rgba(24, 144, 255, 0.2);
        }

        .template-icon {
          width: 64px;
          height: 64px;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 12px;
          font-size: 32px;
          color: #fff;
        }

        .template-title {
          font-size: 16px;
          font-weight: 500;
          margin-bottom: 8px;
        }

        .template-desc {
          font-size: 12px;
          color: #666;
          line-height: 1.5;
        }
      }
    }

    .rule-section {
      margin-bottom: 24px;
      padding-bottom: 24px;
      border-bottom: 1px solid #e8e8e8;

      &:last-child {
        border-bottom: none;
      }

      .section-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .filter-row {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
      }

      .metric-filter {
        .metric-title {
          font-size: 14px;
          font-weight: 500;
          margin-bottom: 16px;
          display: flex;
          align-items: center;
        }

        .metric-condition {
          display: flex;
          align-items: center;
          gap: 8px;
          margin-bottom: 12px;

          .condition-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #1890ff;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
          }
        }
      }

      .action-list {
        margin-top: 12px;
        display: flex;
        flex-direction: column;
        gap: 12px;
      }

      .action-card {
        background: #f7f8fa;
        border-radius: 10px;
        padding: 16px;
        position: relative;
      }

      .action-card-number {
        position: absolute;
        top: 14px;
        left: 14px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #595959;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
      }

      .action-card-content {
        padding-left: 44px; // 给编号预留位置，保证左侧对齐一致
        padding-right: 100px; // 给右上角删除按钮预留空间，避免覆盖内容
      }

      .action-card-delete {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 0;
      }

      .add-action-link {
        padding: 0;
        display: flex;
        align-items: center;
        gap: 0;
        color: #1890ff;
      }

      .frequency-setting {
        margin-bottom: 16px;
        display: flex;
        align-items: center;
      }

      .check-time-setting {
        .setting-row {
          display: flex;
          align-items: center;
          flex-wrap: wrap;
          gap: 12px;
          margin-bottom: 16px;

          label {
            min-width: 80px;
            font-weight: 500;
            flex-shrink: 0;
          }

          .help-text {
            font-size: 12px;
            color: #999;
            margin-left: 8px;
          }
        }
      }
    }

    .custom-time-slots {
      margin-top: 12px;
      padding-left: 92px;

      .time-slot-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
      }

      .add-time-slot-btn {
        padding: 0;
        color: #1890ff;
      }
    }
  }

  .footer-actions {
    background: #fff;
    padding: 0 24px 100px;
    border-radius: 4px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
  }
}
</style>

