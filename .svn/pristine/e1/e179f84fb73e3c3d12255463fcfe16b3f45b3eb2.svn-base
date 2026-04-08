<template>
  <div>
    <a-button type="primary" @click="onCreate">{{ t('Add') }}</a-button>

    <a-modal
      v-model:open="open"
      :title="t('Add Fraud Config')"
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
        <a-form-item name="active" :label="t('Status')" :rules="[{ required: true }]">
          <a-radio-group v-model:value="modelRef.active">
            <a-radio value="1">Active</a-radio>
            <a-radio value="2">InActive</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item name="type" :label="t('Type')" :rules="[{ required: true }]">
          <a-radio-group v-model:value="modelRef.type">
            <a-radio value="domain_whitelist">Domain whitelist</a-radio>
            <a-radio value="url_whitelist">Url whitelist</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item name="valueText" :label="t('Values')" :rules="[{ required: true }]">
          <a-textarea
            v-model:value="modelRef.valueText"
            :rows="6"
            :placeholder="t('Please input one URL per line')"
            style="width: 100%"
          />
        </a-form-item>
        <a-form-item name="actions" :label="t('Actions')" :rules="[{ required: true }]">
          <a-checkbox-group v-model:value="modelRef.actions" mode="tags" style="width: 100%">
            <a-checkbox value="tg_alert">TG Alert</a-checkbox>
            <a-checkbox value="pause_ad">Pause Ad</a-checkbox>
            <a-checkbox value="lock_card">Lock Card</a-checkbox>
          </a-checkbox-group>
        </a-form-item>
        <a-form-item name="excludedAdsText" :label="'Excluded Ads'" :rules="[{ required: false }]">
          <a-textarea
            v-model:value="modelRef.excludedAdsText"
            :rows="4"
            :placeholder="'Please input one Ad ID per line (optional)'"
            style="width: 100%"
          />
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
import type { FraudConfigModel } from '@/utils/fb-interfaces';
import { createFraudConfigs } from '@/api/fraud_config';

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

    const modelRef = reactive<FraudConfigModel>({
      active: '1',
      type: 'domain_whitelist',
      valueText: '',
      excludedAdsText: '',
    });

    const rulesRef = reactive({
      link: [
        {
          required: true,
          message: 'Please input name',
        },
      ],
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
        // 将textarea的内容转换为数组
        const valueArray = modelRef.valueText
          ? modelRef.valueText.split('\n').filter(line => line.trim() !== '')
          : [];

        const excludedAdsArray = modelRef.excludedAdsText
          ? modelRef.excludedAdsText.split('\n').filter(line => line.trim() !== '')
          : [];

        const params = {
          ...modelRef,
          value: valueArray,
          excluded_ads: excludedAdsArray.length > 0 ? excludedAdsArray : null,
        };
        // 删除临时字段
        delete params.valueText;
        delete params.excludedAdsText;

        createFraudConfigs(params)
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
