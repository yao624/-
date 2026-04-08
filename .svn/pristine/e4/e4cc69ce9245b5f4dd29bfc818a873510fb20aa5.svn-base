<template>
  <div class="step-creative-settings">
    <h3 class="section-title">{{ t('创意设置') }}</h3>
    <p class="section-hint">
      {{
        t(
          '对应 Meta 广告对象（Ad）的 name、status；创意身份来自公共主页（object_story_spec.page_id）；网站/应用可配置事件追踪；网址参数用于落地页 UTM 与动态追踪。',
        )
      }}
      <a
        class="doc-link"
        href="https://developers.facebook.com/docs/marketing-api/reference/adgroup"
        target="_blank"
        rel="noopener noreferrer"
        >{{ t('Ad 参考') }}</a
      >
    </p>

    <a-form layout="vertical">
      <a-form-item :label="t('广告名称') + ' *'">
        <a-input
          ref="adNameInputRef"
          v-model:value="local.adName"
          :placeholder="t('请输入')"
          allow-clear
          style="max-width: 560px"
        />
        <div class="name-tags">
          <a-button
            v-for="item in nameTagsShort"
            :key="item.token"
            type="dashed"
            html-type="button"
            size="small"
            @click.stop.prevent="insertNameToken(item.token)"
            >{{ item.label }}</a-button
          >
          <a-button
            type="link"
            html-type="button"
            size="small"
            @click.stop.prevent="nameTagsExpanded = !nameTagsExpanded"
            >{{ nameTagsExpanded ? t('折叠') : t('展开') }}</a-button
          >
        </div>
        <div v-show="nameTagsExpanded" class="name-tags name-tags-expanded">
          <a-button
            v-for="item in nameTagsFull"
            :key="item.token"
            type="dashed"
            html-type="button"
            size="small"
            @click.stop.prevent="insertNameToken(item.token)"
            >{{ item.label }}</a-button
          >
        </div>
        <div class="field-hint">
          {{ t('标签将插入 Meta 支持的宏占位符（如 {macro}）；创建广告时由后端替换为实际值。', macroHintParams) }}
        </div>
      </a-form-item>

      <a-form-item :label="t('广告状态')">
        <a-switch v-model:checked="local.adStatus" :checked-children="t('开启')" :un-checked-children="t('关闭')" />
        <span class="field-hint inline">{{ t('对应 Ad status：ACTIVE / PAUSED') }}</span>
      </a-form-item>

      <a-alert type="warning" show-icon class="tip">
        <template #message
          >{{ t('若您的主页没有全部授权,可能导致无法找到所需主页,建议授权现有和今后的所有主页。') }}
          <a href="https://www.facebook.com/business/help" target="_blank" rel="noopener noreferrer">{{
            t('详细了解')
          }}</a></template
        >
      </a-alert>

      <a-form-item :label="t('主页类型')">
        <a-radio-group v-model:value="local.pageType" button-style="solid">
          <a-radio-button value="all">{{ t('全部主页') }}</a-radio-button>
          <a-radio-button value="personal">{{ t('个人号主页') }}</a-radio-button>
          <a-radio-button value="adaccount">{{ t('广告账户主页') }}</a-radio-button>
        </a-radio-group>
        <div class="field-hint">{{ pageTypeHint }}</div>
      </a-form-item>

      <a-form-item :label="t('Facebook 公共主页') + ' *'">
        <a-space wrap>
          <a-select
            v-model:value="local.fbPage"
            :placeholder="t('请选择')"
            style="width: min(100%, 360px)"
            show-search
            option-filter-prop="label"
            :loading="pagesLoading"
            :filter-option="filterPageOption"
            :not-found-content="pagesLoading ? undefined : t('暂无数据')"
            @dropdown-visible-change="onPageDropdownOpen"
          >
            <a-select-option
              v-for="p in filteredPages"
              :key="p.id"
              :value="p.id"
              :label="`${p.name} ${p.source_id}`"
            >
              {{ p.name }} ({{ p.source_id }})
            </a-select-option>
          </a-select>
          <a href="https://www.facebook.com/business/help" target="_blank" rel="noopener noreferrer">{{
            t('找不到主页?')
          }}</a>
        </a-space>
        <div class="field-hint">{{ t('将写入创建广告时的 page_id（与基础设置可不同，以本页为准）。') }}</div>
      </a-form-item>

      <a-form-item v-if="conversionLocation === 'app'" :label="t('使用公共主页而不是应用名称作为广告发布身份')">
        <a-switch v-model:checked="local.usePageAsIdentity" />
        <div class="field-hint">{{ t('应用推广场景下建议开启，以符合 Meta 对发布身份的要求。') }}</div>
      </a-form-item>

      <a-form-item :label="t('多广告主广告')">
        <a-switch v-model:checked="local.multiAdvertiser" />
        <div class="field-hint">{{ t('与 Meta 多广告主展示相关设置对应（写入投放扩展参数）。') }}</div>
      </a-form-item>

      <template v-if="showWebsiteTrackingPanel">
        <a-form-item :label="websitePixelLabel">
          <a-space wrap align="start">
            <a-select
              v-model:value="websitePixelSelectValue"
              :placeholder="t('请选择')"
              show-search
              allow-clear
              style="width: min(100%, 400px)"
              :loading="pixelsLoading"
              option-filter-prop="label"
              :filter-option="filterPixelOption"
              :not-found-content="pixelsLoading ? undefined : t('暂无数据')"
              @dropdown-visible-change="onPixelDropdownOpen"
            >
              <a-select-option
                v-for="p in pixelsRaw"
                :key="p.id"
                :value="p.id"
                :label="`${p.name || ''} ${p.source_id || p.id}`"
              >
                {{ p.name || p.pixel || p.id }} ({{ p.source_id || p.id }})
              </a-select-option>
            </a-select>
            <a-button :loading="pixelsLoading" @click="loadPixels">{{ t('同步') }}</a-button>
          </a-space>
          <div class="field-hint">
            {{
              websitePixelAndEventRequired
                ? t('网站转化需绑定 Pixel；未单独选择时默认与「基础设置」中的像素一致，可在此覆盖本广告的 Pixel。')
                : t('流量/知名度等目标下为选填；未选时默认与「基础设置」中的像素一致。')
            }}
          </div>
        </a-form-item>
        <a-form-item :label="t('网站转化事件')">
          <a-select
            v-model:value="local.websiteEvent"
            :placeholder="t('请选择')"
            allow-clear
            style="width: 100%; max-width: 400px"
          >
            <a-select-option v-for="opt in websiteEventOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </a-select-option>
          </a-select>
          <div class="field-hint">
            {{
              websitePixelAndEventRequired
                ? t('标准事件类型，与 Pixel 回传及 Ad tracking_specs 一致（如 PURCHASE）。')
                : t('选填；用于衡量或进阶优化时与 Pixel 事件一致。')
            }}
          </div>
        </a-form-item>
      </template>

      <a-alert v-else-if="conversionLocation === 'app'" type="info" show-icon class="tip">
        <template #message>{{
          t('网站 Pixel 与网站转化事件仅在「基础设置」中转化发生位置为「网站」时显示；当前为应用转化，请使用下方「应用事件追踪」。')
        }}</template>
      </a-alert>

      <a-form-item v-if="showAppEventTracking" :label="t('应用事件追踪')">
        <a-select
          v-model:value="local.appEventTracking"
          :placeholder="t('请选择')"
          allow-clear
          style="width: 100%; max-width: 400px"
        >
          <a-select-option v-for="opt in appEventTrackingOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </a-select-option>
        </a-select>
        <div class="field-hint">{{ t('用于应用转化场景，与应用事件及追踪配置一致。') }}</div>
      </a-form-item>

      <a-alert type="info" show-icon class="tip url-params-tip">
        <template #message>
          {{ t('如需查看 Shopify、SHOPLINE 或 Shoplazza 指标，您可以') }}
          <a href="#creative-url-params" @click.prevent="focusUrlParams">{{ t('添加追踪参数') }}</a>
        </template>
      </a-alert>

      <a-form-item :label="t('网址参数')" id="creative-url-params">
        <a-input
          ref="urlParamsInputRef"
          v-model:value="local.urlParams"
          :placeholder="t('请输入网址参数')"
          allow-clear
          style="max-width: 720px"
        />
        <div class="url-param-tags">
          <span class="url-param-label">{{ t('XMP 通配符') }}</span>
          <a-button
            v-for="item in xmpUrlTokens"
            :key="item"
            type="dashed"
            size="small"
            html-type="button"
            @click="appendUrlToken(item)"
            >{{ item }}</a-button
          >
        </div>
        <div v-show="!urlParamsMetaCollapsed" class="url-param-tags url-param-tags-meta">
          <span class="url-param-label">{{ t('Meta 动态网址参数') }}</span>
          <a-button
            v-for="item in metaDynamicUrlTokens"
            :key="item"
            type="dashed"
            size="small"
            html-type="button"
            @click="appendUrlToken(item)"
            >{{ item }}</a-button
          >
        </div>
        <div class="url-param-tags-toggle">
          <a-button type="link" size="small" html-type="button" @click="urlParamsMetaCollapsed = !urlParamsMetaCollapsed">
            {{ urlParamsMetaCollapsed ? t('展开') : t('收起') }}
          </a-button>
        </div>
        <div class="field-hint">
          {{
            t('将追加到落地页链接查询串；点击标签插入 Meta 支持的动态参数或 XMP 通配符（与广告名称宏类似，由后端解析）。')
          }}
        </div>
      </a-form-item>

      <a-form-item :label="t('创意组')">
        <div class="creative-group-toolbar">
          <a-dropdown>
            <template #overlay>
              <a-menu @click="onBatchMenuClick">
                <a-menu-item key="clear">{{ t('清空创意组引用') }}</a-menu-item>
              </a-menu>
            </template>
            <a-button>
              {{ t('批量操作') }}
              <down-outlined />
            </a-button>
          </a-dropdown>
        </div>
        <div class="creative-group-bar">
          <a-button type="dashed" @click="emit('go-creative-group')">+ {{ t('新增') }}</a-button>
          <span class="slot-count">{{ creativeSlotLabel }}</span>
        </div>
        <div class="field-hint">{{ t('详细素材与格式请在「创意组」步骤配置；此处可快速跳转。') }}</div>
      </a-form-item>
    </a-form>
  </div>
</template>

<script lang="ts" setup>
import { ref, reactive, watch, computed, onMounted, nextTick, toRaw } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { DownOutlined } from '@ant-design/icons-vue';
import type { MenuInfo } from 'ant-design-vue/lib/menu/src/interface';
import { queryPagesApi } from '@/api/pages';
import { queryFB_PixelsApi } from '@/api/fb_pixels';
import { queryFB_AD_AccountOneApi } from '@/api/fb_ad_accounts';

const { t } = useI18n();

/** 模板内不能写 `{{date}}` 字面量，否则 `}}` 会截断插值表达式 */
const macroHintParams = { macro: '{{date}}' };

const props = defineProps<{
  formData: any;
  /** 广告账户 ULID，用于拉取可选主页 */
  adAccountId?: string | null;
  /** FB 个人号/操作员 ULID，主页类型为「个人号」时筛选 */
  operatorId?: string | null;
  conversionLocation?: string;
  objective?: string;
  /** 基础设置中选择的像素，用于默认与「未单独覆盖」时的回退 */
  pixelFromStepOne?: string | null;
  /** 来自 stepCreativeGroup，用于展示 1/20 */
  creativeGroup?: any;
}>();

const emit = defineEmits<{
  (e: 'update:form-data', v: any): void;
  (e: 'go-creative-group'): void;
}>();

const adNameInputRef = ref();
const urlParamsInputRef = ref();
const nameTagsExpanded = ref(false);
/** false=展示 Meta 动态参数行；true=收起该行 */
const urlParamsMetaCollapsed = ref(false);
const pagesLoading = ref(false);
const pagesRaw = ref<any[]>([]);
const pixelsLoading = ref(false);
const pixelsRaw = ref<any[]>([]);

const xmpUrlTokens = ['{{AccountID}}', '{{AccountName}}'];
const metaDynamicUrlTokens = [
  '{{campaign.id}}',
  '{{campaign.name}}',
  '{{adset.id}}',
  '{{adset.name}}',
  '{{ad.id}}',
  '{{ad.name}}',
  '{{placement}}',
  '{{site_source_name}}',
];

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

function defaultCreativeSettingsLocal() {
  return {
    adName: '',
    adStatus: true,
    pageType: 'all' as 'all' | 'personal' | 'adaccount',
    fbPage: undefined as string | undefined,
    usePageAsIdentity: false,
    multiAdvertiser: true,
    websitePixelId: null as string | null,
    websiteEvent: 'PURCHASE',
    appEventTracking: 'PURCHASE',
    urlParams: '',
  };
}

/** 使用 reactive + Object.assign 合并父级数据，避免 ref 整体替换导致 v-model 与宏标签插入不同步 */
const initForm = { ...defaultCreativeSettingsLocal(), ...(props.formData || {}) };
if (initForm.fbPage === null || initForm.fbPage === '') {
  initForm.fbPage = undefined;
}
if (initForm.websitePixelId === undefined) {
  initForm.websitePixelId = null;
}
const local = reactive(initForm);

/** 避免 props 回写 local 时再次 emit，造成父子循环更新导致页面卡死 */
const syncingFromParent = ref(false);

/** 标签文案 → 后端识别的宏（与 CreateAd::processName / 扩展解析一致） */
const nameTagsShort = computed(() => [
  { label: t('首个素材名称'), token: '{{first_material_name}}' },
  { label: t('首个素材名称（含格式）'), token: '{{first_material_name_ext}}' },
  { label: t('系统用户名'), token: '{{user.name}}' },
  { label: t('账户名'), token: '{{account.name}}' },
  { label: t('账户备注名'), token: '{{account.notes}}' },
]);

const nameTagsFull = computed(() => [
  { label: t('地区组名称'), token: '{{region_group_name}}' },
  { label: t('定向包名称'), token: '{{targeting_pkg}}' },
  { label: t('创意组名称'), token: '{{creative_group_name}}' },
  { label: t('首个素材备注'), token: '{{first_material_remark}}' },
  { label: t('地区'), token: '{{region}}' },
  { label: t('创建日期(yyyy/mm/dd)'), token: '{{date_ymd_slash}}' },
  { label: t('创建日期(yyyymmdd)'), token: '{{date_ymd}}' },
  { label: t('广告系列名称'), token: '{{campaign_name}}' },
  { label: t('广告组名称'), token: '{{adset_name}}' },
  { label: t('{{date}}'), token: '{{date}}' },
  { label: t('{{random}}'), token: '{{random}}' },
  { label: t('{{acc.id}}'), token: '{{acc.id}}' },
]);

const websiteEventOptions = [
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
  { label: 'Contact', value: 'CONTACT' },
  { label: 'Lead', value: 'LEAD' },
];

/** 只要「基础设置」为网站转化，即展示网站 Pixel + 网站转化事件（与是否流量类目标无关；后者仅影响是否必填） */
const showWebsiteTrackingPanel = computed(() => props.conversionLocation === 'website');

/** 与 index 中 creativeSettingsNeedsWebsitePixel 一致：流量/知名度/互动等目标下网站 Pixel 可不填 */
const websitePixelAndEventRequired = computed(() => {
  const obj = props.objective ?? '';
  return !['OUTCOME_TRAFFIC', 'OUTCOME_AWARENESS', 'OUTCOME_ENGAGEMENT'].includes(obj);
});

const websitePixelLabel = computed(() =>
  websitePixelAndEventRequired.value ? `${t('网站 Pixel')} *` : t('网站 Pixel'),
);

/** 与出价步骤「应用转化事件」展示条件一致 */
const showAppEventTracking = computed(() => {
  const loc = props.conversionLocation ?? '';
  const obj = props.objective ?? '';
  if (loc !== 'app') return false;
  if (obj === 'OUTCOME_APP_PROMOTION' || obj === 'OUTCOME_TRAFFIC') return false;
  return true;
});

const appEventTrackingOptions = computed(() => {
  return props.objective === 'OUTCOME_LEADS' ? appLeadsEventOptions : appSalesEventOptions;
});

/** 创意层 Pixel：有独立选择则存 websitePixelId；否则 null，展示时回落到基础设置 */
const websitePixelSelectValue = computed({
  get(): string | undefined {
    const own = local.websitePixelId;
    if (own != null && own !== '') return String(own);
    const s = props.pixelFromStepOne;
    return s != null && s !== '' ? String(s) : undefined;
  },
  set(v: string | undefined) {
    const step =
      props.pixelFromStepOne != null && props.pixelFromStepOne !== '' ? String(props.pixelFromStepOne) : undefined;
    if (v == null || v === undefined) {
      local.websitePixelId = null;
      return;
    }
    if (step != null && v === step) {
      local.websitePixelId = null;
    } else {
      local.websitePixelId = v;
    }
  },
});

/** 与 Meta Marketing API：广告身份由 Page（object_story_spec.page_id）决定；列表按「全部 / 个人号 / 当前广告账户」筛选 */
const pageTypeHint = computed(() => {
  if (local.pageType === 'all') {
    return t('显示全部已授权公共主页；切换类型后下方列表将按规则重新加载。');
  }
  if (local.pageType === 'personal') {
    return props.operatorId
      ? t('仅显示与当前操作员（个人号）关联的公共主页；若为空请在「基础设置」选择操作员。')
      : t('未选择操作员时无法按个人号筛选；请在「基础设置」选择操作员后重试。');
  }
  return props.adAccountId
    ? t('仅显示与当前广告账户关联的公共主页（用于广告发布身份）；与 Meta 中「广告账户可访问的主页」一致。')
    : t('未选择广告账户时无法按账户筛选；请在「基础设置」选择广告账户后重试。');
});

const filteredPages = computed(() => {
  const list = pagesRaw.value || [];
  if (local.pageType === 'all') {
    return list;
  }
  /** 已按 fb_account_id 请求时，后端已限定范围，一般无需再筛 */
  if (local.pageType === 'personal' && props.operatorId) {
    return list.filter((p: any) =>
      (p.users || []).some((u: any) => String(u.fb_account_id) === String(props.operatorId)),
    );
  }
  if (local.pageType === 'adaccount' && props.adAccountId) {
    return list.filter((p: any) =>
      (p.users || []).some((u: any) => String(u.fb_account_id) === String(props.adAccountId)),
    );
  }
  if (local.pageType === 'adaccount') {
    return list.filter((p: any) => (p.promotion_eligible !== false && p.users_count > 0) || (p.users || []).length > 0);
  }
  return list;
});

const creativeSlotLabel = computed(() => {
  const max = 20;
  const g = props.creativeGroup;
  let n = 1;
  if (g?.groups?.length) {
    const counts = g.groups.map((gr: any) => {
      const m = gr.materialIds?.length || 0;
      const v = gr.videoMaterialIds?.length || 0;
      const i = gr.imageMaterialIds?.length || 0;
      const p = gr.postIds?.length || 0;
      return Math.max(m, v + i, p, 1);
    });
    n = Math.max(1, ...counts);
  } else if (g?.materialIds && Array.isArray(g.materialIds)) {
    n = Math.max(1, g.materialIds.length);
  } else if (g?.postIds && Array.isArray(g.postIds)) {
    n = Math.max(1, g.postIds.length);
  }
  return `${Math.min(n, max)}/${max}`;
});

function filterPageOption(input: string, option: any) {
  const label = String(option?.label ?? '');
  return label.toLowerCase().includes(String(input).toLowerCase());
}

function filterPixelOption(input: string, option: any) {
  const label = String(option?.label ?? '');
  return label.toLowerCase().includes(String(input).toLowerCase());
}

async function loadPixels() {
  pixelsLoading.value = true;
  try {
    if (props.adAccountId) {
      const detail: any = await queryFB_AD_AccountOneApi({ id: props.adAccountId, 'with-campaign': false });
      const pixelListFromAccount = detail?.pixels ?? [];
      if (Array.isArray(pixelListFromAccount) && pixelListFromAccount.length > 0) {
        pixelsRaw.value = pixelListFromAccount;
      } else {
        const pixelRes: any = await queryFB_PixelsApi({ pageNo: 1, pageSize: 200 });
        pixelsRaw.value = Array.isArray(pixelRes?.data) ? pixelRes.data : [];
      }
    } else {
      const pixelRes: any = await queryFB_PixelsApi({ pageNo: 1, pageSize: 200 });
      pixelsRaw.value = Array.isArray(pixelRes?.data) ? pixelRes.data : [];
    }
  } catch {
    pixelsRaw.value = [];
  } finally {
    pixelsLoading.value = false;
  }
}

function onPixelDropdownOpen(open: boolean) {
  if (open && pixelsRaw.value.length === 0) {
    loadPixels();
  }
}

function normalizePagesResponse(res: any): any[] {
  const raw = (res?.data ?? res) || [];
  return Array.isArray(raw) ? raw : [];
}

async function loadPages() {
  pagesLoading.value = true;
  try {
    const params: Record<string, any> = { pageSize: 500, pageNo: 1 };
    const pt = local.pageType;
    /** 与后端 FbPageController：fb_account_id 过滤「与该 FB 账户关联的公共主页」 */
    if (pt === 'personal' && props.operatorId) {
      params.fb_account_id = props.operatorId;
    } else if (pt === 'adaccount' && props.adAccountId) {
      params.fb_account_id = props.adAccountId;
    }
    const res: any = await queryPagesApi(params);
    let list = normalizePagesResponse(res);
    if (list.length === 0 && props.operatorId && pt === 'personal') {
      const res2: any = await queryPagesApi({ pageSize: 500, pageNo: 1, fb_account_id: props.operatorId });
      list = normalizePagesResponse(res2);
    }
    pagesRaw.value = list;
  } catch {
    pagesRaw.value = [];
  } finally {
    pagesLoading.value = false;
  }
}

function onPageDropdownOpen(open: boolean) {
  if (open && pagesRaw.value.length === 0) {
    loadPages();
  }
}

function syncFbPageSelectionAfterFilter() {
  const list = filteredPages.value || [];
  const ids = new Set((list as any[]).map((p) => String(p.id)));
  const cur = local.fbPage != null && local.fbPage !== '' ? String(local.fbPage) : '';
  if (cur && !ids.has(cur)) {
    local.fbPage = undefined;
  }
}

function insertNameToken(token: string) {
  const cur = String(local.adName ?? '');
  local.adName = cur + token;
  void nextTick(() => {
    const el = adNameInputRef.value?.input || adNameInputRef.value?.$el?.querySelector?.('input');
    if (el && typeof el.focus === 'function') {
      el.focus();
    }
  });
}

function appendUrlToken(token: string) {
  const cur = String(local.urlParams ?? '').trim();
  local.urlParams = cur ? `${cur}${cur.endsWith('&') ? '' : '&'}${token}` : token;
  void nextTick(() => {
    const el = urlParamsInputRef.value?.input || urlParamsInputRef.value?.$el?.querySelector?.('input');
    el?.focus?.();
  });
}

function focusUrlParams() {
  void nextTick(() => {
    const el = urlParamsInputRef.value?.input || urlParamsInputRef.value?.$el?.querySelector?.('input');
    el?.focus?.();
    el?.scrollIntoView?.({ block: 'nearest', behavior: 'smooth' });
  });
}

function onBatchMenuClick(info: MenuInfo) {
  const key = String(info.key);
  if (key === 'clear') {
    message.info(t('请在「创意组」步骤管理素材；此处仅跳转。'));
  }
}

onMounted(() => {
  loadPages();
  if (props.conversionLocation === 'website') {
    loadPixels();
  }
});

watch(
  () => props.formData,
  async (v) => {
    if (!v || typeof v !== 'object') return;
    const prevPageType = local.pageType;
    syncingFromParent.value = true;
    const fb = v.fbPage;
    const d = defaultCreativeSettingsLocal();
    Object.assign(local, {
      ...v,
      fbPage: fb === null || fb === '' ? undefined : fb,
      appEventTracking: v.appEventTracking ?? d.appEventTracking,
      urlParams: v.urlParams ?? d.urlParams,
      websitePixelId: v.websitePixelId != null && v.websitePixelId !== '' ? v.websitePixelId : null,
    });
    const pageTypeChangedFromParent = prevPageType !== local.pageType;
    if (pageTypeChangedFromParent) {
      await loadPages();
      await nextTick();
      syncFbPageSelectionAfterFilter();
    }
    nextTick(() => {
      syncingFromParent.value = false;
    });
  },
  { deep: true },
);

/** 用户切换「主页类型」时重新拉取列表并校验已选主页（与 Meta page_id 身份一致） */
watch(
  () => local.pageType,
  async () => {
    if (syncingFromParent.value) return;
    await loadPages();
    await nextTick();
    syncFbPageSelectionAfterFilter();
  },
);

watch(
  () => props.conversionLocation,
  (loc) => {
    if (loc === 'website' && pixelsRaw.value.length === 0) {
      loadPixels();
    }
  },
);

watch(
  local,
  () => {
    if (syncingFromParent.value) return;
    emit('update:form-data', { ...toRaw(local) });
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
.section-hint {
  margin: 0 0 16px;
  font-size: 13px;
  color: #8c8c8c;
  line-height: 1.5;
}
.doc-link {
  margin-left: 8px;
}
.field-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #8c8c8c;
  line-height: 1.45;
}
.field-hint.inline {
  margin-top: 0;
  margin-left: 12px;
}
.tip {
  margin-bottom: 16px;
}
.name-tags {
  margin-top: 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.name-tags-expanded {
  margin-top: 4px;
}
.creative-group-toolbar {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}
.creative-group-bar {
  display: flex;
  align-items: center;
  gap: 12px;
}
.slot-count {
  font-size: 13px;
  color: #8c8c8c;
}
.url-params-tip {
  margin-bottom: 12px;
}
.url-param-tags {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.url-param-tags-meta {
  margin-top: 8px;
}
.url-param-label {
  font-size: 13px;
  color: #595959;
  flex-shrink: 0;
}
.url-param-tags-toggle {
  margin-top: 4px;
}
</style>
