<template>
  <div>
    <a-button type="primary" @click="onCreate">{{ t('Add') }}</a-button>

    <a-modal
      v-model:open="open"
      :title="t('Add Copywriting')"
      @cancel="handleCancel"
      @ok="handleOk"
      :confirm-loading="loading"
      :width="800"
    >
      <a-form
        ref="formRef"
        :label-col="{ span: 4 }"
        name="dynamic_form_nest_item"
        :model="modelRef"
      >
        <a-form-item name="name" :label="t('Primary text')" :rules="[{ required: false }]">
          <a-textarea v-model:value="modelRef.primary_text" />
        </a-form-item>
        <a-form-item name="name" :label="t('Headline')" :rules="[{ required: false }]">
          <a-input v-model:value="modelRef.headline" />
        </a-form-item>
        <a-form-item name="name" :label="t('Description')" :rules="[{ required: false }]">
          <a-textarea v-model:value="modelRef.description" />
        </a-form-item>
        <a-form-item name="notes" :label="t('pages.proxies.notes')">
          <a-input v-model:value="modelRef.notes" />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script lang="ts">
import { defineComponent, reactive, ref } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { Form } from 'ant-design-vue';
import { addCopywritingsOneApi } from '@/api/copywritings';

export default defineComponent({
  name: 'AddItem',
  components: {},
  emits: ['confirm:saved'],
  setup(_, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const inputValue = ref('');
    const formRef = ref<any>();
    const open = ref(false);
    const useForm = Form.useForm;

    const modelRef = reactive({
      primary_text: '',
      headline: '',
      description: '',
      notes: '',
    });

    const rulesRef = reactive({
      // name: [
      //   {
      //     required: true,
      //     message: 'Please input name',
      //   },
      // ],
    });

    const { resetFields, validate } = useForm(modelRef, rulesRef);

    const onCreate = () => {
      showModal();
    };

    const showModal = () => {
      open.value = true;
    };

    const handleCancel = () => {
      open.value = false;
    };

    const handleOk = () => {
      // 在这里处理确认逻辑
      loading.value = true;
      console.log('formRef: ', formRef);
      validate().then(() => {
        const params = {
          ...modelRef,
        };
        addCopywritingsOneApi(params)
          .then(() => {
            emit('confirm:saved');
            message.success(t('pages.opSuccessfully'));
          })
          .finally(() => {
            resetFields();
            loading.value = false;
            open.value = false;
          });
      });
    };

    return {
      open,
      inputValue,
      loading,
      onCreate,
      showModal,
      handleCancel,
      handleOk,
      t,
      modelRef,
    };
  },
});
</script>

<style scoped>
/* 可根据需要添加样式 */
</style>
