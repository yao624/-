<template>
  <a-modal
    :open="open"
    title="导入广告账户"
    :confirm-loading="loading"
    ok-text="上传导入"
    @ok="handleSubmit"
    @cancel="emit('update:open', false)"
  >
    <a-upload
      :before-upload="beforeUpload"
      :file-list="fileList"
      :max-count="1"
      accept=".csv,.xlsx,.xls"
      @remove="handleRemove"
    >
      <a-button>选择文件</a-button>
    </a-upload>
  </a-modal>
</template>

<script setup lang="ts">
import { ref } from 'vue';

defineProps<{ open: boolean; loading?: boolean }>();
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'submit', file: File): void;
}>();

const fileList = ref<any[]>([]);

const beforeUpload = (file: File) => {
  fileList.value = [file as any];
  return false;
};

const handleRemove = () => {
  fileList.value = [];
};

const handleSubmit = () => {
  const file = fileList.value[0] as File | undefined;
  if (!file) return;
  emit('submit', file);
};
</script>
