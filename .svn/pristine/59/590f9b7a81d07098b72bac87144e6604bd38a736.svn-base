<template>
  <div>
    <a-modal
      :open="open"
      :title="t('Edit copywriting')"
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
        <a-form-item name="id" :label="t('pages.proxies.notes')" style="display: none">
          <a-input v-model:value="modelRef.id" />
        </a-form-item>
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
import type { PropType } from 'vue';
import { defineComponent, reactive, ref, toRaw, watchEffect } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { Form } from 'ant-design-vue';
import { addCopywritingsOneApi } from '@/api/copywritings';
import type { CopywritingModel } from '@/utils/fb-interfaces';

export default defineComponent({
  name: 'EditItem',
  components: {},
  props: {
    open: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<CopywritingModel | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const inputValue = ref('');
    const useForm = Form.useForm;

    const modelRef = reactive<CopywritingModel>({
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

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.id = raw.id;
        modelRef.primary_text = raw.primary_text;
        modelRef.headline = raw.headline;
        modelRef.description = raw.description;

        console.log('model ref');
        console.log(modelRef);
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
      validate().then(() => {
        const params = {
          ...modelRef,
        };
        addCopywritingsOneApi(params)
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

    return {
      inputValue,
      loading,
      onCreate,
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
