<template>
  <div>
    <a-modal
      :open="open"
      :title="t('pages.edit')"
      @cancel="handleCancel"
      @ok="handleOk"
      :confirm-loading="loading"
      :width="800"
    >
      <a-form :label-col="{ span: 4 }" name="link_edit_form" :model="modelRef">
        <a-form-item name="link" :label="t('pages.links.link')" :rules="urlRules">
          <a-textarea v-model:value="modelRef.link" :auto-size="{ minRows: 2, maxRows: 4 }" />
        </a-form-item>
        <a-form-item name="notes" :label="t('pages.links.note')">
          <a-input v-model:value="modelRef.notes" />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script lang="ts">
import type { PropType } from 'vue';
import { defineComponent, reactive, ref, toRaw, watchEffect } from 'vue';
import { message, Form } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import type { LinkModel } from '@/utils/fb-interfaces';
import { addLinksOneApi } from '@/api/links';

export default defineComponent({
  name: 'EditItem',
  props: {
    open: {
      type: Boolean,
      required: true,
    },
    model: {
      type: Object as PropType<LinkModel | null>,
      default: () => null,
    },
  },
  emits: ['ok', 'cancel'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const useForm = Form.useForm;

    const modelRef = reactive<LinkModel>({
      id: '',
      link: '',
      notes: '',
    });

    const urlRules = [
      { required: true, message: t('pages.link.not.supported'), trigger: 'blur' },
      {
        pattern: /^(https?|http?|socks5):\/\/[^\s/$.?#].[^\s]*$/i,
        message: t('pages.link.not.supported'),
        trigger: 'blur',
      },
    ];

    watchEffect(() => {
      if (props.model) {
        const raw = toRaw(props.model);
        modelRef.id = raw.id;
        modelRef.link = raw.link;
        modelRef.notes = raw.notes;
      } else {
        modelRef.id = '';
        modelRef.link = '';
        modelRef.notes = '';
      }
    });

    const { resetFields, validate } = useForm(modelRef, reactive({ link: urlRules }));

    const handleCancel = () => {
      resetFields();
      emit('cancel');
    };

    const handleOk = () => {
      validate().then(() => {
        loading.value = true;
        addLinksOneApi({ ...modelRef })
          .then(() => {
            message.success(t('pages.opSuccessfully'));
            resetFields();
            emit('ok');
          })
          .catch(err => {
            message.error(err?.message || t('Operation failed'));
          })
          .finally(() => {
            loading.value = false;
          });
      });
    };

    return {
      loading,
      modelRef,
      handleCancel,
      handleOk,
      t,
      urlRules,
    };
  },
});
</script>

<style scoped>
</style>
