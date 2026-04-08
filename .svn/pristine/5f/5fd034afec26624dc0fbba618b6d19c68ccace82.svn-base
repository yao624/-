<template>
  <div v-if="applied.length > 0" class="applied-filters-inline">
    <template v-for="filter in applied" :key="filter.key">
      <span class="filter-item">
        <span class="filter-label">{{ getFieldLabel(filter.key) }}</span>
        <a-tag
          :closable="true"
          @close="removeFilter(filter.key)"
          class="filter-value"
          size="small"
        >
          <a-tooltip :title="getFullValue(filter.value)" placement="bottom">
            <span>{{ getDisplayValue(filter.value) }}</span>
          </a-tooltip>
        </a-tag>
      </span>
    </template>

    <span class="filter-actions">
      <a-button
        size="small"
        type="text"
        @click="clearAllFilters"
        class="clear-btn"
      >
        <close-outlined />
        {{ t('Clear All') }}
      </a-button>

      <a-divider type="vertical" class="action-divider" />

            <a-tooltip :title="saveButtonTooltip" placement="bottom">
        <a-button
          size="small"
          type="text"
          @click="saveAsBookmark"
          class="save-btn"
          :disabled="!canSaveBookmark"
        >
          <book-outlined />
          {{ t('Save') }}
        </a-button>
      </a-tooltip>
    </span>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { CloseOutlined, BookOutlined } from '@ant-design/icons-vue';

export default defineComponent({
  name: 'AppliedFilters',
  components: {
    CloseOutlined,
    BookOutlined,
  },
  props: {
    filters: {
      type: Object,
      required: false,
      default: () => ({}),
    },
    resultCount: {
      type: Number,
      default: 0,
    },
    bookmarks: {
      type: Array,
      required: false,
      default: () => [],
    },
  },
  emits: ['remove-filter', 'clear-all', 'save-bookmark'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const applied = ref([]);

    // 字段名映射 - 将技术字段名映射为用户友好的标签
    const fieldLabels = {
      'campaign_tags': 'pages.compaign.tag',
      'ad_account_tags': 'pages.ads.acc.tags',
      'fb_account_tags': 'pages.ads.fb_acc.tags',
      'account_names': 'FB Account Name',
      'account_ids': 'FB Account ID',
      'ad_account_names': 'pages.ads.ad.acc_name',
      'ad_account_ids': 'pages.ads.ad.acc_id',
      'page_names': 'Page Name',
      'page_ids': 'Page ID',
      'bm_names': 'BM Name',
      'bm_ids': 'BM ID',
      'account_status': 'pages.adc.status',
      'is_archived': 'pages.adc.is_archived',
      'campaign_names': 'pages.compaign.name',
      'others': 'pages.ads.others',
    };

    // 标准化过滤条件对象，用于比较
    const normalizeFilters = (filters: any) => {
      const normalized = {};
      Object.keys(filters).sort().forEach(key => {
        const value = filters[key];
        if (Array.isArray(value)) {
          // 数组需要排序后再比较
          normalized[key] = [...value].sort();
        } else {
          normalized[key] = value;
        }
      });
      return normalized;
    };

    // 检查当前过滤条件是否与已有书签重复
    const isCurrentFiltersExistInBookmarks = computed(() => {
      if (!props.bookmarks || props.bookmarks.length === 0) {
        return false;
      }

      const currentNormalized = normalizeFilters(props.filters);

      return props.bookmarks.some((bookmark: any) => {
        if (!bookmark.search_conditions) return false;

        const bookmarkNormalized = normalizeFilters(bookmark.search_conditions);

        // 深度比较两个标准化后的对象
        return JSON.stringify(currentNormalized) === JSON.stringify(bookmarkNormalized);
      });
    });

    // 是否可以保存为书签
    const canSaveBookmark = computed(() => {
      // 至少1个条件才能保存，且当前条件不能与已有书签重复
      return applied.value.length >= 1 && !isCurrentFiltersExistInBookmarks.value;
    });

    // 保存按钮的提示文本
    const saveButtonTooltip = computed(() => {
      if (applied.value.length < 1) {
        return t('At least 1 condition required to save');
      }
      if (isCurrentFiltersExistInBookmarks.value) {
        return t('Current filters already saved as bookmark');
      }
      return t('Save current filters as bookmark');
    });

    // 获取字段的友好标签
    const getFieldLabel = (fieldKey: string) => {
      const labelKey = fieldLabels[fieldKey];
      if (labelKey) {
        return t(labelKey);
      }
      // 如果没有映射，就将下划线转换为空格并首字母大写
      return fieldKey.split('_').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1),
      ).join(' ');
    };

    // 处理显示值 - 限制长度
    const getDisplayValue = (value: any) => {
      const fullValue = getFullValue(value);
      const maxLength = 15; // 进一步缩短
      if (fullValue.length > maxLength) {
        return fullValue.substring(0, maxLength) + '...';
      }
      return fullValue;
    };

    // 获取完整值
    const getFullValue = (value: any) => {
      if (Array.isArray(value)) {
        return value.join(', ');
      }
      return String(value || '');
    };

    // 删除单个过滤条件
    const removeFilter = (fieldKey: string) => {
      emit('remove-filter', fieldKey);
    };

    // 清除所有过滤条件
    const clearAllFilters = () => {
      emit('clear-all');
    };

    // 保存为书签
    const saveAsBookmark = () => {
      emit('save-bookmark');
    };

    // 不需要显示在界面上的内部技术参数
    const hiddenFields = ['with-campaign', 'pageSize', 'pageNo', 'current', 'page'];

    // 监听 filters 变化
    watch(
      () => props.filters,
      (newValue) => {
        applied.value = Object.entries(newValue || {})
          .filter(([key, value]) => {
            // 过滤掉内部技术参数
            if (hiddenFields.includes(key)) {
              return false;
            }
            // 过滤掉空值
            if (Array.isArray(value)) {
              return value.length > 0;
            }
            return value !== null && value !== undefined && value !== '';
          })
          .map(([key, value]) => ({ key, value }));
      },
      { immediate: true, deep: true },
    );

    return {
      t,
      applied,
      canSaveBookmark,
      saveButtonTooltip,
      getFieldLabel,
      getDisplayValue,
      getFullValue,
      removeFilter,
      clearAllFilters,
      saveAsBookmark,
    };
  },
});
</script>

<style scoped>
.applied-filters-inline {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  padding: 4px 0;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 4px;
}

.filter-label {
  font-size: 12px;
  color: #595959;
  white-space: nowrap;
  font-weight: 500;
}

.filter-value {
  margin: 0;
  font-size: 11px;
  background: #f6ffed;
  border-color: #b7eb8f;
  color: #52c41a;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-value:hover {
  background: #d9f7be;
  border-color: #95de64;
  transform: translateY(-1px);
}

.filter-value .ant-tag-close-icon {
  color: #52c41a;
  font-size: 9px;
  margin-left: 3px;
  transition: color 0.2s;
}

.filter-value .ant-tag-close-icon:hover {
  color: #ff4d4f;
}

.filter-actions {
  display: flex;
  align-items: center;
  margin-left: auto;
  gap: 0;
}

.clear-btn, .save-btn {
  color: #8c8c8c;
  padding: 0 6px;
  height: 22px;
  font-size: 11px;
  transition: all 0.2s;
}

.clear-btn:hover {
  color: #ff4d4f;
  background: rgba(255, 77, 79, 0.06);
}

.save-btn:hover:not(:disabled) {
  color: #1890ff;
  background: rgba(24, 144, 255, 0.06);
}

.save-btn:disabled {
  color: #d9d9d9;
  cursor: not-allowed;
}

.action-divider {
  height: 12px;
  margin: 0 4px;
    border-color: #e8e8e8;
}

/* 响应式处理 */
@media (max-width: 768px) {
  .applied-filters-inline {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .filter-actions {
    margin-left: 0;
    align-self: flex-end;
  }
}

/* 优雅的过渡效果 */
.filter-item {
  animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>

