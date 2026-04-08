<template>
  <div v-if="showActions" class="account-actions">
    <a-space>
      <a-button type="primary" @click="handleAddAccount">
        {{ t('pages.adAccount.actions.addAccount') }}
      </a-button>
      <a-button :disabled="!hasSelection" @click="handleBatchOperation">
        {{ t('pages.adAccount.actions.batchOperation') }}
      </a-button>
      <a-button @click="handleSyncData" :loading="syncing">
        {{ t('pages.adAccount.actions.syncData') }}
      </a-button>
      <a-button @click="handleCustomColumns">
        {{ t('pages.adAccount.actions.customColumns') }}
      </a-button>
    </a-space>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { message } from 'ant-design-vue';
import type { FilterTab } from '../types';

const props = defineProps<{
  hasSelection?: boolean;
  currentTab?: FilterTab;
}>();

const emit = defineEmits<{
  (e: 'addAccount'): void;
  (e: 'batchOperation'): void;
  (e: 'syncData'): void;
  (e: 'customColumns'): void;
}>();

const { t } = useI18n();

const syncing = ref(false);

// 只在广告账户标签页显示操作按钮
const showActions = computed(() => props.currentTab === 'adAccounts');

const handleAddAccount = () => {
  emit('addAccount');
};

const handleBatchOperation = () => {
  emit('batchOperation');
};

const handleSyncData = async () => {
  syncing.value = true;
  try {
    emit('syncData');
    await new Promise((resolve) => setTimeout(resolve, 500));
    message.success(t('pages.adAccount.messages.syncSuccess'));
  } finally {
    syncing.value = false;
  }
};

const handleCustomColumns = () => {
  emit('customColumns');
};
</script>

<style scoped lang="less">
.account-actions {
  margin-bottom: 16px;
}
</style>
