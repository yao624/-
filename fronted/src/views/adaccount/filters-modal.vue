<template>
  <a-modal
    :open="visible"
    :title="t('pages.adc.filters.manage')"
    width="800px"
    :mask-closable="true"
    @ok="handleSave"
    @cancel="handleCancel"
  >
    <template #footer>
      <a-button @click="handleCancel">
        {{ t('pages.adc.filters.cancel') }}
      </a-button>
      <a-button type="primary" danger @click="handleClear" :loading="clearLoading">
        {{ t('pages.adc.filters.clear') }}
      </a-button>
      <a-button type="primary" @click="handleSave" :loading="saveLoading">
        {{ t('pages.adc.filters.save') }}
      </a-button>
    </template>

    <div class="filters-container">
      <!-- 头部说明 -->
      <div class="header-section">
        <div class="description">
          {{ t('pages.adc.filters.description') }}
        </div>
        <a-button type="primary" ghost @click="addFilter" :icon="h(PlusOutlined)" class="add-btn">
          {{ t('pages.adc.filters.add') }}
        </a-button>
      </div>

      <!-- 空状态 -->
      <div v-if="filters.length === 0" class="empty-state">
        <a-empty>
          <template #image>
            <filter-outlined style="font-size: 48px; color: #bfbfbf;" />
          </template>
          <template #description>
            <span class="empty-text">{{ t('pages.adc.filters.empty.title') }}</span>
            <div class="empty-subtext">{{ t('pages.adc.filters.empty.subtitle') }}</div>
          </template>
        </a-empty>
      </div>

      <!-- 过滤器列表 -->
      <div v-else class="filters-list">
        <div
          v-for="(filter, index) in filters"
          :key="index"
          class="filter-item"
        >
          <div class="filter-header">
            <span class="filter-number">{{ t('pages.adc.filters.condition') }} {{ index + 1 }}</span>
            <a-button
              type="text"
              danger
              :icon="h(DeleteOutlined)"
              size="small"
              @click="removeFilter(index)"
              class="delete-btn"
            />
          </div>

          <div class="filter-form">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">{{ t('pages.adc.filters.field') }}</label>
                <a-select
                  v-model:value="filter.field"
                  :placeholder="t('pages.adc.filters.field')"
                  @change="onFieldChange(filter)"
                  :options="getFieldOptions(index)"
                  class="form-control"
                />
              </div>

              <div class="form-group">
                <label class="form-label">{{ t('pages.adc.filters.operator') }}</label>
                <a-select
                  v-model:value="filter.operator"
                  :placeholder="t('pages.adc.filters.operator')"
                  :options="getOperatorOptions(filter.field)"
                  class="form-control"
                  :disabled="!filter.field"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">{{ t('pages.adc.filters.value') }}</label>
                <!-- 时间选择器 -->
                <a-date-picker
                  v-if="filter.field === 'created_time'"
                  :value="dayjs.isDayjs(filter.value) ? filter.value : undefined"
                  :placeholder="t('pages.adc.filters.value')"
                  :disabled-date="disabledDate"
                  @change="onTimeChange(filter, $event)"
                  class="form-control"
                />
                <!-- 状态多选器 -->
                <a-select
                  v-else-if="isStatusField(filter.field)"
                  :value="Array.isArray(filter.value) ? filter.value : []"
                  @update:value="(val) => filter.value = val"
                  mode="multiple"
                  :placeholder="t('pages.adc.filters.value')"
                  :options="getStatusOptions(filter.field)"
                  class="form-control"
                  :disabled="!filter.operator"
                />
                <!-- 占位符输入框 - 当没有选择字段时显示 -->
                <a-input
                  v-else
                  :placeholder="getValuePlaceholder(filter)"
                  disabled
                  class="form-control placeholder-input"
                />
              </div>

              <div class="form-group">
                <label class="form-label">{{ t('pages.adc.filters.scope') }}</label>
                <a-select
                  v-model:value="filter.scope"
                  mode="multiple"
                  :placeholder="t('pages.adc.filters.scope')"
                  :options="getScopeOptions(filter.field)"
                  class="form-control"
                  :disabled="!filter.field"
                />
              </div>
            </div>
          </div>

          <!-- 验证错误提示 -->
          <div v-if="filter.errors && filter.errors.length > 0" class="error-messages">
            <a-alert
              v-for="error in filter.errors"
              :key="error"
              :message="error"
              type="error"
              show-icon
              class="error-alert"
            />
          </div>
        </div>
      </div>
    </div>
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, ref, watch, h } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import { PlusOutlined, DeleteOutlined, FilterOutlined } from '@ant-design/icons-vue';
import type { Dayjs } from 'dayjs';
import dayjs from 'dayjs';
import { updateAdAccountFilters, clearAdAccountFilters } from '@/api/fb_ad_accounts';

interface FilterItem {
  field: string;
  operator: string;
  value: any;
  scope: string[];
  errors?: string[];
}

export default defineComponent({
  name: 'FiltersModal',
  components: {
    FilterOutlined,
  },
  emits: ['success', 'cancel'],
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    adAccountIds: {
      type: Array as () => string[],
      default: () => [],
    },
    currentFilters: {
      type: Array as () => FilterItem[],
      default: () => [],
    },
  },
  setup(props, { emit }) {
    const { t } = useI18n();
    const saveLoading = ref(false);
    const clearLoading = ref(false);
    const filters = ref<FilterItem[]>([]);

    // 获取特定过滤器的可用字段选项（排除已选择的字段）
    const getFieldOptions = (currentIndex: number) => {
      const allOptions = [
        {
          label: t('pages.adc.filters.created_time'),
          value: 'created_time',
        },
        {
          label: t('pages.adc.filters.campaign_status'),
          value: 'campaign.effective_status',
        },
        {
          label: t('pages.adc.filters.adset_status'),
          value: 'adset.effective_status',
        },
        {
          label: t('pages.adc.filters.ad_status'),
          value: 'ad.effective_status',
        },
      ];

      // 获取其他过滤器已选择的字段
      const usedFields = filters.value
        .map((filter, index) => index !== currentIndex ? filter.field : null)
        .filter(field => field && field.trim() !== '');

      // 返回未被使用的字段选项
      return allOptions.filter(option => !usedFields.includes(option.value));
    };

    // 操作符选项
    const getOperatorOptions = (field: string) => {
      if (field === 'created_time') {
        return [
          { label: t('pages.adc.filters.greater_than'), value: 'GREATER_THAN' },
          { label: t('pages.adc.filters.less_than'), value: 'LESS_THAN' },
        ];
      }
      return [{ label: t('pages.adc.filters.in'), value: 'IN' }];
    };

    // 状态选项
    const getStatusOptions = (field: string) => {
      const campaignStatuses = [
        'ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'IN_PROCESS', 'WITH_ISSUES',
        'PENDING_REVIEW', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED',
      ];

      const adsetStatuses = [
        'ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED', 'IN_PROCESS', 'WITH_ISSUES',
        'PENDING_REVIEW', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED',
      ];

      const adStatuses = [
        'ACTIVE', 'PAUSED', 'DELETED', 'PENDING_REVIEW', 'DISAPPROVED', 'PREAPPROVED',
        'PENDING_BILLING_INFO', 'CAMPAIGN_PAUSED', 'ARCHIVED', 'ADSET_PAUSED', 'IN_PROCESS', 'WITH_ISSUES',
      ];

      let options: string[] = [];
      if (field === 'campaign.effective_status') {
        options = campaignStatuses;
      } else if (field === 'adset.effective_status') {
        options = adsetStatuses;
      } else if (field === 'ad.effective_status') {
        options = adStatuses;
      }

      return options.map(status => ({
        label: status,
        value: status,
      }));
    };

    // 范围选项
    const getScopeOptions = (field: string) => {
      if (field === 'created_time') {
        return [
          { label: t('pages.adc.filters.campaign'), value: 'campaign' },
          { label: t('pages.adc.filters.adset'), value: 'adset' },
          { label: t('pages.adc.filters.ad'), value: 'ad' },
        ];
      } else if (field === 'campaign.effective_status') {
        return [{ label: t('pages.adc.filters.campaign'), value: 'campaign' }];
      } else if (field === 'adset.effective_status') {
        return [{ label: t('pages.adc.filters.adset'), value: 'adset' }];
      } else if (field === 'ad.effective_status') {
        return [{ label: t('pages.adc.filters.ad'), value: 'ad' }];
      }
      return [];
    };

    // 判断是否为状态字段
    const isStatusField = (field: string) => {
      return field.includes('effective_status');
    };

    // 获取值字段的占位符文本
    const getValuePlaceholder = (filter: FilterItem) => {
      if (!filter.field) {
        return t('pages.adc.filters.placeholder.select_field_first');
      }
      if (!filter.operator) {
        return t('pages.adc.filters.placeholder.select_operator_first');
      }
      return t('pages.adc.filters.value');
    };

    // 禁用未来日期
    const disabledDate = (current: Dayjs) => {
      return current && current > dayjs().endOf('day');
    };

        // 添加过滤器
    const addFilter = () => {
      filters.value.push({
        field: '',
        operator: '',
        value: '',
        scope: [],
        errors: [],
      });
    };

    // 移除过滤器
    const removeFilter = (index: number) => {
      filters.value.splice(index, 1);
    };

    // 字段变化处理
    const onFieldChange = (filter: FilterItem) => {
      // 重置其他字段
      filter.operator = '';
      filter.value = '';
      filter.scope = [];
      filter.errors = [];

      // 设置默认值
      const scopeOptions = getScopeOptions(filter.field);
      if (scopeOptions.length === 1) {
        filter.scope = [scopeOptions[0].value];
      }

      const operatorOptions = getOperatorOptions(filter.field);
      if (operatorOptions.length === 1) {
        filter.operator = operatorOptions[0].value;
      }
    };

    // 时间变化处理
    const onTimeChange = (filter: FilterItem, date: any) => {
      if (date && dayjs.isDayjs(date)) {
        filter.value = date;
      } else {
        filter.value = null;
      }
    };

    // 验证过滤器
    const validateFilters = () => {
      let isValid = true;

      filters.value.forEach((filter) => {
        filter.errors = [];

        // 检查必填字段
        if (!filter.field) {
          filter.errors.push(t('pages.adc.filters.validation.field_required'));
          isValid = false;
        }
        if (!filter.operator) {
          filter.errors.push(t('pages.adc.filters.validation.operator_required'));
          isValid = false;
        }
        if (!filter.value || (Array.isArray(filter.value) && filter.value.length === 0)) {
          filter.errors.push(t('pages.adc.filters.validation.value_required'));
          isValid = false;
        }
        if (!filter.scope || filter.scope.length === 0) {
          filter.errors.push(t('pages.adc.filters.validation.scope_required'));
          isValid = false;
        }
      });

      return isValid;
    };

    // 构建API数据
    const buildApiData = () => {
      return filters.value.map(filter => {
        let value: string | string[] = Array.isArray(filter.value) ? filter.value : '';

        // 如果是时间字段且是Dayjs对象，转换为时间戳字符串
        if (filter.field === 'created_time' && dayjs.isDayjs(filter.value)) {
          value = filter.value.unix().toString();
        } else if (typeof filter.value === 'string') {
          value = filter.value;
        } else if (Array.isArray(filter.value)) {
          value = filter.value;
        }

        return {
          field: filter.field,
          operator: filter.operator,
          value: value,
          scope: filter.scope,
        };
      });
    };

    // 保存过滤器
    const handleSave = async () => {
      if (!validateFilters()) {
        return;
      }

      saveLoading.value = true;
      try {
        const apiData = buildApiData();
        await updateAdAccountFilters({
          ids: props.adAccountIds,
          filters: apiData,
        });

        message.success(t('pages.adc.filters.success'));
        emit('success');
      } catch (error) {
        console.error('Save filters error:', error);
        message.error(t('pages.common.request.failed'));
      } finally {
        saveLoading.value = false;
      }
    };

    // 清除过滤器
    const handleClear = async () => {
      clearLoading.value = true;
      try {
        await clearAdAccountFilters({
          ids: props.adAccountIds,
        });

        message.success(t('pages.adc.filters.clear_success'));
        emit('success');
      } catch (error) {
        console.error('Clear filters error:', error);
        message.error(t('pages.common.request.failed'));
      } finally {
        clearLoading.value = false;
      }
    };

        // 重置表单数据
    const resetForm = () => {
      filters.value = [];
    };

    // 取消
    const handleCancel = () => {
      resetForm();
      emit('cancel');
    };

    // 简化的 watch 逻辑
    watch(
      () => props.visible,
      (newVal) => {
        if (newVal) {
          // Modal 打开时初始化数据
          filters.value = props.currentFilters.map(filter => {
            let processedValue = filter.value;

            // 处理时间字段的回显
            if (filter.field === 'created_time' && typeof filter.value === 'string') {
              const timestamp = parseInt(filter.value);
              if (!isNaN(timestamp)) {
                processedValue = dayjs.unix(timestamp);
              }
            }

            return {
              ...filter,
              value: processedValue,
              errors: [],
            };
          });

          // 如果没有过滤器，添加一个空的
          if (filters.value.length === 0) {
            addFilter();
          }
        }
      },
    );

    return {
      t,
      h,
      filters,
      saveLoading,
      clearLoading,
      getFieldOptions,
      getOperatorOptions,
      getStatusOptions,
      getScopeOptions,
      isStatusField,
      getValuePlaceholder,
      disabledDate,
      addFilter,
      removeFilter,
      onFieldChange,
      onTimeChange,
      handleSave,
      handleClear,
      handleCancel,
      PlusOutlined,
      DeleteOutlined,
      FilterOutlined,
      dayjs,
    };
  },
});
</script>

<style scoped>
.filters-container {
  min-height: 200px;
  padding: 4px 0;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  padding: 20px;
  background: linear-gradient(135deg, #f6f8fc 0%, #f0f4f8 100%);
  border-radius: 12px;
  border: 1px solid #e8f0fe;
}

.description {
  color: #64748b;
  font-size: 14px;
  line-height: 1.5;
  flex: 1;
  margin-right: 20px;
}

.add-btn {
  border-radius: 8px;
  height: 36px;
  font-weight: 500;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: #fafbfc;
  border-radius: 12px;
  border: 2px dashed #e1e8ed;
}

.empty-text {
  font-size: 16px;
  color: #334155;
  font-weight: 500;
}

.empty-subtext {
  font-size: 14px;
  color: #94a3b8;
  margin-top: 8px;
}

.filters-list {
  space-y: 16px;
}

.filter-item {
  margin-bottom: 20px;
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: all 0.2s ease;
  overflow: hidden;
}

.filter-item:hover {
  border-color: #1677ff;
  box-shadow: 0 4px 12px rgba(22, 119, 255, 0.12);
}

.filter-item:last-child {
  margin-bottom: 0;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px 12px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.filter-number {
  font-size: 14px;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.delete-btn {
  border-radius: 6px;
  height: 28px;
  width: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.delete-btn:hover {
  background: #fee2e2;
  color: #dc2626;
}

.filter-form {
  padding: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.form-control {
  width: 100%;
  border-radius: 8px;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

.form-control:hover {
  border-color: #1677ff;
}

.form-control:focus,
.form-control.ant-select-focused {
  border-color: #1677ff;
  box-shadow: 0 0 0 3px rgba(22, 119, 255, 0.1);
}

.error-messages {
  margin-top: 16px;
  padding: 0 20px 16px;
}

.error-alert {
  margin-bottom: 8px;
  border-radius: 8px;
}

.error-alert:last-child {
  margin-bottom: 0;
}

.placeholder-input {
  background: #f8fafc !important;
  border-style: dashed !important;
  color: #94a3b8 !important;
}

.placeholder-input::placeholder {
  color: #94a3b8 !important;
  font-style: italic;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: stretch;
  }

  .description {
    margin-right: 0;
    margin-bottom: 16px;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .filter-form {
    padding: 16px;
  }
}

/* 暗色主题适配 */
@media (prefers-color-scheme: dark) {
  .header-section {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-color: #475569;
  }

  .description {
    color: #cbd5e1;
  }

  .filter-item {
    background: #1e293b;
    border-color: #475569;
  }

  .filter-header {
    background: #334155;
    border-color: #475569;
  }

  .filter-number {
    color: #cbd5e1;
  }

  .form-label {
    color: #e2e8f0;
  }

  .empty-state {
    background: #1e293b;
    border-color: #475569;
  }

  .empty-text {
    color: #e2e8f0;
  }

  .empty-subtext {
    color: #94a3b8;
  }
}
</style>