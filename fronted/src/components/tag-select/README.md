# TagSelect 标签选择组件

三级联标签选择组件，支持文件夹 > 标签 > 选项的层级结构，以及任意层级新建子选项。

## API

```
GET /api/v2/meta-tags/tree # 获取标签文件夹、标签、标签选项

POST /api/v2/meta-tag-options # 新增标签选项
```


## 引入

```ts
import TagSelect from '@/components/tag-select';
```

## Props

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `v-model` | `number[]` | `[]` | 选中的标签选项 ID 列表 |
| `tagFolders` | `{ id, name }[]` | `[]` | 文件夹列表 |
| `tags` | `{ id, folder_id, name }[]` | `[]` | 标签（分类）列表 |
| `tagOptions` | `TagOption[]` | `[]` | 选项列表，结构见下方 |
| `placeholder` | `string` | `'请选择标签'` | 占位文本 |
| `creatable` | `boolean` | `true` | 是否显示新建按钮 |
| `createOptionApi` | `function` | - | 新建接口，签名为 `(tagId, name, parentId, isTagLevel) => Promise` |

## TagOption 数据结构

```ts
interface TagOption {
  id: number;
  name: string;
  tag_id: number | null; // 所属标签 ID
  children?: TagOption[]; // 子选项
}
```

## Emits

| 事件名 | 参数 | 说明 |
|--------|------|------|
| `update:modelValue` | `number[]` | 选中值变化 |
| `create-option` | `{ tagId, name, parentId }` | 新建选项（当未传 `createOptionApi` 时触发） |

## 典型用法

```vue
<template>
  <TagSelect
    v-model="selectedTags"
    :tag-folders="tagFolders"
    :tags="tags"
    :tag-options="tagOptions"
    :creatable="true"
    :create-option-api="handleCreateOption"
    placeholder="请选择标签"
  />
</template>

<script setup>
import TagSelect from '@/components/TagSelect';
import { createTagOption } from '@/api/promotion';

const selectedTags = ref([]);

const handleCreateOption = async (tagId, name, parentId, isTagLevel) => {
  // 左侧标签级新建: isTagLevel=true, parentId=0
  // 中间选项级新建: isTagLevel=false, parentId=具体ID
  const payload = isTagLevel
    ? { tag_id: tagId, name, parent_id: 0 }
    : { name, parent_id: parentId };
  await createTagOption(payload);
  // 刷新 tagOptions 数据
};
</script>
```

## 新建选项传参规则

- **左侧标签级新建**：`isTagLevel = true`，`parentId = 0` → API `{ tag_id: tagId, name, parent_id: 0 }`
- **中间选项级新建**：`isTagLevel = false`，`parentId = 具体ID` → API `{ name, parent_id: parentId }`

## 布局结构

```
+-------------------+--------------------+------------------+
|  左侧：文件夹 + 标签 |  中间：选项(4级)     |  右侧：已选列表   |
+-------------------+--------------------+------------------+
| 文件夹1             |  □ 选项1            |  已选 3 个       |
|   标签A           |    □ 子选项1-1      |  × 选项1         |
|   标签B +         |      □ 孙选项1-1-1   |  × 子选项1-1     |
| 标签(无文件夹)      |    □ 子选项1-2 +    |  × 孙选项1-1-1   |
| 标签C +           |  □ 选项2            |                 |
+-------------------+--------------------+------------------+
```

- 任意选项行 hover 时显示 `+` 按钮，点击后在该选项下方展开输入框
- 左侧标签 hover 时显示 `+` 按钮，点击后在中间区域底部展开输入框
- 中间支持 4 层级联展示，最后一级无新建按钮
