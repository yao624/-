<template>
  <div class="search-type">
    <Input
      v-model="searchKeyWord"
      :placeholder="$t('materials.searchPlaceholder') || '搜索素材'"
      clearable
      @on-change="handleSearchChange"
    />
    <Select
      v-model="typeValue"
      :placeholder="$t('materials.selectType') || '选择类型'"
      clearable
      @on-change="handleTypeChange"
      style="margin-top: 8px"
    >
      <Option v-for="type in typeList" :key="type.value" :value="type.value">
        {{ type.label }}
      </Option>
    </Select>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  typeListApi: {
    type: Function,
    default: null,
  },
});

const emit = defineEmits(['change']);

const searchKeyWord = ref('');
const typeValue = ref('');
const typeList = ref([]);

onMounted(async () => {
  if (props.typeListApi) {
    try {
      const result = await props.typeListApi();
      typeList.value = result || [];
    } catch (error) {
      console.error('Failed to load type list:', error);
      typeList.value = [];
    }
  }
});

const handleSearchChange = () => {
  emitChange();
};

const handleTypeChange = () => {
  emitChange();
};

const emitChange = () => {
  emit('change', {
    searchKeyWord: searchKeyWord.value,
    typeValue: typeValue.value,
  });
};

const setType = (type) => {
  typeValue.value = type;
};

defineExpose({
  setType,
});
</script>

<style scoped lang="less">
.search-type {
  padding: 10px;
  background: #fff;
  border-bottom: 1px solid #e8e8e8;
}
</style>
