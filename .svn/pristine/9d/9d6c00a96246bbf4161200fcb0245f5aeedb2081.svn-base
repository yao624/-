<template>
  <div class="step-region" :class="{ 'step-region--compact': compact }">
    <h3 v-if="!compact" class="section-title">{{ t('地区组') }}</h3>
    <a-form layout="vertical">
      <a-form-item v-if="!compact" :label="t('地区组')">
        <div class="region-group-toolbar">
          <a-space wrap align="center">
            <a-checkbox v-model:checked="local.useExisting">
              {{ t('选择已有地区组') }}
            </a-checkbox>
            <a-button
              v-if="local.useExisting"
              type="link"
              class="choose-existing-link"
              @click="openSavedGroupModal"
            >
              {{ t('从列表选择') }}
            </a-button>
            <a-tag v-if="local.useExisting && selectedSavedGroupName" color="blue">
              {{ selectedSavedGroupName }}
            </a-tag>
            <a-button
              v-if="local.useExisting"
              type="dashed"
              class="region-group-add-btn"
              :title="t('新建地区组')"
              @click="startNewRegionGroup"
            >
              <template #icon><plus-outlined /></template>
            </a-button>
            <a-button v-if="local.useExisting && local.regionGroupId" type="link" @click="editSelectedRegionGroup">
              {{ t('编辑当前地区组') }}
            </a-button>
            <template v-else>
              <a-tag color="processing">{{ local.regionGroupId ? t('编辑地区组') : t('新建地区组') }}</a-tag>
              <a-button size="small" @click="cancelNewRegionGroup">{{ t('取消') }}</a-button>
            </template>
          </a-space>
        </div>
        <div v-if="!local.useExisting" class="region-group-create-panel">
          <a-space wrap class="region-group-new-row">
            <a-input
              v-model:value="local.regionGroupName"
              :placeholder="t('地区组名称')"
              style="width: 220px"
            />
            <a-button type="primary" :loading="saveGroupLoading" @click="saveCurrentAsRegionGroup">
              {{ local.regionGroupId ? t('保存修改') : t('保存为地区组') }}
            </a-button>
          </a-space>
          <div class="bind-accounts-block region-group-tags-field">
            <div class="bind-accounts-label">{{ t('标签') }}</div>
            <a-select
              v-model:value="local.region_group_tags"
              mode="tags"
              :placeholder="t('输入标签后回车添加；可多选，逗号可分隔')"
              style="width: 100%; max-width: 480px"
              :token-separators="[',']"
              allow-clear
            />
            <div class="hint">{{ t('保存地区组时一并写入；在「从列表选择」弹窗可用标签筛选。') }}</div>
          </div>
          <div class="bind-accounts-block">
            <div class="bind-accounts-label">{{ t('绑定广告账户') }}</div>
            <a-select
              v-model:value="local.fb_ad_account_ids"
              mode="multiple"
              :placeholder="t('不选表示不限账户；仅所选账户可在下拉中选用本组')"
              style="width: 100%; max-width: 480px"
              allow-clear
              show-search
              :filter-option="filterGroupOption"
              :loading="adAccountsLoading"
              :options="adAccountSelectOptions"
            />
            <div class="hint">{{ t('用于区分不同账户的投放地区：留空则所有账户均可选用本组。') }}</div>
          </div>
        </div>
      </a-form-item>

      <a-alert v-if="!compact" type="info" show-icon class="meta-geo-doc-alert">
        <template #message>
          <span>
            {{
              t(
                '地理与 Meta Ad Set 的 targeting.geo_locations / excluded_geo_locations 一致：国家为 ISO 3166-1 alpha-2；大区 key 来自 Targeting Search API。',
              )
            }}
          </span>
          <a
            class="meta-doc-link"
            href="https://developers.facebook.com/docs/marketing-api/audiences/reference/basic-targeting#location"
            target="_blank"
            rel="noopener noreferrer"
            >{{ t('Basic targeting — Location（官方）') }}</a
          >
        </template>
      </a-alert>
      <a-alert v-else type="info" show-icon class="meta-geo-doc-alert compact-alert">
        <template #message>
          <a
            class="meta-doc-link"
            href="https://developers.facebook.com/docs/marketing-api/audiences/reference/basic-targeting#location"
            target="_blank"
            rel="noopener noreferrer"
            >{{ t('Location 字段说明（Meta）') }}</a
          >
        </template>
      </a-alert>

      <a-form-item :label="t('地区') + ' *'">
        <div class="geo-toolbar">
          <a-button type="link" size="small" @click="bulkModalVisible = true">{{ t('批量导入') }}</a-button>
          <a-button type="link" size="small" danger @click="clearAllGeo">{{ t('清除') }}</a-button>
        </div>
        <div class="region-picker">
          <div class="picker-col picker-search">
            <a-input
              v-model:value="regionSearch"
              :placeholder="t('搜索国家/地区/大区 key')"
              allow-clear
              @update:value="onRegionSearchChange"
            />
            <div class="picker-hint">{{ t('搜索结果可直接加入定向或排除') }}</div>
            <div v-if="searchLoading" class="picker-loading"><a-spin /></div>
            <div v-else class="result-list">
              <div v-for="row in searchRows" :key="row.uid" class="result-row">
                <span class="result-name">{{ row.displayName }}</span>
                <span class="result-actions">
                  <a-button size="small" type="link" @click="addTarget(row)">{{ t('定向') }}</a-button>
                  <a-button size="small" type="link" danger :disabled="!canUseExcludeTab" @click="addExclude(row)">{{ t('排除') }}</a-button>
                </span>
              </div>
              <a-empty v-if="!searchRows.length && regionSearch.trim()" :description="t('暂无结果')" />
            </div>
          </div>
          <div class="picker-col picker-selected">
            <div class="selected-toolbar">
              <span class="selected-title">{{ t('已选') }}</span>
              <a-button type="link" size="small" @click="bulkModalVisible = true">{{ t('批量导入') }}</a-button>
              <a-button type="link" size="small" danger @click="clearCurrentTab">{{ t('清除') }}</a-button>
            </div>
            <a-tabs v-model:activeKey="selectedTab" size="small">
              <a-tab-pane :key="'target'" :tab="`${t('定向')}(${targetTotal})`">
                <div class="selected-tags">
                  <a-tag
                    v-for="(c, idx) in allTargetRows"
                    :key="'tg-' + c.kind + c.key + idx"
                    closable
                    @close.prevent="removeTargetRow(c)"
                  >
                    {{ c.name }}
                  </a-tag>
                  <span v-if="targetTotal === 0" class="empty-hint">{{ t('暂无定向') }}</span>
                </div>
              </a-tab-pane>
              <a-tab-pane :key="'exclude'" :tab="`${t('排除')}(${excludeTotal})`">
                <div class="selected-tags">
                  <a-tag
                    v-for="(c, idx) in allExcludeRows"
                    :key="'ex-' + c.kind + c.key + idx"
                    closable
                    @close.prevent="removeExcludeRow(c)"
                  >
                    {{ c.name }}
                  </a-tag>
                  <span v-if="excludeTotal === 0" class="empty-hint">{{ t('暂无排除') }}</span>
                </div>
              </a-tab-pane>
            </a-tabs>
          </div>
        </div>
      </a-form-item>

      <a-alert v-if="showFinancialDsaFields" type="info" show-icon class="eu-tip">
        {{ t('对于任何定位欧盟地区受众的广告组，你需要指明广告组的受益人或组织，以及广告组的赞助方。') }}
        <a-button type="link" size="small" href="https://www.facebook.com/business/help" target="_blank" rel="noopener noreferrer">
          {{ t('详细了解') }}
        </a-button>
      </a-alert>

      <a-form-item :label="t('在什么地区投放金融产品和服务类广告')">
        <a-select
          v-model:value="local.financialRegion"
          :placeholder="t('请选择')"
          style="width: 100%; max-width: 480px"
          allow-clear
          :options="financialRegionOptions"
        />
      </a-form-item>

      <template v-for="item in beneficiaryItems" :key="item.key">
        <a-form-item v-if="showFinancialDsaFields">
          <a-row :gutter="16">
            <a-col :span="12">
              <div class="sub-label">{{ t('受益方') }}（{{ item.label }}）</div>
              <a-select
                v-model:value="local[item.benefitKey]"
                :placeholder="t('请选择或输入后选择')"
                style="width: 100%; margin-top: 4px"
                allow-clear
                show-search
                :filter-option="filterDsaOption"
                :options="dsaEntityOptions"
              />
            </a-col>
            <a-col :span="12">
              <div class="sub-label">{{ t('赞助方') }}（{{ item.label }}）</div>
              <a-select
                v-model:value="local[item.sponsorKey]"
                :placeholder="t('请选择或输入后选择')"
                style="width: 100%; margin-top: 4px"
                allow-clear
                show-search
                :filter-option="filterDsaOption"
                :options="dsaEntityOptions"
              />
            </a-col>
          </a-row>
        </a-form-item>
      </template>
    </a-form>

    <a-modal
      v-model:open="savedGroupModalVisible"
      :title="t('选择已有地区组')"
      width="900px"
      :footer="null"
      destroy-on-close
    >
      <div class="saved-group-modal-toolbar">
        <a-input
          v-model:value="savedGroupKeyword"
          :placeholder="t('地区组：搜索...')"
          allow-clear
          style="width: 260px"
          @change="onSavedGroupKeywordChange"
        />
        <a-select
          v-model:value="savedGroupTag"
          :placeholder="t('标签：筛选')"
          style="width: 220px"
          allow-clear
          show-search
          :options="savedGroupTagOptions"
          @change="onSavedGroupTagFilterChange"
        />
      </div>
      <a-table
        :columns="savedGroupColumns"
        :data-source="savedGroupsForAccount"
        :loading="groupsLoading"
        row-key="id"
        size="small"
        :pagination="savedGroupPagination"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'action'">
            <a-button type="link" size="small" @click="onSavedGroupSelectChange(record.id)">
              {{ t('使用') }}
            </a-button>
          </template>
          <template v-else-if="column.key === 'tags'">
            {{ formatSavedGroupTags(record.tags) }}
          </template>
        </template>
      </a-table>
      <div class="saved-group-modal-footer">
        <a-button @click="savedGroupModalVisible = false">{{ t('取消') }}</a-button>
        <a-button type="primary" :disabled="!local.regionGroupId" @click="savedGroupModalVisible = false">
          {{ t('确定') }}
        </a-button>
      </div>
    </a-modal>

    <a-modal
      v-model:open="bulkModalVisible"
      :title="t('批量导入')"
      :ok-text="t('确定')"
      @ok="applyBulkImport"
      @cancel="bulkModalVisible = false"
    >
      <a-tabs v-model:activeKey="bulkImportTab" class="bulk-import-tabs">
        <a-tab-pane key="target" :tab="t('定向')" />
        <a-tab-pane key="exclude" :tab="t('排除')" />
      </a-tabs>
      <a-textarea
        v-model:value="bulkText"
        :rows="7"
        :placeholder="t('支持以名称或2位地区代码批量导入，以英文逗号(,)回车区隔')"
      />
      <div class="bulk-help">{{ t('系统会自动过滤已选定的地区') }}</div>
    </a-modal>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, computed, nextTick, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import {
  queryMetaAdCreationRegionGroupsApi,
  createMetaAdCreationRegionGroupApi,
  updateMetaAdCreationRegionGroupApi,
} from '@/api/meta_ad_creation_region_groups';
import { queryFB_AD_AccountsApi } from '@/api/fb_ad_accounts';
import { searchCountries, searchRegions } from '@/api/geo_location';
import debounce from '@/utils/debonce';
import { localStorage } from '@/utils/local-storage';
import { STORAGE_TOKEN_KEY } from '@/store/app';
import { PlusOutlined } from '@ant-design/icons-vue';

const { t } = useI18n();

type GeoKind = 'country' | 'region';

interface GeoItem {
  key: string;
  name: string;
  kind: GeoKind;
}

interface SearchRow {
  uid: string;
  key: string;
  name: string;
  kind: GeoKind;
  displayName: string;
}

const props = defineProps<{
  formData: Record<string, any>;
  /** 嵌入定向包内时隐藏「地区组」标题与保存/载入地区组，仅保留地理与合规字段 */
  compact?: boolean;
  /** 基础设置中选中的广告账户，用于筛选「已有地区组」 */
  adAccountId?: string | null;
}>();
const emit = defineEmits<{ (e: 'update:form-data', v: Record<string, any>): void }>();

function defaultState() {
  return {
    regionGroupName: '',
    useExisting: true,
    regionGroupId: null as string | null,
    /** 与后端 POST/PUT 字段 tags 对应，避免与定向包内 tags 混淆 */
    region_group_tags: [] as string[],
    regionSearch: '',
    fb_ad_account_ids: [] as string[],
    tab: 'target' as 'target' | 'exclude',
    countries_included: [] as { key: string; name: string }[],
    countries_excluded: [] as { key: string; name: string }[],
    regions_included: [] as { key: string; name: string }[],
    regions_excluded: [] as { key: string; name: string }[],
    cities_included: [] as { key: string; name: string; country_name?: string; radius?: number; distance_unit?: 'mile' | 'kilometer' }[],
    cities_excluded: [] as { key: string; name: string; country_name?: string; radius?: number; distance_unit?: 'mile' | 'kilometer' }[],
    financialRegion: undefined as string | undefined,
    benefitTw: undefined as string | undefined,
    sponsorTw: undefined as string | undefined,
    benefitAu: undefined as string | undefined,
    sponsorAu: undefined as string | undefined,
    benefitSg: undefined as string | undefined,
    sponsorSg: undefined as string | undefined,
    benefitTh: undefined as string | undefined,
    sponsorTh: undefined as string | undefined,
    benefitEu: undefined as string | undefined,
    sponsorEu: undefined as string | undefined,
  };
}

function mergeFromProps(incoming: Record<string, any> | undefined) {
  const d = defaultState();
  const v = incoming && typeof incoming === 'object' ? incoming : {};
  const ci = v.countries_included ?? v.countriesIncluded;
  const ce = v.countries_excluded ?? v.countriesExcluded;
  const ri = v.regions_included ?? v.regionsIncluded;
  const re = v.regions_excluded ?? v.regionsExcluded;
  const cyi = v.cities_included ?? v.citiesIncluded;
  const cye = v.cities_excluded ?? v.citiesExcluded;
  const fba = v.fb_ad_account_ids ?? v.fbAdAccountIds;
  const rgt = v.region_group_tags ?? v.regionGroupTags;
  return {
    ...d,
    ...v,
    region_group_tags: Array.isArray(rgt) ? rgt.map(String) : [...d.region_group_tags],
    fb_ad_account_ids: Array.isArray(fba) ? [...fba] : [...d.fb_ad_account_ids],
    countries_included: Array.isArray(ci) ? [...ci] : [...d.countries_included],
    countries_excluded: Array.isArray(ce) ? [...ce] : [...d.countries_excluded],
    regions_included: Array.isArray(ri) ? [...ri] : [...d.regions_included],
    regions_excluded: Array.isArray(re) ? [...re] : [...d.regions_excluded],
    cities_included: Array.isArray(cyi) ? [...cyi] : [...d.cities_included],
    cities_excluded: Array.isArray(cye) ? [...cye] : [...d.cities_excluded],
  };
}

/** 示例地理词库：与 FacebookCreateAdsetV2 中 countries / regions 结构一致 */
/** 提交前统一为 { key, name }，兼容 code / id 等字段 */
function normalizeGeoList(arr: unknown): { key: string; name: string }[] {
  if (!Array.isArray(arr)) return [];
  const rows: { key: string; name: string }[] = [];
  for (const item of arr) {
    if (!item || typeof item !== 'object') continue;
    const o = item as Record<string, unknown>;
    const rawKey = o.key ?? o.code ?? o.id;
    const key = rawKey != null ? String(rawKey).trim() : '';
    if (!key) continue;
    const rawName = o.name ?? o.label;
    const name = rawName != null ? String(rawName).trim() : '';
    rows.push({ key, name: name || key });
  }
  return rows;
}

function formatAxiosErrorMessage(err: unknown): string {
  const e = err as { message?: string; response?: { data?: unknown } };
  const d = e?.response?.data;
  if (d == null) return e?.message ? String(e.message) : '';
  if (typeof d === 'string') return d.slice(0, 300);
  if (typeof d !== 'object') return '';
  const obj = d as Record<string, unknown>;
  if (obj.message != null && String(obj.message).trim()) return String(obj.message);
  const errs = obj.errors;
  if (errs && typeof errs === 'object') {
    for (const v of Object.values(errs)) {
      if (Array.isArray(v) && v[0] != null) return String(v[0]);
      if (typeof v === 'string' && v) return v;
    }
  }
  return '';
}

const local = ref(mergeFromProps(props.formData));

const syncingFromParent = ref(false);
const groupsLoading = ref(false);
const saveGroupLoading = ref(false);
const savedGroups = ref<any[]>([]);
const savedGroupModalVisible = ref(false);
const savedGroupKeyword = ref('');
const savedGroupTag = ref<string | undefined>(undefined);
const savedGroupTagOptions = ref<{ label: string; value: string }[]>([]);
const bulkModalVisible = ref(false);
const bulkImportTab = ref<'target' | 'exclude'>('target');
const bulkText = ref('');

const regionSearch = ref('');
const searchLoading = ref(false);
const searchRows = ref<SearchRow[]>([]);
const selectedTab = ref<'target' | 'exclude'>('target');

const beneficiaryItems = [
  { key: 'tw', label: t('台湾地区'), benefitKey: 'benefitTw' as const, sponsorKey: 'sponsorTw' as const },
  { key: 'au', label: t('澳大利亚'), benefitKey: 'benefitAu' as const, sponsorKey: 'sponsorAu' as const },
  { key: 'sg', label: t('新加坡'), benefitKey: 'benefitSg' as const, sponsorKey: 'sponsorSg' as const },
  { key: 'th', label: t('泰国'), benefitKey: 'benefitTh' as const, sponsorKey: 'sponsorTh' as const },
  { key: 'eu', label: t('欧盟'), benefitKey: 'benefitEu' as const, sponsorKey: 'sponsorEu' as const },
];

const financialRegionOptions = computed(() => [
  { label: t('无 / 不适用'), value: 'none' },
  { label: t('欧洲经济区 (EEA)'), value: 'eea' },
  { label: t('台湾'), value: 'taiwan' },
  { label: t('澳大利亚'), value: 'australia' },
  { label: t('新加坡'), value: 'singapore' },
  { label: t('泰国'), value: 'thailand' },
  { label: t('欧盟'), value: 'eu' },
  { label: t('印度'), value: 'india' },
  { label: t('印度尼西亚'), value: 'indonesia' },
  { label: t('其它（请在广告系列备注中说明）'), value: 'other' },
]);

const showFinancialDsaFields = computed(() => {
  const v = local.value.financialRegion;
  if (v === undefined || v === null) return false;
  return String(v) !== 'none';
});

const dsaEntityOptions = computed(() => {
  const labels = [
    t('本公司（广告主主体）'),
    t('关联公司 / 品牌方'),
    t('广告代理 / 服务商'),
    t('与公共主页一致'),
    t('其它（见商务管理平台设置）'),
  ];
  return labels.map((label) => ({ label, value: label }));
});

watch(
  showFinancialDsaFields,
  (show) => {
    if (show) return;
    // 隐藏时清空，避免你之前填过数据，后面选择“无/不适用”仍会被提交
    local.value.benefitTw = undefined;
    local.value.sponsorTw = undefined;
    local.value.benefitAu = undefined;
    local.value.sponsorAu = undefined;
    local.value.benefitSg = undefined;
    local.value.sponsorSg = undefined;
    local.value.benefitTh = undefined;
    local.value.sponsorTh = undefined;
    local.value.benefitEu = undefined;
    local.value.sponsorEu = undefined;
  },
  { immediate: true },
);

/** 按当前广告账户过滤：未绑定账户的地区组（空列表）对所有账户可见 */
const savedGroupsForAccount = computed(() => {
  const aid = props.adAccountId;
  return (savedGroups.value || []).filter((g: any) => {
    const ids = g.fbAdAccountIds ?? g.fb_ad_account_ids;
    if (!ids || !Array.isArray(ids) || ids.length === 0) return true;
    if (!aid) return true;
    return ids.includes(aid);
  });
});

const selectedSavedGroupName = computed(() => {
  const g = savedGroups.value.find((x: any) => x.id === local.value.regionGroupId);
  return g?.name || '';
});
const savedGroupColumns = computed(() => [
  { title: t('地区组名称'), dataIndex: 'name', key: 'name' },
  { title: t('操作'), key: 'action', width: 100 },
  { title: t('标签'), dataIndex: 'tags', key: 'tags', width: 140 },
  { title: t('创建时间'), dataIndex: 'createdAtText', key: 'createdAt', width: 180 },
]);
const savedGroupPagination = computed(() => ({
  pageSize: 20,
  showSizeChanger: true,
  showTotal: (total: number) => `${t('共')} ${total} ${t('条')}`,
}));

const adAccountsLoading = ref(false);
const adAccounts = ref<{ id: string; name: string }[]>([]);

const adAccountSelectOptions = computed(() =>
  (adAccounts.value || []).map((a) => ({
    label: a.name || a.id,
    value: a.id,
  })),
);

async function loadAdAccountsForBind() {
  if (props.compact) return;
  adAccountsLoading.value = true;
  try {
    // 测试环境：临时代码固定 bm_id
    const TEST_BM_ID = '1476379370819673';
    const res: any = await queryFB_AD_AccountsApi({
      'with-campaign': false,
      is_archived: false,
      pageSize: 500,
      pageNo: 1,
      bm_ids: [TEST_BM_ID],
    });
    const list = res?.data ?? [];
    adAccounts.value = Array.isArray(list)
      ? list
          .map((x: any) => ({
            // 与「基础设置」广告账户下拉一致，使用内部 id，便于与 stepOne.adAccount 匹配
            id: String(x.id ?? x.source_id ?? ''),
            name: String(x.name ?? x.id ?? ''),
          }))
          .filter((x) => x.id)
      : [];
  } catch {
    adAccounts.value = [];
  } finally {
    adAccountsLoading.value = false;
  }
}

onMounted(() => {
  loadAdAccountsForBind();
  if (!props.compact) {
    loadSavedGroups();
    refreshSavedGroupTagOptions();
  }
});

const targetTotal = computed(() => local.value.countries_included.length + local.value.regions_included.length);
const excludeTotal = computed(() => local.value.countries_excluded.length + local.value.regions_excluded.length);
const allTargetRows = computed(() => [
  ...local.value.countries_included.map((x) => ({ ...x, kind: 'country' as const })),
  ...local.value.regions_included.map((x) => ({ ...x, kind: 'region' as const })),
]);
const allExcludeRows = computed(() => [
  ...local.value.countries_excluded.map((x) => ({ ...x, kind: 'country' as const })),
  ...local.value.regions_excluded.map((x) => ({ ...x, kind: 'region' as const })),
]);

/** 须先有定向，再允许使用排除（与 Meta 逻辑一致：先有 geo_locations 再配 excluded） */
const canUseExcludeTab = computed(() => targetTotal.value > 0);

let searchReqToken = 0;
const runRegionSearch = debounce(async () => {
  const q = regionSearch.value.trim();
  if (!q) {
    searchRows.value = [];
    return;
  }
  const token = ++searchReqToken;
  searchLoading.value = true;
  try {
    const [countryRes, regionRes]: any[] = await Promise.all([searchCountries(q), searchRegions(q)]);
    if (token !== searchReqToken) return;
    const countryPayload = Array.isArray(countryRes) ? countryRes : Array.isArray(countryRes?.data) ? countryRes.data : [];
    const regionPayload = Array.isArray(regionRes) ? regionRes : Array.isArray(regionRes?.data) ? regionRes.data : [];
    const countries = normalizeGeoList(countryPayload);
    const regions = normalizeGeoList(regionPayload);
    const seen = new Set<string>();
    const out: SearchRow[] = [];
    for (const x of countries) {
      const uid = `country:${x.key}`;
      if (seen.has(uid)) continue;
      seen.add(uid);
      out.push({ uid, key: x.key, name: x.name, kind: 'country', displayName: `${x.name} (${t('国家')})` });
    }
    for (const x of regions) {
      const uid = `region:${x.key}`;
      if (seen.has(uid)) continue;
      seen.add(uid);
      out.push({ uid, key: x.key, name: x.name, kind: 'region', displayName: `${x.name} (${t('大区/市场')})` });
    }
    searchRows.value = out;
  } catch {
    searchRows.value = [];
    message.warning(t('定向搜索请求失败'));
  } finally {
    if (token === searchReqToken) searchLoading.value = false;
  }
}, 300);

function onRegionSearchChange() {
  runRegionSearch();
}


function filterGroupOption(input: string, option: any) {
  return String(option?.label ?? '')
    .toLowerCase()
    .includes(input.toLowerCase());
}

function filterDsaOption(input: string, option: any) {
  return String(option?.label ?? '')
    .toLowerCase()
    .includes(input.toLowerCase());
}

function pushUnique(list: { key: string; name: string }[], row: { key: string; name: string }) {
  if (list.some((x) => x.key === row.key)) return false;
  list.push({ ...row });
  return true;
}

function addToTargetInclude(item: GeoItem): boolean {
  const row = { key: item.key, name: item.name };
  const list = item.kind === 'country' ? local.value.countries_included : local.value.regions_included;
  if (pushUnique(list, row)) {
    message.success(t('已加入定向'));
    return true;
  }
  message.info(t('已在定向列表中'));
  return false;
}

function addToExclude(item: GeoItem): boolean {
  const row = { key: item.key, name: item.name };
  const list = item.kind === 'country' ? local.value.countries_excluded : local.value.regions_excluded;
  if (pushUnique(list, row)) {
    message.success(t('已加入排除'));
    return true;
  }
  message.info(t('已在排除列表中'));
  return false;
}

function addTarget(row: SearchRow) {
  addToTargetInclude({ kind: row.kind, key: row.key, name: row.name });
}

function addExclude(row: SearchRow) {
  if (!canUseExcludeTab.value) {
    message.warning(t('请先完成定向（至少一个国家/大区），再设置排除'));
    return;
  }
  addToExclude({ kind: row.kind, key: row.key, name: row.name });
}

function removeTargetRow(row: { kind: GeoKind; key: string }) {
  if (row.kind === 'country') {
    local.value.countries_included = local.value.countries_included.filter((x) => x.key !== row.key);
  } else {
    local.value.regions_included = local.value.regions_included.filter((x) => x.key !== row.key);
  }
}

function removeExcludeRow(row: { kind: GeoKind; key: string }) {
  if (row.kind === 'country') {
    local.value.countries_excluded = local.value.countries_excluded.filter((x) => x.key !== row.key);
  } else {
    local.value.regions_excluded = local.value.regions_excluded.filter((x) => x.key !== row.key);
  }
}

function clearCurrentTab() {
  if (selectedTab.value === 'target') {
    local.value.countries_included = [];
    local.value.regions_included = [];
    return;
  }
  local.value.countries_excluded = [];
  local.value.regions_excluded = [];
}


function clearAllGeo() {
  local.value.countries_included = [];
  local.value.countries_excluded = [];
  local.value.regions_included = [];
  local.value.regions_excluded = [];
  message.success(t('已清除'));
}

function parseBulkLine(line: string): GeoItem | null {
  const s = line.trim();
  if (!s) return null;
  if (s.toLowerCase().startsWith('region:')) {
    const rest = s.slice('region:'.length);
    const [k, ...nameParts] = rest.split(',');
    const key = (k || '').trim();
    const name = (nameParts.join(',') || key).trim() || key;
    if (!key) return null;
    return { kind: 'region', key, name };
  }
  if (s.includes(',')) {
    const [k, ...nameParts] = s.split(',');
    const key = (k || '').trim().toUpperCase();
    const name = (nameParts.join(',') || key).trim();
    if (!key) return null;
    return { kind: 'country', key, name };
  }
  const key = s.toUpperCase();
  return { kind: 'country', key, name: key };
}

function applyBulkImport() {
  const lines = bulkText.value.split(/\r?\n/);
  let success = 0;
  for (const line of lines) {
    const item = parseBulkLine(line);
    if (!item) continue;
    const ok =
      bulkImportTab.value === 'target'
        ? addToTargetInclude(item)
        : addToExclude(item);
    if (ok) success += 1;
  }
  bulkModalVisible.value = false;
  bulkText.value = '';
  bulkImportTab.value = 'target';
  message.success(success ? `${t('已处理')} ${success} ${t('条')}` : t('未解析到有效行'));
}

function hasAccessToken(): boolean {
  return Boolean(localStorage.get(STORAGE_TOKEN_KEY));
}

async function refreshSavedGroupTagOptions() {
  if (!hasAccessToken()) {
    savedGroupTagOptions.value = [];
    return;
  }
  try {
    const res: any = await queryMetaAdCreationRegionGroupsApi();
    const list = res?.data ?? [];
    const set = new Set<string>();
    for (const g of list) {
      const tags = g?.tags;
      if (Array.isArray(tags)) {
        for (const x of tags) {
          const s = String(x).trim();
          if (s) set.add(s);
        }
      }
    }
    savedGroupTagOptions.value = [...set].sort().map((s) => ({ label: s, value: s }));
  } catch {
    savedGroupTagOptions.value = [];
  }
}

async function loadSavedGroups(q?: string, opts?: { notifyNoToken?: boolean }) {
  if (!hasAccessToken()) {
    savedGroups.value = [];
    if (opts?.notifyNoToken) {
      message.warning(t('未登录或登录已失效，请重新登录后再加载地区组'));
    }
    return;
  }
  groupsLoading.value = true;
  try {
    const kw = q != null ? String(q).trim() : '';
    const params: Record<string, string> = {};
    if (kw) params.q = kw;
    const tag = (savedGroupTag.value ?? '').trim();
    if (tag) params.tag = tag;
    const res: any = await queryMetaAdCreationRegionGroupsApi(
      Object.keys(params).length ? params : undefined,
    );
    savedGroups.value = res?.data ?? [];
  } catch (e: unknown) {
    savedGroups.value = [];
    const status = (e as { response?: { status?: number } })?.response?.status;
    if (status === 401) {
      message.error(t('登录已失效，请重新登录后再试'));
    } else {
      message.warning(t('加载地区组列表失败，可稍后重试'));
    }
  } finally {
    groupsLoading.value = false;
  }
}

async function openSavedGroupModal() {
  savedGroupModalVisible.value = true;
  await refreshSavedGroupTagOptions();
  await loadSavedGroups(savedGroupKeyword.value || undefined, { notifyNoToken: true });
}

const onSavedGroupKeywordChange = debounce(() => {
  const q = savedGroupKeyword.value;
  const keyword = q.trim();
  loadSavedGroups(keyword || undefined);
}, 300);

const onSavedGroupTagFilterChange = debounce(() => {
  const q = savedGroupKeyword.value?.trim() || '';
  loadSavedGroups(q || undefined);
}, 300);

function formatSavedGroupTags(tags: unknown) {
  if (!Array.isArray(tags) || tags.length === 0) return '—';
  return tags.map(String).join(', ');
}

function applySavedGroupPayload(g: any) {
  local.value.regionGroupName = g.name || '';
  const accIds = g.fbAdAccountIds ?? g.fb_ad_account_ids;
  local.value.fb_ad_account_ids = Array.isArray(accIds) ? [...accIds] : [];
  const tg = g.tags ?? g.region_group_tags;
  local.value.region_group_tags = Array.isArray(tg) ? tg.map(String) : [];
  local.value.countries_included = Array.isArray(g.countriesIncluded) ? [...g.countriesIncluded] : [];
  local.value.countries_excluded = Array.isArray(g.countriesExcluded) ? [...g.countriesExcluded] : [];
  local.value.regions_included = Array.isArray(g.regionsIncluded) ? [...g.regionsIncluded] : [];
  local.value.regions_excluded = Array.isArray(g.regionsExcluded) ? [...g.regionsExcluded] : [];
}

function onSavedGroupSelectChange(id: string | null | undefined) {
  if (id == null || id === '') return;
  local.value.useExisting = true;
  const g = savedGroups.value.find((x: any) => x.id === id);
  if (!g) return;
  local.value.regionGroupId = id;
  applySavedGroupPayload(g);
  message.success(t('已载入地区组'));
  savedGroupModalVisible.value = false;
}

/** 点击「+」：进入新建流程，可多次保存为不同地区组 */
function startNewRegionGroup() {
  local.value.useExisting = false;
  local.value.regionGroupId = null;
  local.value.regionGroupName = '';
  local.value.region_group_tags = [];
  local.value.fb_ad_account_ids = [];
}

function editSelectedRegionGroup() {
  if (!local.value.regionGroupId) return;
  local.value.useExisting = false;
}

function cancelNewRegionGroup() {
  local.value.useExisting = true;
  if (!local.value.regionGroupId) {
    local.value.regionGroupName = '';
    local.value.region_group_tags = [];
    local.value.fb_ad_account_ids = [];
  }
}

async function saveCurrentAsRegionGroup() {
  const name = (local.value.regionGroupName || '').trim();
  if (!name) {
    message.warning(t('请先填写地区组名称'));
    return;
  }
  if (targetTotal.value === 0) {
    message.warning(t('请先添加定向（国家/大区），完成后再设置排除并保存'));
    return;
  }
  saveGroupLoading.value = true;
  try {
    const isEdit = Boolean(local.value.regionGroupId);
    const payload = {
      name,
      tags: Array.isArray(local.value.region_group_tags) ? [...local.value.region_group_tags] : [],
      fb_ad_account_ids: Array.isArray(local.value.fb_ad_account_ids) ? [...local.value.fb_ad_account_ids] : [],
      countries_included: normalizeGeoList(local.value.countries_included),
      countries_excluded: normalizeGeoList(local.value.countries_excluded),
      regions_included: normalizeGeoList(local.value.regions_included),
      regions_excluded: normalizeGeoList(local.value.regions_excluded),
    };
    const res: any = isEdit
      ? await updateMetaAdCreationRegionGroupApi(String(local.value.regionGroupId), payload)
      : await createMetaAdCreationRegionGroupApi(payload);
    const id = res?.data?.id;
    if (id) {
      local.value.regionGroupId = id;
      local.value.useExisting = true;
    }
    message.success(res?.message || (isEdit ? t('地区组已更新') : t('地区组已保存')));
    await loadSavedGroups();
    await refreshSavedGroupTagOptions();
  } catch (e: unknown) {
    const status = (e as { response?: { status?: number } })?.response?.status;
    if (status === 401) {
      message.error(t('登录已失效，请重新登录后再试'));
    } else {
      message.error(formatAxiosErrorMessage(e) || t('保存失败'));
    }
  } finally {
    saveGroupLoading.value = false;
  }
}

watch(
  () => props.formData,
  (v) => {
    syncingFromParent.value = true;
    local.value = mergeFromProps(v);
    nextTick(() => {
      syncingFromParent.value = false;
    });
  },
);

watch(
  () => [props.adAccountId, savedGroups.value, local.value.regionGroupId] as const,
  () => {
    const id = local.value.regionGroupId;
    if (!id || !savedGroups.value.length) return;
    if (!savedGroupsForAccount.value.some((g: any) => g.id === id)) {
      local.value.regionGroupId = null;
      message.info(t('当前广告账户与地区组绑定不匹配，已清空地区组选择'));
    }
  },
);

watch(
  () => local.value.useExisting,
  (useExisting) => {
    if (useExisting) {
      openSavedGroupModal();
      return;
    }
    local.value.regionGroupId = null;
  },
);

watch(
  local,
  (v) => {
    if (syncingFromParent.value) return;
    emit('update:form-data', { ...v });
  },
  { deep: true },
);
</script>

<style lang="less" scoped>
.section-title {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 16px;
  color: #262626;
}
.region-group-toolbar {
  max-width: 640px;
  margin-bottom: 16px;
}
.region-group-select {
  min-width: 280px;
  max-width: 480px;
  flex: 1;
}
.region-group-add-btn {
  min-width: 40px;
}
.choose-existing-link {
  padding-left: 0;
}
.region-group-tags-field {
  margin-top: 12px;
  margin-bottom: 4px;
  max-width: 520px;
}
.region-group-create-panel {
  margin-top: 12px;
  padding: 16px;
  background: #fafafa;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
}
.region-group-new-row {
  margin-bottom: 0;
}
.bind-accounts-block {
  margin-top: 16px;
}
.bind-accounts-label {
  font-size: 13px;
  font-weight: 500;
  color: #595959;
  margin-bottom: 8px;
}
.region-tabs {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.geo-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.region-picker {
  display: flex;
  gap: 16px;
  border: 1px solid #e8e8e8;
  border-radius: 6px;
  padding: 12px;
  background: #fafafa;
}

.picker-col {
  min-width: 0;
}

.picker-search {
  flex: 1.3;
}

.picker-selected {
  flex: 1;
  border: 1px solid #e8e8e8;
  border-radius: 6px;
  padding: 8px;
  background: #fff;
}

.picker-hint {
  font-size: 12px;
  color: #8c8c8c;
  margin: 6px 0;
}

.picker-loading {
  padding: 16px;
  text-align: center;
}

.result-list {
  max-height: 360px;
  overflow-y: auto;
}

.result-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 0;
  border-bottom: 1px solid #f0f0f0;
}

.result-name {
  min-width: 0;
  word-break: break-word;
}

.result-actions {
  flex-shrink: 0;
  white-space: nowrap;
}

.selected-toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.selected-title {
  font-weight: 500;
  margin-right: auto;
}

.selected-tags {
  min-height: 52px;
}

.empty-hint {
  color: #999;
  font-size: 12px;
}
.geo-tab-order-hint {
  margin-top: 8px;
  font-size: 12px;
  color: #8c8c8c;
  line-height: 1.5;
}
.meta-geo-doc-alert {
  margin-bottom: 16px;
}
.meta-geo-doc-alert.compact-alert {
  margin-bottom: 12px;
  padding: 8px 12px;
}
.meta-doc-link {
  margin-left: 8px;
}
.meta-geo-add-block {
  margin-bottom: 16px;
  padding: 12px;
  background: #fafafa;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
}
.meta-geo-add-title {
  font-size: 13px;
  font-weight: 500;
  color: #262626;
  margin-bottom: 8px;
}
.meta-geo-add-title-sp {
  margin-top: 12px;
}
.meta-geo-add-row {
  margin-bottom: 4px;
}
.hint {
  font-size: 12px;
  color: #8c8c8c;
  margin-bottom: 0;
  line-height: 1.5;
}
.eu-tip {
  margin-bottom: 16px;
}
.selected-panel {
  min-height: 80px;
  margin-bottom: 16px;
  padding: 8px 0;
}
.tag-block {
  margin-bottom: 12px;
}
.tag-title {
  font-size: 13px;
  color: #595959;
  margin-bottom: 6px;
}
.sub-label {
  font-size: 13px;
  color: #595959;
}
.bulk-help {
  font-size: 13px;
  color: #8c8c8c;
  margin-top: 10px;
}
.bulk-import-tabs {
  margin-top: -4px;
  margin-bottom: 10px;
}
.saved-group-modal-toolbar {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}
.saved-group-modal-footer {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-top: 16px;
}
.step-region--compact {
  .section-title {
    margin-bottom: 8px;
  }
}
</style>
