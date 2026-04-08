<template>
  <a-modal
    :open="open"
    :title="t('Batch Set Topup')"
    @ok="handleOk"
    @cancel="handleCancel"
    :confirmLoading="loading"
  >
    <div class="topup-modal-content">
      <p>{{ t('Set topup status for selected ad accounts') }}</p>
      <a-form ref="formRef" :model="formState" layout="vertical">
        <a-form-item :label="t('Topup Status')" name="value">
          <a-radio-group v-model:value="formState.value">
            <a-radio :value="true">{{ t('Enable Topup') }}</a-radio>
            <a-radio :value="false">{{ t('Disable Topup') }}</a-radio>
          </a-radio-group>
        </a-form-item>
      </a-form>

      <div class="selected-accounts">
        <h4>{{ t('Selected Ad Accounts') }} ({{ adAccounts.length }})</h4>
        <div class="account-list">
          <a-tag v-for="account in adAccounts" :key="account.id">
            {{ account.name }} ({{ account.source_id }})
          </a-tag>
        </div>
      </div>
    </div>
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, ref, reactive, watch } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { toggleTopup } from '@/api/fb_ad_accounts';

export default defineComponent({
  name: 'TopupModal',
  props: {
    open: {
      type: Boolean,
      default: false,
    },
    adAccounts: {
      type: Array as () => any[],
      default: () => [],
    },
  },
  emits: ['update:open', 'success'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const formRef = ref();

    const formState = reactive({
      value: true, // 默认启用
    });

    // 重置表单
    const resetForm = () => {
      formState.value = true;
      formRef.value?.resetFields();
    };

    // 监听 open 状态变化
    watch(
      () => props.open,
      (newVal) => {
        if (newVal) {
          resetForm();
        }
      },
    );

    const handleOk = async () => {
      try {
        await formRef.value.validate();
        loading.value = true;

        await toggleTopup({
          source_ids: props.adAccounts.map(account => account.source_id),
          value: formState.value,
        });

        message.success(t('Topup status updated successfully'));
        emit('success');
        handleCancel();
      } catch (error) {
        console.error('Update topup status failed:', error);
        message.error(t('Operation failed'));
      } finally {
        loading.value = false;
      }
    };

    const handleCancel = () => {
      emit('update:open', false);
      resetForm();
    };

    return {
      t,
      loading,
      formRef,
      formState,
      handleOk,
      handleCancel,
    };
  },
});
</script>

<style scoped>
.topup-modal-content {
  padding: 16px 0;
}

.selected-accounts {
  margin-top: 24px;
  padding: 16px;
  background-color: #f5f5f5;
  border-radius: 6px;
}

.selected-accounts h4 {
  margin-bottom: 12px;
  color: #262626;
  font-weight: 600;
}

.account-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  max-height: 120px;
  overflow-y: auto;
}

.account-list .ant-tag {
  margin: 0;
  font-size: 12px;
}
</style>
