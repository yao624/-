<template>
  <div>
    <a-button
      type="primary"
      v-if="!props.hideTriggerButton"
      @click="openModal"
      class="custom-select-btn"
    >
      <span v-if="triggerButtonText">{{ triggerButtonText }}</span>
      <template #icon><select-outlined /></template>
    </a-button>

    <a-modal
      v-model:visible="visible"
      :title="modalTitle"
      :width="width"
      @ok="handleOk"
      @cancel="handleCancel"
      :mask-closable="false"
      :destroyOnClose="true"
      class="custom-modal"
      :ok-text="t('Confirm')"
      :cancel-text="t('Cancel')"
      :z-index="1030"
    >
      <div class="modal-content-wrapper">
        <div class="search-container">
          <div class="custom-search-wrapper">
            <input
              v-model="searchText"
              class="custom-search-input"
              :placeholder="t('Search by name...')"
              @keyup.enter="handleSearch"
            />
            <button class="custom-search-button" @click="handleSearch">
              <search-outlined />
            </button>
            <span v-if="searchText" class="clear-icon" @click="clearSearch">
              <close-circle-filled />
            </span>
          </div>
        </div>
        <div class="table-wrapper">
          <a-table
            :custom-row="customRowEvents"
            :row-selection="rowSelection"
            :columns="columns"
            :data-source="dataSource"
            :pagination="pagination"
            :loading="loading"
            @change="handleTableChange"
            :row-key="(item: Item) => item.id"
            class="custom-table"
            size="middle"
          >
            <template #bodyCell="{ column, record }">
              <template v-if="props.customRender && column.key in props.customRender">
                <component :is="props.customRender[column.key]" :record="record" :column="column" />
              </template>
              <template v-else-if="column.key === 'tags' && record.tags">
                <div class="tags-container">
                  <a-tag v-for="tag in record.tags" :key="tag.id || tag.name" class="custom-tag">
                    {{ tag.name }}
                  </a-tag>
                </div>
              </template>
            </template>
          </a-table>
        </div>
      </div>
    </a-modal>
  </div>
</template>

<script setup lang="ts">
import type { PropType } from 'vue';
import { ref, computed, reactive, watch, defineProps, defineEmits, defineExpose } from 'vue';
import { useI18n } from 'vue-i18n';
import { SelectOutlined, SearchOutlined, CloseCircleFilled } from '@ant-design/icons-vue';
import type { TableColumnsType, TableProps } from 'ant-design-vue';

// --- Interfaces ---
interface ApiParams {
  pageNo: number;
  pageSize: number;
  [key: string]: any;
}

interface ApiResponse {
  data: any[];
  totalCount?: number;
}

interface Item {
  id: string | number;
  [key: string]: any;
}

// --- Props ---
const props = defineProps({
  api: {
    type: Function as PropType<(params: ApiParams) => Promise<ApiResponse>>,
    required: true,
  },
  columns: {
    type: Array as PropType<TableColumnsType>,
    required: true,
  },
  multiple: {
    type: Boolean,
    default: true,
  },
  title: {
    type: String,
    default: '',
  },
  allowEmpty: {
    type: Boolean,
    default: true,
  },
  width: {
    type: [String, Number],
    default: 900,
  },
  customRender: {
    type: Object as PropType<Record<string, any>>,
    default: () => ({}),
  },
  defaultSelectedRowKeys: {
    type: Array as PropType<string[] | number[]>,
    default: () => [],
  },
  defaultSelectedRows: {
    type: Array as PropType<any[]>,
    default: () => [],
  },
  buttonText: {
    type: String,
    default: '',
  },
  // Optional prop to hide the default trigger button
  hideTriggerButton: {
    type: Boolean,
    default: false,
  },
});

// --- Emits ---
const emit = defineEmits<{
  (e: 'confirm:items-selected', keys: (string | number)[], rows: any[]): void;
}>();

// --- State ---
const { t } = useI18n();
const visible = ref(false);
const searchText = ref('');
const loading = ref(false);
const dataSource = ref<Item[]>([]);
const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
  showSizeChanger: true,
  pageSizeOptions: ['10', '20', '50', '100'],
});
const selectedRowKeys = ref<(string | number)[]>([]);
const selectedRows = ref<any[]>([]);

// --- Computed ---
const modalTitle = computed(() => props.title || t('Select Items'));
const triggerButtonText = computed(() => props.buttonText || t('Select')); // Renamed to avoid conflict with template variable

const rowSelection = computed<TableProps['rowSelection']>(() => ({
  type: props.multiple ? 'checkbox' : 'radio',
  selectedRowKeys: selectedRowKeys.value,
  onChange: onSelectionChange,
}));

// --- Methods ---
const fetchData = async (searchParams = {}) => {
  loading.value = true;
  try {
    const params: ApiParams = {
      pageNo: pagination.current,
      pageSize: pagination.pageSize,
      ...searchParams,
    };
    if (searchText.value) {
      params.name = searchText.value;
    }

    const response = await props.api(params);

    if (response && response.data) {
      dataSource.value = response.data;
      pagination.total =
        response.totalCount ??
        (response.data.length === pagination.pageSize
          ? pagination.current * pagination.pageSize + 1
          : (pagination.current - 1) * pagination.pageSize + response.data.length);
    } else {
      dataSource.value = [];
      pagination.total = 0;
    }
  } catch (error) {
    console.error('Error fetching data:', error);
    dataSource.value = [];
    pagination.total = 0;
  } finally {
    loading.value = false;
  }
};

const handleTableChange: TableProps['onChange'] = pag => {
  pagination.current = pag.current ?? 1;
  pagination.pageSize = pag.pageSize ?? 10;
  fetchData();
};

const handleSearch = () => {
  pagination.current = 1;
  fetchData();
};

const onSelectionChange = (keys: (string | number)[], rows: any[]) => {
  if (!props.multiple) {
    selectedRowKeys.value = keys.length ? [keys[0]] : [];
    selectedRows.value = rows.length ? [rows[0]] : [];
  } else {
    selectedRowKeys.value = keys;
    selectedRows.value = rows;
  }
};

const handleOk = () => {
  if (!props.allowEmpty && !selectedRowKeys.value.length) {
    return;
  }
  emit('confirm:items-selected', selectedRowKeys.value, selectedRows.value);
  handleCancel();
};

const openModal = () => {
  // Reset state when opened externally
  searchText.value = '';
  pagination.current = 1;
  // Set selection based on initial props passed *when opening*
  selectedRowKeys.value = [...props.defaultSelectedRowKeys];
  selectedRows.value = [...props.defaultSelectedRows];
  fetchData(); // Fetch initial data
  visible.value = true;
};

const handleCancel = () => {
  visible.value = false;
};

const handleRowClick = (record: Item) => {
  const recordId = record.id;
  if (!recordId) return;

  if (!props.multiple) {
    selectedRowKeys.value = [recordId];
    selectedRows.value = [record];
  } else {
    const index = selectedRowKeys.value.findIndex(key => key === recordId);
    if (index >= 0) {
      selectedRowKeys.value.splice(index, 1);
      const rowIndex = selectedRows.value.findIndex(row => row.id === recordId);
      if (rowIndex >= 0) {
        selectedRows.value.splice(rowIndex, 1);
      }
    } else {
      selectedRowKeys.value.push(recordId);
      selectedRows.value.push(record);
    }
  }
};

const customRowEvents = (record: Item) => ({
  onClick: () => handleRowClick(record),
  style: { cursor: 'pointer' },
});

// --- Watchers ---
watch(visible, newValue => {
  if (newValue) {
    // State resetting and initial fetch are now handled in openModal
  }
});

// --- Expose ---
defineExpose({ openModal });

// 添加清除搜索的方法
const clearSearch = () => {
  searchText.value = '';
  handleSearch();
};
</script>

<style lang="less" scoped>
.custom-select-btn {
  border-radius: 6px;
  height: 36px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(24, 144, 255, 0.1);
  background: linear-gradient(to bottom right, #1890ff, #36cfc9);
  border: none;
  padding: 0 16px;

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(24, 144, 255, 0.15);
    background: linear-gradient(to bottom right, #40a9ff, #40d3cc);
  }

  &:active {
    transform: translateY(0);
  }

  :deep(.anticon) {
    font-size: 16px;
  }
}

:deep(.custom-modal) {
  .ant-modal-content {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }

  .ant-modal-header {
    background: linear-gradient(to right, #f5f8ff, #f0f7ff);
    border-bottom: 1px solid #eef2ff;
    padding: 16px 24px;
  }

  .ant-modal-title {
    font-weight: 500;
    color: #333;
    font-size: 16px;
    letter-spacing: 0.5px;
    position: relative;
    padding-left: 8px;

    &::before {
      content: '';
      position: absolute;
      left: -4px;
      top: 50%;
      transform: translateY(-50%);
      width: 4px;
      height: 16px;
      background: linear-gradient(to bottom, #1890ff, #36cfc9);
      border-radius: 2px;
    }
  }

  .ant-modal-body {
    padding: 24px;
  }

  .ant-modal-footer {
    border-top: 1px solid #eef2ff;
    padding: 16px 24px;
    background: #fafbff;

    .ant-btn {
      border-radius: 6px;
      transition: all 0.3s ease;
      height: 36px;
      font-weight: 500;
      min-width: 90px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);

      &:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      }

      &:active {
        transform: translateY(0);
      }
    }

    .ant-btn-default {
      background: white;
      border: 1px solid #e8e8e8;

      &:hover {
        color: #1890ff;
        border-color: #1890ff;
      }
    }

    .ant-btn-primary {
      background: linear-gradient(to bottom right, #1890ff, #36cfc9);
      border: none;

      &:hover {
        background: linear-gradient(to bottom right, #40a9ff, #40d3cc);
      }
    }
  }
}

.modal-content-wrapper {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.search-container {
  position: relative;
  margin-bottom: 12px;
  width: 100%;
}

.custom-search-wrapper {
  display: flex;
  align-items: center;
  position: relative;
  width: 100%;
  height: 44px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;

  &:hover {
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
  }

  &:focus-within {
    box-shadow: 0 3px 15px rgba(24, 144, 255, 0.1);
  }
}

.custom-search-input {
  flex: 1;
  height: 100%;
  border: 1px solid #e8e8e8;
  border-right: none;
  background-color: #fafbff;
  padding: 0 16px;
  font-size: 14px;
  outline: none;
  transition: all 0.3s ease;
  border-radius: 8px 0 0 8px;

  &:hover,
  &:focus {
    background-color: #fff;
    border-color: #40a9ff;
  }

  &::placeholder {
    color: #bbb;
  }
}

.custom-search-button {
  width: 48px;
  height: 100%;
  border: none;
  background: linear-gradient(to bottom right, #1890ff, #36cfc9);
  color: white;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;

  &:hover {
    background: linear-gradient(to bottom right, #40a9ff, #40d3cc);
  }

  &:active {
    transform: scale(0.98);
  }
}

.clear-icon {
  position: absolute;
  right: 56px;
  top: 50%;
  transform: translateY(-50%);
  color: #bbb;
  cursor: pointer;
  font-size: 14px;
  padding: 4px;
  transition: all 0.2s ease;

  &:hover {
    color: #999;
  }
}

.table-wrapper {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
  position: relative;

  &::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(to right, rgba(24, 144, 255, 0.2), rgba(54, 207, 201, 0.2));
  }
}

.custom-table {
  :deep(.ant-table) {
    border-radius: 10px;
    overflow: hidden;
  }

  :deep(.ant-table-thead > tr > th) {
    background: linear-gradient(to right, #f5f8ff 30%, #f0f7ff);
    font-weight: 500;
    color: #333;
    padding: 14px 16px;
    border-bottom: 1px solid #e6f0ff;
    transition: background 0.3s ease;
    font-size: 13px;

    &:first-child {
      padding-left: 20px;
    }

    &:hover {
      background: #e6f0ff;
    }
  }

  :deep(.ant-table-tbody > tr) {
    transition: all 0.2s ease;

    > td {
      padding: 12px 16px;
      border-bottom: 1px solid #f0f0f0;
      font-size: 13px;

      &:first-child {
        padding-left: 20px;
      }
    }

    &:hover > td {
      background-color: #f7faff;
    }

    &.ant-table-row-selected > td {
      background-color: #e6f7ff;
    }

    &:last-child > td {
      border-bottom: none;
    }
  }

  :deep(.ant-pagination) {
    margin: 16px;

    .ant-pagination-item,
    .ant-pagination-prev .ant-pagination-item-link,
    .ant-pagination-next .ant-pagination-item-link {
      border-radius: 6px;
      transition: all 0.3s ease;
      border: 1px solid #e8e8e8;

      &:hover {
        border-color: #1890ff;
        color: #1890ff;
      }
    }

    .ant-pagination-item-active {
      border-color: #1890ff;
      background: #e6f7ff;

      a {
        color: #1890ff;
      }
    }
  }

  :deep(.ant-checkbox-wrapper) {
    margin-right: 8px;
  }

  :deep(.ant-checkbox-checked .ant-checkbox-inner) {
    background-color: #1890ff;
    border-color: #1890ff;
  }

  :deep(.ant-radio-checked .ant-radio-inner) {
    border-color: #1890ff;

    &:after {
      background-color: #1890ff;
    }
  }

  :deep(.ant-table-row) {
    cursor: pointer;
  }

  :deep(.ant-table-placeholder) {
    .ant-table-cell {
      border-bottom: none;
    }
  }

  :deep(.ant-empty-description) {
    color: #999;
  }

  :deep(.ant-table-cell) {
    &.ant-table-selection-column {
      width: 50px;
    }
  }
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.custom-tag {
  border-radius: 4px;
  padding: 2px 8px;
  margin: 0;
  font-size: 12px;
  background: #f0f5ff;
  color: #1890ff;
  border: 1px solid #d6e4ff;
  transition: all 0.3s ease;

  &:hover {
    background: #e6f7ff;
    color: #40a9ff;
  }
}
</style>
