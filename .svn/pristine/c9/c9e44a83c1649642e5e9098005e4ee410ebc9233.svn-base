<template>
  <div>
    <a-button type="primary" @click="onCreate">{{ t('Create') }}</a-button>

    <a-modal
      v-model:visible="isVisible"
      :title="t('Create Fb API Token')"
      @cancel="handleCancel"
      @ok="handleOk"
      :confirm-loading="loading"
    >
      <formly :form-items="formItems" @change:form-data="onFormChange"></formly>
    </a-modal>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, toRaw } from 'vue';
import { Button, Modal, Input, message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import Formly from '@/components/dynamic-form/formly.vue';
import { createFbApiTokens, updateFbApiToken } from '@/api/fb_api_token';

export default defineComponent({
  name: 'AddToken',
  components: {
    'a-button': Button,
    'a-modal': Modal,
    'a-input': Input,
    Formly,
  },
  emits: ['confirm:token-saved'],
  setup(_, { emit }) {
    const { t } = useI18n();
    const isVisible = ref(false);
    const loading = ref(false);
    const inputValue = ref('');
    const tokenId = ref('');

    const formItems = ref([
      {
        label: t('Active'),
        field: 'active',
        options: [
          { label: 'True', value: true },
          { label: 'False', value: false },
        ],
        value: true,
        mode: 'radio',
      },
      { label: t('Bm ID'), field: 'bm_id' },
      { label: t('Name'), field: 'name' },
      { label: t('Token'), field: 'token' },
      { label: t('Notes'), field: 'notes' },
    ]);

    const formData = ref<any>({});

    const onFormChange = data => (formData.value = data);

    const editToken = data => {
      tokenId.value = data.id;
      Object.keys(data).forEach(key => {
        const item: any = formItems.value.find(({ field }) => field === key);
        if (item) {
          item.value = data[key];
        }
      });
      showModal();
    };

    const onCreate = () => {
      tokenId.value = '';
      formItems.value.forEach((item: any) => (item.value = item.field === 'active' ? true : null));
      showModal();
    };

    const showModal = () => {
      isVisible.value = true;
    };

    const handleCancel = () => {
      isVisible.value = false;
    };

    const handleOk = () => {
      // 在这里处理确认逻辑
      loading.value = true;
      if (!tokenId.value) {
        createFbApiTokens(toRaw(formData.value))
          .then(() => emit('confirm:token-saved'))
          .catch(() => message.error(t('Operation failed')))
          .finally(() => {
            loading.value = false;
            isVisible.value = false;
          });
      } else {
        updateFbApiToken({ ...toRaw(formData.value), id: tokenId.value })
          .then(() => emit('confirm:token-saved'))
          .catch(() => message.error(t('Operation failed')))
          .finally(() => {
            loading.value = false;
            isVisible.value = false;
          });
      }
    };

    return {
      isVisible,
      inputValue,
      formItems,
      loading,
      onCreate,
      showModal,
      handleCancel,
      handleOk,
      onFormChange,
      editToken,
      t,
    };
  },
});
</script>

<style scoped>
/* 可根据需要添加样式 */
</style>
