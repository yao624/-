<template>
  <div>
    <a-button type="primary" @click="onCreate">{{ t('Add') }}</a-button>

    <a-modal
      v-model:open="open"
      :title="t('pages.add')"
      @cancel="handleCancel"
      @ok="handleOk"
      :confirm-loading="loading"
      :width="800"
    >
      <a-form :label-col="{ span: 4 }" name="link_add_form" :model="modelRef">
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
import { defineComponent, reactive, ref } from 'vue';
import { message, Form } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import type { LinkModel } from '@/utils/fb-interfaces';
import { addLinksOneApi } from '@/api/links';

export default defineComponent({
  name: 'AddItem',
  emits: ['confirm:saved'],
  setup(_, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const open = ref(false);
    const useForm = Form.useForm;

    const modelRef = reactive<LinkModel>({
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

    const { resetFields, validate } = useForm(modelRef, reactive({ link: urlRules }));

    const onCreate = () => {
      open.value = true;
    };

    const handleCancel = () => {
      resetFields();
      open.value = false;
    };

    const handleOk = () => {
      validate().then(() => {
        loading.value = true;
        addLinksOneApi({ ...modelRef })
          .then(() => {
            message.success(t('pages.opSuccessfully'));
            emit('confirm:saved');
            resetFields();
            open.value = false;
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
      open,
      loading,
      modelRef,
      onCreate,
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
