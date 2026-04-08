<template>
  <page-container :showPageHeader="false">
    <div class="channel-manage-page">
      <div class="content-card">

        <!-- 筛选区域 -->
        <div class="filter-area">
          <filter-section
            ref="filterSectionRef"
            @search="handleSearch"
            @reset="handleReset"
          />
        </div>

        <!-- 操作栏 -->
        <div class="action-bar">
          <div class="action-left">
            <a-dropdown :disabled="!hasSelected">
              <a-button>
                {{ t('pages.channelManage.actions.batchOperation') }}
                <DownOutlined />
              </a-button>
              <template #overlay>
                <a-menu @click="handleBatchOperation">
                  <a-menu-item key="delete">
                    {{ t('pages.channelManage.actions.batchDelete') }}
                  </a-menu-item>
                </a-menu>
              </template>
            </a-dropdown>
          </div>
          <div class="action-right">
            <a-button @click="handleCustomColumns">
              {{ t('pages.channelManage.actions.customColumns') }}
            </a-button>
          </div>
        </div>

        <!-- 表格区域 -->
        <div class="table-area">
          <material-table
            :data-source="materialData"
            :loading="loading"
            :pagination="pagination"
            :selected-row-keys="selectedRowKeys"
            :visible-column-keys="visibleColumnKeys"
            @update:selected-row-keys="handleSelectionChange"
            @page-change="handlePageChange"
          />
        </div>
      </div>
    </div>

    <!-- 自定义列弹窗 -->
    <custom-column-selector
      v-model:open="columnSelectorVisible"
      :title="t('pages.channelManage.actions.customColumns')"
      :categories="columnCategories"
      :default-selected="visibleColumnKeys"
      @confirm="handleColumnConfirm"
    />
  </page-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { message, Modal } from 'ant-design-vue';
import { DownOutlined } from '@ant-design/icons-vue';
import FilterSection from './components/FilterSection.vue';
import MaterialTable from './components/MaterialTable.vue';
import CustomColumnSelector, { type ColumnCategory } from '@/components/custom-column-selector';
import { getMediaMaterialList } from '@/api/channelmanage';
import type { Material, MaterialFilter } from './types';

const { t } = useI18n();

// 常量
const STORAGE_KEY = 'channel-manage-visible-columns';

// 默认可见列
const DEFAULT_VISIBLE_COLUMNS = [
  'name',
  'materialId',
  'channel',
  'useAccountName',
  'belongAccountName',
  'size',
  'duration',
  'shape',
  'format',
  'source',
  'materialNote',
  'createTime',
  'rejectInfo',
];

// Data
const materialData = ref<Material[]>([]);
const loading = ref(false);
const selectedRowKeys = ref<string[]>([]);
const currentPage = ref(1);
const pageSize = ref(20);
const total = ref(0);
const visibleColumnKeys = ref<string[]>([...DEFAULT_VISIBLE_COLUMNS]);
const columnSelectorVisible = ref(false);


// 筛选条件
const currentFilters = ref<MaterialFilter>({});

// 列配置
const columnCategories = computed<ColumnCategory[]>(() => [
  {
    key: 'basic',
    label: t('pages.channelManage.columnCategory.basic'),
    items: [
      { key: 'name', label: t('pages.channelManage.table.materialName') },
      { key: 'materialId', label: t('pages.channelManage.table.materialId') },
      { key: 'channel', label: t('pages.channelManage.table.channel') },
    ],
  },
  {
    key: 'account',
    label: t('pages.channelManage.columnCategory.account'),
    items: [
      { key: 'useAccountName', label: t('pages.channelManage.table.useAccount') },
      { key: 'belongAccountName', label: t('pages.channelManage.table.belongAccount') },
    ],
  },
  {
    key: 'detail',
    label: t('pages.channelManage.columnCategory.detail'),
    items: [
      { key: 'size', label: t('pages.channelManage.table.size') },
      { key: 'duration', label: t('pages.channelManage.table.duration') },
      { key: 'shape', label: t('pages.channelManage.table.shape') },
      { key: 'format', label: t('pages.channelManage.table.format') },
      { key: 'source', label: t('pages.channelManage.table.source') },
    ],
  },
  {
    key: 'other',
    label: t('pages.channelManage.columnCategory.other'),
    items: [
      { key: 'materialNote', label: t('pages.channelManage.table.materialNote') },
      { key: 'createTime', label: t('pages.channelManage.table.createTime') },
      { key: 'rejectInfo', label: t('pages.channelManage.table.rejectInfo') },
    ],
  },
]);

// Computed
const pagination = computed(() => ({
  current: currentPage.value,
  pageSize: pageSize.value,
  total: total.value,
  showSizeChanger: true,
  showQuickJumper: true,
  showTotal: (total: number) => t('pages.channelManage.messages.total', { count: total }),
}));

const hasSelected = computed(() => selectedRowKeys.value.length > 0);

// Methods
const loadVisibleColumns = () => {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      const parsed = JSON.parse(stored);
      if (Array.isArray(parsed) && parsed.length > 0) {
        visibleColumnKeys.value = parsed;
      }
    }
  } catch (error) {
    console.error(t('pages.channelManage.messages.loadDataFailed'), error);
  }
};

const saveVisibleColumns = (keys: string[]) => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(keys));
  } catch (error) {
    console.error(t('pages.channelManage.messages.saveConfigFailed'), error);
  }
};

const loadData = async () => {
  loading.value = true;
  try {
    const response = await getMediaMaterialList({
      ...currentFilters.value,
      pageNo: currentPage.value,
      pageSize: pageSize.value,
    });
    if (response.status) {
      materialData.value = response.data.data;
      total.value = response.data.totalCount;
    } else {
      message.error(response.message || t('pages.channelManage.messages.getDataFailed'));
    }
  } catch (error) {
    message.error(t('pages.channelManage.messages.getDataFailed'));
  } finally {
    loading.value = false;
  }
};

const handleSearch = (filters: MaterialFilter) => {
  currentFilters.value = filters;
  currentPage.value = 1;
  loadData();
};

const handleReset = () => {
  currentFilters.value = {};
  currentPage.value = 1;
  selectedRowKeys.value = [];
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

const handleCustomColumns = () => {
  columnSelectorVisible.value = true;
};

const handleColumnConfirm = ({ selected }: { selected: any[] }) => {
  const keys = selected.map(item => item.key);
  visibleColumnKeys.value = keys;
  saveVisibleColumns(keys);
  message.success(t('pages.channelManage.messages.configSaved'));
};

const handleBatchDelete = () => {
  Modal.confirm({
    title: t('pages.channelManage.messages.batchDelete'),
    content: t('pages.channelManage.messages.batchDeleteConfirm', { count: selectedRowKeys.value.length }),
    onOk: async () => {
      // TODO: 实现批量删除 API 调用
      message.success(t('pages.channelManage.messages.batchDeleteSuccess'));
      selectedRowKeys.value = [];
      loadData();
    },
  });
};

const handleBatchOperation = ({ key }: { key: string | number }) => {
  if (key === 'delete') {
    handleBatchDelete();
  }
};

// Lifecycle
onMounted(() => {
  loadVisibleColumns();
  loadData();
});
</script>

<style scoped lang="less">
.channel-manage-page {
  min-height: 500px;

  .content-card {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 16px;

    .action-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;

      .action-left {
        display: flex;
        gap: 8px;
      }

      .action-right {
        display: flex;
        gap: 8px;
      }
    }

    .filter-area {
      flex-shrink: 0;
    }

    .table-area {
      flex: 1;
      overflow: hidden;
      min-height: 0;
    }
  }
}
</style>
