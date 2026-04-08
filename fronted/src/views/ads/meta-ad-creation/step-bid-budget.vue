<template>
  <div class="step-bid-budget">
    <div class="bb-header">
      <h3 class="section-title">{{ t('出价和预算') }}</h3>
      <a-button type="link" danger class="clear-link" @click="resetToSingle">{{ t('清空') }}</a-button>
    </div>
    <div class="toolbar">
      <div />
      <div class="toolbar-right">
        <a-button type="link" @click="addPackage">{{ t('+ 新增') }}</a-button>
        <span class="pkg-count">{{ packages.length }}/{{ MAX_PKGS }}</span>
      </div>
    </div>

    <div class="bb-layout">
      <div class="bb-sidebar">
        <div
          v-for="(pkg, pkgIndex) in packages"
          :key="pkg.id"
          class="bb-side-item"
          :class="{ active: activeTabKey === pkg.id }"
          role="button"
          tabindex="0"
          @click="activeTabKey = pkg.id"
          @keydown.enter.prevent="activeTabKey = pkg.id"
        >
          {{ tabDisplayName(pkg, pkgIndex) }}
        </div>
      </div>

      <div class="bb-content">
        <div v-for="pkg in packages" :key="pkg.id + '-pane'">
          <div v-if="activeTabKey === pkg.id" class="tab-pane-inner">
          <a-form layout="vertical">
            <a-form-item :label="t('绑定对象') + ' *'">
              <div class="bind-object-row">
                <span>{{ t('地区') }}（{{ t('全部') }}）</span>
                <a-button type="link" size="small" disabled>{{ t('编辑') }}</a-button>
              </div>
            </a-form-item>

            <a-form-item :label="t('成效目标')">
              <a-radio-group
                v-model:value="pkg.goal"
                class="goal-radio-group"
                button-style="solid"
                @change="() => onGoalChange(pkg)"
              >
                <a-radio-button value="offsite_conversions">{{ t('转化量最大化') }}</a-radio-button>
                <a-radio-button value="landing_page_views">{{ t('落地页浏览量最大化') }}</a-radio-button>
                <a-radio-button value="link_clicks">{{ t('链接点击量最大化') }}</a-radio-button>
                <a-radio-button value="reach">{{ t('单日独立覆盖人数最大化') }}</a-radio-button>
              </a-radio-group>
            </a-form-item>

            <a-form-item v-if="needsPixelBlock(pkg)" :label="t('Pixel 分配方式')">
              <div class="btn-switch">
                <a-button
                  size="small"
                  :type="pkg.pixelAssignMode === 'uniform' ? 'primary' : 'default'"
                  @click="pkg.pixelAssignMode = 'uniform'"
                >
                  {{ t('统一分配') }}
                </a-button>
                <a-button
                  size="small"
                  :type="pkg.pixelAssignMode === 'by_account' ? 'primary' : 'default'"
                  @click="pkg.pixelAssignMode = 'by_account'"
                >
                  {{ t('按账户') }}
                </a-button>
              </div>
            </a-form-item>

            <a-form-item v-if="needsPixelBlock(pkg)" :label="t('Pixel') + ' *'">
              <div class="pixel-table">
                <div class="pixel-th">Pixel</div>
                <div class="pixel-th">{{ t('转化事件') }}</div>
                <div class="pixel-td">
                  <a-select v-model:value="pkg.pixelId" :placeholder="t('请选择')" allow-clear />
                </div>
                <div class="pixel-td">
                  <a-select v-model:value="pkg.pixelEvent" :placeholder="t('请选择')" allow-clear />
                </div>
              </div>
            </a-form-item>

            <a-form-item v-if="showAppConversionEvent" :label="t('应用转化事件') + ' *'">
              <a-select
                v-model:value="pkg.appEvent"
                :placeholder="t('请选择')"
                style="width: 100%; max-width: 400px"
                allow-clear
              >
                <a-select-option v-for="opt in appConversionEventOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </a-select-option>
              </a-select>
            </a-form-item>

            <a-form-item v-if="needsWebsitePixel(pkg)" :label="t('网站 Pixel 转化事件') + ' *'">
              <a-select
                v-model:value="pkg.websitePixelEvent"
                :placeholder="t('请选择')"
                style="width: 100%; max-width: 400px"
                allow-clear
              >
                <a-select-option v-for="opt in websitePixelEventOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </a-select-option>
              </a-select>
              <div class="field-hint">{{ t('对应 Meta custom_event_type，需与像素回传一致。') }}</div>
            </a-form-item>

            <a-form-item
              :label="t('竞价策略')"
              :validate-status="bidStrategyItemStatus(pkg)"
              :help="bidStrategyItemHelp(pkg)"
            >
              <a-radio-group v-model:value="pkg.bidStrategy" button-style="solid" @change="() => onBidStrategyChange(pkg)">
                <a-radio-button value="HIGHEST_VOLUME">{{ t('最高数量') }}</a-radio-button>
                <a-radio-button value="COST_PER_RESULT" :disabled="bidStrategyCostDisabled(pkg)">{{
                  t('单次成效费用目标')
                }}</a-radio-button>
                <a-radio-button value="BID_CAP" :disabled="bidStrategyCapDisabled(pkg)">{{ t('竞价上限') }}</a-radio-button>
                <a-radio-button value="ROAS" :disabled="bidStrategyRoasDisabled(pkg)">{{ t('广告花费回报目标') }}</a-radio-button>
              </a-radio-group>
              <div class="field-hint">{{ t('与 Meta bid_strategy 一致；价值类成效常配合 ROAS。') }}</div>
            </a-form-item>

            <a-form-item v-if="pkg.bidStrategy === 'COST_PER_RESULT'" :label="t('单次成效费用目标') + ' (USD) *'">
              <a-input-number v-model:value="pkg.costPerResultTarget" :min="0" :precision="2" style="width: 220px" />
            </a-form-item>
            <a-form-item v-if="pkg.bidStrategy === 'BID_CAP'" :label="t('竞价上限') + ' (USD) *'">
              <a-input-number v-model:value="pkg.bidCapAmount" :min="0" :precision="2" style="width: 220px" />
            </a-form-item>
            <a-form-item v-if="pkg.bidStrategy === 'ROAS'" :label="t('ROAS 目标') + ' (×) *'">
              <a-input-number v-model:value="pkg.roasTarget" :min="0.01" :step="0.1" :precision="2" style="width: 220px" />
              <div class="field-hint">{{ t('例如 2 表示 2:1，写入 bid_constraints.roas_average_floor') }}</div>
            </a-form-item>

            <a-form-item
              :label="t('竞价控制额') + ' *'"
              :validate-status="bidControlSectionStatus(pkg)"
              :help="bidControlSectionHelp(pkg)"
            >
              <a-space wrap>
                <a-input-number
                  v-model:value="pkg.bidControl"
                  :min="0.01"
                  :step="0.01"
                  :precision="2"
                  :placeholder="t('请输入')"
                  style="width: 220px"
                />
                <span class="unit-tag">CNY</span>
                <a-checkbox v-model:checked="pkg.bidByRegion">{{ t('分地区出价') }}</a-checkbox>
              </a-space>
              <div v-if="pkg.bidByRegion" class="region-bid-grid">
                <div class="rb-cell">
                  <span class="rb-label">{{ t('地区组') }}1</span>
                  <a-input-number
                    v-model:value="pkg.regionBidG1"
                    :min="0.01"
                    :step="0.01"
                    :precision="2"
                    :placeholder="t('请输入')"
                    style="width: 140px"
                  />
                  <span class="unit-tag unit-tag--sm">CNY</span>
                </div>
                <div class="rb-cell">
                  <span class="rb-label">{{ t('地区组') }}2</span>
                  <a-input-number
                    v-model:value="pkg.regionBidG2"
                    :min="0.01"
                    :step="0.01"
                    :precision="2"
                    :placeholder="t('请输入')"
                    style="width: 140px"
                  />
                  <span class="unit-tag unit-tag--sm">CNY</span>
                </div>
              </div>
              <div v-if="pkg.bidByRegion" class="field-hint">{{ t('分地区出价时，请为各地区组填写竞价金额（CNY，至少 0.01）。') }}</div>
            </a-form-item>

            <a-form-item v-if="showAttribution(pkg)" :label="t('归因设置') + ' *'">
              <a-select v-model:value="pkg.attribution" style="width: 100%; max-width: 420px">
                <a-select-option v-for="opt in attributionOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </a-select-option>
              </a-select>
              <div class="field-hint">{{ t('写入 attribution_spec，与优化目标可搭配窗口不同。') }}</div>
            </a-form-item>

            <a-form-item :label="t('计费方式') + '（billing_event）'">
              <a-radio-group v-model:value="pkg.billing" button-style="solid">
                <a-radio-button value="impressions">{{ t('展示次数') }} (IMPRESSIONS)</a-radio-button>
                <a-radio-button v-if="showBillingCpc(pkg)" value="cpc">{{ t('链接点击') }} (LINK_CLICKS)</a-radio-button>
              </a-radio-group>
              <div class="field-hint">{{ billingHintFor(pkg) }}</div>
            </a-form-item>

            <a-form-item :label="t('投放类型') + ' *'">
              <a-radio-group v-model:value="pkg.deliveryType" button-style="solid">
                <a-radio-button value="standard">{{ t('标准') }}</a-radio-button>
                <a-radio-button value="accelerated">{{ t('加速') }}</a-radio-button>
              </a-radio-group>
              <div class="field-hint">{{ t('加速对应 pacing_type = no_pacing（在广告组预算层级生效时）。') }}</div>
            </a-form-item>

            <a-form-item :label="t('广告组预算')">
              <a-space direction="vertical" style="width: 100%">
                <a-radio-group v-model:value="pkg.adSetBudgetType" button-style="solid">
                  <a-radio-button value="daily">{{ t('单日预算') }}</a-radio-button>
                  <a-radio-button value="lifetime">{{ t('总预算') }}</a-radio-button>
                </a-radio-group>
                <a-space wrap>
                  <a-input-number v-model:value="pkg.adSetBudget" :min="0" :precision="2" style="width: 160px" />
                  <span class="tz-hint">{{ t('与账户币种一致；与系列预算并存时请避免重复约束。') }}</span>
                </a-space>
                <a-checkbox v-model:checked="pkg.budgetByRegion">{{ t('分地区设置预算') }}</a-checkbox>
                <div v-if="pkg.budgetByRegion" class="region-budget-grid">
                  <div class="rb-cell">
                    <span class="rb-label">T1</span>
                    <a-input-number v-model:value="pkg.regionBudgetT1" :min="0" :precision="2" style="width: 120px" />
                  </div>
                  <div class="rb-cell">
                    <span class="rb-label">T2</span>
                    <a-input-number v-model:value="pkg.regionBudgetT2" :min="0" :precision="2" style="width: 120px" />
                  </div>
                  <div class="rb-cell">
                    <span class="rb-label">T3</span>
                    <a-input-number v-model:value="pkg.regionBudgetT3" :min="0" :precision="2" style="width: 120px" />
                  </div>
                </div>
                <div v-if="pkg.budgetByRegion" class="field-hint">{{ t('写入扩展字段供拆分或多套广告组策略使用。') }}</div>
              </a-space>
            </a-form-item>

            <a-form-item :label="t('排期')">
              <a-radio-group v-model:value="pkg.schedule" button-style="solid">
                <a-radio-button value="now">{{ t('现在开始') }}</a-radio-button>
                <a-radio-button value="custom">{{ t('自定义') }}</a-radio-button>
              </a-radio-group>
            </a-form-item>

            <template v-if="pkg.schedule === 'custom'">
              <a-form-item :label="t('开始时间') + ' *'">
                <a-space wrap>
                  <a-date-picker v-model:value="pkg.startDate" value-format="YYYY-MM-DD" />
                  <a-time-picker v-model:value="pkg.startTime" value-format="HH:mm" format="HH:mm" />
                  <span class="tz-hint">Asia/Shanghai</span>
                </a-space>
              </a-form-item>
              <a-form-item :label="t('结束时间') + ' *'">
                <a-space wrap>
                  <a-date-picker v-model:value="pkg.endDate" value-format="YYYY-MM-DD" />
                  <a-time-picker v-model:value="pkg.endTime" value-format="HH:mm" format="HH:mm" />
                  <span class="tz-hint">Asia/Shanghai</span>
                </a-space>
              </a-form-item>
            </template>

            <a-form-item :label="t('广告组花费限额') + '（' + t('账户币种') + '）'">
              <a-space wrap>
                <a-input-number v-model:value="pkg.spendMin" :min="0" :precision="2" style="width: 140px" />
                <span>~</span>
                <a-input-number v-model:value="pkg.spendMax" :min="0" :precision="2" style="width: 140px" />
              </a-space>
              <div class="field-hint">{{ t('将写入 Meta 广告组花费相关约束（与账户币种一致）；可不填。') }}</div>
            </a-form-item>
          </a-form>
        </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, computed, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { applyBidBudgetLinkage, isBidStrategyAllowedForGoal } from './bid-budget-rules';

const { t } = useI18n();
const MAX_PKGS = 20;

const props = defineProps<{
  formData: any;
  conversionLocation?: string;
  objective?: string;
}>();

const emit = defineEmits<{ (e: 'update:form-data', v: any): void }>();

function genId() {
  return `bb-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 9)}`;
}

function defaultPackage(seq: number) {
  return {
    id: genId(),
    name: `${t('出价')}${seq}`,
    goal: 'link_clicks' as string,
    appEvent: undefined as string | undefined,
    websitePixelEvent: undefined as string | undefined,
    bidStrategy: 'HIGHEST_VOLUME' as string,
    costPerResultTarget: undefined as number | undefined,
    bidCapAmount: undefined as number | undefined,
    roasTarget: undefined as number | undefined,
    attribution: 'click_7d_view_1d' as string,
    billing: 'impressions' as string,
    deliveryType: 'standard' as string,
    adSetBudgetType: 'daily' as string,
    adSetBudget: undefined as number | undefined,
    budgetByRegion: false,
    regionBudgetT1: undefined as number | undefined,
    regionBudgetT2: undefined as number | undefined,
    regionBudgetT3: undefined as number | undefined,
    schedule: 'now' as string,
    startDate: undefined as string | undefined,
    startTime: undefined as string | undefined,
    endDate: undefined as string | undefined,
    endTime: undefined as string | undefined,
    spendMin: undefined as number | undefined,
    spendMax: undefined as number | undefined,
    pixelAssignMode: 'uniform' as 'uniform' | 'by_account',
    pixelId: undefined as string | undefined,
    pixelEvent: undefined as string | undefined,
    bidControl: undefined as number | undefined,
    bidByRegion: false,
    regionBidG1: undefined as number | undefined,
    regionBidG2: undefined as number | undefined,
  };
}

function mergePackage(raw: any, index: number) {
  const d = defaultPackage(index + 1);
  if (!raw || typeof raw !== 'object') {
    applyBidBudgetLinkage(d);
    return d;
  }
  const merged = {
    ...d,
    ...raw,
    id: raw.id || genId(),
    name: String(raw.name ?? d.name),
    goal: raw.goal ?? d.goal,
    websitePixelEvent: raw.websitePixelEvent,
    bidStrategy: raw.bidStrategy ?? d.bidStrategy,
    costPerResultTarget: raw.costPerResultTarget,
    bidCapAmount: raw.bidCapAmount,
    roasTarget: raw.roasTarget,
    attribution: raw.attribution ?? d.attribution,
    billing: raw.billing ?? d.billing,
    deliveryType: raw.deliveryType ?? d.deliveryType,
    adSetBudgetType: raw.adSetBudgetType ?? d.adSetBudgetType,
    adSetBudget: raw.adSetBudget,
    budgetByRegion: raw.budgetByRegion === true,
    regionBudgetT1: raw.regionBudgetT1,
    regionBudgetT2: raw.regionBudgetT2,
    regionBudgetT3: raw.regionBudgetT3,
    schedule: raw.schedule ?? d.schedule,
    appEvent: raw.appEvent,
    startDate: raw.startDate,
    startTime: raw.startTime,
    endDate: raw.endDate,
    endTime: raw.endTime,
    spendMin: raw.spendMin,
    spendMax: raw.spendMax,
    pixelAssignMode: raw.pixelAssignMode === 'by_account' ? 'by_account' : 'uniform',
    pixelId: raw.pixelId,
    pixelEvent: raw.pixelEvent,
    bidControl: raw.bidControl,
    bidByRegion: raw.bidByRegion === true,
    regionBidG1: raw.regionBidG1,
    regionBidG2: raw.regionBidG2,
  };
  applyBidBudgetLinkage(merged);
  return merged;
}

function normalizeFromForm(fd: any): any[] {
  if (fd && Array.isArray(fd.packages) && fd.packages.length > 0) {
    return fd.packages.map((p: any, i: number) => mergePackage(p, i));
  }
  if (fd && typeof fd === 'object' && !fd.packages) {
    return [mergePackage(fd, 0)];
  }
  return [defaultPackage(1)];
}

const packages = ref<any[]>(normalizeFromForm(props.formData));
const activeTabKey = ref<string>(packages.value[0]?.id ?? '');
const syncingFromParent = ref(false);

const appSalesEventOptions = [
  { label: 'Add to cart', value: 'ADD_TO_CART' },
  { label: 'Add payment info', value: 'ADD_PAYMENT_INFO' },
  { label: 'Add to wishlist', value: 'ADD_TO_WISHLIST' },
  { label: 'Complete registration', value: 'COMPLETE_REGISTRATION' },
  { label: 'Donate', value: 'DONATE' },
  { label: 'Init checkout', value: 'INITIATE_CHECKOUT' },
  { label: 'Purchase', value: 'PURCHASE' },
  { label: 'Search', value: 'SEARCH' },
  { label: 'Start trial', value: 'START_TRIAL' },
  { label: 'Subscribe', value: 'SUBSCRIBE' },
  { label: 'View content', value: 'VIEW_CONTENT' },
];

const appLeadsEventOptions = [
  { label: 'Contact', value: 'CONTACT' },
  { label: 'Find Location', value: 'FIND_LOCATION' },
  { label: 'Lead', value: 'LEAD' },
  { label: 'Schedule', value: 'SCHEDULE' },
  { label: 'Search', value: 'SEARCH' },
  { label: 'Start trial', value: 'START_TRIAL' },
  { label: 'Submit Application', value: 'SUBMIT_APPLICATION' },
  { label: 'Subscribe', value: 'SUBSCRIBE' },
  { label: 'View content', value: 'VIEW_CONTENT' },
];

const websitePixelEventOptions = appSalesEventOptions;

function needsPixelBlock(pkg: any) {
  // 对齐截图：仅「转化量最大化」显示 Pixel 分配/事件
  return pkg?.goal === 'offsite_conversions';
}

const attributionOptions = [
  { value: 'click_1d', label: t('点击后 1 天') },
  { value: 'click_7d', label: t('点击后 7 天') },
  { value: 'click_7d_view_1d', label: t('点击后 7 天 / 浏览后 1 天') },
  { value: 'click_1d_view_1d', label: t('点击后 1 天 / 浏览后 1 天') },
];

const showAppConversionEvent = computed(() => {
  const loc = props.conversionLocation ?? '';
  const obj = props.objective ?? '';
  if (loc !== 'app') return false;
  if (obj === 'OUTCOME_APP_PROMOTION' || obj === 'OUTCOME_TRAFFIC') return false;
  return true;
});

const appConversionEventOptions = computed(() => {
  return props.objective === 'OUTCOME_LEADS' ? appLeadsEventOptions : appSalesEventOptions;
});

function needsWebsitePixel(pkg: any) {
  const loc = props.conversionLocation ?? '';
  const obj = props.objective ?? '';
  if (loc !== 'website' || obj !== 'OUTCOME_SALES') return false;
  return ['offsite_conversions', 'conversion_value'].includes(pkg?.goal);
}

function showAttribution(pkg: any) {
  const obj = props.objective ?? '';
  return ['OUTCOME_SALES', 'OUTCOME_LEADS'].includes(obj) && !['link_clicks', 'reach'].includes(pkg?.goal);
}

function bidStrategyCostDisabled(pkg: any) {
  return ['reach', 'link_clicks', 'app_installs'].includes(pkg?.goal);
}

function bidStrategyCapDisabled(pkg: any) {
  return bidStrategyCostDisabled(pkg);
}

function bidStrategyRoasDisabled(pkg: any) {
  return pkg?.goal === 'reach' || pkg?.goal === 'link_clicks' || pkg?.goal === 'app_installs';
}

/** 仅「链接点击量最大化」时展示 CPC（LINK_CLICKS）计费选项，与 Meta 常见搭配一致 */
function showBillingCpc(pkg: any) {
  return pkg?.goal === 'link_clicks';
}

function billingHintFor(pkg: any) {
  if (pkg?.goal === 'link_clicks') {
    return t('链接点击优化时常用 LINK_CLICKS 计费。');
  }
  if (pkg?.goal === 'conversion_value') {
    return t('价值优化通常使用展示计费 IMPRESSIONS。');
  }
  return t('与 optimization_goal 搭配选择计费事件。');
}

function onGoalChange(pkg: any) {
  applyBidBudgetLinkage(pkg);
}

function bidStrategyItemHelp(pkg: any): string {
  if (!isBidStrategyAllowedForGoal(pkg?.goal, pkg?.bidStrategy)) {
    return t('当前成效目标下仅支持「最高数量」，切换成效目标后将自动对齐');
  }
  return '';
}

function bidStrategyItemStatus(pkg: any): 'error' | undefined {
  return bidStrategyItemHelp(pkg) ? 'error' : undefined;
}

function addPackage() {
  if (packages.value.length >= MAX_PKGS) {
    message.warning(t('最多 20 条出价配置'));
    return;
  }
  const np = defaultPackage(packages.value.length + 1);
  packages.value.push(np);
  activeTabKey.value = np.id;
}

function resetToSingle() {
  const np = defaultPackage(1);
  packages.value = [np];
  activeTabKey.value = np.id;
  message.success(t('已清空'));
}

function onBidStrategyChange(pkg: any) {
  if (pkg.bidStrategy !== 'COST_PER_RESULT') pkg.costPerResultTarget = undefined;
  if (pkg.bidStrategy !== 'BID_CAP') pkg.bidCapAmount = undefined;
  if (pkg.bidStrategy !== 'ROAS') pkg.roasTarget = undefined;
}

function tabDisplayName(pkg: any, index: number) {
  const n = String(pkg?.name || '').trim();
  const label = n || `${t('出价')}${index + 1}`;
  return label.length > 14 ? `${label.slice(0, 14)}…` : label;
}

function numOk(v: unknown, min: number) {
  if (v === undefined || v === null || v === '') return false;
  const n = Number(v);
  return !Number.isNaN(n) && n >= min;
}

/** 主竞价额与分地区出价校验，用于表单项提示 */
function bidControlSectionStatus(pkg: any): 'error' | undefined {
  return bidControlSectionHelp(pkg) ? 'error' : undefined;
}

function bidControlSectionHelp(pkg: any): string {
  if (!numOk(pkg?.bidControl, 0.01)) {
    return t('至少应为 0.01');
  }
  if (pkg?.bidByRegion) {
    if (!numOk(pkg?.regionBidG1, 0.01) || !numOk(pkg?.regionBidG2, 0.01)) {
      return t('请为每个地区组填写竞价（至少 0.01）');
    }
  }
  return '';
}

/** 勿用 deep：否则会与父级 form 联动时反复整表替换 packages，导致 Radio.Button 选中态错乱、点击无效 */
watch(
  () => props.formData,
  (v) => {
    syncingFromParent.value = true;
    packages.value = normalizeFromForm(v);
    nextTick(() => {
      syncingFromParent.value = false;
      const ids = new Set(packages.value.map((p) => p.id));
      if (!activeTabKey.value || !ids.has(activeTabKey.value)) {
        activeTabKey.value = packages.value[0]?.id ?? '';
      }
    });
  },
);

watch(
  () => [showAppConversionEvent.value, props.objective] as const,
  () => {
    if (!showAppConversionEvent.value) return;
    for (const p of packages.value) {
      if (!p.appEvent) {
        p.appEvent = props.objective === 'OUTCOME_LEADS' ? 'LEAD' : 'PURCHASE';
      }
    }
  },
  { immediate: true },
);

watch(
  () => [props.conversionLocation, props.objective] as const,
  () => {
    for (const p of packages.value) {
      if (needsWebsitePixel(p) && !p.websitePixelEvent) {
        p.websitePixelEvent = 'PURCHASE';
      }
    }
  },
  { immediate: true },
);

watch(
  packages,
  (list) => {
    if (syncingFromParent.value) return;
    emit('update:form-data', { packages: list.map((p) => ({ ...p })) });
  },
  { deep: true },
);
</script>

<style lang="less" scoped>
.section-title {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 8px;
  color: #262626;
}
.bb-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.clear-link {
  padding-right: 0;
}
.section-hint {
  margin: 0 0 12px;
  font-size: 13px;
  color: #8c8c8c;
  line-height: 1.5;
}
.doc-link {
  margin-left: 8px;
}
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.pkg-count {
  font-size: 13px;
  color: #8c8c8c;
}
.tab-title-text {
  display: inline-block;
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: bottom;
}
.tab-pane-inner {
  min-height: 80px;
}
.bb-layout {
  display: flex;
  gap: 16px;
}
.bb-sidebar {
  width: 200px;
  border-right: 1px solid #f0f0f0;
  padding-right: 12px;
}
.bb-side-item {
  padding: 10px 12px;
  cursor: pointer;
  border-left: 3px solid transparent;
  color: #595959;
}
.bb-side-item.active {
  border-left-color: #1677ff;
  color: #1677ff;
  background: #f0f7ff;
  font-weight: 500;
}
.bb-content {
  flex: 1;
}
.bind-object-row {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.btn-switch {
  display: inline-flex;
  gap: 8px;
  flex-wrap: wrap;
}
.pixel-table {
  display: grid;
  grid-template-columns: 1fr 1fr;
  max-width: 560px;
  border: 1px solid #f0f0f0;
  border-radius: 4px;
  overflow: hidden;
}
.pixel-th {
  background: #fafafa;
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 500;
  border-bottom: 1px solid #f0f0f0;
}
.pixel-td {
  padding: 10px 12px;
}
.unit-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  height: 32px;
  padding: 0 8px;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  color: #595959;
  background: #fafafa;
}
.unit-tag--sm {
  min-width: 40px;
  height: 28px;
  font-size: 12px;
}
.region-bid-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 12px;
  padding: 12px;
  background: #fafafa;
  border: 1px solid #f0f0f0;
  border-radius: 4px;
}
.field-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #8c8c8c;
  line-height: 1.45;
}
.tz-hint {
  color: #999;
  font-size: 12px;
}
.region-budget-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 8px;
}
.rb-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}
.rb-label {
  font-size: 13px;
  color: #595959;
  min-width: 28px;
}

/* 成效目标：2×2 网格，避免 Radio.Button 默认 flex 换行时相邻边框/选中条错位 */
.goal-radio-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  width: 100%;
  max-width: 560px;
}
.goal-radio-group :deep(.ant-radio-button-wrapper) {
  width: 100%;
  margin-inline-start: 0 !important;
  text-align: center;
  border-radius: 6px !important;
  border-inline-start-width: 1px !important;
}
</style>
