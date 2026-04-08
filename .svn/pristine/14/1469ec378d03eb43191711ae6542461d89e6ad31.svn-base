<template>
  <div class="template-actions">
    <a-dropdown>
      <a-button :disabled="!hasSelection">
        <template #icon>
          <AppstoreOutlined />
        </template>
        {{ t('pages.adTemplate.actions.batchOperation') }}
        <DownOutlined class="dropdown-icon" />
      </a-button>
      <template #overlay>
        <a-menu @click="handleMenuClick">
          <a-menu-item key="share"> 
            <ShareAltOutlined />
            {{ t('pages.adTemplate.actions.batchShare') }}
          </a-menu-item>
          <a-menu-item key="delete">
            <DeleteOutlined />
            {{ t('pages.adTemplate.actions.batchDelete') }}
          </a-menu-item>
        </a-menu>
      </template>
    </a-dropdown>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { AppstoreOutlined, DownOutlined, ShareAltOutlined, DeleteOutlined } from '@ant-design/icons-vue';

const props = defineProps<{
  hasSelection?: boolean;
}>();

const emit = defineEmits<{
  (e: 'batchShare'): void;
  (e: 'batchDelete'): void;
}>();

const { t } = useI18n();

const handleMenuClick = ({ key }: { key: string | number }) => {
  if (key === 'share') {
    emit('batchShare');
  } else if (key === 'delete') {
    emit('batchDelete');
  }
};
</script>

<style scoped lang="less">
.template-actions {
  margin-bottom: 16px;

  .dropdown-icon {
    margin-left: 4px;
    font-size: 12px;
  }
}
</style>
