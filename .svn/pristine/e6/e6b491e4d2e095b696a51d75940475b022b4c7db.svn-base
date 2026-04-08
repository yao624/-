<template>
  <div class="material-edit-table">
    <a-table
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :scroll="{ x: 1200 }"
      row-key="id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, record }">
        <!-- 素材预览 -->
        <template v-if="column.dataIndex === 'previewUrl'">
          <a-image
            :src="record.previewUrl"
            :width="60"
            :height="60"
            class="preview-image"
          />
        </template>

        <!-- 原素材预览 -->
        <template v-else-if="column.dataIndex === 'originalPreviewUrl'">
          <a-image
            :src="record.originalPreviewUrl"
            :width="60"
            :height="60"
            class="preview-image"
          />
        </template>

        <!-- 状态 -->
        <template v-else-if="column.dataIndex === 'status'">
          <a-tag :color="getStatusColor(record.status)">
            {{ t(`pages.materialEditorManage.materialStatus.${record.status}`) }}
          </a-tag>
        </template>

        <!-- 编辑内容 -->
        <template v-else-if="column.dataIndex === 'editContent'">
          <a-tooltip :title="record.editContent">
            <span class="ellipsis-text">{{ record.editContent }}</span>
          </a-tooltip>
        </template>

        <!-- 生效原因 -->
        <template v-else-if="column.dataIndex === 'reason'">
          <a-tooltip :title="record.reason">
            <span class="ellipsis-text">{{ record.reason }}</span>
          </a-tooltip>
        </template>
      </template>
    </a-table>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { TableProps } from 'ant-design-vue';
import type { MaterialEditItem } from '../types';

const props = defineProps<{
  dataSource: MaterialEditItem[];
  loading: boolean;
  pagination: {
    current: number;
    pageSize: number;
    total: number;
    showSizeChanger: boolean;
    showQuickJumper: boolean;
    showTotal: (total: number) => string;
  };
}>();

const emit = defineEmits<{
  (e: 'pageChange', page: number, pageSize: number): void;
}>();

const { t } = useI18n();

const columns = computed(() => [
  {
    title: t('pages.materialEditorManage.detailTable.preview'),
    dataIndex: 'previewUrl',
    key: 'previewUrl',
    width: 100,
    align: 'center' as const,
  },
  {
    title: t('pages.materialEditorManage.detailTable.name'),
    dataIndex: 'materialName',
    key: 'materialName',
    width: 200,
    ellipsis: true,
  },
  {
    title: t('pages.materialEditorManage.detailTable.originalPreview'),
    dataIndex: 'originalPreviewUrl',
    key: 'originalPreviewUrl',
    width: 120,
    align: 'center' as const,
  },
  {
    title: t('pages.materialEditorManage.detailTable.status'),
    dataIndex: 'status',
    key: 'status',
    width: 100,
    align: 'center' as const,
  },
  {
    title: t('pages.materialEditorManage.detailTable.editContent'),
    dataIndex: 'editContent',
    key: 'editContent',
    width: 200,
    ellipsis: true,
  },
  {
    title: t('pages.materialEditorManage.detailTable.reason'),
    dataIndex: 'reason',
    key: 'reason',
    width: 180,
    ellipsis: true,
  },
]);

const getStatusColor = (status: string): string => {
  const colorMap: Record<string, string> = {
    success: 'success',
    failed: 'error',
    pending: 'default',
  };
  return colorMap[status] || 'default';
};

const handleTableChange: TableProps['onChange'] = (pagination) => {
  emit('pageChange', pagination.current, pagination.pageSize);
};
</script>

<style scoped lang="less">
.material-edit-table {
  .preview-image {
    border-radius: 4px;
    object-fit: cover;
  }

  .ellipsis-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
  }
}
</style>
