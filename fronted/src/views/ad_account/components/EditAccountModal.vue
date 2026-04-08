<template>
  <a-modal
    v-model:open="visible"
    :title="modalTitle"
    :confirm-loading="loading"
    :width="500"
    @ok="handleOk"
    @cancel="handleCancel"
  >
    <a-form
      ref="formRef"
      :model="formData"
      :label-col="{ span: isBatch ? 7 : 5 }"
      :wrapper-col="{ span: isBatch ? 16 : 18 }"
      :rules="rules"
    >
      <!-- 批量编辑模式 -->
      <template v-if="isBatch">
        <!-- 已选择的广告账户列表 -->
        <a-form-item :label="t('pages.adAccount.edit.selectedAccounts')" :colon="false">
          <div class="selected-accounts-list">
            <div
              v-for="account in selectedAccounts"
              :key="account.id"
              class="account-item"
            >
              <component :is="getPlatformIcon(account.platform)" class="account-platform-icon" />
              <div class="account-info">
                <span class="account-id">{{ account.accountId }}</span>
                <span class="account-name">{{ account.accountName }}</span>
              </div>
            </div>
          </div>
        </a-form-item>

        <!-- 统一设置所属人员 -->
        <a-form-item :label="t('pages.adAccount.edit.unifiedOwner')" :colon="false">
          <a-checkbox v-model:checked="formData.unifiedOwner">
            {{ t('pages.adAccount.edit.unifiedOwnerLabel') }}
          </a-checkbox>
        </a-form-item>

        <!-- 所属人员（勾选后显示） -->
        <a-form-item
          v-show="formData.unifiedOwner"
          :label="t('pages.adAccount.edit.owner')"
          name="owner"
          :label-col="{ span: 7 }"
          :wrapper-col="{ span: 16 }"
        >
          <a-select
            v-model:value="formData.owner"
            :options="ownerOptions"
            :placeholder="t('pages.adAccount.edit.selectOwner')"
            show-search
            :filter-option="filterSelectOption"
            :dropdown-style="{ maxHeight: '400px', overflow: 'auto' }"
          />
        </a-form-item>

        <!-- 统一设置协助人员 -->
        <a-form-item :label="t('pages.adAccount.edit.unifiedAssistants')" :colon="false">
          <a-checkbox v-model:checked="formData.unifiedAssistants">
            {{ t('pages.adAccount.edit.unifiedAssistantsLabel') }}
          </a-checkbox>
        </a-form-item>

        <!-- 协助人员（勾选后显示） -->
        <a-form-item
          v-show="formData.unifiedAssistants"
          :label="t('pages.adAccount.edit.assistants')"
          :label-col="{ span: 7 }"
          :wrapper-col="{ span: 16 }"
        >
          <a-select
            v-model:value="formData.assistants"
            :options="assistantOptions"
            :placeholder="t('pages.adAccount.edit.searchPlaceholder')"
            show-search
            :filter-option="filterSelectOption"
            mode="multiple"
            :max-tag-count="2"
            :dropdown-style="{ maxHeight: '400px', overflow: 'auto' }"
          />
        </a-form-item>
      </template>

      <!-- 单个编辑模式 -->
      <template v-else>
        <!-- 渠道（只读） -->
        <a-form-item :label="t('pages.adAccount.edit.channel')">
          <span class="channel-text">{{ platformLabel }}</span>
        </a-form-item>

        <!-- 所属人员（必填） -->
        <a-form-item
          :label="t('pages.adAccount.edit.owner')"
          name="owner"
        >
          <a-select
            v-model:value="formData.owner"
            :options="ownerOptions"
            :placeholder="t('pages.adAccount.edit.selectOwner')"
            show-search
            :filter-option="filterSelectOption"
            :dropdown-style="{ maxHeight: '400px', overflow: 'auto' }"
          />
        </a-form-item>

        <!-- 协助人员（可选，带搜索） -->
        <a-form-item :label="t('pages.adAccount.edit.assistants')">
          <a-select
            v-model:value="formData.assistants"
            :options="assistantOptions"
            :placeholder="t('pages.adAccount.edit.searchPlaceholder')"
            show-search
            :filter-option="filterSelectOption"
            mode="multiple"
            :max-tag-count="2"
            :dropdown-style="{ maxHeight: '400px', overflow: 'auto' }"
          />
        </a-form-item>
      </template>
    </a-form>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Checkbox } from 'ant-design-vue';
import type { FormInstance } from 'ant-design-vue';
import type { PlatformType } from '../types';

// Register components
const ACheckbox = Checkbox;

// Platform icons (simplified inline components)
const createPlatformIcon = (name: string) => ({
  template: `<span class="platform-icon">${name}</span>`,
});

const platformIcons: Record<PlatformType, any> = {
  meta: createPlatformIcon('Meta'),
  google: createPlatformIcon('Google'),
  tiktok: createPlatformIcon('TikTok'),
};

interface SelectedAccount {
  id: string;
  accountId: string;
  accountName: string;
  platform: PlatformType;
}

interface Props {
  open: boolean;
  platform?: PlatformType;
  isBatch?: boolean;
  record?: any;
  ownerOptions?: any[];
  assistantOptions?: any[];
  selectedAccounts?: SelectedAccount[];
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'confirm', value: any): void;
}

const props = withDefaults(defineProps<Props>(), {
  open: false,
  platform: 'meta',
  isBatch: false,
  ownerOptions: () => [],
  assistantOptions: () => [],
  selectedAccounts: () => [],
});

// 已选择的账户列表
const selectedAccounts = computed(() => props.selectedAccounts);

const emit = defineEmits<Emits>();

const { t, locale } = useI18n();

const formRef = ref<FormInstance>();
const loading = ref(false);

const visible = computed({
  get: () => props.open,
  set: (val) => emit('update:open', val),
});

const isBatch = computed(() => props.isBatch);

// 根据语言环境返回平台标签
const platformLabel = computed(() => {
  const isZh = locale.value.startsWith('zh');
  const platformConfig: Record<PlatformType, { name: string; nameEn: string }> = {
    meta: { name: 'Meta', nameEn: 'Meta' },
    google: { name: 'Google', nameEn: 'Google' },
    tiktok: { name: 'TikTok', nameEn: 'TikTok' },
  };
  const config = platformConfig[props.platform || 'meta'];
  return isZh ? config.name : config.nameEn;
});

// 平台图标
const platformIcon = computed(() => {
  return platformIcons[props.platform || 'meta'];
});

// 获取平台图标
const getPlatformIcon = (platform: PlatformType) => {
  return platformIcons[platform || 'meta'];
};

// 弹窗标题
const modalTitle = computed(() => {
  return props.isBatch
    ? t('pages.adAccount.edit.batchTitle')
    : t('pages.adAccount.edit.title');
});

// 表单数据
const formData = ref({
  // 批量编辑字段
  unifiedOwner: false,
  unifiedAssistants: false,
  // 通用字段
  owner: null as string | null,
  assistants: [] as string[],
});

// 选择器过滤选项
const filterSelectOption = (input: string, option: any) => {
  return option.label?.toLowerCase().includes(input.toLowerCase());
};

// 表单验证规则
const rules = computed(() => {
  const baseRules: any = {};

  // 单个编辑模式：所属人员必填
  if (!isBatch.value) {
    baseRules.owner = [
      {
        required: true,
        message: t('pages.adAccount.edit.ownerRequired'),
        type: 'string',
      },
    ];
  } else {
    // 批量编辑模式：只有勾选了统一设置所属人员时才需要验证
    if (formData.value.unifiedOwner) {
      baseRules.owner = [
        {
          required: true,
          message: t('pages.adAccount.edit.ownerRequired'),
          type: 'string',
        },
      ];
    }
  }

  return baseRules;
});

// 初始化表单数据
const initFormData = () => {
  if (isBatch.value) {
    // 批量编辑模式：重置所有字段
    formData.value.unifiedOwner = false;
    formData.value.unifiedAssistants = false;
    formData.value.owner = null;
    formData.value.assistants = [];
  } else {
    // 单个编辑模式
    if (props.record) {
      console.log('initFormData - props.record:', props.record);
      console.log('initFormData - owner:', props.record.owner);
      console.log('initFormData - assistants:', props.record.assistants);
      // owner 是对象 {id, name}，需要提取 id 并转为字符串
      formData.value.owner = props.record.owner?.id ? String(props.record.owner.id) : null;
      // assistants 是对象数组，需要提取 id 并转为字符串
      formData.value.assistants = (props.record.assistants || []).map((user: any) => String(user.id));
      console.log('initFormData - formData.value.owner:', formData.value.owner);
      console.log('initFormData - formData.value.assistants:', formData.value.assistants);
    } else {
      formData.value.owner = null;
      formData.value.assistants = [];
    }
  }
};

// 监听弹窗打开
watch(() => props.open, (val) => {
  if (val) {
    initFormData();
    formRef.value?.clearValidate();
  }
});

// 监听统一设置复选框变化，清除或添加验证
watch([() => formData.value.unifiedOwner, () => formData.value.unifiedAssistants], () => {
  if (isBatch.value) {
    formRef.value?.clearValidate();
  }
});

// 确定按钮
const handleOk = async () => {
  try {
    console.log('handleOk - formData.value:', formData.value);

    // 批量编辑模式下，只有勾选了统一设置才需要验证对应字段
    if (isBatch.value) {
      if (formData.value.unifiedOwner) {
        await formRef.value?.validate(['owner']);
      }
      // 协助人员是可选的，不需要验证
    } else {
      // 单个编辑模式，需要验证所有必填字段
      await formRef.value?.validate();
    }

    loading.value = true;
    // 模拟API调用
    await new Promise((resolve) => setTimeout(resolve, 500));

    const result: any = {};

    if (isBatch.value) {
      // 批量编辑模式
      if (formData.value.unifiedOwner) {
        result.owner = formData.value.owner;
      }
      if (formData.value.unifiedAssistants) {
        result.assistants = formData.value.assistants;
      }
    } else {
      // 单个编辑模式
      result.owner = formData.value.owner;
      result.assistants = formData.value.assistants;
    }

    console.log('handleOk - emit result:', result);
    emit('confirm', result);
    formRef.value?.resetFields();
  } finally {
    loading.value = false;
  }
};

// 取消按钮
const handleCancel = () => {
  formRef.value?.resetFields();
  visible.value = false;
};

// 暴露初始化方法
defineExpose({
  initFormData,
});
</script>

<style scoped lang="less">
:deep(.ant-modal) {
  .ant-modal-header {
    border-bottom: 1px solid #f0f0f0;
  }

  .ant-modal-body {
    padding: 24px;
  }

  .ant-modal-footer {
    border-top: 1px solid #f0f0f0;
  }
}

:deep(.ant-form-item) {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.channel-text {
  color: rgba(0, 0, 0, 0.85);
  font-size: 14px;
}

.channel-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  font-size: 12px;
  font-weight: 500;
  color: #fff;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 6px;
}

.selected-accounts-list {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #d9d9d9;
  border-radius: 6px;
  padding: 8px;
  background: #fafafa;

  .account-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 8px;
    margin-bottom: 4px;
    background: #fff;
    border-radius: 4px;
    transition: background-color 0.2s;

    &:hover {
      background: #f5f5f5;
    }

    &:last-child {
      margin-bottom: 0;
    }

    .account-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
      flex: 1;
      min-width: 0;
    }
  }

  .account-platform-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 6px;
    font-size: 10px;
    font-weight: 500;
    color: #fff;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
    flex-shrink: 0;
  }

  .account-id {
    font-size: 12px;
    color: rgba(0, 0, 0, 0.45);
    font-family: 'Courier New', monospace;
  }

  .account-name {
    font-size: 13px;
    color: rgba(0, 0, 0, 0.85);
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

</style>
