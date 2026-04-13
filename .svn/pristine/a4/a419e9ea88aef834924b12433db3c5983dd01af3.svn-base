# UserPicker 人员选择器

基于组织树的三栏人员选择器，支持多选、搜索、满足条件模式。

## 使用方式

```vue
<template>
  <UserPicker
    v-model="selectedUserIds"
    :org-tree="orgTreeData"
    placeholder="请选择人员"
    :show-logic-selector="true"
    v-model:logic-mode="logicMode"
    @change="handleChange"
  />
</template>

<script setup>
import { ref } from 'vue';
import { UserPicker } from '@/components/user-picker';

const selectedUserIds = ref([]);
const logicMode = ref('all');
const orgTreeData = ref([]); // 来自 getOrganizationTree()

const handleChange = ({ values, logicMode }) => {
  console.log('选中的用户ID:', values);
  console.log('匹配模式:', logicMode); // 'all' | 'any'
};
</script>
```

## Props

| 参数 | 说明 | 类型 | 默认值 |
|------|------|------|--------|
| `v-model` | 选中的用户 ID 数组 | `(string \| number)[]` | `[]` |
| `orgTree` | 组织树数据，结构见下方 | `OrgNode[]` | `[]` |
| `deptId` | 指定部门 ID，传入会只渲染该部门节点 | `number \| null` | `null` |
| `placeholder` | 占位文本 | `string` | `'请选择人员'` |
| `allowClear` | 是否显示清除按钮 | `boolean` | `true` |
| `searchPlaceholder` | 搜索框占位文本 | `string` | `'搜索人员'` |
| `showLogicSelector` | 是否显示"满足全部/任一条件"选择器 | `boolean` | `false` |
| `v-model:logic-mode` | 当前匹配模式 | `'all' \| 'any'` | `'all'` |
| `allLabel` | 满足全部条件标签文本 | `string` | `'满足全部条件'` |
| `anyLabel` | 满足任一条件标签文本 | `string` | `'满足任一条件'` |
| `clearLabel` | 清除按钮文本 | `string` | `'清除'` |
| `emptyLabel` | 无数据时提示文本 | `string` | `'暂无人员'` |
| `selectedEmptyLabel` | 已选面板为空时提示 | `string` | `''` |
| `removeLabel` | 移除按钮文本 | `string` | `'移除'` |

## Emits

| 事件名 | 触发时机 | payload |
|--------|----------|---------|
| `update:modelValue` | 选择变化时 | `ValueLike[]` — 当前选中的用户 ID 数组 |
| `update:logicMode` | 匹配模式变化时 | `LogicMode` |
| `change` | 选择或模式变化时 | `{ values: ValueLike[]; logicMode: LogicMode }` |

## 组织树数据结构

```ts
interface OrgUser {
  id: number;
  name: string;
  email: string;
  is_super?: number;
}

interface OrgNode {
  id: number;
  parent_id: number;
  name: string;
  code: string | null;
  type: 'org';
  children: OrgNode[];
  users: OrgUser[];
}
```

## 数据获取

```ts
import { getOrganizationTree } from '@/api/system/organization';

const res = await getOrganizationTree();
orgTree.value = res?.data || [];
```

## 特性

- **左侧**：组织树，支持点击名称展开/收起子部门
- **中间**：当前选中部门下的人员列表，支持关键字搜索（跨所有部门搜索姓名/邮箱）
- **右侧**：已选人员列表，支持单条移除
- **顶部**：关键字搜索框 + 已选数量 + 清除按钮；开启 `showLogicSelector` 时显示"满足全部/任一条件"下拉
