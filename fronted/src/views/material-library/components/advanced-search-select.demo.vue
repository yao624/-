<template>
  <div style="width: 420px; padding: 16px; background: #fff;">
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:14px; color:#333;">
      <span>设计师：</span>
      <AdvancedSearchSelect
        v-model="values"
        v-model:logicMode="logicMode"
        :categories="categories"
        :items="items"
        placeholder="请搜索"
      />
    </div>
    <div style="font-size:12px; color:#666;">
      logicMode: {{ logicMode }} | values: {{ values }}
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import AdvancedSearchSelect, {
  type AdvancedSelectCategory,
  type AdvancedSelectItem,
  type LogicMode,
} from './advanced-search-select.vue';

const logicMode = ref<LogicMode>('all');
const values = ref<Array<string | number>>([]);

const categories: AdvancedSelectCategory[] = [
  { key: 'uncategorized', label: '未分类' },
  { key: 'designTeam', label: '设计组' },
  { key: 'outsourcing', label: '外包' },
];

const items: AdvancedSelectItem[] = [
  { categoryKey: 'uncategorized', value: 'u1', label: '未分类' },
  { categoryKey: 'designTeam', value: 'd1', label: '小李' },
  { categoryKey: 'designTeam', value: 'd2', label: '小王' },
  { categoryKey: 'outsourcing', value: 'o1', label: '供应商 A' },
  { categoryKey: 'outsourcing', value: 'o2', label: '供应商 B' },
];
</script>

