<template>
  <a-modal
    v-model:open="visible"
    :title="modalTitle"
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
        <span class="notification-text">{{ notificationText }}</span>
      </div>

      <!-- Filter Fields Grid -->
      <div class="filter-fields-grid">
        <div
          v-for="field in localFields"
          :key="field.key"
          class="filter-field-item"
        >
          <a-checkbox
            v-model:checked="field.checked"
            class="field-checkbox"
          />
          <span class="field-label">{{ field.label }}:</span>

          <!-- Select -->
          <a-select
            v-if="field.type === 'select'"
            v-model:value="field.value"
            :placeholder="field.placeholder || selectPlaceholder"
            :disabled="!field.checked"
            class="field-input"
            allow-clear
            show-search
            :filter-option="filterOption"
          >
            <a-select-option
              v-for="option in field.options"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </a-select-option>
          </a-select>

          <!-- Input -->
          <a-input
            v-else-if="field.type === 'input'"
            v-model:value="field.value"
            :placeholder="field.placeholder || inputPlaceholder"
            :disabled="!field.checked"
            class="field-input"
            allow-clear
          />

          <!-- Date Range -->
          <a-range-picker
            v-else-if="field.type === 'date-range'"
            v-model:value="field.value"
            :disabled="!field.checked"
            class="field-input"
          />

          <!-- Number Range -->
          <div v-else-if="field.type === 'number-range'" class="number-range-input">
            <a-input-number
              v-model:value="field.value[0]"
              :placeholder="numberStartPlaceholder"
              :disabled="!field.checked"
              class="field-input-number"
            />
            <span class="range-separator">-</span>
            <a-input-number
              v-model:value="field.value[1]"
              :placeholder="numberEndPlaceholder"
              :disabled="!field.checked"
              class="field-input-number"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <div class="modal-footer">
        <a-checkbox v-model:checked="saveAsTemplate" class="save-template-checkbox">
          {{ saveAsTemplateText }}
        </a-checkbox>
        <div class="footer-buttons">
          <a-button @click="handleCancel">
            {{ cancelText }}
          </a-button>
          <a-button type="primary" @click="handleConfirm">
            {{ confirmText }}
          </a-button>
        </div>
      </div>
    </template>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { CloseOutlined, InfoCircleOutlined } from '@ant-design/icons-vue';
import type { FilterFieldConfig } from '../types';

interface FilterModalProps {
  open?: boolean;
  fields: FilterFieldConfig[];
  visibleKeys: string[];
  currentValues: Record<string, any>;
  i18nPrefix?: string;
}

interface FilterModalEmits {
  (e: 'update:open', value: boolean): void;
  (e: 'confirm', filters: Record<string, any>): void;
  (e: 'update-visible-keys', keys: string[]): void;
}

const props = withDefaults(defineProps<FilterModalProps>(), {
  open: false,
  fields: () => [],
  visibleKeys: () => [],
  currentValues: () => ({}),
  i18nPrefix: 'components.filter',
});

const emit = defineEmits<FilterModalEmits>();

const { t } = useI18n();

const visible = ref(props.open);
const saveAsTemplate = ref(false);

// Local field state with checked and value
interface LocalField extends FilterFieldConfig {
  checked: boolean;
  value: any;
}

const localFields = ref<LocalField[]>([]);

// Computed i18n texts
const modalTitle = computed(() => t(`${props.i18nPrefix}.modal.title`));
const notificationText = computed(() => t(`${props.i18nPrefix}.modal.notification`));
const selectPlaceholder = computed(() => t(`${props.i18nPrefix}.modal.selectPlaceholder`));
const inputPlaceholder = computed(() => t(`${props.i18nPrefix}.modal.inputPlaceholder`));
const numberStartPlaceholder = computed(() => t(`${props.i18nPrefix}.modal.numberStartPlaceholder`));
const numberEndPlaceholder = computed(() => t(`${props.i18nPrefix}.modal.numberEndPlaceholder`));
const saveAsTemplateText = computed(() => t(`${props.i18nPrefix}.modal.saveAsTemplate`));
const cancelText = computed(() => t(`${props.i18nPrefix}.modal.cancel`));
const confirmText = computed(() => t(`${props.i18nPrefix}.modal.confirm`));

// Filter option for select
const filterOption = (input: string, option: any) => {
  return option?.label?.toLowerCase().includes(input.toLowerCase());
};

// Initialize localFields when modal opens
watch(() => props.open, (newVal) => {
  visible.value = newVal;
  if (newVal) {
    localFields.value = props.fields.map(f => ({
      ...f,
      checked: props.visibleKeys.includes(f.key),
      value: props.currentValues[f.key] ?? getDefaultValue(f.type),
    }));
  }
});

watch(visible, (newVal) => {
  if (!newVal) {
    emit('update:open', false);
  }
});

const getDefaultValue = (type: string) => {
  if (type === 'number-range') {
    return [undefined, undefined];
  }
  return undefined;
};

const handleCancel = () => {
  visible.value = false;
};

const handleConfirm = () => {
  const result: Record<string, any> = {};
  const visibleKeys: string[] = [];

  localFields.value.forEach(field => {
    result[field.key] = field.checked ? field.value : undefined;
    if (field.checked) {
      visibleKeys.push(field.key);
    }
  });

  emit('confirm', result);
  emit('update-visible-keys', visibleKeys);
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

      .number-range-input {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 0;

        .field-input-number {
          flex: 1;
          min-width: 0;
        }

        .range-separator {
          color: #8c8c8c;
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
