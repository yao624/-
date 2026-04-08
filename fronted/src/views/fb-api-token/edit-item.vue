<template>
  <div>
    <a-modal
      :open="open"
      :title="t('Edit')"
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
        <a-form-item name="name" :label="t('Type')" :rules="[{ required: true, type: 'number' }]">
          <a-radio-group v-model:value="formattedTokenType">
            <a-radio :value="'1'">BM</a-radio>
            <a-radio :value="'3'">BM(2)</a-radio>
            <a-radio :value="'2'">App</a-radio>
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
import type { PropType } from 'vue';
import { computed, defineComponent, reactive, ref, toRaw, watchEffect } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { Form } from 'ant-design-vue';
import type { FbApiTokenModel } from '@/utils/fb-interfaces';
import { updateFbApiToken } from '@/api/fb_api_token';

export default defineComponent({
  name: 'EditItem',
  components: {},
  props: {
    open: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<FbApiTokenModel | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const inputValue = ref('');
    const useForm = Form.useForm;

    const modelRef = reactive<FbApiTokenModel>({
      id: '',
      active: '1',
    });

    const rulesRef = reactive({});

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model) as FbApiTokenModel;
        modelRef.id = raw.id;
        modelRef.active = raw.active ? '1' : '0';
        modelRef.name = raw.name;
        modelRef.bm_id = raw.bm_id;
        modelRef.token = raw.token;
        modelRef.token_type = raw.token_type;
        modelRef.app = raw.app;
        modelRef.notes = raw.notes;
      }
    });

    const { resetFields, validate } = useForm(modelRef, rulesRef);

    const onCreate = () => {};

    const handleCancel = () => {
      emit('cancel');
    };

    const handleOk = () => {
      // 在这里处理确认逻辑
      loading.value = true;
      const params = {
        id: modelRef.id,
        active: modelRef.active === '1' ? true : false,
        name: modelRef.name,
        bm_id: modelRef.bm_id,
        token: modelRef.token,
        token_type: modelRef.token_type,
        app: modelRef.app,
        notes: modelRef.notes,
      };
      validate().then(() => {
        updateFbApiToken(params)
          .then(() => {
            emit('ok');
            message.success(t('pages.opSuccessfully'));
          })
          .finally(() => {
            resetFields();
            loading.value = false;
          });
      });
    };

    const formattedTokenType = computed({
      get: () => String(modelRef.token_type), // 从整数转换为字符串
      set: (value: string) => {
        modelRef.token_type = Number(value); // 将字符串转换回整数
      },
    });

    return {
      inputValue,
      loading,
      onCreate,
      handleCancel,
      handleOk,
      t,
      modelRef,
      formattedTokenType,
    };
  },
});
</script>
