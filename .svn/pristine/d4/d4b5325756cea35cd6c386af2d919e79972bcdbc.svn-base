<template>
  <div class="filter-section">
    <div class="filter-content">
      <!-- Left: Filter icon and title (clickable to open modal) - only show when showModal is true -->
      <div v-if="showModal" class="filter-header" @click="handleOpenFilterModal">
        <SettingOutlined class="filter-icon" />
        <span class="filter-title">{{ filterTitle }}</span>
      </div>

      <!-- Center: Visible filter fields -->
      <div class="filter-fields">
        <div v-for="field in displayedFields" :key="field.key" class="filter-field">
          <span class="field-label">{{ field.label }}:</span>

          <!-- Select -->
          <a-select
            v-if="field.type === 'select'"
            v-model:value="activeFilters[field.key]"
            :placeholder="field.placeholder || selectPlaceholder"
            class="field-input"
            :allow-clear="field.allowClear !== false"
            :loading="field.loading"
            show-search
            :filter-option="filterOption"
            @change="handleFilterChange"
          >
            <a-select-option
              v-for="option in field.options"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </a-select-option>
          </a-select>

          <!-- Input -->
          <a-input
            v-else-if="field.type === 'input'"
            v-model:value="activeFilters[field.key]"
            :placeholder="field.placeholder || inputPlaceholder"
            class="field-input"
            allow-clear
            @change="handleFilterChange"
          />

          <!-- Date Range -->
          <a-range-picker
            v-else-if="field.type === 'date-range'"
            v-model:value="activeFilters[field.key]"
            class="field-input"
            @change="handleFilterChange"
          />

          <!-- Number Range -->
          <div v-else-if="field.type === 'number-range'" class="number-range-input">
            <a-input-number
              v-model:value="activeFilters[field.key][0]"
              :placeholder="numberStartPlaceholder"
              class="field-input-number"
              @change="handleFilterChange"
            />
            <span class="range-separator">-</span>
            <a-input-number
              v-model:value="activeFilters[field.key][1]"
              :placeholder="numberEndPlaceholder"
              class="field-input-number"
              @change="handleFilterChange"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Modal - only show when showModal is true -->
    <filter-modal
      v-if="showModal"
      v-model:open="filterModalVisible"
      :fields="modalFields"
      :visible-keys="visibleKeys"
      :current-values="activeFilters"
      :i18n-prefix="i18nPrefix"
      @confirm="handleFilterConfirm"
      @update-visible-keys="handleUpdateVisibleKeys"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { SettingOutlined } from '@ant-design/icons-vue';
import FilterModal from './internal/FilterModal.vue';
import type { FilterSectionProps, FilterEmits, FilterValue, FilterFieldConfig } from './types';

const props = withDefaults(defineProps<FilterSectionProps>(), {
  defaultVisibleKeys: () => [],
  storageKey: 'filter-visible-keys',
  i18nPrefix: 'components.filter',
  showModal: true,
});

const emit = defineEmits<FilterEmits>();

const { t } = useI18n();

// Modal visibility
const filterModalVisible = ref(false);

// Current visible filter keys (with localStorage persistence)
const visibleKeys = ref<string[]>([...props.defaultVisibleKeys]);

// Active filters state
const activeFilters = reactive<FilterValue>({});

// Initialize active filters with default values
const initializeFilters = () => {
  props.fields.forEach(field => {
    if (activeFilters[field.key] === undefined) {
      if (field.type === 'number-range') {
        activeFilters[field.key] = [undefined, undefined];
      } else {
        activeFilters[field.key] = undefined;
      }
    }
  });
};

// Get only visible fields (where visible is true or not specified)
const visibleFields = computed(() => {
  return props.fields.filter(f => f.visible !== false);
});

// Get fields that can be shown in modal (all visible fields)
const modalFields = computed(() => {
  return visibleFields.value;
});

// Displayed fields based on visibleKeys
const displayedFields = computed(() => {
  return visibleFields.value.filter(f => visibleKeys.value.includes(f.key));
});

// Computed i18n texts
const filterTitle = computed(() => t(`${props.i18nPrefix}.title`));
const selectPlaceholder = computed(() => t(`${props.i18nPrefix}.selectPlaceholder`));
const inputPlaceholder = computed(() => t(`${props.i18nPrefix}.inputPlaceholder`));
const numberStartPlaceholder = computed(() => t(`${props.i18nPrefix}.numberStartPlaceholder`));
const numberEndPlaceholder = computed(() => t(`${props.i18nPrefix}.numberEndPlaceholder`));

// Filter option for select
const filterOption = (input: string, option: any) => {
  return option?.label?.toLowerCase().includes(input.toLowerCase());
};

// Load visible keys from localStorage
const loadVisibleKeys = () => {
  const saved = localStorage.getItem(props.storageKey);
  if (saved) {
    try {
      const parsed = JSON.parse(saved);
      visibleKeys.value = parsed.length > 0 ? parsed : [...props.defaultVisibleKeys];
    } catch {
      visibleKeys.value = [...props.defaultVisibleKeys];
    }
  } else {
    visibleKeys.value = [...props.defaultVisibleKeys];
  }
};

// Save visible keys to localStorage
const saveVisibleKeys = (keys: string[]) => {
  localStorage.setItem(props.storageKey, JSON.stringify(keys));
  visibleKeys.value = keys;
};

const handleOpenFilterModal = () => {
  filterModalVisible.value = true;
};

const handleFilterConfirm = (filters: FilterValue) => {
  Object.assign(activeFilters, filters);
  emit('search', { ...activeFilters });
};

const handleUpdateVisibleKeys = (keys: string[]) => {
  saveVisibleKeys(keys);
};

const handleFilterChange = () => {
  emit('search', { ...activeFilters });
};

const handleReset = () => {
  Object.keys(activeFilters).forEach(key => {
    const field = props.fields.find(f => f.key === key);
    if (field?.type === 'number-range') {
      activeFilters[key] = [undefined, undefined];
    } else {
      activeFilters[key] = undefined;
    }
  });
  emit('reset');
};

// Watch for fields changes
watch(() => props.fields, () => {
  initializeFilters();
}, { immediate: true });

// Load saved filter visibility on mount
onMounted(() => {
  loadVisibleKeys();
});

// Expose methods
defineExpose({
  reset: handleReset,
  getFilters: () => ({ ...activeFilters }),
  setFilters: (filters: FilterValue) => {
    Object.assign(activeFilters, filters);
  },
});
</script>

<style scoped lang="less">
.filter-section {
  padding: 12px 16px;
  background: #fff;
  border: 1px solid #f0f0f0;
  border-radius: 4px;

  .filter-content {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;

    .filter-header {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 4px;
      transition: background-color 0.2s;

      &:hover {
        background-color: #f5f5f5;
      }

      .filter-title {
        font-size: 14px;
        font-weight: 500;
        color: #262626;
      }

      .filter-icon {
        font-size: 14px;
        color: #595959;
      }
    }

    .filter-fields {
      display: flex;
      align-items: center;
      gap: 16px;
      flex: 1;
      flex-wrap: wrap;

      .filter-field {
        display: flex;
        align-items: center;
        gap: 8px;

        .field-label {
          font-size: 14px;
          color: #595959;
          white-space: nowrap;
        }

        .field-input {
          min-width: 160px;

          :deep(.ant-select-selector),
          :deep(.ant-input) {
            border-radius: 4px;
          }
        }

        .number-range-input {
          display: flex;
          align-items: center;
          gap: 8px;

          .field-input-number {
            width: 100px;
          }

          .range-separator {
            color: #8c8c8c;
          }
        }
      }
    }
  }
}
</style>
