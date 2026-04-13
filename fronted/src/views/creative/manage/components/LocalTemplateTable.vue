<template>
  <div class="local-template-table">
    <a-table
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-selection="rowSelection"
      row-key="id"
      @change="handleTableChange"
    >
      <template #bodyCell="{ column, record }">
        <!-- 预览 -->
        <template v-if="column.dataIndex === 'preview'">
          <div class="preview-cell">
            <div class="preview-placeholder">
              <FileImageOutlined style="font-size: 32px; color: #d9d9d9;" />
            </div>
          </div>
        </template>

        <!-- 模板名称 -->
        <template v-else-if="column.dataIndex === 'name'">
          <div class="name-cell">
            <span class="name-text">{{ record.name }}</span>
            <EditOutlined class="edit-icon" @click="handleEdit(record)" />
          </div>
        </template>

        <!-- 尺寸 -->
        <template v-else-if="column.dataIndex === 'dimension'">
          {{ record.width }} × {{ record.height }}
        </template>

        <!-- 变量数量 -->
        <template v-else-if="column.dataIndex === 'variableCount'">
          <a-tag v-if="record.dynamicVariables?.length > 0" color="blue">
            {{ record.dynamicVariables.length }} 个变量
          </a-tag>
          <span v-else style="color: #999;">-</span>
        </template>

        <!-- 更新时间 -->
        <template v-else-if="column.dataIndex === 'updatedAt'">
          {{ formatDate(record.updatedAt) }}
        </template>

        <!-- 操作 -->
        <template v-else-if="column.key === 'action'">
          <a-space>
            <a @click="handleEdit(record)">{{ isZh ? '编辑' : 'Edit' }}</a>
            <a
              v-if="record.dynamicVariables?.length > 0"
              @click="handleBatchGenerate(record)"
            >
              {{ isZh ? '批量生成' : 'Batch Generate' }}
            </a>
            <a @click="handleCopy(record)">{{ isZh ? '复制' : 'Copy' }}</a>
            <a-popconfirm
              :title="isZh ? '确认删除？' : 'Confirm delete?'"
              :ok-text="isZh ? '确定' : 'OK'"
              :cancel-text="isZh ? '取消' : 'Cancel'"
              @confirm="handleDelete(record)"
            >
              <a class="danger-text">{{ isZh ? '删除' : 'Delete' }}</a>
            </a-popconfirm>
          </a-space>
        </template>
      </template>
    </a-table>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { TableProps } from 'ant-design-vue';
import { EditOutlined, FileImageOutlined } from '@ant-design/icons-vue';
import type { LocalTemplate } from '../types';
import dayjs from 'dayjs';

const props = defineProps<{
  dataSource: LocalTemplate[];
  loading: boolean;
  pagination: {
    current: number;
    pageSize: number;
    total: number;
    showSizeChanger: boolean;
    showQuickJumper: boolean;
    showTotal: (total: number) => string;
  };
  selectedRowKeys?: string[];
}>();

const emit = defineEmits<{
  (e: 'delete', record: LocalTemplate): void;
  (e: 'edit', record: LocalTemplate): void;
  (e: 'copy', record: LocalTemplate): void;
  (e: 'batchGenerate', record: LocalTemplate): void;
  (e: 'update:selectedRowKeys', keys: string[]): void;
  (e: 'pageChange', page: number, pageSize: number): void;
}>();

const { t, locale } = useI18n();

const isZh = computed(() => locale.value.startsWith('zh'));

const columns = computed(() => [
  {
    title: isZh.value ? '预览' : 'Preview',
    dataIndex: 'preview',
    width: 80,
    align: 'center' as const,
  },
  {
    title: isZh.value ? '模板名称' : 'Template Name',
    dataIndex: 'name',
    width: 250,
    ellipsis: true,
  },
  {
    title: isZh.value ? '尺寸' : 'Dimension',
    dataIndex: 'dimension',
    width: 140,
  },
  {
    title: isZh.value ? '变量数量' : 'Variables',
    dataIndex: 'variableCount',
    width: 120,
  },
  {
    title: isZh.value ? '更新时间' : 'Updated At',
    dataIndex: 'updatedAt',
    width: 170,
  },
  {
    title: isZh.value ? '操作' : 'Actions',
    key: 'action',
    width: 220,
    fixed: 'right' as const,
  },
]);

const rowSelection = computed(() => ({
  selectedRowKeys: props.selectedRowKeys,
  onChange: (keys: string[]) => emit('update:selectedRowKeys', keys),
}));

const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  return dayjs(dateString).format('YYYY-MM-DD HH:mm');
};

const handleDelete = (record: Record<string, any>) => {
  emit('delete', record as LocalTemplate);
};

const handleEdit = (record: Record<string, any>) => {
  emit('edit', record as LocalTemplate);
};

const handleCopy = (record: Record<string, any>) => {
  emit('copy', record as LocalTemplate);
};

const handleBatchGenerate = (record: Record<string, any>) => {
  emit('batchGenerate', record as LocalTemplate);
};

const handleTableChange: TableProps['onChange'] = (pagination) => {
  emit('pageChange', pagination.current, pagination.pageSize);
};
</script>

<style scoped lang="less">
.local-template-table {
  .preview-cell {
    display: flex;
    justify-content: center;
    align-items: center;

    .preview-placeholder {
      width: 48px;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fafafa;
      border: 1px solid #e8e8e8;
      border-radius: 4px;
    }
  }

  .name-cell {
    display: flex;
    align-items: center;
    gap: 8px;

    .name-text {
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .edit-icon {
      color: #1890ff;
      cursor: pointer;
      opacity: 0;
      transition: opacity 0.2s;
      flex-shrink: 0;

      &:hover {
        color: #40a9ff;
      }
    }
  }

  .name-cell:hover .edit-icon {
    opacity: 1;
  }

  .danger-text {
    color: #ff4d4f;

    &:hover {
      color: #ff7875;
    }
  }
}
</style>
