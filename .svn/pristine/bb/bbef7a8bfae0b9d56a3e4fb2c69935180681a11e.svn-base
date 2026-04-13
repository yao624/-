<template>
  <a-modal
    :open="open"
    title="修改账户名称"
    :confirm-loading="loading"
    ok-text="保存"
    @ok="handleSubmit"
    @cancel="emit('update:open', false)"
  >
    <a-input v-model:value="name" placeholder="请输入新名称" />
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{ open: boolean; loading?: boolean; currentName?: string }>();
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'submit', value: string): void;
}>();

const name = ref('');

watch(
  () => props.open,
  opened => {
    if (opened) name.value = props.currentName || '';
  },
);

const handleSubmit = () => {
  emit('submit', name.value.trim());
};
</script>
