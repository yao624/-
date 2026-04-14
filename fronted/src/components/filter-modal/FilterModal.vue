<template>
  <a-modal
    v-model:open="visible"
    :title="t('pages.createRule.filterSettings')"
    width="1100px"
    @ok="handleOk"
  >
    <a-alert
      :message="t('pages.createRule.filterSettingsTip')"
      type="info"
      show-icon
      style="margin-bottom: 20px"
    />
    <div class="filter-modal-grid">
      <div v-for="item in localOptions" :key="item.key" class="filter-modal-row">
        <a-checkbox
          v-model:checked="item.enabled"
          :disabled="item.disabled"
        />
        <span class="filter-modal-label">{{ item.label }}:</span>
        <!-- 标签字段使用 TagSelect 树形选择 -->
        <tag-select
          v-if="item.key === 'tagIds' && item.tagTreeData"
          :model-value="item.value ?? []"
          @update:model-value="val => { item.value = val }"
          :tag-folders="item.tagTreeData.tagFolders || []"
          :tags="item.tagTreeData.tags || []"
          :tag-options="item.tagTreeData.tagOptions || []"
          :placeholder="`请选择${item.label}`"
          :creatable="false"
          style="flex: 1"
        />
        <!-- 设计师字段使用 UserPicker，固定 deptId=2 -->
        <UserPicker
          v-else-if="item.key === 'designer' && item.orgData"
          :model-value="item.value ?? []"
          @update:model-value="val => { item.value = val }"
          :org-tree="item.orgData"
          :dept-id="2"
          :placeholder="`请选择${item.label}`"
          style="flex: 1"
        />
        <!-- 创意人字段使用 UserPicker -->
        <UserPicker
          v-else-if="item.key === 'creator' && item.orgData"
          :model-value="item.value ?? []"
          @update:model-value="val => { item.value = val }"
          :org-tree="item.orgData"
          :placeholder="`请选择${item.label}`"
          style="flex: 1"
        />
        <a-select
          v-else
          :model-value="item.value"
          @change="val => { item.value = val }"
          :placeholder="`请选择${item.label}`"
          style="flex: 1"
          show-search
          :filter-option="(input, option) => String(option?.label || '').toLowerCase().includes(input.toLowerCase())"
          :options="item.options"
          allow-clear
          :disabled="item.disabled"
        />
      </div>
    </div>
    <template #footer>
      <div class="filter-modal-footer">
        <a @click="handleClear">{{ t('pages.createRule.clearSelection') }}</a>
        <div>
          <a-button @click="visible = false">{{ t('pages.createRule.cancel') }}</a-button>
          <a-button type="primary" @click="handleOk">{{ t('pages.createRule.confirm') }}</a-button>
        </div>
      </div>
    </template>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import TagSelect from '@/components/tag-select/index.vue';
import { UserPicker } from '@/components/user-picker';

/** 筛选项数据类型 */
export interface FilterOption {
  key: string;
  label: string;
  enabled: boolean;
  disabled?: boolean;
  value?: string | number | any[];
  options: { label: string; value: string | number }[];
  /** 标签树数据，key=tagIds 时需要 */
  tagTreeData?: {
    tagFolders: any[];
    tags: any[];
    tagOptions: any[];
  };
  /** 组织树数据，key=designer 或 key=creator 时需要 */
  orgData?: any[];
}

const props = defineProps<{
  modelValue: boolean;
  /** 初始值映射，用于回显（key 对应 FilterOption.key） */
  initialValues?: Record<string, any>;
  /** 初始启用的筛选项 key 列表，用于回显勾选状态 */
  visibleFilters?: string[];
  /** 筛选项配置列表 */
  filterOptions: FilterOption[];
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  /** 确认事件，返回深拷贝后的筛选项列表（含最新 value 和 enabled 状态） */
  (e: 'confirm', filterOptions: FilterOption[]): void;
}>();

const { t } = useI18n();

const visible = ref(props.modelValue);
const localOptions = ref<FilterOption[]>([]);

watch(() => props.modelValue, (val) => {
  visible.value = val;
  if (val) {
    // 深拷贝父组件的 options 作为本地状态，不直接修改 props
    localOptions.value = props.filterOptions.map(item => ({
      ...item,
      options: item.options ? [...item.options] : item.options,
    }));
    // 根据 visibleFilters 设置启用状态
    if (props.visibleFilters) {
      localOptions.value.forEach(option => {
        if (!option.disabled) {
          option.enabled = props.visibleFilters!.includes(option.key);
        }
      });
    }
    // 设置所有筛选项的值（从 initialValues 同步）
    if (props.initialValues) {
      Object.entries(props.initialValues).forEach(([key, value]) => {
        const option = localOptions.value.find(item => item.key === key);
        if (option && value !== undefined) {
          option.value = value;
        }
      });
    }
  }
});

watch(visible, (val) => {
  emit('update:modelValue', val);
});

const handleOk = () => {
  // 深拷贝后返回，避免父组件被引用污染
  const result = localOptions.value.map(item => ({
    ...item,
    options: item.options ? [...item.options] : item.options,
  }));
  emit('confirm', result);
  visible.value = false;
};

const handleClear = () => {
  localOptions.value.forEach(item => {
    if (!item.disabled) {
      item.enabled = false;
      item.value = undefined;
    }
  });
};
</script>

<style lang="less" scoped>
.filter-modal-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px 24px;
}

.filter-modal-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-modal-label {
  width: 72px;
  color: #262626;
  white-space: nowrap;
}

.filter-modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
