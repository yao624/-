<template>
  <div class="local-template-filter">
    <a-form layout="inline">
      <a-form-item :label="isZh ? '模板名称' : 'Template Name'">
        <a-input
          v-model:value="filters.name"
          :placeholder="isZh ? '请输入模板名称' : 'Enter template name'"
          allow-clear
          style="width: 200px"
        />
      </a-form-item>

      <a-form-item :label="isZh ? '尺寸' : 'Dimension'">
        <a-select
          v-model:value="filters.dimension"
          :placeholder="isZh ? '选择尺寸' : 'Select dimension'"
          allow-clear
          style="width: 200px"
        >
          <a-select-option value="1080x1080">1080 × 1080</a-select-option>
          <a-select-option value="1080x1920">1080 × 1920</a-select-option>
          <a-select-option value="1920x1080">1920 × 1080</a-select-option>
        </a-select>
      </a-form-item>

      <a-form-item>
        <a-space>
          <a-button type="primary" @click="handleSearch">
            <template #icon><SearchOutlined /></template>
            {{ isZh ? '搜索' : 'Search' }}
          </a-button>
          <a-button @click="handleReset">
            {{ isZh ? '重置' : 'Reset' }}
          </a-button>
        </a-space>
      </a-form-item>
    </a-form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { SearchOutlined } from '@ant-design/icons-vue';
import type { LocalTemplateFilter } from '../types';

const { locale } = useI18n();

const isZh = computed(() => locale.value.startsWith('zh'));

const filters = ref<LocalTemplateFilter>({
  name: undefined,
  dimension: undefined,
});

const emit = defineEmits<{
  (e: 'search', params: LocalTemplateFilter): void;
}>();

const handleSearch = () => {
  emit('search', { ...filters.value });
};

const handleReset = () => {
  filters.value = {
    name: undefined,
    dimension: undefined,
  };
  emit('search', { ...filters.value });
};
</script>

<style scoped lang="less">
.local-template-filter {
  :deep(.ant-form-item) {
    margin-bottom: 0;
  }
}
</style>
