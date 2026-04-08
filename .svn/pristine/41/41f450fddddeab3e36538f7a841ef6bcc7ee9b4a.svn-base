# FilterSection 通用筛选组件

一个开箱即用的筛选组件，支持动态显示/隐藏筛选条件、localStorage 持久化、多种输入类型。

## 特性

- 默认显示指定的筛选条件，点击筛选图标打开弹窗选择更多（可选）
- 筛选条件显示配置自动持久化到 localStorage
- 支持 input、select、date-range、number-range 四种输入类型
- 内置国际化支持（中英文）
- 完全封装，无需额外导入弹窗组件
- 可控制是否启用弹窗功能
- 可控制每个字段是否显示

## 安装使用

### 基础用法（带弹窗）

```vue
<template>
  <filter-section
    :fields="filterFields"
    :default-visible-keys="['status', 'dateRange']"
    storage-key="my-page-filter"
    @search="handleSearch"
  />
</template>

<script setup lang="ts">
import FilterSection from '@/components/filter-section';
import type { FilterFieldConfig } from '@/components/filter-section';

const filterFields: FilterFieldConfig[] = [
  {
    key: 'status',
    label: '状态',
    type: 'select',
    options: [
      { label: '启用', value: 'enabled' },
      { label: '禁用', value: 'disabled' },
    ],
  },
  {
    key: 'keyword',
    label: '关键词',
    type: 'input',
  },
  {
    key: 'dateRange',
    label: '日期范围',
    type: 'date-range',
  },
];

const handleSearch = (filters) => {
  console.log('筛选条件:', filters);
  loadData(filters);
};
</script>
```

### 不带弹窗（筛选条件固定显示）

```vue
<template>
  <filter-section
    :fields="filterFields"
    :show-modal="false"
    @search="handleSearch"
  />
</template>

<script setup lang="ts">
import FilterSection from '@/components/filter-section';
import type { FilterFieldConfig } from '@/components/filter-section';

const filterFields: FilterFieldConfig[] = [
  {
    key: 'status',
    label: '状态',
    type: 'select',
    options: [
      { label: '启用', value: 'enabled' },
      { label: '禁用', value: 'disabled' },
    ],
  },
  {
    key: 'keyword',
    label: '关键词',
    type: 'input',
  },
];
</script>
```

### 控制字段显示

```vue
<template>
  <filter-section
    :fields="filterFields"
    :default-visible-keys="['status', 'keyword']"
    :show-modal="true"
    storage-key="my-page-filter"
    @search="handleSearch"
  />
</template>

<script setup lang="ts">
import FilterSection from '@/components/filter-section';
import type { FilterFieldConfig } from '@/components/filter-section';

const filterFields: FilterFieldConfig[] = [
  {
    key: 'status',
    label: '状态',
    type: 'select',
    options: [
      { label: '启用', value: 'enabled' },
      { label: '禁用', value: 'disabled' },
    ],
  },
  {
    key: 'keyword',
    label: '关键词',
    type: 'input',
  },
  {
    key: 'hiddenField',
    label: '隐藏字段',
    type: 'input',
    visible: false, // 设置为 false 则不会在列表和弹窗中显示
  },
];
</script>
```

### 使用 ref 调用方法

```vue
<template>
  <filter-section
    ref="filterRef"
    :fields="filterFields"
    @search="handleSearch"
  />
</template>

<script setup lang="ts">
import { ref } from 'vue';
import FilterSection from '@/components/filter-section';
import type { FilterSectionInstance } from '@/components/filter-section';

const filterRef = ref<FilterSectionInstance>();

// 获取当前筛选值
const getCurrentFilters = () => {
  return filterRef.value?.getFilters();
};

// 设置筛选值
const setFilters = (filters) => {
  filterRef.value?.setFilters(filters);
};

// 重置筛选
const resetFilters = () => {
  filterRef.value?.reset();
};
</script>
```

### 自定义翻译前缀

```vue
<template>
  <filter-section
    i18n-prefix="pages.myModule.filter"
    :fields="filterFields"
    @search="handleSearch"
  />
</template>
```

## API

### Props

| 参数 | 说明 | 类型 | 默认值 |
|------|------|------|--------|
| fields | 筛选字段配置 | `FilterFieldConfig[]` | `[]` |
| defaultVisibleKeys | 默认显示的筛选字段 key | `string[]` | `[]` |
| storageKey | localStorage 存储键 | `string` | `'filter-visible-keys'` |
| i18nPrefix | i18n 翻译前缀 | `string` | `'components.filter'` |
| showModal | 是否显示弹窗功能 | `boolean` | `true` |

### FilterFieldConfig

| 参数 | 说明 | 类型 | 必填 |
|------|------|------|------|
| key | 字段唯一标识 | `string` | 是 |
| label | 字段标签 | `string` | 是 |
| type | 字段类型 | `'input' \| 'select' \| 'date-range' \| 'number-range'` | 是 |
| options | 选项列表（type 为 select 时必填） | `FilterFieldOption[]` | 否 |
| placeholder | 自定义占位符 | `string` | 否 |
| visible | 是否显示此字段（设为 false 则隐藏） | `boolean` | `true` |

### FilterFieldOption

| 参数 | 说明 | 类型 | 必填 |
|------|------|------|------|
| label | 选项标签 | `string` | 是 |
| value | 选项值 | `string` | 是 |

### Events

| 事件名 | 说明 | 回调参数 |
|--------|------|----------|
| search | 筛选条件变化时触发 | `(filters: FilterValue) => void` |

### Exposed Methods

| 方法名 | 说明 | 返回值 |
|--------|------|--------|
| reset | 重置所有筛选条件 | `void` |
| getFilters | 获取当前筛选条件 | `FilterValue` |
| setFilters | 设置筛选条件 | `void` |

## 国际化

组件内置了默认翻译，如需自定义请在项目中添加对应语言的翻译：

```typescript
// zh-CN.ts
export default {
  components: {
    filter: {
      title: '筛选',
      selectPlaceholder: '请选择',
      inputPlaceholder: '请输入',
      numberStartPlaceholder: '最小值',
      numberEndPlaceholder: '最大值',
      modal: {
        title: '筛选设置',
        notification: '勾选筛选条件确定后将会在界面显示',
        selectPlaceholder: '请选择',
        inputPlaceholder: '请输入',
        numberStartPlaceholder: '最小值',
        numberEndPlaceholder: '最大值',
        saveAsTemplate: '保存为模板',
        cancel: '取消',
        confirm: '确定',
      },
    },
  },
};
```

## 组件结构

```
filter-section/
├── index.ts              # 导出入口
├── FilterSection.vue     # 主组件
├── types.ts              # 类型定义
├── locales/              # 内置翻译
│   ├── zh-CN.ts
│   └── en-US.ts
└── internal/             # 内部组件（不对外暴露）
    └── FilterModal.vue   # 筛选弹窗
```

## 使用场景

### 场景1：需要动态筛选（默认）
```vue
<filter-section
  :fields="filterFields"
  :default-visible-keys="['status']"
  storage-key="page-filter"
  @search="handleSearch"
/>
```
- 显示筛选图标
- 点击可打开弹窗选择更多字段
- 字段显示配置持久化

### 场景2：固定筛选条件
```vue
<filter-section
  :fields="filterFields"
  :show-modal="false"
  @search="handleSearch"
/>
```
- 不显示筛选图标
- 所有 `visible: true` 的字段都显示
- 不能切换显示/隐藏字段

### 场景3：部分字段隐藏
```vue
<script setup>
const filterFields = [
  { key: 'field1', label: '字段1', type: 'input', visible: true },
  { key: 'field2', label: '字段2', type: 'input', visible: false }, // 不显示
];
</script>
```
- `visible: false` 的字段不会在列表和弹窗中显示
- 可用于条件控制字段显示
