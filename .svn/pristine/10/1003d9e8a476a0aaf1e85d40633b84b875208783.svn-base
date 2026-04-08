<template>
  <a-modal
    :open="open"
    :title="t('Set spend cap')"
    @ok="handleOk"
    @cancel="handleCancel"
    :confirmLoading="loading"
  >
    <a-form ref="formRef" :model="formState" layout="vertical">
      <a-form-item :label="t('Cap Type')" name="cap_type">
        <a-radio-group v-model:value="formState.cap_type">
          <a-radio value="amount">{{ t('Set specific amount') }}</a-radio>
          <a-radio value="reset">{{ t('Reset') }}</a-radio>
          <a-radio value="remove">{{ t('Remove') }}</a-radio>
        </a-radio-group>
      </a-form-item>

      <a-form-item
        v-if="formState.cap_type === 'amount'"
        :label="t('Amount')"
        name="cap_value"
        :rules="[
          { required: true, message: t('Please enter the amount') },
          { type: 'number', min: 0, message: t('Amount must be a positive number') },
        ]"
      >
        <a-input-number
          v-model:value="formState.cap_value"
          :precision="2"
          :min="0"
          style="width: 100%"
          :placeholder="t('Please enter the amount')"
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script lang="ts">
import { defineComponent, ref, reactive, watch } from 'vue';
import { message } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { setAccountSpendCap } from '@/api/fb_ad_accounts';

export default defineComponent({
  name: 'SpendCapModal',
  props: {
    open: {
      type: Boolean,
      default: false,
    },
    adAccountIds: {
      type: Array,
      required: true,
    },
  },
  emits: ['update:open', 'success'],
  setup(props, { emit }) {
    const { t } = useI18n();
    const loading = ref(false);
    const formRef = ref(null);

    const formState = reactive({
      cap_type: 'amount',
      cap_value: undefined,
    });

    // 重置表单
    const resetForm = () => {
      formState.cap_type = 'amount';
      formState.cap_value = undefined;
      formRef.value?.resetFields();
    };

    // 处理确认按钮点击
    const handleOk = async () => {
      // 验证金额
      if (
        formState.cap_type === 'amount' &&
        (formState.cap_value === undefined || formState.cap_value < 0)
      ) {
        message.error(t('Please enter a valid amount'));
        return;
      }

      try {
        loading.value = true;
        const params: any = {
          ids: props.adAccountIds,
          cap_type: formState.cap_type,
        };

        if (formState.cap_type === 'amount') {
          params.cap_value = formState.cap_value;
        }

        await setAccountSpendCap(params);
        message.success(t('Successfully set'));
        emit('success');
        closeModal();
      } catch (error) {
        console.error('Failed to set spend cap:', error);
        message.error(t('Failed to set, please try again'));
      } finally {
        loading.value = false;
      }
    };

    const closeModal = () => {
      resetForm();
      emit('update:open', false);
    };

    const handleCancel = () => {
      closeModal();
    };

    // 监听open变化，重置表单
    watch(
      () => props.open,
      val => {
        if (val) {
          // Modal打开时重置表单
          resetForm();
        }
      },
    );

    return {
      t,
      formRef,
      formState,
      loading,
      handleOk,
      handleCancel,
      resetForm,
    };
  },
});
</script>
