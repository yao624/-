<template>
  <InputNumber
    v-model="innerValue"
    v-bind="$attrs"
    @on-change="handleChange"
  />
</template>

<script setup>
import { ref, watch } from 'vue';
import { InputNumber } from 'view-ui-plus';

const props = defineProps({
  modelValue: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['update:modelValue', 'on-change']);

const innerValue = ref(props.modelValue);

watch(() => props.modelValue, (newVal) => {
  innerValue.value = newVal;
});

const handleChange = (value) => {
  emit('update:modelValue', value);
  emit('on-change', value);
};
</script>

<style scoped>
/* View UI Plus InputNumber styles will be applied automatically */
</style>
