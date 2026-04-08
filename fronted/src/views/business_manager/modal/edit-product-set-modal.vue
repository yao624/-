<template>
  <a-form
    ref="formRef"
    :model="formState"
    name="basic"
    :label-col="{ span: 8 }"
    :wrapper-col="{ span: 16 }"
    autocomplete="off"
  >
    <a-form-item label="Name" name="name" :rules="rules.name">
      <a-input v-model:value="formState.name"></a-input>
    </a-form-item>

    <a-form-item label="Object" name="object" :rules="rules.object">
      <a-select
        v-model:value="formState.object"
        :options="keyOptions"
        @change="onKeyChange"
      ></a-select>
    </a-form-item>
    <a-form-item label="Condition" name="condition" :rules="rules.condition">
      <a-select
        v-model:value="formState.condition"
        :options="operatorOptions"
        @change="onOperatorChange"
      ></a-select>
    </a-form-item>
    <a-form-item label="Product(s)" name="products" :rules="rules.products">
      <a-select
        :mode="formState.condition === 'is_any' ? 'multiple' : undefined"
        v-model:value="formState.products"
        :options="selectedKeyOptions"
        placeholder="Select values"
        :allowClear="true"
        style="width: 100%"
      ></a-select>
    </a-form-item>
  </a-form>
</template>

<script lang="ts">
import { ref, computed, watchEffect, reactive, defineComponent } from 'vue';
import type { PropType } from 'vue';
// 确保这个路径是正确的，或者如果 TagModel 结构简单，可以直接在此定义
import type { Tag as TagModel } from '@/utils/fb-interfaces';
import { Form } from 'ant-design-vue';
import type { Rule } from 'ant-design-vue/es/form';

// --- 类型定义 ---

// 成功提交时要返回的数据结构
interface SubmitPayload {
  name: string;
  filter: FilterObject;
}

// Filter 结构
interface FilterObject {
  [key: string]: {
    [operator: string]: any | any[];
  };
}

type ProductsValue = any[] | string | number | null | undefined;
// 表单状态结构
interface FormState {
  name: string;
  object: string;
  condition: string;
  products: ProductsValue; // 或者更具体的类型，如 (string | number)[]
}

// Tag 结构 (如果 TagModel 导入不方便，可以在此定义)
// interface TagModel {
//   id: string;
//   name: string;
//   // 其他可能的属性...
// }

// Product 结构
interface Product {
  retailer_id: string | number;
  product_item_id: string | number; // 可能需要，取决于业务逻辑
  source_id: string | number; // 用于下拉选项的值
  tags: TagModel[]; // 使用 TagModel 类型
  // 其他可能的属性...
}

export default defineComponent({
  name: 'EditProductSetModal', // 给组件一个名字
  props: {
    name: String,
    filter: {
      type: Object as PropType<FilterObject | null | undefined>,
      default: () => ({}),
    },
    productList: {
      type: Array as PropType<Product[]>,
      default: () => [],
    },
  },
  // 如果父组件需要响应子组件事件，可以在这里声明
  // emits: ['update:filter'], // 示例

  setup(props, { expose }) {
    // 接收 expose (虽然主要依赖 return)
    const formRef = ref();
    const useForm = Form.useForm; // Antd useForm hook

    // --- 下拉选项常量 ---
    const keyOptions = [
      { label: 'Retailer ID', value: 'retailer_id' },
      { label: 'Product Item ID', value: 'product_item_id' }, // 这个 value 会触发使用 source_id
    ];

    const operatorOptions = [
      { label: 'is_any', value: 'is_any' }, // 多选
      { label: 'eq', value: 'eq' }, // 单选
    ];

    // --- 表单响应式状态 ---
    const formState = reactive<FormState>({
      name: '',
      object: keyOptions[0]?.value || '',
      condition: operatorOptions[0]?.value || '',
      products: null,
    });

    // --- 验证规则 ---
    const validateProducts = async (_rule: Rule, value: ProductsValue) => {
      // 参数类型改为联合类型
      const currentCondition = formState.condition;
      console.log(
        'Validator triggered. Condition:',
        currentCondition,
        'Value type:',
        typeof value,
        'Value:',
        value,
      );

      if (currentCondition === 'eq') {
        // eq 条件：检查值是否存在且不为空字符串
        if (value === null || value === undefined || value === '') {
          return Promise.reject('请选择一个产品');
        }
        return Promise.resolve();
      } else {
        // is_any 条件
        // is_any 条件：检查是否为非空数组
        if (!Array.isArray(value) || value.length === 0) {
          return Promise.reject('请至少选择一个产品');
        }
        return Promise.resolve();
      }
    };

    const rules = reactive({
      name: [{ required: true, message: '请输入规则集名称', trigger: 'blur' }],
      object: [{ required: true, message: '请选择对象', trigger: 'change' }],
      condition: [{ required: true, message: '请选择条件', trigger: 'change' }],
      products: [{ required: true, validator: validateProducts, trigger: 'change' }],
    });

    // --- Antd Form Hook 实例 ---
    const { validate, validateInfos, resetFields, clearValidate } = useForm(formState, rules);

    // --- 事件处理器 ---
    const onKeyChange = () => {
      formState.products = []; // 对象变化，清空产品
      clearValidate('products'); // 清除验证状态
    };

    const onOperatorChange = (newCondition: string) => {
      const currentProductsState = formState.products;
      formState.condition = newCondition;

      if (newCondition === 'eq') {
        if (Array.isArray(currentProductsState) && currentProductsState.length > 0) {
          formState.products = currentProductsState[0];
        } else if (Array.isArray(currentProductsState)) {
          formState.products = null;
        }
      } else {
        // is_any
        if (currentProductsState === null || currentProductsState === undefined) {
          formState.products = [];
        } else if (!Array.isArray(currentProductsState)) {
          formState.products = [currentProductsState];
        }
      }
      clearValidate('products');
    };

    // --- 辅助函数 ---
    const formatLabelWithTags = (id: string | number, tags: TagModel[] | undefined): string => {
      let tagString = '';
      if (Array.isArray(tags) && tags.length > 0) {
        const tagNames = tags.map(tag => tag.name).filter(name => name);
        if (tagNames.length > 0) {
          tagString = ` (${tagNames.join(', ')})`;
        }
      }
      return `${id}${tagString}`;
    };

    // --- 计算属性 (产品下拉选项) ---
    const selectedKeyOptions = computed(() => {
      if (!Array.isArray(props.productList)) {
        console.warn('productList prop is not an array:', props.productList);
        return [];
      }

      const options: { label: string; value: string | number }[] = [];
      const processedIds = new Set<string | number>();

      for (const product of props.productList) {
        let id: string | number | undefined;
        const tags = product.tags;

        if (formState.object === 'retailer_id') {
          id = product.retailer_id;
        } else if (formState.object === 'product_item_id') {
          id = product.source_id;
        }

        // --- 增强检查：加入对空字符串的判断 ---
        if (id !== undefined && id !== null && id !== '' && !processedIds.has(id)) {
          const label = formatLabelWithTags(id, tags);
          options.push({ label: label, value: id });
          processedIds.add(id);
        } else if (id !== undefined && id !== null && id !== '') {
          // (可选日志)
          // console.log('Skipping duplicate ID:', id);
        } else {
          // (可选日志) 打印出无效或空的 ID 帮助调试数据源
          // console.log('Skipping invalid or empty ID:', id, 'from product:', JSON.stringify(product));
        }
      }
      return options;
    });

    // --- watchEffect (同步 props 到 formState) ---
    watchEffect(() => {
      formState.name = props.name || '';
      const currentFilter = props.filter;
      if (
        currentFilter &&
        typeof currentFilter === 'object' &&
        Object.keys(currentFilter).length > 0
      ) {
        const key = Object.keys(currentFilter)[0];
        if (
          key &&
          keyOptions.some(opt => opt.value === key) &&
          currentFilter[key] &&
          typeof currentFilter[key] === 'object'
        ) {
          const operator = Object.keys(currentFilter[key])[0];
          if (operator && operatorOptions.some(opt => opt.value === operator)) {
            const filterValue = currentFilter[key][operator];
            formState.object = key;
            formState.condition = operator;
            if (operator === 'eq') {
              if (filterValue === undefined || filterValue === null) {
                formState.products = null;
              } else if (Array.isArray(filterValue)) {
                formState.products = filterValue.length > 0 ? filterValue[0] : null;
              } else {
                formState.products = filterValue;
              }
            } else {
              // is_any
              if (filterValue === undefined || filterValue === null) {
                formState.products = [];
              } else {
                formState.products = Array.isArray(filterValue) ? filterValue : [filterValue];
              }
            }
          } else {
            /* 处理 operator 无效 */
            formState.products = [];
            formState.condition = operatorOptions[0]?.value || '';
          }
        } else {
          /* 处理 key 无效 */
          formState.products = [];
          formState.object = keyOptions[0]?.value || '';
          formState.condition = operatorOptions[0]?.value || '';
        }
      } else {
        /* 处理 filter 无效 */
        formState.products = [];
        formState.object = keyOptions[0]?.value || '';
        formState.condition = operatorOptions[0]?.value || '';
      }
    });

    // --- 需要暴露给父组件的方法 ---
    const getFormDataAndValidate = async (): Promise<SubmitPayload | null> => {
      try {
        await validate(); // 触发表单验证
        console.log('Validation successful.');
        // 构造 filter
        const constructedFilter: FilterObject = {
          [formState.object]: {
            [formState.condition]: formState.products,
          },
        };
        // 返回数据
        return {
          name: formState.name,
          filter: constructedFilter,
        };
      } catch (errorInfo) {
        console.error('Validation failed:', errorInfo);
        return null; // 验证失败返回 null
      }
    };

    // --- 使用 expose (可选，但保留作为标记) ---
    // 虽然主要依赖 return 来暴露，保留 expose 调用可以表明意图
    expose({
      getFormDataAndValidate,
      resetFields,
    });

    // --- 返回模板需要的数据和父组件需要调用的方法 ---
    return {
      // 模板绑定
      formRef,
      formState,
      rules,
      keyOptions,
      operatorOptions,
      selectedKeyOptions,
      validateInfos,
      // 模板事件
      onKeyChange,
      onOperatorChange,

      // 父组件调用 (关键)
      getFormDataAndValidate,
      resetFields,
    };
  },
});
</script>
