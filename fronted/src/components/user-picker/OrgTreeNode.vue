<template>
  <div class="org-node">
    <div
      class="org-item"
      :class="{ active: selectedOrgId === org.id }"
      :style="{ paddingLeft: (depth * 16 + 12) + 'px' }"
      @click="handleClick"
    >
      <span class="org-expand-icon" @click.stop="emit('toggle', org.id)">
        <right-outlined v-if="hasChildren && !expandedSet.has(org.id)" class="expand-icon" />
        <down-outlined v-else-if="hasChildren && expandedSet.has(org.id)" class="expand-icon" />
        <span v-else class="expand-placeholder" />
      </span>
      <span class="org-text" :title="org.name">{{ org.name }}</span>
    </div>
    <template v-if="hasChildren && expandedSet.has(org.id)">
      <OrgTreeNode
        v-for="child in org.children"
        :key="child.id"
        :org="child"
        :depth="depth + 1"
        :selectedOrgId="selectedOrgId"
        :expandedSet="expandedSet"
        @select="(o) => emit('select', o)"
        @toggle="(id) => emit('toggle', id)"
      />
    </template>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import { DownOutlined, RightOutlined } from '@ant-design/icons-vue';
import type { OrgNode } from './UserPicker.vue';

interface Props {
  org: OrgNode;
  depth?: number;
  selectedOrgId: number | null;
  expandedSet: Set<number>;
}

const props = withDefaults(defineProps<Props>(), {
  depth: 0,
});

interface Emits {
  (e: 'select', org: OrgNode): void;
  (e: 'toggle', orgId: number): void;
}

const emit = defineEmits<Emits>();

const hasChildren = computed(() => props.org.children && props.org.children.length > 0);

const handleClick = () => {
  if (hasChildren.value) {
    emit('toggle', props.org.id);
  }
  emit('select', props.org);
};
</script>

<style lang="less" scoped>
.org-node {
  // no extra wrapper needed
}
.org-item {
  height: 36px;
  display: flex;
  align-items: center;
  cursor: pointer;
  font-size: 14px;
  color: #333;
  user-select: none;
}
.org-item:hover {
  background: #f5f7fb;
}
.org-item.active {
  color: #1890ff;
  background: #f0f7ff;
}
.org-expand-icon {
  display: inline-flex;
  align-items: center;
  width: 16px;
  margin-right: 4px;
  flex-shrink: 0;
}
.expand-icon {
  font-size: 10px;
  color: #8c8c8c;
}
.expand-placeholder {
  width: 10px;
  display: inline-block;
}
.org-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
