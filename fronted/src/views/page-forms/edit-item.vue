<template>
  <div>
    <a-modal
      :open="open"
      :title="t('Edit notes')"
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
        <a-form-item name="name" :label="t('Notes')" :rules="[{ required: false }]">
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
import type { FbPage } from '@/utils/fb-interfaces';
import { updatePageFormNote } from '@/api/pages';

export default defineComponent({
  name: 'EditItem',
  components: {},
  props: {
    open: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<FbPage | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const inputValue = ref('');
    const useForm = Form.useForm;

    const modelRef = reactive<FbPage>({
      id: '',
      notes: '',
    });

    const rulesRef = reactive({});

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.id = raw.id;
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
      validate().then(() => {
        updatePageFormNote(modelRef.id, modelRef.notes)
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
