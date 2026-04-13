<template>
  <div class="xmo-user-picker">
    <a-popover
      v-model:open="open"
      trigger="click"
      placement="bottomLeft"
      overlayClassName="xmo-user-picker-popover"
      :overlayStyle="{ padding: '0' }"
      :overlayInnerStyle="{ padding: '0' }"
    >
      <template #content>
        <div class="panel" @mousedown.prevent @click.stop>
          <div class="panel-top">
            <div class="panel-top-left">
              <a-select v-if="showLogicSelector" v-model:value="logicModeInner" class="logic-select">
                <a-select-option value="all">{{ allLabel }}</a-select-option>
                <a-select-option value="any">{{ anyLabel }}</a-select-option>
              </a-select>
              <a-input
                v-else
                v-model:value="keyword"
                :placeholder="searchPlaceholder"
                allow-clear
                class="search-input top-search-input"
              >
                <template #prefix>
                  <search-outlined />
                </template>
              </a-input>
            </div>

            <div class="panel-top-right">
              <span class="selected-count">{{ selectedCountLabel }}</span>
              <a-button type="link" size="small" class="clear-btn" :disabled="selectedValuesSet.size === 0" @click="handleClear">
                {{ clearLabel }}
              </a-button>
            </div>
          </div>

          <div v-if="showLogicSelector" class="panel-search">
            <a-input
              v-model:value="keyword"
              :placeholder="searchPlaceholder"
              allow-clear
              class="search-input"
            >
              <template #prefix>
                <search-outlined />
              </template>
            </a-input>
          </div>

          <div class="panel-body">
            <!-- 左栏：组织树 -->
            <div class="col col-left org-tree-scroll">
              <OrgTreeNode
                v-for="org in orgTreeSafe"
                :key="org.id"
                :org="org"
                :selectedOrgId="selectedOrgId"
                :expandedSet="expandedSet"
                @select="selectOrg"
                @toggle="toggleExpand"
              />
            </div>

            <!-- 中栏：人员列表 -->
            <div class="col col-middle">
              <div v-if="currentUsers.length === 0" class="empty-wrap">
                <a-empty :description="emptyLabel" />
              </div>
              <a-checkbox-group v-else :value="modelValueSafe" class="options-group" @change="handleCheckboxGroupChange">
                <div v-for="user in currentUsers" :key="user.id" class="opt-row">
                  <a-checkbox :value="user.id">{{ user.name }}</a-checkbox>
                </div>
              </a-checkbox-group>
            </div>

            <!-- 右栏：已选列表 -->
            <div class="col col-right">
              <div v-if="selectedUsers.length === 0" class="selected-empty">{{ selectedEmptyLabel }}</div>
              <div v-else class="selected-list">
                <div v-for="user in selectedUsers" :key="user.id" class="selected-item">
                  <span class="selected-item-text" :title="user.name">{{ user.name }}</span>
                  <a-button type="link" size="small" class="remove-btn" @click="removeOne(user.id)">{{ removeLabel }}</a-button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <div class="trigger-box" @click="open = true">
        <span class="trigger-text" :class="{ 'is-placeholder': !displayValue }">
          {{ displayValue || placeholder }}
        </span>
        <span class="trigger-icons">
          <close-circle-filled
            v-if="allowClear && selectedValuesSet.size > 0"
            class="trigger-clear"
            @click.stop="handleClear"
          />
          <down-outlined class="trigger-arrow" />
        </span>
      </div>
    </a-popover>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue';
import { CloseCircleFilled, DownOutlined, RightOutlined, SearchOutlined } from '@ant-design/icons-vue';
import { isArray } from 'lodash-es';
import OrgTreeNode from './OrgTreeNode.vue';

export type LogicMode = 'all' | 'any';
export type ValueLike = string | number;

export interface OrgUser {
  id: number;
  name: string;
  email: string;
  is_super?: number;
}

export interface OrgNode {
  id: number;
  parent_id: number;
  name: string;
  code: string | null;
  type: string;
  children: OrgNode[];
  users: OrgUser[];
}

interface Props {
  modelValue?: ValueLike[];
  orgTree?: OrgNode[];
  deptId?: number | null;
  placeholder?: string;
  allowClear?: boolean;
  searchPlaceholder?: string;
  clearLabel?: string;
  emptyLabel?: string;
  selectedEmptyLabel?: string;
  removeLabel?: string;
  showLogicSelector?: boolean;
  logicMode?: LogicMode;
  allLabel?: string;
  anyLabel?: string;
}

interface Emits {
  (e: 'update:modelValue', value: ValueLike[]): void;
  (e: 'update:logicMode', value: LogicMode): void;
  (e: 'change', payload: { values: ValueLike[]; logicMode: LogicMode }): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  orgTree: () => [],
  deptId: null,
  placeholder: '请选择人员',
  allowClear: true,
  searchPlaceholder: '搜索人员',
  clearLabel: '清除',
  emptyLabel: '暂无人员',
  selectedEmptyLabel: '',
  removeLabel: '移除',
  showLogicSelector: false,
  logicMode: 'all',
  allLabel: '满足全部条件',
  anyLabel: '满足任一条件',
});

const emit = defineEmits<Emits>();

const open = ref(false);
const keyword = ref('');
const selectedOrgId = ref<number | null>(null);
const expandedSet = ref<Set<number>>(new Set());

const logicModeInner = computed<LogicMode>({
  get() {
    return props.logicMode;
  },
  set(v) {
    emit('update:logicMode', v);
    emit('change', { values: modelValueSafe.value, logicMode: v });
  },
});

const orgTreeSafe = computed(() => {
  const tree = isArray(props.orgTree) ? props.orgTree : [];
  if (props.deptId == null) return tree;
  const findDept = (nodes: OrgNode[]): OrgNode | null => {
    for (const node of nodes) {
      if (node.id === props.deptId) return node;
      if (node.children?.length) {
        const found = findDept(node.children);
        if (found) return found;
      }
    }
    return null;
  };
  const dept = findDept(tree);
  return dept ? [dept] : [];
});

// 将树形结构扁平化为全部节点列表，供搜索用
const allNodes = computed(() => {
  const result: OrgNode[] = [];
  const traverse = (nodes: OrgNode[]) => {
    for (const node of nodes) {
      result.push(node);
      if (node.children?.length) traverse(node.children);
    }
  };
  traverse(orgTreeSafe.value);
  return result;
});

// 搜索命中的用户列表
const searchedUsers = computed<OrgUser[]>(() => {
  const k = String(keyword.value || '').trim().toLowerCase();
  if (!k) return [];
  const result: OrgUser[] = [];
  for (const node of allNodes.value) {
    if (node.users?.length) {
      for (const user of node.users) {
        if (
          String(user.name || '').toLowerCase().includes(k) ||
          String(user.email || '').toLowerCase().includes(k)
        ) {
          result.push(user);
        }
      }
    }
  }
  return result;
});

// 当前选中组织下的人员
const currentUsers = computed<OrgUser[]>(() => {
  if (keyword.value.trim()) {
    return searchedUsers.value;
  }
  if (selectedOrgId.value === null) return [];
  const node = allNodes.value.find((n) => n.id === selectedOrgId.value);
  return node?.users || [];
});

// 自动选中第一个组织（deptId 模式下选中指定部门）
watch(
  orgTreeSafe,
  (tree) => {
    if (tree.length && selectedOrgId.value === null) {
      selectedOrgId.value = tree[0].id;
    }
  },
  { immediate: true },
);

// deptId 变化时重新选中目标部门
watch(
  () => props.deptId,
  (deptId) => {
    if (deptId != null) {
      selectedOrgId.value = deptId;
    }
  },
);

const selectOrg = (org: OrgNode) => {
  selectedOrgId.value = org.id;
  keyword.value = '';
};

const toggleExpand = (orgId: number) => {
  const next = new Set(expandedSet.value);
  if (next.has(orgId)) {
    next.delete(orgId);
  } else {
    next.add(orgId);
  }
  expandedSet.value = next;
};

const modelValueSafe = computed(() => (isArray(props.modelValue) ? props.modelValue : []));
const selectedValuesSet = computed(() => new Set(modelValueSafe.value.map((v) => String(v))));

// 构建 userId -> user 的映射
const userMap = computed(() => {
  const map = new Map<string, OrgUser>();
  for (const node of allNodes.value) {
    if (node.users?.length) {
      for (const user of node.users) {
        map.set(String(user.id), user);
      }
    }
  }
  return map;
});

const selectedUsers = computed<OrgUser[]>(() => {
  return modelValueSafe.value.map((v) => userMap.value.get(String(v))).filter(Boolean) as OrgUser[];
});

const selectedCountLabel = computed(() => `已选 ${selectedUsers.value.length} 人`);
const displayValue = computed(() => (selectedUsers.value.length ? selectedCountLabel.value : undefined));

const handleCheckboxGroupChange = (vals: any) => {
  const currentOrgUserIds = new Set(currentUsers.value.map((u) => String(u.id)));
  const keepOtherOrgValues = modelValueSafe.value.filter((v) => !currentOrgUserIds.has(String(v)));
  const currentValues = Array.isArray(vals) ? vals : [];
  const next = [...keepOtherOrgValues, ...currentValues];
  emit('update:modelValue', next);
  emit('change', { values: next, logicMode: logicModeInner.value });
};

const removeOne = (id: ValueLike) => {
  const next = modelValueSafe.value.filter((v) => String(v) !== String(id));
  emit('update:modelValue', next);
  emit('change', { values: next, logicMode: logicModeInner.value });
};

const handleClear = () => {
  keyword.value = '';
  emit('update:modelValue', []);
  emit('change', { values: [], logicMode: logicModeInner.value });
};
</script>

<style lang="less" scoped>
.xmo-user-picker {
  width: 100%;
}

.trigger-box {
  width: 100%;
  height: 34px;
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  background: #fff;
  padding: 0 11px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}
.trigger-box:hover {
  border-color: #4096ff;
}
.trigger-text {
  font-size: 14px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding-right: 8px;
}
.trigger-text.is-placeholder {
  color: #bfbfbf;
}
.trigger-icons {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.trigger-clear {
  color: #bfbfbf;
  font-size: 12px;
}
.trigger-clear:hover {
  color: #8c8c8c;
}
.trigger-arrow {
  color: #8c8c8c;
  font-size: 12px;
}

.panel {
  width: 680px;
  background: #fff;
  border: 1px solid #e6e6e6;
  border-radius: 6px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  padding: 10px;
}

:global(.xmo-user-picker-popover .ant-popover-inner) {
  padding: 0 !important;
}

:global(.xmo-user-picker-popover .ant-popover-inner-content) {
  padding: 0 !important;
  width: 100%;
  height: 100%;
}

.panel-top {
  display: flex;
  align-items: center;
  gap: 12px;
}
.panel-top-left {
  flex: 1;
  min-width: 0;
}
.logic-select {
  width: 60%;
}
.top-search-input {
  width: 100%;
}
.panel-top-right {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}
.selected-count {
  font-size: 14px;
  color: #333;
}
.clear-btn {
  padding: 0;
  color: #1890ff;
}

.panel-search {
  margin-top: 10px;
}
.search-input :deep(.ant-input) {
  font-size: 14px;
}
.search-input {
  flex: 1;
}
.search-input :deep(.ant-input) {
  font-size: 14px;
}

.panel-body {
  margin-top: 10px;
  display: grid;
  grid-template-columns: 180px 1fr 220px;
  border: 1px solid #f0f0f0;
  border-radius: 6px;
  overflow: hidden;
  min-height: 260px;
}

.col {
  min-height: 260px;
}
.col-left {
  border-right: 1px solid #f0f0f0;
  background: #fff;
  overflow-y: auto;
}
.col-middle {
  border-right: 1px solid #f0f0f0;
  background: #fff;
  overflow-y: auto;
}
.col-right {
  background: #fff;
  overflow-y: auto;
}

.org-item {
  height: 36px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  font-size: 14px;
  color: #333;
}
.org-item:hover {
  background: #f5f7fb;
}
.org-item.active {
  color: #1890ff;
  background: #f0f7ff;
}
.org-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.org-arrow {
  font-size: 12px;
  color: #1890ff;
}

.options-group {
  width: 100%;
  display: block;
}
.opt-row {
  height: 34px;
  display: flex;
  align-items: center;
  padding: 0 12px;
}
.opt-row:hover {
  background: #fafafa;
}

.empty-wrap {
  padding: 24px 0;
}

.selected-empty {
  padding: 12px;
  font-size: 14px;
  color: #999;
}
.selected-list {
  padding: 6px 0;
  max-height: 220px;
  overflow: auto;
}
.selected-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 6px 12px;
}
.selected-item-text {
  font-size: 14px;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.remove-btn {
  padding: 0;
  color: #1890ff;
}
</style>
