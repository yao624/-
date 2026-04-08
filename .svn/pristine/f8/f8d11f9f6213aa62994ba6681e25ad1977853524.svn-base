<template>
  <div>
    <a-button type="primary" @click="onCreate">{{ t('Add') }}</a-button>

    <a-modal
      v-model:open="open"
      :title="t('Add')"
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
        <a-form-item name="name" :label="t('Status')" :rules="[{ required: true, type: 'string' }]">
          <a-radio-group v-model:value="modelRef.active">
            <a-radio value="1">Active</a-radio>
            <a-radio value="0">InActive</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item name="name" :label="t('Name')" :rules="[{ required: true }]">
          <a-input v-model:value="modelRef.name" />
        </a-form-item>
        <a-form-item name="name" :label="t('Bm ID')" :rules="[{ required: true }]">
          <a-input v-model:value="modelRef.bm_id" />
        </a-form-item>
        <a-form-item name="name" :label="t('Token')" :rules="[{ required: true }]">
          <a-input v-model:value="modelRef.token" />
        </a-form-item>
        <a-form-item name="name" :label="t('Type')" :rules="[{ required: true, type: 'string' }]">
          <a-radio-group v-model:value="modelRef.token_type">
            <a-radio value="1">BM</a-radio>
            <a-radio value="3">BM(2)</a-radio>
            <a-radio value="2">App</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item name="app" :label="'App'" :rules="[{ required: false }]">
          <a-input v-model:value="modelRef.app" />
        </a-form-item>
        <a-form-item name="name" :label="t('Notes')" :rules="[{ required: false }]">
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
import type { FbApiTokenModel } from '@/utils/fb-interfaces';
import { createFbApiTokens } from '@/api/fb_api_token';

export default defineComponent({
  name: 'AddItem',
  components: {},
  emits: ['confirm:saved'],
  setup(_, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const inputValue = ref('');
    const formRef = ref<FbApiTokenModel>();
    const open = ref(false);
    const useForm = Form.useForm;

    const modelRef = reactive<FbApiTokenModel>({
      active: '1',
    });

    const rulesRef = reactive({});

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
        createFbApiTokens(params)
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
