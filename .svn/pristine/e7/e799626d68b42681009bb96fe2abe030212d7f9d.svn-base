<template>
  <page-container :showPageHeader="false">
    <div class="local-template-page">
      <div class="content-card">
        <!-- 筛选区域 -->
        <div class="filter-area">
          <local-template-filter-component @search="handleSearch" />
        </div>

        <!-- 批量操作栏 -->
        <div class="batch-action-bar">
          <a-button :disabled="!hasSelected" @click="handleBatchDelete">
            {{ isZh ? '批量删除' : 'Batch Delete' }}
            <span v-if="hasSelected">({{ selectedRowKeys.length }})</span>
          </a-button>
          <a-button type="primary" @click="createNewTemplate">
            <template #icon><PlusOutlined /></template>
            {{ isZh ? '新建模版' : 'New Template' }}
          </a-button>
        </div>

        <!-- 表格区域 -->
        <div class="table-area">
          <local-template-table
            :data-source="templateData"
            :loading="loading"
            :pagination="pagination"
            :selected-row-keys="selectedRowKeys"
            @update:selected-row-keys="handleSelectionChange"
            @page-change="handlePageChange"
            @delete="handleDelete"
            @edit="handleEdit"
            @copy="handleCopy"
            @batch-generate="handleBatchGenerate"
          />
        </div>
      </div>
    </div>

    <!-- 批量生成弹窗 -->
    <batch-generate-modal ref="batchGenerateModalRef" />
  </page-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { message, Modal } from 'ant-design-vue';
import { PlusOutlined } from '@ant-design/icons-vue';
import LocalTemplateFilterComponent from './components/LocalTemplateFilter.vue';
import LocalTemplateTable from './components/LocalTemplateTable.vue';
import BatchGenerateModal from './components/BatchGenerateModal.vue';
import type { LocalTemplate, LocalTemplateFilter } from './types';
import * as creativeApi from '@/api/creative';

const { t, locale } = useI18n();
const router = useRouter();

const isZh = computed(() => locale.value.startsWith('zh'));

// Data
const templateData = ref<LocalTemplate[]>([]);
const loading = ref(false);
const selectedRowKeys = ref<string[]>([]);
const currentPage = ref(1);
const pageSize = ref(10);
const totalCount = ref(0);

// 筛选条件
const currentFilters = ref<LocalTemplateFilter>({});

// 批量生成弹窗引用
const batchGenerateModalRef = ref<InstanceType<typeof BatchGenerateModal> | null>(null);

// Computed
const pagination = computed(() => ({
  current: currentPage.value,
  pageSize: pageSize.value,
  total: totalCount.value,
  showSizeChanger: true,
  showQuickJumper: true,
  showTotal: (total: number) => isZh.value ? `共 ${total} 条` : `Total ${total} items`,
}));

const hasSelected = computed(() => selectedRowKeys.value.length > 0);

// Methods
const loadData = async () => {
  loading.value = true;
  try {
    const response: creativeApi.ApiResponse<creativeApi.ImageTemplateListResponse> =
      await creativeApi.getImageTemplateList({
        template_name: currentFilters.value.name,
        dimension: currentFilters.value.dimension,
        pageSize: pageSize.value,
        pageNo: currentPage.value,
      });

    if (response.status) {
      templateData.value = (response.data.data || []).map((item: any) => ({
        id: item.id || '',
        name: item.name,
        width: item.width,
        height: item.height,
        json: item.json, // 后端返回 json 字段
        dynamicVariables: item.dynamicVariables || [],
        createdAt: item.createdAt || '',
        updatedAt: item.updatedAt || '',
      }));
      totalCount.value = response.data.totalCount;
    } else {
      message.error(response.message || (isZh.value ? '加载数据失败' : 'Failed to load data'));
    }
  } catch (error: any) {
    console.error('Load data error:', error);
    message.error(error?.response?.data?.message || (isZh.value ? '加载数据失败' : 'Failed to load data'));
  } finally {
    loading.value = false;
  }
};

const handleSearch = (params: LocalTemplateFilter) => {
  currentFilters.value = { ...params };
  currentPage.value = 1;
  loadData();
};

const handleSelectionChange = (keys: string[]) => {
  selectedRowKeys.value = keys;
};

const handlePageChange = (page: number, size: number) => {
  currentPage.value = page;
  pageSize.value = size;
  loadData();
};

const createNewTemplate = () => {
  router.push('/creative/canvas');
};

const handleEdit = (record: LocalTemplate) => {
  router.push({
    path: '/creative/canvas',
    query: { templateId: record.id }
  });
};

const handleCopy = async (record: LocalTemplate) => {
  try {
    const response = await creativeApi.copyImageTemplate(record.id);
    if (response.status) {
      message.success(isZh.value ? '复制成功' : 'Copied successfully');

      // 跳转到画布页面编辑复制的模板
      router.push({
        path: '/creative/canvas',
        query: { templateId: response.data.id }
      });
    } else {
      message.error(response.message || (isZh.value ? '复制失败' : 'Failed to copy'));
    }
  } catch (error: any) {
    console.error('Copy template error:', error);
    message.error(error?.response?.data?.message || (isZh.value ? '复制失败' : 'Failed to copy'));
  }
};

const handleDelete = async (record: LocalTemplate) => {
  Modal.confirm({
    title: isZh.value ? '确认删除' : 'Confirm Delete',
    content: isZh.value
      ? `确定要删除模板 "${record.name}" 吗？`
      : `Are you sure to delete template "${record.name}"?`,
    onOk: async () => {
      try {
        const response = await creativeApi.deleteImageTemplate(record.id);
        if (response.status) {
          selectedRowKeys.value = selectedRowKeys.value.filter(key => key !== record.id);
          loadData();
          message.success(isZh.value ? '删除成功' : 'Deleted successfully');
        } else {
          message.error(response.message || (isZh.value ? '删除失败' : 'Failed to delete'));
        }
      } catch (error: any) {
        console.error('Delete template error:', error);
        message.error(error?.response?.data?.message || (isZh.value ? '删除失败' : 'Failed to delete'));
      }
    },
  });
};

const handleBatchDelete = () => {
  Modal.confirm({
    title: isZh.value ? '确认批量删除' : 'Confirm Batch Delete',
    content: isZh.value
      ? `确定要删除选中的 ${selectedRowKeys.value.length} 个模板吗？`
      : `Are you sure to delete ${selectedRowKeys.value.length} selected templates?`,
    onOk: async () => {
      try {
        const response = await creativeApi.batchDeleteImageTemplates(selectedRowKeys.value);
        if (response.status) {
          selectedRowKeys.value = [];
          loadData();
          message.success(isZh.value ? '批量删除成功' : 'Batch deleted successfully');
        } else {
          message.error(response.message || (isZh.value ? '批量删除失败' : 'Failed to batch delete'));
        }
      } catch (error: any) {
        console.error('Batch delete template error:', error);
        message.error(error?.response?.data?.message || (isZh.value ? '批量删除失败' : 'Failed to batch delete'));
      }
    },
  });
};

const handleBatchGenerate = (record: LocalTemplate) => {
  // 打开批量生成弹窗，并传入模板对象
  batchGenerateModalRef.value?.open(record);
};

// Lifecycle
onMounted(() => {
  loadData();
});
</script>

<style scoped lang="less">
.local-template-page {
  min-height: 500px;

  .content-card {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 16px;

    .filter-area {
      flex-shrink: 0;
    }

    .batch-action-bar {
      flex-shrink: 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .table-area {
      flex: 1;
      overflow: hidden;
      min-height: 0;
    }
  }
}
</style>
