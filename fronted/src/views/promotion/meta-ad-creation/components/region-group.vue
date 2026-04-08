<template>
  <div class="region-group">
    <div class="region-group-header">
      <h2 class="section-title">{{ t('地区组') }}</h2>
      <div class="header-actions">
        <a-dropdown>
          <template #overlay>
            <a-menu>
              <a-menu-item @click="bulkModalVisible = true">{{ t('批量导入') }}</a-menu-item>
            </a-menu>
          </template>
          <a-button>{{ t('批量操作') }}</a-button>
        </a-dropdown>
        <a-button @click="handleClear">{{ t('清空') }}</a-button>
        <a-button type="primary" @click="handleAddNew">{{ t('+ 新增') }}</a-button>
        <span class="item-count">{{ currentItemCount }}/{{ maxItemCount }}</span>
      </div>
    </div>

    <a-form :model="local" layout="vertical">
      <a-form-item>
        <a-space wrap>
          <a-checkbox v-model:checked="local.selectExisting">{{ t('选择已有地区组') }}</a-checkbox>
          <a-button v-if="local.selectExisting" type="link" @click="openSavedGroupModal">{{ t('从列表选择') }}</a-button>
        </a-space>
      </a-form-item>

      <!-- 地区：左侧搜索列表 + 定向/排除 + 已选 -->
      <a-form-item :label="t('地区') + ' *'" required>
        <div class="region-picker">
          <div class="picker-col picker-search picker-search-wide">
            <a-input
              v-model:value="regionSearch"
              :placeholder="t('搜索...')"
              allow-clear
              @update:value="onRegionSearchChange"
            />
            <div class="picker-hint">{{ t('国家/大区来自 Targeting Search；请先添加定向再排除') }}</div>
            <div v-if="searchLoading" class="picker-loading"><a-spin /></div>
            <div v-else class="result-list">
              <div
                v-for="row in searchRows"
                :key="row.uid"
                class="result-row-flex"
              >
                <span class="result-name">{{ row.displayName }}</span>
                <span class="result-btns">
                  <a-button size="small" type="link" @click="addTarget(row)">{{ t('定向') }}</a-button>
                  <a-button
                    size="small"
                    type="link"
                    danger
                    :disabled="!canUseExclude"
                    @click="addExclude(row)"
                  >
                    {{ t('排除') }}
                  </a-button>
                </span>
              </div>
              <a-empty v-if="!searchRows.length && regionSearch.trim()" :description="t('暂无数据')" />
            </div>
          </div>
          <div class="picker-col picker-selected">
            <div class="selected-toolbar">
              <span class="selected-title">{{ t('已选') }}</span>
              <a-button type="link" size="small" @click="bulkModalVisible = true">{{ t('批量导入') }}</a-button>
              <a-button type="link" size="small" danger @click="handleClearSelected">{{ t('清除') }}</a-button>
            </div>
            <a-tabs v-model:activeKey="selectedTab" size="small">
              <a-tab-pane :key="'target'" :tab="`${t('定向')}(${targetTotal})`">
                <div class="selected-tags">
                  <a-tag
                    v-for="(c, idx) in allTargetRows"
                    :key="'tg-' + c.kind + c.key + idx"
                    closable
                    @close="removeTargetRow(c)"
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
                    @close="removeExcludeRow(c)"
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

      <a-alert type="info" show-icon style="margin-bottom: 16px">
        <template #message>
          <span>
            {{ t('对于任何定位欧盟地区受众的广告组,你需要指明广告组的受益人或组织,以及广告组的赞助方。') }}
            <a href="https://www.facebook.com/business/help" target="_blank" rel="noopener noreferrer">{{ t('详细了解') }}</a>
          </span>
        </template>
      </a-alert>

      <a-form-item :label="t('在什么地区投放金融产品和服务类广告')">
        <a-select v-model:value="local.financialRegion" :placeholder="t('请选择')" allow-clear style="max-width: 480px; width: 100%">
          <a-select-option v-for="opt in financialRegionOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</a-select-option>
        </a-select>
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

      <a-form-item :label="t('标签')">
        <a-select v-model:value="local.tag" :placeholder="t('请选择')" allow-clear style="max-width: 480px; width: 100%" disabled>
          <a-select-option value="placeholder">{{ t('（暂未对接后端字段）') }}</a-select-option>
        </a-select>
      </a-form-item>

      <a-form-item :label="t('地区组名称')">
        <a-input v-model:value="local.regionGroupName" :placeholder="t('例如：地区组1')" :maxlength="50" show-count style="max-width: 480px" />
      </a-form-item>

      <a-form-item :label="t('绑定广告账户')">
        <a-select
          v-model:value="local.fb_ad_account_ids"
          mode="multiple"
          :placeholder="t('不选表示不限账户')"
          style="width: 100%; max-width: 480px"
          allow-clear
          show-search
          :filter-option="filterAdAccountOption"
          :loading="adAccountsLoading"
          :options="adAccountSelectOptions"
        />
      </a-form-item>

      <a-form-item>
        <a-button type="primary" :loading="saveGroupLoading" @click="saveCurrentAsRegionGroup">{{ t('保存为地区组') }}</a-button>
      </a-form-item>
    </a-form>

    <a-modal
      v-model:open="savedGroupModalVisible"
      :title="t('选择已有地区组')"
      width="720px"
      :footer="null"
      destroy-on-close
    >
      <a-space wrap style="margin-bottom: 12px">
        <a-input
          v-model:value="modalGroupSearch"
          :placeholder="t('搜索地区组')"
          allow-clear
          style="width: 220px"
          @change="scheduleReloadModal"
        />
        <a-select v-model:value="modalTagFilter" :placeholder="t('标签')" style="width: 160px" disabled>
          <a-select-option value="">{{ t('（暂无）') }}</a-select-option>
        </a-select>
      </a-space>
      <a-table
        :columns="savedGroupColumns"
        :data-source="savedGroupRows"
        :loading="savedGroupsLoading"
        :pagination="savedGroupPagination"
        row-key="id"
        size="small"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.dataIndex === 'action'">
            <a-button type="link" size="small" @click="applySavedGroup(record)">{{ t('使用') }}</a-button>
          </template>
          <template v-else-if="column.dataIndex === 'tags'">
            {{ formatTags(record.tags) }}
          </template>
        </template>
      </a-table>
    </a-modal>

    <a-modal
      v-model:open="bulkModalVisible"
      :title="t('批量导入')"
      :ok-text="t('导入')"
      @ok="applyBulkImport"
      @cancel="bulkModalVisible = false"
    >
      <p class="bulk-help">
        {{ t('每行一条加入定向：国家为 ISO 或「代码,显示名」；大区为「region:key,显示名」') }}
      </p>
      <pre class="bulk-example">US
GB,United Kingdom
region:3843,California</pre>
      <a-textarea v-model:value="bulkText" :rows="8" :placeholder="t('粘贴内容')" />
    </a-modal>
  </div>
</template>

<script lang="ts" setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { searchCountries, searchRegions } from '@/api/geo_location';
import {
  queryMetaAdCreationRegionGroupsApi,
  createMetaAdCreationRegionGroupApi,
} from '@/api/meta_ad_creation_region_groups';
import { queryFB_AD_AccountsApi } from '@/api/fb_ad_accounts';
import debounce from '@/utils/debonce';

const { t } = useI18n();

type GeoKind = 'country' | 'region';

interface GeoRow {
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

const props = defineProps<{ formData: Record<string, any> }>();
const emit = defineEmits<{ (e: 'update:formData', value: Record<string, any>): void }>();

function defaultLocal() {
  return {
    selectExisting: false,
    regionGroupName: '',
    regionGroupId: null as string | null,
    fb_ad_account_ids: [] as string[],
    tag: undefined as string | undefined,
    countries_included: [] as { key: string; name: string }[],
    countries_excluded: [] as { key: string; name: string }[],
    regions_included: [] as { key: string; name: string }[],
    regions_excluded: [] as { key: string; name: string }[],
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

function mergeLocal(src: Record<string, any> | undefined) {
  const d = defaultLocal();
  const v = src && typeof src === 'object' ? src : {};
  return {
    ...d,
    ...v,
    fb_ad_account_ids: Array.isArray(v.fb_ad_account_ids) ? [...v.fb_ad_account_ids] : [...d.fb_ad_account_ids],
    countries_included: Array.isArray(v.countries_included) ? [...v.countries_included] : [...d.countries_included],
    countries_excluded: Array.isArray(v.countries_excluded) ? [...v.countries_excluded] : [...d.countries_excluded],
    regions_included: Array.isArray(v.regions_included) ? [...v.regions_included] : [...d.regions_included],
    regions_excluded: Array.isArray(v.regions_excluded) ? [...v.regions_excluded] : [...d.regions_excluded],
  };
}

const local = ref(mergeLocal(props.formData));

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

const regionSearch = ref('');
const searchLoading = ref(false);
const searchRows = ref<SearchRow[]>([]);
const selectedTab = ref('target');
const currentItemCount = ref(1);
const maxItemCount = ref(30);

const targetTotal = computed(
  () => local.value.countries_included.length + local.value.regions_included.length,
);
const excludeTotal = computed(
  () => local.value.countries_excluded.length + local.value.regions_excluded.length,
);
const canUseExclude = computed(() => targetTotal.value > 0);

const allTargetRows = computed(() => [
  ...local.value.countries_included.map((x) => ({ ...x, kind: 'country' as const })),
  ...local.value.regions_included.map((x) => ({ ...x, kind: 'region' as const })),
]);
const allExcludeRows = computed(() => [
  ...local.value.countries_excluded.map((x) => ({ ...x, kind: 'country' as const })),
  ...local.value.regions_excluded.map((x) => ({ ...x, kind: 'region' as const })),
]);

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

const beneficiaryItems = [
  { key: 'tw', label: t('台湾地区'), benefitKey: 'benefitTw' as const, sponsorKey: 'sponsorTw' as const },
  { key: 'au', label: t('澳大利亚'), benefitKey: 'benefitAu' as const, sponsorKey: 'sponsorAu' as const },
  { key: 'sg', label: t('新加坡'), benefitKey: 'benefitSg' as const, sponsorKey: 'sponsorSg' as const },
  { key: 'th', label: t('泰国'), benefitKey: 'benefitTh' as const, sponsorKey: 'sponsorTh' as const },
  { key: 'eu', label: t('欧盟'), benefitKey: 'benefitEu' as const, sponsorKey: 'sponsorEu' as const },
];

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

function filterDsaOption(input: string, option: any) {
  return String(option?.label ?? '')
    .toLowerCase()
    .includes(input.toLowerCase());
}

function filterAdAccountOption(input: string, option: any) {
  return String(option?.label ?? '')
    .toLowerCase()
    .includes(input.toLowerCase());
}

function pushUnique(list: { key: string; name: string }[], row: { key: string; name: string }) {
  if (list.some((x) => x.key === row.key)) return false;
  list.push({ ...row });
  return true;
}

function rowToGeo(row: SearchRow): GeoRow {
  return { key: row.key, name: row.name, kind: row.kind };
}

function addTarget(row: SearchRow) {
  const geo = rowToGeo(row);
  const list = geo.kind === 'country' ? local.value.countries_included : local.value.regions_included;
  if (pushUnique(list, { key: geo.key, name: geo.name })) {
    const exList = geo.kind === 'country' ? local.value.countries_excluded : local.value.regions_excluded;
    const i = exList.findIndex((x) => x.key === geo.key);
    if (i >= 0) exList.splice(i, 1);
    message.success(t('已加入定向'));
    emitUpdate();
  } else message.info(t('已在定向列表中'));
}

function addExclude(row: SearchRow) {
  if (!canUseExclude.value) {
    message.warning(t('请先添加定向（至少一个国家/大区），再设置排除'));
    return;
  }
  const geo = rowToGeo(row);
  const list = geo.kind === 'country' ? local.value.countries_excluded : local.value.regions_excluded;
  const incList = geo.kind === 'country' ? local.value.countries_included : local.value.regions_included;
  if (pushUnique(list, { key: geo.key, name: geo.name })) {
    const j = incList.findIndex((x) => x.key === geo.key);
    if (j >= 0) incList.splice(j, 1);
    message.success(t('已加入排除'));
    emitUpdate();
  } else message.info(t('已在排除列表中'));
}

function removeTargetRow(row: { kind: GeoKind; key: string }) {
  if (row.kind === 'country') {
    local.value.countries_included = local.value.countries_included.filter((x) => x.key !== row.key);
  } else {
    local.value.regions_included = local.value.regions_included.filter((x) => x.key !== row.key);
  }
  emitUpdate();
}

function removeExcludeRow(row: { kind: GeoKind; key: string }) {
  if (row.kind === 'country') {
    local.value.countries_excluded = local.value.countries_excluded.filter((x) => x.key !== row.key);
  } else {
    local.value.regions_excluded = local.value.regions_excluded.filter((x) => x.key !== row.key);
  }
  emitUpdate();
}

function emitUpdate() {
  emit('update:formData', { ...local.value });
}

let searchReq = 0;
const runSearch = debounce(async () => {
  const q = regionSearch.value.trim();
  if (!q) {
    searchRows.value = [];
    return;
  }
  const token = ++searchReq;
  searchLoading.value = true;
  try {
    const [cRes, rRes]: any[] = await Promise.all([searchCountries(q), searchRegions(q)]);
    if (token !== searchReq) return;
    const cPayload = Array.isArray(cRes) ? cRes : Array.isArray(cRes?.data) ? cRes.data : [];
    const rPayload = Array.isArray(rRes) ? rRes : Array.isArray(rRes?.data) ? rRes.data : [];
    const countries = normalizeGeoList(cPayload);
    const regions = normalizeGeoList(rPayload);
    const seen = new Set<string>();
    const out: SearchRow[] = [];
    for (const x of countries) {
      const uid = `country:${x.key}`;
      if (seen.has(uid)) continue;
      seen.add(uid);
      out.push({
        uid,
        key: x.key,
        name: x.name,
        kind: 'country',
        displayName: `${x.name} (${t('国家')})`,
      });
    }
    for (const x of regions) {
      const uid = `region:${x.key}`;
      if (seen.has(uid)) continue;
      seen.add(uid);
      out.push({
        uid,
        key: x.key,
        name: x.name,
        kind: 'region',
        displayName: `${x.name} (${t('大区/市场')})`,
      });
    }
    searchRows.value = out;
  } catch {
    searchRows.value = [];
    message.warning(t('定向搜索请求失败'));
  } finally {
    if (token === searchReq) searchLoading.value = false;
  }
}, 300);

function onRegionSearchChange() {
  runSearch();
}

function handleClear() {
  local.value.countries_included = [];
  local.value.countries_excluded = [];
  local.value.regions_included = [];
  local.value.regions_excluded = [];
  regionSearch.value = '';
  searchRows.value = [];
  message.success(t('已清空'));
  emitUpdate();
}

function handleClearSelected() {
  if (selectedTab.value === 'target') {
    local.value.countries_included = [];
    local.value.regions_included = [];
  } else {
    local.value.countries_excluded = [];
    local.value.regions_excluded = [];
  }
  emitUpdate();
}

function handleAddNew() {
  if (currentItemCount.value < maxItemCount.value) currentItemCount.value++;
}

const bulkModalVisible = ref(false);
const bulkText = ref('');

function parseBulkLine(line: string): GeoRow | null {
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
  let n = 0;
  for (const line of lines) {
    const item = parseBulkLine(line);
    if (!item) continue;
    const list = item.kind === 'country' ? local.value.countries_included : local.value.regions_included;
    if (pushUnique(list, { key: item.key, name: item.name })) n++;
  }
  bulkModalVisible.value = false;
  bulkText.value = '';
  message.success(n ? `${t('已处理')} ${n} ${t('条')}` : t('未解析到有效行'));
  emitUpdate();
}

/** 已有地区组弹窗 */
const savedGroupModalVisible = ref(false);
const savedGroupsLoading = ref(false);
const savedGroupRows = ref<any[]>([]);
const modalGroupSearch = ref('');
const modalTagFilter = ref('');
const saveGroupLoading = ref(false);

const savedGroupColumns = computed(() => [
  { title: t('地区组名称'), dataIndex: 'name', key: 'name' },
  { title: t('操作'), dataIndex: 'action', key: 'action', width: 100 },
  { title: t('标签'), dataIndex: 'tags', key: 'tags', width: 120 },
  { title: t('创建时间'), dataIndex: 'createdAtText', key: 'createdAtText', width: 180 },
]);

const savedGroupPagination = computed(() => ({
  pageSize: 20,
  showSizeChanger: true,
  showTotal: (total: number) => `${t('共')} ${total} ${t('条')}`,
}));

function formatTags(tags: unknown) {
  if (!Array.isArray(tags) || tags.length === 0) return '—';
  return tags.map(String).join(', ');
}

function openSavedGroupModal() {
  savedGroupModalVisible.value = true;
}

watch(savedGroupModalVisible, (open) => {
  if (open) loadSavedGroups();
});

async function loadSavedGroups() {
  savedGroupsLoading.value = true;
  try {
    const res: any = await queryMetaAdCreationRegionGroupsApi(
      modalGroupSearch.value.trim() ? { q: modalGroupSearch.value.trim() } : undefined,
    );
    const list = res?.data ?? [];
    savedGroupRows.value = Array.isArray(list) ? list : [];
  } catch {
    savedGroupRows.value = [];
    message.warning(t('加载地区组列表失败，可稍后重试'));
  } finally {
    savedGroupsLoading.value = false;
  }
}

const scheduleReloadModal = debounce(() => {
  loadSavedGroups();
}, 300);

function applySavedGroup(g: any) {
  local.value.regionGroupId = g.id;
  local.value.regionGroupName = g.name || '';
  local.value.fb_ad_account_ids = Array.isArray(g.fbAdAccountIds) ? [...g.fbAdAccountIds] : [];
  local.value.countries_included = Array.isArray(g.countriesIncluded) ? [...g.countriesIncluded] : [];
  local.value.countries_excluded = Array.isArray(g.countriesExcluded) ? [...g.countriesExcluded] : [];
  local.value.regions_included = Array.isArray(g.regionsIncluded) ? [...g.regionsIncluded] : [];
  local.value.regions_excluded = Array.isArray(g.regionsExcluded) ? [...g.regionsExcluded] : [];
  savedGroupModalVisible.value = false;
  message.success(t('已载入地区组'));
  emitUpdate();
}

const adAccountsLoading = ref(false);
const adAccounts = ref<{ id: string; name: string }[]>([]);
const adAccountSelectOptions = computed(() =>
  (adAccounts.value || []).map((a) => ({ label: a.name || a.id, value: a.id })),
);

async function loadAdAccountsForBind() {
  adAccountsLoading.value = true;
  try {
    const res: any = await queryFB_AD_AccountsApi({
      'with-campaign': false,
      is_archived: false,
      pageSize: 500,
      pageNo: 1,
    });
    const list = res?.data ?? [];
    adAccounts.value = Array.isArray(list)
      ? list
          .map((x: any) => ({
            id: String(x.source_id ?? x.id ?? ''),
            name: String(x.name ?? x.source_id ?? x.id ?? ''),
          }))
          .filter((x) => x.id)
      : [];
  } catch {
    adAccounts.value = [];
  } finally {
    adAccountsLoading.value = false;
  }
}

loadAdAccountsForBind();

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
    const res: any = await createMetaAdCreationRegionGroupApi({
      name,
      fb_ad_account_ids: Array.isArray(local.value.fb_ad_account_ids) ? [...local.value.fb_ad_account_ids] : [],
      countries_included: normalizeGeoList(local.value.countries_included),
      countries_excluded: normalizeGeoList(local.value.countries_excluded),
      regions_included: normalizeGeoList(local.value.regions_included),
      regions_excluded: normalizeGeoList(local.value.regions_excluded),
    });
    const id = res?.data?.id;
    if (id) local.value.regionGroupId = id;
    message.success(res?.message || t('地区组已保存'));
  } catch (e: any) {
    message.error(e?.response?.data?.message || e?.message || t('保存失败'));
  } finally {
    saveGroupLoading.value = false;
  }
  emitUpdate();
}

let syncingFromParent = false;

watch(
  () => props.formData,
  (v) => {
    syncingFromParent = true;
    local.value = mergeLocal(v);
    nextTick(() => {
      syncingFromParent = false;
    });
  },
  { deep: true },
);

watch(
  local,
  (v) => {
    if (syncingFromParent) return;
    emit('update:formData', { ...v });
  },
  { deep: true },
);
</script>

<style lang="less" scoped>
.region-group {
  .region-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;

    .section-title {
      font-size: 18px;
      font-weight: 500;
      color: #333;
      margin: 0;
    }

    .header-actions {
      display: flex;
      gap: 8px;
      align-items: center;

      .item-count {
        color: #999;
        font-size: 12px;
        margin-left: 8px;
      }
    }
  }

  .region-picker {
    display: flex;
    gap: 16px;
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    padding: 12px;
    background: #fafafa;
  }

  .picker-col {
    min-width: 0;
  }

  .picker-search-wide {
    flex: 1.4;
  }

  .result-row-flex {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 6px 0;
    border-bottom: 1px solid #f0f0f0;
  }

  .result-btns {
    flex-shrink: 0;
    white-space: nowrap;
  }

  .picker-selected {
    flex: 1.1;
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

  .result-name {
    font-size: 13px;
    min-width: 0;
    word-break: break-word;
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
    min-height: 48px;
  }

  .empty-hint {
    color: #999;
    font-size: 12px;
  }

  .sub-label {
    font-size: 13px;
    color: #595959;
  }

  .bulk-help {
    font-size: 13px;
    color: #595959;
  }

  .bulk-example {
    font-size: 12px;
    background: #f5f5f5;
    padding: 8px;
    border-radius: 4px;
    margin-bottom: 8px;
  }
}
</style>
