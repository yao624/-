<template>
  <a-modal
    :open="open"
    title="像素管理"
    :footer="null"
    width="680px"
    @cancel="emit('update:open', false)"
  >
    <a-space direction="vertical" style="width: 100%">
      <a-space>
        <a-input v-model:value="pixelName" placeholder="新像素名称" style="width: 260px" />
        <a-button type="primary" :loading="loading" @click="handleAttach">新增并绑定</a-button>
      </a-space>
      <a-table :columns="columns" :data-source="pixels" :pagination="false" row-key="id" size="small">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'actions'">
            <a-button danger size="small" :loading="loading" @click="emit('detach', record)">解绑</a-button>
          </template>
        </template>
      </a-table>
    </a-space>
  </a-modal>
</template>

<script setup lang="ts">
import { ref } from 'vue';

defineProps<{ open: boolean; loading?: boolean; pixels: any[] }>();
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'attach', name: string): void;
  (e: 'detach', pixel: any): void;
}>();

const pixelName = ref('');

const columns = [
  { title: '像素ID', dataIndex: 'source_id', key: 'source_id' },
  { title: '像素名称', dataIndex: 'name', key: 'name' },
  { title: '操作', key: 'actions', width: 90 },
];

const handleAttach = () => {
  const value = pixelName.value.trim();
  if (!value) return;
  emit('attach', value);
  pixelName.value = '';
};
</script>
