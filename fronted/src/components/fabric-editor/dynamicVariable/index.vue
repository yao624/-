<!--
 * @Author: Claude
 * @Date: 2024-04-07
 * @Description: 动态变量配置面板
-->
<template>
  <div class="dynamic-variable-panel">
    <!-- 调试信息 -->
    <div style="padding: 8px; background: #f0f0f0; margin-bottom: 8px; font-size: 12px;">
      <div>canvasEditor: {{ !!canvasEditor }}</div>
      <div>fabricCanvas: {{ !!canvasEditor?.fabricCanvas }}</div>
      <div>selectedObject: {{ !!selectedObject }}</div>
      <div>activeObject: {{ !!canvasEditor?.fabricCanvas?.getActiveObject() }}</div>
    </div>

    <!-- 当前选中元素的动态配置 -->
    <div class="current-object-config" v-if="selectedObject">
      <div class="panel-header">
        <span>当前元素</span>
        <Tag v-if="isDynamic" color="primary">动态</Tag>
        <Tag v-else color="default">静态</Tag>
      </div>

      <div class="config-form">
        <Form :label-width="80">
          <!-- 动态开关 -->
          <FormItem label="设为动态">
            <i-switch v-model="formData.isDynamic" @on-change="onDynamicToggle">
              <template #open>是</template>
              <template #close>否</template>
            </i-switch>
          </FormItem>

          <!-- 动态变量配置 -->
          <template v-if="formData.isDynamic">
            <FormItem label="变量名">
              <Input
                v-model="formData.variableName"
                placeholder="请输入变量名"
                @on-blur="onConfigChange"
              />
            </FormItem>

            <FormItem label="变量类型">
              <Select v-model="formData.variableType" @on-change="onConfigChange">
                <Option value="text">文本</Option>
                <Option value="image">图片</Option>
              </Select>
            </FormItem>

            <FormItem label="关联分类" v-if="formData.variableType === 'image'">
              <Select
                v-model="formData.categoryId"
                placeholder="选择素材分类"
                @on-change="onConfigChange"
              >
                <Option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </Option>
              </Select>
            </FormItem>

            <FormItem label="默认值">
              <Input
                v-model="formData.defaultValue"
                type="textarea"
                :rows="2"
                placeholder="默认值（可选）"
                @on-blur="onConfigChange"
              />
            </FormItem>

            <FormItem label="备注">
              <Input
                v-model="formData.remark"
                placeholder="添加备注说明（可选）"
                @on-blur="onConfigChange"
              />
            </FormItem>
          </template>
        </Form>
      </div>
    </div>

    <div v-else class="no-selection">
      <p>请选择一个元素</p>
    </div>

    <!-- 模板中的所有动态变量 -->
    <div v-if="selectedObject" style="margin-top: 16px;">
      <Divider />
      <div class="all-variables">
        <div class="panel-header">
          <span>动态变量列表</span>
          <Badge :count="dynamicVariables.length" />
        </div>

        <div class="variables-list" v-if="dynamicVariables.length > 0">
          <div v-for="variable in dynamicVariables" :key="variable.id" class="variable-item">
            <div class="variable-main">
              <div class="variable-info">
                <Tag :color="variable.variableType === 'text' ? 'blue' : 'green'">
                  {{ variable.variableType === 'text' ? '文本' : '图片' }}
                </Tag>
                <span class="variable-name">{{ variable.variableName }}</span>
              </div>
              <div v-if="variable.remark" class="variable-remark">
                <Icon type="md-information-circle" size="12" />
                <span>{{ variable.remark }}</span>
              </div>
            </div>
            <div class="variable-actions">
              <Button type="primary" size="small" ghost @click="locateVariable(variable)">
                定位
              </Button>
            </div>
          </div>
        </div>

        <Empty v-else description="暂无动态变量" />
      </div>
    </div>
  </div>
</template>

<script name="DynamicVariablePanel" setup lang="ts">
import { ref, inject, nextTick, onMounted, onUnmounted } from 'vue';
import { fabric } from 'fabric';
import type { IEditor } from '@kuaitu/core';
import type { DynamicConfig, DynamicVariable } from '@kuaitu/core';

// 获取 canvasEditor 实例
const canvasEditor = inject<IEditor>('canvasEditor');

// 状态
const selectedObject = ref<fabric.Object | null>(null);
const isDynamic = ref(false);
const formData = ref<DynamicConfig>({
  isDynamic: false,
  variableName: '',
  variableType: 'text',
  categoryId: '',
  defaultValue: '',
  remark: '',
});

// 模拟分类数据（实际应从 API 获取）
const categories = ref([
  { id: 'cat_products', name: '商品图片' },
  { id: 'cat_logos', name: '品牌Logo' },
  { id: 'cat_backgrounds', name: '背景图片' },
]);

// 动态变量列表
const dynamicVariables = ref<DynamicVariable[]>([]);

// 检查选中对象是否为动态
const checkDynamic = () => {
  console.log('checkDynamic 调用', canvasEditor);
  if (!canvasEditor) {
    console.log('canvasEditor 不存在');
    return;
  }
  const canvas = canvasEditor.fabricCanvas;
  console.log('fabricCanvas:', canvas);
  const activeObject = canvas?.getActiveObject();
  console.log('activeObject:', activeObject);

  if (!activeObject) {
    selectedObject.value = null;
    isDynamic.value = false;
    return;
  }

  selectedObject.value = activeObject;
  const config = canvasEditor.getDynamicConfig(activeObject);
  console.log('DynamicConfig:', config);

  if (config?.isDynamic) {
    isDynamic.value = true;
    formData.value = { ...config };
  } else {
    isDynamic.value = false;
    formData.value = {
      isDynamic: false,
      variableName: '',
      variableType: activeObject.type === 'textbox' ? 'text' : 'image',
      categoryId: '',
      defaultValue: '',
      remark: '',
    };
  }
};

// 动态开关切换
const onDynamicToggle = (value: boolean) => {
  if (!canvasEditor || !selectedObject.value) return;

  if (value) {
    // 设为动态
    canvasEditor.setDynamic(selectedObject.value, {
      isDynamic: true,
      variableType: formData.value.variableType,
      variableName: formData.value.variableName,
      categoryId: formData.value.categoryId,
      defaultValue: formData.value.defaultValue,
    });
  } else {
    // 取消动态
    canvasEditor.removeDynamic(selectedObject.value);
  }

  isDynamic.value = value;
  refreshVariablesList();
};

// 配置变更
const onConfigChange = () => {
  if (!canvasEditor || !selectedObject.value || !formData.value.isDynamic) return;

  canvasEditor.setDynamic(selectedObject.value, {
    ...formData.value,
    isDynamic: true,
  });

  refreshVariablesList();
};

// 刷新变量列表
const refreshVariablesList = () => {
  console.log('refreshVariablesList 调用');
  if (!canvasEditor) {
    console.log('canvasEditor 不存在');
    return;
  }

  // 检查是否有 getDynamicVariables 方法
  if (typeof canvasEditor.getDynamicVariables !== 'function') {
    console.log('canvasEditor 没有 getDynamicVariables 方法');
    dynamicVariables.value = [];
    return;
  }

  const variables = canvasEditor.getDynamicVariables();
  console.log('刷新动态变量列表:', variables);
  dynamicVariables.value = variables || [];
};

// 定位到变量对应的元素
const locateVariable = (variable: DynamicVariable) => {
  if (!canvasEditor) return;
  const canvas = canvasEditor.fabricCanvas;
  if (!canvas) return;

  const objects = canvas.getObjects();
  const target = objects.find((obj) => {
    const config = canvasEditor.getDynamicConfig(obj);
    return config?.isDynamic && config.variableName === variable.variableName;
  });

  if (target) {
    canvas.setActiveObject(target);
    canvas.renderAll();
  }
};

// 监听选中事件
const onSelectionCreated = () => checkDynamic();
const onSelectionUpdated = () => checkDynamic();
const onSelectionCleared = () => {
  selectedObject.value = null;
  isDynamic.value = false;
};

// 监听动态变量事件
const onDynamicAdded = () => {
  console.log('动态变量已添加事件');
  nextTick(() => {
    refreshVariablesList();
    checkDynamic();
  });
};

const onDynamicRemoved = () => {
  console.log('动态变量已移除事件');
  nextTick(() => {
    refreshVariablesList();
    checkDynamic();
  });
};

// 生命周期
onMounted(() => {
  console.log('DynamicVariablePanel onMounted');
  console.log('canvasEditor:', canvasEditor);

  if (!canvasEditor) {
    console.log('canvasEditor 不存在，延迟初始化');
    // 延迟初始化，等待 canvasEditor 准备好
    setTimeout(() => {
      console.log('延迟初始化 canvasEditor:', canvasEditor);
      if (canvasEditor?.fabricCanvas) {
        initEvents();
      }
    }, 1000);
    return;
  }

  const canvas = canvasEditor.fabricCanvas;
  console.log('fabricCanvas:', canvas);
  if (!canvas) {
    console.log('fabricCanvas 不存在，延迟初始化');
    setTimeout(() => {
      console.log('延迟初始化 fabricCanvas:', canvasEditor?.fabricCanvas);
      if (canvasEditor?.fabricCanvas) {
        initEvents();
      }
    }, 1000);
    return;
  }

  initEvents();
});

// 初始化事件监听
const initEvents = () => {
  if (!canvasEditor?.fabricCanvas) {
    console.log('initEvents: fabricCanvas 不存在');
    return;
  }

  const canvas = canvasEditor.fabricCanvas;
  console.log('初始化事件监听', canvas);

  // 移除旧的事件监听器（如果存在）
  canvas.off('selection:created', onSelectionCreated);
  canvas.off('selection:updated', onSelectionUpdated);
  canvas.off('selection:cleared', onSelectionCleared);

  // 添加新的事件监听器
  canvas.on('selection:created', onSelectionCreated);
  canvas.on('selection:updated', onSelectionUpdated);
  canvas.on('selection:cleared', onSelectionCleared);

  // 初始化检查
  console.log('执行初始 checkDynamic');
  checkDynamic();
  refreshVariablesList();
};

onUnmounted(() => {
  console.log('DynamicVariablePanel onUnmounted');
  if (!canvasEditor?.fabricCanvas) return;

  const canvas = canvasEditor.fabricCanvas;
  canvas.off('selection:created', onSelectionCreated);
  canvas.off('selection:updated', onSelectionUpdated);
  canvas.off('selection:cleared', onSelectionCleared);
});
</script>

<style lang="less" scoped>
.dynamic-variable-panel {
  padding: 16px;

  .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    font-weight: 600;
  }

  .current-object-config {
    margin-bottom: 16px;
  }

  .config-form {
    :deep(.ivu-form-item) {
      margin-bottom: 12px;
    }
  }

  .no-selection {
    text-align: center;
    color: #999;
    padding: 40px 0;
  }

  .all-variables {
    .variables-list {
      max-height: 300px;
      overflow-y: auto;

      .variable-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 8px 12px;
        margin-bottom: 8px;
        background: #f5f5f5;
        border-radius: 4px;

        .variable-main {
          flex: 1;
          min-width: 0;
        }

        .variable-info {
          display: flex;
          align-items: center;
          gap: 8px;

          .variable-name {
            font-size: 14px;
          }
        }

        .variable-remark {
          display: flex;
          align-items: center;
          gap: 4px;
          margin-top: 6px;
          margin-left: 28px;
          font-size: 12px;
          color: #666;

          .ivu-icon {
            color: #1890ff;
            flex-shrink: 0;
          }
        }

        .variable-actions {
          flex-shrink: 0;
          margin-left: 12px;
        }
      }
    }
  }
}
</style>
