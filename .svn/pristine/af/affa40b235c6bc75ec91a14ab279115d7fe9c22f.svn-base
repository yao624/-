# MaterialPicker 素材选择器
基于素材库树的弹窗式素材选择器，支持单选/多选、左侧素材库树浏览、关键词搜索、素材预览。

## 使用方式

```vue
<template>
  <MaterialPicker
    v-model:open="open"
    :multiple="true"
    :default-selected-row-keys="selectedKeys"
    :default-selected-rows="selectedRows"
    @confirm:items-selected="handleConfirm"
  />
</template>

<script setup lang="ts">
import { ref } from 'vue';
import MaterialPicker from '@/components/material-picker/index.vue';

const open = ref(false);
const selectedKeys = ref<Array<string | number>>([]);
const selectedRows = ref<any[]>([]);

const handleConfirm = (keys: Array<string | number>, rows: any[]) => {
  selectedKeys.value = keys;
  selectedRows.value = rows;
  console.log('选中的素材:', rows);
};
</script>
```

## Props

| 参数 | 说明 | 类型 | 默认值 |
|------|------|------|--------|
| `open` | 是否打开弹窗 | `boolean` | `false` |
| `title` | 弹窗标题 | `string` | `'选择素材'` |
| `width` | 弹窗宽度 | `string \| number` | `1380` |
| `multiple` | 是否多选 | `boolean` | `true` |
| `allowEmpty` | 是否允许空选确认 | `boolean` | `true` |
| `defaultSelectedRowKeys` | 默认选中的素材 key 列表 | `(string \| number)[]` | `[]` |
| `defaultSelectedRows` | 默认选中的素材行数据 | `any[]` | `[]` |

## Emits

| 事件名 | 触发时机 | payload |
|--------|----------|---------|
| `update:open` | 弹窗开关变化时 | `boolean` |
| `cancel` | 点击取消或关闭弹窗时 | 无 |
| `confirm:items-selected` | 点击确认时 | `(keys, rows)`，即当前选中的素材 key 和素材数据 |

## 数据来源

```ts
import { getMaterialLibraryFolders, getMaterialLibraryFolderChildren } from '@/api/material-library/folders';
import { getMaterialLibraryMaterials } from '@/api/material-library/materials';
```

组件会自动完成以下数据加载：

- 根据当前用户加载“我的素材库”和“企业素材库”根目录
- 左侧树递归加载所有素材库和全部文件夹
- 右侧列表根据当前选中的文件夹加载素材数据

## 素材树逻辑

- 顶部通过 `libraryType` 在“我的素材库 / 企业素材库”之间切换
- 左侧树会直接显示当前分类下的全部素材库
- 每个素材库下的文件夹会递归展开，空文件夹也会显示
- 当前选中节点会作为右侧素材列表的 `folder_id`

## 列表与预览

- 列表支持按素材名称、`Local ID`、备注搜索
- 支持“包含子文件夹”查询
- 列表支持单击整行选中素材
- 多选模式下，已选素材会跨文件夹、跨“我的素材库 / 企业素材库”保留
- 下方预览区会展示图片或视频预览，以及名称、类型、尺寸、时长、创建时间、备注等信息
- “已选素材”列表支持滚动浏览，点击某一项可切换右侧预览
- “已选素材”列表中的素材支持直接移除

## 返回数据结构

组件确认后返回的每一项素材数据为素材库接口原始行数据，常见字段示例：

```ts
interface MaterialRow {
  id: string | number;
  name?: string;
  material_name?: string;
  localId?: string;
  type?: string;
  width?: number;
  height?: number;
  duration?: number;
  remarks?: string;
  remark?: string;
  thumbnail?: string;
  preview_url?: string;
  created_at?: string;
  createdAt?: string;
  createTime?: string;
  [key: string]: any;
}
```

## 特点

- 左侧：素材库 + 文件夹完整树
- 中间：素材列表，支持搜索和分页
- 下方：素材预览与详情，预览区内部滚动，不会撑大弹窗
- 交互：支持默认回填、行点击选中、跨目录保留多选、已选项移除、确认返回
