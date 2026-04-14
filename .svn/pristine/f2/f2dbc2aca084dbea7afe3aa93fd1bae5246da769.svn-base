# FilterModal 筛选设置弹窗

通用的筛选条件配置弹窗组件，支持普通下拉框、树形标签选择（TagSelect）、人员选择器（UserPicker）三种渲染模式。

---

## 基础用法

```vue
<template>
  <a-button @click="visible = true">打开筛选弹窗</a-button>
  <FilterModal
    v-model="visible"
    :filter-options="filterOptions"
    :initial-values="currentValues"
    :visible-filters="visibleKeys"
    @confirm="handleConfirm"
  />
</template>

<script setup>
import { ref } from 'vue';
import { FilterModal } from '@/components/filter-modal';

const visible = ref(false);

const filterOptions = ref([
  {
    key: 'status',
    label: '状态',
    enabled: false,
    disabled: false,
    value: undefined,
    options: [
      { label: '启用', value: 1 },
      { label: '禁用', value: 0 },
    ],
  },
  {
    key: 'channel',
    label: '渠道',
    enabled: true,
    disabled: true,
    value: 'meta',
    options: [
      { label: 'Meta', value: 'meta' },
      { label: 'Google', value: 'google' },
    ],
  },
]);

const currentValues = ref({ status: 1, channel: 'meta' });
const visibleKeys = ref(['channel']);

const handleConfirm = (filterOptions) => {
  console.log('选中的筛选项:', filterOptions);
  // filterOptions[0].key === 'status', filterOptions[0].value === 1
};
</script>
```

---

## Props

| 参数 | 说明 | 类型 | 默认值 |
|------|------|------|--------|
| `modelValue` | 弹窗显示状态，控制打开/关闭 | `boolean` | `false` |
| `filterOptions` | 筛选项配置列表，每项包含 key/label/options 等 | `FilterOption[]` | `[]` |
| `initialValues` | 初始值映射，用于回显（key 对应 FilterOption.key） | `Record<string, any>` | `{}` |
| `visibleFilters` | 初始启用的筛选项 key 列表，用于回显勾选状态 | `string[]` | `[]` |

### FilterOption 结构

```ts
interface FilterOption {
  /** 筛选项唯一标识 */
  key: string;
  /** 筛选项显示名称 */
  label: string;
  /** 是否启用（勾选） */
  enabled: boolean;
  /** 是否禁用（禁用项checkbox失效且始终显示） */
  disabled?: boolean;
  /** 当前选中的值 */
  value?: string | number | any[];
  /** 下拉选项列表（普通字段） */
  options: { label: string; value: string | number }[];
  /** 标签树数据（key=tagIds 时传入） */
  tagTreeData?: {
    tagFolders: any[];
    tags: any[];
    tagOptions: any[];
  };
  /** 组织树数据（key=designer 或 key=creator 时传入） */
  orgData?: any[];
}
```

---

## Events

| 事件名 | 说明 | 返回值 |
|--------|------|--------|
| `confirm` | 点击确认按钮时触发 | `FilterOption[]` — 深拷贝后的筛选项列表（含最新 value 和 enabled 状态） |
| `update:modelValue` | 弹窗显隐变化 | `boolean` |

---

## 字段类型与渲染组件对照

组件根据 `filterOptions` 中每个 item 的 `key` 自动选择渲染方式：

| key 值 | 渲染组件 | 说明 |
|--------|----------|------|
| `tagIds` | `TagSelect` | 需要同时传入 `tagTreeData` |
| `designer` | `UserPicker` | 需要同时传入 `orgData`，自动设置 `deptId=2` |
| `creator` | `UserPicker` | 需要同时传入 `orgData` |
| 其他（如 `status`、`channel` 等） | `a-select` | 使用 `options` 渲染下拉框 |

---

## 完整使用示例

### 1. 定义筛选项（通常从后端接口加载）

```ts
const filterOptions = ref([
  // 固定项：禁用但始终显示
  {
    key: 'adAccount',
    label: '广告账户',
    enabled: true,
    disabled: true,
    value: 'acc_001',
    options: [],
  },
  // 普通下拉
  {
    key: 'status',
    label: '状态',
    enabled: false,
    disabled: false,
    value: undefined,
    options: [
      { label: '启用', value: 1 },
      { label: '禁用', value: 0 },
    ],
  },
  // 标签树（需要 tagTreeData）
  {
    key: 'tagIds',
    label: '标签',
    enabled: false,
    disabled: false,
    value: undefined,
    options: [],
    tagTreeData: {
      tagFolders: [{ id: 1, name: '文件夹A' }],
      tags: [{ id: 1, folder_id: 1, name: '标签1' }],
      tagOptions: [{ id: 1, tag_id: 1, name: '选项1', children: [] }],
    },
  },
  // 人员选择器（需要 orgData）
  {
    key: 'creator',
    label: '创意人',
    enabled: false,
    disabled: false,
    value: undefined,
    options: [],
    orgData: [
      {
        id: 1,
        name: '技术部',
        children: [
          { id: 2, name: '前端组', users: [{ id: 1, name: '张三' }] },
        ],
        users: [],
      },
    ],
  },
]);
```

### 2. 确认后处理

```ts
const handleConfirm = (result) => {
  // result 是深拷贝后的数组，可安全使用
  result.forEach(item => {
    if (item.enabled && item.value !== undefined) {
      // 将筛选值写入表单数据
      formData.filters[item.key] = item.value;
    } else {
      // 取消勾选的字段清空
      formData.filters[item.key] = undefined;
    }
  });
};
```

---

## 接口调用

筛选项数据通常由后端统一接口返回：

```ts
import { getFilterOptionData } from '@/api/promotion';

// 请求
const res = await getFilterOptionData({
  keys: [
    'adAccount',
    'channel',
    'campaign',
    'adGroup',
    'tagIds',
    'designer',
    'creator',
    'accountStatus',
    'authorizationStatus',
    'timezone',
  ],
  language: 'zh',
});

// 响应数据结构（res.data 是数组）
[
  {
    key: 'adAccount',
    label: '广告账户',
    enabled: true,
    disabled: true,
    value: null,
    options: [{ label: 'ad_jinyu - Facebook广告账户001', value: 'Facebook广告账户001' }],
  },
  {
    key: 'tagIds',
    label: '标签',
    enabled: false,
    disabled: false,
    value: null,
    tagTreeData: {
      tagFolders: [{ id: 1, name: '文件夹A' }],
      tags: [{ id: 1, folder_id: 1, name: '标签1' }],
      tagOptions: [{ id: 1, tag_id: 1, name: '选项1', children: [] }],
    },
  },
  {
    key: 'creator',
    label: '创意人',
    enabled: false,
    disabled: false,
    value: null,
    orgData: [
      {
        id: 1,
        name: 'Meta广告集团',
        children: [
          { id: 2, name: '设计部', users: [{ id: 1, name: 'admin', email: 'admin@example.com' }] },
        ],
        users: [],
      },
    ],
  },
  // ... 其他字段
]
```

**字段类型说明：**

| 字段 key | options | tagTreeData | orgData | 说明 |
|----------|---------|-------------|---------|------|
| `adAccount` | 有 | - | - | 普通下拉 |
| `channel` | 有 | - | - | 普通下拉 |
| `campaign` | 有 | - | - | 普通下拉 |
| `adGroup` | 有 | - | - | 普通下拉 |
| `tagIds` | - | 有 | - | 标签树 |
| `designer` | - | - | 有 | 人员选择（deptId=2） |
| `creator` | - | - | 有 | 人员选择 |
| `accountStatus` | 有 | - | - | 普通下拉 |
| `authorizationStatus` | 有 | - | - | 普通下拉 |
| `timezone` | 有 | - | - | 普通下拉 |

---

## 注意事项

### 1. 父页面必须引入 TagSelect / UserPicker

如果 `filterOptions` 中包含 `tagIds`、`designer` 或 `creator` 字段，**父页面必须在使用 FilterModal 的组件中手动引入对应的组件**，否则这些字段无法正确渲染：

```vue
<script setup>
import { FilterModal } from '@/components/filter-modal';
import TagSelect from '@/components/tag-select/index.vue';      // tagIds 需要
import { UserPicker } from '@/components/user-picker';         // designer / creator 需要
</script>
```

> FilterModal 内部虽然使用了 TagSelect 和 UserPicker，但它们是作为子组件注册的，如果父页面的 `filterOptions` 中含有这些类型的字段，必须确保这两个组件在父页面中可用。

### 2. 深拷贝机制

- `confirm` 事件返回的是 `localOptions` 的**深拷贝**，不会影响父组件的 `filterOptions` 引用
- 但 `confirm` 返回的数组中 `options` 字段是浅拷贝（展开了一层），如需修改 `options` 请注意

### 3. disabled 字段

- `disabled: true` 的字段 checkbox 被禁用但仍然显示在弹窗中
- `disabled: true` 的字段不会进入"清空"逻辑（`handleClear` 不会清除其值）

### 4. initialValues 只负责回显 value

- `initialValues` 在弹窗打开时同步到 `localOptions.item.value`
- 但 `disabled: false` 的字段如果 `initialValues` 中有值，**不会自动设置为 `enabled: true`**
- 需要在打开弹窗前，自己构建好 `visibleFilters` 数组

### 5. key=designer 的 deptId

- `designer` 字段固定传入 `deptId=2`，不可自定义
- 如需其他部门的人员选择，请使用 `orgData` + 自行扩展字段

### 6. 取消勾选不清空旧值

- 如果用户先选择了某个字段，再取消勾选，**值不会被自动清空**，仍然保留在 `value` 中
- 确认时需要根据 `enabled` 状态决定是否使用该值：
  ```ts
  const value = item.enabled ? item.value : undefined;
  ```

### 7. TagTreeData 和 OrgData 的数据来源

- 这两个字段通常由后端接口 `/filter-option-data` 统一返回
- 确保在打开弹窗前 `filterOptions` 已包含完整的 `tagTreeData` 和 `orgData`
- 如果异步加载，请使用 `watch` 等待数据到位后再打开弹窗

---

## 与表单数据联动建议

推荐将 `filterOptions` 作为筛选项的**定义/元数据**，而实际表单数据存储在独立的 `ruleConfig.filters` 中：

```
filterOptions (元数据) ──v-model──> 弹窗选择
ruleConfig.filters (数据) ──v-model──> 过滤行显示
handleConfirm ──同步──> ruleConfig.filters
```

这样分离的好处是：
- `filterOptions` 只负责选项定义，不污染业务数据
- `ruleConfig.filters` 是纯净的表单数据，提交时直接使用
