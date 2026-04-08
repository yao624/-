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
    <a-form-item label="Description" name="description" :rules="rules.description">
      <a-input v-model:value="formState.description"></a-input>
    </a-form-item>
    <a-form-item label="Url" name="url" :rules="rules.url">
      <a-input v-model:value="formState.url"></a-input>
    </a-form-item>
    <a-form-item label="Image url" name="image_url" :rules="rules.image_url">
      <a-input v-model:value="formState.image_url"></a-input>
    </a-form-item>
    <a-form-item label="Price" name="price" :rules="rules.price">
      <a-input v-model:value="formState.price"></a-input>
    </a-form-item>
  </a-form>
</template>

<script lang="ts">
import { ref, watchEffect, reactive, defineComponent, toRaw } from 'vue';
// 确保这个路径是正确的，或者如果 TagModel 结构简单，可以直接在此定义
import { Form } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';

// 表单状态结构
interface FormState {
  name: string;
  description: string;
  url: string;
  image_url: string;
  price: string;
}

export default defineComponent({
  name: 'EditProductModal', // 给组件一个名字
  props: {
    name: String,
    description: String,
    url: String,
    image_url: String,
    price: String,
  },
  // 如果父组件需要响应子组件事件，可以在这里声明
  // emits: ['update:filter'], // 示例

  setup(props, { expose }) {
    // 接收 expose (虽然主要依赖 return)
    const { t } = useI18n();
    const formRef = ref();
    const useForm = Form.useForm; // Antd useForm hook

    // --- 表单响应式状态 ---
    const formState = reactive<FormState>({
      name: '',
      description: '',
      url: '',
      image_url: '',
      price: '',
    });

    const rules = reactive({
      name: [{ required: true, message: t('Please input name'), trigger: 'blur' }],
      description: [{ required: true, message: t('Please input description'), trigger: 'blur' }],
      url: [{ required: true, message: t('Please input url'), trigger: 'blur' }],
      image_url: [{ required: true, message: t('Please input image_url'), trigger: 'blur' }],
      price: [{ required: true, message: t('Please input price'), trigger: 'blur' }],
    });

    // --- Antd Form Hook 实例 ---
    const { validate, validateInfos, resetFields } = useForm(formState, rules);

    // --- watchEffect (同步 props 到 formState) ---
    watchEffect(() => {
      formState.name = props.name || '';
      formState.description = props.description || '';
      formState.url = props.url || '';
      formState.image_url = props.image_url || '';
      formState.price = props.price;
    });

    // --- 需要暴露给父组件的方法 ---
    const getFormDataAndValidate = async (): Promise<FormState | null> => {
      try {
        await validate(); // 触发表单验证
        console.log('Validation successful.');
        // 返回数据
        return toRaw(formState);
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
      validateInfos,

      // 父组件调用 (关键)
      getFormDataAndValidate,
      resetFields,
    };
  },
});
</script>
