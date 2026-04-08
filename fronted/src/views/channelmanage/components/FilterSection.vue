<template>
  <filter-section
    :fields="filterFields"
    :default-visible-keys="defaultVisibleKeys"
    storage-key="channel-filter-visible"
    :show-modal="true"
    i18n-prefix="pages.channelManage.filter"
    @search="handleSearch"
    @reset="handleReset"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FilterSection from '@/components/filter-section';
import type { FilterFieldConfig } from '@/components/filter-section';
import type { MaterialFilter } from '../types';

const { t } = useI18n();
const emit = defineEmits<{
  (e: 'search', filters: MaterialFilter): void;
  (e: 'reset'): void;
}>();

// 渠道选项
const channelOptions = computed(() => [
  { label: t('pages.channelManage.channel.facebook'), value: 'fb' },
  { label: t('pages.channelManage.channel.google'), value: 'google' },
  { label: t('pages.channelManage.channel.tiktok'), value: 'tiktok' },
  { label: t('pages.channelManage.channel.kwai'), value: 'kwai' },
  { label: 'Meta', value: 'meta' },
]);

// 形状选项
const shapeOptions = computed(() => [
  { label: t('pages.channelManage.shape.square'), value: '正方形' },
  { label: t('pages.channelManage.shape.landscape'), value: 'landscape' },
  { label: t('pages.channelManage.shape.portrait'), value: 'portrait' },
  { label: t('pages.channelManage.shape.square_shape'), value: 'square' },
]);

// 格式选项
const formatOptions = [
  { label: 'JPG', value: 'jpg' },
  { label: 'PNG', value: 'png' },
  { label: 'MP4', value: 'mp4' },
  { label: 'GIF', value: 'gif' },
  { label: 'WEBP', value: 'webp' },
];

// 来源选项
const sourceOptions = computed(() => [
  { label: t('pages.channelManage.source.upload'), value: 'upload' },
  { label: t('pages.channelManage.source.sync'), value: 'sync' },
  { label: t('pages.channelManage.source.import'), value: 'import' },
]);

// Filter field configurations
const filterFields = computed<FilterFieldConfig[]>(() => [
  {
    key: 'name',
    label: t('pages.channelManage.filter.materialName'),
    type: 'input',
    placeholder: t('pages.channelManage.filter.pleaseInput', { field: t('pages.channelManage.filter.materialName') }),
  },
  {
    key: 'materialId',
    label: t('pages.channelManage.filter.materialId'),
    type: 'input',
    placeholder: t('pages.channelManage.filter.pleaseInput', { field: t('pages.channelManage.filter.materialId') }),
  },
  {
    key: 'channel',
    label: t('pages.channelManage.filter.channel'),
    type: 'select',
    options: channelOptions.value,
    placeholder: t('pages.channelManage.filter.selectChannel'),
  },
  {
    key: 'useAccount',
    label: t('pages.channelManage.filter.useAccount'),
    type: 'input',
    placeholder: t('pages.channelManage.filter.pleaseInput', { field: t('pages.channelManage.filter.useAccount') }),
  },
  {
    key: 'belongAccount',
    label: t('pages.channelManage.filter.belongAccount'),
    type: 'input',
    placeholder: t('pages.channelManage.filter.pleaseInput', { field: t('pages.channelManage.filter.belongAccount') }),
  },
  {
    key: 'size',
    label: t('pages.channelManage.filter.size'),
    type: 'input',
    placeholder: t('pages.channelManage.filter.pleaseInput', { field: t('pages.channelManage.filter.size') }),
  },
  {
    key: 'duration',
    label: t('pages.channelManage.filter.duration'),
    type: 'input',
    placeholder: t('pages.channelManage.filter.pleaseInput', { field: t('pages.channelManage.filter.duration') }),
  },
  {
    key: 'shape',
    label: t('pages.channelManage.filter.shape'),
    type: 'select',
    options: shapeOptions.value,
    placeholder: t('pages.channelManage.filter.selectShape'),
  },
  {
    key: 'format',
    label: t('pages.channelManage.filter.format'),
    type: 'select',
    options: formatOptions,
    placeholder: t('pages.channelManage.filter.selectFormat'),
  },
  {
    key: 'source',
    label: t('pages.channelManage.filter.source'),
    type: 'select',
    options: sourceOptions.value,
    placeholder: t('pages.channelManage.filter.selectSource'),
  },
  {
    key: 'materialNote',
    label: t('pages.channelManage.filter.materialNote'),
    type: 'input',
    placeholder: t('pages.channelManage.filter.pleaseInput', { field: t('pages.channelManage.filter.materialNote') }),
  },
  {
    key: 'createTime',
    label: t('pages.channelManage.filter.createTime'),
    type: 'date-range',
    startKey: 'createTimeStart',
    endKey: 'createTimeEnd',
    placeholder: [t('pages.channelManage.filter.startTime'), t('pages.channelManage.filter.endTime')],
  },
  {
    key: 'rejectInfo',
    label: t('pages.channelManage.filter.rejectInfo'),
    type: 'input',
    placeholder: t('pages.channelManage.filter.pleaseInput', { field: t('pages.channelManage.filter.rejectInfo') }),
  },
]);

// 默认显示前4个字段
const defaultVisibleKeys = computed(() => {
  return filterFields.value.slice(0, 4).map(f => f.key);
});

const handleSearch = (filters: Record<string, string | undefined>) => {
  emit('search', filters as MaterialFilter);
};

const handleReset = () => {
  emit('reset');
};
</script>

<style scoped lang="less">
// Styles are handled by the generic component
</style>
