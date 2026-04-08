<template>
  <a-modal
    v-model:open="visible"
    :title="displayTitle"
    :width="900"
    :closable="true"
    :footer="null"
    wrap-class-name="custom-column-selector-modal"
    @cancel="handleCancel"
  >
    <div class="custom-column-selector">
      <!-- 左侧面板 -->
      <div class="left-panel">
        <!-- 搜索框 -->
        <div class="search-box">
          <a-input
            v-model:value="searchKeyword"
            :placeholder="t('customColumn.searchPlaceholder')"
            allow-clear
          >
            <template #suffix>
              <SearchOutlined />
            </template>
          </a-input>
        </div>

        <!-- 分类数据区域 -->
        <div class="category-content">
          <div
            v-for="category in filteredCategories"
            :key="category.key"
            class="category-section"
          >
            <!-- 分类标题 -->
            <div class="category-header" @click="toggleCategory(category.key)">
              <CaretDownOutlined v-if="expandedCategories.includes(category.key)" class="arrow-icon" />
              <CaretRightOutlined v-else class="arrow-icon" />
              <span class="category-title">{{ category.label }}</span>
              <span class="category-count">({{ category.items.length }})</span>
              <a-checkbox
                class="category-select-all"
                :checked="isCategoryAllSelected(category)"
                :indeterminate="isCategoryPartiallySelected(category)"
                @click.stop="handleCategorySelectAll(category, $event)"
              />
            </div>

            <!-- 复选框列表 -->
            <div v-show="expandedCategories.includes(category.key)" class="checkbox-grid">
              <a-checkbox
                v-for="item in category.items"
                :key="item.key"
                :checked="isItemSelected(item)"
                class="checkbox-item"
                @change="handleCheckboxChange(item, $event)"
              >
                {{ item.label }}
              </a-checkbox>
            </div>
          </div>
        </div>

        <!-- 底部选项 -->
        <div class="left-footer">
          <a-checkbox v-model:checked="saveAsCommon">
            {{ t('customColumn.saveAsCommon') }}
          </a-checkbox>
        </div>
      </div>

      <!-- 右侧面板 -->
      <div class="right-panel">
        <div class="right-header">
          <span class="selected-count">
            {{ t('customColumn.selectedCount', { count: selectedItems.length }) }}
          </span>
          <a class="clear-link" @click="handleClearAll">
            {{ t('customColumn.clear') }}
          </a>
        </div>

        <div class="right-content">
          <p class="drag-hint">{{ t('customColumn.dragHint') }}</p>

          <div class="selected-list">
            <drag-container
              lock-axis="y"
              drag-class="dragging-item"
              drop-class="drop-target"
              @drop="handleDrop"
            >
              <draggable v-for="(item, index) in selectedItems" :key="item.key">
                <div class="selected-item">
                  <span class="drag-handle">
                    <DragIcon />
                  </span>
                  <span class="item-label">{{ item.label }}</span>
                  <span class="remove-btn" @click.stop="handleRemoveItem(index)">
                    <CloseOutlined />
                  </span>
                </div>
              </draggable>
            </drag-container>

            <div v-if="selectedItems.length === 0" class="empty-state">
              {{ t('customColumn.emptyState') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部按钮 -->
    <div class="modal-footer">
      <a-button @click="handleCancel">
        {{ t('customColumn.cancel') }}
      </a-button>
      <a-button type="primary" @click="handleConfirm">
        {{ t('customColumn.confirm') }}
      </a-button>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { SearchOutlined, CaretDownOutlined, CaretRightOutlined, CloseOutlined } from '@ant-design/icons-vue';
import { Container as DragContainer, Draggable } from '@/components/draggable';
import DragIcon from '@/components/table/drag-icon.vue';

// 类型定义
export interface ColumnItem {
  key: string;
  label: string;
}

export interface ColumnCategory {
  key: string;
  label: string;
  items: ColumnItem[];
}

export interface CustomColumnSelectorProps {
  open?: boolean;
  title?: string;
  categories?: ColumnCategory[];
  defaultSelected?: string[];
}

export interface CustomColumnSelectorEmits {
  (e: 'update:open', value: boolean): void;
  (e: 'confirm', value: { selected: ColumnItem[]; saveAsCommon: boolean }): void;
  (e: 'cancel'): void;
}

// Props
const props = withDefaults(defineProps<CustomColumnSelectorProps>(), {
  open: false,
  title: '',
  categories: () => [],
  defaultSelected: () => [],
});

// Emits
const emit = defineEmits<CustomColumnSelectorEmits>();

// I18n
const { t } = useI18n();

// 状态
const visible = ref(props.open);
const searchKeyword = ref('');
const expandedCategories = ref<string[]>([]);
const selectedItems = ref<ColumnItem[]>([]);
const saveAsCommon = ref(false);

// 计算属性：显示标题
const displayTitle = computed(() => {
  return props.title || t('customColumn.title');
});

// 计算属性：过滤后的分类数据
const filteredCategories = computed(() => {
  if (!searchKeyword.value) {
    return props.categories;
  }

  return props.categories
    .map(category => ({
      ...category,
      items: category.items.filter(item =>
        item.label.toLowerCase().includes(searchKeyword.value.toLowerCase())
      ),
    }))
    .filter(category => category.items.length > 0);
});

// 判断项目是否被选中
const isItemSelected = (item: ColumnItem): boolean => {
  return selectedItems.value.some(selected => selected.key === item.key);
};

// 判断分类是否全部选中
const isCategoryAllSelected = (category: ColumnCategory): boolean => {
  if (category.items.length === 0) return false;
  return category.items.every(item => isItemSelected(item));
};

// 判断分类是否部分选中
const isCategoryPartiallySelected = (category: ColumnCategory): boolean => {
  if (category.items.length === 0) return false;
  const selectedCount = category.items.filter(item => isItemSelected(item)).length;
  return selectedCount > 0 && selectedCount < category.items.length;
};

// 处理分类全选/取消全选
const handleCategorySelectAll = (category: ColumnCategory, event: any) => {
  const checked = event.target.checked;
  if (checked) {
    // 全选：添加所有未选中的项
    category.items.forEach(item => {
      if (!isItemSelected(item)) {
        selectedItems.value.push(item);
      }
    });
  } else {
    // 取消全选：移除所有该分类下的项
    category.items.forEach(item => {
      const index = selectedItems.value.findIndex(selected => selected.key === item.key);
      if (index > -1) {
        selectedItems.value.splice(index, 1);
      }
    });
  }
};

// 切换分类展开/收起
const toggleCategory = (categoryKey: string) => {
  const index = expandedCategories.value.indexOf(categoryKey);
  if (index > -1) {
    expandedCategories.value.splice(index, 1);
  } else {
    expandedCategories.value.push(categoryKey);
  }
};

// 处理复选框变化
const handleCheckboxChange = (item: ColumnItem, event: any) => {
  if (event.target.checked) {
    if (!isItemSelected(item)) {
      selectedItems.value.push(item);
    }
  } else {
    const index = selectedItems.value.findIndex(selected => selected.key === item.key);
    if (index > -1) {
      selectedItems.value.splice(index, 1);
    }
  }
};

// 处理拖拽排序
const handleDrop = ({ removedIndex, addedIndex }: { removedIndex: number; addedIndex: number }) => {
  const item = selectedItems.value.splice(removedIndex, 1)[0];
  selectedItems.value.splice(addedIndex, 0, item);
};

// 移除单个项目
const handleRemoveItem = (index: number) => {
  selectedItems.value.splice(index, 1);
};

// 清除全部
const handleClearAll = () => {
  selectedItems.value = [];
};

// 确认
const handleConfirm = () => {
  emit('confirm', {
    selected: [...selectedItems.value],
    saveAsCommon: saveAsCommon.value,
  });
  visible.value = false;
};

// 取消
const handleCancel = () => {
  emit('cancel');
  visible.value = false;
};

// 初始化选中项
const initSelectedItems = () => {
  if (props.defaultSelected.length > 0) {
    const allItems = props.categories.flatMap(category => category.items);
    selectedItems.value = allItems.filter(item =>
      props.defaultSelected.includes(item.key)
    );
  }
};

// 展开/收起所有分类
const expandAllCategories = () => {
  expandedCategories.value = props.categories.map(c => c.key);
};

// 监听 open 变化
watch(() => props.open, (newVal) => {
  visible.value = newVal;
  if (newVal) {
    initSelectedItems();
    expandAllCategories();
    searchKeyword.value = '';
    saveAsCommon.value = false;
  }
});

// 监听 visible 变化
watch(visible, (newVal) => {
  if (!newVal) {
    emit('update:open', false);
  }
});

// 暴露方法
defineExpose({
  selectedItems,
  clearAll: handleClearAll,
});
</script>

<style scoped lang="less">
.custom-column-selector-modal {
  .custom-column-selector {
    display: flex;
    gap: 16px;
    min-height: 400px;
    max-height: 500px;
  }

  // 左侧面板
  .left-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #f0f0f0;
    padding-right: 16px;
    overflow: hidden;

    .search-box {
      margin-bottom: 16px;
      flex-shrink: 0;

      :deep(.ant-input) {
        border-radius: 4px;
      }

      :deep(.ant-input-suffix) {
        color: #bfbfbf;
      }
    }

    .category-content {
      flex: 1;
      overflow-y: auto;
      padding-right: 8px;

      // 自定义滚动条
      &::-webkit-scrollbar {
        width: 6px;
      }

      &::-webkit-scrollbar-thumb {
        background-color: #d9d9d9;
        border-radius: 3px;

        &:hover {
          background-color: #bfbfbf;
        }
      }
    }

    .category-section {
      margin-bottom: 16px;

      .category-header {
        display: flex;
        align-items: center;
        padding: 8px 0;
        cursor: pointer;
        user-select: none;

        .arrow-icon {
          color: #1890ff;
          margin-right: 8px;
          font-size: 12px;
          transition: transform 0.2s;
          flex-shrink: 0;
        }

        .category-title {
          font-size: 14px;
          font-weight: 500;
          color: #262626;
          flex-shrink: 0;
        }

        .category-count {
          font-size: 13px;
          color: #999;
          margin-left: 4px;
          margin-right: auto;
        }

        .category-select-all {
          margin-left: 8px;
          flex-shrink: 0;

          :deep(.ant-checkbox-wrapper) {
            font-size: 13px;
          }
        }

        &:hover .category-title {
          color: #1890ff;
        }
      }

      .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px 16px;
        padding: 8px 0 8px 20px;

        .checkbox-item {
          margin: 0;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;

          :deep(.ant-checkbox) {
            .ant-checkbox-inner {
              border-radius: 2px;
            }

            &.ant-checkbox-checked .ant-checkbox-inner {
              background-color: #1890ff;
              border-color: #1890ff;
            }
          }

          :deep(span) {
            font-size: 13px;
            color: #595959;
          }
        }
      }
    }

    .left-footer {
      flex-shrink: 0;
      padding-top: 12px;
      border-top: 1px solid #f0f0f0;

      :deep(.ant-checkbox-wrapper) {
        span {
          font-size: 13px;
          color: #595959;
        }
      }
    }
  }

  // 右侧面板
  .right-panel {
    width: 260px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .right-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      flex-shrink: 0;

      .selected-count {
        font-size: 14px;
        font-weight: 500;
        color: #262626;
      }

      .clear-link {
        font-size: 13px;
        color: #1890ff;
        cursor: pointer;
        transition: color 0.2s;

        &:hover {
          color: #40a9ff;
        }
      }
    }

    .right-content {
      flex: 1;
      overflow-y: auto;

      .drag-hint {
        font-size: 12px;
        color: #999;
        margin: 0 0 12px 0;
        padding: 0;
      }

      .selected-list {
        min-height: 100px;

        .selected-item {
          display: flex;
          align-items: center;
          padding: 8px 12px;
          margin-bottom: 8px;
          background-color: #fafafa;
          border: 1px solid #f0f0f0;
          border-radius: 4px;
          cursor: move;
          transition: all 0.2s;

          &:hover {
            background-color: #f5f5f5;
            border-color: #d9d9d9;

            .remove-btn {
              opacity: 1;
            }
          }

          &.dragging-item {
            opacity: 0.5;
            transform: scale(0.98);
          }

          .drag-handle {
            flex-shrink: 0;
            margin-right: 8px;
            color: #bfbfbf;
          }

          .item-label {
            flex: 1;
            font-size: 13px;
            color: #595959;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
          }

          .remove-btn {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff4d4f;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: 2px;

            &:hover {
              background-color: #fff1f0;
            }
          }
        }

        .empty-state {
          text-align: center;
          padding: 40px 16px;
          color: #bfbfbf;
          font-size: 13px;
        }
      }
    }
  }

  // 底部按钮
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding-top: 16px;
    border-top: 1px solid #f0f0f0;
    margin-top: 16px;
  }
}

// Modal 样式覆盖
:deep(.ant-modal) {
  .ant-modal-header {
    padding: 16px 24px;
    border-bottom: 1px solid #f0f0f0;

    .ant-modal-title {
      font-size: 16px;
      font-weight: 500;
      color: #262626;
    }
  }

  .ant-modal-close {
    top: 16px;
    right: 16px;

    .ant-modal-close-x {
      width: 32px;
      height: 32px;
      line-height: 32px;
      font-size: 14px;
      color: #8c8c8c;

      &:hover {
        color: #262626;
      }
    }
  }

  .ant-modal-body {
    padding: 16px 24px;
  }
}
</style>
