<template>
  <a-modal
    :visible="visible"
    title="创建商品组"
    @ok="handleOk"
    @update:visible="handleUpdateVisible"
    @cancel="handleCancel"
    :confirm-loading="isSubmitting"
    :destroyOnClose="true"
    width="85%"
    style="top: 20px"
    :ok-text="t('common.create')"
    :cancel-text="t('common.cancel')"
    :ok-button-props="{ disabled: formState.productSetList.length === 0 }"
  >
    <a-form ref="formRef" :model="formState" layout="vertical" autocomplete="off">
      <!-- 表头 (可选, 增加清晰度) -->
      <a-row :gutter="16" class="form-header">
        <a-col :span="1"></a-col>
        <!-- 操作列 -->
        <a-col :span="5">{{ t('Name') }}</a-col>
        <!-- 名称 -->
        <a-col :span="4">{{ t('Object') }}</a-col>
        <!-- 过滤对象 -->
        <a-col :span="3">{{ t('Condition') }}</a-col>
        <!-- 条件 -->
        <a-col :span="11">{{ t('Product') }}</a-col>
        <!-- 值 -->
      </a-row>
      <a-divider class="header-divider" />

      <!-- 动态商品组行 -->
      <div v-if="formState.productSetList.length === 0" class="empty-state">
        <a-empty :description="t('Add at least one product set')" />
        <!-- 提示添加 -->
      </div>
      <div v-else>
        <transition-group name="list" tag="div">
          <div
            v-for="(productSet, index) in formState.productSetList"
            :key="productSet.id"
            class="product-set-row"
          >
            <a-row :gutter="16" align="top">
              <!-- 1. 操作列 -->
              <a-col :span="1" class="action-col">
                <a-space direction="vertical">
                  <a-tooltip :title="t('common.delete')">
                    <!-- 删除提示 -->
                    <a-button
                      type="text"
                      danger
                      size="small"
                      @click="removeProductSet(productSet.id)"
                      :disabled="formState.productSetList.length <= 1"
                    >
                      <template #icon><delete-outlined /></template>
                    </a-button>
                  </a-tooltip>
                  <a-tooltip :title="t('Duplicate')">
                    <!-- 复制提示 -->
                    <a-button type="text" size="small" @click="duplicateProductSet(productSet.id)">
                      <template #icon><copy-outlined /></template>
                    </a-button>
                  </a-tooltip>
                </a-space>
              </a-col>

              <!-- 2. 名称 -->
              <a-col :span="5">
                <a-form-item :name="['productSetList', index, 'name']" :rules="rules.name">
                  <a-input v-model:value="productSet.name" :placeholder="t('Enter name')" />
                </a-form-item>
              </a-col>

              <!-- 3. 过滤对象 (Object) -->
              <a-col :span="4">
                <a-form-item
                  :name="['productSetList', index, 'filter_object']"
                  :rules="rules.filter_object"
                >
                  <a-select
                    v-model:value="productSet.filter_object"
                    @change="onFilterObjectChange(productSet)"
                    :placeholder="t('Select object')"
                  >
                    <a-select-option value="product_item_id">product_item_id</a-select-option>
                    <a-select-option value="retailer_id">retailer_id</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>

              <!-- 4. 条件 (Condition) -->
              <a-col :span="3">
                <a-form-item
                  :name="['productSetList', index, 'filter_condition']"
                  :rules="rules.filter_condition"
                >
                  <a-select
                    v-model:value="productSet.filter_condition"
                    @change="onFilterConditionChange(productSet)"
                    :placeholder="t('productSet.selectCondition')"
                  >
                    <a-select-option value="eq">eq</a-select-option>
                    <a-select-option value="is_any">is_any</a-select-option>
                  </a-select>
                </a-form-item>
              </a-col>

              <!-- 5. 值 (Value) - 动态 Select -->
              <a-col :span="11">
                <!-- 验证规则应用在外层 FormItem 上 -->
                <a-form-item
                  :name="['productSetList', index, 'filter_value']"
                  :rules="rules.filter_value"
                >
                  <a-select
                    v-model:value="productSet.filter_value"
                    :options="getOptionsForProductSet(productSet)"
                    :mode="productSet.filter_condition === 'is_any' ? 'multiple' : undefined"
                    :placeholder="t('productSet.selectValue')"
                    allowClear
                    showSearch
                    optionFilterProp="label"
                    style="width: 100%"
                  />
                </a-form-item>
              </a-col>
            </a-row>
            <a-divider v-if="index < formState.productSetList.length - 1" class="row-divider" />
          </div>
        </transition-group>
      </div>

      <!-- 添加按钮 -->
      <a-button type="dashed" block @click="addProductSet" style="margin-top: 16px">
        <template #icon><plus-outlined /></template>
        {{ t('Add new set') }}
        <!-- 添加新项 -->
      </a-button>
    </a-form>

    <template #footer>
      <!-- 标准页脚或自定义 -->
      <div style="text-align: right">
        <a-button @click="handleCancel" style="margin-right: 8px">
          {{ t('Cancel') }}
        </a-button>
        <a-button
          type="primary"
          @click="handleOk"
          :loading="isSubmitting"
          :disabled="formState.productSetList.length === 0"
        >
          {{ t('Create') }}
        </a-button>
      </div>
    </template>
  </a-modal>
</template>

<script lang="ts" setup>
import { ref, reactive, computed, watch } from 'vue';
import {
  Modal as AModal,
  Form as AForm,
  FormItem as AFormItem,
  Input as AInput,
  Button as AButton,
  Row as ARow,
  Col as ACol,
  Divider as ADivider,
  Select as ASelect,
  SelectOption as ASelectOption,
  Tooltip as ATooltip,
  TypographyText as ATypographyText,
  Space as ASpace,
  Empty as AEmpty,
  message,
  type FormInstance, // 引入 Form 实例类型
} from 'ant-design-vue';
import { PlusOutlined, DeleteOutlined, CopyOutlined } from '@ant-design/icons-vue';
import { useI18n } from 'vue-i18n';
import type { PropType } from 'vue';

// 引入或在此定义类型
import type {
  ProductListModel,
  ProductSetFormState,
  ProcessedProductSet,
  SelectOption,
} from '@/utils/fb-interfaces'; // 如果类型放在单独文件，调整路径

// --- Props 和 Emits 定义 ---
const props = defineProps({
  // 控制模态框可见性
  visible: {
    type: Boolean,
    required: true,
  },
  // 传入的商品列表，用于生成选项
  products: {
    type: Array as PropType<ProductListModel[]>, // 使用 PropType 增强类型检查
    required: true,
    default: () => [], // 提供默认空数组，防止 products 未传入时出错
  },
});

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void; // 支持 v-model:visible
  (e: 'submit', productSets: ProcessedProductSet[]): void; // 提交处理后的数据
}>();

// --- Setup 部分 ---
const { t } = useI18n(); // 获取 i18n 翻译函数
const formRef = ref<FormInstance>(); // a-form 的引用
const isSubmitting = ref(false); // 控制提交按钮的加载状态

// 表单状态：包含一个商品组列表
const formState = reactive<{ productSetList: ProductSetFormState[] }>({
  productSetList: [],
});

// --- 计算属性：用于生成 Select 选项 ---

// 辅助函数：格式化商品标签
const formatTags = (tags: Array<{ name: string }>): string => {
  // 如果 tags 存在且不为空，则拼接名称，否则返回空字符串
  return tags && tags.length > 0 ? ` (${tags.map(t => t.name).join(', ')})` : '';
};

// 计算 product_item_id (source_id) 的选项 (使用 computed 进行缓存)
const productItemOptions = computed<SelectOption[]>(() => {
  console.log('重新计算 productItemOptions'); // 用于调试 props.products 变化
  if (!props.products) return []; // 如果 products 未传入，返回空
  // 使用 Map 进行去重，同时保留第一个遇到的标签信息
  const optionsMap = new Map<string, string>(); // key: source_id, value: label
  props.products.forEach(p => {
    // 确保 source_id 存在且尚未添加到 Map 中
    if (p.source_id && !optionsMap.has(p.source_id)) {
      // 构建 label: "source_id (tag1, tag2)"
      const label = `${p.source_id}${formatTags(p.tags)}`;
      optionsMap.set(p.source_id, label);
    }
  });
  // 将 Map 转换为 Ant Design Select 需要的 { value, label } 格式数组
  return Array.from(optionsMap.entries()).map(([value, label]) => ({ value, label }));
});

// 计算 retailer_id 的选项 (使用 computed 进行缓存)
const retailerOptions = computed<SelectOption[]>(() => {
  console.log('重新计算 retailerOptions'); // 用于调试 props.products 变化
  if (!props.products) return [];
  // 同样使用 Map 去重
  const optionsMap = new Map<string, string>(); // key: retailer_id, value: label
  props.products.forEach(p => {
    // 假设 retailer_id 可能在不同商品中重复，但我们只显示第一次遇到的标签
    if (p.retailer_id && !optionsMap.has(p.retailer_id)) {
      // 根据需求决定是否显示 retailer_id 的标签，这里假设需要显示
      const label = `${p.retailer_id}${formatTags(p.tags)}`;
      optionsMap.set(p.retailer_id, label);
    }
  });
  // 转换为 { value, label } 格式数组
  return Array.from(optionsMap.entries()).map(([value, label]) => ({ value, label }));
});

// 函数：根据当前行的状态获取对应的选项列表
const getOptionsForProductSet = (productSet: ProductSetFormState): SelectOption[] => {
  if (productSet.filter_object === 'product_item_id') {
    return productItemOptions.value; // 返回计算好的 product item 选项
  } else if (productSet.filter_object === 'retailer_id') {
    return retailerOptions.value; // 返回计算好的 retailer 选项
  }
  return []; // 如果过滤对象未选或无效，返回空数组
};

// --- 验证规则 ---
const rules = computed(() => ({
  // 使用 computed 保证 i18n 响应性
  name: [
    {
      required: true,
      message: t('validation.required', { field: t('productSet.name') }),
      trigger: 'blur',
      whitespace: true,
    },
  ], // 名称必填
  filter_object: [
    {
      required: true,
      message: t('validation.required', { field: t('productSet.filterObject') }),
      trigger: 'change',
    },
  ], // 过滤对象必选
  filter_condition: [
    {
      required: true,
      message: t('validation.required', { field: t('productSet.condition') }),
      trigger: 'change',
    },
  ], // 条件必选
  filter_value: [
    // 值必填，需要自定义验证器处理单选/多选情况
    {
      required: true,
      validator: async (_rule: any, value: string | string[] | undefined) => {
        // 检查值是否为 undefined, null, 空字符串, 或空数组
        if (
          value === undefined ||
          value === null ||
          value === '' ||
          (Array.isArray(value) && value.length === 0)
        ) {
          // 如果为空，则验证失败
          return Promise.reject(t('validation.required', { field: t('productSet.value') }));
        }
        // 如果有值，则验证通过
        return Promise.resolve();
      },
      trigger: ['change', 'blur'], // 在改变或失焦时触发验证
    },
  ],
}));

// --- 功能函数 ---

// 创建一个空的商品组对象，包含默认值
const createEmptyProductSet = (): ProductSetFormState => ({
  id: Date.now() + Math.random(), // 生成唯一 ID
  name: '',
  filter_object: 'product_item_id', // 默认过滤对象
  filter_condition: 'eq', // 默认条件
  filter_value: undefined, // 默认值未选
});

// 添加一个新的商品组表单行
const addProductSet = () => {
  formState.productSetList.push(createEmptyProductSet());
};

// 移除一个商品组表单行
const removeProductSet = (idToRemove: number) => {
  formState.productSetList = formState.productSetList.filter(ps => ps.id !== idToRemove);
};

// 复制一个商品组表单行
const duplicateProductSet = (idToDuplicate: number) => {
  const setToCopy = formState.productSetList.find(ps => ps.id === idToDuplicate);
  if (setToCopy) {
    const index = formState.productSetList.indexOf(setToCopy);
    // 创建副本，并生成新 ID 和修改名称
    const newSet = {
      ...setToCopy, // 复制所有属性
      id: Date.now() + Math.random(), // 生成新的唯一 ID
      name: `${setToCopy.name} (复制)`, // 添加后缀区分
    };
    // 在原项后面插入副本
    formState.productSetList.splice(index + 1, 0, newSet);
  }
};

// 当过滤对象 (Object) 或条件 (Condition) 改变时，重置值 (Value)
// 这是为了防止选择了一个对象/条件后，保留着之前不兼容的值
const onFilterObjectChange = (productSet: ProductSetFormState) => {
  productSet.filter_value = undefined; // 清空值
};
const onFilterConditionChange = (productSet: ProductSetFormState) => {
  productSet.filter_value = undefined; // 清空值
};

// 处理模态框确认按钮点击：验证表单并处理数据
const handleOk = async () => {
  if (!formRef.value) return; // 确保 form 引用存在
  if (formState.productSetList.length === 0) {
    message.warn(t('prompt.addAtLeastOneSet')); // 提示至少添加一项
    return;
  }

  isSubmitting.value = true; // 设置加载状态
  try {
    // 1. 触发表单验证
    await formRef.value.validate();
    console.log('商品组表单验证成功');

    // 2. 处理数据，转换为 API 需要的格式
    const processedSets: ProcessedProductSet[] = [];
    for (const formItem of formState.productSetList) {
      // 验证已通过，可以安全地断言 filter_value 存在
      const value = formItem.filter_value!; // 使用非空断言

      // 动态构建 filter 对象内部结构 { condition: value }
      const filterContent = {
        [formItem.filter_condition]: value,
      };
      // 动态构建 filter 对象外部结构 { object: { condition: value } }
      const filterWrapper = {
        [formItem.filter_object]: filterContent,
      };

      // 将处理好的数据添加到结果数组
      processedSets.push({
        name: formItem.name,
        // 需要进行类型断言，因为 TS 无法完全推断动态构建的 key
        filter: filterWrapper as ProcessedProductSet['filter'],
      });
    }

    // 3. 验证通过且处理完成，触发 submit 事件，将数据传递给父组件
    console.log('处理后的商品组数据:', processedSets);
    emit('submit', processedSets);
    // 注意：数据提交成功后关闭模态框的操作应该由父组件处理
  } catch (errorInfo) {
    // 捕获验证失败信息
    console.error('商品组表单验证失败:', errorInfo);
    message.error(t('validation.checkForm')); // 提示用户检查表单
  } finally {
    isSubmitting.value = false; // 清除加载状态
  }
};

// 处理模态框取消按钮点击
const handleCancel = () => {
  emit('update:visible', false); // 通知父组件关闭模态框
};

// 处理 v-model:visible 的更新，并在模态框首次可见时添加初始项
const handleUpdateVisible = (value: boolean) => {
  emit('update:visible', value); // 同步父组件状态
  // 当模态框变为可见 (value is true) 且列表为空时，添加一个默认项
  if (value && formState.productSetList.length === 0) {
    addProductSet();
  }
};
</script>

<style scoped>
/* 样式与 create-product-modal 类似，可复用或微调 */
.form-header {
  margin-bottom: 8px;
  padding: 0 8px;
  color: rgba(0, 0, 0, 0.65);
  font-weight: 500;
}
.header-divider {
  margin-top: 0;
  margin-bottom: 16px;
}
.product-set-row {
  margin-bottom: 16px;
}
.row-divider {
  margin-top: 0;
  margin-bottom: 16px;
}
.action-col {
  text-align: center;
  padding-top: 8px;
}
.action-col .ant-space-item {
  margin-bottom: 4px !important;
} /* 调整操作按钮间距 */
.ant-form-item {
  margin-bottom: 0;
} /* 让行更紧凑 */
.empty-state {
  padding: 40px 0;
  text-align: center;
}
/* 列表过渡动画 */
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* 确保 Select 组件足够宽 */
.ant-select {
  width: 100%;
}

/* Select 组件验证失败时的错误样式 */
.ant-form-item-has-error .ant-select-selector {
  border-color: #ff4d4f !important; /* 强制显示红色边框 */
}
</style>
