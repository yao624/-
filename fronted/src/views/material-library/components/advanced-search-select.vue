<template>
  <div class="xmp-advanced-search-select">
    <a-popover
      v-model:open="open"
      trigger="click"
      placement="bottomLeft"
      overlayClassName="xmp-advanced-search-popover"
      :overlayStyle="{ padding: '0' }"
      :overlayInnerStyle="{ padding: '0' }"
    >
      <template #content>
        <div class="panel" @mousedown.prevent @click.stop>
          <div class="panel-top">
            <div class="panel-top-left">
              <a-select v-if="showLogicSelector" v-model:value="logicModeInner" class="logic-select">
                <a-select-option value="all">{{ allLabel }}</a-select-option>
                <a-select-option value="any">{{ anyLabel }}</a-select-option>
              </a-select>
              <a-input
                v-else
                v-model:value="keyword"
                :placeholder="searchPlaceholder"
                allow-clear
                class="search-input top-search-input"
              >
                <template #prefix>
                  <search-outlined />
                </template>
              </a-input>
            </div>

            <div class="panel-top-right">
              <span class="selected-count">{{ selectedCountLabel }}</span>
              <a-button type="link" size="small" class="clear-btn" :disabled="selectedValuesSet.size === 0" @click="handleClear">
                {{ clearLabel }}
              </a-button>
            </div>
          </div>

          <div v-if="showLogicSelector" class="panel-search">
            <a-input
              v-model:value="keyword"
              :placeholder="searchPlaceholder"
              allow-clear
              class="search-input"
            >
              <template #prefix>
                <search-outlined />
              </template>
            </a-input>
          </div>

          <div class="panel-body">
            <div class="col col-left">
              <div
                v-for="cat in categoriesSafe"
                :key="cat.key"
                class="cat-item"
                :class="{ active: cat.key === activeCategoryKey }"
                @click="activeCategoryKey = cat.key"
              >
                <span class="cat-text" :title="cat.label">{{ cat.label }}</span>
                <right-outlined v-if="cat.key === activeCategoryKey" class="cat-arrow" />
              </div>
            </div>

            <div class="col col-middle">
              <div v-if="filteredItems.length === 0" class="empty-wrap">
                <a-empty :description="emptyLabel" />
              </div>
              <a-checkbox-group v-else :value="modelValueSafe" class="options-group" @change="handleCheckboxGroupChange">
                <div v-for="opt in filteredItems" :key="opt.value" class="opt-row">
                  <a-checkbox :value="opt.value">{{ opt.label }}</a-checkbox>
                </div>
              </a-checkbox-group>
            </div>

            <div class="col col-right">
              <!-- <div class="selected-title">{{ selectedCountLabel }}</div> -->
              <div v-if="selectedItems.length === 0" class="selected-empty">{{ selectedEmptyLabel }}</div>
              <div v-else class="selected-list">
                <div v-for="it in selectedItems" :key="it.value" class="selected-item">
                  <span class="selected-item-text" :title="it.label">{{ it.label }}</span>
                  <a-button type="link" size="small" class="remove-btn" @click="removeOne(it.value)">{{ removeLabel }}</a-button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <div class="trigger-box" @click="open = true">
        <span class="trigger-text" :class="{ 'is-placeholder': !displayValue }">
          {{ displayValue || placeholder }}
        </span>
        <span class="trigger-icons">
          <close-circle-filled
            v-if="allowClear && selectedValuesSet.size > 0"
            class="trigger-clear"
            @click.stop="handleClear"
          />
          <down-outlined class="trigger-arrow" />
        </span>
      </div>
    </a-popover>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue';
import { CloseCircleFilled, DownOutlined, RightOutlined, SearchOutlined } from '@ant-design/icons-vue';

export type LogicMode = 'all' | 'any';
export type ValueLike = string | number;

export interface AdvancedSelectItem {
  value: ValueLike;
  label: string;
  categoryKey: string;
}

export interface AdvancedSelectCategory {
  key: string;
  label: string;
}

interface Props {
  modelValue?: ValueLike[];
  categories?: AdvancedSelectCategory[];
  items?: AdvancedSelectItem[];
  placeholder?: string;
  allowClear?: boolean;
  searchPlaceholder?: string;
  enableRemoteSearch?: boolean;
  remoteSearchDebounceMs?: number;
  showLogicSelector?: boolean;
  logicMode?: LogicMode;
  allLabel?: string;
  anyLabel?: string;
  clearLabel?: string;
  emptyLabel?: string;
  selectedPanelTitle?: string;
  selectedEmptyLabel?: string;
  removeLabel?: string;
}

interface Emits {
  (e: 'update:modelValue', value: ValueLike[]): void;
  (e: 'update:logicMode', value: LogicMode): void;
  (e: 'change', payload: { values: ValueLike[]; logicMode: LogicMode }): void;
  (e: 'search', payload: { keyword: string; categoryKey: string; logicMode: LogicMode }): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  categories: () => [],
  items: () => [],
  placeholder: '请选择',
  allowClear: true,
  searchPlaceholder: '请搜索',
  enableRemoteSearch: false,
  remoteSearchDebounceMs: 250,
  showLogicSelector: true,
  logicMode: 'all',
  allLabel: '满足全部条件',
  anyLabel: '满足任一条件',
  clearLabel: '清除',
  emptyLabel: '暂无数据',
  selectedPanelTitle: '已选 0 个',
  selectedEmptyLabel: '',
  removeLabel: '移除',
});

const emit = defineEmits<Emits>();

const open = ref(false);
const keyword = ref('');

const categoriesSafe = computed(() => (Array.isArray(props.categories) ? props.categories : []));
const itemsSafe = computed(() => (Array.isArray(props.items) ? props.items : []));
const modelValueSafe = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

const activeCategoryKey = ref<string>('');
watch(
  categoriesSafe,
  (cats) => {
    if (!activeCategoryKey.value) {
      activeCategoryKey.value = cats?.[0]?.key ? String(cats[0].key) : '';
    } else if (cats?.length && !cats.some((c) => String(c.key) === String(activeCategoryKey.value))) {
      activeCategoryKey.value = String(cats[0].key);
    }
  },
  { immediate: true },
);

const logicModeInner = computed<LogicMode>({
  get() {
    return props.logicMode;
  },
  set(v) {
    emit('update:logicMode', v);
    emit('change', { values: modelValueSafe.value, logicMode: v });
  },
});

const selectedValuesSet = computed(() => new Set(modelValueSafe.value.map((v) => String(v))));

const activeItems = computed(() => {
  const key = String(activeCategoryKey.value || '');
  return itemsSafe.value.filter((it) => String(it.categoryKey) === key);
});

const filteredItems = computed(() => {
  const k = String(keyword.value || '').trim().toLowerCase();
  if (!k) return activeItems.value;
  return activeItems.value.filter((it) => String(it.label || '').toLowerCase().includes(k));
});

const selectedItems = computed(() => {
  const index = new Map<string, AdvancedSelectItem>();
  itemsSafe.value.forEach((it) => index.set(String(it.value), it));
  return modelValueSafe.value
    .map((v) => index.get(String(v)))
    .filter(Boolean) as AdvancedSelectItem[];
});

const selectedCountLabel = computed(() => `已选 ${selectedItems.value.length} 个`);
const displayValue = computed(() => (selectedItems.value.length ? selectedCountLabel.value : undefined));

const handleCheckboxGroupChange = (vals: any) => {
  const currentCategoryValueSet = new Set(activeItems.value.map((it) => String(it.value)));
  const keepOtherCategoryValues = modelValueSafe.value.filter((v) => !currentCategoryValueSet.has(String(v)));
  const currentCategoryValues = Array.isArray(vals) ? vals : [];
  const next = [...keepOtherCategoryValues, ...currentCategoryValues];
  emit('update:modelValue', next);
  emit('change', { values: next, logicMode: logicModeInner.value });
};

const removeOne = (value: ValueLike) => {
  const next = modelValueSafe.value.filter((v) => String(v) !== String(value));
  emit('update:modelValue', next);
  emit('change', { values: next, logicMode: logicModeInner.value });
};

const handleClear = () => {
  keyword.value = '';
  emit('update:modelValue', []);
  emit('change', { values: [], logicMode: logicModeInner.value });
};

let remoteSearchTimer: ReturnType<typeof setTimeout> | null = null;
watch(
  [open, keyword, activeCategoryKey, logicModeInner],
  ([isOpen]) => {
    if (!props.enableRemoteSearch) return;
    if (!isOpen) return;
    if (remoteSearchTimer) clearTimeout(remoteSearchTimer);
    remoteSearchTimer = setTimeout(() => {
      emit('search', {
        keyword: String(keyword.value || ''),
        categoryKey: String(activeCategoryKey.value || ''),
        logicMode: logicModeInner.value,
      });
    }, Math.max(0, Number(props.remoteSearchDebounceMs || 0)));
  },
  { immediate: false },
);
</script>

<style lang="less" scoped>
.xmp-advanced-search-select {
  width: 100%;
}

.trigger-box {
  width: 100%;
  height: 34px;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  background: #fff;
  padding: 0 11px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}
.trigger-box:hover {
  border-color: #4096ff;
}
.trigger-text {
  font-size: 14px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding-right: 8px;
}
.trigger-text.is-placeholder {
  color: #bfbfbf;
}
.trigger-icons {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.trigger-clear {
  color: #bfbfbf;
  font-size: 12px;
}
.trigger-clear:hover {
  color: #8c8c8c;
}
.trigger-arrow {
  color: #8c8c8c;
  font-size: 12px;
}

.panel {
  width: 680px;
  background: #fff;
  border: 1px solid #e6e6e6;
  border-radius: 6px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  padding: 10px;
}

:global(.xmp-advanced-search-popover .ant-popover-inner) {
  padding: 0 !important;
}

:global(.xmp-advanced-search-popover .ant-popover-inner-content) {
  padding: 0 !important;
  width: 100%;
  height: 100%;
}

.panel-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.panel-top-left {
  flex: 1;
  min-width: 0;
}
.logic-select {
  width: 60%;
}
.top-search-input {
  width: 100%;
}
.panel-top-right {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}
.selected-count {
  font-size: 14px;
  color: #333;
}
.clear-btn {
  padding: 0;
  color: #1890ff;
}

.panel-search {
  margin-top: 10px;
}
.search-input :deep(.ant-input) {
  font-size: 14px;
}

.panel-body {
  margin-top: 10px;
  display: grid;
  grid-template-columns: 180px 1fr 220px;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  overflow: hidden;
  min-height: 260px;
}

.col {
  min-height: 260px;
}
.col-left {
  border-right: 1px solid #f0f0f0;
  background: #fff;
}
.col-middle {
  border-right: 1px solid #f0f0f0;
  background: #fff;
}
.col-right {
  background: #fff;
}

.cat-item {
  height: 36px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  font-size: 14px;
  color: #333;
}
.cat-item:hover {
  background: #f5f7fb;
}
.cat-item.active {
  color: #1890ff;
  background: #f0f7ff;
}
.cat-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.cat-arrow {
  font-size: 12px;
  color: #1890ff;
}

.options-group {
  width: 100%;
  display: block;
}
.opt-row {
  height: 34px;
  display: flex;
  align-items: center;
  padding: 0 12px;
}
.opt-row:hover {
  background: #fafafa;
}

.empty-wrap {
  padding: 24px 0;
}

.selected-title {
  height: 36px;
  display: flex;
  align-items: center;
  padding: 0 12px;
  font-size: 14px;
  color: #333;
  border-bottom: 1px solid #f0f0f0;
}
.selected-empty {
  padding: 12px;
  font-size: 14px;
  color: #999;
}
.selected-list {
  padding: 6px 0;
  max-height: 220px;
  overflow: auto;
}
.selected-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 6px 12px;
}
.selected-item-text {
  font-size: 14px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.remove-btn {
  padding: 0;
  color: #1890ff;
}
</style>

