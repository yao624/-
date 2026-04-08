<template>
  <a-modal
    v-model:open="visible"
    :title="t('pages.channelManage.filterModal.title')"
    :width="800"
    :closable="true"
    wrap-class-name="filter-modal"
    @cancel="handleCancel"
  >
    <template #closeIcon>
      <CloseOutlined />
    </template>

    <div class="filter-modal-content">
      <!-- Notification Bar -->
      <div class="notification-bar">
        <InfoCircleOutlined class="info-icon" />
        <span class="notification-text">{{ t('pages.channelManage.filterModal.notification') }}</span>
      </div>

      <!-- Filter Fields Grid -->
      <div class="filter-fields-grid">
        <div
          v-for="field in localFilters"
          :key="field.key"
          class="filter-field-item"
        >
          <a-checkbox
            v-model:checked="field.checked"
            class="field-checkbox"
          />
          <span class="field-label">{{ field.label }}:</span>
          <a-select
            v-if="field.type === 'select'"
            v-model:value="field.value"
            :placeholder="t('pages.channelManage.filterModal.selectPlaceholder')"
            :disabled="!field.checked"
            class="field-input"
            allow-clear
          >
            <a-select-option
              v-for="option in field.options"
              :key="option"
              :value="option"
            >
              {{ option }}
            </a-select-option>
          </a-select>
          <a-input
            v-else
            v-model:value="field.value"
            :placeholder="t('pages.channelManage.filterModal.inputPlaceholder')"
            :disabled="!field.checked"
            class="field-input"
            allow-clear
          />
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <div class="modal-footer">
        <a-checkbox v-model:checked="saveAsTemplate" class="save-template-checkbox">
          {{ t('pages.channelManage.filterModal.saveAsTemplate') }}
        </a-checkbox>
        <div class="footer-buttons">
          <a-button @click="handleCancel">
            {{ t('pages.channelManage.filterModal.cancel') }}
          </a-button>
          <a-button type="primary" @click="handleConfirm">
            {{ t('pages.channelManage.filterModal.confirm') }}
          </a-button>
        </div>
      </div>
    </template>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { CloseOutlined, InfoCircleOutlined } from '@ant-design/icons-vue';

export interface FilterField {
  key: string;
  label: string;
  type: 'input' | 'select';
  checked: boolean;
  value?: string;
  options?: string[];
}

interface Props {
  open?: boolean;
  filters: FilterField[];
  visibleKeys?: string[];
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'confirm', filters: Record<string, string | undefined>): void;
  (e: 'update-visible-filters', keys: string[]): void;
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  filters: () => [],
  visibleKeys: () => [],
});

const emit = defineEmits<Emits>();

const { t } = useI18n();

const visible = ref(props.open);
const saveAsTemplate = ref(false);

// Create a local reactive copy of filters
const localFilters = ref<FilterField[]>([]);

// Initialize localFilters when modal opens
watch(() => props.open, (newVal) => {
  visible.value = newVal;
  if (newVal) {
    // Deep copy the filters to local state and set checked based on visibleKeys
    localFilters.value = props.filters.map(f => ({
      ...f,
      checked: props.visibleKeys.includes(f.key) || f.checked,
      value: f.value
    }));
  }
});

watch(visible, (newVal) => {
  if (!newVal) {
    emit('update:open', false);
  }
});

const handleCancel = () => {
  visible.value = false;
};

const handleConfirm = () => {
  const result: Record<string, string | undefined> = {};
  const visibleKeys: string[] = [];

  localFilters.value.forEach(field => {
    if (field.checked && field.value) {
      result[field.key] = field.value;
    } else {
      result[field.key] = undefined;
    }
    // Collect visible filter keys (checked filters)
    if (field.checked) {
      visibleKeys.push(field.key);
    }
  });

  console.log('Filter confirm result:', result);
  console.log('Visible filter keys:', visibleKeys);

  emit('confirm', result);
  emit('update-visible-filters', visibleKeys);
  visible.value = false;
};
</script>

<style scoped lang="less">
.filter-modal {
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

    .ant-modal-footer {
      padding: 12px 24px;
      border-top: 1px solid #f0f0f0;
    }
  }
}

.filter-modal-content {
  .notification-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background-color: #e6f0ff;
    border-radius: 4px;
    margin-bottom: 20px;

    .info-icon {
      font-size: 14px;
      color: #1890ff;
      flex-shrink: 0;
    }

    .notification-text {
      font-size: 13px;
      color: #262626;
      line-height: 1.5;
    }
  }

  .filter-fields-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px 24px;

    .filter-field-item {
      display: flex;
      align-items: center;
      gap: 8px;

      .field-checkbox {
        flex-shrink: 0;

        :deep(.ant-checkbox-wrapper) {
          font-size: 14px;
        }
      }

      .field-label {
        font-size: 14px;
        color: #595959;
        white-space: nowrap;
        flex-shrink: 0;
      }

      .field-input {
        flex: 1;
        min-width: 0;

        :deep(.ant-select-selector),
        :deep(.ant-input) {
          border-radius: 4px;
        }

        :deep(.ant-select-disabled .ant-select-selector),
        :deep(.ant-input-disabled) {
          background-color: #f5f5f5;
          cursor: not-allowed;
        }
      }
    }
  }
}

.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;

  .save-template-checkbox {
    :deep(.ant-checkbox-wrapper) {
      font-size: 14px;
      color: #595959;
    }
  }

  .footer-buttons {
    display: flex;
    gap: 8px;
  }
}
</style>
