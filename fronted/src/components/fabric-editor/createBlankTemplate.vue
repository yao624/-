<!--
 * @Author: Claude AI
 * @Date: 2026-04-14
 * @Description: 创建空白模版
-->

<template>
  
  <div class="create-blank-template">
    <Button type="primary" @click="createBlank">
    {{ $t('createBlankTemplate') }}
  </Button>
  </div>

  
</template>
<script setup name="create-blank-template">
import { inject } from 'vue';
import { Modal } from 'view-ui-plus';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { Message } from 'view-ui-plus';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const props = defineProps({
  editingTemplate: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:editingTemplate']);

const canvasEditor = inject('canvasEditor');

/**
 * @desc 创建空白模版
 */
const createBlank = () => {
  Modal.confirm({
    title: t('tip'),
    content: `<p>${t('clearCanvasAndCreateBlank')}</p>`,
    okText: t('ok'),
    cancelText: t('cancel'),
    onOk: () => {
      try {
        // 清空画布
        canvasEditor.clear();
        if (canvasEditor.canvas && typeof canvasEditor.canvas.clearHistory === 'function') {
          canvasEditor.canvas.clearHistory();
        }
        canvasEditor.historyUpdate();

        // 重置模版信息
        emit('update:editingTemplate', null);

        // 清除 URL 中的 templateId 参数
        if (route.query.templateId) {
          router.replace({ query: {} });
        }

        Message.success(t('blankTemplateCreated'));
      } catch (error) {
        console.error('创建空白模版失败:', error);
        Message.error(t('createBlankTemplateFailed'));
      }
    },
  });
};
</script>

<style scoped>
.create-blank-template {
  margin-left: 18px;
} 
</style>