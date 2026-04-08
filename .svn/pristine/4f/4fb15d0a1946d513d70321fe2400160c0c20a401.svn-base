<template>
  <div class="step-one-account">
    <!-- 基础设置 标题区（与图示一致） -->
    <h3 class="section-title">{{ t('基础设置') }}</h3>

    <!-- 已选模板提示 -->
    <div v-if="templatePrefill?.name" class="template-badge">
      <a-tag color="blue">{{ t('已选模板') }}: {{ templatePrefill.name }}</a-tag>
    </div>

    <!-- 创建模式 -->
    <div class="section">
      <a-form-item :label="t('创建模式')">
        <a-radio-group v-model:value="localFormData.creationMode">
          <a-radio-button value="standard">{{ t('标准') }}</a-radio-button>
        </a-radio-group>
      </a-form-item>
    </div>

    <!-- 广告目标（打勾表示选中） -->
    <div class="section">
      <a-form-item :label="t('广告目标')">
        <div class="objective-group">
          <button
            v-for="option in objectiveOptions"
            :key="option.value"
            type="button"
            class="objective-option"
            :class="{ active: localFormData.objective === option.value }"
            @click.prevent="onObjectiveChange(option.value)"
          >
            <div class="objective-card">
              <div class="objective-check">
                <component
                  :is="CheckCircleFilled"
                  v-if="localFormData.objective === option.value"
                  class="check-icon"
                />
              </div>
              <div class="objective-icon">
                <component :is="option.icon" />
              </div>
              <div class="objective-content">
                <div class="objective-title">{{ option.label }}</div>
                <div class="objective-description">{{ option.description }}</div>
              </div>
            </div>
          </button>
        </div>
      </a-form-item>
    </div>

    <!-- 进阶赋能型目录广告 -->
    <div class="section">
      <a-form-item :label="t('进阶赋能型目录广告')">
        <a-switch v-model:checked="localFormData.advancedCatalogAds" />
      </a-form-item>
    </div>

    <!-- 转化发生位置 -->
    <div class="section">
      <a-form-item :label="t('转化发生位置')">
        <a-radio-group
          v-model:value="localFormData.conversionLocation"
          @change="onConversionLocationChange"
        >
          <a-radio-button value="app">{{ t('应用') }}</a-radio-button>
          <a-radio-button value="website" :disabled="localFormData.objective === 'APP_INSTALLS'">
            {{ t('网站') }}
          </a-radio-button>
        </a-radio-group>
      </a-form-item>
    </div>

    <!-- 广告账户 *：失焦时仅显示一行触发器；聚焦/展开时显示双栏多选（首项为下游使用的 adAccount） -->
    <div class="section" v-show="showAccountSelection">
      <a-form-item :label="t('广告账户') + ' *'">
        <div ref="adAccountWrapRef" class="ad-account-field-wrap">
          <div
            class="ad-account-trigger"
            tabindex="0"
            @click="onAdAccountTriggerClick"
            @keydown.enter.prevent="accountPickerExpanded = true"
          >
            <span class="ad-account-trigger-text">{{ collapsedAdAccountSummary }}</span>
            <down-outlined class="ad-account-trigger-arrow" />
          </div>
          <div
            v-show="accountPickerExpanded"
            class="ad-account-picker-panel"
            @mousedown.prevent
          >
            <div class="ad-account-picker">
              <div class="picker-left">
                <a-input-search
                  v-model:value="accountSearchKeyword"
                  allow-clear
                  :placeholder="t('搜索广告账户、ID、备注名或标签')"
                  class="picker-search"
                />
                <div class="picker-scroll">
                  <div v-if="adAccountsListLoading" class="picker-loading">
                    <a-spin size="small" />
                    <span>{{ t('正在加载广告账户…') }}</span>
                  </div>
                  <template v-else-if="adAccounts.length === 0">
                    <div class="picker-empty">{{ t('暂无广告账户，请检查 BM 同步或接口权限') }}</div>
                  </template>
                  <template v-else-if="Object.keys(filteredGroupedAccounts).length === 0">
                    <div class="picker-empty">{{ t('无匹配账户') }}</div>
                  </template>
                  <template v-else>
                    <div
                      v-for="(rows, currency) in filteredGroupedAccounts"
                      :key="currency"
                      class="currency-block"
                    >
                      <div class="currency-head">{{ currency }}</div>
                      <button
                        v-for="acc in rows"
                        :key="acc.id"
                        type="button"
                        class="account-row"
                        :class="{ selected: isAdAccountSelected(acc.id) }"
                        @click.prevent="toggleAdAccount(acc.id)"
                      >
                        <div class="account-row-main">
                          <div class="account-name">{{ acc.name }}</div>
                          <div class="account-id">ID: {{ acc.source_id }}</div>
                        </div>
                        <span class="account-status">{{ accountStatusLabel(acc) }}</span>
                        <check-outlined v-if="isAdAccountSelected(acc.id)" class="row-check" />
                      </button>
                    </div>
                  </template>
                </div>
              </div>
              <div class="picker-right">
                <div class="picker-right-toolbar">
                  <span class="picker-count">{{ t('已选') }} {{ selectedAccountCount }} {{ t('个') }}</span>
                  <a-button type="link" size="small" class="picker-clear" @click="clearAdAccountSelection">
                    {{ t('清除') }}
                  </a-button>
                </div>
                <div class="picker-selected-list">
                  <div v-if="selectedAccountCount === 0" class="picker-empty subtle">
                    {{ t('请从左侧选择广告投放账户') }}
                  </div>
                  <div v-else class="selected-chips">
                    <div
                      v-for="item in selectedAccountChips"
                      :key="item.id"
                      class="selected-chip"
                    >
                      <span class="selected-chip-name">{{ item.name }}</span>
                      <close-outlined class="selected-chip-close" @click.stop="removeAdAccountChip(item.id)" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </a-form-item>
    </div>

    <!-- FB 个人号 * -->
    <div class="section" v-show="showAccountSelection">
      <a-form-item :label="t('FB 个人号') + ' *'">
        <a-select
          v-model:value="localFormData.operator"
          :placeholder="t('请选择')"
          style="width: 100%"
          show-search
          :loading="operatorsLoading"
          :filter-option="filterOption"
          @dropdownVisibleChange="onOperatorDropdownVisibleChange"
          @change="onOperatorChange"
        >
          <a-select-option
            v-for="operator in operators"
            :key="operator.id"
            :value="operator.id"
          >
            <div>{{ operator.name }}</div>
            <div v-if="operator.source_id" class="operator-id">ID: {{ operator.source_id }}</div>
          </a-select-option>
        </a-select>
      </a-form-item>
    </div>

    <!-- 广告设置 -->
    <div class="section" v-show="showAccountSelection && localFormData.adAccount">
      <h3 class="section-title">{{ t('广告设置') }}</h3>
      <a-form :model="adSettings" layout="vertical">
        <a-form-item :label="t('操作员')" v-show="false">
          <a-select
            v-model:value="adSettings.operator"
            :placeholder="t('请选择操作员')"
            @change="onOperatorChange"
          >
            <a-select-option
              v-for="operator in operators"
              :key="operator.id"
              :value="operator.id"
            >
              {{ operator.name }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item :label="t('Facebook页面')">
          <a-select
            v-model:value="adSettings.page"
            :placeholder="t('请选择Facebook页面')"
            @change="onPageChange"
          >
            <a-select-option
              v-for="page in pages"
              :key="page.id"
              :value="page.id"
            >
              {{ page.name }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item :label="t('像素')">
          <a-select
            v-model:value="adSettings.pixel"
            :placeholder="t('请选择像素')"
            @change="onPixelChange"
          >
            <a-select-option
              v-for="pixel in pixels"
              :key="pixel.id"
              :value="pixel.id"
            >
              {{ pixel.name || pixel.pixel }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item
          v-if="showFormSelection"
          :label="t('表单')"
        >
          <a-select
            v-model:value="adSettings.form"
            :placeholder="t('请选择表单')"
            @change="onFormChange"
          >
            <a-select-option
              v-for="form in forms"
              :key="form.id"
              :value="form.id"
            >
              {{ form.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { onClickOutside } from '@vueuse/core';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import {
  MobileOutlined,
  ShoppingOutlined,
  GlobalOutlined,
  TeamOutlined,
  ThunderboltOutlined,
  NotificationOutlined,
  CheckCircleFilled,
  CheckOutlined,
  CloseOutlined,
  DownOutlined,
} from '@ant-design/icons-vue';
import type { FbAdAccount, FbAccount, FbPage, Pixel } from '@/utils/fb-interfaces';
import { queryFB_AD_AccountOneApi, queryFB_AD_AccountsApi } from '@/api/fb_ad_accounts';
import {
  getMetaAdCreationBmPersonalAccountsApi,
  syncMetaAdCreationBmGraphAssetsApi,
} from '@/api/meta_ad_creation/index';
import { queryPagesApi, getPageForms } from '@/api/pages';
import { queryFB_PixelsApi } from '@/api/fb_pixels';

interface Props {
  formData: {
    purchaseType?: string;
    objective: string;
    /** 多选 id 列表；下游接口仍用首项作为 adAccount */
    adAccountIds?: string[];
    adAccount: string | null;
    operator: string | null;
    page: string | null;
    pixel: string | null;
    form: string | null;
    creationMode?: string;
    advancedCatalogAds?: boolean;
    conversionLocation?: string;
  };
  /** 从模板进入时传入的模板信息，用于展示已选模板名称（如蓉城先锋） */
  templatePrefill?: { id?: string | number; name?: string; [key: string]: any } | null;
}

interface Emits {
  (e: 'update:form-data', value: Props['formData']): void;
  (e: 'sub-step-change', step: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { t } = useI18n();

/** 父级回填 / 模板同步时抑制 emit，避免与 deep watch 形成死循环（与 step-two-campaign 一致） */
const syncingFromParent = ref(false);

function emitStepOneToParent() {
  const ids = [...(localFormData.value.adAccountIds || [])];
  emit('update:form-data', {
    ...localFormData.value,
    adAccountIds: ids,
    adAccount: ids[0] ?? null,
  } as Props['formData']);
}

function syncPrimaryFromIds() {
  const ids = localFormData.value.adAccountIds || [];
  localFormData.value.adAccount = ids[0] ?? null;
}

/** 与仅有 adAccount 的旧数据兼容 */
function normalizeAdAccountIdsState(): void {
  const v = localFormData.value;
  const ids = v.adAccountIds;
  if (Array.isArray(ids) && ids.length > 0) {
    v.adAccount = ids[0] ?? null;
  } else if (v.adAccount) {
    v.adAccountIds = [v.adAccount];
  } else {
    v.adAccountIds = [];
    v.adAccount = null;
  }
}

// 本地表单数据（含图示基础设置字段）
const localFormData = ref({
  purchaseType: 'auction',
  creationMode: 'standard',
  advancedCatalogAds: false,
  conversionLocation: 'app',
  adAccountIds: [] as string[],
  ...props.formData,
});
normalizeAdAccountIdsState();

// 广告设置
const adSettings = ref({
  operator: props.formData.operator,
  page: props.formData.page,
  pixel: props.formData.pixel,
  form: props.formData.form,
});

// 是否显示账户选择
const showAccountSelection = computed(() => {
  return localFormData.value.objective && localFormData.value.objective !== '';
});

// 是否显示表单选择（仅当推广目标为线索时）
const showFormSelection = computed(() => {
  return localFormData.value.objective === 'OUTCOME_LEADS';
});

// 推广目标选项
const objectiveOptions = [
  {
    value: 'APP_INSTALLS',
    label: t('应用推广'),
    description: t('吸引更多用户安装您的应用'),
    icon: MobileOutlined,
  },
  {
    value: 'OUTCOME_SALES',
    label: t('销量'),
    description: t('寻找可能购买你的商品或服务的用户'),
    icon: ShoppingOutlined,
  },
  {
    value: 'OUTCOME_LEADS',
    label: t('潜在客户'),
    description: t('寻找可能对你的业务或服务感兴趣的潜在客户'),
    icon: TeamOutlined,
  },
  {
    value: 'OUTCOME_ENGAGEMENT',
    label: t('互动'),
    description: t('提高贴文互动、消息互动或主页互动'),
    icon: ThunderboltOutlined,
  },
  {
    value: 'OUTCOME_AWARENESS',
    label: t('知名度'),
    description: t('向更多人展示你的品牌并提升认知'),
    icon: NotificationOutlined,
  },
  {
    value: 'OUTCOME_TRAFFIC',
    label: t('流量'),
    description: t('把用户送往目标位置,例如你的网站、应用或Facebook 活动'),
    icon: GlobalOutlined,
  },
];

// 广告账户等数据（账户从 API 获取，失败时会注入一组本地模拟数据，方便联调后续步骤）
const adAccounts = ref<FbAdAccount[]>([]);
const operators = ref<FbAccount[]>([]);
/** 仅个号下拉触发的 GET /fb-ad-accounts/{id} 加载态 */
const operatorsLoading = ref(false);
const pages = ref<FbPage[]>([]);
const pixels = ref<Pixel[]>([]);
const forms = ref<any[]>([]);
const ENABLE_MOCK_FALLBACK = false;

/** 左侧搜索关键字（匹配名称、source_id、备注、标签名） */
const accountSearchKeyword = ref('');

function accountMatchesSearch(acc: FbAdAccount, q: string): boolean {
  const t = (q || '').trim().toLowerCase();
  if (!t) return true;
  const tagStr = (acc.tags || [])
    .map((x: any) => (typeof x === 'string' ? x : x?.name))
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
  return (
    (acc.name || '').toLowerCase().includes(t) ||
    String(acc.source_id || '').toLowerCase().includes(t) ||
    String(acc.notes || '').toLowerCase().includes(t) ||
    tagStr.includes(t)
  );
}

function sortCurrencyKeys(a: string, b: string): number {
  const order = ['CNY', 'USD', 'EUR', 'HKD', 'GBP', 'JPY', 'AUD', 'CAD', 'SGD'];
  const ia = order.indexOf(a);
  const ib = order.indexOf(b);
  if (ia !== -1 && ib !== -1) return ia - ib;
  if (ia !== -1) return -1;
  if (ib !== -1) return 1;
  if (a === '—') return 1;
  if (b === '—') return -1;
  return a.localeCompare(b);
}

function accountStatusLabel(acc: FbAdAccount): string {
  const s = String(acc.account_status || '').trim();
  if (!s) return '—';
  if (/^active$/i.test(s)) return 'Active';
  return s;
}

/** 搜索后的列表按币种分组：{ CNY: FbAdAccount[], USD: ... }，数据仍来自 GET /fb-ad-accounts 扁平 data */
const filteredGroupedAccounts = computed(() => {
  const list = adAccounts.value.filter((a) => accountMatchesSearch(a, accountSearchKeyword.value));
  const map: Record<string, FbAdAccount[]> = {};
  for (const a of list) {
    const cur = (a.currency || '—').toUpperCase();
    if (!map[cur]) map[cur] = [];
    map[cur].push(a);
  }
  const keys = Object.keys(map).sort(sortCurrencyKeys);
  const out: Record<string, FbAdAccount[]> = {};
  for (const k of keys) out[k] = map[k];
  return out;
});

const adAccountWrapRef = ref<HTMLElement | null>(null);
const accountPickerExpanded = ref(false);

onClickOutside(adAccountWrapRef, () => {
  accountPickerExpanded.value = false;
});

async function onAdAccountTriggerClick() {
  const willOpen = !accountPickerExpanded.value;
  accountPickerExpanded.value = willOpen;
  if (willOpen && !adAccountsListLoading.value && adAccounts.value.length === 0) {
    await fetchAdAccountsWithSync({ silent: true });
  }
}

const selectedAccountCount = computed(() => (localFormData.value.adAccountIds || []).length);

const selectedAccountChips = computed(() => {
  const ids = localFormData.value.adAccountIds || [];
  return ids.map((id) => {
    const a = adAccounts.value.find((x) => x.id === id);
    return { id, name: a?.name ?? id };
  });
});

const collapsedAdAccountSummary = computed(() => {
  const ids = localFormData.value.adAccountIds || [];
  if (ids.length === 0) return t('请选择广告投放账户');
  if (ids.length === 1) {
    const a = adAccounts.value.find((x) => x.id === ids[0]);
    return a?.name ?? ids[0];
  }
  // 多选：与右侧「已选 N 个」一致。同名账户若用「名称 + 等 N-1 个」易被看成只选 1 个
  const labels = ids.map((id) => {
    const a = adAccounts.value.find((x) => x.id === id);
    return a?.name ?? id;
  });
  const allSameName = labels.every((n) => n === labels[0]);
  if (allSameName) {
    return `${t('已选')} ${ids.length} ${t('个')}（${labels[0]}）`;
  }
  return `${t('已选')} ${ids.length} ${t('个')} · ${labels[0]}`;
});

function isAdAccountSelected(id: string): boolean {
  return (localFormData.value.adAccountIds || []).includes(id);
}

function clearAssetsForNoPrimary() {
  localFormData.value.operator = null;
  operators.value = [];
  pages.value = [];
  pixels.value = [];
  forms.value = [];
  adSettings.value = {
    operator: null,
    page: null,
    pixel: null,
    form: null,
  };
}

function toggleAdAccount(accountId: string) {
  const prevPrimary = localFormData.value.adAccountIds?.[0] ?? null;
  const ids = [...(localFormData.value.adAccountIds || [])];
  const idx = ids.indexOf(accountId);
  if (idx === -1) {
    ids.push(accountId);
  } else {
    ids.splice(idx, 1);
  }
  localFormData.value.adAccountIds = ids;
  syncPrimaryFromIds();
  const newPrimary = localFormData.value.adAccountIds[0] ?? null;
  emit('sub-step-change', 'account');
  if (newPrimary !== prevPrimary) {
    if (newPrimary) {
      void loadAccountAssets(newPrimary);
    } else {
      clearAssetsForNoPrimary();
    }
  }
}

function removeAdAccountChip(accountId: string) {
  const prevPrimary = localFormData.value.adAccountIds?.[0] ?? null;
  const ids = (localFormData.value.adAccountIds || []).filter((x) => x !== accountId);
  localFormData.value.adAccountIds = ids;
  syncPrimaryFromIds();
  const newPrimary = ids[0] ?? null;
  emit('sub-step-change', 'account');
  if (newPrimary !== prevPrimary) {
    if (newPrimary) {
      void loadAccountAssets(newPrimary);
    } else {
      clearAssetsForNoPrimary();
    }
  }
}

function clearAdAccountSelection() {
  localFormData.value.adAccountIds = [];
  localFormData.value.adAccount = null;
  clearAssetsForNoPrimary();
  emit('sub-step-change', 'account');
}

// 本地开发 / 接口失败时注入的模拟数据，保证至少能选到一套有效的「账户 + 个人号 + 主页 + 像素 + 表单」
const setupMockDevData = () => {
  const mockAccountId = '00000000000000000000000001';
  const mockOperatorId = '00000000000000000000000002';
  const mockPageId = '00000000000000000000000003';
  const mockPixelId = '00000000000000000000000004';
  const mockFormId = '00000000000000000000000005';

  adAccounts.value = [
    { id: mockAccountId, name: t('测试广告账户（本地模拟）') } as any,
  ];
  operators.value = [
    { id: mockOperatorId, name: t('测试个人号（本地模拟）') } as any,
  ];
  pixels.value = [
    { id: mockPixelId, name: t('测试像素（本地模拟）') } as any,
  ];
  pages.value = [
    {
      id: mockPageId,
      name: t('测试主页（本地模拟）'),
      users: [{ fb_account_id: mockOperatorId }],
      source_id: 'MOCK_PAGE_SOURCE_ID',
    } as any,
  ];
  forms.value = [
    { id: mockFormId, name: t('测试表单（本地模拟）') },
  ];

  localFormData.value = {
    ...localFormData.value,
    adAccountIds: [mockAccountId],
    adAccount: mockAccountId,
    operator: mockOperatorId,
    page: mockPageId,
    pixel: mockPixelId,
    form: mockFormId,
  };
  adSettings.value = {
    operator: mockOperatorId,
    page: mockPageId,
    pixel: mockPixelId,
    form: mockFormId,
  };
};

/** 与 Meta 创建同步接口写死 BM 一致 */
const TEST_BM_ID = '1476379370819673';

/** GET /fb-ad-accounts 拉列表时的 loading（展开面板时也会触发） */
const adAccountsListLoading = ref(false);

/**
 * 从业务库拉广告账户列表；为空时再 BM 同步后重拉。
 * silent：为 true 时不弹全局错误（用于用户点击展开时补拉）。
 */
async function fetchAdAccountsWithSync(options?: { silent?: boolean }): Promise<void> {
  const silent = options?.silent ?? false;

  const fetchList = async () => {
    const res = await queryFB_AD_AccountsApi({
      'with-campaign': false,
      is_archived: false,
      bm_ids: [TEST_BM_ID],
      pageNo: 1,
      pageSize: 500,
    });
    const list = res?.data ?? [];
    adAccounts.value = Array.isArray(list) ? list : [];
  };

  const runSyncThenFetch = async () => {
    try {
      await syncMetaAdCreationBmGraphAssetsApi({ bm_id: TEST_BM_ID });
    } catch (e) {
      console.warn('[meta-ad-creation] BM Graph 同步失败（将仍尝试拉列表）', e);
    }
    await fetchList();
  };

  adAccountsListLoading.value = true;
  try {
    try {
      await fetchList();
      if (adAccounts.value.length === 0) {
        await runSyncThenFetch();
      }
    } catch (e) {
      console.warn('[meta-ad-creation] 先查库失败，尝试 BM 同步后重查', e);
      try {
        await runSyncThenFetch();
      } catch (_) {
        /* ignore */
      }
    }
  } finally {
    adAccountsListLoading.value = false;
  }

  if (!silent && adAccounts.value.length === 0 && !ENABLE_MOCK_FALLBACK) {
    operators.value = [];
    pages.value = [];
    pixels.value = [];
    forms.value = [];
    message.error(t('数据库无广告账户，BM 同步后仍为空，请检查 token 权限和同步接口返回'));
  }
}

// 加载广告账户列表，并设置默认选中第一个
const loadAdAccounts = async () => {
  await fetchAdAccountsWithSync({ silent: false });

  if (adAccounts.value.length > 0) {
    if (!localFormData.value.adAccountIds?.length) {
      const first = adAccounts.value[0];
      const defaultId = (first as any).id ?? (first as any).source_id;
      if (defaultId) {
        localFormData.value.adAccountIds = [defaultId];
        syncPrimaryFromIds();
        await loadAccountAssets(defaultId);
        emit('sub-step-change', 'account');
      }
    }
  } else if (ENABLE_MOCK_FALLBACK) {
    setupMockDevData();
  }
};

// 过滤选项
const filterOption = (input: string, option: any) => {
  return option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0;
};

// 转化发生位置变化（v-model 已写回 localFormData；由 deep watch 同步父级）
const onConversionLocationChange = () => {
  emit('sub-step-change', 'goal');
};

// 推广目标变化（localFormData 更新后由 deep watch 同步父级）
const onObjectiveChange = (value: string) => {
  const next = {
    ...localFormData.value,
    objective: value,
    // 应用推广仅支持应用转化，避免与「网站」组合导致后端无法组 promoted_object
    ...(value === 'APP_INSTALLS' ? { conversionLocation: 'app' as const } : {}),
  };
  localFormData.value = next;
  emit('sub-step-change', 'goal');
};

// 操作员变化
const onOperatorChange = (value: string) => {
  emit('sub-step-change', 'settings');
  adSettings.value.operator = value;
  localFormData.value.operator = value;
};

// 页面变化
const onPageChange = (value: string) => {
  emit('sub-step-change', 'assets');
  adSettings.value.page = value;
  localFormData.value.page = value;
  loadFormsByPageId(value);
};

// 像素变化
const onPixelChange = (value: string) => {
  emit('sub-step-change', 'assets');
  adSettings.value.pixel = value;
  localFormData.value.pixel = value;
};

// 表单变化
const onFormChange = (value: string) => {
  emit('sub-step-change', 'assets');
  adSettings.value.form = value;
  localFormData.value.form = value;
};

// 加载账户资产
const loadFormsByPageId = async (pageId?: string | null) => {
  if (!pageId) {
    forms.value = [];
    return;
  }
  const selectedPage = pages.value.find((p: any) => p.id === pageId);
  const pageSourceId = (selectedPage as any)?.source_id;
  if (!pageSourceId) {
    forms.value = [];
    return;
  }
  try {
    const formRes = await getPageForms({ page_source_id: pageSourceId, pageNo: 1, pageSize: 200 });
    const formList = (formRes as any)?.data ?? [];
    forms.value = Array.isArray(formList) ? formList : [];
    if (!forms.value.find((f: any) => f.id === localFormData.value.form)) {
      localFormData.value.form = forms.value[0]?.id ?? null;
      adSettings.value.form = localFormData.value.form;
    }
  } catch {
    forms.value = [];
  }
};

/** 当前主广告账户 ULID（与 loadAccountAssets 一致） */
const primaryAdAccountId = () =>
  localFormData.value.adAccountIds?.[0] ?? localFormData.value.adAccount ?? null;

/** 个号所属 BM：优先当前主广告账户在列表里带的 bms[0].source_id，否则用同步用的默认 BM */
const resolveBmSourceIdForOperators = (): string => {
  const primaryId = primaryAdAccountId();
  if (primaryId) {
    const acc = adAccounts.value.find((x) => x.id === primaryId) as FbAdAccount & {
      bms?: { source_id?: string }[];
    };
    const sid = acc?.bms?.[0]?.source_id;
    if (sid) return String(sid);
  }
  return TEST_BM_ID;
};

/**
 * 从 GET /meta-ad-creation/bm-personal-accounts 拉个号（后端 Graph：/{bm-id}/business_users），不经过广告账户。
 */
const loadOperatorsFromBm = async () => {
  const bmSourceId = resolveBmSourceIdForOperators();
  operatorsLoading.value = true;
  try {
    const raw = await getMetaAdCreationBmPersonalAccountsApi({ bm_source_id: bmSourceId });
    const list = (raw as any)?.data ?? [];
    operators.value = Array.isArray(list) ? list : [];
    if (!operators.value.find((o: any) => o.id === localFormData.value.operator)) {
      localFormData.value.operator = operators.value[0]?.id ?? null;
      adSettings.value.operator = localFormData.value.operator;
    }
  } catch {
    /* 保留原列表 */
  } finally {
    operatorsLoading.value = false;
  }
};

const onOperatorDropdownVisibleChange = (open: boolean) => {
  if (!open) return;
  if (operators.value.length > 0) return;
  void loadOperatorsFromBm();
};

const loadAccountAssets = async (accountId: string) => {
  operators.value = [];
  pages.value = [];
  pixels.value = [];
  forms.value = [];
  try {
    await loadOperatorsFromBm();
    const raw = await queryFB_AD_AccountOneApi({ id: accountId, 'with-campaign': false });
    // Laravel JsonResource 常为 { data: {...} }，与 step-delivery 一致取 payload
    const detail = (raw as any)?.data ?? raw;
    const pixelListFromAccount = detail?.pixels ?? [];
    if (!operators.value.find((o: any) => o.id === localFormData.value.operator)) {
      localFormData.value.operator = operators.value[0]?.id ?? null;
      adSettings.value.operator = localFormData.value.operator;
    }

    if (Array.isArray(pixelListFromAccount) && pixelListFromAccount.length > 0) {
      pixels.value = pixelListFromAccount as any[];
    } else {
      const pixelRes = await queryFB_PixelsApi({ pageNo: 1, pageSize: 200 });
      pixels.value = Array.isArray((pixelRes as any)?.data) ? (pixelRes as any).data : [];
    }
    if (!pixels.value.find((p: any) => p.id === localFormData.value.pixel)) {
      localFormData.value.pixel = pixels.value[0]?.id ?? null;
      adSettings.value.pixel = localFormData.value.pixel;
    }

    const pageRes = await queryPagesApi({ pageNo: 1, pageSize: 500 });
    const pageList = Array.isArray((pageRes as any)?.data) ? (pageRes as any).data : [];
    const operatorIds = new Set((operators.value || []).map((op: any) => op.id));
    pages.value = pageList.filter((page: any) =>
      Array.isArray(page?.users) && page.users.some((u: any) => operatorIds.has(u.fb_account_id)),
    );

    if (!pages.value.find((p: any) => p.id === localFormData.value.page)) {
      localFormData.value.page = pages.value[0]?.id ?? null;
      adSettings.value.page = localFormData.value.page;
    }

    await loadFormsByPageId(localFormData.value.page);
  } catch (_) {
    operators.value = [];
    pages.value = [];
    pixels.value = [];
    forms.value = [];
  }
};

// 监听 formData 变化，同步 adSettings 和 localFormData（模板/草稿回填）
watch(
  () => props.formData,
  (newVal) => {
    syncingFromParent.value = true;
    const incoming = (newVal || {}) as Record<string, unknown>;
    const next = {
      purchaseType: 'auction',
      creationMode: 'standard',
      advancedCatalogAds: false,
      conversionLocation: 'app',
      adAccountIds: [] as string[],
      ...incoming,
    } as typeof localFormData.value;
    if (!next.adAccountIds?.length && next.adAccount) {
      next.adAccountIds = [next.adAccount];
    } else if (next.adAccountIds?.length && !next.adAccount) {
      next.adAccount = next.adAccountIds[0] ?? null;
    }
    // 父级若未带 objective（或异常空值），勿用空值覆盖当前选择，避免「点了应用推广又弹回销量」
    const hasObjective =
      Object.prototype.hasOwnProperty.call(incoming, 'objective') &&
      incoming.objective != null &&
      String(incoming.objective as string).trim() !== '';
    if (!hasObjective) {
      next.objective = localFormData.value.objective || 'OUTCOME_SALES';
    }
    let patchedParent = false;
    if (next.objective === 'APP_INSTALLS' && next.conversionLocation !== 'app') {
      next.conversionLocation = 'app';
      patchedParent = true;
    }
    localFormData.value = next;
    if (patchedParent) {
      emit('update:form-data', { ...next } as Props['formData']);
    }
    adSettings.value = {
      operator: newVal?.operator,
      page: newVal?.page,
      pixel: newVal?.pixel,
      form: newVal?.form,
    };
    nextTick(() => {
      syncingFromParent.value = false;
    });
  },
  { deep: true, immediate: true },
);

watch(
  localFormData,
  () => {
    if (syncingFromParent.value) return;
    emitStepOneToParent();
  },
  { deep: true },
);

onMounted(() => {
  loadAdAccounts();
});
</script>

<style lang="less" scoped>
.step-one-account {
  .ad-account-field-wrap {
    position: relative;
    width: 100%;
  }

  .ad-account-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    min-height: 32px;
    padding: 4px 11px;
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    text-align: left;
    width: 100%;
    font: inherit;
    outline: none;
    transition: border-color 0.2s;

    &:hover {
      border-color: #4096ff;
    }

    &:focus-visible {
      border-color: #4096ff;
      box-shadow: 0 0 0 2px rgba(5, 145, 255, 0.1);
    }
  }

  .ad-account-trigger-text {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: rgba(0, 0, 0, 0.88);
    font-size: 14px;
  }

  .ad-account-trigger-arrow {
    flex-shrink: 0;
    color: rgba(0, 0, 0, 0.45);
    font-size: 12px;
  }

  .ad-account-picker-panel {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    z-index: 100;
    background: #fff;
    border-radius: 8px;
    box-shadow:
      0 3px 6px -4px rgba(0, 0, 0, 0.12),
      0 6px 16px 0 rgba(0, 0, 0, 0.08);
    overflow: hidden;
  }

  .ad-account-picker {
    display: flex;
    gap: 16px;
    width: 100%;
    min-height: 320px;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    overflow: hidden;
    background: #fafafa;
  }

  .selected-chips {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .picker-left {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-right: 1px solid #f0f0f0;
  }

  .picker-search {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
  }

  .picker-scroll {
    flex: 1;
    overflow: auto;
    padding: 0 0 12px;
    max-height: 420px;
  }

  .picker-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 24px 16px;
    justify-content: center;
    color: #8c8c8c;
    font-size: 13px;
  }

  .picker-empty {
    padding: 24px 16px;
    text-align: center;
    color: #8c8c8c;
    font-size: 13px;

    &.subtle {
      padding: 16px;
    }
  }

  .currency-block {
    margin-top: 8px;
  }

  .currency-head {
    padding: 8px 12px;
    font-weight: 600;
    font-size: 13px;
    color: #262626;
    background: #f5f5f5;
    border-top: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
  }

  .account-row {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border: none;
    border-bottom: 1px solid #f5f5f5;
    background: #fff;
    cursor: pointer;
    text-align: left;
    font: inherit;
    transition: background 0.15s;

    &:hover {
      background: #f9f9f9;
    }

    &.selected {
      background: #e6f7ff;

      .account-name {
        color: #1890ff;
        font-weight: 500;
      }
    }
  }

  .account-row-main {
    flex: 1;
    min-width: 0;
  }

  .account-name {
    font-size: 14px;
    color: #262626;
    line-height: 1.35;
  }

  .account-id {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 2px;
  }

  .account-status {
    flex-shrink: 0;
    font-size: 12px;
    color: #52c41a;
  }

  .row-check {
    flex-shrink: 0;
    color: #1890ff;
    font-size: 14px;
  }

  .picker-right {
    width: 260px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    background: #fff;
    padding: 12px;
  }

  .picker-right-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f0;
  }

  .picker-count {
    font-size: 13px;
    color: #595959;
  }

  .picker-clear {
    padding: 0;
    height: auto;
  }

  .picker-selected-list {
    flex: 1;
    min-height: 80px;
  }

  .selected-chip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 10px;
    background: #f5f5f5;
    border-radius: 4px;
    font-size: 13px;
  }

  .selected-chip-name {
    flex: 1;
    min-width: 0;
    word-break: break-word;
  }

  .selected-chip-close {
    cursor: pointer;
    color: #8c8c8c;
    flex-shrink: 0;

    &:hover {
      color: #ff4d4f;
    }
  }

  @media (max-width: 900px) {
    .ad-account-picker {
      flex-direction: column;
      min-height: auto;
    }

    .picker-left {
      border-right: none;
      border-bottom: 1px solid #f0f0f0;
    }

    .picker-right {
      width: 100%;
    }
  }

  .template-badge {
    margin-bottom: 16px;
  }

  .operator-id {
    font-size: 12px;
    color: #666;
    margin-top: 2px;
  }

  .section {
    margin-bottom: 32px;

    .section-title {
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 16px;
      color: #262626;
    }
  }

  .purchase-type-group {
    width: 100%;

    :deep(.ant-radio-button-wrapper) {
      flex: 1;
      text-align: center;
      height: 40px;
      line-height: 40px;
      border-color: #d9d9d9;

      &:first-child {
        border-radius: 4px 0 0 4px;
      }

      &:last-child {
        border-radius: 0 4px 4px 0;
      }

      &.ant-radio-button-wrapper-checked {
        background: #52c41a;
        border-color: #52c41a;
        color: #fff;

        .radio-label {
          color: #fff;
        }
      }
    }
  }

  .objective-group {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 8px;

    .objective-option {
      display: block;
      width: 100%;
      margin: 0;
      padding: 0;
      border: none;
      background: transparent;
      text-align: left;
      font: inherit;
      cursor: pointer;
      outline: none;
      -webkit-tap-highlight-color: transparent;

      .objective-card {
        display: flex;
        align-items: flex-start;
        padding: 10px 12px;
        border: 2px solid #d9d9d9;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;

        &:hover {
          border-color: #1890ff;
        }

        .objective-check {
          width: 20px;
          margin-right: 10px;
          flex-shrink: 0;
          .check-icon {
            font-size: 18px;
            color: #52c41a;
          }
        }

        .objective-icon {
          width: 32px;
          height: 32px;
          display: flex;
          align-items: center;
          justify-content: center;
          margin-right: 12px;
          font-size: 18px;
          color: #52c41a;
        }

        .objective-content {
          flex: 1;
          min-width: 0;
          position: relative;

          .objective-title {
            font-size: 14px;
            font-weight: 500;
            color: #262626;
            line-height: 1.3;
          }

          .objective-description {
            display: none;
            position: absolute;
            left: 0;
            top: calc(100% + 6px);
            z-index: 10;
            min-width: 180px;
            max-width: 260px;
            padding: 6px 8px;
            background: #ffffff;
            border: 1px solid #d9d9d9;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            font-size: 12px;
            color: #8c8c8c;
            line-height: 1.35;
            word-break: break-word;
          }
        }

        &:hover {
          .objective-content {
            .objective-description {
              display: block;
            }
          }
        }
      }

      &.active {
        .objective-card {
          border-color: #52c41a;
          background: #f6ffed;
        }
      }
    }
  }
}
</style>

