<template>
  <div class="position-breadcrumb">
    <span v-if="pathParts.length > 0">
      <span v-for="(part, index) in pathParts" :key="index">
        <span :class="index === pathParts.length - 1 ? 'path-current' : 'path-parent'">{{ part }}</span>
        <span v-if="index < pathParts.length - 1" class="path-separator"> / </span>
      </span>
    </span>
    <span v-else-if="defaultText" class="path-default">{{ defaultText }}</span>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{
  path?: string;
  defaultText?: string;
}>();

const pathParts = computed(() => {
  if (!props.path) return [];
  return props.path.split('/');
});
</script>

<style lang="less" scoped>
.position-breadcrumb {
  font-size: 14px;
}

:deep(.path-current) {
  color: #1890ff;
  font-weight: 600;
  font-size: 14px;
}

:deep(.path-parent) {
  color: #999;
  font-size: 12px;
}

:deep(.path-separator) {
  color: #d9d9d9;
}

:deep(.path-default) {
  color: #666;
}
</style>
