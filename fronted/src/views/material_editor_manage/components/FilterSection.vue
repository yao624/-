<template>
  <filter-section
    :fields="filterFields"
    :show-modal="false"
    i18n-prefix="pages.materialEditorManage.filter"
    @search="handleSearch"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FilterSection from '@/components/filter-section';
import type { FilterFieldConfig, FilterValue } from '@/components/filter-section';
import type { TaskStatus } from '../types';

const { t } = useI18n();

const emit = defineEmits<{
  (e: 'search', taskId?: string, status?: TaskStatus, createdBy?: string, dateRange?: [string, string], timeRange?: [string, string]): void;
  (e: 'reset'): void;
}>();

// 筛选字段配置
const filterFields = computed<FilterFieldConfig[]>(() => [
  {
    key: 'taskId',
    label: t('pages.materialEditorManage.filter.taskId'),
    type: 'input',
  },
  {
    key: 'status',
    label: t('pages.materialEditorManage.filter.status'),
    type: 'select',
    options: [
      { label: '不限', value: '' },
      { label: t('pages.materialEditorManage.status.pending'), value: 'pending' },
      { label: t('pages.materialEditorManage.status.processing'), value: 'processing' },
      { label: t('pages.materialEditorManage.status.completed'), value: 'completed' },
      { label: t('pages.materialEditorManage.status.failed'), value: 'failed' },
    ],
  },
  {
    key: 'timeRange',
    label: t('pages.materialEditorManage.filter.timeRange'),
    type: 'date-range',
  },
  {
    key: 'createdAt',
    label: t('pages.materialEditorManage.filter.createdAt'),
    type: 'date-range',
  },
  {
    key: 'createdBy',
    label: t('pages.materialEditorManage.filter.createdBy'),
    type: 'input',
  },
]);

const handleSearch = (filters: FilterValue) => {
  const dateRange = filters.createdAt as [string, string] | undefined;
  const timeRange = filters.timeRange as [string, string] | undefined;

  emit('search',
    filters.taskId as string || undefined,
    filters.status as TaskStatus || undefined,
    filters.createdBy as string || undefined,
    dateRange,
    timeRange,
  );
};

// Reset is handled by the generic component internally
defineExpose({
  reset: () => {
    emit('reset');
  },
});
</script>

<style scoped lang="less">
// Styles are handled by the generic component
</style>
